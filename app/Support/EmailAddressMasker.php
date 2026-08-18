<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Produces privacy-safe email-address representations.
 */
final class EmailAddressMasker
{
    private const MASKED_LOCAL_PART = 'XXXX';

    private const MASKED_DOMAIN = 'XX';

    private function __construct() {}

    /**
     * Mask an email while retaining only its final domain suffix.
     *
     * Examples:
     *
     * member@gmail.com    -> XXXX@XX.com
     * member@example.in   -> XXXX@XX.in
     * member@example.org  -> XXXX@XX.org
     */
    public static function mask(
        mixed $emailAddress
    ): string {
        $normalizedEmail = mb_strtolower(
            trim(
                (string) $emailAddress
            )
        );

        if (
            $normalizedEmail === ''
            || filter_var(
                $normalizedEmail,
                FILTER_VALIDATE_EMAIL
            ) === false
        ) {
            return '';
        }

        $atPosition = mb_strrpos(
            $normalizedEmail,
            '@'
        );

        if ($atPosition === false) {
            return '';
        }

        $domain = mb_substr(
            $normalizedEmail,
            $atPosition + 1
        );

        $lastDotPosition = mb_strrpos(
            $domain,
            '.'
        );

        if (
            $lastDotPosition === false
            || $lastDotPosition
            === mb_strlen($domain) - 1
        ) {
            return self::MASKED_LOCAL_PART
                . '@'
                . self::MASKED_DOMAIN;
        }

        $domainSuffix = mb_substr(
            $domain,
            $lastDotPosition
        );

        return self::MASKED_LOCAL_PART
            . '@'
            . self::MASKED_DOMAIN
            . $domainSuffix;
    }
}
