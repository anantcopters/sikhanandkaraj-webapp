<?php

declare(strict_types=1);

/**
 * @var string                         $pageTitle
 * @var array<string, mixed>|null      $coupon
 * @var list<array<string, mixed>>     $membershipPlans
 * @var list<array<string, mixed>>     $members
 * @var array<string, string>          $validationErrors
 * @var array<string, string>|null     $formAlert
 * @var list<string>                   $pageScripts
 */

$resolvedCoupon =
    isset($coupon)
    && is_array($coupon)
    ? $coupon
    : [];

$plans =
    isset($membershipPlans)
    && is_array($membershipPlans)
    ? $membershipPlans
    : [];

$resolvedMembers =
    isset($members)
    && is_array($members)
    ? $members
    : [];

$errors =
    isset($validationErrors)
    && is_array($validationErrors)
    ? $validationErrors
    : [];

$resolvedFormAlert =
    isset($formAlert)
    && is_array($formAlert)
    ? $formAlert
    : null;

$isEdit =
    (int) (
        $resolvedCoupon['id']
        ?? 0
    ) > 0;

$couponId =
    (int) (
        $resolvedCoupon['id']
        ?? 0
    );

$usedCount =
    max(
        0,
        (int) (
            $resolvedCoupon['used_count']
            ?? 0
        )
    );

$hasRedemptions =
    $usedCount > 0;

$selectedPlanIds =
    array_map(
        'intval',
        (array) (
            $resolvedCoupon['plan_ids']
            ?? []
        )
    );

$selectedMemberIds =
    array_map(
        'intval',
        (array) (
            $resolvedCoupon['member_ids']
            ?? []
        )
    );

$selectedDiscountType =
    mb_strtoupper(
        trim(
            (string) old(
                'discount_type',
                $resolvedCoupon['discount_type']
                    ?? ''
            )
        )
    );

$selectedEligibilityType =
    mb_strtoupper(
        trim(
            (string) old(
                'eligibility_type',
                $resolvedCoupon['eligibility_type']
                    ?? ''
            )
        )
    );

$selectedGender =
    mb_strtoupper(
        trim(
            (string) old(
                'eligible_gender',
                $resolvedCoupon['eligible_gender']
                    ?? ''
            )
        )
    );

$expiryDate = '';

if (
    !empty($resolvedCoupon['expires_at'])
) {
    $expiryTimestamp =
        strtotime(
            (string) $resolvedCoupon['expires_at']
        );

    if ($expiryTimestamp !== false) {
        $expiryDate =
            date(
                'Y-m-d',
                $expiryTimestamp
            );
    }
}

$expiryDate =
    (string) old(
        'expiry_date',
        $expiryDate
    );

$discountValue = '';

if (
    isset(
        $resolvedCoupon['discount_value']
    )
) {
    if (
        ($resolvedCoupon['discount_type'] ?? '')
        === 'FLAT'
    ) {
        $discountValue =
            number_format(
                ((int) $resolvedCoupon['discount_value']) / 100,
                2,
                '.',
                ''
            );
    } else {
        $discountValue =
            (string) $resolvedCoupon['discount_value'];
    }
}

$discountValue =
    (string) old(
        'discount_value',
        $discountValue
    );

$isActive =
    old(
        'is_active',
        $isEdit
            ? (string) (
                $resolvedCoupon['is_active']
                ?? '0'
            )
            : '1'
    ) === '1';

$this->extend(
    'Admin/Layouts/Main'
);

$this->section(
    'content'
);
?>

