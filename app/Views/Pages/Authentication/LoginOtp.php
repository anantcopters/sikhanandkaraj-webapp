<?php

declare(strict_types=1);

/**
 * @var array<string, string>|null $validationErrors
 * @var array<string, string>|null $formAlert
 * @var string|null                $mobileNumber
 */

$errors = is_array(
    $validationErrors ?? null
)
    ? $validationErrors
    : [];

$alert = is_array(
    $formAlert ?? null
)
    ? $formAlert
    : null;

$resolvedMobile = trim(
    (string) (
        $mobileNumber
        ?? ''
    )
);

$mobileError = trim(
    (string) (
        $errors['mobile_number']
        ?? ''
    )
);

$mobileClass = $mobileError !== ''
    ? 'is-invalid'
    : '';

$this->extend('Layouts/Main');
$this->section('content');
?>

<section class="py-4">
    <div class="container">
        <div
            class="row justify-content-center align-items-center auth-content-height">

            <div
                class="col-12 col-sm-10 col-md-8 col-lg-5 col-xl-5">

                <div class="card border-0 shadow-lg mb-0">
                    <div class="card-body p-4 p-md-5 pt-md-4">
                        <div class="text-center mb-4">
                            <h1 class="fs-24 fw-semibold mb-2">
                                Login with OTP
                            </h1>

                            <p class="text-muted mb-0">
                                Enter your verified mobile number
                            </p>
                        </div>

                        <?= view(
                            'Components/Alerts/FormAlert',
                            [
                                'alert' => $alert,
                            ]
                        ) ?>

                        <form
                            id="otpLoginForm"
                            action="<?= route_to(
                                        'web.login.otp.send'
                                    ) ?>"
                            method="post"
                            data-validate
                            data-submit-loader
                            novalidate
                            autocomplete="on">

                            <?= csrf_field() ?>

                            <div class="mb-3">
                                <label
                                    for="otpLoginMobile"
                                    class="form-label">

                                    Mobile Number
                                </label>

                                <div class="input-group has-validation">
                                    <span class="input-group-text">
                                        +91
                                    </span>

                                    <input
                                        type="tel"
                                        id="otpLoginMobile"
                                        name="mobile_number"
                                        value="<?= esc(
                                                    $resolvedMobile,
                                                    'attr'
                                                ) ?>"
                                        class="form-control <?= esc(
                                                                $mobileClass,
                                                                'attr'
                                                            ) ?>"
                                        placeholder="Enter mobile number"
                                        inputmode="numeric"
                                        pattern="[6-9][0-9]{9}"
                                        minlength="10"
                                        maxlength="10"
                                        autocomplete="tel-national"
                                        aria-describedby="otpLoginMobileError"
                                        data-error-required="Please enter your registered mobile number."
                                        data-error-pattern="Please enter a valid 10-digit Indian mobile number."
                                        data-error-minlength="Please enter a 10-digit mobile number."
                                        data-error-maxlength="Please enter a 10-digit mobile number."
                                        required>

                                    <div
                                        id="otpLoginMobileError"
                                        class="invalid-feedback"
                                        data-validation-error="mobile_number">

                                        <?= esc($mobileError) ?>
                                    </div>
                                </div>

                                <div class="form-text">
                                    OTP login is available only for a
                                    verified mobile number linked to an
                                    active account.
                                </div>
                            </div>

                            <div class="mt-5">
                                <button
                                    type="submit"
                                    class="btn registration-form__submit fs-16 fw-semibold text-uppercase"
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
                            <a
                                href="<?= route_to(
                                            'web.login'
                                        ) ?>"
                                class="fw-semibold text-primary text-decoration-underline">

                                Choose another login method
                            </a>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <p class="text-muted mb-0 fs-13">
                        <i
                            class="ri-shield-check-line text-primary me-1"
                            aria-hidden="true"></i>

                        Your OTP is securely protected.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<?= $this->endSection() ?>