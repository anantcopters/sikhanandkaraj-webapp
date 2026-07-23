<?php

declare(strict_types=1);

/**
 * Family Detail add/edit form.
 *
 * @var array<string, mixed>|null $familyDetails
 * @var array<string, string>     $validationErrors
 * @var array<string, mixed>      $masterData
 */

$details = is_array($familyDetails ?? null)
    ? $familyDetails
    : [];

$errors = is_array($validationErrors ?? null)
    ? $validationErrors
    : [];

$options = is_array($masterData ?? null)
    ? $masterData
    : [];

$familyValues = $options['familyValues'] ?? [];
$familyTypes = $options['familyTypes'] ?? [];
$familyStatuses = $options['familyStatuses'] ?? [];
$familyOccupations = $options['familyOccupations'] ?? [];
$siblingCounts = $options['siblingCounts'] ?? range(0, 10);
$country = $options['country'] ?? [];
$states = $options['states'] ?? [];
$cities = $options['cities'] ?? [];

$fieldValue = static function (
    string $field,
    mixed $storedValue = ''
): string {
    $oldValue = old(
        $field,
        null,
        false
    );

    return $oldValue !== null
        ? (string) $oldValue
        : (string) $storedValue;
};

$isChecked = static function (
    string $field,
    string $option,
    mixed $storedValue = ''
) use ($fieldValue): string {
    return strtoupper(
        trim($fieldValue($field, $storedValue))
    ) === strtoupper($option)
        ? 'checked'
        : '';
};

$isSelected = static function (
    string $field,
    string $option,
    mixed $storedValue = ''
) use ($fieldValue): string {
    return trim(
        $fieldValue($field, $storedValue)
    ) === $option
        ? 'selected'
        : '';
};

$selectedStateId = $fieldValue(
    'state_id',
    $details['state_id'] ?? ''
);

$selectedCityId = $fieldValue(
    'city_id',
    $details['city_id'] ?? ''
);
?>

