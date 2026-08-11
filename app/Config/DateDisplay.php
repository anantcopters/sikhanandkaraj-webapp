<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Controls user-facing date and timestamp presentation.
 *
 * Database timestamps and application processing remain in UTC.
 * This configuration is used only when presenting timestamps to users.
 */
final class DateDisplay extends BaseConfig
{
    /**
     * Timezone used for displaying UTC timestamps.
     *
     * India Standard Time:
     * Asia/Kolkata
     */
    public string $timezone;

    /**
     * User-facing date format.
     *
     * Example:
     * 5th Aug 2026
     */
    public string $dateFormat;

    /**
     * User-facing date and time format.
     *
     * Example:
     * 5th Aug 2026 10:58 PM
     */
    public string $dateTimeFormat;

    public function __construct()
    {
        parent::__construct();

        $this->timezone = trim(
            (string) env(
                'DATE_DISPLAY_TIMEZONE',
                'Asia/Kolkata'
            )
        );

        if ($this->timezone === '') {
            $this->timezone =
                'Asia/Kolkata';
        }

        $this->dateFormat = trim(
            (string) env(
                'DATE_DISPLAY_FORMAT',
                'jS M Y'
            )
        );

        if ($this->dateFormat === '') {
            $this->dateFormat =
                'jS M Y';
        }

        $this->dateTimeFormat = trim(
            (string) env(
                'DATE_DISPLAY_TIME_FORMAT',
                'jS M Y h:i A'
            )
        );

        if ($this->dateTimeFormat === '') {
            $this->dateTimeFormat =
                'jS M Y h:i A';
        }
    }
}
