<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Persists purchased member membership instances.
 *
 * A row represents one commercial membership period. Historical memberships
 * are retained; they are never converted into the member's next plan.
 */
final class MemberMembershipModel extends Model
{
    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_EXPIRED = 'EXPIRED';

    public const STATUS_REPLACED = 'REPLACED';

    public const STATUS_CANCELLED = 'CANCELLED';

    protected $table = 'member_memberships';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

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

    protected $useTimestamps = true;

    protected $dateFormat = 'datetime';

    protected $createdField = 'created_at';

    protected $updatedField = 'updated_at';

    protected $skipValidation = true;

    /**
     * Resolve the currently usable paid membership.
     *
     * The timestamp check is deliberately part of runtime resolution.
     * Therefore access expires correctly even if the housekeeping cron has
     * not yet changed an expired ACTIVE row to EXPIRED.
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

        $record = $this
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
            ->first();

        return is_array($record)
            ? $record
            : null;
    }

    /**
     * Return membership history newest first.
     *
     * @return list<array<string, mixed>>
     */
    public function historyForUser(
        int $userId
    ): array {
        if ($userId <= 0) {
            return [];
        }

        $rows = $this
            ->where(
                'user_id',
                $userId
            )
            ->orderBy(
                'created_at',
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
}
