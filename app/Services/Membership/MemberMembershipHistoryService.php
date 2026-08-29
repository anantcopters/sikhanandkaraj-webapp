<?php

declare(strict_types=1);

namespace App\Services\Membership;

use App\Models\MemberMembershipLiveIntroductionViewModel;
use App\Models\MemberMembershipModel;
use App\Models\MemberMembershipProfileViewModel;
use App\Support\DateDisplay;

/**
 * Builds member-facing membership and commercial usage history.
 *
 * This is a read-only presentation service.
 *
 * It deliberately does not make entitlement decisions. MembershipService,
 * ProfileAccessPolicy and LiveIntroductionAccessPolicy remain the respective
 * authorities for product access.
 *
 * Presentation values are prepared here so Account Settings views do not
 * duplicate date/time conversion logic.
 */
final class MemberMembershipHistoryService
{
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

        /*
         * MembershipService remains the source of truth for the current
         * membership. We only enrich its timestamps for presentation.
         */
        if (
            is_array($current)
            && isset($current['membership'])
            && is_array($current['membership'])
        ) {
            $current['membership'] =
                $this->withDisplayTimestamps(
                    $current['membership']
                );
        }

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

        $startsAt =
            (string) (
                $membership['starts_at']
                ?? ''
            );

        $expiresAt =
            (string) (
                $membership['expires_at']
                ?? ''
            );

        $createdAt =
            (string) (
                $membership['created_at']
                ?? ''
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

            /*
             * Keep raw values available where needed, but presentation values
             * are generated here through the existing DateDisplay support.
             */
            'startsAt' =>
            $startsAt,

            'startsAtDisplay' =>
            DateDisplay::formatUtcDateTime(
                $startsAt
            ),

            'startsAtIso' =>
            DateDisplay::utcToDisplayIso(
                $startsAt
            ),

            'expiresAt' =>
            $expiresAt,

            'expiresAtDisplay' =>
            DateDisplay::formatUtcDateTime(
                $expiresAt
            ),

            'expiresAtIso' =>
            DateDisplay::utcToDisplayIso(
                $expiresAt
            ),

            'createdAt' =>
            $createdAt,

            'createdAtDisplay' =>
            DateDisplay::formatUtcDateTime(
                $createdAt
            ),

            'createdAtIso' =>
            DateDisplay::utcToDisplayIso(
                $createdAt
            ),
        ];
    }

    /**
     * Normalize one Verified Profile usage row.
     *
     * @param array<string, mixed> $usage
     *
     * @return array<string, mixed>
     */
    private function normalizeProfileUsage(
        array $usage
    ): array {
        $firstViewedAt =
            (string) (
                $usage['first_viewed_at']
                ?? ''
            );

        $lastViewedAt =
            (string) (
                $usage['last_viewed_at']
                ?? ''
            );

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

            /*
             * This is a business-ledger calendar date, not a UTC timestamp.
             * Do not run it through DateDisplay timezone conversion.
             */
            'usageDateIst' =>
            trim(
                (string) (
                    $usage['usage_date_ist']
                    ?? ''
                )
            ),

            'firstViewedAt' =>
            $firstViewedAt,

            'firstViewedAtDisplay' =>
            DateDisplay::formatUtcDateTime(
                $firstViewedAt
            ),

            'firstViewedAtIso' =>
            DateDisplay::utcToDisplayIso(
                $firstViewedAt
            ),

            'lastViewedAt' =>
            $lastViewedAt,

            'lastViewedAtDisplay' =>
            DateDisplay::formatUtcDateTime(
                $lastViewedAt
            ),

            'lastViewedAtIso' =>
            DateDisplay::utcToDisplayIso(
                $lastViewedAt
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
     * Normalize one Live Introduction usage row.
     *
     * @param array<string, mixed> $usage
     *
     * @return array<string, mixed>
     */
    private function normalizeLiveIntroductionUsage(
        array $usage
    ): array {
        $firstViewedAt =
            (string) (
                $usage['first_viewed_at']
                ?? ''
            );

        $lastViewedAt =
            (string) (
                $usage['last_viewed_at']
                ?? ''
            );

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
            $firstViewedAt,

            'firstViewedAtDisplay' =>
            DateDisplay::formatUtcDateTime(
                $firstViewedAt
            ),

            'firstViewedAtIso' =>
            DateDisplay::utcToDisplayIso(
                $firstViewedAt
            ),

            'lastViewedAt' =>
            $lastViewedAt,

            'lastViewedAtDisplay' =>
            DateDisplay::formatUtcDateTime(
                $lastViewedAt
            ),

            'lastViewedAtIso' =>
            DateDisplay::utcToDisplayIso(
                $lastViewedAt
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
     * Add display-safe timestamp values to the current membership resolved
     * by MembershipService.
     *
     * The raw values remain unchanged because authorization/lifecycle logic
     * must continue using authoritative membership timestamps.
     *
     * @param array<string, mixed> $membership
     *
     * @return array<string, mixed>
     */
    private function withDisplayTimestamps(
        array $membership
    ): array {
        $startsAt =
            $membership['startsAt']
            ?? null;

        $expiresAt =
            $membership['expiresAt']
            ?? null;

        $membership['startsAtDisplay'] =
            DateDisplay::formatUtcDateTime(
                $startsAt
            );

        $membership['startsAtIso'] =
            DateDisplay::utcToDisplayIso(
                $startsAt
            );

        $membership['expiresAtDisplay'] =
            DateDisplay::formatUtcDateTime(
                $expiresAt
            );

        $membership['expiresAtIso'] =
            DateDisplay::utcToDisplayIso(
                $expiresAt
            );

        return $membership;
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
