<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Provides persistence access to the authoritative membership plan master.
 *
 * Commercial plan definitions belong in membership_plans. Views and
 * controllers must not maintain independent copies of plan limits/prices.
 */
final class MembershipPlanModel extends Model
{
    public const CODE_GO = 'GO';

    public const CODE_PLUS = 'PLUS';

    public const CODE_PRO = 'PRO';

    protected $table = 'membership_plans';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'code',
        'name',
        'positioning',
        'price_paise',
        'duration_months',
        'profile_view_limit',
        'daily_profile_view_limit',
        'live_introduction_view_limit',
        'has_match_manager',
        'commercial_priority',
        'display_order',
        'is_active',
    ];

    protected $useTimestamps = true;

    protected $dateFormat = 'datetime';

    protected $createdField = 'created_at';

    protected $updatedField = 'updated_at';

    protected $skipValidation = true;

    /**
     * Return all currently purchasable plans in pricing-page order.
     *
     * @return list<array<string, mixed>>
     */
    public function activePlans(): array
    {
        $rows = $this
            ->where(
                'is_active',
                true
            )
            ->orderBy(
                'display_order',
                'ASC'
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
     * Find an active plan by its stable commercial code.
     *
     * Plan names are presentation text and may change. Business logic must
     * therefore identify plans by GO / PLUS / PRO rather than by display name.
     *
     * @return array<string, mixed>|null
     */
    public function findActiveByCode(
        string $code
    ): ?array {
        $normalizedCode = mb_strtoupper(
            trim($code)
        );

        if (
            !in_array(
                $normalizedCode,
                [
                    self::CODE_GO,
                    self::CODE_PLUS,
                    self::CODE_PRO,
                ],
                true
            )
        ) {
            return null;
        }

        $record = $this
            ->where(
                'code',
                $normalizedCode
            )
            ->where(
                'is_active',
                true
            )
            ->first();

        return is_array($record)
            ? $record
            : null;
    }
}
