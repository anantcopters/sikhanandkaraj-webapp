<?php

declare(strict_types=1);

namespace App\Services\Logging;

use App\Models\HttpRequestLogModel;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Router\Router;
use JsonException;
use Throwable;

/**
 * Builds and persists one sanitized technical log per selected request.
 */
final class HttpRequestLogService
{
    private const MAX_JSON_BYTES = 16_384;

    /**
     * Routes and files that should never be stored in the database.
     *
     * @var list<string>
     */
    private const EXCLUDED_PREFIXES = [
        'assets/',
        'favicon.ico',
        'robots.txt',
        'health',
        'debugbar',
    ];

    public function __construct(
        private readonly HttpRequestLogModel $model,
        private readonly RequestDataSanitizer $sanitizer
    ) {
    }

    /**
     * Decide whether this response should be persisted.
     *
     * The first version stores:
     * - POST, PUT, PATCH and DELETE requests;
     * - all HTTP errors;
     * - authenticated requests when explicitly enabled.
     *
     * Successful anonymous GET requests are skipped to reduce database load.
     */
    public function shouldLog(
        IncomingRequest $request,
        ResponseInterface $response
    ): bool {
        if (!$this->isEnabled()) {
            return false;
        }

        $path = ltrim(
            $request->getUri()->getPath(),
            '/'
        );

        foreach (self::EXCLUDED_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return false;
            }
        }

        $method = strtoupper(
            $request->getMethod()
        );

        if (
            in_array(
                $method,
                ['POST', 'PUT', 'PATCH', 'DELETE'],
                true
            )
        ) {
            return true;
        }

