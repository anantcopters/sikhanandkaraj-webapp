<?php

declare(strict_types=1);

/**
 * Reusable OTP verification screen.
 *
 * Used by:
 *
 * - Registration mobile verification
 * - Forgot-password OTP verification
 *
 * @var string|null                $pageTitle
 * @var string|null                $heading
 * @var string|null                $description
 * @var string|null                $profileReference
 * @var int|null                   $expiresAtTimestamp
 * @var string|null                $verifyAction
 * @var string|null                $resendAction
 * @var string|null                $cancelAction
 * @var string|null                $cancelLabel
 * @var string|null                $sendLimitMessage
 * @var array<string, string>|null $validationErrors
 * @var array<string, string>|null $formAlert
 * @var bool|null                  $hidePublicLoginAction
 */

$pageTitle = isset($pageTitle)
    ? trim((string) $pageTitle)
    : 'Verify OTP';

$heading = isset($heading)
    ? trim((string) $heading)
    : 'Verify your mobile';

$description = isset($description)
    ? trim((string) $description)
    : 'Enter the four-digit OTP sent to your mobile number.';

$profileReference = isset($profileReference)
    ? trim((string) $profileReference)
    : '';

$expiresAtTimestamp = isset($expiresAtTimestamp)
    && is_numeric($expiresAtTimestamp)
    ? (int) $expiresAtTimestamp
    : 0;

$verifyAction = isset($verifyAction)
    ? trim((string) $verifyAction)
    : '';

$resendAction = isset($resendAction)
    ? trim((string) $resendAction)
    : '';

$cancelAction = isset($cancelAction)
    ? trim((string) $cancelAction)
    : '';

$cancelLabel = isset($cancelLabel)
    ? trim((string) $cancelLabel)
    : 'Cancel';

$sendLimitMessage = isset($sendLimitMessage)
    ? trim((string) $sendLimitMessage)
    : '';

$validationErrors = isset($validationErrors)
    && is_array($validationErrors)
    ? $validationErrors
    : [];

$formAlert = isset($formAlert)
    && is_array($formAlert)
    ? $formAlert
    : null;

$hidePublicLoginAction =
    ($hidePublicLoginAction ?? false)
    === true;

$otpError = isset($validationErrors['otp'])
    ? trim((string) $validationErrors['otp'])
    : '';

$otpHasError = $otpError !== '';

$isExpired = $expiresAtTimestamp <= time();

/**
 * Restore only validated one-digit old values.
 */
$otpDigits = [];

for ($index = 1; $index <= 4; $index++) {
    $oldDigit = trim(
        (string) old('otp_' . $index)
    );

    $otpDigits[$index] = preg_match(
        '/^\d$/',
        $oldDigit
    ) === 1
        ? $oldDigit
        : '';
}

$this->extend('Layouts/Main');
$this->section('content');
?>

