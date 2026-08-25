<?php

declare(strict_types=1);

namespace App\Services\Membership;

use App\Models\MemberMembershipLiveIntroductionViewModel;
use App\Models\MemberMembershipModel;
use App\Models\MemberMembershipProfileViewModel;

/**
 * Builds member-facing membership and commercial usage history.
 *
 * This is a read-only presentation service.
 *
 * It deliberately does not make entitlement decisions. MembershipService,
 * ProfileAccessPolicy and LiveIntroductionAccessPolicy remain the respective
 * authorities for product access.
 */
final class MemberMembershipHistoryService
{
    private const IST_TIMEZONE =
    'Asia/Kolkata';

    public function __construct(
        private readonly MembershipService
        $membershipService,

        private readonly MemberMembershipModel
        $membershipModel,

        private readonly MemberMembershipProfileViewModel
        $profileUsageModel,

        private readonly MemberMembershipLiveIntroductionViewModel
        $liveIntroductionUsageModel
    ) {}

    /**
     * Return all membership/usage information required by Account Settings.
     *
     * @return array<string, mixed>
     */
    public function historyForUser(
        int $userId
    ): array {
        if ($userId <= 0) {
            return $this->emptyHistory();
        }

        $current =
            $this->membershipService
            ->resolveForUser(
                $userId
            );

        $memberships =
            array_map(
                fn(array $membership): array =>
                $this->normalizeMembership(
                    $membership
                ),
                $this->membershipModel
                    ->historyForUser(
                        $userId
                    )
            );

        $profileUsage =
            array_map(
                fn(array $usage): array =>
                $this->normalizeProfileUsage(
                    $usage
                ),
                $this->profileUsageModel
                    ->historyForUser(
                        $userId
                    )
            );

        $liveIntroductionUsage =
            array_map(
                fn(array $usage): array =>
                $this->normalizeLiveIntroductionUsage(
                    $usage
                ),
                $this->liveIntroductionUsageModel
                    ->historyForUser(
                        $userId
                    )
            );

        return [
            'currentMembership' =>
            $current,

            'membershipHistory' =>
            $memberships,

            'profileUsageHistory' =>
            $profileUsage,

            'liveIntroductionUsageHistory' =>
            $liveIntroductionUsage,
        ];
    }

    /**
     * Normalize one immutable purchased-membership snapshot.
     *
     * @param array<string, mixed> $membership
     *
     * @return array<string, mixed>
     */
    private function normalizeMembership(
        array $membership
    ): array {
        $status =
            mb_strtoupper(
                trim(
                    (string) (
                        $membership['status']
                        ?? ''
                    )
                )
            );

        return [
            'id' =>
            max(
                0,
                (int) (
                    $membership['id']
                    ?? 0
                )
            ),

            'planCode' =>
            mb_strtoupper(
                trim(
                    (string) (
                        $membership['plan_code_snapshot']
                        ?? ''
                    )
                )
            ),

            'planName' =>
            trim(
                (string) (
                    $membership['plan_name_snapshot']
                    ?? ''
                )
            ),

            'status' =>
            $status,

            'statusLabel' =>
            match ($status) {
                MemberMembershipModel::STATUS_ACTIVE =>
                'Active',

                MemberMembershipModel::STATUS_EXPIRED =>
                'Expired',

                MemberMembershipModel::STATUS_REPLACED =>
                'Replaced',

                MemberMembershipModel::STATUS_CANCELLED =>
                'Cancelled',

                default =>
                'Unknown',
            },

            'pricePaise' =>
            max(
                0,
                (int) (
                    $membership['price_paise_snapshot']
                    ?? 0
                )
            ),

            'durationMonths' =>
            max(
                0,
                (int) (
                    $membership['duration_months_snapshot']
                    ?? 0
                )
            ),

            'profileViewLimit' =>
            max(
                0,
                (int) (
                    $membership['profile_view_limit_snapshot']
                    ?? 0
                )
            ),

            'dailyProfileViewLimit' =>
            max(
                0,
                (int) (
                    $membership['daily_profile_view_limit_snapshot']
                    ?? 0
                )
            ),

            'liveIntroductionViewLimit' =>
            max(
                0,
                (int) (
                    $membership['live_introduction_view_limit_snapshot']
                    ?? 0
                )
            ),

            'startsAt' =>
            (string) (
                $membership['starts_at']
                ?? ''
            ),

            'expiresAt' =>
            (string) (
                $membership['expires_at']
                ?? ''
            ),

            'createdAt' =>
            (string) (
                $membership['created_at']
                ?? ''
            ),
        ];
    }

    /**
     * @param array<string, mixed> $usage
     *
     * @return array<string, mixed>
     */
    private function normalizeProfileUsage(
        array $usage
    ): array {
        return [
            'membershipId' =>
            max(
                0,
                (int) (
                    $usage['membership_id']
                    ?? 0
                )
            ),

            'profileReference' =>
            trim(
                (string) (
                    $usage['profile_reference']
                    ?? ''
                )
            ),

            'planName' =>
            trim(
                (string) (
                    $usage['plan_name_snapshot']
                    ?? ''
                )
            ),

            'usageDateIst' =>
            trim(
                (string) (
                    $usage['usage_date_ist']
                    ?? ''
                )
            ),

            'firstViewedAt' =>
            (string) (
                $usage['first_viewed_at']
                ?? ''
            ),

            'lastViewedAt' =>
            (string) (
                $usage['last_viewed_at']
                ?? ''
            ),

            'viewCount' =>
            max(
                1,
                (int) (
                    $usage['view_count']
                    ?? 1
                )
            ),
        ];
    }

    /**
     * @param array<string, mixed> $usage
     *
     * @return array<string, mixed>
     */
    private function normalizeLiveIntroductionUsage(
        array $usage
    ): array {
        return [
            'membershipId' =>
            max(
                0,
                (int) (
                    $usage['membership_id']
                    ?? 0
                )
            ),

            'profileReference' =>
            trim(
                (string) (
                    $usage['profile_reference']
                    ?? ''
                )
            ),

            'planName' =>
            trim(
                (string) (
                    $usage['plan_name_snapshot']
                    ?? ''
                )
            ),

            'videoIntroductionId' =>
            max(
                0,
                (int) (
                    $usage['video_introduction_id']
                    ?? 0
                )
            ),

            'firstViewedAt' =>
            (string) (
                $usage['first_viewed_at']
                ?? ''
            ),

            'lastViewedAt' =>
            (string) (
                $usage['last_viewed_at']
                ?? ''
            ),

            'viewCount' =>
            max(
                1,
                (int) (
                    $usage['view_count']
                    ?? 1
                )
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyHistory(): array
    {
        return [
            'currentMembership' => [
                'accountType' =>
                MembershipService::ACCOUNT_FREE,

                'accountLabel' =>
                'Free Account',

                'isPaid' =>
                false,

                'membership' =>
                null,
            ],

            'membershipHistory' =>
            [],

            'profileUsageHistory' =>
            [],

            'liveIntroductionUsageHistory' =>
            [],
        ];
    }
}
