<?php

declare(strict_types=1);

/**
 * Shared SAK Volunteer form.
 *
 * Parent view/controller supplied variables.
 *
 * @var array<string, mixed>|null $formInput
 * @var array<string, string>|null $validationErrors
 * @var list<array<string, mixed>>|null $countries
 * @var list<array<string, mixed>>|null $states
 * @var list<array<string, mixed>>|null $cities
 * @var bool|null $isEdit
 * @var bool|null $isPublicRegistration
 * @var string|null $formAction
 * @var string|null $citiesBaseUrl
 * @var string|null $submitLabel
 * @var string|null $submitLoadingLabel
 * @var bool|null $showCaptcha
 * @var string|null $captchaChallenge
 * @var string|null $captchaError
 */

$resolvedFormInput =
    is_array(
        $formInput
            ?? null
    )
    ? $formInput
    : [];

$errors =
    is_array(
        $validationErrors
            ?? null
    )
    ? $validationErrors
    : [];

$editing =
    ($isEdit ?? false)
    === true;

$isPublicRegistration =
    ($isPublicRegistration ?? false)
    === true;

$showCaptcha =
    ($showCaptcha ?? false)
    === true;

$resolvedFormAction = trim(
    (string) (
        $formAction
        ?? ''
    )
);

$citiesBaseUrl = trim(
    (string) (
        $citiesBaseUrl
        ?? site_url(
            'admin/field-officers/master/cities'
        )
    )
);

$submitLabel = trim(
    (string) (
        $submitLabel
        ?? (
            $editing
            ? 'Save Changes'
            : 'Add SAK Volunteer'
        )
    )
);

$submitLoadingLabel = trim(
    (string) (
        $submitLoadingLabel
        ?? (
            $editing
            ? 'Saving changes...'
            : 'Creating SAK Volunteer...'
        )
    )
);

$captchaChallenge = trim(
    (string) (
        $captchaChallenge
        ?? ''
    )
);

$captchaError = trim(
    (string) (
        $captchaError
        ?? ''
    )
);

$fullName = trim(
    (string) (
        $resolvedFormInput['full_name']
        ?? ''
    )
);

$mobileNumber = trim(
    (string) (
        $resolvedFormInput['mobile_number']
        ?? ''
    )
);

$aadhaarNumber = trim(
    (string) (
        $resolvedFormInput['aadhaar_number']
        ?? ''
    )
);

$panNumber = strtoupper(
    trim(
        (string) (
            $resolvedFormInput['pan_number']
            ?? ''
        )
    )
);

$address = trim(
    (string) (
        $resolvedFormInput['address']
        ?? ''
    )
);

$upiId = trim(
    (string) (
        $resolvedFormInput['upi_id']
        ?? ''
    )
);

$selectedCountry = trim(
    (string) (
        $resolvedFormInput['country_id']
        ?? ''
    )
);

$selectedState = trim(
    (string) (
        $resolvedFormInput['state_id']
        ?? ''
    )
);

$selectedCity = trim(
    (string) (
        $resolvedFormInput['city_id']
        ?? ''
    )
);

$fullNameError = trim(
    (string) (
        $errors['full_name']
        ?? ''
    )
);

$mobileNumberError = trim(
    (string) (
        $errors['mobile_number']
        ?? ''
    )
);

$aadhaarError = trim(
    (string) (
        $errors['aadhaar_number']
        ?? ''
    )
);

$panError = trim(
    (string) (
        $errors['pan_number']
        ?? ''
    )
);

$countryError = trim(
    (string) (
        $errors['country_id']
        ?? ''
    )
);

$stateError = trim(
    (string) (
        $errors['state_id']
        ?? ''
    )
);

$cityError = trim(
    (string) (
        $errors['city_id']
        ?? ''
    )
);

$addressError = trim(
    (string) (
        $errors['address']
        ?? ''
    )
);

$upiError = trim(
    (string) (
        $errors['upi_id']
        ?? ''
    )
);

$fullNameClass =
    $fullNameError !== ''
    ? 'is-invalid'
    : '';

$mobileNumberClass =
    $mobileNumberError !== ''
    ? 'is-invalid'
    : '';

$aadhaarClass =
    $aadhaarError !== ''
    ? 'is-invalid'
    : '';

$panClass =
    $panError !== ''
    ? 'is-invalid'
    : '';

$countryClass =
    $countryError !== ''
    ? 'is-invalid'
    : '';

$stateClass =
    $stateError !== ''
    ? 'is-invalid'
    : '';

$cityClass =
    $cityError !== ''
    ? 'is-invalid'
    : '';

$addressClass =
    $addressError !== ''
    ? 'is-invalid'
    : '';

$upiClass =
    $upiError !== ''
    ? 'is-invalid'
    : '';

$captchaClass =
    $captchaError !== ''
    ? 'is-invalid'
    : '';

$countryOptions = [];

