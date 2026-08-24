<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Commercial usage persistence for Live Introduction playback.
 *
 * This model must not decide whether playback is authorized. Authorization
 * belongs to LiveIntroductionAccessPolicy.
 *
 * Its responsibility is limited to:
 *
 * - membership-scoped uniqueness;
 * - consumed-count queries;
 * - concurrency locking;
 * - successful playback activity.
 */
final class MemberMembershipLiveIntroductionViewModel
extends Model
{
    protected $table =
    'member_membership_live_introduction_views';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $protectFields = true;

    protected $allowedFields = [
        'membership_id',
        'viewer_user_id',
        'owner_user_id',
        'video_introduction_id',
        'first_viewed_at',
        'last_viewed_at',
        'view_count',
    ];

    protected $useTimestamps = true;

    protected $dateFormat = 'datetime';

    protected $createdField = 'created_at';

    protected $updatedField = 'updated_at';

    protected $skipValidation = true;

    /**
     * Return whether this exact approved video version has already consumed
     * allowance during the supplied membership.
     */
    public function hasConsumedVideo(
        int $membershipId,
        int $videoIntroductionId
    ): bool {
        if (
            $membershipId <= 0
            || $videoIntroductionId <= 0
        ) {
            return false;
        }

        return $this
            ->where(
                'membership_id',
                $membershipId
            )
            ->where(
                'video_introduction_id',
                $videoIntroductionId
            )
            ->countAllResults() > 0;
    }

    /**
     * Count distinct approved Live Introductions consumed during this
     * membership.
     */
    public function consumedCount(
        int $membershipId
    ): int {
        if ($membershipId <= 0) {
            return 0;
        }

        return $this
            ->where(
                'membership_id',
                $membershipId
            )
            ->countAllResults();
    }

    /**
     * Lock the active membership row before checking and consuming quota.
     *
     * Every new Live Introduction consumption for one membership is therefore
     * serialized against the same row.
     *
     * @return array<string, mixed>|null
     */
    public function lockMembership(
        int $membershipId
    ): ?array {
        if ($membershipId <= 0) {
            return null;
        }

        $row = $this->db
            ->query(
                <<<'SQL'
                    SELECT
                        id,
                        user_id,
                        status,
                        starts_at,
                        expires_at,
                        live_introduction_view_limit_snapshot
                    FROM member_memberships
                    WHERE id = ?
                    FOR UPDATE
                    SQL,
                [
                    $membershipId,
                ]
            )
            ->getRowArray();

        return is_array($row)
            ? $row
            : null;
    }

    /**
     * Persist the first successful playback of one approved video version.
     */
    public function consume(
        int $membershipId,
        int $viewerUserId,
        int $ownerUserId,
        int $videoIntroductionId,
        string $nowUtc
    ): bool {
        $insertId = $this->insert(
            [
                'membership_id' =>
                $membershipId,

                'viewer_user_id' =>
                $viewerUserId,

                'owner_user_id' =>
                $ownerUserId,

                'video_introduction_id' =>
                $videoIntroductionId,

                'first_viewed_at' =>
                $nowUtc,

                'last_viewed_at' =>
                $nowUtc,

                'view_count' =>
                1,
            ],
            true
        );

        return is_numeric(
            $insertId
        );
    }

    /**
     * Record another playback request for an already-consumed video version.
     *
     * Replays remain free from the commercial quota perspective.
     */
    public function recordRepeatView(
        int $membershipId,
        int $videoIntroductionId,
        string $nowUtc
    ): void {
        $this->db->query(
            <<<'SQL'
                UPDATE member_membership_live_introduction_views
                SET
                    view_count = view_count + 1,
                    last_viewed_at = ?,
                    updated_at = ?
                WHERE membership_id = ?
                AND video_introduction_id = ?
                SQL,
            [
                $nowUtc,
                $nowUtc,
                $membershipId,
                $videoIntroductionId,
            ]
        );
    }
}
