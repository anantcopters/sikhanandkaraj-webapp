<?php

declare(strict_types=1);

/**
 * @var int                  $memberId
 * @var string               $memberName
 * @var string               $profileReference
 * @var array<string, mixed> $membershipPlans
 * @var array<string, string> $validationErrors
 * @var bool                 $openModal
 */

$plans = isset(
    $membershipPlans['plans']
)
    && is_array(
        $membershipPlans['plans']
    )
    ? $membershipPlans['plans']
    : [];

$errors = isset($validationErrors)
    && is_array($validationErrors)
    ? $validationErrors
    : [];

$selectedPlan =
    mb_strtoupper(
        trim(
            (string) old(
                'plan_code'
            )
        )
    );

$selectedPaymentMethod =
    mb_strtoupper(
        trim(
            (string) old(
                'payment_method'
            )
        )
    );

?>

<div
    class="modal fade"
    id="offline-payment-modal"
    tabindex="-1"
    aria-labelledby="offline-payment-modal-title"
    aria-hidden="true"
    data-open-modal="<?= $openModal
                            ? '1'
                            : '0' ?>">

    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header bg-info-subtle py-2">
                <div>
                    <h2
                        class="modal-title fs-18 fw-semibold"
                        id="offline-payment-modal-title">
                        Record Offline Payment
                    </h2>

                    <p class="text-muted fs-13 mb-0">
                        <?= esc($memberName) ?>

                        <?php if ($profileReference !== ''): ?>
                            · <?= esc($profileReference) ?>
                        <?php endif; ?>
                    </p>
                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close">
                </button>
            </div>

            <form
                method="post"
                action="<?= esc(
                            route_to(
                                'admin.members.offline-payment',
                                $memberId
                            ),
                            'attr'
                        ) ?>"
                data-validate
                data-submit-loader
                novalidate>

                <?= csrf_field() ?>

                <div class="modal-body">

                    <div class="alert alert-warning fs-13">
                        <i
                            class="ri-information-line me-1"
                            aria-hidden="true">
                        </i>

                        Saving this payment will immediately activate,
                        renew or upgrade the member's plan according to
                        the existing membership rules.
                    </div>

                    <div class="row g-3">

                        <div class="col-12 col-md-6">
                            <label
                                for="offlinePaymentPlan"
                                class="form-label">
                                Membership Plan
                            </label>

                            <select
                                id="offlinePaymentPlan"
                                name="plan_code"
                                class="form-select <?= isset(
                                                        $errors['plan_code']
                                                    )
                                                        ? 'is-invalid'
                                                        : '' ?>"
                                data-error-required="Please select a membership plan."
                                required>

                                <option value="">
                                    Select Plan
                                </option>

                                <?php foreach ($plans as $plan): ?>
                                    <?php
                                    if (!is_array($plan)) {
                                        continue;
                                    }

                                    $code = mb_strtoupper(
                                        trim(
                                            (string) (
                                                $plan['code']
                                                ?? ''
                                            )
                                        )
                                    );

                                    $name = trim(
                                        (string) (
                                            $plan['name']
                                            ?? $code
                                        )
                                    );

                                    $decision =
                                        isset(
                                            $plan['purchaseDecision']
                                        )
                                        && is_array(
                                            $plan['purchaseDecision']
                                        )
                                        ? $plan['purchaseDecision']
                                        : [];

                                    $allowed =
                                        ($decision['allowed']
                                            ?? false)
                                        === true;

                                    if (
                                        $code === ''
                                        || !$allowed
                                    ) {
                                        continue;
                                    }
                                    ?>

                                    <option
                                        value="<?= esc(
                                                    $code,
                                                    'attr'
                                                ) ?>"
                                        <?= $selectedPlan === $code
                                            ? 'selected'
                                            : '' ?>>
                                        <?= esc($name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <div
                                class="invalid-feedback <?= isset(
                                                            $errors['plan_code']
                                                        )
                                                            ? 'd-block'
                                                            : '' ?>"
                                data-validation-error="plan_code">
                                <?= esc(
                                    $errors['plan_code']
                                        ?? ''
                                ) ?>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label
                                for="offlinePaymentDate"
                                class="form-label">
                                Payment Date
                            </label>

                            <input
                                type="date"
                                id="offlinePaymentDate"
                                name="payment_date"
                                class="form-control <?= isset(
                                                        $errors['payment_date']
                                                    )
                                                        ? 'is-invalid'
                                                        : '' ?>"
                                value="<?= esc(
                                            old('payment_date'),
                                            'attr'
                                        ) ?>"
                                max="<?= date('Y-m-d') ?>"
                                data-error-required="Please select the payment date."
                                required>

                            <div
                                class="invalid-feedback <?= isset(
                                                            $errors['payment_date']
                                                        )
                                                            ? 'd-block'
                                                            : '' ?>"
                                data-validation-error="payment_date">
                                <?= esc(
                                    $errors['payment_date']
                                        ?? ''
                                ) ?>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label
                                for="offlinePaymentMethod"
                                class="form-label">
                                Payment Source
                            </label>

                            <select
                                id="offlinePaymentMethod"
                                name="payment_method"
                                class="form-select <?= isset(
                                                        $errors['payment_method']
                                                    )
                                                        ? 'is-invalid'
                                                        : '' ?>"
                                data-error-required="Please select the payment source."
                                required>

                                <option value="">
                                    Select Payment Source
                                </option>

                                <?php foreach (
                                    [
                                        'BANK_TRANSFER' =>
                                        'Bank Transfer',

                                        'UPI' =>
                                        'UPI',

                                        'CASH' =>
                                        'Cash',

                                        'OTHER' =>
                                        'Other',
                                    ]
                                    as $value => $label
                                ): ?>

                                    <option
                                        value="<?= esc(
                                                    $value,
                                                    'attr'
                                                ) ?>"
                                        <?= $selectedPaymentMethod
                                            === $value
                                            ? 'selected'
                                            : '' ?>>
                                        <?= esc($label) ?>
                                    </option>

                                <?php endforeach; ?>
                            </select>

                            <div
                                class="invalid-feedback <?= isset(
                                                            $errors['payment_method']
                                                        )
                                                            ? 'd-block'
                                                            : '' ?>"
                                data-validation-error="payment_method">
                                <?= esc(
                                    $errors['payment_method']
                                        ?? ''
                                ) ?>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label
                                for="offlinePaymentAmount"
                                class="form-label">
                                Amount Received
                            </label>

                            <div class="input-group">
                                <span class="input-group-text">
                                    ₹
                                </span>

                                <input
                                    type="number"
                                    id="offlinePaymentAmount"
                                    name="amount"
                                    class="form-control <?= isset(
                                                            $errors['amount']
                                                        )
                                                            ? 'is-invalid'
                                                            : '' ?>"
                                    value="<?= esc(
                                                old('amount'),
                                                'attr'
                                            ) ?>"
                                    min="0.01"
                                    max="999999.99"
                                    step="0.01"
                                    data-error-required="Please enter the amount received."
                                    required>
                            </div>

                            <div
                                class="invalid-feedback <?= isset(
                                                            $errors['amount']
                                                        )
                                                            ? 'd-block'
                                                            : '' ?>"
                                data-validation-error="amount">
                                <?= esc(
                                    $errors['amount']
                                        ?? ''
                                ) ?>
                            </div>
                        </div>

                        <div class="col-12">
                            <label
                                for="offlinePaymentReference"
                                class="form-label">
                                Transaction / Reference Number
                                <span class="text-muted fw-normal">
                                    (Optional)
                                </span>
                            </label>

                            <input
                                type="text"
                                id="offlinePaymentReference"
                                name="transaction_reference"
                                class="form-control <?= isset(
                                                        $errors['transaction_reference']
                                                    )
                                                        ? 'is-invalid'
                                                        : '' ?>"
                                value="<?= esc(
                                            old(
                                                'transaction_reference'
                                            ),
                                            'attr'
                                        ) ?>"
                                maxlength="120">

                            <div
                                class="invalid-feedback <?= isset(
                                                            $errors['transaction_reference']
                                                        )
                                                            ? 'd-block'
                                                            : '' ?>"
                                data-validation-error="transaction_reference">
                                <?= esc(
                                    $errors['transaction_reference']
                                        ?? ''
                                ) ?>
                            </div>
                        </div>

                        <div class="col-12">
                            <label
                                for="offlinePaymentNote"
                                class="form-label">
                                Payment Note
                                <span class="text-muted fw-normal">
                                    (Optional)
                                </span>
                            </label>

                            <textarea
                                id="offlinePaymentNote"
                                name="payment_note"
                                class="form-control <?= isset(
                                                        $errors['payment_note']
                                                    )
                                                        ? 'is-invalid'
                                                        : '' ?>"
                                rows="3"
                                maxlength="500"><?= esc(
                                                    old('payment_note')
                                                ) ?></textarea>

                            <div
                                class="invalid-feedback <?= isset(
                                                            $errors['payment_note']
                                                        )
                                                            ? 'd-block'
                                                            : '' ?>"
                                data-validation-error="payment_note">
                                <?= esc(
                                    $errors['payment_note']
                                        ?? ''
                                ) ?>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-light fs-14"
                        data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="btn registration-form__submit
                            fs-14 fw-medium text-uppercase w-25"
                        data-submit-button>

                        <span
                            class="registration-submit__idle"
                            data-submit-idle>

                            <i
                                class="mdi mdi-cloud-upload-outline fs-14"
                                aria-hidden="true">
                            </i>

                            Save Payment
                        </span>

                        <span
                            class="registration-submit__loading d-none"
                            data-submit-loading>

                            <span
                                class="spinner-border spinner-border-sm"
                                role="status"
                                aria-hidden="true">
                            </span>

                            Saving...
                        </span>
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>