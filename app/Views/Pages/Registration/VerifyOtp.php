<?php

declare(strict_types=1);

/**
 * @var string|null $profileReference
 * @var int|null $expiresAtTimestamp
 */

$formAlert = session('formAlert');

$formAlert = is_array($formAlert)
    ? $formAlert
    : null;

$expiry = is_numeric($expiresAtTimestamp)
    ? (int) $expiresAtTimestamp
    : 0;

$isExpired = $expiry <= time();

$this->extend('Layouts/Main');
$this->section('content');
?>

<section class="registration-otp-page py-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-5">

                <?= view('Components/Alerts/FormAlert', [
                    'alert' => $formAlert,
                ]) ?>

                <div class="card registration-otp-card">
                    <div class="card-body p-4 p-md-5 pt-md-4 pb-md-4 text-center">

                        <h1 class="fs-22 mb-2">
                            Verify your mobile
                        </h1>

                        <p class="text-muted mb-2">
                            Enter the four-digit OTP sent to your
                            registered mobile number.
                        </p>

                        <?php if (
                            is_string($profileReference)
                            && $profileReference !== ''
                        ): ?>
                            <p class="registration-profile-reference mb-4">
                                Profile reference:
                                <strong>
                                    <?= esc($profileReference) ?>
                                </strong>
                            </p>
                        <?php endif; ?>

                        <form
                            method="post"
                            action="<?= url_to(
                                        'web.registration.verify.submit'
                                    ) ?>"
                            id="registrationOtpForm"
                            autocomplete="one-time-code"
                            data-submit-loader
                            novalidate>

                            <?= csrf_field() ?>

                            <div
                                class="registration-otp-inputs"
                                role="group"
                                aria-label="Enter four-digit OTP">

                                <?php for (
                                    $index = 1;
                                    $index <= 4;
                                    $index++
                                ): ?>
                                    <input
                                        type="text"
                                        name="otp_<?= $index ?>"
                                        id="otp_<?= $index ?>"
                                        class="form-control registration-otp-input"
                                        inputmode="numeric"
                                        pattern="[0-9]"
                                        maxlength="1"
                                        autocomplete="<?= $index === 1
                                                            ? 'one-time-code'
                                                            : 'off' ?>"
                                        aria-label="OTP digit <?= $index ?>"
                                        required>
                                <?php endfor; ?>
                            </div>

                            <div
                                class="registration-otp-timer mt-4"
                                data-otp-expiry="<?= esc(
                                                        (string) ($expiry * 1000),
                                                        'attr'
                                                    ) ?>">

                                <div
                                    id="otpTimerMessage"
                                    class="<?= $isExpired
                                                ? 'd-none'
                                                : '' ?>">

                                    <span class="text-muted">
                                        OTP expires in
                                    </span>

                                    <strong
                                        id="otpTimer"
                                        class="ms-1">
                                        03:00
                                    </strong>
                                </div>

                                <div
                                    id="otpExpiredMessage"
                                    class="<?= $isExpired
                                                ? ''
                                                : 'd-none' ?>">

                                    <span class="text-danger">
                                        OTP expired.
                                    </span>
                                </div>

                                <div class="small mt-2">
                                    <span class="text-muted">
                                        Didn't receive the OTP?
                                    </span>

                                    <button
                                        type="submit"
                                        form="resendOtpForm"
                                        id="resendOtpButton"
                                        class="registration-otp-resend-link fs-12"
                                        <?= $isExpired
                                            ? ''
                                            : 'disabled' ?>>

                                        <span
                                            class="mdi mdi-refresh"
                                            aria-hidden="true">
                                        </span>

                                        <span>Resend OTP</span>
                                    </button>
                                </div>

                                <p class="small text-muted mb-0">
                                    You can request a maximum of three
                                    OTPs within 24 hours.
                                </p>
                            </div>

                            <button
                                type="submit"
                                class="btn btn-primary w-100 mt-4 d-inline-flex align-items-center justify-content-center gap-2"
                                id="verifyOtpButton"
                                <?= $isExpired
                                    ? 'disabled'
                                    : '' ?>>
                                <span
                                    class="mdi mdi-shield-check-outline fs-18"
                                    aria-hidden="true">
                                </span>

                                <span>Verify OTP</span>
                            </button>

                            <div class="d-flex gap-2 mt-3">
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary flex-fill d-inline-flex align-items-center justify-content-center gap-2"
                                    id="resetOtpButton">

                                    <span>Reset</span>
                                </button>

                                <button
                                    type="submit"
                                    class="btn btn-outline-danger flex-fill d-inline-flex align-items-center justify-content-center gap-2"
                                    form="cancelRegistrationForm">

                                    <span>Cancel</span>
                                </button>
                            </div>
                        </form>

                        <form
                            method="post"
                            action="<?= url_to(
                                        'web.registration.otp.resend'
                                    ) ?>"
                            id="resendOtpForm">

                            <?= csrf_field() ?>                            
                        </form>

                        <form
                            method="post"
                            action="<?= url_to(
                                        'web.registration.cancel'
                                    ) ?>"
                            id="cancelRegistrationForm">

                            <?= csrf_field() ?>
                        </form>

                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<?php $this->endSection(); ?>