<form
    method="post"
    action="<?= url_to(
                'web.profile.family-details.update'
            ) ?>"
    id="familyDetailsForm"
    data-validate
    novalidate>

    <?= csrf_field() ?>

    <div class="row g-3">

        <?php
        $radioGroups = [
            [
                'name' => 'family_value',
                'label' => 'Family value',
                'options' => $familyValues,
                'stored' => $details['family_value'] ?? '',
                'error' => 'Please select your family value.',
            ],
            [
                'name' => 'family_type',
                'label' => 'Family type',
                'options' => $familyTypes,
                'stored' => $details['family_type'] ?? '',
                'error' => 'Please select your family type.',
            ],
            [
                'name' => 'family_status',
                'label' => 'Family status',
                'options' => $familyStatuses,
                'stored' => $details['family_status'] ?? '',
                'error' => 'Please select your family status.',
            ],
        ];
        ?>

        <?php foreach ($radioGroups as $group): ?>
            <?php
            $fieldName = (string) $group['name'];
            $errorId = $fieldName . 'Error';
            ?>

            <div class="col-12">
                <fieldset>
                    <legend class="form-label fw-medium fs-14">
                        <?= esc((string) $group['label']) ?>
                        <span class="text-danger">*</span>
                    </legend>

                    <div class="d-flex flex-wrap gap-3">
                        <?php foreach ($group['options'] as $index => $option): ?>
                            <?php
                            $optionValue = (string) (
                                $option['value'] ?? ''
                            );

                            $optionId = $fieldName
                                . ucfirst(strtolower(
                                    str_replace(
                                        '_',
                                        '',
                                        $optionValue
                                    )
                                ));
                            ?>

                            <div class="form-check">
                                <input
                                    type="radio"
                                    class="form-check-input"
                                    id="<?= esc(
                                            $optionId,
                                            'attr'
                                        ) ?>"
                                    name="<?= esc(
                                                $fieldName,
                                                'attr'
                                            ) ?>"
                                    value="<?= esc(
                                                $optionValue,
                                                'attr'
                                            ) ?>"
                                    data-error-required="<?= esc(
                                                                (string) $group['error'],
                                                                'attr'
                                                            ) ?>"
                                    aria-describedby="<?= esc(
                                                            $errorId,
                                                            'attr'
                                                        ) ?>"
                                    <?= $isChecked(
                                        $fieldName,
                                        $optionValue,
                                        $group['stored']
                                    ) ?>
                                    <?= $index === 0
                                        ? 'required'
                                        : '' ?>>

                                <label
                                    class="form-check-label"
                                    for="<?= esc(
                                                $optionId,
                                                'attr'
                                            ) ?>">
                                    <?= esc(
                                        (string) (
                                            $option['label'] ?? ''
                                        )
                                    ) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?= view(
                        'Components/Forms/FieldError',
                        [
                            'field' => $fieldName,
                            'errorId' => $errorId,
                            'errors' => $errors,
                        ]
                    ) ?>
                </fieldset>
            </div>
        <?php endforeach; ?>

        <?php
        $parentFields = [
            [
                'id' => 'fatherOccupationId',
                'name' => 'father_occupation_id',
                'label' => "Father's occupation",
                'stored' =>
                $details['father_occupation_id'] ?? '',
            ],
            [
                'id' => 'motherOccupationId',
                'name' => 'mother_occupation_id',
                'label' => "Mother's occupation",
                'stored' =>
                $details['mother_occupation_id'] ?? '',
            ],
        ];
        ?>

        <?php foreach ($parentFields as $parentField): ?>
            <div class="col-12 col-md-6">
                <label
                    for="<?= esc(
                                $parentField['id'],
                                'attr'
                            ) ?>"
                    class="form-label fw-medium">
                    <?= esc($parentField['label']) ?>
                </label>

                <select
                    id="<?= esc(
                            $parentField['id'],
                            'attr'
                        ) ?>"
                    name="<?= esc(
                                $parentField['name'],
                                'attr'
                            ) ?>"
                    class="form-select"
                    data-choice
                    data-choice-search="false"
                    data-choice-position="bottom">

                    <option value="">
                        Select occupation
                    </option>

                    <?php foreach (
                        $familyOccupations as $occupation
                    ): ?>
                        <?php
                        $occupationId = (string) (
                            $occupation['id'] ?? ''
                        );
                        ?>

                        <option
                            value="<?= esc(
                                        $occupationId,
                                        'attr'
                                    ) ?>"
                            <?= $isSelected(
                                $parentField['name'],
                                $occupationId,
                                $parentField['stored']
                            ) ?>>
                            <?= esc(
                                (string) (
                                    $occupation['name'] ?? ''
                                )
                            ) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <?= view(
                    'Components/Forms/FieldError',
                    [
                        'field' => $parentField['name'],
                        'errorId' =>
                        $parentField['name'] . 'Error',
                        'errors' => $errors,
                    ]
                ) ?>
            </div>
        <?php endforeach; ?>

        <?php
        $siblingFields = [
            [
                'id' => 'brothersCount',
                'name' => 'brothers_count',
                'label' => 'No. of brothers',
                'stored' => $details['brothers_count'] ?? 0,
            ],
            [
                'id' => 'marriedBrothersCount',
                'name' => 'married_brothers_count',
                'label' => 'No. of married brothers',
                'stored' =>
                $details['married_brothers_count'] ?? 0,
            ],
            [
                'id' => 'sistersCount',
                'name' => 'sisters_count',
                'label' => 'No. of sisters',
                'stored' => $details['sisters_count'] ?? 0,
            ],
            [
                'id' => 'marriedSistersCount',
                'name' => 'married_sisters_count',
                'label' => 'No. of married sisters',
                'stored' =>
                $details['married_sisters_count'] ?? 0,
            ],
        ];
        ?>

        <?php foreach ($siblingFields as $siblingField): ?>
            <div class="col-12 col-sm-6 col-lg-3">
                <label
                    for="<?= esc(
                                $siblingField['id'],
                                'attr'
                            ) ?>"
                    class="form-label fw-medium">

                    <?= esc($siblingField['label']) ?>
                    <span class="text-danger">*</span>
                </label>

                <select
                    id="<?= esc(
                            $siblingField['id'],
                            'attr'
                        ) ?>"
                    name="<?= esc(
                                $siblingField['name'],
                                'attr'
                            ) ?>"
                    class="form-select"
                    data-choice
                    data-choice-search="false"
                    data-choice-position="bottom"
                    required>

                    <?php foreach ($siblingCounts as $count): ?>
                        <?php $countValue = (string) $count; ?>

                        <option
                            value="<?= esc(
                                        $countValue,
                                        'attr'
                                    ) ?>"
                            <?= $isSelected(
                                $siblingField['name'],
                                $countValue,
                                $siblingField['stored']
                            ) ?>>
                            <?= esc($countValue) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <?= view(
                    'Components/Forms/FieldError',
                    [
                        'field' => $siblingField['name'],
                        'errorId' =>
                        $siblingField['name'] . 'Error',
                        'errors' => $errors,
                    ]
                ) ?>
            </div>
        <?php endforeach; ?>

        <div class="col-12">
            <h2 class="fs-16 fw-semibold mb-0 mt-2">
                Family Location
            </h2>
        </div>

        <div class="col-12 col-md-4">
            <label
                for="familyStateId"
                class="form-label fw-medium">
                State
                <span class="text-danger">*</span>
            </label>

            <select
                id="familyStateId"
                name="state_id"
                class="form-select"
                data-choice
                data-choice-search="true"
                data-choice-search-placeholder="Search state"
                data-choice-position="bottom"
                data-error-required="Please select your family state."
                required>

                <option value="">
                    Select state
                </option>

                <?php foreach ($states as $state): ?>
                    <?php
                    $stateId = (string) ($state['id'] ?? '');
                    ?>

                    <option
                        value="<?= esc($stateId, 'attr') ?>"
                        <?= $isSelected(
                            'state_id',
                            $stateId,
                            $details['state_id'] ?? ''
                        ) ?>>
                        <?= esc(
                            (string) ($state['name'] ?? '')
                        ) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?= view(
                'Components/Forms/FieldError',
                [
                    'field' => 'state_id',
                    'errorId' => 'stateIdError',
                    'errors' => $errors,
                ]
            ) ?>
        </div>

        <div class="col-12 col-md-4">
            <label
                for="familyCityId"
                class="form-label fw-medium">
                City
                <span class="text-danger">*</span>
            </label>

            <select
                id="familyCityId"
                name="city_id"
                class="form-select"
                data-choice
                data-choice-search="true"
                data-choice-search-placeholder="Search city"
                data-choice-position="bottom"
                data-cities-url="<?= esc(
                                        site_url('profile/master/cities'),
                                        'attr'
                                    ) ?>"
                data-selected-city="<?= esc(
                                        $selectedCityId,
                                        'attr'
                                    ) ?>"
                data-error-required="Please select your family city."
                <?= $selectedStateId === ''
                    ? 'disabled'
                    : '' ?>
                required>

                <option value="">
                    <?= $selectedStateId === ''
                        ? 'Select state first'
                        : 'Select city' ?>
                </option>

                <?php foreach ($cities as $city): ?>
                    <?php
                    $cityId = (string) ($city['id'] ?? '');
                    ?>

                    <option
                        value="<?= esc($cityId, 'attr') ?>"
                        <?= $isSelected(
                            'city_id',
                            $cityId,
                            $details['city_id'] ?? ''
                        ) ?>>
                        <?= esc(
                            (string) ($city['name'] ?? '')
                        ) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?= view(
                'Components/Forms/FieldError',
                [
                    'field' => 'city_id',
                    'errorId' => 'cityIdError',
                    'errors' => $errors,
                ]
            ) ?>
        </div>

        <div class="col-12 col-md-4">
            <label
                for="familyCountryName"
                class="form-label fw-medium">
                Country
            </label>

            <input
                type="text"
                id="familyCountryName"
                class="form-control bg-light"
                value="<?= esc(
                            (string) ($country['name'] ?? 'India'),
                            'attr'
                        ) ?>"
                readonly>

            <input
                type="hidden"
                name="country_id"
                value="<?= esc(
                            (string) ($country['id'] ?? ''),
                            'attr'
                        ) ?>">
        </div>
    </div>

    <div
        id="familySiblingValidationError"
        class="invalid-feedback d-block mt-3"
        aria-live="polite">
    </div>

    <div class="row g-2 mt-4">
        <div
            class="col-12 col-sm-6 col-md-3
                ms-md-auto order-2 order-sm-1">

            <a
                href="<?= url_to('web.profile.edit') ?>"
                class="btn btn-outline-danger
                    fs-14 fw-medium w-100">
                Cancel
            </a>
        </div>

        <div
            class="col-12 col-sm-6 col-md-3
                order-1 order-sm-2">

            <button
                type="submit"
                id="saveFamilyDetailsButton"
                class="btn registration-form__submit
                    fs-14 fw-semibold text-uppercase">

                <span class="registration-submit__label">
                    Save Details
                </span>

                <span
                    class="registration-submit__loading d-none"
                    aria-hidden="true">

                    <span
                        class="spinner-border spinner-border-sm"
                        role="status"
                        aria-hidden="true">
                    </span>

                    <span>Saving...</span>
                </span>
            </button>
        </div>
    </div>
</form> 