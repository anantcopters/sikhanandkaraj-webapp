<?php

declare(strict_types=1);

use App\Support\BooleanValue;
use App\Support\PartnerPreference\AdditionalPreferenceItem;

/**
 * Additional Partner Preference form.
 *
 * @var string                     $item
 * @var array<string, mixed>       $masterData
 * @var array<string, list<int|string>> $selectedValues
 * @var array<string, mixed>|null  $religiousPreference
 * @var array<string, mixed>|null  $professionalPreference
 * @var array<string, mixed>|null  $locationPreference
 * @var array<string, mixed>|null  $specialRequest
 * @var array<string, string>      $validationErrors
 */

$resolvedItem = isset($item)
    ? trim((string) $item)
    : '';

$errors = is_array($validationErrors ?? null)
    ? $validationErrors
    : [];

$resolvedMasterData = is_array($masterData ?? null)
    ? $masterData
    : [];

$resolvedSelectedValues = is_array(
    $selectedValues ?? null
)
    ? $selectedValues
    : [];

$religious = is_array(
    $religiousPreference ?? null
)
    ? $religiousPreference
    : [];

$professional = is_array(
    $professionalPreference ?? null
)
    ? $professionalPreference
    : [];

$location = is_array(
    $locationPreference ?? null
)
    ? $locationPreference
    : [];

$special = is_array(
    $specialRequest ?? null
)
    ? $specialRequest
    : [];

/*
 * Resolve all master datasets locally before rendering.
 */
$communities = is_array(
    $resolvedMasterData['communities'] ?? null
)
    ? $resolvedMasterData['communities']
    : [];

$educations = is_array(
    $resolvedMasterData['educations'] ?? null
)
    ? $resolvedMasterData['educations']
    : [];

$employmentTypes = is_array(
    $resolvedMasterData['employmentTypes'] ?? null
)
    ? $resolvedMasterData['employmentTypes']
    : [];

$occupations = is_array(
    $resolvedMasterData['occupations'] ?? null
)
    ? $resolvedMasterData['occupations']
    : [];

$annualIncomes = is_array(
    $resolvedMasterData['annualIncomes'] ?? null
)
    ? $resolvedMasterData['annualIncomes']
    : [];

$states = is_array(
    $resolvedMasterData['states'] ?? null
)
    ? $resolvedMasterData['states']
    : [];

$cities = is_array(
    $resolvedMasterData['cities'] ?? null
)
    ? $resolvedMasterData['cities']
    : [];

/*
 * Resolve saved multi-select values locally.
 */
$selectedCommunities = is_array(
    $resolvedSelectedValues['communities'] ?? null
)
    ? $resolvedSelectedValues['communities']
    : [];

$selectedEducations = is_array(
    $resolvedSelectedValues['educations'] ?? null
)
    ? $resolvedSelectedValues['educations']
    : [];

$selectedEmploymentTypes = is_array(
    $resolvedSelectedValues['employmentTypes'] ?? null
)
    ? $resolvedSelectedValues['employmentTypes']
    : [];

$selectedOccupations = is_array(
    $resolvedSelectedValues['occupations'] ?? null
)
    ? $resolvedSelectedValues['occupations']
    : [];


$selectedAnnualIncomes = is_array(
    $resolvedSelectedValues['annualIncomes'] ?? null
)
    ? $resolvedSelectedValues['annualIncomes']
    : [];

$selectedStates = is_array(
    $resolvedSelectedValues['states'] ?? null
)
    ? $resolvedSelectedValues['states']
    : [];

$selectedCities = is_array(
    $resolvedSelectedValues['cities'] ?? null
)
    ? $resolvedSelectedValues['cities']
    : [];

/**
 * Resolve submitted array input before stored values.
 *
 * @param list<int|string> $storedValues
 *
 * @return list<int|string>
 */
$arrayValue = static function (
    string $field,
    array $storedValues = []
): array {
    $oldValue = old(
        $field,
        null,
        false
    );

    if (!is_array($oldValue)) {
        return array_values($storedValues);
    }

    return array_values(
        array_filter(
            array_map(
                static function (
                    mixed $value
                ): int|string|null {
                    $normalizedValue = trim(
                        (string) $value
                    );

                    if ($normalizedValue === '') {
                        return null;
                    }

                    return ctype_digit(
                        $normalizedValue
                    )
                        ? (int) $normalizedValue
                        : $normalizedValue;
                },
                $oldValue
            ),
            static fn(
                int|string|null $value
            ): bool => $value !== null
        )
    );
};

/**
 * Resolve submitted scalar input before stored values.
 */
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

/*
 * Resolve the saved matching mode for the active item.
 */
$strictStored = match ($resolvedItem) {
    AdditionalPreferenceItem::COMMUNITY =>
    $religious['community_match_mode'] ?? false,

    AdditionalPreferenceItem::EDUCATION =>
    $professional['education_match_mode'] ?? false,

    AdditionalPreferenceItem::EMPLOYED_IN =>
    $professional['employed_in_match_mode'] ?? false,

    AdditionalPreferenceItem::OCCUPATION =>
    $professional['occupation_match_mode'] ?? false,

    AdditionalPreferenceItem::ANNUAL_INCOME =>
    $professional['annual_income_match_mode'] ?? false,

    AdditionalPreferenceItem::LOCATION =>
    $location['location_match_mode'] ?? false,

    default => false,
};

