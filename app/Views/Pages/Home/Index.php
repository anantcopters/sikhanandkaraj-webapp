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
                            data-submit-loader
                            novalidate
                            autocomplete="off">

                            <?= csrf_field() ?>

                            <?php
                            $profileTypeHasError = isset(
                                $validationErrors['profile_created_for']
                            );

                            $genderHasError = isset(
                                $validationErrors['gender']
                            );

                            $fullNameHasError = isset(
                                $validationErrors['full_name']
                            );

                            $emailHasError = isset(
                                $validationErrors['email']
                            );

                            $countryCodeHasError = isset(
                                $validationErrors['country_code']
                            );

                            $mobileHasError = isset(
                                $validationErrors['mobile_number']
                            );

                            $passwordHasError = isset(
                                $validationErrors['password']
                            );
                            ?>

                            <!-- Profile created for -->
                            <div class="mb-3">
                                <label
                                    for="profileCreatedFor"
                                    class="visually-hidden">
                                    Profile created for
                                </label>

                                <select
                                    id="profileCreatedFor"
                                    name="profile_created_for"
                                    class="form-select <?= $profileTypeHasError
                                                            ? 'is-invalid'
                                                            : '' ?>"
                                    <?= $profileTypeHasError
                                        ? 'aria-invalid="true"'
                                        : '' ?>
                                    aria-describedby="profileCreatedForError"
                                    data-choice
                                    data-choice-search-false
                                    required>

                                    <option
                                        value=""
                                        <?= old('profile_created_for') === null
                                            || old('profile_created_for') === ''
                                            ? 'selected'
                                            : '' ?>>
                                        Profile created for
                                    </option>

                                    <option
                                        value="self"
                                        <?= old('profile_created_for') === 'self'
                                            ? 'selected'
                                            : '' ?>>
                                        Self
                                    </option>

                                    <option
                                        value="son"
                                        <?= old('profile_created_for') === 'son'
                                            ? 'selected'
                                            : '' ?>>
                                        Son
                                    </option>

                                    <option
                                        value="daughter"
                                        <?= old('profile_created_for') === 'daughter'
                                            ? 'selected'
                                            : '' ?>>
                                        Daughter
                                    </option>

                                    <option
                                        value="brother"
                                        <?= old('profile_created_for') === 'brother'
                                            ? 'selected'
                                            : '' ?>>
                                        Brother
                                    </option>

                                    <option
                                        value="sister"
                                        <?= old('profile_created_for') === 'sister'
                                            ? 'selected'
                                            : '' ?>>
                                        Sister
                                    </option>
                                </select>

                                <?= view('Components/Forms/FieldError', [
                                    'field' => 'profile_created_for',
                                    'errorId' => 'profileCreatedForError',
                                    'errors' => $validationErrors,
                                ]) ?>
                            </div>

                            <!-- Gender -->
                            <div
                                id="genderContainer"
                                class="mb-3 <?= old('profile_created_for') === 'self'
                                                ? ''
                                                : 'd-none' ?>"
                                data-validation-group="gender">

                                <fieldset>
                                    <legend class="visually-hidden">
                                        Gender
                                    </legend>

                                    <div class="row align-items-center g-2">

                                        <!-- Gender label -->
                                        <div class="col-auto">
                                            <span class="form-label fs-13 fw-medium mb-0 pe-3">
                                                Gender
                                            </span>
                                        </div>

                                        <!-- Gender options -->
                                        <div class="col">
                                            <div class="d-flex align-items-center gap-4 flex-nowrap">

                                                <!-- Male -->
                                                <div class="form-check form-check-inline mb-0 me-0">
                                                    <input
                                                        type="radio"
                                                        name="gender"
                                                        id="genderMale"
                                                        value="M"
                                                        class="form-check-input <?= $genderHasError
                                                                                    ? 'is-invalid'
                                                                                    : '' ?>"
                                                        <?= old('gender') === 'M'
                                                            ? 'checked'
                                                            : '' ?>
                                                        <?= $genderHasError
                                                            ? 'aria-invalid="true"'
                                                            : '' ?>
                                                        aria-describedby="genderError"
                                                        data-error-required="Please select gender.">

                                                    <label
                                                        class="form-check-label"
                                                        for="genderMale">
                                                        Male
                                                    </label>
                                                </div>

                                                <!-- Female -->
                                                <div class="form-check form-check-inline mb-0 me-0">
                                                    <input
                                                        type="radio"
                                                        name="gender"
                                                        id="genderFemale"
                                                        value="F"
                                                        class="form-check-input <?= $genderHasError
                                                                                    ? 'is-invalid'
                                                                                    : '' ?>"
                                                        <?= old('gender') === 'F'
                                                            ? 'checked'
                                                            : '' ?>
                                                        <?= $genderHasError
                                                            ? 'aria-invalid="true"'
                                                            : '' ?>
                                                        aria-describedby="genderError"
                                                        data-error-required="Please select gender.">

                                                    <label
                                                        class="form-check-label"
                                                        for="genderFemale">
                                                        Female
                                                    </label>
                                                </div>

                                            </div>
                                        </div>
                                    </div>

                                    <?= view('Components/Forms/FieldError', [
                                        'field' => 'gender',
                                        'errorId' => 'genderError',
                                        'errors' => $validationErrors,
                                    ]) ?>
                                </fieldset>
                            </div>

                            <!-- Full name -->
                            <div class="mb-3">
                                <label
                                    for="fullName"
                                    class="visually-hidden">
                                    Full name
                                </label>

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
                                    autocomplete="name"
                                    data-error-required="Please enter full name."
                                    data-error-minlength="Full name must contain at least 2 characters."
                                    data-error-maxlength="Full name cannot exceed 100 characters."
                                    required>

                                <?= view('Components/Forms/FieldError', [
                                    'field' => 'full_name',
                                    'errorId' => 'fullNameError',
                                    'errors' => $validationErrors,
                                ]) ?>
                            </div>

                            <!-- Email -->
                            <div class="mb-3">
                                <label
                                    for="email"
                                    class="visually-hidden">
                                    Email address
                                </label>

                                <input
                                    type="text"
                                    id="email"
                                    name="email"
                                    value="<?= esc(old('email'), 'attr') ?>"
                                    class="form-control <?= $emailHasError
                                                            ? 'is-invalid'
                                                            : '' ?>"
                                    <?= $emailHasError
                                        ? 'aria-invalid="true"'
                                        : '' ?>
                                    aria-describedby="emailError emailHelp"
                                    placeholder="Enter email"
                                    maxlength="128"
                                    autocomplete="email"
                                    data-error-required="Please enter email address."
                                    data-error-email="Please enter a valid email address."
                                    data-error-maxlength="Email address is too long."
                                    required>

                                <?= view('Components/Forms/FieldError', [
                                    'field' => 'email',
                                    'errorId' => 'emailError',
                                    'errors' => $validationErrors,
                                ]) ?>

                                <div
                                    id="emailHelp"
                                    class="form-text color-pink">
                                    Verification link will be sent to this email.
                                </div>
                            </div>

                            <!-- Country code and mobile number -->
                            <div class="mb-3">
                                <div class="row g-3">

                                    <div class="col-3">
                                        <label
                                            for="countryCode"
                                            class="visually-hidden">
                                            Country code
                                        </label>

                                        <select
                                            id="countryCode"
                                            name="country_code"
                                            class="form-select registration-country-code
                        <?= $countryCodeHasError
                            ? 'is-invalid'
                            : '' ?>"
                                            <?= $countryCodeHasError
                                                ? 'aria-invalid="true"'
                                                : '' ?>
                                            aria-describedby="countryCodeError"
                                            aria-label="Country code"
                                            data-choice
                                            data-choice-search-false
                                            required>

                                            <option
                                                value="+91"
                                                <?= old('country_code', '+91') === '+91'
                                                    ? 'selected'
                                                    : '' ?>>
                                                +91
                                            </option>
                                        </select>

                                        <?= view('Components/Forms/FieldError', [
                                            'field' => 'country_code',
                                            'errorId' => 'countryCodeError',
                                            'errors' => $validationErrors,
                                        ]) ?>
                                    </div>

                                    <div class="col-9">
                                        <label
                                            for="mobileNumber"
                                            class="visually-hidden">
                                            Mobile number
                                        </label>

                                        <input
                                            type="text"
                                            id="mobileNumber"
                                            name="mobile_number"
                                            value="<?= esc(old('mobile_number'), 'attr') ?>"
                                            class="form-control <?= $mobileHasError
                                                                    ? 'is-invalid'
                                                                    : '' ?>"
                                            <?= $mobileHasError
                                                ? 'aria-invalid="true"'
                                                : '' ?>
                                            aria-describedby="mobileNumberError mobileNumberHelp"
                                            placeholder="Enter Mobile Number"
                                            inputmode="numeric"
                                            pattern="[6-9][0-9]{9}"
                                            minlength="10"
                                            maxlength="10"
                                            autocomplete="tel"
                                            data-error-required="Please enter mobile number."
                                            data-error-pattern="Please enter a valid 10-digit Indian mobile number."
                                            data-error-minlength="Please enter a 10-digit mobile number."
                                            data-error-maxlength="Please enter a 10-digit mobile number."
                                            required>

                                        <?= view('Components/Forms/FieldError', [
                                            'field' => 'mobile_number',
                                            'errorId' => 'mobileNumberError',
                                            'errors' => $validationErrors,
                                        ]) ?>

                                        <div
                                            id="mobileNumberHelp"
                                            class="form-text color-pink">
                                            OTP will be sent to this number.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Password -->
                            <div class="mb-3">
                                <label
                                    for="password"
                                    class="visually-hidden">
                                    Password
                                </label>

                                <div class="password-field">
                                    <input
                                        type="password"
                                        id="password"
                                        name="password"
                                        class="form-control password-field__input
                <?= $passwordHasError
                    ? 'is-invalid'
                    : '' ?>"
                                        <?= $passwordHasError
                                            ? 'aria-invalid="true"'
                                            : '' ?>
                                        aria-describedby="passwordError passwordHelp"
                                        placeholder="Create password"
                                        minlength="10"
                                        maxlength="128"
                                        autocomplete="new-password"
                                        data-error-required="Please enter a password."
                                        data-error-minlength="The password must contain at least 10 characters."
                                        data-error-maxlength="The password cannot exceed 128 characters."
                                        data-error-pattern="The password must contain at least one uppercase letter, one lowercase letter, one number and one special character."
                                        pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{10,128}"
                                        required>

                                    <button
                                        type="button"
                                        class="password-field__toggle"
                                        data-password-toggle="password"
                                        aria-label="Show password"
                                        aria-controls="password"
                                        aria-pressed="false">

                                        <span
                                            class="mdi mdi-eye-off-outline"
                                            aria-hidden="true">
                                        </span>
                                    </button>
                                </div>

                                <?= view('Components/Forms/FieldError', [
                                    'field' => 'password',
                                    'errorId' => 'passwordError',
                                    'errors' => $validationErrors,
                                ]) ?>

                                <div
                                    id="passwordHelp"
                                    class="form-text color-pink">
                                    Use at least 10 characters with one uppercase letter,
                                    one lowercase letter, one number and one special character.
                                </div>
                            </div>

                            <button
                                type="submit"
                                class="btn registration-form__submit fs-16 fw-semibold text-uppercase" data-submit-button>

                                <span
                                    class="registration-submit__idle"
                                    data-submit-idle>

                                    <span>Register Free</span>

                                    <span
                                        class="mdi mdi-arrow-right fs-20"
                                        aria-hidden="true">
                                    </span>
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

                                    <span>Creating Profile...</span>
                                </span>
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