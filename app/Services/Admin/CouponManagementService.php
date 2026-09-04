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
        private readonly BaseConnection $database,
        private readonly CouponModel $couponModel,
        private readonly CouponRedemptionModel $redemptionModel,
        private readonly CouponAuditLogModel $auditModel
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function coupons(): array
    {
        $rows = $this->couponModel
            ->orderBy('id', 'DESC')
            ->findAll();

        foreach ($rows as &$coupon) {
            $usedCount = $this->redemptionModel
                ->completedCount(
                    (int) ($coupon['id'] ?? 0)
                );

            $coupon['used_count'] = $usedCount;
            $coupon['effective_status'] =
                $this->effectiveStatus(
                    $coupon,
                    $usedCount
                );
        }

        unset($coupon);

        return $rows;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $couponId): ?array
    {
        if ($couponId <= 0) {
            return null;
        }

        $coupon = $this->couponModel
            ->find($couponId);

        if (!is_array($coupon)) {
            return null;
        }

        $coupon['plan_ids'] = array_map(
            static fn(array $row): int =>
            (int) $row['membership_plan_id'],
            $this->database
                ->table('coupon_plans')
                ->select('membership_plan_id')
                ->where('coupon_id', $couponId)
                ->get()
                ->getResultArray()
        );

        $coupon['member_ids'] = array_map(
            static fn(array $row): int =>
            (int) $row['user_id'],
            $this->database
                ->table('coupon_members')
                ->select('user_id')
                ->where('coupon_id', $couponId)
                ->get()
                ->getResultArray()
        );

        $coupon['used_count'] =
            $this->redemptionModel
            ->completedCount($couponId);

        $coupon['effective_status'] =
            $this->effectiveStatus(
                $coupon,
                (int) $coupon['used_count']
            );

        return $coupon;
    }

    /**
     * @param array<string, mixed> $input
     */
    public function create(
        array $input,
        int $adminUserId
    ): int {
        if ($adminUserId <= 0) {
            throw new DomainException(
                'A valid administrator is required.'
            );
        }

        $normalized =
            $this->normalizeAndValidate($input);

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

        $this->database->transBegin();

        try {
            $couponData =
                $normalized['coupon'];

            $couponData['created_by_admin_user_id'] = $adminUserId;

            $couponData['updated_by_admin_user_id'] = $adminUserId;

            $couponId =
                $this->couponModel
                ->insert(
                    $couponData,
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

            $couponId = (int) $couponId;

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
                $this->database->transStatus()
                === false
            ) {
                throw new RuntimeException(
                    'Coupon creation transaction failed.'
                );
            }

            $this->database->transCommit();

            return $couponId;
        } catch (\Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }
    }

    /**
     * @param array<string, mixed> $input
     */
    public function update(
        int $couponId,
        array $input,
        int $adminUserId
    ): void {
        if (
            $couponId <= 0
            || $adminUserId <= 0
        ) {
            throw new DomainException(
                'Invalid coupon update request.'
            );
        }

        $existing =
            $this->find($couponId);

        if ($existing === null) {
            throw new DomainException(
                'Coupon was not found.'
            );
        }

        $normalized =
            $this->normalizeAndValidate(
                $input,
                false
            );

        $usedCount =
            (int) (
                $existing['used_count']
                ?? 0
            );

        /*
         * Once a coupon has been redeemed, commercial
         * and eligibility rules become historical facts.
         *
         * Only expiry extension, usage-limit increase and
         * active/inactive state are editable.
         */
        if ($usedCount > 0) {
            $this->validatePostRedemptionUpdate(
                $existing,
                $normalized
            );

            $updateData = [
                'usage_limit' =>
                $normalized['coupon']['usage_limit'],

                'expires_at' =>
                $normalized['coupon']['expires_at'],

                'is_active' =>
                $normalized['coupon']['is_active'],

                'updated_by_admin_user_id' =>
                $adminUserId,
            ];
        } else {
            $duplicate =
                $this->couponModel
                ->findByCode(
                    $normalized['code']
                );

            if (
                is_array($duplicate)
                && (int) $duplicate['id']
                !== $couponId
            ) {
                throw new DomainException(
                    'This coupon code already exists.'
                );
            }

            $updateData =
                $normalized['coupon'];

            /*
             * Start time is historical even before
             * redemption. Editing a coupon must not
             * silently restart its validity.
             */
            unset(
                $updateData['starts_at']
            );

            $updateData['updated_by_admin_user_id'] = $adminUserId;
        }

        $this->database->transBegin();

        try {
            if (
                !$this->couponModel->update(
                    $couponId,
                    $updateData
                )
            ) {
                throw new RuntimeException(
                    'Coupon could not be updated.'
                );
            }

            if ($usedCount === 0) {
                $this->replacePlans(
                    $couponId,
                    $normalized['plan_ids']
                );

                $this->replaceMembers(
                    $couponId,
                    $normalized['eligibility_type'],
                    $normalized['member_ids']
                );
            }

            $this->audit(
                $couponId,
                $adminUserId,
                'UPDATED',
                $existing,
                $normalized
            );

            if (
                $this->database->transStatus()
                === false
            ) {
                throw new RuntimeException(
                    'Coupon update transaction failed.'
                );
            }

            $this->database->transCommit();
        } catch (\Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }
    }

    public function setStatus(
        int $couponId,
        bool $isActive,
        int $adminUserId
    ): void {
        $existing =
            $this->find($couponId);

        if ($existing === null) {
            throw new DomainException(
                'Coupon was not found.'
            );
        }

        $previousStatus =
            (int) (
                $existing['is_active']
                ?? 0
            );

        $newStatus =
            $isActive ? 1 : 0;

        if ($previousStatus === $newStatus) {
            return;
        }

        if (
            !$this->couponModel->update(
                $couponId,
                [
                    'is_active' =>
                    $newStatus,

                    'updated_by_admin_user_id' =>
                    $adminUserId,
                ]
            )
        ) {
            throw new RuntimeException(
                'Coupon status could not be updated.'
            );
        }

        $this->audit(
            $couponId,
            $adminUserId,
            $isActive
                ? 'ACTIVATED'
                : 'DEACTIVATED',
            [
                'is_active' =>
                $previousStatus,
            ],
            [
                'is_active' =>
                $newStatus,
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function report(
        int $couponId
    ): array {
        $coupon =
            $this->find($couponId);

        if ($coupon === null) {
            throw new DomainException(
                'Coupon was not found.'
            );
        }

        $redemptions =
            $this->database
            ->table('coupon_redemptions cr')
            ->select(
                'cr.*, '
                    . 'u.profile_id, '
                    . 'u.first_name, '
                    . 'u.last_name, '
                    . 'mp.name AS plan_name, '
                    . 'mp.code AS plan_code'
            )
            ->join(
                'users u',
                'u.id = cr.user_id',
                'left'
            )
            ->join(
                'membership_plans mp',
                'mp.id = cr.membership_plan_id',
                'left'
            )
            ->where(
                'cr.coupon_id',
                $couponId
            )
            ->orderBy(
                'cr.redeemed_at',
                'DESC'
            )
            ->get()
            ->getResultArray();

        $completedCount = 0;
        $totalDiscountPaise = 0;
        $totalFinalPayablePaise = 0;

        foreach ($redemptions as $row) {
            if (
                ($row['status'] ?? '')
                !== CouponRedemptionModel
                ::STATUS_COMPLETED
            ) {
                continue;
            }

            $completedCount++;

            $totalDiscountPaise +=
                (int) (
                    $row['discount_amount_paise']
                    ?? 0
                );

            $totalFinalPayablePaise +=
                (int) (
                    $row['final_payable_paise']
                    ?? 0
                );
        }

        return [
            'coupon' =>
            $coupon,

            'redemptions' =>
            $redemptions,

            'summary' => [
                'completed_count' =>
                $completedCount,

                'usage_limit' =>
                (int) (
                    $coupon['usage_limit']
                    ?? 0
                ),

                'total_discount_paise' =>
                $totalDiscountPaise,

                'total_final_payable_paise' =>
                $totalFinalPayablePaise,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    private function normalizeAndValidate(
        array $input,
        bool $isCreate = true
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

        if (
            $code === ''
            || !preg_match(
                '/^[A-Z0-9_-]+$/',
                $code
            )
        ) {
            throw new DomainException(
                'Please enter a valid coupon code.'
            );
        }

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
            $this->positiveIds(
                (array) (
                    $input['plan_ids']
                    ?? []
                )
            );

        $memberIds =
            $this->positiveIds(
                (array) (
                    $input['member_ids']
                    ?? []
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
            if (
                !ctype_digit($discountInput)
            ) {
                throw new DomainException(
                    'Percentage discount must be a whole number.'
                );
            }

            $discountValue =
                (int) $discountInput;

            if (
                $discountValue < 1
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
            if (
                !is_numeric($discountInput)
                || (float) $discountInput <= 0
            ) {
                throw new DomainException(
                    'Flat discount must be greater than zero.'
                );
            }

            $discountValue =
                (int) round(
                    ((float) $discountInput)
                        * 100
                );
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
        ) {
            if (
                !in_array(
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
        } else {
            $eligibleGender = '';
        }

        if ($usageLimit <= 0) {
            throw new DomainException(
                'Usage limit must be greater than zero.'
            );
        }

        $timezone =
            new \DateTimeZone(
                'Asia/Kolkata'
            );

        $expiry =
            \DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $expiryDate,
                $timezone
            );

        if (
            !$expiry
                instanceof \DateTimeImmutable
        ) {
            throw new DomainException(
                'Please select a valid expiry date.'
            );
        }

        $expiry =
            $expiry->setTime(
                23,
                59,
                59
            );

        $now =
            new \DateTimeImmutable(
                'now',
                $timezone
            );

        if (
            $isCreate
            && $expiry < $now
        ) {
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

                /*
                 * Percentage:
                 *     integer percentage.
                 *
                 * Flat:
                 *     paise.
                 */
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

    /**
     * @param array<string, mixed> $existing
     * @param array<string, mixed> $normalized
     */
    private function validatePostRedemptionUpdate(
        array $existing,
        array $normalized
    ): void {
        $newCoupon =
            $normalized['coupon'];

        $immutableFields = [
            'code',
            'discount_type',
            'discount_value',
            'eligibility_type',
            'eligible_gender',
            'country_id',
            'state_id',
            'city_id',
        ];

        foreach (
            $immutableFields
            as $field
        ) {
            if (
                (string) (
                    $existing[$field]
                    ?? ''
                )
                !==
                (string) (
                    $newCoupon[$field]
                    ?? ''
                )
            ) {
                throw new DomainException(
                    'Coupon commercial and eligibility rules cannot be changed after the first redemption.'
                );
            }
        }

        $existingPlans =
            array_map(
                'intval',
                (array) (
                    $existing['plan_ids']
                    ?? []
                )
            );

        $newPlans =
            array_map(
                'intval',
                (array) (
                    $normalized['plan_ids']
                    ?? []
                )
            );

        sort($existingPlans);
        sort($newPlans);

        if ($existingPlans !== $newPlans) {
            throw new DomainException(
                'Applicable plans cannot be changed after the first redemption.'
            );
        }

        $existingMembers =
            array_map(
                'intval',
                (array) (
                    $existing['member_ids']
                    ?? []
                )
            );

        $newMembers =
            array_map(
                'intval',
                (array) (
                    $normalized['member_ids']
                    ?? []
                )
            );

        sort($existingMembers);
        sort($newMembers);

        if ($existingMembers !== $newMembers) {
            throw new DomainException(
                'Selected members cannot be changed after the first redemption.'
            );
        }

        $oldLimit =
            (int) (
                $existing['usage_limit']
                ?? 0
            );

        $newLimit =
            (int) (
                $newCoupon['usage_limit']
                ?? 0
            );

        if ($newLimit < $oldLimit) {
            throw new DomainException(
                'Usage limit can only be increased after the first redemption.'
            );
        }

        $oldExpiry =
            strtotime(
                (string) (
                    $existing['expires_at']
                    ?? ''
                )
            );

        $newExpiry =
            strtotime(
                (string) (
                    $newCoupon['expires_at']
                    ?? ''
                )
            );

        if (
            $oldExpiry !== false
            && $newExpiry !== false
            && $newExpiry < $oldExpiry
        ) {
            throw new DomainException(
                'Expiry date can only be extended after the first redemption.'
            );
        }
    }

    /**
     * @param list<int> $planIds
     */
    private function replacePlans(
        int $couponId,
        array $planIds
    ): void {
        $builder =
            $this->database
            ->table('coupon_plans');

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

    /**
     * @param list<int> $memberIds
     */
    private function replaceMembers(
        int $couponId,
        string $eligibilityType,
        array $memberIds
    ): void {
        $builder =
            $this->database
            ->table('coupon_members');

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
        array $coupon,
        int $usedCount
    ): string {
        if (
            (int) (
                $coupon['is_active']
                ?? 0
            ) !== 1
        ) {
            return 'INACTIVE';
        }

        $timezone =
            new \DateTimeZone(
                'Asia/Kolkata'
            );

        $now =
            new \DateTimeImmutable(
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

        if ($now < $startsAt) {
            return 'SCHEDULED';
        }

        if ($now > $expiresAt) {
            return 'EXPIRED';
        }

        if (
            $usedCount >=
            (int) (
                $coupon['usage_limit']
                ?? 0
            )
        ) {
            return 'EXHAUSTED';
        }

        return 'ACTIVE';
    }

    /**
     * @return list<int>
     */
    private function positiveIds(
        array $values
    ): array {
        return array_values(
            array_unique(
                array_filter(
                    array_map(
                        'intval',
                        $values
                    ),
                    static fn(int $id): bool =>
                    $id > 0
                )
            )
        );
    }

    private function nullableId(
        mixed $value
    ): ?int {
        $id = (int) $value;

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
        $result =
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
                    date(
                        'Y-m-d H:i:s'
                    ),
                ]
            );

        if ($result === false) {
            throw new RuntimeException(
                'Coupon audit history could not be recorded.'
            );
        }
    }
}
