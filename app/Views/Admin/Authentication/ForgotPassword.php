<?php

declare(strict_types=1);

/**
 * @var string|null                $pageTitle
 * @var array<string, string>|null $validationErrors
 * @var array<string, string>|null $formAlert
 * @var string|null                $adminLoginUrl
 */

$pageTitle =
    isset($pageTitle)
    ? trim((string) $pageTitle)
    : 'Forgot Administrator Password';

$validationErrors =
    isset($validationErrors)
    && is_array($validationErrors)
    ? $validationErrors
    : [];

$formAlert =
    isset($formAlert)
    && is_array($formAlert)
    ? $formAlert
    : null;

$adminLoginUrl =
    isset($adminLoginUrl)
    ? (string) $adminLoginUrl
    : route_to('admin.login');

$identifier =
    trim(
        (string) old('identifier')
    );

$identifierError =
    isset(
        $validationErrors['identifier']
    )
    ? (string) $validationErrors['identifier']
    : '';

$this->extend(
    'Admin/Layouts/Main'
);

$this->section(
    'content'
);
?>

<div class="auth-page-wrapper min-vh-100">

    <div
        class="auth-page-content
            d-flex
            align-items-center
            py-5">

        <div class="container">

            <div class="row justify-content-center">

                <div
                    class="col-md-8
                        col-lg-6
                        col-xl-5">

                    <div
                        class="card
                            border
                            border-danger
                            border-opacity-25
                            mb-0">

                        <div class="card-body p-4">

                            <div class="text-center mt-2">

                                <div
                                    class="avatar-md
                                        mx-auto
                                        mb-3">

                                    <div
                                        class="avatar-title
                                            rounded-circle
                                            bg-primary-subtle
                                            text-primary
                                            fs-24">

                                        <i
                                            class="ri-lock-password-line"
                                            aria-hidden="true">
                                        </i>

                                    </div>

                                </div>

                                <h1 class="fs-20">
                                    Forgot Administrator Password
                                </h1>

                                <p class="text-muted mb-0">
                                    Enter your registered email address
                                    or mobile number.
                                </p>

                            </div>

                            <div class="p-2 mt-4">

                                <?= view(
                                    'Components/Alerts/FormAlert',
                                    [
                                        'alert' =>
                                        $formAlert,
                                    ]
                                ) ?>

                                <form
                                    action="<?= esc(
                                                route_to(
                                                    'admin.forgot-password.send-otp'
                                                ),
                                                'attr'
                                            ) ?>"
                                    method="post"
                                    data-submit-loader
                                    novalidate>

                                    <?= csrf_field() ?>

                                    <div class="mb-4">

                                        <label
                                            class="form-label"
                                            for="adminForgotIdentifier">

                                            Email or Mobile Number

                                        </label>

                                        <input
                                            type="text"
                                            class="form-control
                                                <?= $identifierError !== ''
                                                    ? 'is-invalid'
                                                    : '' ?>"
                                            id="adminForgotIdentifier"
                                            name="identifier"
                                            value="<?= esc(
                                                        $identifier,
                                                        'attr'
                                                    ) ?>"
                                            placeholder="Email or mobile number"
                                            maxlength="254"
                                            autocomplete="username"
                                            required>

                                        <div
                                            class="invalid-feedback">

                                            <?= esc(
                                                $identifierError
                                            ) ?>

                                        </div>

                                    </div>

                                    <div
                                        class="alert
                                            alert-light
                                            border
                                            mb-4">

                                        <div
                                            class="d-flex
                                                align-items-start">

                                            <i
                                                class="ri-information-line
                                                    fs-20
                                                    text-primary
                                                    me-2"
                                                aria-hidden="true">
                                            </i>

                                            <p
                                                class="mb-0
                                                    fs-13
                                                    text-muted">

                                                The OTP will be sent only
                                                to your verified primary
                                                mobile number.

                                            </p>

                                        </div>

                                    </div>

                                    <button
                                        type="submit"
                                        class="btn
                                            registration-form__submit
                                            fs-16
                                            fw-semibold"
                                        data-submit-button>

                                        <span
                                            data-submit-idle>

                                            Send OTP

                                        </span>

                                        <span
                                            class="registration-submit__loading
                                                d-none"
                                            data-submit-loading>

                                            <span
                                                class="spinner-border
                                                    spinner-border-sm">
                                            </span>

                                            Sending OTP...

                                        </span>

                                    </button>

                                </form>

                                <div
                                    class="mt-4
                                        text-center">

                                    <p
                                        class="mb-0
                                            text-muted">

                                        Remember your password?

                                        <a
                                            href="<?= esc(
                                                        $adminLoginUrl,
                                                        'attr'
                                                    ) ?>"
                                            class="fw-semibold
                                                text-primary
                                                text-decoration-underline">

                                            Back to Administrator Login

                                        </a>

                                    </p>

                                </div>

                            </div>

                        </div>

                    </div>

                    <p
                        class="text-center
                            text-muted
                            mt-4
                            mb-0">

                        <i
                            class="ri-shield-check-line
                                text-danger
                                me-1"
                            aria-hidden="true">
                        </i>

                        Administrator password reset is securely protected.

                    </p>

                </div>

            </div>

        </div>

    </div>

</div>

<?php

$this->endSection();