        return $response->getStatusCode() >= 400;
    }

    /**
     * Write the completed request.
     *
     * Logging failures are caught and written to the normal CI4 log.
     * They must never change the response seen by the user.
     */
    public function write(
        string $requestId,
        int $startedAtNanoseconds,
        IncomingRequest $request,
        ResponseInterface $response
    ): void {
        try {
            if (!$this->shouldLog($request, $response)) {
                return;
            }

            $statusCode = $response->getStatusCode();

            $durationMs = max(
                0,
                (int) round(
                    (hrtime(true) - $startedAtNanoseconds)
                    / 1_000_000
                )
            );

            $requestPayload = $this->readRequestPayload(
                $request
            );

            $responsePayload = $this->readResponseSummary(
                $response
            );

            $router = service('router');

            $routeName = $this->resolveRouteName(
                $router
            );

            $controllerAction =
                $this->resolveControllerAction(
                    $router
                );

            $authenticated = false;
            $userId = null;
            $profileReference = null;

            /**
             * Session access occurs only for requests that are actually logged,
             * avoiding unnecessary database-session work for static/public GETs.
             */
            try {
                $authenticated =
                    session('is_authenticated') === true;

                $sessionUserId = session('auth_user_id');

                $userId = is_numeric($sessionUserId)
                    ? (int) $sessionUserId
                    : null;

                $sessionReference = session(
                    'auth_profile_reference'
                );

                $profileReference =
                    is_string($sessionReference)
                        ? mb_substr(
                            $sessionReference,
                            0,
                            50
                        )
                        : null;
            } catch (Throwable) {
                /**
                 * Logging remains valid even when a session cannot be read.
                 */
            }

            $this->model->insert([
                'request_id' => $requestId,

                'occurred_at' =>
                    gmdate('Y-m-d H:i:s') . '+00:00',

                'environment' => ENVIRONMENT,

                'request_method' =>
                    strtoupper($request->getMethod()),

                'request_uri' => mb_substr(
                    (string) $request->getUri(),
                    0,
                    2000
                ),

                'route_name' => $routeName,

                'controller_action' =>
                    $controllerAction,

                'response_status' => $statusCode,

                'duration_ms' => $durationMs,

                'ip_address' =>
                    $request->getIPAddress(),

                'user_id' => $userId,

                'profile_reference' =>
                    $profileReference,

                'is_authenticated' =>
                    $authenticated,

                'user_agent' => mb_substr(
                    $request->getUserAgent()->getAgentString(),
                    0,
                    1000
                ),

                'referer' => $this->readReferer(
                    $request
                ),

                'request_headers' =>
                    $this->encodeJson(
                        $this->sanitizer->safeHeaders(
                            $request
                        )
                    ),

                'request_payload' =>
                    $this->encodeJson(
                        $requestPayload
                    ),

                'response_payload' =>
                    $this->encodeJson(
                        $responsePayload
                    ),

                'request_size_bytes' =>
                    $this->resolveRequestSize(
                        $request
                    ),

                'response_size_bytes' =>
                    strlen($response->getBody()),

                'severity' =>
                    $this->resolveSeverity(
                        $statusCode
                    ),

                'is_successful' =>
                    $statusCode < 400,
            ]);
        } catch (Throwable $exception) {
            /**
             * Never throw from technical logging.
             *
             * A logging outage must not prevent registration, OTP verification,
             * login, profile update or any other business operation.
             */
            log_message(
                'error',
                'Unable to persist HTTP request log: {message}',
                [
                    'message' =>
                        $exception->getMessage(),
                ]
            );
        }
    }

    private function isEnabled(): bool
    {
        return filter_var(
            env('requestLogging.enabled', true),
            FILTER_VALIDATE_BOOLEAN
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readRequestPayload(
        IncomingRequest $request
    ): ?array {
        $contentType = mb_strtolower(
            $request->getHeaderLine(
                'Content-Type'
            )
        );

        $payload = [];

        if (str_contains($contentType, 'application/json')) {
            try {
                $json = $request->getJSON(true);

                $payload = is_array($json)
                    ? $json
                    : [];
            } catch (Throwable) {
                return [
                    '_payload' =>
                        '[INVALID OR UNREADABLE JSON]',
                ];
            }
        } else {
            $post = $request->getPost();

            $payload = is_array($post)
                ? $post
                : [];
        }

        if ($payload === []) {
            return null;
        }

        return $this->sanitizer->sanitize(
            $payload
        );
    }

    /**
     * Do not store full HTML responses.
     *
     * @return array<string, mixed>|null
     */
    private function readResponseSummary(
        ResponseInterface $response
    ): ?array {
        $summary = [
            'status' => $response->getStatusCode(),
        ];

        $location = $response->getHeaderLine(
            'Location'
        );

        if ($location !== '') {
            $summary['type'] = 'redirect';
            $summary['location'] = mb_substr(
                $location,
                0,
                2000
            );

            return $summary;
        }

        $contentType = mb_strtolower(
            $response->getHeaderLine(
                'Content-Type'
            )
        );

        if (!str_contains($contentType, 'json')) {
            $summary['type'] = 'html';

            return $summary;
        }

        $body = $response->getBody();

        if (strlen($body) > self::MAX_JSON_BYTES) {
            $summary['type'] = 'json';
            $summary['_truncated'] = true;
            $summary['_original_size'] =
                strlen($body);

            return $summary;
        }

        try {
            $decoded = json_decode(
                $body,
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            $summary['type'] = 'json';
            $summary['body'] =
                $this->sanitizer->sanitize(
                    $decoded
                );
        } catch (JsonException) {
            $summary['type'] = 'json';
            $summary['body'] =
                '[INVALID JSON RESPONSE]';
        }

        return $summary;
    }

    private function resolveRequestSize(
        IncomingRequest $request
    ): ?int {
        $contentLength = $request->getHeaderLine(
            'Content-Length'
        );

        return is_numeric($contentLength)
            ? (int) $contentLength
            : null;
    }

    private function readReferer(
        IncomingRequest  $request
    ): ?string {
        $referer = trim(
            $request->getHeaderLine('Referer')
        );

        return $referer !== ''
            ? mb_substr($referer, 0, 2000)
            : null;
    }

    private function resolveSeverity(
        int $statusCode
    ): string {
        return match (true) {
            $statusCode >= 500 => 'ERROR',
            $statusCode >= 400 => 'WARNING',
            default => 'INFO',
        };
    }

    private function resolveRouteName(
        mixed $router
    ): ?string {
        if (
            !is_object($router)
            || !method_exists(
                $router,
                'getMatchedRouteOptions'
            )
        ) {
            return null;
        }

        $options = $router->getMatchedRouteOptions();

        $name = is_array($options)
            ? ($options['as'] ?? null)
            : null;

        return is_string($name)
            ? mb_substr($name, 0, 150)
            : null;
    }

    private function resolveControllerAction(
        mixed $router
    ): ?string {
        if (!$router instanceof Router) {
            return null;
        }

        $controller = $router->controllerName();
        $method = $router->methodName();

        if ($controller === '') {
            return null;
        }

        return mb_substr(
            $controller
            . ($method !== '' ? '::' . $method : ''),
            0,
            255
        );
    }

    private function encodeJson(
        mixed $value
    ): ?string {
        if ($value === null || $value === []) {
            return null;
        }

        try {
            return json_encode(
                $value,
                JSON_THROW_ON_ERROR
                | JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE
            );
        } catch (JsonException) {
            return json_encode([
                '_error' =>
                    'Unable to encode log value.',
            ]);
        }
    }
}

