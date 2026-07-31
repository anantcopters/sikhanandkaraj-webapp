<?php

declare(strict_types=1);

namespace App\Support;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Throwable;

/**
 * Provides consistent date and time formatting for application views.
 *
 * Database timestamps are treated as UTC unless a DateTimeInterface object
 * already contains explicit timezone information. Member-facing values are
 * converted to the Indian timezone only at the display boundary.
 */
final class DateTimeFormatter
{
    /**
     * Timezone used for timestamps stored in the database.
     */
    private const STORAGE_TIMEZONE = 'UTC';

    /**
     * Timezone used for member-facing display.
     */
    private const DISPLAY_TIMEZONE = 'Asia/Kolkata';

    /**
     * Standard project display-date format.
     *
     * Example: 27th Jul 2026
     */
    private const DISPLAY_DATE_FORMAT = 'jS M Y';

    /**
     * Prevent this utility class from being instantiated.
     */
    private function __construct() {}

    /**
     * Format a UTC database timestamp as an Indian display date.
     *
     * Examples:
     *
     * 2026-07-27 10:30:00 -> 27th Jul 2026
     * null                -> ''
     * invalid value       -> ''
     */
    public static function indianDate(
        DateTimeInterface|string|null $value
    ): string {
        $dateTime = self::toIndianDateTime($value);

        if ($dateTime === null) {
            return '';
        }

        return $dateTime->format(
            self::DISPLAY_DATE_FORMAT
        );
    }

    /**
     * Convert a database timestamp to an immutable Indian DateTime object.
     *
     * This method can be reused later where a view needs both date and time.
     */
    public static function toIndianDateTime(
        DateTimeInterface|string|null $value
    ): ?DateTimeImmutable {
        if ($value === null) {
            return null;
        }

        try {
            $displayTimezone = new DateTimeZone(
                self::DISPLAY_TIMEZONE
            );

            if ($value instanceof DateTimeInterface) {
                return DateTimeImmutable::createFromInterface(
                    $value
                )->setTimezone(
                    $displayTimezone
                );
            }

            $resolvedValue = trim($value);

            if ($resolvedValue === '') {
                return null;
            }

            /*
             * PostgreSQL TIMESTAMP values in this project do not carry an
             * explicit timezone. Interpret them as UTC before converting them
             * to Asia/Kolkata.
             */
            $dateTime = new DateTimeImmutable(
                $resolvedValue,
                new DateTimeZone(
                    self::STORAGE_TIMEZONE
                )
            );

            return $dateTime->setTimezone(
                $displayTimezone
            );
        } catch (Throwable $exception) {
            /*
             * Invalid display data must not break the complete page.
             */
            log_message(
                'warning',
                'Unable to format display date "{value}": {message}',
                [
                    'value' => is_string($value)
                        ? $value
                        : get_debug_type($value),

                    'message' => $exception->getMessage(),
                ]
            );

            return null;
        }
    }
}
