<?php

declare(strict_types=1);

/**
 * @var string|null $pageTitle
 */
$resolvedPageTitle = isset($pageTitle) && is_string($pageTitle)
    ? $pageTitle
    : 'Sikh Anand Karaj';

/**
 * Resolve server-side validation errors once.
 *
 * The controller stores these errors in flashdata when redirecting back
 * after an unsuccessful form submission.
 *
 * @var array<string, string> $validationErrors
 */
$sessionValidationErrors = session('validationErrors');

$validationErrors = is_array($sessionValidationErrors)
    ? $sessionValidationErrors
    : [];

/**
 * Resolve the form-level alert once.
 *
 * @var array<string, string>|null $formAlert
 */
$sessionFormAlert = session('formAlert');

$formAlert = is_array($sessionFormAlert)
    ? $sessionFormAlert
    : null;

$this->extend('Layouts/Main');
$this->section('content');
?>
<section class="home-registration-section">
    <div class="container">
        <div class="row align-items-center home-registration-section__row">

            <!--
                Reserved for promotional content or artwork.
                This column is intentionally blank for now.
            -->
            <div
                class="col-lg-7 d-none d-lg-block"
                aria-hidden="true"></div>

            <div class="col-12 col-lg-5">
                <div class="registration-card">

                    <div class="registration-card__header">
                        <h1 class="fs-22 fw-normal mb-0 text-white text-center lh-base">
                            Create a Matrimony Profile
                        </h1>
                    </div>

                    <div class="registration-card__body">

                        <h2 class="fs-20 fw-semibold text-center lh-base mt-0 mb-3">
                            Find your perfect match
                        </h2>
                        <?= view('Components/Alerts/FormAlert', [
                            'alert' => $formAlert,
                        ]) ?>
                        <form
                            action="<?= route_to('web.register.create') ?>"
                            method="post"
                            class="registration-form"
                            data-validate
                            novalidate
                            autocomplete="off">
                            <?= csrf_field() ?>

                            <div class="mb-3">
                                <label
                                    for="profileCreatedFor"
                                    class="visually-hidden">
                                    Profile created for
                                </label>

                                <select
                                    id="profileCreatedFor"
                                    name="profile_created_for"
                                    class="form-select <?= isset(
                                                            $validationErrors['profile_created_for']
                                                        ) ? 'is-invalid' : '' ?>"
                                    data-choices data-choices-search-false
                                    required>

                                    <option value="">Profile created for</option>
                                    <option value="self">Self</option>
                                    <option value="son">Son</option>
                                    <option value="daughter">Daughter</option>
                                    <option value="brother">Brother</option>
                                    <option value="sister">Sister</option>
                                </select>
                            </div>
                            <div
                                id="genderContainer"
                                class="mb-3 d-none">
                                <label class="form-label fw-semibold">
                                    Gender
                                </label>

                                <div class="d-flex gap-4">
                                    <div class="form-check">
                                        <input
                                            class="form-check-input"
                                            type="radio"
                                            name="gender"
                                            id="genderMale"
                                            value="M">

                                        <label
                                            class="form-check-label"
                                            for="genderMale">
                                            Male
                                        </label>
                                    </div>

                                    <div class="form-check">
                                        <input
                                            class="form-check-input"
                                            type="radio"
                                            name="gender"
                                            id="genderFemale"
                                            value="F">

                                        <label
                                            class="form-check-label"
                                            for="genderFemale">
                                            Female
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label
                                    for="fullName"
                                    class="visually-hidden">
                                    Full name
                                </label>
                                <?php
                                $fullNameHasError = isset($validationErrors['full_name']);
                                ?>
                                <input
                                    type="text"
                                    id="fullName"
                                    name="full_name"
                                    value="<?= esc(old('full_name'), 'attr') ?>"
                                    class="form-control <?= $fullNameHasError
                                                            ? 'is-invalid'
                                                            : '' ?>"
                                    <?= $fullNameHasError
                                        ? 'aria-invalid="true"'
                                        : '' ?>
                                    aria-describedby="fullNameError"
                                    placeholder="Enter full name"
                                    minlength="2"
                                    maxlength="100"
                                    autocomplete="full_name"
                                    data-error-required="Please enter full name."
                                    data-error-email="Please enter a valid full name."
                                    required>
                                <?= view('Components/Forms/FieldError', [
                                    'field' => 'full_name',
                                    'errors' => $validationErrors,
                                ]) ?>
                            </div>
                            <div class="mb-3">
                                <label
                                    for="email"
                                    class="visually-hidden">
                                    Email
                                </label>
                                <?php
                                $emailHasError = isset($validationErrors['email']);
                                ?>

                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    value="<?= esc(old('email'), 'attr') ?>"
                                    class="form-control <?= $emailHasError
                                                            ? 'is-invalid'
                                                            : '' ?>"
                                    <?= $emailHasError
                                        ? 'aria-invalid="true"'
                                        : '' ?>
                                    aria-describedby="emailError"
                                    placeholder="Enter email"
                                    maxlength="128"
                                    autocomplete="email"
                                    data-error-required="Please enter the email address."
                                    data-error-email="Please enter a valid email address."
                                    required>
                                <?= view('Components/Forms/FieldError', [
                                    'field' => 'email',
                                    'errors' => $validationErrors,
                                ]) ?>
                                <div id="emailInput" class="form-text color-pink">Verification link will be sent on this email.</div>
                            </div>

                            <div class="mb-3">
                                <div class="row g-3">
                                    <div class="col-md-3 col-sm-3">
                                        <label
                                            for="countryCode"
                                            class="visually-hidden">
                                            Country code
                                        </label>

                                        <select
                                            id="countryCode"
                                            name="country_code"
                                            class="form-select registration-country-code"
                                            aria-label="Country code"
                                            required>
                                            <option value="+91" selected>+91</option>
                                        </select>
                                    </div>
                                    <div class="col-md-9 col-sm-9">
                                        <label
                                            for="mobileNumber"
                                            class="visually-hidden">
                                            Mobile number
                                        </label>

                                        <input
                                            type="text"
                                            id="mobileNumber"
                                            name="mobile_number"
                                            class="form-control"
                                            placeholder="Enter Mobile Number"
                                            inputmode="numeric"
                                            pattern="[6-9][0-9]{9}"
                                            minlength="10"
                                            maxlength="10"
                                            autocomplete="tel"
                                            required>
                                        <div id="passwordInput" class="form-text color-pink">OTP will be sent to this number.</div>
                                    </div>

                                </div>
                            </div>

                            <button
                                type="submit"
                                class="btn registration-form__submit fs-16 fw-semibold text-uppercase">
                                <span>Register Free</span>

                                <span
                                    class="mdi mdi-arrow-right fs-20"
                                    aria-hidden="true"></span>
                            </button>

                            <p class="registration-form__terms fs-12 text-muted mb-0">
                                By clicking Register Free, I agree to the
                                <a href="<?= site_url('terms-and-conditions') ?>">
                                    T&amp;C
                                </a>
                                and
                                <a href="<?= site_url('privacy-policy') ?>">
                                    Privacy Policy
                                </a>.
                            </p>
                        </form>
                    </div>

                </div>
            </div>

        </div>
    </div>
</section>
<?php $this->endSection(); ?>