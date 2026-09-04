<?php

declare(strict_types=1);

$errors =
    isset($validationErrors)
    && is_array($validationErrors)
    ? $validationErrors
    : [];

$plans =
    isset($membershipPlans)
    && is_array($membershipPlans)
    ? $membershipPlans
    : [];

$isEdit =
    isset($coupon)
    && is_array($coupon);

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
                        (int) $coupon['id']
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
                                            $coupon['code']
                                                ?? ''
                                        ),
                                        'attr'
                                    ) ?>"
                            data-error-required="Please enter the coupon code."
                            required>

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
                                            $coupon['usage_limit']
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
                                            $coupon['description']
                                                ?? ''
                                        ),
                                        'attr'
                                    ) ?>">

                    </div>

                    <div class="col-12">

                        <label class="form-label">
                            Applicable Plans
                        </label>

                        <select
                            name="plan_ids[]"
                            class="form-select"
                            multiple
                            data-choice
                            data-choice-search="false"
                            required>

                            <?php foreach (
                                $plans as $plan
                            ): ?>

                                <option
                                    value="<?= (int) (
                                                $plan['id']
                                                ?? 0
                                            ) ?>">

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

                        <select
                            id="couponDiscountType"
                            name="discount_type"
                            class="form-select"
                            data-coupon-discount-type
                            required>

                            <option value="">
                                Select Discount Type
                            </option>

                            <option value="PERCENTAGE">
                                Percentage
                            </option>

                            <option value="FLAT">
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
                            required>

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
                            required>

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

                        <select
                            id="couponEligibility"
                            name="eligibility_type"
                            class="form-select"
                            data-coupon-eligibility
                            required>

                            <option value="">
                                Select Eligibility
                            </option>

                            <option value="ALL">
                                All Members
                            </option>

                            <option value="SELECTED">
                                Selected Members
                            </option>

                            <option value="GENDER">
                                Male / Female Members
                            </option>

                        </select>

                    </div>

                    <div
                        class="
                            col-12
                            col-md-6
                            d-none
                        "
                        data-coupon-gender-container>

                        <label
                            for="couponGender"
                            class="form-label">
                            Gender
                        </label>

                        <select
                            id="couponGender"
                            name="eligible_gender"
                            class="form-select">

                            <option value="">
                                Select Gender
                            </option>

                            <option value="MALE">
                                Male
                            </option>

                            <option value="FEMALE">
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

                        <select
                            id="couponMembers"
                            name="member_ids[]"
                            class="form-select"
                            multiple
                            data-choice>
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
                                checked>

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