<section class="registration-otp-page py-4">
    <div class="container">
        <div
            class="row justify-content-center
                align-items-center auth-content-height">

            <div class="col-12 col-sm-10 col-md-8 col-lg-5">

                <?= view(
                    'Components/Alerts/FormAlert',
                    [
                        'alert' => $formAlert,
                    ]
                ) ?>

                <div class="card registration-otp-card">
                    <div
                        class="card-body p-4 p-md-5
                            pt-md-4 pb-md-4 text-center">

                        <div class="mb-3">
                            <span
                                class="mdi mdi-cellphone-key
                                    fs-36 text-primary"
                                aria-hidden="true">
                            </span>
                        </div>

                        <h1 class="fs-22 mb-2">
                            <?= esc($heading) ?>
                        </h1>

                        <p class="text-muted mb-2">
                            <?= esc($description) ?>
                        </p>

                        <?php if ($profileReference !== ''): ?>
                            <p
                                class="registration-profile-reference
                                    mb-4">

                                Profile reference:

                                <strong>
                                    <?= esc($profileReference) ?>
                                </strong>
                            </p>
                        <?php endif; ?>

                        <form
                            method="post"
                            action="<?= esc(
                                        $verifyAction,
                                        'attr'
                                    ) ?>"
                            id="registrationOtpForm"
                            autocomplete="one-time-code"
                            data-submit-loader
                            novalidate>

                            <?= csrf_field() ?>

                            <div
                                class="registration-otp-inputs"
                                role="group"
                                aria-label="Enter four-digit OTP"
                                aria-describedby="<?= $otpHasError
                                                        ? 'otpError'
                                                        : 'otpHelp' ?>">

                                <?php for (
                                    $index = 1;
                                    $index <= 4;
                                    $index++
                                ): ?>
                                    <input
                                        type="text"
                                        name="otp_<?= $index ?>"
                                        id="otp_<?= $index ?>"
                                        class="form-control
                                            registration-otp-input
                                            <?= $otpHasError
                                                ? 'is-invalid'
                                                : '' ?>"
                                        value="<?= esc(
                                                    $otpDigits[$index],
                                                    'attr'
                                                ) ?>"
                                        inputmode="numeric"
                                        pattern="[0-9]"
                                        maxlength="1"
                                        autocomplete="<?= $index === 1
                                                            ? 'one-time-code'
                                                            : 'off' ?>"
                                        aria-label="OTP digit <?= $index ?>"
                                        <?= $otpHasError
                                            ? 'aria-invalid="true"'
                                            : '' ?>
                                        required>
                                <?php endfor; ?>
                            </div>

                            <div
                                id="otpHelp"
                                class="small text-muted mt-2">
                                Enter all four OTP digits.
                            </div>

                            <?php if ($otpHasError): ?>
                                <div
                                    id="otpError"
                                    class="text-danger small mt-2"
                                    role="alert">
                                    <?= esc($otpError) ?>
                                </div>
                            <?php endif; ?>

                            <div
                                class="registration-otp-timer mt-4"
                                data-otp-expiry="<?= esc(
                                                        (string) (
                                                            $expiresAtTimestamp
                                                            * 1000
                                                        ),
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
                                        --:--
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
                                        class="registration-otp-resend-link
                                            fs-12"
                                        <?= $isExpired
                                            ? ''
                                            : 'disabled' ?>>

                                        <span
                                            class="mdi mdi-refresh"
                                            aria-hidden="true">
                                        </span>

                                        <span>
                                            Resend OTP
                                        </span>
                                    </button>
                                </div>

                                <?php if ($sendLimitMessage !== ''): ?>
                                    <p class="small text-muted mb-0">
                                        <?= esc($sendLimitMessage) ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <button
                                type="submit"
                                class="btn btn-primary w-100 mt-4
        d-inline-flex align-items-center
        justify-content-center gap-2"
                                id="verifyOtpButton"
                                data-submit-button
                                <?= $isExpired
                                    ? 'disabled'
                                    : '' ?>>

                                <span
                                    class="d-inline-flex align-items-center gap-2"
                                    data-submit-idle
                                    aria-hidden="false">

                                    <span
                                        class="mdi mdi-shield-check-outline fs-18"
                                        aria-hidden="true">
                                    </span>

                                    <span>
                                        Verify OTP
                                    </span>
                                </span>

                                <span
                                    class="registration-submit__loading
            d-none align-items-center gap-2"
                                    data-submit-loading
                                    aria-hidden="true">

                                    <span
                                        class="spinner-border spinner-border-sm"
                                        role="status"
                                        aria-hidden="true">
                                    </span>

                                    <span>
                                        Verifying...
                                    </span>
                                </span>
                            </button>

                            <div class="d-flex gap-2 mt-3">
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary
                                        flex-fill d-inline-flex
                                        align-items-center
                                        justify-content-center gap-2"
                                    id="resetOtpButton">

                                    <span>
                                        Reset
                                    </span>
                                </button>

                                <button
                                    type="submit"
                                    class="btn btn-outline-danger
                                        flex-fill d-inline-flex
                                        align-items-center
                                        justify-content-center gap-2"
                                    form="cancelOtpForm">

                                    <span>
                                        <?= esc($cancelLabel) ?>
                                    </span>
                                </button>
                            </div>
                        </form>

                        <form
                            method="post"
                            action="<?= esc(
                                        $resendAction,
                                        'attr'
                                    ) ?>"
                            id="resendOtpForm">

                            <?= csrf_field() ?>
                        </form>

                        <form
                            method="post"
                            action="<?= esc(
                                        $cancelAction,
                                        'attr'
                                    ) ?>"
                            id="cancelOtpForm">

                            <?= csrf_field() ?>
                        </form>

                    </div>
                </div>

                <div class="text-center mt-4">
                    <p class="text-muted mb-0 fs-13">
                        <span
                            class="mdi mdi-shield-check-outline
                                text-primary me-1"
                            aria-hidden="true">
                        </span>

                        Never share your OTP with anyone.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php $this->endSection(); ?>