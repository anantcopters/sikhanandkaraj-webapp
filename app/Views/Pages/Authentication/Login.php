<?php

declare(strict_types=1);

/**
 * @var string|null                $pageTitle
 * @var array<string, string>|null $validationErrors
 * @var array<string, string>|null $formAlert
 * @var string|null                $loginIdentifier
 */

$pageTitle = isset($pageTitle)
    ? trim((string) $pageTitle)
    : 'Login';

$validationErrors = isset($validationErrors)
    && is_array($validationErrors)
    ? $validationErrors
    : [];

$formAlert = isset($formAlert)
    && is_array($formAlert)
    ? $formAlert
    : null;

$loginIdentifier = isset($loginIdentifier)
    ? trim((string) $loginIdentifier)
    : '';

$identifierHasError = isset(
    $validationErrors['identifier']
);

$passwordHasError = isset(
    $validationErrors['password']
);

$this->extend('Layouts/Main');
$this->section('content');
?>

<section class="py-4">
    <div class="container">
        <div
            class="row justify-content-center align-items-center auth-content-height">

            <div class="col-12 col-sm-10 col-md-8 col-lg-5 col-xl-5">

                <div class="card border-0 shadow-lg mb-0">
                    <div class="card-body p-4 p-md-5 pt-md-4">

                        <div class="text-center mb-4">
                            <h1 class="fs-24 fw-semibold mb-2">
                                Welcome Back
                            </h1>

                            <p class="text-muted mb-0">
                                Login to continue to SikhAnandKaraj
                            </p>
                        </div>

                        <?= view(
                            'Components/Alerts/FormAlert',
                            [
                                'alert' => $formAlert,
                            ]
                        ) ?>

                        <form
                            id="loginForm"
                            action="<?= route_to(
                                        'web.login.submit'
                                    ) ?>"
                            method="post"
                            data-validate
                            data-submit-loader
                            novalidate
                            autocomplete="on">

                            <?= csrf_field() ?>

                            <!-- Email or mobile -->
                            <div class="mb-3">
                                <label
                                    for="loginIdentifier"
                                    class="form-label">
                                    Email or Mobile Number
                                </label>

                                <input
                                    type="text"
                                    id="loginIdentifier"
                                    name="identifier"
                                    value="<?= esc(
                                                $loginIdentifier,
                                                'attr'
                                            ) ?>"
                                    class="form-control
                                        <?= $identifierHasError
                                            ? 'is-invalid'
                                            : '' ?>"
                                    placeholder="Enter email or mobile number"
                                    maxlength="254"
                                    autocomplete="username"
                                    data-error-required="Please enter your email address or mobile number."
                                    data-error-pattern="Enter a valid email address or 10-digit Indian mobile number."
                                    aria-describedby="loginIdentifierError"
                                    <?= $identifierHasError
                                        ? 'aria-invalid="true"'
                                        : '' ?>
                                    required>

                                <div
                                    id="loginIdentifierError"
                                    class="invalid-feedback"
                                    data-validation-error="identifier">
                                    <?= esc(
                                        $validationErrors['identifier'] ?? ''
                                    ) ?>
                                </div>
                            </div>

                            <!-- Password -->
                            <div class="mb-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <label
                                        for="loginPassword"
                                        class="form-label">
                                        Password
                                    </label>

                                    <a
                                        href="<?= route_to(
                                                    'web.forgot-password'
                                                ) ?>"
                                        class="color-pink fs-13">
                                        Forgot password?
                                    </a>
                                </div>

                                <div class="password-field">
                                    <input
                                        type="password"
                                        id="loginPassword"
                                        name="password"
                                        class="form-control password-field__input
                <?= $passwordHasError
                    ? 'is-invalid'
                    : '' ?>"
                                        <?= $passwordHasError
                                            ? 'aria-invalid="true"'
                                            : '' ?>
                                        aria-describedby="loginPasswordError"
                                        placeholder="Enter password"
                                        maxlength="64"
                                        autocomplete="current-password"
                                        data-error-required="Please enter your password."
                                        data-error-maxlength="Password cannot exceed 64 characters."
                                        required>

                                    <button
                                        type="button"
                                        class="password-field__toggle"
                                        data-password-toggle="loginPassword"
                                        aria-label="Show password"
                                        aria-controls="loginPassword"
                                        aria-pressed="false">

                                        <span
                                            class="mdi mdi-eye-off-outline"
                                            aria-hidden="true">
                                        </span>
                                    </button>
                                </div>

                                <?= view('Components/Forms/FieldError', [
                                    'field' => 'password',
                                    'errorId' => 'loginPasswordError',
                                    'errors' => $validationErrors,
                                ]) ?>
                            </div>

                            <div class="mt-5">
                                <button
                                    type="submit"
                                    class="btn registration-form__submit
            fs-16 fw-semibold text-uppercase"
                                    data-submit-button>

                                    <span
                                        data-submit-idle
                                        aria-hidden="false">
                                        Login
                                    </span>

                                    <span
                                        class="registration-submit__loading d-none"
                                        data-submit-loading
                                        aria-hidden="true">

                                        <span
                                            class="spinner-border spinner-border-sm"
                                            role="status"
                                            aria-hidden="true">
                                        </span>

                                        <span>
                                            Checking credentials...
                                        </span>
                                    </span>
                                </button>
                            </div>
                        </form>

                        <div class="mt-4 text-center">
                            <p class="mb-0 text-muted">
                                Don&apos;t have a profile?

                                <a
                                    href="<?= route_to(
                                                'web.home'
                                            ) ?>"
                                    class="fw-semibold
                                        text-primary
                                        text-decoration-underline">
                                    Register Free
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

                        Your login information is securely protected.
                    </p>
                </div>

            </div>
        </div>
    </div>
</section>

<?php
$this->endSection();
