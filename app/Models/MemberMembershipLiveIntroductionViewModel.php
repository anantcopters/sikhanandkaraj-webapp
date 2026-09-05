<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Commercial usage persistence for Live Introduction playback.
 *
 * Authorization belongs to LiveIntroductionAccessPolicy.
 *
 * IMPORTANT COMMERCIAL RULE:
 *
 * Live Introduction allowance is consumed per candidate/member, not per
 * uploaded video version.
 *
 * Therefore:
 *
 *     membership_id + owner_user_id
 *
 * identifies one commercial consumption.
 *
 * Re-uploading/replacing an approved Live Introduction for the same member
 * must never consume another allowance during the same membership.
 */
final class MemberMembershipLiveIntroductionViewModel
extends Model
{
    protected $table =
    'member_membership_live_introduction_views';

    protected $primaryKey =
    'id';

    protected $returnType =
    'array';

    protected $useAutoIncrement =
    true;

    protected $protectFields =
    true;

    protected $allowedFields = [
        'membership_id',
        'viewer_user_id',
        'owner_user_id',
        'video_introduction_id',
        'first_viewed_at',
        'last_viewed_at',
        'view_count',
    ];

    protected $useTimestamps =
    true;

    protected $dateFormat =
    'datetime';

    protected $createdField =
    'created_at';

    protected $updatedField =
    'updated_at';

    protected $skipValidation =
    true;

    /**
     * Return whether this candidate's Live Introduction has already consumed
     * allowance during the supplied membership.
     *
     * Deliberately use owner_user_id rather than video_introduction_id.
     */
    public function hasConsumedOwner(
        int $membershipId,
        int $ownerUserId
    ): bool {
        if (
            $membershipId <= 0
            || $ownerUserId <= 0
        ) {
            return false;
        }

        return $this
            ->where(
                'membership_id',
                $membershipId
            )
            ->where(
                'owner_user_id',
                $ownerUserId
            )
            ->countAllResults() > 0;
    }

    /**
     * Count distinct candidate Live Introductions consumed during this
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
     * Return member-facing Live Introduction usage history.
     *
     * Usage history is commercial history. A replacement video therefore
     * updates the same candidate's activity rather than creating a second
     * allowance entry.
     *
     * @return list<array<string, mixed>>
     */
    public function historyForUser(
        int $viewerUserId,
        int $limit = 100
    ): array {
        if ($viewerUserId <= 0) {
            return [];
        }

        $limit = max(
            1,
            min(
                200,
                $limit
            )
        );

        $rows = $this->db
            ->table(
                $this->table . ' usage'
            )
            ->select(
                '
                    usage.id,
                    usage.membership_id,
                    usage.owner_user_id,
                    usage.video_introduction_id,
                    usage.first_viewed_at,
                    usage.last_viewed_at,
                    usage.view_count,

                    owner.profile_ref_number
                        AS profile_reference,

                    membership.plan_code_snapshot,
                    membership.plan_name_snapshot
                ',
                false
            )
            ->join(
                'users owner',
                'owner.id = usage.owner_user_id',
                'left'
            )
            ->join(
                'member_memberships membership',
                'membership.id = usage.membership_id',
                'inner'
            )
            ->where(
                'usage.viewer_user_id',
                $viewerUserId
            )
            ->orderBy(
                'usage.last_viewed_at',
                'DESC'
            )
            ->orderBy(
                'usage.id',
                'DESC'
            )
            ->limit(
                $limit
            )
            ->get()
            ->getResultArray();

        return array_values(
            array_filter(
                $rows,
                'is_array'
            )
        );
    }

    /**
     * Return paginated member-facing Live Introduction usage history.
     *
     * Search is intentionally limited to data already exposed in the
     * authenticated member's own commercial usage ledger:
     *
     * - Profile ID
     * - Membership plan name
     * - Membership plan code
     *
     * @return array{
     *     rows: list<array<string, mixed>>,
     *     total: int
     * }
     */
    public function paginatedHistoryForUser(
        int $viewerUserId,
        string $search,
        int $page,
        int $perPage = 10
    ): array {
        if ($viewerUserId <= 0) {
            return [
                'rows' => [],
                'total' => 0,
            ];
        }

        $search = trim($search);

        $page = max(
            1,
            $page
        );

        $perPage = max(
            1,
            min(
                50,
                $perPage
            )
        );

        $offset =
            ($page - 1)
            * $perPage;

        $countBuilder =
            $this->db
            ->table(
                $this->table . ' usage'
            )
            ->join(
                'users owner',
                'owner.id = usage.owner_user_id',
                'left'
            )
            ->join(
                'member_memberships membership',
                'membership.id = usage.membership_id',
                'inner'
            )
            ->where(
                'usage.viewer_user_id',
                $viewerUserId
            );

        if ($search !== '') {
            $countBuilder
                ->groupStart()
                ->like(
                    'owner.profile_ref_number',
                    $search
                )
                ->orLike(
                    'membership.plan_name_snapshot',
                    $search
                )
                ->orLike(
                    'membership.plan_code_snapshot',
                    $search
                )
                ->groupEnd();
        }

        $total =
            $countBuilder
            ->countAllResults();

        $rowsBuilder =
            $this->db
            ->table(
                $this->table . ' usage'
            )
            ->select(
                '
                usage.id,
                usage.membership_id,
                usage.owner_user_id,
                usage.video_introduction_id,
                usage.first_viewed_at,
                usage.last_viewed_at,
                usage.view_count,

                owner.profile_ref_number
                    AS profile_reference,

                membership.plan_code_snapshot,
                membership.plan_name_snapshot
            ',
                false
            )
            ->join(
                'users owner',
                'owner.id = usage.owner_user_id',
                'left'
            )
            ->join(
                'member_memberships membership',
                'membership.id = usage.membership_id',
                'inner'
            )
            ->where(
                'usage.viewer_user_id',
                $viewerUserId
            );

        if ($search !== '') {
            $rowsBuilder
                ->groupStart()
                ->like(
                    'owner.profile_ref_number',
                    $search
                )
                ->orLike(
                    'membership.plan_name_snapshot',
                    $search
                )
                ->orLike(
                    'membership.plan_code_snapshot',
                    $search
                )
                ->groupEnd();
        }

        $rows =
            $rowsBuilder
            ->orderBy(
                'usage.last_viewed_at',
                'DESC'
            )
            ->orderBy(
                'usage.id',
                'DESC'
            )
            ->limit(
                $perPage,
                $offset
            )
            ->get()
            ->getResultArray();

        return [
            'rows' =>
            array_values(
                array_filter(
                    $rows,
                    'is_array'
                )
            ),

            'total' =>
            max(
                0,
                (int) $total
            ),
        ];
    }

    /**
     * Lock the membership before checking/consuming quota.
     *
     * Every first-time Live Introduction consumption for a membership is
     * serialized against the same membership row. This prevents concurrent
     * requests from consuming beyond the membership allowance.
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
     * Persist the first successful Live Introduction access for one candidate.
     *
     * video_introduction_id is retained as audit information showing which
     * approved video version was first viewed when the allowance was consumed.
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
     * Record another successful playback for an already-consumed candidate.
     *
     * A replacement video remains free from the commercial quota perspective.
     *
     * We update video_introduction_id so support/audit can see the latest
     * approved video version that was successfully accessed.
     */
    public function recordRepeatView(
        int $membershipId,
        int $ownerUserId,
        int $videoIntroductionId,
        string $nowUtc
    ): void {
        $this->db->query(
            <<<'SQL'
                UPDATE member_membership_live_introduction_views
                SET
                    video_introduction_id = ?,
                    view_count = view_count + 1,
                    last_viewed_at = ?,
                    updated_at = ?
                WHERE membership_id = ?
                AND owner_user_id = ?
                SQL,
            [
                $videoIntroductionId,
                $nowUtc,
                $nowUtc,
                $membershipId,
                $ownerUserId,
            ]
        );
    }
}
