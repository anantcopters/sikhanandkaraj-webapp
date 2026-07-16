<?php

declare(strict_types=1);

namespace App\Services\Logging;

/**
 * Removes sensitive information before request or response data is logged.
 */
final class RequestDataSanitizer
{
    private const REDACTED = '[REDACTED]';

    private const MAX_STRING_LENGTH = 2000;

    /**
     * Fields containing one of these terms are never stored.
     *
     * This covers:
     * password, password_confirmation, otp, otp_1, otp_hash,
     * csrf_token, access_token, refresh_token and api_key.
     *
     * @var list<string>
     */
    private const SENSITIVE_KEYWORDS = [
        'password',
        'passwd',
        'otp',
        'token',
        'authorization',
        'cookie',
        'secret',
        'api_key',
        'apikey',
        'card_number',
        'cvv',
        'session',
    ];

    /**
     * Sanitize a nested request or response payload.
     */
    public function sanitize(mixed $value): mixed
    {
        if (is_array($value)) {
            $sanitized = [];

            foreach ($value as $key => $item) {
                $normalizedKey = mb_strtolower(
                    (string) $key
                );

                if ($this->isSensitiveKey($normalizedKey)) {
                    $sanitized[$key] = self::REDACTED;

                    continue;
                }

                if ($this->isEmailKey($normalizedKey)) {
                    $sanitized[$key] = $this->maskEmail(
                        (string) $item
                    );

                    continue;
                }

                if ($this->isMobileKey($normalizedKey)) {
                    $sanitized[$key] = $this->maskMobile(
                        (string) $item
                    );

                    continue;
                }

                $sanitized[$key] = $this->sanitize($item);
            }

            return $sanitized;
        }

        if (is_object($value)) {
            return $this->sanitize(
                (array) $value
            );
        }

        if (is_string($value)) {
            return mb_substr(
                $value,
                0,
                self::MAX_STRING_LENGTH
            );
        }

        if (
            is_int($value)
            || is_float($value)
            || is_bool($value)
            || $value === null
        ) {
            return $value;
        }

        return '[UNSUPPORTED VALUE]';
    }

    /**
     * Store only selected harmless headers.
     *
     * Authorization, Cookie, CSRF and API-key headers are deliberately
     * not included in this allow-list.
     *
     * @return array<string, string>
     */
    public function safeHeaders(
        \CodeIgniter\HTTP\RequestInterface $request
    ): array {
        $allowedHeaders = [
            'Accept',
            'Content-Type',
            'X-Requested-With',
            'Accept-Language',
        ];

        $headers = [];

        foreach ($allowedHeaders as $headerName) {
            $header = $request->getHeaderLine(
                $headerName
            );

            if ($header === '') {
                continue;
            }

            $headers[$headerName] = mb_substr(
                $header,
                0,
                1000
            );
        }

        return $headers;
    }

    private function isSensitiveKey(string $key): bool
    {
        foreach (self::SENSITIVE_KEYWORDS as $keyword) {
            if (str_contains($key, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function isEmailKey(string $key): bool
    {
        return str_contains($key, 'email');
    }

    private function isMobileKey(string $key): bool
    {
        return str_contains($key, 'mobile')
            || str_contains($key, 'phone');
    }

    private function maskEmail(string $email): string
    {
        $email = trim($email);

        if (
            $email === ''
            || !str_contains($email, '@')
        ) {
            return '[MASKED EMAIL]';
        }

        [$local, $domain] = explode('@', $email, 2);

        $visible = mb_substr($local, 0, 2);

        return $visible
            . '***@'
            . $domain;
    }

    private function maskMobile(string $mobile): string
    {
        $digits = preg_replace(
            '/\D+/',
            '',
            $mobile
        ) ?? '';

        if (strlen($digits) < 4) {
            return '[MASKED MOBILE]';
        }

        return '******' . substr($digits, -4);
    }
}

