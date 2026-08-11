<?php

declare(strict_types=1);

namespace App\Support;

use Config\DateDisplay as DateDisplayConfig;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Throwable;

/**
 * Formats calendar dates and UTC timestamps for user-facing screens.
 *
 * Important:
 * - This helper is for display only.
 * - Database values must remain unchanged.
 * - HTML date inputs must continue using Y-m-d.
 * - Date-only values such as date_of_birth are never timezone-converted.
 * - Timestamp values are interpreted as UTC and converted to the configured
 *   display timezone before formatting.
 */
final class DateDisplay
{
    /**
     * Format a calendar date without timezone conversion.
     *
     * Suitable for:
     * - date_of_birth
     * - anniversary dates
     * - other date-only database columns
     *
     * Example input:
     * 1990-08-05
     *
     * Example output:
     * 5th Aug 1990
     */
    public static function formatDate(
        mixed $value,
        string $fallback = '—'
    ): string {
        if ($value === null) {
            return $fallback;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(
                self::configuration()
                    ->dateFormat
            );
        }

        $resolvedValue = trim(
            (string) $value
        );

        if ($resolvedValue === '') {
            return $fallback;
        }

        try {
            /*
             * The leading ! resets time fields and prevents the PHP/server
             * timezone from changing the intended calendar date.
             */
            $date = DateTimeImmutable
                ::createFromFormat(
                    '!Y-m-d',
                    mb_substr(
                        $resolvedValue,
                        0,
                        10
                    )
                );

            if (!$date instanceof DateTimeImmutable) {
                return $fallback;
            }

            return $date->format(
                self::configuration()
                    ->dateFormat
            );
        } catch (Throwable) {
            return $fallback;
        }
    }

    /**
     * Format a calendar date and return an empty string for missing values.
     */
    public static function formatDateOrEmpty(
        mixed $value
    ): string {
        return self::formatDate(
            $value,
            ''
        );
    }

    /**
     * Convert a UTC timestamp to the configured display timezone and show
     * only its local calendar date.
     *
     * Suitable for compact list screens where time is not required.
     *
     * Example UTC input:
     * 2026-08-05 12:30:00
     *
     * Example IST output:
     * 5th Aug 2026
     */
    public static function formatUtcDate(
        mixed $value,
        string $fallback = '—'
    ): string {
        return self::formatUtc(
            $value,
            self::configuration()
                ->dateFormat,
            $fallback
        );
    }

    /**
     * Convert a UTC timestamp to the configured display timezone and show
     * both local date and time.
     *
     * Suitable for:
     * - created_at
     * - updated_at
     * - approved_at
     * - rejected_at
     * - changed_at
     * - last_login_at
     *
     * Example UTC input:
     * 2026-08-05 12:30:00
     *
     * Example IST output:
     * 5th Aug 2026 06:00 PM
     */
    public static function formatUtcDateTime(
        mixed $value,
        string $fallback = '—'
    ): string {
        return self::formatUtc(
            $value,
            self::configuration()
                ->dateTimeFormat,
            $fallback
        );
    }

    /**
     * Convert a UTC timestamp to an ISO-8601 value in the display timezone.
     *
     * This is useful for:
     * - HTML <time datetime="">
     * - JSON consumed by JavaScript
     * - machine-readable UI attributes
     *
     * Example:
     * 2026-08-05T18:00:00+05:30
     */
    public static function utcToDisplayIso(
        mixed $value,
        string $fallback = ''
    ): string {
        $date = self::utcToDisplayDateTime(
            $value
        );

        if (!$date instanceof DateTimeImmutable) {
            return $fallback;
        }

        return $date->format(
            DateTimeInterface::ATOM
        );
    }

    /**
     * Convert one UTC value into the configured display timezone.
     */
    private static function utcToDisplayDateTime(
        mixed $value
    ): ?DateTimeImmutable {
        if ($value === null) {
            return null;
        }

        try {
            $utcTimezone = new DateTimeZone(
                'UTC'
            );

            $displayTimezone = new DateTimeZone(
                self::configuration()
                    ->timezone
            );

            if ($value instanceof DateTimeInterface) {
                /*
                 * Recreate the supplied value from its absolute timestamp.
                 * This avoids depending on the timezone attached to mutable
                 * DateTime implementations.
                 */
                return (
                    new DateTimeImmutable(
                        '@' . $value->getTimestamp()
                    )
                )->setTimezone(
                    $displayTimezone
                );
            }

            $resolvedValue = trim(
                (string) $value
            );

            if ($resolvedValue === '') {
                return null;
            }

            /*
             * Values containing Z or an explicit offset already describe an
             * absolute moment. PHP can parse them directly.
             */
            if (
                preg_match(
                    '/(?:Z|[+-]\d{2}:\d{2})$/i',
                    $resolvedValue
                ) === 1
            ) {
                return (
                    new DateTimeImmutable(
                        $resolvedValue
                    )
                )->setTimezone(
                    $displayTimezone
                );
            }

            /*
             * PostgreSQL timestamp values often arrive without an offset:
             * 2026-08-05 12:30:00
             *
             * The project stores these values as UTC, so UTC must be supplied
             * explicitly rather than relying on the PHP/server timezone.
             */
            return (
                new DateTimeImmutable(
                    $resolvedValue,
                    $utcTimezone
                )
            )->setTimezone(
                $displayTimezone
            );
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Convert and format one UTC timestamp.
     */
    private static function formatUtc(
        mixed $value,
        string $format,
        string $fallback
    ): string {
        $date = self::utcToDisplayDateTime(
            $value
        );

        if (!$date instanceof DateTimeImmutable) {
            return $fallback;
        }

        return $date->format(
            $format
        );
    }

    /**
     * Resolve the display configuration.
     */
    private static function configuration(): DateDisplayConfig
    {
        /** @var DateDisplayConfig $configuration */
        $configuration = config(
            DateDisplayConfig::class
        );

        return $configuration;
    }

    private function __construct() {}
}
