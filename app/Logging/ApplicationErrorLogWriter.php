<?php

declare(strict_types=1);

namespace App\Logging;

use CodeIgniter\Database\BaseConnection;
use Config\Database;
use Config\ErrorLogging;
use JsonException;
use Throwable;

/**
 * Writes application errors to PostgreSQL.
 *
 * Stability rules:
 * - uses a non-shared database connection;
 * - never calls log_message();
 * - never throws to the caller;
 * - does not read application tables;
 * - does not start or join business transactions.
 */
final class ApplicationErrorLogWriter
{
    private ?BaseConnection $database = null;

    private readonly ErrorLogging $configuration;

    private readonly ErrorLogSanitizer $sanitizer;

    public function __construct(
        ?ErrorLogging $configuration = null,
        ?ErrorLogSanitizer $sanitizer = null
    ) {
        $this->configuration =
            $configuration
            ?? config(ErrorLogging::class);

        $this->sanitizer =
            $sanitizer
            ?? new ErrorLogSanitizer();
    }

    /**
     * Persist one application error.
     *
     * @param array<string, mixed> $context
     */
    public function write(
        string $severity,
        string $message,
        array $context = []
    ): bool {
        if (
            !$this->configuration
                ->databaseEnabled
        ) {
            return true;
        }

        try {
            $database = $this->database();

            $sanitizedContext =
                $this->sanitizer
                ->sanitize($context);

            $encodedContext =
                $this->encodeContext(
                    $sanitizedContext
                );

            $database
                ->table(
                    'application_error_logs'
                )
                ->insert([
                    'request_id' =>
                    $this->requestId(),

                    'severity' =>
                    $this->normalizeSeverity(
                        $severity
                    ),

                    'message' =>
                    mb_substr(
                        trim($message),
                        0,
                        $this->configuration
                            ->messageMaxLength
                    ),

                    /*
                     * PostgreSQL JSONB accepts the encoded JSON string.
                     */
                    'context' =>
                    $encodedContext,

                    'environment' =>
                    $this->environment(),

                    'source' =>
                    $this->source(),

                    'request_method' =>
                    $this->requestMethod(),

                    'request_uri' =>
                    $this->requestUri(),

                    /*
                     * Do not initialize a session from the logger. Authenticated
                     * IDs can be supplied explicitly through structured context
                     * when a caller already has them.
                     */
                    'member_user_id' =>
                    $this->positiveIntegerOrNull(
                        $context['member_user_id']
                            ?? null
                    ),

                    'admin_user_id' =>
                    $this->positiveIntegerOrNull(
                        $context['admin_user_id']
                            ?? null
                    ),

                    'created_at' =>
                    gmdate(
                        'Y-m-d H:i:s'
                    )
                        . '+00:00',
                ]);

            return true;
        } catch (Throwable $exception) {
            /*
             * Do not use log_message() here. Doing so would invoke this writer
             * again and could create an infinite recursion loop.
             *
             * Returning true allows subsequent handlers, including FileHandler,
             * to continue processing the original error.
             */
            error_log(
                sprintf(
                    'ApplicationErrorLogWriter failed: %s',
                    $exception->getMessage()
                )
            );

            return true;
        }
    }

    /**
     * Create a separate, non-shared database connection.
     */
    private function database(): BaseConnection
    {
        if (
            $this->database
            instanceof BaseConnection
        ) {
            return $this->database;
        }

        /*
         * shared=false prevents this insert from joining a failed or aborted
         * business transaction on the application's shared connection.
         */
        $this->database = Database::connect(
            null,
            false
        );

        return $this->database;
    }

