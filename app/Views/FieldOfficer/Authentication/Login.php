<?php

declare(strict_types=1);

/**
 * Field Officer login.
 *
 * All controller-supplied values are normalized before
 * presentation markup begins.
 */

$pageTitle = trim(
    (string) (
        $pageTitle
        ?? 'Field Officer Login'
    )
);

if ($pageTitle === '') {
    $pageTitle =
        'Field Officer Login';
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

$mobileNumberError = trim(
    (string) (
        $validationErrors['mobile_number']
        ?? ''
    )
);

$mobileHasError =
    $mobileNumberError !== '';

$mobileErrorClass =
    $mobileHasError
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
                                    Field Officer Login
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

                                    <div class="mb-4">

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

                        Restricted Field Officer access

                    </p>

                </div>
            </div>
        </div>
    </div>
</div>

<?php $this->endSection(); ?>