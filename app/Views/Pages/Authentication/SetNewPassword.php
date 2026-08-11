<?php

declare(strict_types=1);

/**
 * Set-new-password screen.
 *
 * @var string                           $pageTitle
 * @var array<string, string>            $validationErrors
 * @var array<string, string>|null       $formAlert
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
    'web.forgot-password.password.update'
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
                <div class="card border border-danger border-opacity-25 shadow-lg mb-0">
                    <div class="card-body p-4 p-md-5 pt-md-4">

                        <div class="text-center mb-4">
                            <div class="mb-3">
                                <span
                                    class="mdi mdi-lock-reset
                                        fs-36 text-primary"
                                    aria-hidden="true"></span>
                            </div>

                            <h1 class="fs-24 fw-semibold mb-2">
                                <?= esc($pageTitle) ?>
                            </h1>

                            <p class="text-muted mb-0">
                                Create a strong new password for your
                                Sikhanandkaraj account.
                            </p>
                        </div>

                        <?= view(
                            'Components/Alerts/FormAlert',
                            [
                                'alert' => $formAlert,
                            ]
                        ) ?>

                        <form
                            id="setNewPasswordForm"
                            action="<?= esc(
                                        $formAction,
                                        'attr'
                                    ) ?>"
                            method="post"
                            data-registration-form
                            data-submit-loader
                            data-password-form
                            novalidate
                            autocomplete="off">
                            <?= csrf_field() ?>

                            <div class="mb-3">
                                <label
                                    for="newPassword"
                                    class="form-label">
                                    New Password
                                </label>

                                <div class="password-field">
                                    <input
                                        type="password"
                                        id="newPassword"
                                        name="password"
                                        class="form-control
                                            password-field__input
                                            <?= $passwordHasError
                                                ? 'is-invalid'
                                                : '' ?>"
                                        placeholder="Enter your new password"
                                        maxlength="128"
                                        autocomplete="new-password"
                                        aria-describedby="newPasswordHelp newPasswordError"
                                        data-password
                                        data-error-required="Please enter your new password."
                                        data-error-invalid="Password must contain at least 10 characters, uppercase, lowercase, number and special character."
                                        <?= $passwordHasError
                                            ? 'aria-invalid="true"'
                                            : '' ?>
                                        required>

                                    <button
                                        type="button"
                                        class="password-field__toggle"
                                        data-password-toggle="newPassword"
                                        aria-label="Show password"
                                        aria-controls="newPassword"
                                        aria-pressed="false">
                                        <span
                                            class="mdi
                                                mdi-eye-off-outline"
                                            aria-hidden="true"></span>
                                    </button>
                                </div>

                                <div
                                    id="newPasswordHelp"
                                    class="form-text color-pink">
                                    Minimum 10 characters with uppercase,
                                    lowercase, number and special character.
                                </div>

                                <div
                                    id="newPasswordError"
                                    class="invalid-feedback d-block"
                                    data-field-error="password">
                                    <?= esc($passwordError) ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label
                                    for="newPasswordConfirmation"
                                    class="form-label">
                                    Confirm New Password
                                </label>

                                <div class="password-field">
                                    <input
                                        type="password"
                                        id="newPasswordConfirmation"
                                        name="password_confirmation"
                                        class="form-control
                                            password-field__input
                                            <?= $passwordConfirmationHasError
                                                ? 'is-invalid'
                                                : '' ?>"
                                        placeholder="Re-enter your new password"
                                        maxlength="128"
                                        autocomplete="new-password"
                                        aria-describedby="newPasswordConfirmationError"
                                        data-password-confirmation
                                        data-error-required="Please confirm your new password."
                                        data-error-mismatch="Password confirmation does not match."
                                        <?= $passwordConfirmationHasError
                                            ? 'aria-invalid="true"'
                                            : '' ?>
                                        required>

                                    <button
                                        type="button"
                                        class="password-field__toggle"
                                        data-password-toggle="newPasswordConfirmation"
                                        aria-label="Show password confirmation"
                                        aria-controls="newPasswordConfirmation"
                                        aria-pressed="false">
                                        <span
                                            class="mdi
                                                mdi-eye-off-outline"
                                            aria-hidden="true"></span>
                                    </button>
                                </div>

                                <div
                                    id="newPasswordConfirmationError"
                                    class="invalid-feedback d-block"
                                    data-field-error="password_confirmation">
                                    <?= esc(
                                        $passwordConfirmationError
                                    ) ?>
                                </div>
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
                                        Reset Password
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
                                            Updating Password...
                                        </span>
                                    </span>
                                </button>
                            </div>
                        </form>
                        <div class="mt-4 text-center">
                            <p class="mb-0 text-muted">
                                Remember your password?

                                <a
                                    href="<?= esc(
                                            $cancelAction,
                                            'attr'
                                        ) ?>"
                                    class="fw-semibold
                                        text-primary
                                        text-decoration-underline">
                                    Back to Login
                                </a>
                            </p>
                        </div>

                    </div>
                </div>

                <div class="text-center mt-4">
                    <p class="text-muted mb-0 fs-13">
                        <i
                            class="ri-shield-check-line
                                text-primary me-1"
                            aria-hidden="true"></i>

                        Your new password will be securely encrypted.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
$this->endSection();
