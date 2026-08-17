<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Produces privacy-safe mobile-number representations.
 */
final class MobileNumberMasker
{
    private const VISIBLE_DIGITS = 3;

    private const INDIAN_MOBILE_LENGTH = 10;

    private function __construct() {}

    /**
     * Mask an Indian mobile number while retaining its last three digits.
     *
     * Examples:
     *
     * 9876543210   -> XXXXXXX210
     * +919876543210 -> XXXXXXX210
     */
    public static function lastThree(
        mixed $mobileNumber
    ): string {
        $digits = preg_replace(
            '/\D+/',
            '',
            trim(
                (string) $mobileNumber
            )
        ) ?? '';

        if ($digits === '') {
            return '';
        }

        /*
         * Contact values may contain the +91 country code.
         * Use only the final ten digits for Indian mobile presentation.
         */
        if (
            mb_strlen($digits)
            > self::INDIAN_MOBILE_LENGTH
        ) {
            $digits = mb_substr(
                $digits,
                -self::INDIAN_MOBILE_LENGTH
            );
        }

        $length = mb_strlen(
            $digits
        );

        if (
            $length
            <= self::VISIBLE_DIGITS
        ) {
            return str_repeat(
                'X',
                $length
            );
        }

        return str_repeat(
            'X',
            $length - self::VISIBLE_DIGITS
        ) . mb_substr(
            $digits,
            -self::VISIBLE_DIGITS
        );
    }
}
