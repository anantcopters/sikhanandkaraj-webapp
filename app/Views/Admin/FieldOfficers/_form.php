<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $formInput
 * @var array<string, string> $validationErrors
 * @var list<array<string, mixed>> $countries
 * @var list<array<string, mixed>> $states
 * @var list<array<string, mixed>> $cities
 * @var bool $isEdit
 * @var string $formAction
 */

$resolvedFormInput =
    is_array($formInput ?? null)
    ? $formInput
    : [];

$errors =
    is_array($validationErrors ?? null)
    ? $validationErrors
    : [];

$resolvedCountries =
    is_array($countries ?? null)
    ? $countries
    : [];

$resolvedStates =
    is_array($states ?? null)
    ? $states
    : [];

$resolvedCities =
    is_array($cities ?? null)
    ? $cities
    : [];

$editing =
    ($isEdit ?? false) === true;

$selectedCountry =
    (string) (
        $resolvedFormInput['country_id']
        ?? ''
    );

$selectedState =
    (string) (
        $resolvedFormInput['state_id']
        ?? ''
    );

$selectedCity =
    (string) (
        $resolvedFormInput['city_id']
        ?? ''
    );
?>

<form
    action="<?= esc(
                $formAction,
                'attr'
            ) ?>"
    method="post"
    data-validate
    data-submit-loader
    data-field-officer-form
    data-cities-url="<?= esc(
                            site_url(
                                'admin/field-officers/master/cities'
                            ),
                            'attr'
                        ) ?>"
    novalidate>

    <?= csrf_field() ?>

    <div class="row g-3">

        <?php if (!$editing): ?>

            <div class="col-12 col-md-6">

                <label
                    for="fieldOfficerName"
                    class="form-label">

                    Name
                    <span class="text-danger">*</span>

                </label>

                <input
                    type="text"
                    id="fieldOfficerName"
                    name="full_name"
                    class="form-control <?= isset(
                                            $errors['full_name']
                                        )
                                            ? 'is-invalid'
                                            : '' ?>"
                    value="<?= esc(
                                $resolvedFormInput['full_name'] ?? '',
                                'attr'
                            ) ?>"
                    minlength="2"
                    maxlength="150"
                    pattern="[\p{L}\p{M} .'\-]+"
                    autocomplete="name"
                    required>

                <div class="invalid-feedback">
                    <?= esc(
                        $errors['full_name']
                            ?? 'Enter the Field Officer name.'
                    ) ?>
                </div>

            </div>

            <div class="col-12 col-md-6">

                <label
                    for="fieldOfficerMobile"
                    class="form-label">

                    Mobile Number
                    <span class="text-danger">*</span>

                </label>

                <div class="input-group">

                    <span class="input-group-text">
                        +91
                    </span>

                    <input
                        type="tel"
                        id="fieldOfficerMobile"
                        name="mobile_number"
                        class="form-control <?= isset(
                                                $errors['mobile_number']
                                            )
                                                ? 'is-invalid'
                                                : '' ?>"
                        value="<?= esc(
                                    $resolvedFormInput['mobile_number'] ?? '',
                                    'attr'
                                ) ?>"
                        inputmode="numeric"
                        autocomplete="tel"
                        minlength="10"
                        maxlength="10"
                        pattern="[6-9][0-9]{9}"
                        required>

                </div>

                <?php if (
                    isset(
                        $errors['mobile_number']
                    )
                ): ?>

                    <div
                        class="text-danger
                            fs-13
                            mt-1">

                        <?= esc(
                            $errors['mobile_number']
                        ) ?>

                    </div>

                <?php else: ?>

                    <div class="form-text color-pink">
                        Enter a unique 10-digit Indian
                        mobile number.
                    </div>

                <?php endif; ?>

            </div>
        <?php endif; ?>
        <div class="col-12 col-md-6">

            <label
                for="fieldOfficerAadhaar"
                class="form-label">

                Aadhaar Number
                <span class="text-danger">*</span>

            </label>

            <input
                type="text"
                id="fieldOfficerAadhaar"
                name="aadhaar_number"
                class="form-control <?= isset(
                                        $errors['aadhaar_number']
                                    )
                                        ? 'is-invalid'
                                        : '' ?>"
                value="<?= esc(
                            $resolvedFormInput['aadhaar_number'] ?? '',
                            'attr'
                        ) ?>"
                inputmode="numeric"
                autocomplete="off"
                minlength="12"
                maxlength="12"
                pattern="[0-9]{12}"
                placeholder="12-digit Aadhaar number"
                required>

            <div class="invalid-feedback">
                <?= esc(
                    $errors['aadhaar_number']
                        ?? 'Enter a valid 12-digit Aadhaar number.'
                ) ?>
            </div>

            <div class="form-text color-pink">
                Enter exactly 12 digits without
                spaces or hyphens.
            </div>

        </div>

        <div class="col-12 col-md-6">

            <label
                for="fieldOfficerPan"
                class="form-label">

                PAN Number
                <span class="text-danger">*</span>

            </label>

            <input
                type="text"
                id="fieldOfficerPan"
                name="pan_number"
                class="form-control <?= isset(
                                        $errors['pan_number']
                                    )
                                        ? 'is-invalid'
                                        : '' ?>"
                value="<?= esc(
                            strtoupper(
                                (string) (
                                    $resolvedFormInput['pan_number'] ?? ''
                                )
                            ),
                            'attr'
                        ) ?>"
                autocomplete="off"
                minlength="10"
                maxlength="10"
                pattern="[A-Za-z]{5}[0-9]{4}[A-Za-z]"
                placeholder="ABCDE1234F"
                required>

            <div class="invalid-feedback">
                <?= esc(
                    $errors['pan_number']
                        ?? 'Enter a valid PAN number.'
                ) ?>
            </div>

            <div class="form-text color-pink">
                Example: ABCDE1234F
            </div>

        </div>



        <div class="col-12 col-md-4">

            <label
                for="fieldOfficerCountry"
                class="form-label">

                Country
                <span class="text-danger">*</span>

            </label>

            <select
                id="fieldOfficerCountry"
                name="country_id"
                class="form-select <?= isset(
                                        $errors['country_id']
                                    )
                                        ? 'is-invalid'
                                        : '' ?>"
                data-choice
                data-choice-search="false"
                required>

                <option value="">
                    Select Country
                </option>

                <?php foreach (
                    $resolvedCountries
                    as $country
                ): ?>

                    <?php
                    $countryId =
                        (string) (
                            $country['id']
                            ?? ''
                        );
                    ?>

                    <option
                        value="<?= esc(
                                    $countryId,
                                    'attr'
                                ) ?>"
                        <?= $selectedCountry
                            === $countryId
                            ? 'selected'
                            : '' ?>>

                        <?= esc(
                            (string) (
                                $country['name']
                                ?? ''
                            )
                        ) ?>

                    </option>

                <?php endforeach; ?>

            </select>

            <div class="invalid-feedback">
                <?= esc(
                    $errors['country_id']
                        ?? 'Select a country.'
                ) ?>
            </div>

        </div>

        <div class="col-12 col-md-4">

            <label
                for="fieldOfficerState"
                class="form-label">

                State
                <span class="text-danger">*</span>

            </label>

            <select
                id="fieldOfficerState"
                name="state_id"
                class="form-select <?= isset(
                                        $errors['state_id']
                                    )
                                        ? 'is-invalid'
                                        : '' ?>"
                data-choice
                data-choice-search="true"
                data-state-select
                required>

                <option value="">
                    Select State
                </option>

                <?php foreach (
                    $resolvedStates
                    as $state
                ): ?>

                    <?php
                    $stateId =
                        (string) (
                            $state['id']
                            ?? ''
                        );
                    ?>

                    <option
                        value="<?= esc(
                                    $stateId,
                                    'attr'
                                ) ?>"
                        <?= $selectedState
                            === $stateId
                            ? 'selected'
                            : '' ?>>

                        <?= esc(
                            (string) (
                                $state['name']
                                ?? ''
                            )
                        ) ?>

                    </option>

                <?php endforeach; ?>

            </select>

            <div class="invalid-feedback">
                <?= esc(
                    $errors['state_id']
                        ?? 'Select a state.'
                ) ?>
            </div>

        </div>

        <div class="col-12 col-md-4">

            <label
                for="fieldOfficerCity"
                class="form-label">

                City
                <span class="text-danger">*</span>

            </label>

            <select
                id="fieldOfficerCity"
                name="city_id"
                class="form-select <?= isset(
                                        $errors['city_id']
                                    )
                                        ? 'is-invalid'
                                        : '' ?>"
                data-choice
                data-choice-search="true"
                data-city-select
                required>

                <option value="">
                    Select City
                </option>

                <?php foreach (
                    $resolvedCities
                    as $city
                ): ?>

                    <?php
                    $cityId =
                        (string) (
                            $city['id']
                            ?? ''
                        );
                    ?>

                    <option
                        value="<?= esc(
                                    $cityId,
                                    'attr'
                                ) ?>"
                        <?= $selectedCity
                            === $cityId
                            ? 'selected'
                            : '' ?>>

                        <?= esc(
                            (string) (
                                $city['name']
                                ?? ''
                            )
                        ) ?>

                    </option>

                <?php endforeach; ?>

            </select>

            <div class="invalid-feedback">
                <?= esc(
                    $errors['city_id']
                        ?? 'Select a city.'
                ) ?>
            </div>

        </div>

        <div class="col-12 col-md-6">

            <label
                for="fieldOfficerAddress"
                class="form-label">

                Address

                <span class="text-muted">
                    (Optional)
                </span>

            </label>

            <textarea
                id="fieldOfficerAddress"
                name="address"
                class="form-control <?= isset(
                                        $errors['address']
                                    )
                                        ? 'is-invalid'
                                        : '' ?>"
                rows="3"
                maxlength="500"><?= esc(
                                    $resolvedFormInput['address'] ?? ''
                                ) ?></textarea>

            <div class="invalid-feedback">
                <?= esc(
                    $errors['address']
                        ?? 'Enter a valid address.'
                ) ?>
            </div>

            <div class="form-text color-pink">
                Maximum 500 characters.
            </div>

        </div>

        <div class="col-12 col-md-6">

            <label
                for="fieldOfficerUpi"
                class="form-label">

                UPI ID

                <span class="text-muted">
                    (Optional)
                </span>

            </label>

            <input
                type="text"
                id="fieldOfficerUpi"
                name="upi_id"
                class="form-control <?= isset(
                                        $errors['upi_id']
                                    )
                                        ? 'is-invalid'
                                        : '' ?>"
                value="<?= esc(
                            $resolvedFormInput['upi_id'] ?? '',
                            'attr'
                        ) ?>"
                maxlength="150"
                pattern="[A-Za-z0-9._\-]{2,256}@[A-Za-z][A-Za-z0-9.\-]{1,63}"
                autocomplete="off"
                placeholder="name@bank">

            <div class="invalid-feedback">
                <?= esc(
                    $errors['upi_id']
                        ?? 'Enter a valid UPI ID.'
                ) ?>
            </div>

            <div class="form-text color-pink">

                <?php if ($editing): ?>

                    UPI ID must be unique. A valid UPI ID keeps
                    the Field Officer active. Removing it will
                    make the account inactive.

                <?php else: ?>

                    UPI ID must be unique. Providing a valid
                    UPI ID will create this Field Officer in
                    active status.

                <?php endif; ?>

            </div>

        </div>

    </div>

    <div
        class="mt-4
        d-flex
        justify-content-end">

        <button
            type="submit"
            class="btn
            btn-primary
            fs-16
            fw-semibold
            px-4"
            data-submit-button style="background-color: var(--sak-primary); border-color: var(--sak-primary)">

            <span data-submit-idle>
                <?= $editing
                    ? 'Save Changes'
                    : 'Add Field Officer' ?>
            </span>

            <span
                class="d-none"
                data-submit-loading>

                <span
                    class="spinner-border
                    spinner-border-sm"
                    aria-hidden="true">
                </span>

                <span class="ms-1">
                    <?= $editing
                        ? 'Saving changes...'
                        : 'Creating Field Officer...' ?>
                </span>

            </span>

        </button>

    </div>

</form>