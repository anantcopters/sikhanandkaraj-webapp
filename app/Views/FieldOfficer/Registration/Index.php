<?php

declare(strict_types=1);

/**
 * Public SAK Volunteer registration.
 *
 * Controller supplied variables.
 *
 * @var string|null $pageTitle
 * @var array<string, mixed>|null $formInput
 * @var array<string, string>|null $validationErrors
 * @var array<string, mixed>|null $formAlert
 * @var list<array<string, mixed>>|null $countries
 * @var list<array<string, mixed>>|null $states
 * @var list<array<string, mixed>>|null $cities
 * @var string|null $captchaChallenge
 * @var list<string>|null $pageScripts
 */

$pageTitle = trim(
    (string) (
        $pageTitle
        ?? 'Register as SAK Volunteer'
    )
);

$formInput =
    is_array(
        $formInput
            ?? null
    )
    ? $formInput
    : [];

$validationErrors =
    is_array(
        $validationErrors
            ?? null
    )
    ? $validationErrors
    : [];

$formAlert =
    is_array(
        $formAlert
            ?? null
    )
    ? $formAlert
    : null;

$countries =
    is_array(
        $countries
            ?? null
    )
    ? $countries
    : [];

$states =
    is_array(
        $states
            ?? null
    )
    ? $states
    : [];

$cities =
    is_array(
        $cities
            ?? null
    )
    ? $cities
    : [];

$captchaChallenge = trim(
    (string) (
        $captchaChallenge
        ?? ''
    )
);

$captchaError = trim(
    (string) (
        $validationErrors['captcha_answer']
        ?? ''
    )
);

$formAction =
    route_to(
        'field-officer.register.store'
    );

$citiesUrl =
    site_url(
        'field-officer/register/master/cities'
    );

$loginUrl =
    route_to(
        'field-officer.login'
    );

$this->extend(
    'FieldOfficer/Layouts/Main'
);

$this->section('content');
?>

<div class="py-5">

    <div class="container">

        <div
            class="row
            justify-content-center">

            <div
                class="col-12
                col-lg-10
                col-xl-9">

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
                    border-opacity-25">

                    <div
                        class="card-body
                        p-3
                        p-md-4">

                        <div
                            class="d-flex
                            align-items-start
                            justify-content-between
                            flex-wrap
                            gap-3
                            mb-4">

                            <div>

                                <h1
                                    class="fs-20
                                    mb-1">

                                    Register as SAK Volunteer

                                </h1>

                                <p
                                    class="text-muted
                                    mb-0">

                                    Submit your details for
                                    verification and approval.

                                </p>

                            </div>

                            <a
                                href="<?= esc(
                                            $loginUrl,
                                            'attr'
                                        ) ?>"
                                class="btn
                                btn-soft-secondary">

                                <i
                                    class="ri-login-box-line
                                    me-1"
                                    aria-hidden="true">
                                </i>

                                Back to Login

                            </a>

                        </div>

                        <?= view(
                            'Admin/FieldOfficers/_form',
                            [
                                'formInput' =>
                                $formInput,

                                'validationErrors' =>
                                $validationErrors,

                                'countries' =>
                                $countries,

                                'states' =>
                                $states,

                                'cities' =>
                                $cities,

                                'isEdit' =>
                                false,

                                'isPublicRegistration' =>
                                true,

                                'formAction' =>
                                $formAction,

                                'citiesBaseUrl' =>
                                $citiesUrl,

                                'submitLabel' =>
                                'Submit Registration',

                                'submitLoadingLabel' =>
                                'Submitting...',

                                'showCaptcha' =>
                                true,

                                'captchaChallenge' =>
                                $captchaChallenge,

                                'captchaError' =>
                                $captchaError,
                            ]
                        ) ?>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

<?php $this->endSection(); ?>