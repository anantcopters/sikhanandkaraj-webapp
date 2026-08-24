<?php

declare(strict_types=1);

/**
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
$communities = $options['communities'] ?? [];
$familyOccupations = $options['familyOccupations'] ?? [];
$siblingCounts = $options['siblingCounts'] ?? range(0, 10);
$country = $options['country'] ?? [];
$countries = $options['countries'] ?? [];
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

$selectedCountryId = $fieldValue(
    'country_id',
    $details['country_id'] ?? ($country['id'] ?? '')
);

$selectedCityId = $fieldValue(
    'city_id',
    $details['city_id'] ?? ''
);

$selectedCommunityId = $fieldValue(
    'community_id',
    $details['community_id'] ?? ''
);

$isJourney = ($isProfileJourney ?? false) === true;

$formAction = url_to(
    'web.profile.family-details.update'
);

if ($isJourney) {
    $formAction .= '?journey=1';
}
?>

<form
    method="post"
    action="<?= esc($formAction, 'attr') ?>"
    id="familyDetailsForm"
    data-validate
    novalidate>

    <?= csrf_field() ?>

    <div class="row g-3">

        <?php
        $familyMasterFields = [
            [
                'id' => 'familyValueId',
                'name' => 'family_value_id',
                'label' => 'Family value',
                'placeholder' => 'Select family value',
                'options' => $familyValues,
                'stored' => $details['family_value_id'] ?? '',
            ],
            [
                'id' => 'familyTypeId',
                'name' => 'family_type_id',
                'label' => 'Family type',
                'placeholder' => 'Select family type',
                'options' => $familyTypes,
                'stored' => $details['family_type_id'] ?? '',
            ],
            [
                'id' => 'familyStatusId',
                'name' => 'family_status_id',
                'label' => 'Family status',
                'placeholder' => 'Select family status',
                'options' => $familyStatuses,
                'stored' => $details['family_status_id'] ?? '',
            ],
        ];
        ?>

        <?php foreach ($familyMasterFields as $field): ?>
            <div class="col-12 col-md-4">
                <label
                    for="<?= esc($field['id'], 'attr') ?>"
                    class="form-label">

                    <?= esc($field['label']) ?>
                    <span class="text-muted fw-normal">
                        (Optional)
                    </span>
                </label>

                <select
                    id="<?= esc($field['id'], 'attr') ?>"
                    name="<?= esc($field['name'], 'attr') ?>"
                    class="form-select"
                    data-choice
                    data-choice-search="false"
                    data-choice-position="bottom">

                    <option value="">
                        <?= esc($field['placeholder']) ?>
                    </option>

                    <?php foreach ($field['options'] as $option): ?>
                        <?php
                        $optionId = (string) (
                            $option['id'] ?? ''
                        );
                        ?>

                        <option
                            value="<?= esc($optionId, 'attr') ?>"
                            <?= $isSelected(
                                $field['name'],
                                $optionId,
                                $field['stored']
                            ) ?>>

                            <?= esc(
                                (string) ($option['name'] ?? '')
                            ) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <?= view(
                    'Components/Forms/FieldError',
                    [
                        'field' => $field['name'],
                        'errorId' =>
                        $field['name'] . 'Error',
                        'errors' => $errors,
                    ]
                ) ?>
            </div>
        <?php endforeach; ?>

        <div class="col-12">
            <hr class="my-2 mb-3">

            <h2 class="fs-16 fw-semibold mb-0 mt-2 text-secondary-emphasis">
                Community Details
            </h2>
        </div>

        <div class="col-12 col-md-6">
            <label
                for="familyCommunityId"
                class="form-label">

                Community
                <span class="text-danger">*</span>
            </label>

            <select
                id="familyCommunityId"
                name="community_id"
                class="form-select"
                data-choice
                data-choice-search="true"
                data-choice-search-placeholder="Search community"
                data-choice-position="bottom"
                data-error-required="Please select your community."
                required>

                <option value="">
                    Select community
                </option>

                <?php foreach ($communities as $community): ?>
                    <?php
                    $communityId = (string) (
                        $community['id'] ?? ''
                    );
                    ?>

                    <option
                        value="<?= esc($communityId, 'attr') ?>"
                        <?= $isSelected(
                            'community_id',
                            $communityId,
                            $selectedCommunityId
                        ) ?>>

                        <?= esc(
                            (string) ($community['name'] ?? '')
                        ) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?= view(
                'Components/Forms/FieldError',
                [
                    'field' => 'community_id',
                    'errorId' => 'communityIdError',
                    'errors' => $errors,
                ]
            ) ?>
        </div>


        <div class="col-12 col-md-6">
            <label
                for="familyGotra"
                class="form-label">

                Father Gotra
                <span class="text-danger">*</span>
            </label>

            <input
                type="text"
                id="familyGotra"
                name="gotra"
                class="form-control"
                value="<?= esc(
                            $fieldValue(
                                'gotra',
                                $details['gotra'] ?? ''
                            ),
                            'attr'
                        ) ?>"
                placeholder="Enter Father Gotra"
                maxlength="100"
                autocomplete="off"
                data-error-required="Please enter your Father Gotra."
                required>

            <?= view(
                'Components/Forms/FieldError',
                [
                    'field' => 'gotra',
                    'errorId' => 'gotraError',
                    'errors' => $errors,
                ]
            ) ?>
        </div>

        <div class="col-12 col-md-6">
            <label
                for="familyGotraMaternal"
                class="form-label">

                Mother Gotra (Maternal Side)
                <span class="text-danger">*</span>
            </label>

            <input
                type="text"
                id="familyGotraMaternal"
                name="gotra_maternal"
                class="form-control"
                value="<?= esc(
                            $fieldValue(
                                'gotra_maternal',
                                $details['gotra_maternal'] ?? ''
                            ),
                            'attr'
                        ) ?>"
                placeholder="Enter Mother Gotra (Maternal Side)"
                maxlength="100"
                autocomplete="off"
                data-error-required="Please enter your Mother Gotra (Maternal Side)."
                required>

            <?= view(
                'Components/Forms/FieldError',
                [
                    'field' => 'gotra_maternal',
                    'errorId' => 'gotraMaternalError',
                    'errors' => $errors,
                ]
            ) ?>
        </div>
        <div class="col-12">
            <hr class="my-2 mb-3">

            <h2 class="fs-16 fw-semibold mb-0 mt-2 text-secondary-emphasis">
                Parent Details
            </h2>
        </div>

        <?php
        $parentNameFields = [
            [
                'id' => 'fatherName',
                'name' => 'father_name',
                'label' => "Father's name",
                'placeholder' => "Enter father's name",
                'stored' => $details['father_name'] ?? '',
                'requiredMessage' =>
                "Please enter your father's name.",
            ],
            [
                'id' => 'motherName',
                'name' => 'mother_name',
                'label' => "Mother's name",
                'placeholder' => "Enter mother's name",
                'stored' => $details['mother_name'] ?? '',
                'requiredMessage' =>
                "Please enter your mother's name.",
            ],
        ];
        ?>

        <?php foreach ($parentNameFields as $parentNameField): ?>
            <div class="col-12 col-md-6">
                <label
                    for="<?= esc(
                                $parentNameField['id'],
                                'attr'
                            ) ?>"
                    class="form-label">

                    <?= esc($parentNameField['label']) ?>
                    <span class="text-danger">*</span>
                </label>

                <input
                    type="text"
                    id="<?= esc(
                            $parentNameField['id'],
                            'attr'
                        ) ?>"
                    name="<?= esc(
                                $parentNameField['name'],
                                'attr'
                            ) ?>"
                    class="form-control"
                    value="<?= esc(
                                $fieldValue(
                                    $parentNameField['name'],
                                    $parentNameField['stored']
                                ),
                                'attr'
                            ) ?>"
                    placeholder="<?= esc(
                                        $parentNameField['placeholder'],
                                        'attr'
                                    ) ?>"
                    maxlength="150"
                    autocomplete="name"
                    data-error-required="<?= esc(
                                                $parentNameField['requiredMessage'],
                                                'attr'
                                            ) ?>"
                    required>

                <?= view(
                    'Components/Forms/FieldError',
                    [
                        'field' => $parentNameField['name'],
                        'errorId' =>
                        $parentNameField['name'] . 'Error',
                        'errors' => $errors,
                    ]
                ) ?>
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
                    class="form-label">
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
        <div class="col-12 col-md-6">
            <label
                for="parentContactNumber"
                class="form-label">

                Parent/Guardian Contact Number
                <span
                    class="text-danger"
                    aria-hidden="true">
                    *
                </span>
            </label>

            <div class="input-group has-validation">
                <span class="input-group-text">
                    +91
                </span>

                <input
                    type="tel"
                    id="parentContactNumber"
                    name="parent_contact_number"
                    class="form-control"
                    value="<?= esc(
                                preg_replace(
                                    '/^\+91/',
                                    '',
                                    $fieldValue(
                                        'parent_contact_number',
                                        $details['parent_contact_number'] ?? ''
                                    )
                                ) ?? '',
                                'attr'
                            ) ?>"
                    placeholder="Enter parent contact number"
                    inputmode="numeric"
                    pattern="[6-9][0-9]{9}"
                    minlength="10"
                    maxlength="10"
                    autocomplete="tel"
                    data-error-required="Please enter a contact number for either parent/guardian."
                    data-error-pattern="Please enter a valid 10-digit Indian parent/guardian contact number."
                    data-error-minlength="Parent contact number must contain 10 digits."
                    data-error-maxlength="Parent contact number must contain 10 digits."
                    aria-describedby="parentContactNumberHelp parentContactNumberError">

                <?= view(
                    'Components/Forms/FieldError',
                    [
                        'field' =>
                        'parent_contact_number',

                        'errorId' =>
                        'parentContactNumberError',

                        'errors' =>
                        $errors,
                    ]
                ) ?>
            </div>

            <div
                id="parentContactNumberHelp"
                class="form-text text-muted">
                Enter the mobile number of either parent/guardian, when available.
            </div>
        </div>
        <div class="col-12">
            <hr class="my-2 mb-3">

            <h2 class="fs-16 fw-semibold mb-0 mt-2 text-secondary-emphasis">
                Sibling Details
            </h2>
        </div>

        <?php
        $siblingFields = [
            [
                'id' => 'brothersCount',
                'name' => 'brothers_count',
                'label' => 'No. of brothers',
                'stored' => $details['brothers_count'] ?? 0,
                'requiredMessage' =>
                'Please select the number of brothers.',
            ],
            [
                'id' => 'sistersCount',
                'name' => 'sisters_count',
                'label' => 'No. of sisters',
                'stored' => $details['sisters_count'] ?? 0,
                'requiredMessage' =>
                'Please select the number of sisters.',
            ],
        ];
        ?>

        <?php foreach ($siblingFields as $siblingField): ?>
            <div class="col-12 col-md-6">
                <label
                    for="<?= esc(
                                $siblingField['id'],
                                'attr'
                            ) ?>"
                    class="form-label">

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
                    data-error-required="<?= esc(
                                                $siblingField['requiredMessage'],
                                                'attr'
                                            ) ?>"
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
            <hr class="my-2 mb-3">

            <h2 class="fs-16 fw-semibold mb-0 mt-2 text-secondary-emphasis">
                Family Location
            </h2>
        </div>

        <div class="col-12 col-md-4">
            <label
                for="familyCountryId"
                class="form-label">
                Country
                <span class="text-danger">*</span>
            </label>

            <select
                id="familyCountryId"
                name="country_id"
                class="form-select"
                data-choice
                data-choice-search="false"
                data-dependent-url-template="<?= esc(
                                                    site_url(
                                                        'profile/master/states/__PARENT_ID__'
                                                    ),
                                                    'attr'
                                                ) ?>"
                data-error-required="Please select your family country."
                required>
                <option value="">Select country</option>
                <?php foreach ($countries as $countryOption): ?>
                    <?php $optionId = (string) ($countryOption['id'] ?? ''); ?>
                    <option
                        value="<?= esc($optionId, 'attr') ?>"
                        <?= $selectedCountryId === $optionId ? 'selected' : '' ?>>
                        <?= esc((string) ($countryOption['name'] ?? '')) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?= view('Components/Forms/FieldError', [
                'field' => 'country_id',
                'errorId' => 'familyCountryIdError',
                'errors' => $errors,
            ]) ?>
        </div>

        <div class="col-12 col-md-4">
            <label
                for="familyStateId"
                class="form-label">

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
                data-dependent-url-template="<?= esc(
                                                    site_url(
                                                        'profile/master/cities/__PARENT_ID__'
                                                    ),
                                                    'attr'
                                                ) ?>"
                data-error-required="Please select your family state."
                <?= $selectedCountryId === '' ? 'disabled' : '' ?>
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
                class="form-label">

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
                data-selected-value="<?= esc(
                                            $selectedCityId,
                                            'attr'
                                        ) ?>"
                data-error-required="Please select your family city."
                required
                <?= $selectedStateId === ''
                    ? 'disabled'
                    : '' ?>>

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
                            $selectedCityId
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




        <div class="col-12">
            <hr class="my-2 mb-3">

            <h2 class="fs-16 fw-semibold mb-1 mt-2 text-secondary-emphasis">
                Gurudwara and References
            </h2>

            <p class="text-muted fs-12 mb-0">
                These details will help with family verification.
            </p>
        </div>

        <div class="col-12">
            <label
                for="nearestGurudwara"
                class="form-label">

                Nearest Gurudwara
                <span class="text-danger">*</span>
            </label>

            <input
                type="text"
                id="nearestGurudwara"
                name="nearest_gurudwara"
                class="form-control"
                value="<?= esc(
                            $fieldValue(
                                'nearest_gurudwara',
                                $details['nearest_gurudwara'] ?? ''
                            ),
                            'attr'
                        ) ?>"
                placeholder="Enter Gurudwara name or location"
                maxlength="300"
                data-error-required="Please enter the nearest Gurudwara name or location."
                data-error-maxlength="Nearest Gurudwara cannot exceed 200 characters."
                autocomplete="off"
                aria-describedby="nearestGurudwaraError">

            <?= view(
                'Components/Forms/FieldError',
                [
                    'field' => 'nearest_gurudwara',
                    'errorId' => 'nearestGurudwaraError',
                    'errors' => $errors,
                ]
            ) ?>
        </div>

        <div class="col-12 col-md-6">
            <label
                for="referencePerson1"
                class="form-label">

                Name/Contact of 1st Reference Person

                <span class="text-muted fw-normal">
                    (Optional)
                </span>
            </label>

            <input
                type="text"
                id="referencePerson1"
                name="reference_person_1"
                class="form-control"
                value="<?= esc(
                            $fieldValue(
                                'reference_person_1',
                                $details['reference_person_1'] ?? ''
                            ),
                            'attr'
                        ) ?>"
                placeholder="Enter name and contact details"
                maxlength="200"
                data-error-maxlength="First reference person details cannot exceed 200 characters."
                autocomplete="off"
                aria-describedby="referencePerson1Error">

            <?= view(
                'Components/Forms/FieldError',
                [
                    'field' => 'reference_person_1',
                    'errorId' => 'referencePerson1Error',
                    'errors' => $errors,
                ]
            ) ?>
        </div>

        <div class="col-12 col-md-6">
            <label
                for="referencePerson2"
                class="form-label">

                Name/Contact of 2nd Reference Person

                <span class="text-muted fw-normal">
                    (Optional)
                </span>
            </label>

            <input
                type="text"
                id="referencePerson2"
                name="reference_person_2"
                class="form-control"
                value="<?= esc(
                            $fieldValue(
                                'reference_person_2',
                                $details['reference_person_2'] ?? ''
                            ),
                            'attr'
                        ) ?>"
                placeholder="Enter name and contact details"
                maxlength="200"
                data-error-maxlength="Second reference person details cannot exceed 200 characters."
                autocomplete="off"
                aria-describedby="referencePerson1Error">

            <?= view(
                'Components/Forms/FieldError',
                [
                    'field' => 'reference_person_2',
                    'errorId' => 'referencePerson2Error',
                    'errors' => $errors,
                ]
            ) ?>
        </div>

        <?php
        $fieldOfficerAssigned =
            !empty($details['field_officer_id']
                ?? null);

        $fieldOfficerCode =
            $fieldOfficerAssigned
            ? (string) (
                $details['field_officer_code'] ?? ''
            )
            : $fieldValue(
                'field_officer_code',
                ''
            );

        $fieldOfficerName =
            $fieldOfficerAssigned
            ? (string) (
                $details['field_officer_name'] ?? ''
            )
            : '';
        ?>

        <div class="col-12">
            <hr class="my-2 mb-3">

            <h2 class="fs-16 fw-semibold mb-1 mt-2 text-secondary-emphasis">
                SAK Volunteer
            </h2>

            <p class="text-muted fs-13 mb-0">
                Optional. If you enter a SAK Volunteer ID,
                it must be verified before saving.
            </p>
        </div>

        <div class="col-12 col-md-6">
            <label
                for="fieldOfficerCode"
                class="form-label">

                SAK Volunteer ID

                <span class="text-muted fw-normal">
                    (Optional)
                </span>
            </label>

            <?php if ($fieldOfficerAssigned): ?>

                <input
                    type="text"
                    id="fieldOfficerCode"
                    name="field_officer_code"
                    class="form-control"
                    value="<?= esc(
                                $fieldOfficerCode,
                                'attr'
                            ) ?>"
                    readonly
                    aria-readonly="true">

                <div class="form-text color-pink">
                    SAK Volunteer ID cannot be changed
                    after it has been saved.
                </div>

            <?php else: ?>

                <div class="input-group has-validation">

                    <input
                        type="text"
                        id="fieldOfficerCode"
                        name="field_officer_code"
                        class="form-control"
                        value="<?= esc(
                                    $fieldOfficerCode,
                                    'attr'
                                ) ?>"
                        placeholder="Example: FOSAK000123"
                        maxlength="11"
                        pattern="FOSAK[0-9]{6}"
                        autocomplete="off"
                        data-error-pattern="Please enter a valid SAK Volunteer ID."
                        data-verify-url="<?= esc(
                                                url_to(
                                                    'web.profile.family-details.field-officer.verify'
                                                ),
                                                'attr'
                                            ) ?>"
                        aria-describedby="fieldOfficerHelp fieldOfficerVerificationMessage field_officer_codeError">

                    <button
                        type="button"
                        id="verifyFieldOfficerButton"
                        class="btn btn-primary">
                        Verify
                    </button>

                </div>

                <?= view(
                    'Components/Forms/FieldError',
                    [
                        'field' =>
                        'field_officer_code',

                        'errorId' =>
                        'field_officer_codeError',

                        'errors' =>
                        $errors,
                    ]
                ) ?>

                <div
                    id="fieldOfficerHelp"
                    class="form-text color-pink">
                    Enter the Code provided by your SAK Volunteer.
                </div>

                <div
                    id="fieldOfficerVerificationMessage"
                    class="form-text"
                    aria-live="polite">
                </div>

            <?php endif; ?>
        </div>

        <div class="col-12 col-md-6">
            <label
                for="fieldOfficerName"
                class="form-label">

                SAK Volunteer Name
            </label>

            <input
                type="text"
                id="fieldOfficerName"
                class="form-control"
                value="<?= esc(
                            $fieldOfficerName,
                            'attr'
                        ) ?>"
                placeholder="Name appears after verification"
                readonly
                aria-readonly="true">

            <?php if ($fieldOfficerAssigned): ?>
                <div class="form-text text-success">
                    SAK Volunteer verified and saved.
                </div>
            <?php endif; ?>
        </div>
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
                    <?= $isJourney
                        ? 'Save and Continue'
                        : 'Save' ?>
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