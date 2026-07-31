<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Normalizes OTP form input submitted through individual digit fields.
 *
 * The class is independent of HTTP requests and can therefore be reused by
 * registration, password reset and future OTP workflows.
 */
final class OtpInputNormalizer
{
    public const DEFAULT_LENGTH = 4;

    /**
     * Prevent instantiation of this stateless support class.
     */
    private function __construct() {}

    /**
     * Build a numeric OTP from individual form fields.
     *
     * Expected input:
     *
     * [
     *     'otp_1' => '0',
     *     'otp_2' => '0',
     *     'otp_3' => '0',
     *     'otp_4' => '0',
     * ]
     *
     * Result:
     *
     * 0000
     *
     * Invalid or missing digits are converted to an empty string. The caller
     * must still validate the resulting OTP length.
     *
     * @param array<string, mixed> $input
     */
    public static function fromDigitFields(
        array $input,
        int $length = self::DEFAULT_LENGTH,
        string $fieldPrefix = 'otp_'
    ): string {
        if ($length < 1) {
            return '';
        }

        $digits = [];

        for ($index = 1; $index <= $length; $index++) {
            $fieldName = $fieldPrefix . $index;

            $digit = trim(
                (string) ($input[$fieldName] ?? '')
            );

            $digits[] = preg_match(
                '/^\d$/',
                $digit
            ) === 1
                ? $digit
                : '';
        }

        return implode(
            '',
            $digits
        );
    }

    /**
     * Confirm that a submitted OTP contains the required number of digits.
     */
    public static function isValid(
        string $otp,
        int $length = self::DEFAULT_LENGTH
    ): bool {
        if ($length < 1) {
            return false;
        }

        return preg_match(
            '/^\d{' . $length . '}$/',
            $otp
        ) === 1;
    }
}
