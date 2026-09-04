<?php

declare(strict_types=1);

namespace App\Services\Membership;

use App\Models\CouponAuditLogModel;
use App\Models\CouponModel;
use App\Models\CouponRedemptionModel;
use App\Models\MembershipPlanModel;
use CodeIgniter\Database\BaseConnection;
use App\Support\BooleanValue;
use DomainException;
use RuntimeException;

final class CouponService
{
    public function __construct(
        private readonly BaseConnection
        $database,

        private readonly CouponModel
        $couponModel,

        private readonly CouponRedemptionModel
        $redemptionModel,

        private readonly CouponAuditLogModel
        $auditModel,

        private readonly MembershipPlanModel
        $planModel
    ) {}

    /**
     * Validate a coupon without consuming it.
     *
     * @return array<string, mixed>
     */
    public function evaluate(
        int $userId,
        string $planCode,
        string $couponCode,
        ?\DateTimeImmutable $effectiveAt = null,
        bool $effectiveDateOnly = false
    ): array {
        if ($userId <= 0) {
            throw new DomainException(
                'A valid member is required.'
            );
        }

        $couponCode =
            mb_strtoupper(
                trim($couponCode)
            );

        if ($couponCode === '') {
            throw new DomainException(
                'Please enter a coupon code.'
            );
        }

        $coupon =
            $this->couponModel
            ->findByCode(
                $couponCode
            );

        if (!is_array($coupon)) {
            throw new DomainException(
                'Coupon does not exist.'
            );
        }

        return $this->evaluateCoupon(
            $coupon,
            $userId,
            $planCode,
            $effectiveAt,
            $effectiveDateOnly
        );
    }

