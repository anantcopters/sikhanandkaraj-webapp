<?php

declare(strict_types=1);

/**
 * Local view variables.
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

$otpError = isset($validationErrors['otp'])
    ? (string) $validationErrors['otp']
    : '';

$otpHasError = $otpError !== '';

$verifyAction = route_to(
    'web.forgot-password.verify.submit'
);

$resendAction = route_to(
    'web.forgot-password.resend'
);

$cancelUrl = route_to(
    'web.forgot-password.cancel'
);

$this->extend('Layouts/Main');
$this->section('content');
?>

<section class="registration-page">
    <div class="container">
        <div class="registration-page__content">
            <div class="registration-form">
                <div class="registration-form__header">
                    <h1 class="registration-form__title">
                        <?= esc($pageTitle) ?>
                    </h1>

                    <p class="registration-form__description">
                        Enter the OTP sent to your verified mobile number.
                    </p>
                </div>

                <?php if ($formAlert !== null): ?>
                    <?= view(
                        'Components/Alert',
                        [
                            'alert' => $formAlert,
                        ]
                    ) ?>
                <?php endif; ?>

                <?= view(
                    'Components/Authentication/OtpForm',
                    [
                        'verifyAction' =>
                        $verifyAction,

                        'resendAction' =>
                        $resendAction,

                        'cancelUrl' =>
                        $cancelUrl,

                        'expiresAtTimestamp' =>
                        $expiresAtTimestamp,

                        'otpError' =>
                        $otpError,

                        'otpHasError' =>
                        $otpHasError,
                    ]
                ) ?>
            </div>
        </div>
    </div>
</section>

<?php $this->endSection(); ?>