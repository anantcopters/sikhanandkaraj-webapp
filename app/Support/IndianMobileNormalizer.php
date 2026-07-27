<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Normalizes Indian mobile numbers into the application's canonical
 * E.164-style database format.
 */
final class IndianMobileNormalizer
{
    private const COUNTRY_CODE = '91';

    /**
     * Normalize an Indian mobile number.
     *
     * Supported input:
     *
     * 9876543210
     * 919876543210
     * +919876543210
     * +91 98765 43210
     *
     * Output:
     *
     * +919876543210
     */
    public static function normalize(
        string $mobileNumber
    ): ?string {
        $digits = preg_replace(
            '/\D+/',
            '',
            trim($mobileNumber)
        ) ?? '';

        if (
            strlen($digits) === 12
            && str_starts_with(
                $digits,
                self::COUNTRY_CODE
            )
        ) {
            $digits = substr(
                $digits,
                2
            );
        }

        if (
            preg_match(
                '/^[6-9][0-9]{9}$/',
                $digits
            ) !== 1
        ) {
            return null;
        }

        return '+'
            . self::COUNTRY_CODE
            . $digits;
    }
}
