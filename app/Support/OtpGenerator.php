<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

/**
 * Generates secure numeric one-time passwords.
 *
 * Development and QA may use OTP_FIXED_VALUE from the environment to simplify
 * testing. Production must always generate a cryptographically secure random
 * OTP.
 *
 * Example Development/QA configuration:
 *
 * OTP_FIXED_VALUE=0000
 *
 * Example Production configuration:
 *
 * OTP_FIXED_VALUE=
 */
final class OtpGenerator
{
    /**
     * Default OTP length used by registration and password reset.
     */
    public const DEFAULT_LENGTH = 4;

    /**
     * Prevent this stateless utility class from being instantiated.
     */
    private function __construct() {}

    /**
     * Generate a numeric OTP.
     *
     * In non-production environments:
     *
     * - A valid OTP_FIXED_VALUE is returned when configured.
     * - A secure random OTP is returned when OTP_FIXED_VALUE is empty.
     *
     * In production:
     *
     * - OTP_FIXED_VALUE must remain empty.
     * - A secure random OTP is always generated.
     *
     * @throws RuntimeException When OTP configuration is invalid.
     */
    public static function generate(
        int $length = self::DEFAULT_LENGTH
    ): string {
        self::validateLength($length);

        $configuredOtp = trim(
            (string) env('OTP_FIXED_VALUE')
        );

        /**
         * Fixed OTP values are prohibited in production.
         *
         * Throwing an exception is safer than silently ignoring an accidentally
         * configured fixed OTP because it exposes a deployment configuration
         * problem immediately.
         */
        if (
            ENVIRONMENT === 'production'
            && $configuredOtp !== ''
        ) {
            log_message(
                'critical',
                'OTP_FIXED_VALUE must not be configured in production.'
            );

            throw new RuntimeException(
                'OTP configuration is invalid.'
            );
        }

        /**
         * Development and QA may use the fixed OTP configured in .env.
         */
        if ($configuredOtp !== '') {
            self::validateConfiguredOtp(
                $configuredOtp,
                $length
            );

            return $configuredOtp;
        }

        return self::generateRandomOtp(
            $length
        );
    }

    /**
     * Validate the requested OTP length.
     *
     * The upper limit prevents unsafe integer exponent calculations and avoids
     * generating OTP values that cannot be represented reliably as integers.
     */
    private static function validateLength(
        int $length
    ): void {
        if (
            $length < 4
            || $length > 9
        ) {
            throw new RuntimeException(
                'OTP length must be between 4 and 9 digits.'
            );
        }
    }

    /**
     * Validate OTP_FIXED_VALUE against the requested OTP length.
     */
    private static function validateConfiguredOtp(
        string $configuredOtp,
        int $length
    ): void {
        if (
            preg_match(
                '/^\d{' . $length . '}$/',
                $configuredOtp
            ) !== 1
        ) {
            throw new RuntimeException(
                'OTP_FIXED_VALUE must contain exactly '
                    . $length
                    . ' digits.'
            );
        }
    }

    /**
     * Generate a cryptographically secure numeric OTP.
     */
    private static function generateRandomOtp(
        int $length
    ): string {
        $minimum = 10 ** ($length - 1);
        $maximum = (10 ** $length) - 1;

        return (string) random_int(
            $minimum,
            $maximum
        );
    }
}
