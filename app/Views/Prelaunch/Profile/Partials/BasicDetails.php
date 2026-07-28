<?php

declare(strict_types=1);

/**
 * Basic Details master-data section for the standalone
 * pre-launch profile collection form.
 *
 * The parent Index.php already renders:
 *
 * - Profile created for
 * - Gender
 * - Full name
 * - Date of birth
 * - Email
 * - Country code
 * - Mobile number
 *
 * This partial therefore renders only the remaining reusable
 * Basic Details fields.
 *
 * @var array<string, string>              $validationErrors
 * @var array<int, array<string, mixed>>   $maritalStatuses
 * @var array<int, array<string, mixed>>   $heights
 * @var array<int, array<string, mixed>>   $motherTongues
 * @var array<int, array<string, mixed>>   $countries
 * @var array<int, array<string, mixed>>   $states
 * @var array<int, array<string, mixed>>   $cities
 */

$errors = is_array($validationErrors ?? null)
    ? $validationErrors
    : [];

$maritalStatusOptions = is_array($maritalStatuses ?? null)
    ? $maritalStatuses
    : [];

$heightOptions = is_array($heights ?? null)
    ? $heights
    : [];

$motherTongueOptions = is_array($motherTongues ?? null)
    ? $motherTongues
    : [];

$countryOptions = is_array($countries ?? null)
    ? $countries
    : [];

$stateOptions = is_array($states ?? null)
    ? $states
    : [];

$cityOptions = is_array($cities ?? null)
    ? $cities
    : [];

$selectedMaritalStatusId = (string) old(
    'marital_status_id',
    ''
);

$selectedHeightId = (string) old(
    'height_id',
    ''
);

$selectedMotherTongueId = (string) old(
    'mother_tongue_id',
    ''
);

$selectedCountryId = (string) old(
    'country_id',
    ''
);

$selectedStateId = (string) old(
    'state_id',
    ''
);

$selectedCityId = (string) old(
    'city_id',
    ''
);

/*
 * The pre-launch page reuses the existing public city endpoint.
 */
$cityLookupUrl = route_to(
    'web.profile.master.cities',
    0
);
?>