    /**
     * Encode and bound the context JSON.
     *
     * @param array<string, mixed> $context
     */
    private function encodeContext(
        array $context
    ): string {
        try {
            $encoded = json_encode(
                $context,
                JSON_THROW_ON_ERROR
                    | JSON_UNESCAPED_SLASHES
                    | JSON_UNESCAPED_UNICODE
            );
        } catch (JsonException) {
            $encoded = json_encode([
                '_error' =>
                'Context could not be encoded.',
            ]);

            if (!is_string($encoded)) {
                return '{}';
            }
        }

        if (
            mb_strlen($encoded)
            <= $this->configuration
            ->contextMaxLength
        ) {
            return $encoded;
        }

        $fallback = json_encode([
            '_truncated' =>
            'Context exceeded the configured size limit.',
        ]);

        return is_string($fallback)
            ? $fallback
            : '{}';
    }

    /**
     * Resolve the correlation identifier used by http_logs.
     */
    private function requestId(): ?string
    {
        $requestId = trim(
            (string) (
                $_SERVER['HTTP_X_REQUEST_ID']
                ?? $_SERVER['REQUEST_ID']
                ?? ''
            )
        );

        if ($requestId === '') {
            return null;
        }

        return mb_substr(
            $requestId,
            0,
            64
        );
    }

    /**
     * Resolve the current environment to a database-supported value.
     */
    private function environment(): string
    {
        $environment = defined(
            'ENVIRONMENT'
        )
            ? mb_strtolower(
                trim(
                    (string) ENVIRONMENT
                )
            )
            : 'production';

        return in_array(
            $environment,
            [
                'development',
                'testing',
                'qa',
                'production',
            ],
            true
        )
            ? $environment
            : 'production';
    }

    /**
     * Resolve whether the current execution is Web, CLI, cron or queue.
     */
    private function source(): string
    {
        if (PHP_SAPI !== 'cli') {
            return 'WEB';
        }

        $scriptName = mb_strtolower(
            basename(
                (string) (
                    $_SERVER['SCRIPT_NAME']
                    ?? $_SERVER['argv'][0]
                    ?? ''
                )
            )
        );

        if (
            str_contains(
                $scriptName,
                'cron'
            )
        ) {
            return 'CRON';
        }

        if (
            str_contains(
                $scriptName,
                'queue'
            )
            || str_contains(
                $scriptName,
                'worker'
            )
        ) {
            return 'QUEUE';
        }

        return 'CLI';
    }

    private function requestMethod(): ?string
    {
        if (PHP_SAPI === 'cli') {
            return null;
        }

        $method = mb_strtoupper(
            trim(
                (string) (
                    $_SERVER['REQUEST_METHOD']
                    ?? ''
                )
            )
        );

        if ($method === '') {
            return null;
        }

        return mb_substr(
            $method,
            0,
            10
        );
    }

    private function requestUri(): ?string
    {
        if (PHP_SAPI === 'cli') {
            return null;
        }

        $requestUri = trim(
            (string) (
                $_SERVER['REQUEST_URI']
                ?? ''
            )
        );

        if ($requestUri === '') {
            return null;
        }

        /*
         * Remove the query string. It may contain tokens, search values or
         * other personal information. http_logs remains the authoritative
         * request-detail source.
         */
        $path = parse_url(
            $requestUri,
            PHP_URL_PATH
        );

        $resolvedPath = is_string($path)
            ? $path
            : '';

        if ($resolvedPath === '') {
            return null;
        }

        return mb_substr(
            $resolvedPath,
            0,
            $this->configuration
                ->requestUriMaxLength
        );
    }

    private function normalizeSeverity(
        string $severity
    ): string {
        $normalized = mb_strtolower(
            trim($severity)
        );

        return in_array(
            $normalized,
            [
                'emergency',
                'alert',
                'critical',
                'error',
                'warning',
                'notice',
                'info',
                'debug',
            ],
            true
        )
            ? $normalized
            : 'error';
    }

    private function positiveIntegerOrNull(
        mixed $value
    ): ?int {
        if (!is_numeric($value)) {
            return null;
        }

        $resolved = (int) $value;

        return $resolved > 0
            ? $resolved
            : null;
    }
}
