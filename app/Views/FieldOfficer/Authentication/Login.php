<?php

declare(strict_types=1);

/**
 * SAK Volunteer login.
 *
 * Controller supplied variables.
 *
 * @var string|null $pageTitle
 * @var array<string, string>|null $validationErrors
 * @var array<string, mixed>|null $formAlert
 * @var string|null $mobileNumber
 * @var string|null $captchaChallenge
 * @var list<string>|null $pageScripts
 */

$registrationUrl =
    route_to(
        'field-officer.register'
    );

$pageTitle = trim(
    (string) (
        $pageTitle
        ?? 'SAK Volunteer Login'
    )
);

if ($pageTitle === '') {
    $pageTitle =
        'SAK Volunteer Login';
}

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

$mobileNumber = trim(
    (string) (
        $mobileNumber
        ?? ''
    )
);

$captchaChallenge = trim(
    (string) (
        $captchaChallenge
        ?? ''
    )
);

$mobileNumberError = trim(
    (string) (
        $validationErrors['mobile_number']
        ?? ''
    )
);

$captchaError = trim(
    (string) (
        $validationErrors['captcha_answer']
        ?? ''
    )
);

$mobileHasError =
    $mobileNumberError !== '';

$captchaHasError =
    $captchaError !== '';

$mobileErrorClass =
    $mobileHasError
    ? 'is-invalid'
    : '';

$captchaErrorClass =
    $captchaHasError
    ? 'is-invalid'
    : '';

$sendOtpUrl =
    route_to(
        'field-officer.login.send-otp'
    );

$this->extend(
    'FieldOfficer/Layouts/Main'
);

$this->section('content');
?>

<div class="auth-page-wrapper min-vh-100">

    <div
        class="auth-page-content
        d-flex
        align-items-center
        py-5">

        <div class="container">

            <div
                class="row
                justify-content-center">

                <div
                    class="col-md-8
                    col-lg-6
                    col-xl-5">

                    <?= view(
                        'Components/Alerts/FormAlert',
                        [
                            'alert' =>
                            $formAlert,
                        ]
                    ) ?>

                    <div
                        class="card
                        border
                        border-danger
                        border-opacity-25
                        mb-0">

                        <div class="card-body p-4">

                            <div
                                class="text-center
                                mt-2">

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
                                            class="ri-user-location-line"
                                            aria-hidden="true">
                                        </i>

                                    </div>

                                </div>

                                <h1 class="fs-20">
                                    SAK Volunteer Login
                                </h1>

                                <p
                                    class="text-muted
                                    mb-0">

                                    Sign in using your
                                    registered mobile number.
                                </p>

                            </div>

                            <div class="p-2 mt-4">

                                <form
                                    action="<?= esc(
                                                $sendOtpUrl,
                                                'attr'
                                            ) ?>"
                                    method="post"
                                    data-validate
                                    data-submit-loader
                                    novalidate>

                                    <?= csrf_field() ?>

                                    <div class="mb-3">

                                        <label
                                            for="foMobileNumber"
                                            class="form-label">

                                            Mobile Number
                                        </label>

                                        <div
                                            class="input-group
                                            has-validation">

                                            <span
                                                class="input-group-text">

                                                +91
                                            </span>

                                            <input
                                                type="tel"
                                                id="foMobileNumber"
                                                name="mobile_number"
                                                class="form-control
                                                <?= esc(
                                                    $mobileErrorClass,
                                                    'attr'
                                                ) ?>"
                                                value="<?= esc(
                                                            $mobileNumber,
                                                            'attr'
                                                        ) ?>"
                                                inputmode="numeric"
                                                maxlength="10"
                                                pattern="[6-9][0-9]{9}"
                                                autocomplete="tel"
                                                placeholder="Enter mobile number"
                                                aria-invalid="<?= $mobileHasError
                                                                    ? 'true'
                                                                    : 'false' ?>"
                                                required>

                                            <div
                                                class="invalid-feedback">

                                                <?= esc(
                                                    $mobileNumberError
                                                ) ?>

                                            </div>

                                        </div>

                                    </div>

                                    <div class="mb-4">

                                        <label
                                            for="foCaptchaAnswer"
                                            class="form-label">

                                            Security Verification
                                        </label>

                                        <div
                                            class="border
                                            rounded
                                            p-2
                                            mb-2
                                            bg-light
                                            border-primary-subtle">

                                            <div
                                                class="d-flex
                                                align-items-center
                                                justify-content-between">

                                                <span
                                                    class="text-muted">

                                                    Solve this question
                                                </span>

                                                <span
                                                    class="fw-bold
                                                    fs-18">

                                                    <?= esc(
                                                        $captchaChallenge
                                                    ) ?> = ?

                                                </span>

                                            </div>

                                        </div>

                                        <input
                                            type="text"
                                            id="foCaptchaAnswer"
                                            name="captcha_answer"
                                            class="form-control
                                            <?= esc(
                                                $captchaErrorClass,
                                                'attr'
                                            ) ?>"
                                            value=""
                                            placeholder="Enter answer"
                                            inputmode="numeric"
                                            autocomplete="off"
                                            maxlength="2"
                                            pattern="[0-9]{1,2}"
                                            aria-invalid="<?= $captchaHasError
                                                                ? 'true'
                                                                : 'false' ?>"
                                            required>

                                        <div
                                            class="invalid-feedback">

                                            <?= esc(
                                                $captchaError
                                            ) ?>

                                        </div>

                                        <div
                                            class="form-text
                                            color-pink">

                                            The security question expires
                                            after 5 minutes.
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
                                                spinner-border-sm"
                                                role="status"
                                                aria-hidden="true">
                                            </span>

                                            Sending OTP...

                                        </span>

                                    </button>

                                </form>
                                <div
                                    class="d-flex
    align-items-center
    gap-2
    mt-3">

                                    <span class="text-muted">
                                        Want to become a SAK Volunteer?
                                    </span>

                                    <a
                                        href="<?= esc(
                                                    $registrationUrl,
                                                    'attr'
                                                ) ?>"
                                        class="btn
        btn-soft-primary
        btn-sm">

                                        Register
                                    </a>

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
                            class="ri-lock-2-line
                            text-danger
                            me-1"
                            aria-hidden="true">
                        </i>

                        Restricted SAK Volunteer access

                    </p>

                </div>
            </div>
        </div>
    </div>
</div>

<?php $this->endSection(); ?>