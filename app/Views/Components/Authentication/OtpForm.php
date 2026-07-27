<?php

declare(strict_types=1);

/**
 * Reusable four-digit OTP verification form.
 *
 * Expected variables:
 *
 * @var string $verifyAction
 * @var string $resendAction
 * @var string $cancelAction
 * @var int    $expiresAtTimestamp
 * @var string $otpError
 * @var bool   $otpHasError
 */

$verifyAction = isset($verifyAction)
    ? (string) $verifyAction
    : '';

$resendAction = isset($resendAction)
    ? (string) $resendAction
    : '';

$cancelAction = isset($cancelAction)
    ? (string) $cancelAction
    : '';

$expiresAtTimestamp = isset($expiresAtTimestamp)
    && is_numeric($expiresAtTimestamp)
    ? (int) $expiresAtTimestamp
    : 0;

$otpError = isset($otpError)
    ? (string) $otpError
    : '';

$otpHasError = isset($otpHasError)
    ? (bool) $otpHasError
    : false;

$otpValue = preg_replace(
    '/\D/',
    '',
    trim((string) old('otp'))
);

$otpValue = substr(
    (string) $otpValue,
    0,
    4
);
?>

<form
    id="passwordResetOtpForm"
    action="<?= esc($verifyAction, 'attr') ?>"
    method="post"
    class="registration-form__form"
    data-password-reset-otp-form
    data-expires-at="<?= esc(
                            (string) $expiresAtTimestamp,
                            'attr'
                        ) ?>"
    novalidate>
    <?= csrf_field() ?>

    <div class="registration-form__group">
        <label
            for="otp"
            class="registration-form__label">
            OTP
        </label>

        <input
            type="text"
            id="otp"
            name="otp"
            class="registration-form__control
                <?= $otpHasError ? 'is-invalid' : '' ?>"
            value="<?= esc($otpValue, 'attr') ?>"
            inputmode="numeric"
            autocomplete="one-time-code"
            maxlength="4"
            minlength="4"
            pattern="[0-9]{4}"
            aria-describedby="otpHelp
                <?= $otpHasError ? ' otpError' : '' ?>"
            required>

        <div
            id="otpHelp"
            class="registration-form__help">
            Enter the four-digit OTP sent to your verified mobile number.
        </div>

        <?php if ($otpHasError): ?>
            <div
                id="otpError"
                class="invalid-feedback">
                <?= esc($otpError) ?>
            </div>
        <?php endif; ?>
    </div>

    <div
        class="registration-otp__timer"
        data-password-reset-otp-timer
        aria-live="polite">
        OTP expires in
        <span data-password-reset-otp-countdown>
            --:--
        </span>
    </div>

    <button
        type="submit"
        id="verifyPasswordResetOtpButton"
        class="registration-form__submit">
        <span class="registration-submit__text">
            Verify OTP
        </span>

        <span
            class="registration-submit__loading"
            hidden>
            <span
                class="spinner-border spinner-border-sm"
                aria-hidden="true"></span>

            Verifying...
        </span>
    </button>
</form>

<div class="registration-otp__actions">
    <form
        action="<?= esc($resendAction, 'attr') ?>"
        method="post"
        data-password-reset-resend-form>
        <?= csrf_field() ?>

        <button
            type="submit"
            class="registration-form__link-button"
            data-password-reset-resend-button
            disabled>
            Resend OTP
        </button>
    </form>

    <form
        action="<?= esc($cancelAction, 'attr') ?>"
        method="post">
        <?= csrf_field() ?>

        <button
            type="submit"
            class="registration-form__link-button">
            Cancel
        </button>
    </form>
</div>