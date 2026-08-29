<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Persists membership-scoped Full Profile consumption.
 *
 * This model is deliberately separate from MemberProfileViewModel:
 *
 * MemberProfileViewModel
 *     = general member interaction/activity history.
 *
 * MemberMembershipProfileViewModel
 *     = commercial membership allowance consumption.
 */
final class MemberMembershipProfileViewModel extends Model
{
    protected $table =
    'member_membership_profile_views';

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
        'viewed_user_id',
        'usage_date_ist',
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
     * Return whether this target has already consumed allowance during the
     * supplied membership.
     */
    public function hasConsumedTarget(
        int $membershipId,
        int $viewedUserId
    ): bool {
        if (
            $membershipId <= 0
            || $viewedUserId <= 0
        ) {
            return false;
        }

        return $this
            ->where(
                'membership_id',
                $membershipId
            )
            ->where(
                'viewed_user_id',
                $viewedUserId
            )
            ->countAllResults() > 0;
    }

    /**
     * Count unique Verified Profiles consumed during this membership.
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
     * Count newly consumed profiles for one IST calendar day.
     */
    public function consumedCountForDate(
        int $membershipId,
        string $usageDateIst
    ): int {
        if (
            $membershipId <= 0
            || $usageDateIst === ''
        ) {
            return 0;
        }

        return $this
            ->where(
                'membership_id',
                $membershipId
            )
            ->where(
                'usage_date_ist',
                $usageDateIst
            )
            ->countAllResults();
    }

    /**
     * Return member-facing Full Profile consumption history.
     *
     * Only information already belonging to the viewing member's commercial
     * usage ledger is returned.
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

        $limit =
            max(
                1,
                min(
                    200,
                    $limit
                )
            );

        $rows =
            $this->db
            ->table(
                $this->table . ' usage'
            )
            ->select(
                '
                    usage.id,
                    usage.membership_id,
                    usage.viewed_user_id,
                    usage.usage_date_ist,
                    usage.first_viewed_at,
                    usage.last_viewed_at,
                    usage.view_count,

                    target.profile_ref_number
                        AS profile_reference,

                    membership.plan_code_snapshot,
                    membership.plan_name_snapshot
                ',
                false
            )
            ->join(
                'users target',
                'target.id = usage.viewed_user_id',
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
     * Lock one membership before checking and consuming quota.
     *
     * Concurrent requests from the same member must serialize against the
     * membership row. Otherwise two simultaneous requests could both observe
     * an available final slot and consume beyond the commercial limit.
     *
     * @return array<string, mixed>|null
     */
    public function lockMembership(
        int $membershipId
    ): ?array {
        if ($membershipId <= 0) {
            return null;
        }

        $record =
            $this->db
            ->query(
                <<<'SQL'
                    SELECT
                        id,
                        user_id,
                        status,
                        starts_at,
                        expires_at,
                        profile_view_limit_snapshot,
                        daily_profile_view_limit_snapshot
                    FROM member_memberships
                    WHERE id = ?
                    FOR UPDATE
                    SQL,
                [
                    $membershipId,
                ]
            )
            ->getRowArray();

        return is_array($record)
            ? $record
            : null;
    }

    /**
     * Record the first commercial consumption for one target.
     *
     * The database unique constraint remains the final concurrency guard.
     */
    public function consume(
        int $membershipId,
        int $viewerUserId,
        int $viewedUserId,
        string $usageDateIst,
        string $nowUtc
    ): bool {
        $insertId =
            $this->insert(
                [
                    'membership_id' =>
                    $membershipId,

                    'viewer_user_id' =>
                    $viewerUserId,

                    'viewed_user_id' =>
                    $viewedUserId,

                    'usage_date_ist' =>
                    $usageDateIst,

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
     * Record another successful opening of a previously consumed target.
     *
     * This does NOT consume another allowance.
     */
    public function recordRepeatView(
        int $membershipId,
        int $viewedUserId,
        string $nowUtc
    ): void {
        $this->db->query(
            <<<'SQL'
                UPDATE member_membership_profile_views
                SET
                    view_count = view_count + 1,
                    last_viewed_at = ?,
                    updated_at = ?
                WHERE membership_id = ?
                AND viewed_user_id = ?
                SQL,
            [
                $nowUtc,
                $nowUtc,
                $membershipId,
                $viewedUserId,
            ]
        );
    }
}
