<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Matchmaking product configuration.
 *
 * Algorithm tuning values belong here rather than controllers or views.
 */
final class Matchmaking extends BaseConfig
{
    /**
     * Minimum percentage of configured structured preferences
     * that a candidate must satisfy.
     */
    public int $minimumMatchPercentage;

    /**
     * Number of days for which a matched member is treated
     * as a New Match.
     */
    public int $newMatchDays;

    public function __construct()
    {
        parent::__construct();

        $this->minimumMatchPercentage = min(
            100,
            max(
                1,
                (int) env(
                    'matchmaking.minimumMatchPercentage',
                    30
                )
            )
        );

        $this->newMatchDays = min(
            365,
            max(
                1,
                (int) env(
                    'matchmaking.newMatchDays',
                    30
                )
            )
        );
    }
}
