<?php

declare(strict_types=1);

/**
 * @var array<string, string>|null            $validationErrors
 * @var array<string, mixed>|null             $country
 * @var array<int, array<string, mixed>>|null $maritalStatuses
 * @var array<int, array<string, mixed>>|null $heights
 * @var array<int, array<string, mixed>>|null $states
 * @var array<int, array<string, mixed>>|null $cities
 */

$errorBag = is_array($validationErrors ?? null)
    ? $validationErrors
    : [];

$countryData = is_array($country ?? null)
    ? $country
    : [];

$maritalStatusOptions = is_array(
    $maritalStatuses ?? null
)
    ? $maritalStatuses
    : [];

$heightOptions = is_array($heights ?? null)
    ? $heights
    : [];

$stateOptions = is_array($states ?? null)
    ? $states
    : [];

$cityOptions = is_array($cities ?? null)
    ? $cities
    : [];

$countryId = (string) (
    $countryData['id']
    ?? ''
);

$countryName = trim(
    (string) (
        $countryData['name']
        ?? 'India'
    )
);

if ($countryName === '') {
    $countryName = 'India';
}

$maritalStatusId = (string) old(
    'marital_status_id',
    ''
);

$heightId = (string) old(
    'height_id',
    ''
);

$stateId = (string) old(
    'state_id',
    ''
);

$cityId = (string) old(
    'city_id',
    ''
);

$maritalStatusError = trim(
    (string) (
        $errorBag['marital_status_id']
        ?? ''
    )
);

$heightError = trim(
    (string) (
        $errorBag['height_id']
        ?? ''
    )
);

$countryError = trim(
    (string) (
        $errorBag['country_id']
        ?? ''
    )
);

$stateError = trim(
    (string) (
        $errorBag['state_id']
        ?? ''
    )
);

$cityError = trim(
    (string) (
        $errorBag['city_id']
        ?? ''
    )
);

$maritalStatusClass =
    $maritalStatusError !== ''
    ? 'is-invalid'
    : '';

$heightClass = $heightError !== ''
    ? 'is-invalid'
    : '';

$countryClass = $countryError !== ''
    ? 'is-invalid'
    : '';

$stateClass = $stateError !== ''
    ? 'is-invalid'
    : '';

$cityClass = $cityError !== ''
    ? 'is-invalid'
    : '';

$cityRouteTemplate = route_to(
    'prelaunch.master.cities',
    0
);
?>

