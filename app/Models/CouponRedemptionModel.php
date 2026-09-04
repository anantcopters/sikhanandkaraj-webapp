<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Model;

final class CouponRedemptionModel extends Model
{
    public const STATUS_COMPLETED =
    'COMPLETED';

    public const STATUS_VOIDED =
    'VOIDED';

    protected $table =
    'coupon_redemptions';

    protected $primaryKey =
    'id';

    protected $returnType =
    'array';

    protected $useTimestamps =
    true;

    protected $allowedFields = [
        'coupon_id',
        'user_id',
        'member_payment_id',
        'membership_plan_id',
        'coupon_code_snapshot',
        'discount_type_snapshot',
        'discount_value_snapshot',
        'plan_price_paise',
        'discount_amount_paise',
        'final_payable_paise',
        'status',
        'redeemed_by_admin_user_id',
        'redeemed_at',
        'voided_at',
        'voided_by_admin_user_id',
    ];

    public function __construct(
        ?ConnectionInterface $db = null
    ) {
        parent::__construct(
            $db
        );
    }

    public function completedCount(
        int $couponId
    ): int {
        return $this
            ->where(
                'coupon_id',
                $couponId
            )
            ->where(
                'status',
                self::STATUS_COMPLETED
            )
            ->countAllResults();
    }

    public function memberHasCompletedRedemption(
        int $couponId,
        int $userId
    ): bool {
        return $this
            ->where(
                'coupon_id',
                $couponId
            )
            ->where(
                'user_id',
                $userId
            )
            ->where(
                'status',
                self::STATUS_COMPLETED
            )
            ->countAllResults()
            > 0;
    }
}
