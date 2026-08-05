<?php

declare(strict_types=1);

namespace App\Logging;

use Stringable;
use Throwable;

/**
 * Sanitizes structured values before they are written to an error-log table.
 */
final class ErrorLogSanitizer
{
    /**
     * Keys whose values must never be persisted.
     *
     * @var list<string>
     */
    private const SENSITIVE_KEYS = [
        'authorization',
        'proxy_authorization',
        'proxy-authorization',
        'cookie',
        'set_cookie',
        'set-cookie',
        'password',
        'password_hash',
        'current_password',
        'new_password',
        'confirm_password',
        'otp',
        'otp_code',
        'token',
        'access_token',
        'refresh_token',
        'csrf_token',
        'x_csrf_token',
        'x-csrf-token',
        'x_api_key',
        'x-api-key',
        'secret',
        'secret_key',
        'aws_secret_access_key',
        'cloudfront_policy',
        'cloudfront_signature',
        'cloudfront_key_pair_id',
    ];

    /**
     * Maximum depth of nested context values.
     */
    private const MAX_DEPTH = 4;

    /**
     * Sanitize a context array.
     *
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function sanitize(
        array $context
    ): array {
        return $this->sanitizeArray(
            $context,
            0
        );
    }

    /**
     * Sanitize nested context recursively.
     *
     * @param array<array-key, mixed> $values
     *
     * @return array<array-key, mixed>
     */
    private function sanitizeArray(
        array $values,
        int $depth
    ): array {
        if ($depth >= self::MAX_DEPTH) {
            return [
                '_truncated' =>
                'Maximum context depth reached.',
            ];
        }

        $sanitized = [];

        foreach ($values as $key => $value) {
            $normalizedKey = mb_strtolower(
                trim(
                    (string) $key
                )
            );

            if (
                in_array(
                    $normalizedKey,
                    self::SENSITIVE_KEYS,
                    true
                )
            ) {
                $sanitized[$key] =
                    '[REDACTED]';

                continue;
            }

            $sanitized[$key] =
                $this->sanitizeValue(
                    $value,
                    $depth + 1
                );
        }

        return $sanitized;
    }

    /**
     * Convert one context value into a JSON-safe value.
     */
    private function sanitizeValue(
        mixed $value,
        int $depth
    ): mixed {
        if (
            $value === null
            || is_bool($value)
            || is_int($value)
            || is_float($value)
        ) {
            return $value;
        }

        if (is_string($value)) {
            return mb_substr(
                $value,
                0,
                4000
            );
        }

        if (is_array($value)) {
            return $this->sanitizeArray(
                $value,
                $depth
            );
        }

        if ($value instanceof Throwable) {
            return [
                'class' =>
                $value::class,

                'message' =>
                mb_substr(
                    $value->getMessage(),
                    0,
                    4000
                ),

                'file' =>
                $value->getFile(),

                'line' =>
                $value->getLine(),
            ];
        }

        if ($value instanceof Stringable) {
            return mb_substr(
                (string) $value,
                0,
                4000
            );
        }

        if (is_object($value)) {
            return [
                'class' =>
                $value::class,
            ];
        }

        if (is_resource($value)) {
            return sprintf(
                '[resource:%s]',
                get_resource_type($value)
            );
        }

        return '[unsupported-value]';
    }
}