<div class="card border border-danger border-opacity-25 shadow-sm mb-3">
    <div class="card-body p-3 p-md-4">
        <div class="d-flex align-items-start gap-3 mb-3">
            <div class="fs-3 text-primary">
                <i
                    class="ri-profile-line"
                    aria-hidden="true"></i>
            </div>

            <div>
                <h5 class="mb-1 fs-14 fw-semibold">
                    Basic details
                </h5>

                <p class="text-muted mb-0 fs-12">
                    Add personal attributes and the member’s
                    current location.
                </p>
            </div>
        </div>

        <input
            type="hidden"
            id="country_id"
            name="country_id"
            value="<?= esc(
                        $countryId,
                        'attr'
                    ) ?>">
        <hr class="my-2 mb-2">
        </hr>
        <div class="row g-3 pt-2">

            <div class="col-12 col-md-6">
                <label
                    for="marital_status_id"
                    class="form-label">
                    Marital status
                </label>

                <select
                    id="marital_status_id"
                    name="marital_status_id"
                    class="form-select <?= esc(
                                                        $maritalStatusClass,
                                                        'attr'
                                                    ) ?>"
                    data-choice
                    data-choice-search="false"
                    data-choice-position="bottom"
                    data-error-required="Please select your marital status."
                    required>
                    <option value="">
                        Select marital status
                    </option>

                    <?php foreach (
                        $maritalStatusOptions as
                        $maritalStatusOption
                    ): ?>
                        <?php
                        if (!is_array(
                            $maritalStatusOption
                        )) {
                            continue;
                        }

                        $optionId = (string) (
                            $maritalStatusOption['id']
                            ?? ''
                        );

                        $optionName = trim(
                            (string) (
                                $maritalStatusOption['name']
                                ?? $maritalStatusOption['label']
                                ?? ''
                            )
                        );

                        if (
                            $optionId === ''
                            || $optionName === ''
                        ) {
                            continue;
                        }

                        $optionSelected =
                            $maritalStatusId
                            === $optionId;
                        ?>

                        <option
                            value="<?= esc(
                                        $optionId,
                                        'attr'
                                    ) ?>"
                            <?= $optionSelected
                                ? 'selected'
                                : '' ?>>
                            <?= esc($optionName) ?>
                        </option>
                    <?php endforeach ?>
                </select>
                <div
                    id="marital_status_idError"
                    class="invalid-feedback"
                    data-validation-error="marital_status_id">
                    <?= esc($maritalStatusError) ?>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <label
                    for="height_id"
                    class="form-label">
                    Height
                </label>

                <select
                    id="height_id"
                    name="height_id"
                    class="form-select <?= esc(
                                                        $heightClass,
                                                        'attr'
                                                    ) ?>"
                    data-choice
                    data-choice-search="true"
                    data-choice-position="bottom"
                    data-error-required="Please select height."
                    required>
                    <option value="">
                        Select height
                    </option>

                    <?php foreach (
                        $heightOptions as $heightOption
                    ): ?>
                        <?php
                        if (!is_array($heightOption)) {
                            continue;
                        }

                        $optionId = (string) (
                            $heightOption['id']
                            ?? ''
                        );

                        $optionName = trim(
                            (string) (
                                $heightOption['label']
                                ?? $heightOption['name']
                                ?? ''
                            )
                        );

                        if (
                            $optionId === ''
                            || $optionName === ''
                        ) {
                            continue;
                        }

                        $optionSelected =
                            $heightId === $optionId;
                        ?>

                        <option
                            value="<?= esc(
                                        $optionId,
                                        'attr'
                                    ) ?>"
                            <?= $optionSelected
                                ? 'selected'
                                : '' ?>>
                            <?= esc($optionName) ?>
                        </option>
                    <?php endforeach ?>
                </select>
                <div
                    id="height_idError"
                    class="invalid-feedback"
                    data-validation-error="height_id">
                    <?= esc($heightError) ?>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <label
                    for="country_display"
                    class="form-label">
                    Country
                </label>

                <input
                    type="text"
                    id="country_display"
                    class="form-control <?= esc(
                                            $countryClass,
                                            'attr'
                                        ) ?>"
                    value="<?= esc(
                                $countryName,
                                'attr'
                            ) ?>"
                    readonly
                    aria-readonly="true">
                <div
                    id="country_displayError"
                    class="invalid-feedback"
                    data-validation-error="country_display">
                    <?= esc($countryError) ?>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <label
                    for="state_id"
                    class="form-label">
                    State
                </label>

                <select
                    id="state_id"
                    name="state_id"
                    class="form-select <?= esc(
                                                        $stateClass,
                                                        'attr'
                                                    ) ?>"
                    data-city-url-template="<?= esc(
                                                $cityRouteTemplate,
                                                'attr'
                                            ) ?>"
                    data-choice
                    data-choice-search="true"
                    data-choice-position="bottom"
                    data-error-required="Please select state."
                    required>
                    <option value="">
                        Select state
                    </option>

                    <?php foreach (
                        $stateOptions as $stateOption
                    ): ?>
                        <?php
                        if (!is_array($stateOption)) {
                            continue;
                        }

                        $optionId = (string) (
                            $stateOption['id']
                            ?? ''
                        );

                        $optionName = trim(
                            (string) (
                                $stateOption['name']
                                ?? $stateOption['label']
                                ?? ''
                            )
                        );

                        if (
                            $optionId === ''
                            || $optionName === ''
                        ) {
                            continue;
                        }

                        $optionSelected =
                            $stateId === $optionId;
                        ?>

                        <option
                            value="<?= esc(
                                        $optionId,
                                        'attr'
                                    ) ?>"
                            <?= $optionSelected
                                ? 'selected'
                                : '' ?>>
                            <?= esc($optionName) ?>
                        </option>
                    <?php endforeach ?>
                </select>
                <div
                    id="state_idError"
                    class="invalid-feedback"
                    data-validation-error="state_id">
                    <?= esc($stateError) ?>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <label
                    for="city_id"
                    class="form-label">
                    City
                </label>

                <select
                    id="city_id"
                    name="city_id"
                    class="form-select <?= esc(
                                                        $cityClass,
                                                        'attr'
                                                    ) ?>"
                    data-selected-value="<?= esc(
                                                $cityId,
                                                'attr'
                                            ) ?>"
                    data-choice
                    data-choice-search="true"
                    data-choice-position="bottom"
                    data-error-required="Please select city."
                    required>
                    <option value="">
                        Select city
                    </option>

                    <?php foreach (
                        $cityOptions as $cityOption
                    ): ?>
                        <?php
                        if (!is_array($cityOption)) {
                            continue;
                        }

                        $optionId = (string) (
                            $cityOption['id']
                            ?? ''
                        );

                        $optionName = trim(
                            (string) (
                                $cityOption['name']
                                ?? $cityOption['label']
                                ?? ''
                            )
                        );

                        if (
                            $optionId === ''
                            || $optionName === ''
                        ) {
                            continue;
                        }

                        $optionSelected =
                            $cityId === $optionId;
                        ?>

                        <option
                            value="<?= esc(
                                        $optionId,
                                        'attr'
                                    ) ?>"
                            <?= $optionSelected
                                ? 'selected'
                                : '' ?>>
                            <?= esc($optionName) ?>
                        </option>
                    <?php endforeach ?>
                </select>
                <div
                    id="city_idError"
                    class="invalid-feedback"
                    data-validation-error="city_id">
                    <?= esc($cityError) ?>
                </div>
            </div>
        </div>
    </div>
</div>