<div class="container-fluid">

    <div class="row">
        <div class="col-12">

            <div
                class="
                    page-title-box
                    d-sm-flex
                    align-items-sm-center
                    justify-content-between
                ">

                <div>
                    <h4 class="mb-sm-0">
                        <?= $isEdit
                            ? 'Edit Coupon'
                            : 'Create Coupon' ?>
                    </h4>

                    <p class="text-muted mb-0">
                        Configure membership-plan pricing
                        and eligibility.
                    </p>
                </div>

                <a
                    href="<?= route_to(
                                'admin.coupons.index'
                            ) ?>"
                    class="
                        btn
                        btn-light
                        d-inline-flex
                        align-items-center
                        gap-1
                    ">

                    <i
                        class="ri-arrow-left-line"
                        aria-hidden="true">
                    </i>

                    Back to Coupons

                </a>

            </div>

        </div>
    </div>

    <?= view(
        'Components/Alerts/FormAlert',
        [
            'alert' =>
            $formAlert ?? null,
        ]
    ) ?>

    <form
        method="post"
        action="<?= $isEdit
                    ? route_to(
                        'admin.coupons.update',
                        (int) $resolvedCoupon['id']
                    )
                    : route_to(
                        'admin.coupons.store'
                    ) ?>"
        data-validate
        data-submit-loader
        novalidate>

        <?= csrf_field() ?>

        <div
            class="
                card
                border
                border-danger
                border-opacity-25
            ">

            <div class="card-body">
                <?php if ($hasRedemptions): ?>

                    <div
                        class="
            alert
            alert-info
            fs-13
        ">

                        <i
                            class="ri-information-line me-1"
                            aria-hidden="true">
                        </i>

                        This coupon has already been used
                        <?= $usedCount ?> time<?= $usedCount === 1
                                                    ? ''
                                                    : 's' ?>.

                        Commercial rules, applicable plans,
                        member eligibility and location eligibility
                        can no longer be changed.

                        You may increase the usage limit,
                        extend the expiry date or deactivate
                        the coupon.

                    </div>

                <?php endif; ?>
                <div class="row g-3">

                    <div class="col-12 col-md-6">

                        <label
                            for="couponCode"
                            class="form-label">
                            Coupon Code
                        </label>

                        <input
                            type="text"
                            id="couponCode"
                            name="code"
                            class="form-control"
                            maxlength="40"
                            value="<?= esc(
                                        old(
                                            'code',
                                            $resolvedCoupon['code']
                                                ?? ''
                                        ),
                                        'attr'
                                    ) ?>"
                            data-error-required="Please enter the coupon code."
                            required <?= $hasRedemptions
                                            ? 'readonly'
                                            : '' ?>>

                    </div>

                    <div class="col-12 col-md-6">

                        <label
                            for="couponUsageLimit"
                            class="form-label">
                            Usage Limit
                        </label>

                        <input
                            type="number"
                            id="couponUsageLimit"
                            name="usage_limit"
                            class="form-control"
                            min="1"
                            step="1"
                            value="<?= esc(
                                        old(
                                            'usage_limit',
                                            $resolvedCoupon['usage_limit']
                                                ?? ''
                                        ),
                                        'attr'
                                    ) ?>"
                            data-error-required="Please enter the usage limit."
                            required>

                    </div>

                    <div class="col-12">

                        <label
                            for="couponDescription"
                            class="form-label">

                            Internal Description

                            <span
                                class="
                                    text-muted
                                    fw-normal
                                ">
                                (Optional)
                            </span>

                        </label>

                        <input
                            type="text"
                            id="couponDescription"
                            name="description"
                            class="form-control"
                            maxlength="255"
                            value="<?= esc(
                                        old(
                                            'description',
                                            $resolvedCoupon['description']
                                                ?? ''
                                        ),
                                        'attr'
                                    ) ?>">

                    </div>

                    <div class="col-12">

                        <label class="form-label">
                            Applicable Plans
                        </label>
                        <?php if ($hasRedemptions): ?>

                            <?php foreach (
                                $selectedPlanIds
                                as $selectedPlanId
                            ): ?>

                                <input
                                    type="hidden"
                                    name="plan_ids[]"
                                    value="<?= (int) $selectedPlanId ?>">

                            <?php endforeach; ?>

                        <?php endif; ?>
                        <select
                            name="plan_ids[]"
                            class="form-select"
                            multiple
                            data-choice
                            data-choice-search="false"
                            required <?= $hasRedemptions
                                            ? 'disabled'
                                            : '' ?>>

                            <?php foreach (
                                $plans as $plan
                            ): ?>

                                <option
                                    value="<?= (int) (
                                                $plan['id']
                                                ?? 0
                                            ) ?>"
                                    <?= in_array(
                                        (int) (
                                            $plan['id']
                                            ?? 0
                                        ),
                                        array_map(
                                            'intval',
                                            (array) old(
                                                'plan_ids',
                                                $selectedPlanIds
                                            )
                                        ),
                                        true
                                    )
                                        ? 'selected'
                                        : '' ?>>

                                    <?= esc(
                                        $plan['name']
                                            ?? $plan['code']
                                            ?? ''
                                    ) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="col-12 col-md-6">

                        <label
                            for="couponDiscountType"
                            class="form-label">
                            Discount Type
                        </label>
                        <?php if ($hasRedemptions): ?>

                            <input
                                type="hidden"
                                name="discount_type"
                                value="<?= esc(
                                            $selectedDiscountType,
                                            'attr'
                                        ) ?>">

                        <?php endif; ?>
                        <select
                            id="couponDiscountType"
                            name="discount_type"
                            class="form-select"
                            data-coupon-discount-type
                            required <?= $hasRedemptions
                                            ? 'disabled'
                                            : '' ?>>

                            <option
                                value="PERCENTAGE"
                                <?= $selectedDiscountType
                                    === 'PERCENTAGE'
                                    ? 'selected'
                                    : '' ?>>
                                Percentage
                            </option>

                            <option
                                value="FLAT"
                                <?= $selectedDiscountType
                                    === 'FLAT'
                                    ? 'selected'
                                    : '' ?>>
                                Flat Amount
                            </option>

                        </select>

                    </div>

                    <div class="col-12 col-md-6">

                        <label
                            for="couponDiscountValue"
                            class="form-label">
                            Discount Value
                        </label>

                        <input
                            type="number"
                            id="couponDiscountValue"
                            name="discount_value"
                            class="form-control"
                            min="0.01"
                            step="0.01"
                            required value="<?= esc(
                                                $discountValue,
                                                'attr'
                                            ) ?>" <?= $hasRedemptions
                                                        ? 'readonly'
                                                        : '' ?>>

                    </div>

                    <div class="col-12 col-md-6">

                        <label
                            for="couponExpiryDate"
                            class="form-label">
                            Expiry Date
                        </label>

                        <input
                            type="date"
                            id="couponExpiryDate"
                            name="expiry_date"
                            class="form-control"
                            min="<?= date(
                                        'Y-m-d'
                                    ) ?>"
                            required value="<?= esc(
                                                $expiryDate,
                                                'attr'
                                            ) ?>">

                        <div class="form-text">
                            Coupon remains valid through
                            11:59:59 PM on this date.
                        </div>

                    </div>

                    <div class="col-12 col-md-6">

                        <label
                            for="couponEligibility"
                            class="form-label">
                            Member Eligibility
                        </label>
                        <?php if ($hasRedemptions): ?>

                            <input
                                type="hidden"
                                name="eligibility_type"
                                value="<?= esc(
                                            $selectedEligibilityType,
                                            'attr'
                                        ) ?>">

                        <?php endif; ?>
                        <select
                            id="couponEligibility"
                            name="eligibility_type"
                            class="form-select"
                            data-coupon-eligibility
                            required <?= $hasRedemptions
                                            ? 'disabled'
                                            : '' ?>>

                            <option
                                value="ALL"
                                <?= $selectedEligibilityType
                                    === 'ALL'
                                    ? 'selected'
                                    : '' ?>>
                                All Members
                            </option>

                            <option
                                value="SELECTED"
                                <?= $selectedEligibilityType
                                    === 'SELECTED'
                                    ? 'selected'
                                    : '' ?>>
                                Selected Members
                            </option>

                            <option
                                value="GENDER"
                                <?= $selectedEligibilityType
                                    === 'GENDER'
                                    ? 'selected'
                                    : '' ?>>
                                Male / Female Members
                            </option>

                        </select>

                    </div>

                    <div
                        class="
        col-12
        col-md-6
        <?= $selectedEligibilityType
            === 'GENDER'
            ? ''
            : 'd-none' ?>
    "
                        data-coupon-gender-container>

                        <label
                            for="couponGender"
                            class="form-label">
                            Gender
                        </label>
                        <?php if ($hasRedemptions): ?>

                            <input
                                type="hidden"
                                name="eligible_gender"
                                value="<?= esc(
                                            $selectedGender,
                                            'attr'
                                        ) ?>">

                        <?php endif; ?>
                        <select
                            id="couponGender"
                            name="eligible_gender"
                            class="form-select" <?= $hasRedemptions
                                                    ? 'disabled'
                                                    : '' ?>>

                            <option value="">
                                Select Gender
                            </option>

                            <option
                                value="MALE"
                                <?= $selectedGender
                                    === 'MALE'
                                    ? 'selected'
                                    : '' ?>>
                                Male
                            </option>

                            <option
                                value="FEMALE"
                                <?= $selectedGender
                                    === 'FEMALE'
                                    ? 'selected'
                                    : '' ?>>
                                Female
                            </option>

                        </select>

                    </div>

                    <div
                        class="
                            col-12
                            d-none
                        "
                        data-coupon-members-container>

                        <label
                            for="couponMembers"
                            class="form-label">
                            Selected Members
                        </label>
                        <?php if ($hasRedemptions): ?>

                            <?php foreach (
                                $resolvedMembers
                                as $member
                            ): ?>

                                <input
                                    type="hidden"
                                    name="member_ids[]"
                                    value="<?= (int) $memberId ?>">

                            <?php endforeach; ?>

                        <?php endif; ?>
                        <select
                            id="couponMembers"
                            name="member_ids[]"
                            class="form-select"
                            multiple
                            data-choice
                            data-choice-search="true" <?= $hasRedemptions
                                                            ? 'disabled'
                                                            : '' ?>>

                            <?php foreach (
                                $resolvedMembers
                                as $member
                            ): ?>

                                <?php

                                $memberId =
                                    (int) (
                                        $member['id']
                                        ?? 0
                                    );

                                if ($memberId <= 0) {
                                    continue;
                                }

                                $memberName =
                                    trim(
                                        (string) (
                                            $member['first_name']
                                            ?? ''
                                        )
                                            . ' '
                                            . (string) (
                                                $member['last_name']
                                                ?? ''
                                            )
                                    );

                                $profileId =
                                    trim(
                                        (string) (
                                            $member['profile_id']
                                            ?? ''
                                        )
                                    );

                                $selectedMembers =
                                    array_map(
                                        'intval',
                                        (array) old(
                                            'member_ids',
                                            $selectedMemberIds
                                        )
                                    );

                                ?>

                                <option
                                    value="<?= $memberId ?>"
                                    <?= in_array(
                                        $memberId,
                                        $selectedMembers,
                                        true
                                    )
                                        ? 'selected'
                                        : '' ?>>

                                    <?= esc(
                                        $memberName
                                            . (
                                                $profileId !== ''
                                                ? ' · ' . $profileId
                                                : ''
                                            )
                                    ) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                        <div class="form-text">
                            Search and select one or more
                            registered members.
                        </div>

                    </div>

                    <div class="col-12">

                        <div class="form-check form-switch">

                            <input
                                type="checkbox"
                                id="couponActive"
                                name="is_active"
                                value="1"
                                class="form-check-input"
                                <?= $isActive
                                    ? 'checked'
                                    : '' ?>>

                            <label
                                for="couponActive"
                                class="form-check-label">
                                Active
                            </label>

                        </div>

                    </div>

                </div>

            </div>

            <div class="card-footer text-end">

                <button
                    type="submit"
                    class="
                        btn
                        registration-form__submit
                        fs-14
                        fw-medium
                        text-uppercase
                    "
                    data-submit-button>

                    <span
                        class="registration-submit__idle"
                        data-submit-idle>

                        <i
                            class="
                                mdi
                                mdi-cloud-upload-outline
                                fs-20
                            "
                            aria-hidden="true">
                        </i>

                        Save Coupon

                    </span>

                    <span
                        class="
                            registration-submit__loading
                            d-none
                        "
                        data-submit-loading>

                        <span
                            class="
                                spinner-border
                                spinner-border-sm
                            "
                            role="status"
                            aria-hidden="true">
                        </span>

                        Saving...

                    </span>

                </button>

            </div>

        </div>

    </form>

</div>

<?php
$this->endSection();