    /**
     * Revalidate and lock the coupon during payment completion.
     *
     * The caller must already own the surrounding DB transaction.
     *
     * @return array<string, mixed>
     */
    public function evaluateForRedemption(
        int $couponId,
        int $userId,
        string $planCode,
        ?\DateTimeImmutable $effectiveAt = null,
        bool $effectiveDateOnly = false
    ): array {
        $coupon =
            $this->couponModel
            ->lockById(
                $couponId
            );

        if (!is_array($coupon)) {
            throw new DomainException(
                'Coupon does not exist.'
            );
        }

        return $this->evaluateCoupon(
            $coupon,
            $userId,
            $planCode,
            $effectiveAt,
            $effectiveDateOnly
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function evaluateCoupon(
        array $coupon,
        int $userId,
        string $planCode,
        ?\DateTimeImmutable $effectiveAt = null,
        bool $effectiveDateOnly = false
    ): array {
        if (
            !BooleanValue::fromDatabase(
                $coupon['is_active']
                    ?? false
            )
        ) {
            throw new DomainException(
                'Coupon is inactive.'
            );
        }

        $timezone =
            new \DateTimeZone(
                'Asia/Kolkata'
            );

        $effectiveAt =
            $effectiveAt !== null
            ? $effectiveAt->setTimezone(
                $timezone
            )
            : new \DateTimeImmutable(
                'now',
                $timezone
            );

        $startsAt =
            new \DateTimeImmutable(
                (string) $coupon['starts_at'],
                $timezone
            );

        $expiresAt =
            new \DateTimeImmutable(
                (string) $coupon['expires_at'],
                $timezone
            );

        /*
        * Offline payment captures only a calendar date.
        *
        * Therefore coupon validity for an offline payment is evaluated against
        * the whole business date rather than inventing an arbitrary payment time.
        *
        * Normal/current coupon evaluation continues to use exact timestamps.
        */
        if ($effectiveDateOnly) {
            $effectiveDate =
                $effectiveAt->format(
                    'Y-m-d'
                );

            $startDate =
                $startsAt->format(
                    'Y-m-d'
                );

            $expiryDate =
                $expiresAt->format(
                    'Y-m-d'
                );

            if ($effectiveDate < $startDate) {
                throw new DomainException(
                    'Coupon was not active on the selected payment date.'
                );
            }

            if ($effectiveDate > $expiryDate) {
                throw new DomainException(
                    'Coupon had expired on the selected payment date.'
                );
            }
        } else {
            if ($effectiveAt < $startsAt) {
                throw new DomainException(
                    'Coupon is not active yet.'
                );
            }

            if ($effectiveAt > $expiresAt) {
                throw new DomainException(
                    'Coupon has expired.'
                );
            }
        }

        $couponId =
            (int) $coupon['id'];

        $used =
            $this->redemptionModel
            ->completedCount(
                $couponId
            );

        $limit =
            (int) (
                $coupon['usage_limit']
                ?? 0
            );

        if (
            $limit <= 0
            || $used >= $limit
        ) {
            throw new DomainException(
                'Coupon usage limit has been reached.'
            );
        }

        if (
            $this->redemptionModel
            ->memberHasCompletedRedemption(
                $couponId,
                $userId
            )
        ) {
            throw new DomainException(
                'Member has already used this coupon.'
            );
        }

        $plan =
            $this->planModel
            ->findActiveByCode(
                $planCode
            );

        if (!is_array($plan)) {
            throw new DomainException(
                'The selected membership plan is not available.'
            );
        }

        $planId =
            (int) (
                $plan['id']
                ?? 0
            );

        $planEligible =
            $this->database
            ->table(
                'coupon_plans'
            )
            ->where(
                'coupon_id',
                $couponId
            )
            ->where(
                'membership_plan_id',
                $planId
            )
            ->countAllResults()
            > 0;

        if (!$planEligible) {
            throw new DomainException(
                'Coupon is not valid for the selected plan.'
            );
        }

        $member =
            $this->database
            ->table('users u')
            ->select(
                'u.id, u.gender, '
                    . 'bd.country_id, '
                    . 'bd.state_id, '
                    . 'bd.city_id'
            )
            ->join(
                'member_basic_details bd',
                'bd.user_id = u.id',
                'left'
            )
            ->where(
                'u.id',
                $userId
            )
            ->get()
            ->getRowArray();

        if (!is_array($member)) {
            throw new DomainException(
                'Member is not eligible for this coupon.'
            );
        }

        $eligibilityType =
            mb_strtoupper(
                trim(
                    (string) (
                        $coupon['eligibility_type']
                        ?? ''
                    )
                )
            );

        if (
            $eligibilityType
            === CouponModel::ELIGIBILITY_SELECTED
        ) {
            $selected =
                $this->database
                ->table(
                    'coupon_members'
                )
                ->where(
                    'coupon_id',
                    $couponId
                )
                ->where(
                    'user_id',
                    $userId
                )
                ->countAllResults()
                > 0;

            if (!$selected) {
                throw new DomainException(
                    'Member is not eligible for this coupon.'
                );
            }
        } elseif (
            $eligibilityType
            === CouponModel::ELIGIBILITY_GENDER
        ) {
            $memberGender =
                mb_strtoupper(
                    trim(
                        (string) (
                            $member['gender']
                            ?? ''
                        )
                    )
                );

            $eligibleGender =
                mb_strtoupper(
                    trim(
                        (string) (
                            $coupon['eligible_gender']
                            ?? ''
                        )
                    )
                );

            if (
                $eligibleGender === ''
                || $memberGender
                !== $eligibleGender
            ) {
                throw new DomainException(
                    'Member is not eligible for this coupon.'
                );
            }
        } elseif (
            $eligibilityType
            !== CouponModel::ELIGIBILITY_ALL
        ) {
            throw new DomainException(
                'Coupon member eligibility is invalid.'
            );
        }

        foreach (
            [
                'country_id',
                'state_id',
                'city_id',
            ]
            as $field
        ) {
            $requiredValue =
                (int) (
                    $coupon[$field]
                    ?? 0
                );

            if (
                $requiredValue > 0
                && (int) (
                    $member[$field]
                    ?? 0
                ) !== $requiredValue
            ) {
                throw new DomainException(
                    'Coupon is restricted to another location.'
                );
            }
        }

        $planPricePaise =
            max(
                0,
                (int) (
                    $plan['price_paise']
                    ?? 0
                )
            );

        $discountValue =
            (int) (
                $coupon['discount_value']
                ?? 0
            );

        $discountType =
            mb_strtoupper(
                trim(
                    (string) (
                        $coupon['discount_type']
                        ?? ''
                    )
                )
            );

        if (
            $discountType
            === CouponModel::DISCOUNT_PERCENTAGE
        ) {
            if (
                $discountValue <= 0
                || $discountValue > 100
            ) {
                throw new DomainException(
                    'Coupon discount is invalid.'
                );
            }

            $discountPaise =
                (int) round(
                    $planPricePaise
                        * ($discountValue / 100)
                );
        } elseif (
            $discountType
            === CouponModel::DISCOUNT_FLAT
        ) {
            /*
             * Flat coupon values are persisted in paise.
             */
            $discountPaise =
                $discountValue;
        } else {
            throw new DomainException(
                'Coupon discount is invalid.'
            );
        }

        if ($discountPaise <= 0) {
            throw new DomainException(
                'Coupon discount is invalid.'
            );
        }

        /*
        * A flat coupon may be configured against multiple plans.
        *
        * Its configured amount is bounded by the highest selected plan
        * during coupon administration. When applied to a cheaper plan,
        * the effective discount cannot exceed that plan's price.
        *
        * Percentage 100 therefore also resolves naturally to a zero-payable
        * membership.
        */
        $discountPaise =
            min(
                $discountPaise,
                $planPricePaise
            );

        return [
            'couponId' =>
            $couponId,

            'code' =>
            (string) $coupon['code'],

            'discountType' =>
            $discountType,

            'discountValue' =>
            $discountValue,

            'planId' =>
            $planId,

            'planPricePaise' =>
            $planPricePaise,

            'discountAmountPaise' =>
            $discountPaise,

            'finalPayablePaise' =>
            $planPricePaise
                - $discountPaise,
        ];
    }

    /**
     * Record the successful coupon redemption.
     *
     * IMPORTANT:
     *
     * This method is called from the successful-payment transaction.
     * The redemption row and its audit history therefore participate
     * in the same database transaction as payment completion and
     * membership activation.
     *
     * @param array<string, mixed> $evaluation
     */
    public function recordRedemption(
        array $evaluation,
        int $userId,
        int $paymentId,
        int $adminUserId
    ): int {
        $couponId =
            (int) (
                $evaluation['couponId']
                ?? 0
            );

        if (
            $couponId <= 0
            || $userId <= 0
            || $paymentId <= 0
            || $adminUserId <= 0
        ) {
            throw new RuntimeException(
                'Coupon redemption details are invalid.'
            );
        }

        $id =
            $this->redemptionModel
            ->insert(
                [
                    'coupon_id' =>
                    $couponId,

                    'user_id' =>
                    $userId,

                    'member_payment_id' =>
                    $paymentId,

                    'membership_plan_id' =>
                    (int) $evaluation['planId'],

                    'coupon_code_snapshot' =>
                    (string) $evaluation['code'],

                    'discount_type_snapshot' =>
                    (string) $evaluation['discountType'],

                    'discount_value_snapshot' =>
                    (int) $evaluation['discountValue'],

                    'plan_price_paise' =>
                    (int) $evaluation['planPricePaise'],

                    'discount_amount_paise' =>
                    (int) $evaluation['discountAmountPaise'],

                    'final_payable_paise' =>
                    (int) $evaluation['finalPayablePaise'],

                    'status' =>
                    CouponRedemptionModel
                    ::STATUS_COMPLETED,

                    'redeemed_by_admin_user_id' =>
                    $adminUserId,

                    'redeemed_at' =>
                    gmdate(
                        'Y-m-d H:i:s'
                    ),
                ],
                true
            );

        if (
            !is_numeric($id)
            || (int) $id <= 0
        ) {
            throw new RuntimeException(
                'Coupon redemption could not be recorded.'
            );
        }

        $redemptionId =
            (int) $id;

        /*
     * COUPON AUDIT:
     *
     * Redemption is a material coupon event and must appear in
     * coupon_audit_logs just like CREATED, UPDATED, ACTIVATED and
     * DEACTIVATED.
     *
     * Do not store member profile/contact data here. The canonical
     * user ID, payment ID and financial snapshot are sufficient for
     * tracing the redemption while avoiding unnecessary matrimonial PII.
     *
     * This insert intentionally happens after coupon_redemptions.
     * Because the caller already owns the successful-payment database
     * transaction, failure to write the audit record will cause the
     * surrounding transaction to fail rather than leaving an
     * unaudited successful redemption.
     */
        $auditId =
            $this->auditModel
            ->insert(
                [
                    'coupon_id' =>
                    $couponId,

                    'admin_user_id' =>
                    $adminUserId,

                    'action' =>
                    'REDEEMED',

                    'previous_values' =>
                    null,

                    'new_values' =>
                    json_encode(
                        [
                            'redemption_id' =>
                            $redemptionId,

                            'user_id' =>
                            $userId,

                            'member_payment_id' =>
                            $paymentId,

                            'membership_plan_id' =>
                            (int) $evaluation['planId'],

                            'coupon_code' =>
                            (string) $evaluation['code'],

                            'discount_type' =>
                            (string) $evaluation['discountType'],

                            'discount_value' =>
                            (int) $evaluation['discountValue'],

                            'plan_price_paise' =>
                            (int) $evaluation['planPricePaise'],

                            'discount_amount_paise' =>
                            (int) $evaluation['discountAmountPaise'],

                            'final_payable_paise' =>
                            (int) $evaluation['finalPayablePaise'],

                            'status' =>
                            CouponRedemptionModel
                            ::STATUS_COMPLETED,
                        ],
                        JSON_UNESCAPED_UNICODE
                            | JSON_UNESCAPED_SLASHES
                    ),

                    'created_at' =>
                    date(
                        'Y-m-d H:i:s'
                    ),
                ],
                true
            );

        if (
            !is_numeric($auditId)
            || (int) $auditId <= 0
        ) {
            throw new RuntimeException(
                'Coupon redemption audit history could not be recorded.'
            );
        }

        return $redemptionId;
    }
}
