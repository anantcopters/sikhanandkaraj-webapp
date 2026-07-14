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

<section class="registration-otp-page py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-5">

                <?= view('Components/Alerts/FormAlert', [
                    'alert' => $formAlert,
                ]) ?>

                <div class="card registration-otp-card">
                    <div class="card-body p-4 p-md-5 text-center">

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

                                <p
                                    id="otpTimerMessage"
                                    class="mb-1 <?= $isExpired
                                        ? 'd-none'
                                        : '' ?>">
                                    OTP expires in
                                    <strong id="otpTimer">
                                        03:00
                                    </strong>
                                </p>

                                <p
                                    id="otpExpiredMessage"
                                    class="text-danger mb-1 <?= $isExpired
                                        ? ''
                                        : 'd-none' ?>">
                                    Your OTP has expired.
                                </p>

                                <p class="small text-muted mb-0">
                                    You can request a maximum of three
                                    OTPs within 24 hours.
                                </p>
                            </div>

                            <button
                                type="submit"
                                class="btn btn-primary w-100 mt-4"
                                id="verifyOtpButton"
                                <?= $isExpired
                                    ? 'disabled'
                                    : '' ?>>
                                Verify OTP
                            </button>

                            <div class="d-flex gap-2 mt-3">
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary flex-fill"
                                    id="resetOtpButton">
                                    Reset
                                </button>

                                <button
                                    type="submit"
                                    class="btn btn-outline-danger flex-fill"
                                    form="cancelRegistrationForm">
                                    Cancel
                                </button>
                            </div>
                        </form>

                        <form
                            method="post"
                            action="<?= url_to(
                                'web.registration.otp.resend'
                            ) ?>"
                            id="resendOtpForm"
                            class="mt-4">

                            <?= csrf_field() ?>

                            <button
                                type="submit"
                                id="resendOtpButton"
                                class="btn btn-link p-0"
                                <?= $isExpired
                                    ? ''
                                    : 'disabled' ?>>
                                Resend OTP
                            </button>
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

