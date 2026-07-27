<?php

declare(strict_types=1);

/**
 * Password-reset OTP verification screen.
 *
 * @var string                           $pageTitle
 * @var array<string, string>            $validationErrors
 * @var array<string, string>|null       $formAlert
 * @var int|null                         $expiresAtTimestamp
 */

$pageTitle = isset($pageTitle)
    ? (string) $pageTitle
    : 'Verify OTP';

$validationErrors = isset($validationErrors)
    && is_array($validationErrors)
    ? $validationErrors
    : [];

$formAlert = isset($formAlert)
    && is_array($formAlert)
    ? $formAlert
    : null;

$expiresAtTimestamp = isset($expiresAtTimestamp)
    && is_numeric($expiresAtTimestamp)
    ? (int) $expiresAtTimestamp
    : 0;

$otp = preg_replace(
    '/\D/',
    '',
    trim((string) old('otp'))
);

$otp = substr(
    (string) $otp,
    0,
    4
);

$otpError = isset(
    $validationErrors['otp']
)
    ? (string) $validationErrors['otp']
    : '';

$otpHasError =
    $otpError !== '';

$verifyAction = route_to(
    'web.forgot-password.verify.submit'
);

$resendAction = route_to(
    'web.forgot-password.resend'
);

$cancelAction = route_to(
    'web.forgot-password.cancel'
);

$this->extend('Layouts/Main');
$this->section('content');
?>

<section class="py-4">
    <div class="container">
        <div
            class="row justify-content-center
                align-items-center auth-content-height">
            <div
                class="col-12 col-sm-10 col-md-8
                    col-lg-5 col-xl-5">
                <div class="card border-0 shadow-lg mb-0">
                    <div class="card-body p-4 p-md-5 pt-md-4">

                        <div class="text-center mb-4">
                            <div class="mb-3">
                                <span
                                    class="mdi mdi-cellphone-key
                                        fs-36 text-primary"
                                    aria-hidden="true"></span>
                            </div>

                            <h1 class="fs-24 fw-semibold mb-2">
                                <?= esc($pageTitle) ?>
                            </h1>

                            <p class="text-muted mb-0">
                                Enter the four-digit OTP sent to your
                                verified primary mobile number.
                            </p>
                        </div>

                        <?= view(
                            'Components/Alerts/FormAlert',
                            [
                                'alert' => $formAlert,
                            ]
                        ) ?>

                        <form
                            id="passwordResetOtpForm"
                            action="<?= esc(
                                        $verifyAction,
                                        'attr'
                                    ) ?>"
                            method="post"
                            data-registration-form
                            data-submit-loader
                            data-otp-form
                            data-expires-at="<?= esc(
                                                    (string) $expiresAtTimestamp,
                                                    'attr'
                                                ) ?>"
                            novalidate
                            autocomplete="one-time-code">
                            <?= csrf_field() ?>

                            <div class="mb-3">
                                <label
                                    for="passwordResetOtp"
                                    class="form-label">
                                    One-Time Password
                                </label>

                                <input
                                    type="text"
                                    id="passwordResetOtp"
                                    name="otp"
                                    value="<?= esc(
                                                $otp,
                                                'attr'
                                            ) ?>"
                                    class="form-control text-center
                                        fs-24 fw-semibold
                                        <?= $otpHasError
                                            ? 'is-invalid'
                                            : '' ?>"
                                    placeholder="Enter 4-digit OTP"
                                    inputmode="numeric"
                                    autocomplete="one-time-code"
                                    maxlength="4"
                                    minlength="4"
                                    pattern="[0-9]{4}"
                                    aria-describedby="passwordResetOtpError"
                                    data-otp-input
                                    data-error-required="Please enter the OTP."
                                    data-error-invalid="Please enter the complete four-digit OTP."
                                    <?= $otpHasError
                                        ? 'aria-invalid="true"'
                                        : '' ?>
                                    required>

                                <div
                                    id="passwordResetOtpError"
                                    class="invalid-feedback"
                                    data-field-error="otp">
                                    <?= esc($otpError) ?>
                                </div>
                            </div>

                            <div
                                class="text-center text-muted fs-13 mb-4"
                                data-otp-timer-wrapper
                                aria-live="polite">
                                OTP expires in

                                <span
                                    class="fw-semibold text-primary"
                                    data-otp-countdown>
                                    --:--
                                </span>
                            </div>

                            <div class="mt-4">
                                <button
                                    type="submit"
                                    class="btn
                                        registration-form__submit
                                        fs-16 fw-semibold text-uppercase"
                                    data-submit-button>
                                    <span
                                        data-submit-idle
                                        aria-hidden="false">
                                        Verify OTP
                                    </span>

                                    <span
                                        class="registration-submit__loading d-none"
                                        data-submit-loading
                                        aria-hidden="true">
                                        <span
                                            class="spinner-border spinner-border-sm"
                                            role="status"
                                            aria-hidden="true"></span>

                                        <span>
                                            Verifying...
                                        </span>
                                    </span>
                                </button>
                            </div>
                        </form>

                        <div
                            class="d-flex justify-content-between
                                align-items-center gap-3 mt-4">
                            <form
                                action="<?= esc(
                                            $resendAction,
                                            'attr'
                                        ) ?>"
                                method="post"
                                data-resend-form>
                                <?= csrf_field() ?>

                                <button
                                    type="submit"
                                    class="btn btn-link p-0
                                        fw-semibold text-decoration-underline"
                                    data-resend-button
                                    disabled>
                                    Resend OTP
                                </button>
                            </form>

                            <form
                                action="<?= esc(
                                            $cancelAction,
                                            'attr'
                                        ) ?>"
                                method="post">
                                <?= csrf_field() ?>

                                <button
                                    type="submit"
                                    class="btn btn-link p-0
                                        text-muted text-decoration-underline">
                                    Cancel
                                </button>
                            </form>
                        </div>

                    </div>
                </div>

                <div class="text-center mt-4">
                    <p class="text-muted mb-0 fs-13">
                        <i
                            class="ri-shield-check-line
                                text-primary me-1"
                            aria-hidden="true"></i>

                        Never share your OTP with anyone.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
$this->endSection();
