<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Creates safe structured context for infrastructure operations.
 *
 * Sensitive values such as object keys, API credentials, email addresses,
 * SMS bodies, OTP values and signed URLs must never be added directly.
 */
final class InfrastructureErrorContext
{
    /**
     * Build standard infrastructure-operation context.
     *
     * @param array<string, mixed> $additionalContext
     *
     * @return array<string, mixed>
     */
    public static function forOperation(
        string $operation,
        string $component,
        string $method,
        array $additionalContext = []
    ): array {
        return array_merge(
            [
                'operation' =>
                $operation,

                'component' =>
                $component,

                'method' =>
                $method,
            ],
            $additionalContext
        );
    }

    /**
     * Return a non-reversible object-key identifier.
     */
    public static function objectKeyHash(
        string $objectKey
    ): string {
        $normalized = trim(
            str_replace(
                '\\',
                '/',
                $objectKey
            )
        );

        return $normalized !== ''
            ? hash(
                'sha256',
                $normalized
            )
            : '';
    }

    /**
     * Return only the email domain.
     */
    public static function emailDomain(
        string $email
    ): string {
        $parts = explode(
            '@',
            mb_strtolower(
                trim($email)
            ),
            2
        );

        if (
            count($parts) !== 2
            || trim($parts[1]) === ''
        ) {
            return '';
        }

        return mb_substr(
            trim($parts[1]),
            0,
            255
        );
    }

    /**
     * Return only the final four mobile digits.
     */
    public static function mobileSuffix(
        string $mobileNumber
    ): string {
        $digits = preg_replace(
            '/\D+/',
            '',
            $mobileNumber
        ) ?? '';

        return $digits !== ''
            ? mb_substr(
                $digits,
                -4
            )
            : '';
    }

    private function __construct() {}
}
