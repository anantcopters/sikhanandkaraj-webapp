<?php

declare(strict_types=1);

/**
 * Local view variables.
 *
 * @var string $pageTitle
 * @var array<string, string> $validationErrors
 * @var array<string, string>|null $formAlert
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

$loginUrl = route_to(
    'web.login'
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
                <div class="card border-0 shadow-lg mb-0">
                    <div class="card-body p-4 p-md-5 pt-md-4">

                        <div class="text-center mb-4">
                            <h1 class="fs-24 fw-semibold mb-2">
                                <?= esc($pageTitle) ?>
                            </h1>

                            <p class="text-muted mb-0">
                                Enter your registered email address or
                                mobile number.
                            </p>
                        </div>

                        <?= view(
                            'Components/Alerts/FormAlert',
                            [
                                'alert' => $formAlert,
                            ]
                        ) ?>

                        <form
                            id="forgotPasswordForm"
                            action="<?= esc(
                                        $formAction,
                                        'attr'
                                    ) ?>"
                            method="post"
                            data-validate
                            data-submit-loader
                            novalidate
                            autocomplete="on">
                            <?= csrf_field() ?>

                            <div class="mb-3">
                                <label
                                    for="forgotPasswordIdentifier"
                                    class="form-label">
                                    Email or Mobile Number
                                </label>

                                <input
                                    type="text"
                                    id="forgotPasswordIdentifier"
                                    name="identifier"
                                    value="<?= esc(
                                                $identifier,
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
                                    aria-describedby="forgotPasswordIdentifierError"
                                    <?= $identifierHasError
                                        ? 'aria-invalid="true"'
                                        : '' ?>
                                    required>

                                <div
                                    id="forgotPasswordIdentifierError"
                                    class="invalid-feedback"
                                    data-validation-error="identifier">
                                    <?= esc($identifierError) ?>
                                </div>
                            </div>

                            <div class="alert alert-light border mb-4">
                                <div class="d-flex align-items-start">
                                    <span
                                        class="mdi mdi-information-outline
                                            fs-20 me-2 text-primary"
                                        aria-hidden="true"></span>

                                    <p class="mb-0 fs-13 text-muted">
                                        The OTP will be sent only to your
                                        verified primary mobile number.
                                    </p>
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
                                        Send OTP
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
                                            Sending OTP...
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
                                                $loginUrl,
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

                        Your password reset request is securely protected.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
$this->endSection();
