<?php

declare(strict_types=1);

$validationErrors =
    is_array(
        $validationErrors
            ?? null
    )
    ? $validationErrors
    : [];

$formAlert =
    is_array(
        $formAlert
            ?? null
    )
    ? $formAlert
    : null;

$this->extend(
    'FieldOfficer/Layouts/Main'
);

$this->section('content');
?>

<div class="auth-page-wrapper min-vh-100">

    <div
        class="auth-page-content
        d-flex
        align-items-center
        py-5">

        <div class="container">

            <div
                class="row
                justify-content-center">

                <div
                    class="col-md-8
                    col-lg-6
                    col-xl-5">

                    <div
                        class="card
                        border
                        border-danger
                        border-opacity-25">

                        <div class="card-body p-4">

                            <div
                                class="text-center mb-4">

                                <div
                                    class="avatar-md
                                    mx-auto
                                    mb-3">

                                    <div
                                        class="avatar-title
                                        rounded-circle
                                        bg-primary-subtle
                                        text-primary
                                        fs-24">

                                        <i
                                            class="ri-shield-check-line">
                                        </i>
                                    </div>
                                </div>

                                <h1 class="fs-20">
                                    Verify OTP
                                </h1>

                                <p
                                    class="text-muted mb-0">

                                    Enter the four-digit OTP sent
                                    to your registered mobile number.
                                </p>
                            </div>

                            <?= view(
                                'Components/Alerts/FormAlert',
                                [
                                    'alert' =>
                                    $formAlert,
                                ]
                            ) ?>

                            <form
                                action="<?= route_to(
                                            'field-officer.login.verify.submit'
                                        ) ?>"
                                method="post"
                                data-submit-loader>

                                <?= csrf_field() ?>

                                <div class="mb-4">

                                    <label
                                        for="foOtp"
                                        class="form-label">

                                        OTP
                                    </label>

                                    <input
                                        type="text"
                                        id="foOtp"
                                        name="otp"
                                        class="form-control
                                        text-center
                                        fs-20
                                        <?= isset(
                                            $validationErrors['otp']
                                        )
                                            ? 'is-invalid'
                                            : '' ?>"
                                        maxlength="4"
                                        inputmode="numeric"
                                        pattern="[0-9]{4}"
                                        autocomplete="one-time-code"
                                        required>

                                    <div
                                        class="invalid-feedback">

                                        <?= esc(
                                            $validationErrors['otp']
                                                ?? ''
                                        ) ?>
                                    </div>
                                </div>

                                <button
                                    type="submit"
                                    class="btn
                                    registration-form__submit
                                    fs-16
                                    fw-semibold"
                                    data-submit-button>

                                    <span
                                        data-submit-idle>
                                        Verify OTP
                                    </span>

                                    <span
                                        class="registration-submit__loading
                                        d-none"
                                        data-submit-loading>

                                        <span
                                            class="spinner-border
                                            spinner-border-sm">
                                        </span>

                                        Verifying...
                                    </span>
                                </button>
                            </form>

                            <div
                                class="d-flex
                                justify-content-between
                                mt-3">

                                <form
                                    method="post"
                                    action="<?= route_to(
                                                'field-officer.login.resend'
                                            ) ?>">

                                    <?= csrf_field() ?>

                                    <button
                                        type="submit"
                                        class="btn btn-link px-0">

                                        Resend OTP
                                    </button>
                                </form>

                                <form
                                    method="post"
                                    action="<?= route_to(
                                                'field-officer.login.cancel'
                                            ) ?>">

                                    <?= csrf_field() ?>

                                    <button
                                        type="submit"
                                        class="btn btn-link
                                        text-muted px-0">

                                        Cancel
                                    </button>
                                </form>

                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php $this->endSection(); ?>