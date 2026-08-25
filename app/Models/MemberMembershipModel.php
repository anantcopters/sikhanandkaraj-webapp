<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Persists purchased member membership instances.
 *
 * A row represents one commercial membership period.
 *
 * IMPORTANT:
 *
 * Historical memberships are retained permanently. Expiry, replacement and
 * cancellation change lifecycle status only; an old membership is never
 * converted into the member's next membership.
 *
 * Runtime access must continue checking starts_at/expires_at even though the
 * housekeeping process also converts expired ACTIVE rows to EXPIRED.
 */
final class MemberMembershipModel extends Model
{
    public const STATUS_ACTIVE =
    'ACTIVE';

    public const STATUS_EXPIRED =
    'EXPIRED';

    public const STATUS_REPLACED =
    'REPLACED';

    public const STATUS_CANCELLED =
    'CANCELLED';

    protected $table =
    'member_memberships';

    protected $primaryKey =
    'id';

    protected $returnType =
    'array';

    protected $useAutoIncrement =
    true;

    protected $allowedFields = [
        'user_id',
        'membership_plan_id',
        'status',
        'starts_at',
        'expires_at',
        'plan_code_snapshot',
        'plan_name_snapshot',
        'price_paise_snapshot',
        'duration_months_snapshot',
        'profile_view_limit_snapshot',
        'daily_profile_view_limit_snapshot',
        'live_introduction_view_limit_snapshot',
        'has_match_manager_snapshot',
        'commercial_priority_snapshot',
        'replaced_by_membership_id',
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
     * Resolve the currently usable paid membership.
     *
     * SECURITY:
     *
     * The timestamp check deliberately remains here even though lifecycle
     * housekeeping changes expired ACTIVE rows to EXPIRED.
     *
     * Therefore membership access expires immediately at expires_at even if
     * the housekeeping cron has not executed yet.
     *
     * @return array<string, mixed>|null
     */
    public function activeForUser(
        int $userId,
        string $nowUtc
    ): ?array {
        if ($userId <= 0) {
            return null;
        }

        $record =
            $this
            ->where(
                'user_id',
                $userId
            )
            ->where(
                'status',
                self::STATUS_ACTIVE
            )
            ->where(
                'starts_at <=',
                $nowUtc
            )
            ->where(
                'expires_at >',
                $nowUtc
            )
            ->orderBy(
                'starts_at',
                'DESC'
            )
            ->orderBy(
                'id',
                'DESC'
            )
            ->first();

        return is_array($record)
            ? $record
            : null;
    }

    /**
     * Return membership history newest first.
     *
     * This is the commercial membership history shown to the member.
     *
     * @return list<array<string, mixed>>
     */
    public function historyForUser(
        int $userId
    ): array {
        if ($userId <= 0) {
            return [];
        }

        $rows =
            $this
            ->where(
                'user_id',
                $userId
            )
            ->orderBy(
                'starts_at',
                'DESC'
            )
            ->orderBy(
                'id',
                'DESC'
            )
            ->findAll();

        return array_values(
            array_filter(
                $rows,
                'is_array'
            )
        );
    }

    /**
     * Return ACTIVE rows whose commercial validity has ended.
     *
     * This is intentionally used only by lifecycle housekeeping.
     *
     * Product authorization must never depend on this method having run.
     *
     * @return list<array<string, mixed>>
     */
    public function expiredActiveMemberships(
        string $nowUtc,
        int $limit = 500
    ): array {
        $limit =
            max(
                1,
                min(
                    1000,
                    $limit
                )
            );

        $rows =
            $this
            ->where(
                'status',
                self::STATUS_ACTIVE
            )
            ->where(
                'expires_at <=',
                $nowUtc
            )
            ->orderBy(
                'expires_at',
                'ASC'
            )
            ->orderBy(
                'id',
                'ASC'
            )
            ->findAll(
                $limit
            );

        return array_values(
            array_filter(
                $rows,
                'is_array'
            )
        );
    }

    /**
     * Mark one membership expired.
     *
     * The status condition makes the operation idempotent:
     *
     * - ACTIVE -> EXPIRED is allowed;
     * - EXPIRED stays EXPIRED;
     * - CANCELLED stays CANCELLED;
     * - REPLACED stays REPLACED.
     *
     * A housekeeping process must never overwrite a stronger lifecycle state.
     */
    public function markExpired(
        int $membershipId
    ): bool {
        if ($membershipId <= 0) {
            return false;
        }

        return $this
            ->where(
                'id',
                $membershipId
            )
            ->where(
                'status',
                self::STATUS_ACTIVE
            )
            ->set(
                [
                    'status' =>
                    self::STATUS_EXPIRED,
                ]
            )
            ->update();
    }

    /**
     * Count ACTIVE memberships that have passed expires_at.
     *
     * Primarily useful for lifecycle monitoring/CLI reporting.
     */
    public function expiredActiveCount(
        string $nowUtc
    ): int {
        return (int) $this
            ->where(
                'status',
                self::STATUS_ACTIVE
            )
            ->where(
                'expires_at <=',
                $nowUtc
            )
            ->countAllResults();
    }
}