foreach (
    is_array($countries ?? null)
        ? $countries
        : []
    as $country
) {
    if (!is_array($country)) {
        continue;
    }

    $value = trim(
        (string) (
            $country['id']
            ?? ''
        )
    );

    $label = trim(
        (string) (
            $country['name']
            ?? ''
        )
    );

    if (
        $value === ''
        || $label === ''
    ) {
        continue;
    }

    $countryOptions[] = [
        'value' =>
        $value,

        'label' =>
        $label,

        'selected' =>
        $selectedCountry
            === $value,
    ];
}

$stateOptions = [];

foreach (
    is_array($states ?? null)
        ? $states
        : []
    as $state
) {
    if (!is_array($state)) {
        continue;
    }

    $value = trim(
        (string) (
            $state['id']
            ?? ''
        )
    );

    $label = trim(
        (string) (
            $state['name']
            ?? ''
        )
    );

    if (
        $value === ''
        || $label === ''
    ) {
        continue;
    }

    $stateOptions[] = [
        'value' =>
        $value,

        'label' =>
        $label,

        'selected' =>
        $selectedState
            === $value,
    ];
}

$cityOptions = [];

foreach (
    is_array($cities ?? null)
        ? $cities
        : []
    as $city
) {
    if (!is_array($city)) {
        continue;
    }

    $value = trim(
        (string) (
            $city['id']
            ?? ''
        )
    );

    $label = trim(
        (string) (
            $city['name']
            ?? ''
        )
    );

    if (
        $value === ''
        || $label === ''
    ) {
        continue;
    }

    $cityOptions[] = [
        'value' =>
        $value,

        'label' =>
        $label,

        'selected' =>
        $selectedCity
            === $value,
    ];
}

if ($isPublicRegistration) {
    $upiHelpText =
        'UPI ID must be unique. Your registration '
        . 'will remain inactive until reviewed. '
        . 'If approved and a valid UPI ID is present, '
        . 'your SAK Volunteer account will be activated.';
} elseif ($editing) {
    $upiHelpText =
        'UPI ID must be unique. A valid UPI ID keeps '
        . 'the SAK Volunteer active. Removing it will '
        . 'make the account inactive.';
} else {
    $upiHelpText =
        'UPI ID must be unique. Providing a valid '
        . 'UPI ID will create this SAK Volunteer in '
        . 'active status.';
}
?>

