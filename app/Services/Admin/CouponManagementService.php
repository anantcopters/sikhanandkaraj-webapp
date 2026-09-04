<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\CouponAuditLogModel;
use App\Models\CouponModel;
use App\Models\CouponRedemptionModel;
use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;

final class CouponManagementService
{
    public function __construct(
        private readonly BaseConnection
        $database,

        private readonly CouponModel
        $couponModel,

        private readonly CouponRedemptionModel
        $redemptionModel,

        private readonly CouponAuditLogModel
        $auditModel
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function coupons(): array
    {
        $rows =
            $this->couponModel
            ->orderBy(
                'id',
                'DESC'
            )
            ->findAll();

        foreach ($rows as &$coupon) {
            $coupon['used_count'] =
                $this->redemptionModel
                ->completedCount(
                    (int) $coupon['id']
                );

            $coupon['effective_status'] =
                $this->effectiveStatus(
                    $coupon
                );
        }

        unset($coupon);

        return $rows;
    }

    /**
     * @param array<string, mixed> $input
     */
    public function create(
        array $input,
        int $adminUserId
    ): int {
        $normalized =
            $this->normalizeAndValidate(
                $input
            );

        if (
            $this->couponModel
            ->findByCode(
                $normalized['code']
            ) !== null
        ) {
            throw new DomainException(
                'This coupon code already exists.'
            );
        }

        $this->database
            ->transBegin();

        try {
            $couponId =
                $this->couponModel
                ->insert(
                    [
                        ...$normalized['coupon'],

                        'created_by_admin_user_id' =>
                        $adminUserId,

                        'updated_by_admin_user_id' =>
                        $adminUserId,
                    ],
                    true
                );

            if (
                !is_numeric($couponId)
                || (int) $couponId <= 0
            ) {
                throw new RuntimeException(
                    'Coupon could not be created.'
                );
            }

            $couponId =
                (int) $couponId;

            $this->replacePlans(
                $couponId,
                $normalized['plan_ids']
            );

            $this->replaceMembers(
                $couponId,
                $normalized['eligibility_type'],
                $normalized['member_ids']
            );

            $this->audit(
                $couponId,
                $adminUserId,
                'CREATED',
                null,
                $normalized
            );

            if (
                $this->database
                ->transStatus()
                === false
            ) {
                throw new RuntimeException(
                    'Coupon creation transaction failed.'
                );
            }

            $this->database
                ->transCommit();

            return $couponId;
        } catch (\Throwable $exception) {
            $this->database
                ->transRollback();

            throw $exception;
        }
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    private function normalizeAndValidate(
        array $input
    ): array {
        $code =
            mb_strtoupper(
                trim(
                    (string) (
                        $input['code']
                        ?? ''
                    )
                )
            );

        $discountType =
            mb_strtoupper(
                trim(
                    (string) (
                        $input['discount_type']
                        ?? ''
                    )
                )
            );

        $discountInput =
            trim(
                (string) (
                    $input['discount_value']
                    ?? ''
                )
            );

        $eligibilityType =
            mb_strtoupper(
                trim(
                    (string) (
                        $input['eligibility_type']
                        ?? ''
                    )
                )
            );

        $eligibleGender =
            mb_strtoupper(
                trim(
                    (string) (
                        $input['eligible_gender']
                        ?? ''
                    )
                )
            );

        $usageLimit =
            (int) (
                $input['usage_limit']
                ?? 0
            );

        $expiryDate =
            trim(
                (string) (
                    $input['expiry_date']
                    ?? ''
                )
            );

        $planIds =
            array_values(
                array_unique(
                    array_filter(
                        array_map(
                            'intval',
                            (array) (
                                $input['plan_ids']
                                ?? []
                            )
                        ),
                        static fn(
                            int $id
                        ): bool => $id > 0
                    )
                )
            );

        $memberIds =
            array_values(
                array_unique(
                    array_filter(
                        array_map(
                            'intval',
                            (array) (
                                $input['member_ids']
                                ?? []
                            )
                        ),
                        static fn(
                            int $id
                        ): bool => $id > 0
                    )
                )
            );

        if ($planIds === []) {
            throw new DomainException(
                'Please select at least one membership plan.'
            );
        }

        if (
            $discountType
            === CouponModel::DISCOUNT_PERCENTAGE
        ) {
            $discountValue =
                (int) $discountInput;

            if (
                $discountValue <= 0
                || $discountValue > 90
            ) {
                throw new DomainException(
                    'Percentage discount must be between 1 and 90.'
                );
            }
        } elseif (
            $discountType
            === CouponModel::DISCOUNT_FLAT
        ) {
            $discountValue =
                (int) round(
                    ((float) $discountInput)
                        * 100
                );

            if ($discountValue <= 0) {
                throw new DomainException(
                    'Flat discount must be greater than zero.'
                );
            }
        } else {
            throw new DomainException(
                'Please select a valid discount type.'
            );
        }

        if (
            !in_array(
                $eligibilityType,
                [
                    CouponModel::ELIGIBILITY_ALL,
                    CouponModel::ELIGIBILITY_SELECTED,
                    CouponModel::ELIGIBILITY_GENDER,
                ],
                true
            )
        ) {
            throw new DomainException(
                'Please select member eligibility.'
            );
        }

        if (
            $eligibilityType
            === CouponModel::ELIGIBILITY_SELECTED
            && $memberIds === []
        ) {
            throw new DomainException(
                'Please select at least one member.'
            );
        }

        if (
            $eligibilityType
            === CouponModel::ELIGIBILITY_GENDER
            && !in_array(
                $eligibleGender,
                [
                    CouponModel::GENDER_MALE,
                    CouponModel::GENDER_FEMALE,
                ],
                true
            )
        ) {
            throw new DomainException(
                'Please select Male or Female.'
            );
        }

        if (
            $eligibilityType
            !== CouponModel::ELIGIBILITY_GENDER
        ) {
            $eligibleGender = '';
        }

        if ($usageLimit <= 0) {
            throw new DomainException(
                'Usage limit must be greater than zero.'
            );
        }

        $expiry =
            \DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $expiryDate,
                new \DateTimeZone(
                    'Asia/Kolkata'
                )
            );

        if (
            !$expiry
                instanceof \DateTimeImmutable
        ) {
            throw new DomainException(
                'Please select a valid expiry date.'
            );
        }

        $now =
            new \DateTimeImmutable(
                'now',
                new \DateTimeZone(
                    'Asia/Kolkata'
                )
            );

        $expiry =
            $expiry->setTime(
                23,
                59,
                59
            );

        if ($expiry < $now) {
            throw new DomainException(
                'Expiry date cannot be before the coupon start.'
            );
        }

        return [
            'code' =>
            $code,

            'coupon' => [
                'code' =>
                $code,

                'description' =>
                trim(
                    (string) (
                        $input['description']
                        ?? ''
                    )
                ) ?: null,

                'discount_type' =>
                $discountType,

                'discount_value' =>
                $discountValue,

                'eligibility_type' =>
                $eligibilityType,

                'eligible_gender' =>
                $eligibleGender !== ''
                    ? $eligibleGender
                    : null,

                'usage_limit' =>
                $usageLimit,

                'starts_at' =>
                $now->format(
                    'Y-m-d H:i:s'
                ),

                'expires_at' =>
                $expiry->format(
                    'Y-m-d H:i:s'
                ),

                'country_id' =>
                $this->nullableId(
                    $input['country_id']
                        ?? null
                ),

                'state_id' =>
                $this->nullableId(
                    $input['state_id']
                        ?? null
                ),

                'city_id' =>
                $this->nullableId(
                    $input['city_id']
                        ?? null
                ),

                'is_active' =>
                !empty($input['is_active'])
                    ? 1
                    : 0,
            ],

            'eligibility_type' =>
            $eligibilityType,

            'plan_ids' =>
            $planIds,

            'member_ids' =>
            $memberIds,
        ];
    }

    private function replacePlans(
        int $couponId,
        array $planIds
    ): void {
        $builder =
            $this->database
            ->table(
                'coupon_plans'
            );

        $builder
            ->where(
                'coupon_id',
                $couponId
            )
            ->delete();

        foreach ($planIds as $planId) {
            $builder->insert(
                [
                    'coupon_id' =>
                    $couponId,

                    'membership_plan_id' =>
                    $planId,
                ]
            );
        }
    }

    private function replaceMembers(
        int $couponId,
        string $eligibilityType,
        array $memberIds
    ): void {
        $builder =
            $this->database
            ->table(
                'coupon_members'
            );

        $builder
            ->where(
                'coupon_id',
                $couponId
            )
            ->delete();

        if (
            $eligibilityType
            !== CouponModel::ELIGIBILITY_SELECTED
        ) {
            return;
        }

        foreach ($memberIds as $memberId) {
            $builder->insert(
                [
                    'coupon_id' =>
                    $couponId,

                    'user_id' =>
                    $memberId,
                ]
            );
        }
    }

    private function effectiveStatus(
        array $coupon
    ): string {
        if (
            (int) (
                $coupon['is_active']
                ?? 0
            ) !== 1
        ) {
            return 'INACTIVE';
        }

        $now =
            new \DateTimeImmutable(
                'now',
                new \DateTimeZone(
                    'Asia/Kolkata'
                )
            );

        $startsAt =
            new \DateTimeImmutable(
                (string) $coupon['starts_at'],
                new \DateTimeZone(
                    'Asia/Kolkata'
                )
            );

        $expiresAt =
            new \DateTimeImmutable(
                (string) $coupon['expires_at'],
                new \DateTimeZone(
                    'Asia/Kolkata'
                )
            );

        if ($now < $startsAt) {
            return 'SCHEDULED';
        }

        if ($now > $expiresAt) {
            return 'EXPIRED';
        }

        if (
            (int) (
                $coupon['used_count']
                ?? 0
            )
            >=
            (int) (
                $coupon['usage_limit']
                ?? 0
            )
        ) {
            return 'EXHAUSTED';
        }

        return 'ACTIVE';
    }

    private function nullableId(
        mixed $value
    ): ?int {
        $id =
            (int) $value;

        return $id > 0
            ? $id
            : null;
    }

    private function audit(
        int $couponId,
        int $adminUserId,
        string $action,
        ?array $previous,
        ?array $new
    ): void {
        $this->auditModel
            ->insert(
                [
                    'coupon_id' =>
                    $couponId,

                    'admin_user_id' =>
                    $adminUserId,

                    'action' =>
                    $action,

                    'previous_values' =>
                    $previous !== null
                        ? json_encode(
                            $previous,
                            JSON_UNESCAPED_UNICODE
                                | JSON_UNESCAPED_SLASHES
                        )
                        : null,

                    'new_values' =>
                    $new !== null
                        ? json_encode(
                            $new,
                            JSON_UNESCAPED_UNICODE
                                | JSON_UNESCAPED_SLASHES
                        )
                        : null,

                    'created_at' =>
                    gmdate(
                        'Y-m-d H:i:s'
                    ),
                ]
            );
    }
}
