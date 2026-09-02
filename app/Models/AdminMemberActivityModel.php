<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Database\BaseConnection;

/**
 * Read-only administrator projection for member-to-member activity.
 *
 * This model deliberately returns unique related member IDs.
 * The Admin activity count and the activity profile listing therefore
 * always use the same authoritative relationship definition.
 */
final class AdminMemberActivityModel
{
    public function __construct(
        private readonly BaseConnection $database
    ) {}

    /**
     * @return list<int>
     */
    public function memberIds(
        int $memberUserId,
        string $activityType
    ): array {
        if ($memberUserId <= 0) {
            return [];
        }

        $rows = match ($activityType) {
            'interest-received' =>
            $this->interestReceived(
                $memberUserId
            ),

            'interest-sent' =>
            $this->interestSent(
                $memberUserId
            ),

            'profiles-shortlisted' =>
            $this->profilesShortlisted(
                $memberUserId
            ),

            'shortlisted-by' =>
            $this->shortlistedBy(
                $memberUserId
            ),

            'interests-accepted' =>
            $this->interestsAccepted(
                $memberUserId
            ),

            'interests-rejected' =>
            $this->interestsRejected(
                $memberUserId
            ),

            'profiles-viewed' =>
            $this->profilesViewed(
                $memberUserId
            ),

            'profile-viewed-by' =>
            $this->profileViewedBy(
                $memberUserId
            ),

            'videos-watched' =>
            $this->videosWatched(
                $memberUserId
            ),

            'video-viewed-by' =>
            $this->videoViewedBy(
                $memberUserId
            ),

            'mutual-interests' =>
            $this->mutualInterests(
                $memberUserId
            ),

            'profiles-blocked' =>
            $this->profilesBlocked(
                $memberUserId
            ),

            'blocked-by' =>
            $this->blockedBy(
                $memberUserId
            ),

            default =>
            [],
        };

        return $this->normalizeIds(
            $rows
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function interestReceived(
        int $memberUserId
    ): array {
        return $this->database
            ->table('member_interests')
            ->select(
                'from_user_id AS member_id'
            )
            ->where(
                'to_user_id',
                $memberUserId
            )
            ->orderBy(
                'created_at',
                'DESC'
            )
            ->get()
            ->getResultArray();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function interestSent(
        int $memberUserId
    ): array {
        return $this->database
            ->table('member_interests')
            ->select(
                'to_user_id AS member_id'
            )
            ->where(
                'from_user_id',
                $memberUserId
            )
            ->orderBy(
                'created_at',
                'DESC'
            )
            ->get()
            ->getResultArray();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function profilesShortlisted(
        int $memberUserId
    ): array {
        return $this->database
            ->table('member_shortlists')
            ->select(
                'shortlisted_user_id AS member_id'
            )
            ->where(
                'user_id',
                $memberUserId
            )
            ->orderBy(
                'created_at',
                'DESC'
            )
            ->get()
            ->getResultArray();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function shortlistedBy(
        int $memberUserId
    ): array {
        return $this->database
            ->table('member_shortlists')
            ->select(
                'user_id AS member_id'
            )
            ->where(
                'shortlisted_user_id',
                $memberUserId
            )
            ->orderBy(
                'created_at',
                'DESC'
            )
            ->get()
            ->getResultArray();
    }

    /**
     * Interest accepted BY this member.
     *
     * Therefore:
     *
     * other member -> this member
     * status = ACCEPTED
     *
     * @return list<array<string, mixed>>
     */
    private function interestsAccepted(
        int $memberUserId
    ): array {
        return $this->database
            ->table('member_interests')
            ->select(
                'from_user_id AS member_id'
            )
            ->where(
                'to_user_id',
                $memberUserId
            )
            ->where(
                'status',
                MemberInterestModel::STATUS_ACCEPTED
            )
            ->orderBy(
                'responded_at',
                'DESC'
            )
            ->get()
            ->getResultArray();
    }

    /**
     * Interest rejected BY this member.
     *
     * @return list<array<string, mixed>>
     */
    private function interestsRejected(
        int $memberUserId
    ): array {
        return $this->database
            ->table('member_interests')
            ->select(
                'from_user_id AS member_id'
            )
            ->where(
                'to_user_id',
                $memberUserId
            )
            ->where(
                'status',
                MemberInterestModel::STATUS_DECLINED
            )
            ->orderBy(
                'responded_at',
                'DESC'
            )
            ->get()
            ->getResultArray();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function profilesViewed(
        int $memberUserId
    ): array {
        return $this->database
            ->table('member_profile_views')
            ->select(
                'viewed_user_id AS member_id'
            )
            ->where(
                'viewer_user_id',
                $memberUserId
            )
            ->orderBy(
                'last_viewed_at',
                'DESC'
            )
            ->get()
            ->getResultArray();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function profileViewedBy(
        int $memberUserId
    ): array {
        return $this->database
            ->table('member_profile_views')
            ->select(
                'viewer_user_id AS member_id'
            )
            ->where(
                'viewed_user_id',
                $memberUserId
            )
            ->orderBy(
                'last_viewed_at',
                'DESC'
            )
            ->get()
            ->getResultArray();
    }

    /**
     * Live Introductions watched by this member.
     *
     * DISTINCT is required because the same candidate may have been viewed
     * under more than one historical membership.
     *
     * @return list<array<string, mixed>>
     */
    private function videosWatched(
        int $memberUserId
    ): array {
        return $this->database
            ->table(
                'member_membership_live_introduction_views'
            )
            ->select(
                'owner_user_id AS member_id'
            )
            ->where(
                'viewer_user_id',
                $memberUserId
            )
            ->orderBy(
                'MAX(last_viewed_at)',
                'DESC',
                false
            )
            ->groupBy(
                'owner_user_id'
            )
            ->get()
            ->getResultArray();
    }

    /**
     * Members who watched this member's Live Introduction.
     *
     * @return list<array<string, mixed>>
     */
    private function videoViewedBy(
        int $memberUserId
    ): array {
        return $this->database
            ->table(
                'member_membership_live_introduction_views'
            )
            ->select(
                'viewer_user_id AS member_id'
            )
            ->where(
                'owner_user_id',
                $memberUserId
            )
            ->orderBy(
                'MAX(last_viewed_at)',
                'DESC',
                false
            )
            ->groupBy(
                'viewer_user_id'
            )
            ->get()
            ->getResultArray();
    }

    /**
     * An ACCEPTED Interest represents the established mutual Interest
     * relationship in the current application model.
     *
     * Interest creation already prevents another reverse Interest between
     * the same member pair.
     *
     * @return list<array<string, mixed>>
     */
    private function mutualInterests(
        int $memberUserId
    ): array {
        return $this->database
            ->table('member_interests')
            ->select(
                '
                CASE
                    WHEN from_user_id = '
                    . $memberUserId
                    . '
                    THEN to_user_id
                    ELSE from_user_id
                END AS member_id',
                false
            )
            ->where(
                'status',
                MemberInterestModel::STATUS_ACCEPTED
            )
            ->groupStart()
            ->where(
                'from_user_id',
                $memberUserId
            )
            ->orWhere(
                'to_user_id',
                $memberUserId
            )
            ->groupEnd()
            ->orderBy(
                'responded_at',
                'DESC'
            )
            ->get()
            ->getResultArray();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function profilesBlocked(
        int $memberUserId
    ): array {
        return $this->database
            ->table('member_blocks')
            ->select(
                'blocked_user_id AS member_id'
            )
            ->where(
                'blocker_user_id',
                $memberUserId
            )
            ->orderBy(
                'created_at',
                'DESC'
            )
            ->get()
            ->getResultArray();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function blockedBy(
        int $memberUserId
    ): array {
        return $this->database
            ->table('member_blocks')
            ->select(
                'blocker_user_id AS member_id'
            )
            ->where(
                'blocked_user_id',
                $memberUserId
            )
            ->orderBy(
                'created_at',
                'DESC'
            )
            ->get()
            ->getResultArray();
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return list<int>
     */
    private function normalizeIds(
        array $rows
    ): array {
        return array_values(
            array_unique(
                array_filter(
                    array_map(
                        static fn(array $row): int =>
                        max(
                            0,
                            (int) (
                                $row['member_id']
                                ?? 0
                            )
                        ),
                        $rows
                    ),
                    static fn(int $memberId): bool =>
                    $memberId > 0
                )
            )
        );
    }
}