$strictMode = $fieldValue(
    'is_compulsory',
    BooleanValue::fromDatabase(
        $strictStored
    )
        ? '1'
        : '0'
) === '1';

$formAction = url_to(
    'web.partner-preference.item.update',
    $resolvedItem
);
?>

<form
    method="post"
    action="<?= esc(
                $formAction,
                'attr'
            ) ?>"
    id="partnerPreferenceAdditionalForm"
    data-preference-item="<?= esc(
                                $resolvedItem,
                                'attr'
                            ) ?>"
    data-validate
    novalidate>

    <?= csrf_field() ?>

    <div class="row g-3">

        <?php if (
            $resolvedItem ===
            AdditionalPreferenceItem::COMMUNITY
        ): ?>
            <?= view(
                'Pages/PartnerPreference/'
                    . 'Additional/_multi_select',
                [
                    'field' => 'community_ids',
                    'label' => 'Community',
                    'placeholder' =>
                    'Select communities',
                    'options' => $communities,
                    'optionValueKey' => 'id',
                    'optionLabelKey' => 'name',
                    'selectedValues' =>
                    $arrayValue(
                        'community_ids',
                        $selectedCommunities
                    ),
                    'errors' => $errors,
                ]
            ) ?>
        <?php endif; ?>


        <?php if (
            $resolvedItem ===
            AdditionalPreferenceItem::EDUCATION
        ): ?>
            <?= view(
                'Pages/PartnerPreference/'
                    . 'Additional/_multi_select',
                [
                    'field' => 'education_ids',
                    'label' => 'Education',
                    'placeholder' =>
                    'Select education qualifications',
                    'options' => $educations,
                    'optionValueKey' => 'id',
                    'optionLabelKey' => 'name',
                    'selectedValues' =>
                    $arrayValue(
                        'community_ids',
                        $selectedEducations
                    ),
                    'errors' => $errors,
                ]
            ) ?>
        <?php endif; ?>


        <?php if (
            $resolvedItem ===
            AdditionalPreferenceItem::EMPLOYED_IN
        ): ?>
            <?= view(
                'Pages/PartnerPreference/'
                    . 'Additional/_multi_select',
                [
                    'field' =>
                    'employed_in_values',
                    'label' =>
                    'Employed In',
                    'placeholder' =>
                    'Select employment types',
                    'options' =>
                    $employmentTypes,
                    'optionValueKey' =>
                    'value',
                    'optionLabelKey' =>
                    'label',
                    'selectedValues' =>
                    $arrayValue(
                        'community_ids',
                        $selectedEmploymentTypes
                    ),
                    'errors' => $errors,
                ]
            ) ?>
        <?php endif; ?>


        <?php if (
            $resolvedItem ===
            AdditionalPreferenceItem::OCCUPATION
        ): ?>
            <?= view(
                'Pages/PartnerPreference/'
                    . 'Additional/_multi_select',
                [
                    'field' => 'occupation_ids',
                    'label' => 'Occupation',
                    'placeholder' =>
                    'Select occupations',
                    'options' => $occupations,
                    'optionValueKey' => 'id',
                    'optionLabelKey' => 'name',
                    'selectedValues' =>
                    $arrayValue(
                        'community_ids',
                        $selectedOccupations
                    ),
                    'errors' => $errors,
                ]
            ) ?>
        <?php endif; ?>


        <?php if (
            $resolvedItem ===
            AdditionalPreferenceItem::ANNUAL_INCOME
        ): ?>
            <?= view(
                'Pages/PartnerPreference/'
                    . 'Additional/_multi_select',
                [
                    'field' =>
                    'annual_income_ids',

                    'label' =>
                    'Annual Income',

                    'placeholder' =>
                    'Select annual income options',

                    'options' =>
                    $annualIncomes,

                    'optionValueKey' =>
                    'id',

                    'optionLabelKey' =>
                    'display_name',

                    'selectedValues' =>
                    $arrayValue(
                        'annual_income_ids',
                        $selectedAnnualIncomes
                    ),

                    'showSelectAll' =>
                    false,

                    'errors' =>
                    $errors,
                ]
            ) ?>

            <div class="col-12">
                <div class="form-text text-secondary">
                    Select one or more acceptable annual-income
                    brackets.
                </div>
            </div>
        <?php endif; ?>


        <?php if (
            $resolvedItem ===
            AdditionalPreferenceItem::LOCATION
        ): ?>
            <?php
            $resolvedStateValues = $arrayValue(
                'state_ids',
                $selectedStates
            );

            $resolvedCityValues = $arrayValue(
                'city_ids',
                $selectedCities
            );

            $hasSelectedStates =
                $resolvedStateValues !== [];
            ?>

            <div class="col-12">
                <div
                    class="alert alert-light
                border mb-0 fs-14">

                    Select one or more preferred states.
                    Cities belonging to the selected states
                    will appear below.
                </div>
            </div>

            <?= view(
                'Pages/PartnerPreference/'
                    . 'Additional/_multi_select',
                [
                    'field' =>
                    'state_ids',

                    'label' =>
                    'Preferred States',

                    'placeholder' =>
                    'Select states',

                    'options' =>
                    $states,

                    'optionValueKey' =>
                    'id',

                    'optionLabelKey' =>
                    'name',

                    'selectedValues' =>
                    $resolvedStateValues,

                    'showSelectAll' =>
                    true,

                    'disabled' =>
                    false,

                    'errors' =>
                    $errors,
                ]
            ) ?>

            <div class="col-12">
                <hr class="my-1">
            </div>

            <?= view(
                'Pages/PartnerPreference/'
                    . 'Additional/_multi_select',
                [
                    'field' =>
                    'city_ids',

                    'label' =>
                    'Preferred Cities',

                    'placeholder' =>
                    'Select cities',

                    'options' =>
                    $cities,

                    'optionValueKey' =>
                    'id',

                    'optionLabelKey' =>
                    'name',

                    'selectedValues' =>
                    $resolvedCityValues,

                    'showSelectAll' =>
                    true,

                    /*
             * City remains disabled until at least
             * one state has been selected.
             */
                    'disabled' =>
                    !$hasSelectedStates,

                    'errors' =>
                    $errors,
                ]
            ) ?>

            <input
                type="hidden"
                id="partnerCitiesUrl"
                value="<?= esc(
                            url_to(
                                'web.partner-preference'
                                    . '.master.cities'
                            ),
                            'attr'
                        ) ?>">
        <?php endif; ?>


        <?php if (
            $resolvedItem ===
            AdditionalPreferenceItem::SPECIAL_REQUEST
        ): ?>
            <div class="col-12">
                <label
                    for="specialRequestText"
                    class="form-labelm">

                    Any Special Request
                    <span class="text-danger">*</span>
                </label>

                <textarea
                    id="specialRequestText"
                    name="request_text"
                    class="form-control <?= isset(
                                            $errors['request_text']
                                        )
                                            ? 'is-invalid'
                                            : '' ?>"
                    rows="5"
                    minlength="10"
                    maxlength="1000"
                    data-error-required="
                        Please enter your special request."
                    required><?= esc(
                                    $fieldValue(
                                        'request_text',
                                        $special['request_text'] ?? ''
                                    )
                                ) ?></textarea>

                <?= view(
                    'Components/Forms/FieldError',
                    [
                        'field' =>
                        'request_text',

                        'errorId' =>
                        'specialRequestTextError',

                        'errors' => $errors,
                    ]
                ) ?>

                <div
                    class="form-text color-pink">

                    Maximum 1000 characters. Do not add
                    phone numbers, email addresses or other
                    direct contact information.
                </div>
            </div>
        <?php endif; ?>


        <?php if (
            $resolvedItem !==
            AdditionalPreferenceItem::SPECIAL_REQUEST
        ): ?>
            <div class="col-12">
                <div
                    class="border rounded p-3
                        bg-light mt-2">

                    <div
                        class="fw-semibold
                            text-dark mb-3">

                        Matching Preference
                    </div>

                    <div class="form-check mb-2">
                        <input
                            class="form-check-input"
                            type="radio"
                            name="is_compulsory"
                            id="preferredMatch"
                            value="0"
                            <?= !$strictMode
                                ? 'checked'
                                : '' ?>
                            required>

                        <label
                            class="form-check-label"
                            for="preferredMatch">

                            Prefer profiles matching this preference

                            <span
                                class="badge
                                    bg-success-subtle
                                    text-success ms-2">

                                Recommended
                            </span>
                        </label>
                    </div>

                    <div class="form-check">
                        <input
                            class="form-check-input"
                            type="radio"
                            name="is_compulsory"
                            id="strictMatch"
                            value="1"
                            <?= $strictMode
                                ? 'checked'
                                : '' ?>
                            required>

                        <label
                            class="form-check-label"
                            for="strictMatch">

                            Show only profiles matching this preference

                            <span
                                class="badge
                                    bg-danger-subtle
                                    text-danger ms-2">

                                Strict Match
                            </span>
                        </label>
                    </div>

                    <div class="form-text mt-3">
                        Recommended provides more matching
                        profiles. Strict Match only shows
                        profiles that satisfy this preference.
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <div class="row g-2 mt-4">
        <div
            class="col-12 col-sm-6 col-md-3
                ms-md-auto order-2 order-sm-1">

            <a
                href="<?= url_to(
                            'web.partner-preference'
                        ) ?>"
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
                class="btn registration-form__submit
                    fs-14 fw-semibold text-uppercase"
                id="saveAdditionalPreferenceButton">

                <span
                    class="registration-submit__label">
                    Save
                </span>

                <span
                    class="registration-submit__loading
                        d-none"
                    aria-hidden="true">

                    <span
                        class="spinner-border
                            spinner-border-sm"
                        role="status"
                        aria-hidden="true"></span>

                    <span>Saving...</span>
                </span>
            </button>
        </div>
    </div>
</form>