<form
    action="<?= esc(
                $resolvedFormAction,
                'attr'
            ) ?>"
    method="post"
    data-validate
    data-submit-loader
    data-field-officer-form
    data-cities-url="<?= esc(
                            $citiesBaseUrl,
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
                    class="form-control <?= esc(
                                            $fullNameClass,
                                            'attr'
                                        ) ?>"
                    value="<?= esc(
                                $fullName,
                                'attr'
                            ) ?>"
                    minlength="2"
                    maxlength="150"
                    pattern="[\p{L}\p{M} .'\-]+"
                    autocomplete="name"
                    required>

                <div class="invalid-feedback">

                    <?= esc(
                        $fullNameError !== ''
                            ? $fullNameError
                            : 'Enter the SAK Volunteer name.'
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
                        class="form-control <?= esc(
                                                $mobileNumberClass,
                                                'attr'
                                            ) ?>"
                        value="<?= esc(
                                    $mobileNumber,
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
                    $mobileNumberError !== ''
                ): ?>

                    <div
                        class="text-danger
                        fs-13
                        mt-1">

                        <?= esc(
                            $mobileNumberError
                        ) ?>

                    </div>

                <?php else: ?>

                    <div
                        class="form-text
                        color-pink">

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
                class="form-control <?= esc(
                                        $aadhaarClass,
                                        'attr'
                                    ) ?>"
                value="<?= esc(
                            $aadhaarNumber,
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
                    $aadhaarError !== ''
                        ? $aadhaarError
                        : 'Enter a valid 12-digit Aadhaar number.'
                ) ?>

            </div>

            <div
                class="form-text
                color-pink">

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
                class="form-control <?= esc(
                                        $panClass,
                                        'attr'
                                    ) ?>"
                value="<?= esc(
                            $panNumber,
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
                    $panError !== ''
                        ? $panError
                        : 'Enter a valid PAN number.'
                ) ?>

            </div>

            <div
                class="form-text
                color-pink">

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
                class="form-select <?= esc(
                                        $countryClass,
                                        'attr'
                                    ) ?>"
                data-choice
                data-choice-search="false"
                required>

                <option value="">
                    Select Country
                </option>

                <?php foreach (
                    $countryOptions
                    as $option
                ): ?>

                    <option
                        value="<?= esc(
                                    $option['value'],
                                    'attr'
                                ) ?>"
                        <?= $option['selected']
                            ? 'selected'
                            : '' ?>>

                        <?= esc(
                            $option['label']
                        ) ?>

                    </option>

                <?php endforeach; ?>

            </select>

            <div class="invalid-feedback">

                <?= esc(
                    $countryError !== ''
                        ? $countryError
                        : 'Select a country.'
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
                class="form-select <?= esc(
                                        $stateClass,
                                        'attr'
                                    ) ?>"
                data-choice
                data-choice-search="true"
                data-state-select
                required>

                <option value="">
                    Select State
                </option>

                <?php foreach (
                    $stateOptions
                    as $option
                ): ?>

                    <option
                        value="<?= esc(
                                    $option['value'],
                                    'attr'
                                ) ?>"
                        <?= $option['selected']
                            ? 'selected'
                            : '' ?>>

                        <?= esc(
                            $option['label']
                        ) ?>

                    </option>

                <?php endforeach; ?>

            </select>

            <div class="invalid-feedback">

                <?= esc(
                    $stateError !== ''
                        ? $stateError
                        : 'Select a state.'
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
                class="form-select <?= esc(
                                        $cityClass,
                                        'attr'
                                    ) ?>"
                data-choice
                data-choice-search="true"
                data-city-select
                required>

                <option value="">
                    Select City
                </option>

                <?php foreach (
                    $cityOptions
                    as $option
                ): ?>

                    <option
                        value="<?= esc(
                                    $option['value'],
                                    'attr'
                                ) ?>"
                        <?= $option['selected']
                            ? 'selected'
                            : '' ?>>

                        <?= esc(
                            $option['label']
                        ) ?>

                    </option>

                <?php endforeach; ?>

            </select>

            <div class="invalid-feedback">

                <?= esc(
                    $cityError !== ''
                        ? $cityError
                        : 'Select a city.'
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
                class="form-control <?= esc(
                                        $addressClass,
                                        'attr'
                                    ) ?>"
                rows="3"
                maxlength="500"><?= esc(
                                    $address
                                ) ?></textarea>

            <div class="invalid-feedback">

                <?= esc(
                    $addressError !== ''
                        ? $addressError
                        : 'Enter a valid address.'
                ) ?>

            </div>

            <div
                class="form-text
                color-pink">

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
                class="form-control <?= esc(
                                        $upiClass,
                                        'attr'
                                    ) ?>"
                value="<?= esc(
                            $upiId,
                            'attr'
                        ) ?>"
                maxlength="150"
                pattern="[A-Za-z0-9._\-]{2,256}@[A-Za-z][A-Za-z0-9.\-]{1,63}"
                autocomplete="off"
                placeholder="name@bank">

            <div class="invalid-feedback">

                <?= esc(
                    $upiError !== ''
                        ? $upiError
                        : 'Enter a valid UPI ID.'
                ) ?>

            </div>

            <div
                class="form-text
                color-pink">

                <?= esc(
                    $upiHelpText
                ) ?>

            </div>

        </div>

    </div>

    <?php if ($showCaptcha): ?>

        <div class="row mt-4">

            <div
                class="col-12
            col-md-6
            ms-md-auto">

                <label
                    for="fieldOfficerRegistrationCaptcha"
                    class="form-label">

                    Security Verification

                    <span class="text-danger">*</span>

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
                    justify-content-between
                    gap-2">

                        <span
                            class="text-muted
                        fs-13">

                            Solve

                        </span>

                        <span class="fw-bold">

                            <?= esc(
                                $captchaChallenge
                            ) ?> = ?

                        </span>

                    </div>

                </div>

                <input
                    type="text"
                    id="fieldOfficerRegistrationCaptcha"
                    name="captcha_answer"
                    class="form-control <?= esc(
                                            $captchaClass,
                                            'attr'
                                        ) ?>"
                    value=""
                    inputmode="numeric"
                    maxlength="2"
                    pattern="[0-9]{1,2}"
                    autocomplete="off"
                    placeholder="Enter answer"
                    required>

                <div class="invalid-feedback">

                    <?= esc(
                        $captchaError !== ''
                            ? $captchaError
                            : 'Enter the security verification answer.'
                    ) ?>

                </div>

                <div
                    class="form-text
                color-pink">

                    Expires after 5 minutes.

                </div>

            </div>

        </div>

    <?php endif; ?>

    <div
        class="mt-4
    d-flex
    justify-content-end">

        <button
            type="submit"
            class="btn
        registration-form__submit
        w-auto
        px-3
        py-2
        fs-14
        fw-medium
        text-uppercase"
            data-submit-button>

            <span
                class="registration-form__idle
            d-inline-flex
            align-items-center
            gap-2"
                data-submit-idle>

                <i
                    class="ri-save-line
                fs-18"
                    aria-hidden="true">
                </i>

                <?= esc(
                    $submitLabel
                ) ?>

            </span>

            <span
                class="registration-form__loading
            d-none"
                data-submit-loading>

                <span
                    class="spinner-border
                spinner-border-sm"
                    role="status"
                    aria-hidden="true">
                </span>

                <span class="ms-1">

                    <?= esc(
                        $submitLoadingLabel
                    ) ?>

                </span>

            </span>

        </button>

    </div>
</form>