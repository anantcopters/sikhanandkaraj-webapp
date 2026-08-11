<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Services\Matchmaking\MemberMatchmakingService;

/**
 * Supplies dashboard-specific member data.
 */
final class MemberDashboardDataService
{
    public function __construct(
        private readonly MemberMatchmakingService
        $matchmakingService
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getDashboardData(
        int $userId
    ): array {
        return array_merge(
            [
                /*
                 * Retain the existing placeholder plan until the
                 * subscription module is introduced.
                 */
                'accountPlan' => [
                    'name' =>
                    'Free account',

                    'code' =>
                    'FREE',
                ],
            ],
            $this
                ->matchmakingService
                ->dashboardCollections(
                    $userId
                )
        );
    }
}
