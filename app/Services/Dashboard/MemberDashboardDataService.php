<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Services\Matchmaking\MemberMatchmakingService;
use App\Services\Membership\MembershipService;

/**
 * Supplies dashboard-specific member data.
 */
final class MemberDashboardDataService
{
    public function __construct(
        private readonly MemberMatchmakingService
        $matchmakingService,

        private readonly MembershipService
        $membershipService
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getDashboardData(
        int $userId
    ): array {
        $membership =
            $this->membershipService
            ->resolveForUser(
                $userId
            );

        return array_merge(
            [
                /*
                 * MembershipService is the authoritative current-account
                 * resolver. Dashboard must never hardcode Free Account.
                 */
                'accountPlan' => [
                    'name' =>
                    trim(
                        (string) (
                            $membership['accountLabel']
                            ?? 'Free Account'
                        )
                    ),

                    'code' =>
                    mb_strtoupper(
                        trim(
                            (string) (
                                $membership['accountType']
                                ?? MembershipService::ACCOUNT_FREE
                            )
                        )
                    ),

                    'isPaid' => ($membership['isPaid']
                        ?? false) === true,
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
