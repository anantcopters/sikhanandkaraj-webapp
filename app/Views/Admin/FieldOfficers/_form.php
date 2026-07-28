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

$selectedCountry = (string) (
    $formInput['country_id'] ?? ''
);

$selectedState = (string) (
    $formInput['state_id'] ?? ''
);

$selectedCity = (string) (
    $formInput['city_id'] ?? ''
);
?>

<form
    action="<?= esc($formAction, 'attr') ?>"
    method="post"
    data-validate
    data-submit-loader
    data-field-officer-form
    data-states-url="<?= esc(
                            site_url(
                                'admin/field-officers/master/states'
                            ),
                            'attr'
                        ) ?>"
    data-cities-url="<?= esc(
                            site_url(
                                'admin/field-officers/master/cities'
                            ),
                            'attr'
                        ) ?>"
    novalidate>

    <?= csrf_field() ?>

    <div class="row g-3">

        <?php if (!$isEdit): ?>
            <div class="col-12">
                <label
                    for="fieldOfficerName"
                    class="form-label">
                    Name
                </label>

                <input
                    type="text"
                    id="fieldOfficerName"
                    name="full_name"
                    class="form-control
                        <?= isset(
                            $validationErrors['full_name']
                        )
                            ? 'is-invalid'
                            : '' ?>"
                    value="<?= esc(
                                $formInput['full_name'] ?? '',
                                'attr'
                            ) ?>"
                    minlength="2"
                    maxlength="150"
                    pattern="[\p{L}\p{M} .'-]+"
                    required>

                <div class="invalid-feedback">
                    <?= esc(
                        $validationErrors['full_name']
                            ?? 'Enter the Field Officer name.'
                    ) ?>
                </div>
            </div>

            <div class="col-12">
                <label
                    for="fieldOfficerMobile"
                    class="form-label">
                    Mobile Number
                </label>

                <div class="input-group">
                    <span class="input-group-text">
                        +91
                    </span>

                    <input
                        type="tel"
                        id="fieldOfficerMobile"
                        name="mobile_number"
                        class="form-control
                            <?= isset(
                                $validationErrors['mobile_number']
                            )
                                ? 'is-invalid'
                                : '' ?>"
                        value="<?= esc(
                                    $formInput['mobile_number'] ?? '',
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
                        $validationErrors['mobile_number']
                    )
                ): ?>
                    <div class="text-danger fs-13 mt-1">
                        <?= esc(
                            $validationErrors['mobile_number']
                        ) ?>
                    </div>
                <?php else: ?>
                    <div class="form-text">
                        Enter a unique 10-digit Indian mobile number.
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="col-12 col-md-4">
            <label
                for="fieldOfficerCountry"
                class="form-label">
                Country
            </label>

            <select
                id="fieldOfficerCountry"
                name="country_id"
                class="form-select
                    <?= isset(
                        $validationErrors['country_id']
                    )
                        ? 'is-invalid'
                        : '' ?>"
                data-country-select
                required>

                <option value="">
                    Select Country
                </option>

                <?php foreach ($countries as $country): ?>
                    <option
                        value="<?= esc(
                                    (string) $country['id'],
                                    'attr'
                                ) ?>"
                        <?= $selectedCountry
                            === (string) $country['id']
                            ? 'selected'
                            : '' ?>>
                        <?= esc(
                            (string) $country['name']
                        ) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <div class="invalid-feedback">
                <?= esc(
                    $validationErrors['country_id']
                        ?? 'Select a country.'
                ) ?>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <label
                for="fieldOfficerState"
                class="form-label">
                State
            </label>

            <select
                id="fieldOfficerState"
                name="state_id"
                class="form-select
                    <?= isset(
                        $validationErrors['state_id']
                    )
                        ? 'is-invalid'
                        : '' ?>"
                data-state-select
                required>

                <option value="">
                    Select State
                </option>

                <?php foreach ($states as $state): ?>
                    <option
                        value="<?= esc(
                                    (string) $state['id'],
                                    'attr'
                                ) ?>"
                        <?= $selectedState
                            === (string) $state['id']
                            ? 'selected'
                            : '' ?>>
                        <?= esc(
                            (string) $state['name']
                        ) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <div class="invalid-feedback">
                <?= esc(
                    $validationErrors['state_id']
                        ?? 'Select a state.'
                ) ?>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <label
                for="fieldOfficerCity"
                class="form-label">
                City
            </label>

            <select
                id="fieldOfficerCity"
                name="city_id"
                class="form-select
                    <?= isset(
                        $validationErrors['city_id']
                    )
                        ? 'is-invalid'
                        : '' ?>"
                data-city-select
                required>

                <option value="">
                    Select City
                </option>

                <?php foreach ($cities as $city): ?>
                    <option
                        value="<?= esc(
                                    (string) $city['id'],
                                    'attr'
                                ) ?>"
                        <?= $selectedCity
                            === (string) $city['id']
                            ? 'selected'
                            : '' ?>>
                        <?= esc(
                            (string) $city['name']
                        ) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <div class="invalid-feedback">
                <?= esc(
                    $validationErrors['city_id']
                        ?? 'Select a city.'
                ) ?>
            </div>
        </div>

        <div class="col-12">
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
                class="form-control
                    <?= isset(
                        $validationErrors['address']
                    )
                        ? 'is-invalid'
                        : '' ?>"
                rows="3"
                maxlength="500"><?= esc(
                                    $formInput['address'] ?? ''
                                ) ?></textarea>

            <div class="invalid-feedback">
                <?= esc(
                    $validationErrors['address']
                        ?? ''
                ) ?>
            </div>

            <div class="form-text">
                Maximum 500 characters.
            </div>
        </div>

        <div class="col-12">
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
                class="form-control
                    <?= isset(
                        $validationErrors['upi_id']
                    )
                        ? 'is-invalid'
                        : '' ?>"
                value="<?= esc(
                            $formInput['upi_id'] ?? '',
                            'attr'
                        ) ?>"
                maxlength="150"
                pattern="[A-Za-z0-9._-]{2,256}@[A-Za-z][A-Za-z0-9.-]{1,63}"
                placeholder="name@bank">

            <div class="invalid-feedback">
                <?= esc(
                    $validationErrors['upi_id']
                        ?? 'Enter a valid UPI ID.'
                ) ?>
            </div>
        </div>
    </div>

    <div
        class="mt-4 d-grid
            d-sm-flex justify-content-sm-end">

        <button
            type="submit"
            class="btn registration-form__submit
                fs-16 fw-semibold
                w-100 w-sm-auto"
            data-submit-button>

            <span data-submit-idle>
                <?= $isEdit
                    ? 'Save Changes'
                    : 'Add Field Officer' ?>
            </span>

            <span
                class="registration-submit__loading
                    d-none"
                data-submit-loading>

                <span
                    class="spinner-border
                        spinner-border-sm">
                </span>

                <?= $isEdit
                    ? 'Saving changes...'
                    : 'Creating Field Officer...' ?>
            </span>
        </button>
    </div>
</form>