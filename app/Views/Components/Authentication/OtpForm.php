<?php

declare(strict_types=1);

/**
 * Local component variables.
 */
$verifyAction = isset($verifyAction)
    ? (string) $verifyAction
    : '';

$resendAction = isset($resendAction)
    ? (string) $resendAction
    : '';

$cancelUrl = isset($cancelUrl)
    ? (string) $cancelUrl
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

$otpValue = trim(
    (string) old('otp')
);
?>

<form
    action="<?= esc($verifyAction, 'attr') ?>"
    method="post"
    class="registration-form__form"
    data-otp-form
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
                <?= $otpHasError
                    ? 'is-invalid'
                    : '' ?>"
            value="<?= esc($otpValue, 'attr') ?>"
            inputmode="numeric"
            autocomplete="one-time-code"
            maxlength="6"
            pattern="[0-9]{6}"
            required>

        <?php if ($otpHasError): ?>
            <div class="invalid-feedback">
                <?= esc($otpError) ?>
            </div>
        <?php endif; ?>
    </div>

    <div
        class="registration-otp__timer"
        data-otp-timer>
        OTP expires in
        <span data-otp-countdown>--:--</span>
    </div>

    <button
        type="submit"
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
        method="post">
        <?= csrf_field() ?>

        <button
            type="submit"
            class="registration-form__link-button"
            data-resend-button>
            Resend OTP
        </button>
    </form>

    <a
        href="<?= esc($cancelUrl, 'attr') ?>"
        class="registration-form__link">
        Cancel
    </a>
</div>