<fieldset class="mb-4">
    <legend class="h5 mb-3">
        Basic details
    </legend>

    <div class="row g-3">
        <div class="col-12 col-md-6">
            <label
                for="marital_status_id"
                class="form-label">
                Marital status

                <span
                    class="text-danger"
                    aria-hidden="true">
                    *
                </span>
            </label>

            <select
                id="marital_status_id"
                name="marital_status_id"
                class="form-select <?= isset(
                                        $errors['marital_status_id']
                                    )
                                        ? 'is-invalid'
                                        : '' ?>"
                required>
                <option value="">
                    Select marital status
                </option>

                <?php foreach (
                    $maritalStatusOptions as $status
                ): ?>
                    <?php
                    if (!is_array($status)) {
                        continue;
                    }

                    $statusId = (string) (
                        $status['id'] ?? ''
                    );

                    $statusName = (string) (
                        $status['name']
                        ?? $status['label']
                        ?? ''
                    );

                    if (
                        $statusId === ''
                        || $statusName === ''
                    ) {
                        continue;
                    }
                    ?>

                    <option
                        value="<?= esc(
                                    $statusId,
                                    'attr'
                                ) ?>"
                        <?= $selectedMaritalStatusId
                            === $statusId
                            ? 'selected'
                            : '' ?>>
                        <?= esc($statusName) ?>
                    </option>
                <?php endforeach ?>
            </select>

            <div class="invalid-feedback">
                <?= esc(
                    $errors['marital_status_id']
                        ?? 'Please select marital status.'
                ) ?>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <label
                for="height_id"
                class="form-label">
                Height

                <span
                    class="text-danger"
                    aria-hidden="true">
                    *
                </span>
            </label>

            <select
                id="height_id"
                name="height_id"
                class="form-select <?= isset(
                                        $errors['height_id']
                                    )
                                        ? 'is-invalid'
                                        : '' ?>"
                required>
                <option value="">
                    Select height
                </option>

                <?php foreach (
                    $heightOptions as $height
                ): ?>
                    <?php
                    if (!is_array($height)) {
                        continue;
                    }

                    $heightId = (string) (
                        $height['id'] ?? ''
                    );

                    $heightName = (string) (
                        $height['label']
                        ?? $height['name']
                        ?? $height['display_name']
                        ?? ''
                    );

                    if (
                        $heightId === ''
                        || $heightName === ''
                    ) {
                        continue;
                    }
                    ?>

                    <option
                        value="<?= esc(
                                    $heightId,
                                    'attr'
                                ) ?>"
                        <?= $selectedHeightId
                            === $heightId
                            ? 'selected'
                            : '' ?>>
                        <?= esc($heightName) ?>
                    </option>
                <?php endforeach ?>
            </select>

            <div class="invalid-feedback">
                <?= esc(
                    $errors['height_id']
                        ?? 'Please select height.'
                ) ?>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <label
                for="mother_tongue_id"
                class="form-label">
                Mother tongue

                <span
                    class="text-danger"
                    aria-hidden="true">
                    *
                </span>
            </label>

            <select
                id="mother_tongue_id"
                name="mother_tongue_id"
                class="form-select <?= isset(
                                        $errors['mother_tongue_id']
                                    )
                                        ? 'is-invalid'
                                        : '' ?>"
                required>
                <option value="">
                    Select mother tongue
                </option>

                <?php foreach (
                    $motherTongueOptions as $motherTongue
                ): ?>
                    <?php
                    if (!is_array($motherTongue)) {
                        continue;
                    }

                    $motherTongueId = (string) (
                        $motherTongue['id'] ?? ''
                    );

                    $motherTongueName = (string) (
                        $motherTongue['name']
                        ?? $motherTongue['label']
                        ?? ''
                    );

                    if (
                        $motherTongueId === ''
                        || $motherTongueName === ''
                    ) {
                        continue;
                    }
                    ?>

                    <option
                        value="<?= esc(
                                    $motherTongueId,
                                    'attr'
                                ) ?>"
                        <?= $selectedMotherTongueId
                            === $motherTongueId
                            ? 'selected'
                            : '' ?>>
                        <?= esc($motherTongueName) ?>
                    </option>
                <?php endforeach ?>
            </select>

            <div class="invalid-feedback">
                <?= esc(
                    $errors['mother_tongue_id']
                        ?? 'Please select mother tongue.'
                ) ?>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <label
                for="country_id"
                class="form-label">
                Country

                <span
                    class="text-danger"
                    aria-hidden="true">
                    *
                </span>
            </label>

            <select
                id="country_id"
                name="country_id"
                class="form-select <?= isset(
                                        $errors['country_id']
                                    )
                                        ? 'is-invalid'
                                        : '' ?>"
                required>
                <option value="">
                    Select country
                </option>

                <?php foreach (
                    $countryOptions as $country
                ): ?>
                    <?php
                    if (!is_array($country)) {
                        continue;
                    }

                    $countryId = (string) (
                        $country['id'] ?? ''
                    );

                    $countryName = (string) (
                        $country['name']
                        ?? $country['label']
                        ?? ''
                    );

                    if (
                        $countryId === ''
                        || $countryName === ''
                    ) {
                        continue;
                    }

                    /*
                     * India is currently the only supported country.
                     * Select it automatically when old input is empty.
                     */
                    $isSelected =
                        $selectedCountryId !== ''
                        ? $selectedCountryId === $countryId
                        : count($countryOptions) === 1;
                    ?>

                    <option
                        value="<?= esc(
                                    $countryId,
                                    'attr'
                                ) ?>"
                        <?= $isSelected
                            ? 'selected'
                            : '' ?>>
                        <?= esc($countryName) ?>
                    </option>
                <?php endforeach ?>
            </select>

            <div class="invalid-feedback">
                <?= esc(
                    $errors['country_id']
                        ?? 'Please select country.'
                ) ?>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <label
                for="state_id"
                class="form-label">
                State

                <span
                    class="text-danger"
                    aria-hidden="true">
                    *
                </span>
            </label>

            <select
                id="state_id"
                name="state_id"
                class="form-select <?= isset(
                                        $errors['state_id']
                                    )
                                        ? 'is-invalid'
                                        : '' ?>"
                data-city-url-template="<?= esc(
                                            $cityLookupUrl,
                                            'attr'
                                        ) ?>"
                required>
                <option value="">
                    Select state
                </option>

                <?php foreach (
                    $stateOptions as $state
                ): ?>
                    <?php
                    if (!is_array($state)) {
                        continue;
                    }

                    $stateId = (string) (
                        $state['id'] ?? ''
                    );

                    $stateName = (string) (
                        $state['name']
                        ?? $state['label']
                        ?? ''
                    );

                    if (
                        $stateId === ''
                        || $stateName === ''
                    ) {
                        continue;
                    }
                    ?>

                    <option
                        value="<?= esc(
                                    $stateId,
                                    'attr'
                                ) ?>"
                        <?= $selectedStateId === $stateId
                            ? 'selected'
                            : '' ?>>
                        <?= esc($stateName) ?>
                    </option>
                <?php endforeach ?>
            </select>

            <div class="invalid-feedback">
                <?= esc(
                    $errors['state_id']
                        ?? 'Please select state.'
                ) ?>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <label
                for="city_id"
                class="form-label">
                City

                <span
                    class="text-danger"
                    aria-hidden="true">
                    *
                </span>
            </label>

            <select
                id="city_id"
                name="city_id"
                class="form-select <?= isset(
                                        $errors['city_id']
                                    )
                                        ? 'is-invalid'
                                        : '' ?>"
                data-selected-value="<?= esc(
                                            $selectedCityId,
                                            'attr'
                                        ) ?>"
                <?= $selectedStateId === ''
                    ? 'disabled'
                    : '' ?>
                required>
                <option value="">
                    <?= $selectedStateId === ''
                        ? 'Select state first'
                        : 'Select city' ?>
                </option>

                <?php foreach (
                    $cityOptions as $city
                ): ?>
                    <?php
                    if (!is_array($city)) {
                        continue;
                    }

                    $cityId = (string) (
                        $city['id'] ?? ''
                    );

                    $cityName = (string) (
                        $city['name']
                        ?? $city['label']
                        ?? ''
                    );

                    if (
                        $cityId === ''
                        || $cityName === ''
                    ) {
                        continue;
                    }
                    ?>

                    <option
                        value="<?= esc(
                                    $cityId,
                                    'attr'
                                ) ?>"
                        <?= $selectedCityId === $cityId
                            ? 'selected'
                            : '' ?>>
                        <?= esc($cityName) ?>
                    </option>
                <?php endforeach ?>
            </select>

            <div class="invalid-feedback">
                <?= esc(
                    $errors['city_id']
                        ?? 'Please select city.'
                ) ?>
            </div>
        </div>
    </div>
</fieldset>