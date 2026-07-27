<?php

declare(strict_types=1);

/**
 * Local view variables.
 */
$pageTitle = isset($pageTitle)
    ? (string) $pageTitle
    : 'Set New Password';

$validationErrors = isset($validationErrors)
    && is_array($validationErrors)
    ? $validationErrors
    : [];

$formAlert = isset($formAlert)
    && is_array($formAlert)
    ? $formAlert
    : null;

$passwordError = isset(
    $validationErrors['password']
)
    ? (string) $validationErrors['password']
    : '';

$passwordConfirmationError = isset(
    $validationErrors['password_confirmation']
)
    ? (string) $validationErrors['password_confirmation']
    : '';

$passwordHasError =
    $passwordError !== '';

$passwordConfirmationHasError =
    $passwordConfirmationError !== '';

$formAction = route_to(
    'web.forgot-password.password.submit'
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
                        Create a password containing at least 10
                        characters, uppercase, lowercase, number and
                        special character.
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

                <form
                    action="<?= esc($formAction, 'attr') ?>"
                    method="post"
                    class="registration-form__form"
                    novalidate>
                    <?= csrf_field() ?>

                    <div class="registration-form__group">
                        <label
                            for="password"
                            class="registration-form__label">
                            New password
                        </label>

                        <div class="password-field">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="registration-form__control
                                    <?= $passwordHasError
                                        ? 'is-invalid'
                                        : '' ?>"
                                maxlength="128"
                                autocomplete="new-password"
                                required>

                            <button
                                type="button"
                                class="password-field__toggle"
                                data-password-toggle="password"
                                aria-label="Show password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>

                        <?php if ($passwordHasError): ?>
                            <div class="invalid-feedback d-block">
                                <?= esc($passwordError) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="registration-form__group">
                        <label
                            for="password_confirmation"
                            class="registration-form__label">
                            Confirm password
                        </label>

                        <div class="password-field">
                            <input
                                type="password"
                                id="password_confirmation"
                                name="password_confirmation"
                                class="registration-form__control
                                    <?= $passwordConfirmationHasError
                                        ? 'is-invalid'
                                        : '' ?>"
                                maxlength="128"
                                autocomplete="new-password"
                                required>

                            <button
                                type="button"
                                class="password-field__toggle"
                                data-password-toggle="password_confirmation"
                                aria-label="Show confirmed password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>

                        <?php if (
                            $passwordConfirmationHasError
                        ): ?>
                            <div class="invalid-feedback d-block">
                                <?= esc(
                                    $passwordConfirmationError
                                ) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <button
                        type="submit"
                        class="registration-form__submit">
                        <span class="registration-submit__text">
                            Reset Password
                        </span>

                        <span
                            class="registration-submit__loading"
                            hidden>
                            <span
                                class="spinner-border spinner-border-sm"
                                aria-hidden="true"></span>

                            Updating...
                        </span>
                    </button>
                </form>

                <div class="registration-form__footer">
                    <a
                        href="<?= esc($cancelUrl, 'attr') ?>"
                        class="registration-form__link">
                        Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php $this->endSection(); ?>