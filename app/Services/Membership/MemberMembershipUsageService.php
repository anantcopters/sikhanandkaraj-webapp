<?php

declare(strict_types=1);

namespace App\Services\Membership;

use App\Models\MemberMembershipLiveIntroductionViewModel;
use App\Models\MemberMembershipProfileViewModel;
use App\Support\BooleanValue;

/**
 * Builds member-facing membership usage presentation data.
 *
 * This service does not authorize Full Profile or Live Introduction access.
 *
 * Authorization/consumption remains owned by:
 *
 * - ProfileAccessPolicy;
 * - MembershipProfileUsageService;
 * - LiveIntroductionAccessPolicy;
 * - MembershipLiveIntroductionUsageService.
 *
 * This service is read-only and exists only to present the member's own
 * commercial usage ledger in Account Settings.
 */
final class MemberMembershipUsageService
{
    private const IST_TIMEZONE =
    'Asia/Kolkata';

    public function __construct(
        private readonly MembershipService
        $membershipService,

        private readonly MemberMembershipProfileViewModel
        $profileUsageModel,

        private readonly
        MemberMembershipLiveIntroductionViewModel
        $liveIntroductionUsageModel
    ) {}

    /**
     * Build the current membership usage screen.
     *
     * @return array<string, mixed>
     */
    public function forUser(
        int $userId
    ): array {
        if ($userId <= 0) {
            return $this->freeState();
        }

        $membership = $this
            ->membershipService
            ->resolveForUser(
                $userId
            );

        /*
        * MembershipService normally returns a native boolean, but use the existing
        * project BooleanValue support at this boundary so PostgreSQL/string boolean
        * representations cannot accidentally alter membership presentation.
        */
        if (
            !is_array($membership)
            || !BooleanValue::fromDatabase(
                $membership['isPaid']
                    ?? false
            )
            || (int) ($membership['id'] ?? 0) <= 0
        ) {
            return $this->freeState();
        }

        $membershipId =
            (int) $membership['id'];

        $profileLimit = max(
            0,
            (int) (
                $membership['profileViewLimit']
                ?? 0
            )
        );

        $dailyProfileLimit = max(
            0,
            (int) (
                $membership['dailyProfileViewLimit']
                ?? 0
            )
        );

        $liveIntroductionLimit = max(
            0,
            (int) (
                $membership['liveIntroductionViewLimit']
                ?? 0
            )
        );

        $todayIst = (
            new \DateTimeImmutable(
                'now',
                new \DateTimeZone(
                    self::IST_TIMEZONE
                )
            )
        )->format(
            'Y-m-d'
        );

        $profileUsed = $this
            ->profileUsageModel
            ->consumedCount(
                $membershipId
            );

        $profileUsedToday = $this
            ->profileUsageModel
            ->consumedCountForDate(
                $membershipId,
                $todayIst
            );

        $liveIntroductionUsed = $this
            ->liveIntroductionUsageModel
            ->consumedCount(
                $membershipId
            );

        return [
            'isPaid' =>
            true,

            'membership' =>
            $membership,

            'profileUsage' => [
                'used' =>
                $profileUsed,

                'limit' =>
                $profileLimit,

                'remaining' =>
                max(
                    0,
                    $profileLimit
                        - $profileUsed
                ),

                'usedToday' =>
                $profileUsedToday,

                'dailyLimit' =>
                $dailyProfileLimit,

                'dailyRemaining' =>
                max(
                    0,
                    $dailyProfileLimit
                        - $profileUsedToday
                ),

                'history' =>
                $this
                    ->profileUsageModel
                    ->historyForUser(
                        $userId
                    ),
            ],

            'liveIntroductionUsage' => [
                'used' =>
                $liveIntroductionUsed,

                'limit' =>
                $liveIntroductionLimit,

                'remaining' =>
                max(
                    0,
                    $liveIntroductionLimit
                        - $liveIntroductionUsed
                ),

                'history' =>
                $this
                    ->liveIntroductionUsageModel
                    ->historyForUser(
                        $userId
                    ),
            ],
        ];
    }

    /**
     * Free members have no commercial usage ledger.
     *
     * Keep the shape stable so the Account Settings view remains simple.
     *
     * @return array<string, mixed>
     */
    private function freeState(): array
    {
        return [
            'isPaid' =>
            false,

            'membership' =>
            null,

            'profileUsage' => [
                'used' => 0,
                'limit' => 0,
                'remaining' => 0,
                'usedToday' => 0,
                'dailyLimit' => 0,
                'dailyRemaining' => 0,
                'history' => [],
            ],

            'liveIntroductionUsage' => [
                'used' => 0,
                'limit' => 0,
                'remaining' => 0,
                'history' => [],
            ],
        ];
    }
}
