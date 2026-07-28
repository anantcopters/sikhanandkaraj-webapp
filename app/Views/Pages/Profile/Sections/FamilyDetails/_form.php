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
$subcommunities = $options['subcommunities'] ?? [];
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

$selectedCommunityId = $fieldValue(
    'community_id',
    $details['community_id'] ?? ''
);

$selectedSubcommunityId = $fieldValue(
    'subcommunity_id',
    $details['subcommunity_id'] ?? ''
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
                'requiredMessage' =>
                'Please select your family value.',
            ],
            [
                'id' => 'familyTypeId',
                'name' => 'family_type_id',
                'label' => 'Family type',
                'placeholder' => 'Select family type',
                'options' => $familyTypes,
                'stored' => $details['family_type_id'] ?? '',
                'requiredMessage' =>
                'Please select your family type.',
            ],
            [
                'id' => 'familyStatusId',
                'name' => 'family_status_id',
                'label' => 'Family status',
                'placeholder' => 'Select family status',
                'options' => $familyStatuses,
                'stored' => $details['family_status_id'] ?? '',
                'requiredMessage' =>
                'Please select your family status.',
            ],
        ];
        ?>

        <?php foreach ($familyMasterFields as $field): ?>
            <div class="col-12 col-md-4">
                <label
                    for="<?= esc($field['id'], 'attr') ?>"
                    class="form-label fw-medium">

                    <?= esc($field['label']) ?>
                    <span class="text-danger">*</span>
                </label>

                <select
                    id="<?= esc($field['id'], 'attr') ?>"
                    name="<?= esc($field['name'], 'attr') ?>"
                    class="form-select"
                    data-choice
                    data-choice-search="false"
                    data-choice-position="bottom"
                    data-error-required="<?= esc(
                                                $field['requiredMessage'],
                                                'attr'
                                            ) ?>"
                    required>

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

            <h2 class="fs-16 fw-semibold mb-0 mt-2">
                Community Details
            </h2>
        </div>

        <div class="col-12 col-md-6">
            <label
                for="familyCommunityId"
                class="form-label fw-medium">

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
                data-dependent-url-template="<?= esc(
                                                    site_url(
                                                        'profile/master/subcommunities/__PARENT_ID__'
                                                    ),
                                                    'attr'
                                                ) ?>"
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
                for="familySubcommunityId"
                class="form-label fw-medium">

                Sub-community
                <span class="text-danger">*</span>
            </label>

            <select
                id="familySubcommunityId"
                name="subcommunity_id"
                class="form-select"
                data-choice
                data-choice-search="true"
                data-choice-search-placeholder="Search sub-community"
                data-choice-position="bottom"
                data-selected-value="<?= esc(
                                            $selectedSubcommunityId,
                                            'attr'
                                        ) ?>"
                data-error-required="Please select your sub-community."
                required
                <?= $selectedCommunityId === ''
                    ? 'disabled'
                    : '' ?>>

                <option value="">
                    <?= $selectedCommunityId === ''
                        ? 'Select community first'
                        : 'Select sub-community' ?>
                </option>

                <?php foreach ($subcommunities as $subcommunity): ?>
                    <?php
                    $subcommunityId = (string) (
                        $subcommunity['id'] ?? ''
                    );
                    ?>

                    <option
                        value="<?= esc(
                                    $subcommunityId,
                                    'attr'
                                ) ?>"
                        <?= $isSelected(
                            'subcommunity_id',
                            $subcommunityId,
                            $selectedSubcommunityId
                        ) ?>>

                        <?= esc(
                            (string) (
                                $subcommunity['name'] ?? ''
                            )
                        ) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?= view(
                'Components/Forms/FieldError',
                [
                    'field' => 'subcommunity_id',
                    'errorId' => 'subcommunityIdError',
                    'errors' => $errors,
                ]
            ) ?>
        </div>
        <div class="col-12">
            <hr class="my-2 mb-3">

            <h2 class="fs-16 fw-semibold mb-0 mt-2">
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
                    class="form-label fw-medium">

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

        <div class="col-12">
            <hr class="my-2 mb-3">

            <h2 class="fs-16 fw-semibold mb-0 mt-2">
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
                data-dependent-url-template="<?= esc(
                                                    site_url(
                                                        'profile/master/cities/__PARENT_ID__'
                                                    ),
                                                    'attr'
                                                ) ?>"
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