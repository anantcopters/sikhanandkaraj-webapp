<?php

declare(strict_types=1);

/**
 * @var array<string, string>      $validationErrors
 * @var array<string, string>|null $formAlert
 */

$errors = is_array($validationErrors ?? null)
    ? $validationErrors
    : [];

$alert = is_array($formAlert ?? null)
    ? $formAlert
    : null;

$this->extend('Admin/Layouts/Main');
$this->section('content');
/*
 * This is intentionally a single-page pre-launch profile form.
 * Existing Bootstrap and app.css utility/component classes are reused.
 * No prelaunch-specific CSS classes are introduced.
 */
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
            <div class="card shadow-sm">
                <div class="card-body p-3 p-md-4">
                    <div class="mb-4">
                        <h1 class="h3 mb-2">
                            Create Pre-launch Profile
                        </h1>

                        <p class="text-muted mb-0">
                            Complete all required details and upload
                            exactly three recent photographs.
                        </p>
                    </div>

                    <?php if ($alert !== null): ?>
                        <div
                            class="alert alert-<?= esc(
                                                    $alert['type'] ?? 'danger'
                                                ) ?>"
                            role="alert">
                            <strong>
                                <?= esc($alert['title'] ?? '') ?>
                            </strong>

                            <div>
                                <?= esc($alert['message'] ?? '') ?>
                            </div>
                        </div>
                    <?php endif ?>

                    <form
                        action="<?= route_to(
                                    'prelaunch.profile.store'
                                ) ?>"
                        method="post"
                        enctype="multipart/form-data"
                        novalidate
                        data-submit-loader
                        id="prelaunch-profile-form">
                        <?= csrf_field() ?>

                        <!--
                            Section 1: identity and contact details.
                            Every field marked required is validated in
                            the browser and again on the server.
                        -->
                        <fieldset class="mb-4">
                            <legend class="h5 mb-3">
                                Member details
                            </legend>

                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label
                                        for="profile_created_for"
                                        class="form-label">
                                        Profile created for
                                    </label>

                                    <select
                                        class="form-select <?= isset(
                                                                $errors['profile_created_for']
                                                            ) ? 'is-invalid' : '' ?>"
                                        id="profile_created_for"
                                        name="profile_created_for"
                                        required>
                                        <option value="">
                                            Select
                                        </option>

                                        <?php foreach (
                                            [
                                                'SELF' => 'Self',
                                                'SON' => 'Son',
                                                'DAUGHTER' => 'Daughter',
                                                'BROTHER' => 'Brother',
                                                'SISTER' => 'Sister',
                                                'RELATIVE' => 'Relative',
                                                'FRIEND' => 'Friend',
                                            ] as $value => $label
                                        ): ?>
                                            <option
                                                value="<?= esc($value) ?>"
                                                <?= old(
                                                    'profile_created_for'
                                                ) === $value
                                                    ? 'selected'
                                                    : '' ?>>
                                                <?= esc($label) ?>
                                            </option>
                                        <?php endforeach ?>
                                    </select>

                                    <div class="invalid-feedback">
                                        <?= esc(
                                            $errors['profile_created_for']
                                                ?? 'Please select an option.'
                                        ) ?>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label
                                        for="gender"
                                        class="form-label">
                                        Gender
                                    </label>

                                    <select
                                        class="form-select <?= isset(
                                                                $errors['gender']
                                                            ) ? 'is-invalid' : '' ?>"
                                        id="gender"
                                        name="gender"
                                        required>
                                        <option value="">
                                            Select gender
                                        </option>
                                        <option
                                            value="MALE"
                                            <?= old('gender') === 'MALE'
                                                ? 'selected'
                                                : '' ?>>
                                            Male
                                        </option>
                                        <option
                                            value="FEMALE"
                                            <?= old('gender') === 'FEMALE'
                                                ? 'selected'
                                                : '' ?>>
                                            Female
                                        </option>
                                    </select>

                                    <div class="invalid-feedback">
                                        <?= esc(
                                            $errors['gender']
                                                ?? 'Please select gender.'
                                        ) ?>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label
                                        for="full_name"
                                        class="form-label">
                                        Full name
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control <?= isset(
                                                                $errors['full_name']
                                                            ) ? 'is-invalid' : '' ?>"
                                        id="full_name"
                                        name="full_name"
                                        value="<?= esc(
                                                    old('full_name')
                                                ) ?>"
                                        maxlength="100"
                                        required>

                                    <div class="invalid-feedback">
                                        <?= esc(
                                            $errors['full_name']
                                                ?? 'Please enter the full name.'
                                        ) ?>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label
                                        for="date_of_birth"
                                        class="form-label">
                                        Date of birth
                                    </label>

                                    <input
                                        type="date"
                                        class="form-control <?= isset(
                                                                $errors['date_of_birth']
                                                            ) ? 'is-invalid' : '' ?>"
                                        id="date_of_birth"
                                        name="date_of_birth"
                                        value="<?= esc(
                                                    old('date_of_birth')
                                                ) ?>"
                                        required>

                                    <div class="invalid-feedback">
                                        <?= esc(
                                            $errors['date_of_birth']
                                                ?? 'Please select date of birth.'
                                        ) ?>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label
                                        for="email"
                                        class="form-label">
                                        Email
                                    </label>

                                    <input
                                        type="email"
                                        class="form-control <?= isset(
                                                                $errors['email']
                                                            ) ? 'is-invalid' : '' ?>"
                                        id="email"
                                        name="email"
                                        value="<?= esc(old('email')) ?>"
                                        maxlength="190"
                                        required>

                                    <div class="invalid-feedback">
                                        <?= esc(
                                            $errors['email']
                                                ?? 'Please enter a valid email.'
                                        ) ?>
                                    </div>
                                </div>

                                <div class="col-4 col-md-2">
                                    <label
                                        for="country_code"
                                        class="form-label">
                                        Code
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control <?= isset(
                                                                $errors['country_code']
                                                            ) ? 'is-invalid' : '' ?>"
                                        id="country_code"
                                        name="country_code"
                                        value="<?= esc(
                                                    old(
                                                        'country_code',
                                                        '+91'
                                                    )
                                                ) ?>"
                                        maxlength="5"
                                        required>
                                </div>

                                <div class="col-8 col-md-4">
                                    <label
                                        for="mobile_number"
                                        class="form-label">
                                        Mobile number
                                    </label>

                                    <input
                                        type="tel"
                                        inputmode="numeric"
                                        class="form-control <?= isset(
                                                                $errors['mobile_number']
                                                            ) ? 'is-invalid' : '' ?>"
                                        id="mobile_number"
                                        name="mobile_number"
                                        value="<?= esc(
                                                    old('mobile_number')
                                                ) ?>"
                                        pattern="[0-9]{10,15}"
                                        maxlength="15"
                                        required>

                                    <div class="invalid-feedback">
                                        <?= esc(
                                            $errors['mobile_number']
                                                ?? 'Please enter a valid mobile number.'
                                        ) ?>
                                    </div>
                                </div>
                            </div>
                        </fieldset>

                        <!--
                            Section 2: existing master-data selections.
                            Render the same select helper/partial currently
                            used by Basic Details to avoid duplicated markup.
                        -->
                        <?= $this->include(
                            'Prelaunch/Profile/Partials/BasicDetails'
                        ) ?>

                        <?= $this->include(
                            'Prelaunch/Profile/Partials/EducationProfession'
                        ) ?>

                        <?= $this->include(
                            'Prelaunch/Profile/Partials/FamilyDetails'
                        ) ?>

                        <!--
                            Section 3: three secure photo uploads.
                            Images are not publicly accessible after upload.
                        -->
                        <fieldset class="mb-4">
                            <legend class="h5 mb-3">
                                Photographs
                            </legend>

                            <div class="row g-3">
                                <?php foreach ([1, 2, 3] as $number): ?>
                                    <?php $field = 'photo_' . $number ?>

                                    <div class="col-12 col-md-4">
                                        <label
                                            for="<?= esc($field) ?>"
                                            class="form-label">
                                            Photo <?= $number ?>
                                        </label>

                                        <input
                                            type="file"
                                            class="form-control <?= isset(
                                                                    $errors[$field]
                                                                ) ? 'is-invalid' : '' ?>"
                                            id="<?= esc($field) ?>"
                                            name="<?= esc($field) ?>"
                                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                            required>

                                        <div class="form-text">
                                            JPG, PNG or WebP, maximum
                                            5 MB and minimum 400 × 400.
                                        </div>

                                        <div class="invalid-feedback">
                                            <?= esc(
                                                $errors[$field]
                                                    ?? 'Please select a valid photograph.'
                                            ) ?>
                                        </div>
                                    </div>
                                <?php endforeach ?>
                            </div>
                        </fieldset>

                        <!--
                            Section 4: Field Officer verification.
                            Saving remains disabled until the server confirms
                            an active Field Officer for the entered code.
                        -->
                        <fieldset class="mb-4">
                            <legend class="h5 mb-3">
                                Field Officer verification
                            </legend>

                            <div class="row g-3 align-items-end">
                                <div class="col-12 col-md-8">
                                    <label
                                        for="field_officer_code"
                                        class="form-label">
                                        Field Officer code
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control text-uppercase <?= isset(
                                                                                $errors['field_officer_code']
                                                                            ) ? 'is-invalid' : '' ?>"
                                        id="field_officer_code"
                                        name="field_officer_code"
                                        value="<?= esc(
                                                    old('field_officer_code')
                                                ) ?>"
                                        maxlength="20"
                                        autocomplete="off"
                                        required>

                                    <input
                                        type="hidden"
                                        id="verified_field_officer_id"
                                        name="verified_field_officer_id"
                                        value="">

                                    <div class="invalid-feedback">
                                        <?= esc(
                                            $errors['field_officer_code']
                                                ?? 'Please verify the Field Officer.'
                                        ) ?>
                                    </div>
                                </div>

                                <div class="col-12 col-md-4">
                                    <button
                                        type="button"
                                        class="btn btn-outline-primary w-100"
                                        id="verify-field-officer"
                                        data-url="<?= route_to(
                                                        'prelaunch.field-officer.verify'
                                                    ) ?>">
                                        Verify Field Officer
                                    </button>
                                </div>

                                <div class="col-12">
                                    <div
                                        class="alert alert-secondary d-none mb-0"
                                        id="field-officer-result"
                                        role="status"
                                        aria-live="polite"></div>
                                </div>
                            </div>
                        </fieldset>

                        <div class="form-check mb-4">
                            <input
                                type="checkbox"
                                class="form-check-input <?= isset(
                                                            $errors['consent']
                                                        ) ? 'is-invalid' : '' ?>"
                                id="consent"
                                name="consent"
                                value="1"
                                required>

                            <label
                                class="form-check-label"
                                for="consent">
                                The member has consented to the
                                collection and review of these details.
                            </label>

                            <div class="invalid-feedback">
                                <?= esc(
                                    $errors['consent']
                                        ?? 'Consent is required.'
                                ) ?>
                            </div>
                        </div>

                        <button
                            type="submit"
                            class="registration-form__submit"
                            id="save-prelaunch-profile"
                            disabled>
                            <span data-submit-label>
                                Save Draft Profile
                            </span>

                            <span
                                class="registration-submit__loading d-none"
                                data-submit-loading>
                                <span
                                    class="spinner-border spinner-border-sm"
                                    aria-hidden="true"></span>

                                Saving...
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>