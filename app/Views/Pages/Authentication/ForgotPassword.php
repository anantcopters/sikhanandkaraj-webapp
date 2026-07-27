<?php

declare(strict_types=1);

/**
 * Local view variables.
 */

$pageTitle = isset($pageTitle)
    ? (string) $pageTitle
    : 'Forgot Password';

$validationErrors = isset($validationErrors)
    && is_array($validationErrors)
    ? $validationErrors
    : [];

$formAlert = isset($formAlert)
    && is_array($formAlert)
    ? $formAlert
    : null;

$identifier = trim(
    (string) old('identifier')
);

$identifierError = isset(
    $validationErrors['identifier']
)
    ? (string) $validationErrors['identifier']
    : '';

$identifierHasError =
    $identifierError !== '';

$formAction = route_to(
    'web.forgot-password.send-otp'
);

$loginUrl = route_to('web.login');

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
                        Enter your registered email address or mobile
                        number. The OTP will be sent only to your verified
                        mobile number.
                    </p>
                </div>

                <?php if ($formAlert !== null): ?>
                    <?= view(
                        'Components/Alerts/FormAlert',
                        [
                            'alert' => $formAlert,
                        ]
                    ) ?>
                <?php endif; ?>

                <form
                    action="<?= esc($formAction, 'attr') ?>"
                    method="post"
                    class="registration-form__form"
                    novalidate>
                    <?= csrf_field() ?>

                    <div class="registration-form__group">
                        <label
                            for="identifier"
                            class="registration-form__label">
                            Email or mobile number
                        </label>

                        <input
                            type="text"
                            id="identifier"
                            name="identifier"
                            class="registration-form__control
                                <?= $identifierHasError
                                    ? 'is-invalid'
                                    : '' ?>"
                            value="<?= esc(
                                        $identifier,
                                        'attr'
                                    ) ?>"
                            maxlength="254"
                            autocomplete="username"
                            required>

                        <?php if ($identifierHasError): ?>
                            <div class="invalid-feedback">
                                <?= esc($identifierError) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <button
                        type="submit"
                        class="registration-form__submit">
                        <span class="registration-submit__text">
                            Send OTP
                        </span>

                        <span
                            class="registration-submit__loading"
                            hidden>
                            <span
                                class="spinner-border spinner-border-sm"
                                aria-hidden="true"></span>

                            Sending...
                        </span>
                    </button>
                </form>

                <div class="registration-form__footer">
                    <a
                        href="<?= esc($loginUrl, 'attr') ?>"
                        class="registration-form__link">
                        Back to login
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php $this->endSection(); ?>