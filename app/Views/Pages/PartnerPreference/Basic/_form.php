<?php

declare(strict_types=1);

use App\Support\BooleanValue;
use App\Support\PartnerPreference\BasicPreferenceItem;

/**
 * @var string                    $item
 * @var array<string, mixed>      $preference
 * @var array<string, list<int>>  $selectedIds
 * @var array<string, mixed>      $masterData
 * @var string                    $compulsoryText
 * @var array<string, string>     $validationErrors
 */

$preference = is_array($preference ?? null)
    ? $preference
    : [];

$selectedIds = is_array($selectedIds ?? null)
    ? $selectedIds
    : [];

$masterData = is_array($masterData ?? null)
    ? $masterData
    : [];

$errors = is_array($validationErrors ?? null)
    ? $validationErrors
    : [];

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

$arrayValue = static function (
    string $field,
    array $storedValue = []
): array {
    $oldValue = old(
        $field,
        null,
        false
    );

    if (is_array($oldValue)) {
        return array_values(
            array_map(
                static fn(mixed $value): int =>
                (int) $value,
                $oldValue
            )
        );
    }

    return array_values(
        array_map(
            static fn(mixed $value): int =>
            (int) $value,
            $storedValue
        )
    );
};

$selectedMultiValues = match ($item) {
    BasicPreferenceItem::MOTHER_TONGUE =>
    $arrayValue(
        'mother_tongue_ids',
        $selectedIds['motherTongues'] ?? []
    ),

    BasicPreferenceItem::EATING_HABITS =>
    $arrayValue(
        'eating_habit_ids',
        $selectedIds['eatingHabits'] ?? []
    ),

    BasicPreferenceItem::DRINKING_HABITS =>
    $arrayValue(
        'drinking_habit_ids',
        $selectedIds['drinkingHabits'] ?? []
    ),

    default => [],
};

$storedCompulsory = match ($item) {
    BasicPreferenceItem::AGE =>
    $preference['age_match_mode'] ?? false,

    BasicPreferenceItem::HEIGHT =>
    $preference['height_match_mode'] ?? false,

    BasicPreferenceItem::MARITAL_STATUS =>
    $preference['marital_status_match_mode'] ?? false,

    BasicPreferenceItem::HAVE_CHILDREN =>
    $preference['have_children_match_mode'] ?? false,

    BasicPreferenceItem::MOTHER_TONGUE =>
    $preference['mother_tongue_match_mode'] ?? false,

    BasicPreferenceItem::PHYSICAL_STATUS =>
    $preference['physical_status_match_mode'] ?? false,

    BasicPreferenceItem::EATING_HABITS =>
    $preference['eating_habit_match_mode'] ?? false,

    BasicPreferenceItem::DRINKING_HABITS =>
    $preference['drinking_habit_match_mode'] ?? false,

    default => false,
};

$isCompulsory = old(
    'is_compulsory',
    BooleanValue::fromDatabase(
        $storedCompulsory
    )
        ? '1'
        : '0',
    false
) === '1';

$formAction = url_to(
    'web.partner-preference.basic.update',
    $item
);

$heights = is_array(
    $masterData['heights'] ?? null
)
    ? $masterData['heights']
    : [];

$maritalStatuses = is_array(
    $masterData['maritalStatuses'] ?? null
)
    ? $masterData['maritalStatuses']
    : [];

$motherTongues = is_array(
    $masterData['motherTongues'] ?? null
)
    ? $masterData['motherTongues']
    : [];

$physicalStatuses = is_array(
    $masterData['physicalStatuses'] ?? null
)
    ? $masterData['physicalStatuses']
    : [];

$eatingHabits = is_array(
    $masterData['eatingHabits'] ?? null
)
    ? $masterData['eatingHabits']
    : [];

$drinkingHabits = is_array(
    $masterData['drinkingHabits'] ?? null
)
    ? $masterData['drinkingHabits']
    : [];
?>

<form
    method="post"
    action="<?= esc($formAction, 'attr') ?>"
    id="partnerPreferenceBasicForm"
    data-preference-item="<?= esc($item, 'attr') ?>"
    data-validate
    novalidate>

    <?= csrf_field() ?>

    <div class="row g-3">

        <?php if (
            $item === BasicPreferenceItem::AGE
        ): ?>
            <div class="col-12 col-sm-6">
                <label
                    for="ageFrom"
                    class="form-labelm">
                    Age From
                    <span class="text-danger">*</span>
                </label>

                <select
                    id="ageFrom"
                    name="age_from"
                    class="form-select <?= isset(
                                            $errors['age_from']
                                        )
                                            ? 'is-invalid'
                                            : '' ?>"
                    data-choice
                    data-choice-search="false"
                    data-error-required="
                        Please select minimum age."
                    required>
                    <option value="">
                        Select age
                    </option>

                    <?php for (
                        $age = 18;
                        $age <= 80;
                        $age++
                    ): ?>
                        <option
                            value="<?= esc(
                                        (string) $age,
                                        'attr'
                                    ) ?>"
                            <?= $fieldValue(
                                'age_from',
                                $preference['age_from'] ?? ''
                            ) === (string) $age
                                ? 'selected'
                                : '' ?>>
                            <?= esc((string) $age) ?>
                        </option>
                    <?php endfor; ?>
                </select>

                <?= view(
                    'Components/Forms/FieldError',
                    [
                        'field' => 'age_from',
                        'errorId' => 'ageFromError',
                        'errors' => $errors,
                    ]
                ) ?>
            </div>

            <div class="col-12 col-sm-6">
                <label
                    for="ageTo"
                    class="form-labelm">
                    Age To
                    <span class="text-danger">*</span>
                </label>

                <select
                    id="ageTo"
                    name="age_to"
                    class="form-select <?= isset(
                                            $errors['age_to']
                                        )
                                            ? 'is-invalid'
                                            : '' ?>"
                    data-choice
                    data-choice-search="false"
                    data-error-required="
                        Please select maximum age."
                    required>
                    <option value="">
                        Select age
                    </option>

                    <?php for (
                        $age = 18;
                        $age <= 80;
                        $age++
                    ): ?>
                        <option
                            value="<?= esc(
                                        (string) $age,
                                        'attr'
                                    ) ?>"
                            <?= $fieldValue(
                                'age_to',
                                $preference['age_to'] ?? ''
                            ) === (string) $age
                                ? 'selected'
                                : '' ?>>
                            <?= esc((string) $age) ?>
                        </option>
                    <?php endfor; ?>
                </select>

                <?= view(
                    'Components/Forms/FieldError',
                    [
                        'field' => 'age_to',
                        'errorId' => 'ageToError',
                        'errors' => $errors,
                    ]
                ) ?>
            </div>
        <?php endif; ?>


        <?php if (
            $item === BasicPreferenceItem::HEIGHT
        ): ?>
            <?php
            $heightFields = [
                'height_from_id' => [
                    'id' => 'heightFromId',
                    'label' => 'Height From',
                    'placeholder' =>
                    'Select minimum height',
                ],
                'height_to_id' => [
                    'id' => 'heightToId',
                    'label' => 'Height To',
                    'placeholder' =>
                    'Select maximum height',
                ],
            ];
            ?>

            <?php foreach (
                $heightFields as $field => $configuration
            ): ?>
                <?php
                $fieldHasError = isset(
                    $errors[$field]
                );

                $selectedHeightId = $fieldValue(
                    $field,
                    $preference[$field] ?? ''
                );
                ?>

                <div class="col-12 col-sm-6">
                    <label
                        for="<?= esc(
                                    $configuration['id'],
                                    'attr'
                                ) ?>"
                        class="form-labelm">

                        <?= esc(
                            $configuration['label']
                        ) ?>

                        <span class="text-danger">*</span>
                    </label>

                    <select
                        id="<?= esc(
                                $configuration['id'],
                                'attr'
                            ) ?>"
                        name="<?= esc(
                                    $field,
                                    'attr'
                                ) ?>"
                        class="form-select <?= $fieldHasError
                                                ? 'is-invalid'
                                                : '' ?>"
                        <?= $fieldHasError
                            ? 'aria-invalid="true"'
                            : '' ?>
                        aria-describedby="<?= esc(
                                                $configuration['id']
                                                    . 'Error',
                                                'attr'
                                            ) ?>"
                        data-choice
                        data-choice-search="true"
                        data-choice-search-placeholder="Search height"
                        data-choice-position="bottom"
                        data-error-required="<?= esc(
                                                    'Please select '
                                                        . strtolower(
                                                            $configuration['label']
                                                        )
                                                        . '.',
                                                    'attr'
                                                ) ?>"
                        required>

                        <option value="">
                            <?= esc(
                                $configuration['placeholder']
                            ) ?>
                        </option>

                        <?php foreach (
                            $heights as $height
                        ): ?>
                            <?php
                            $heightId = isset(
                                $height['id']
                            )
                                ? (int) $height['id']
                                : 0;

                            $heightDisplayName = trim(
                                (string) (
                                    $height['display_name']
                                    ?? ''
                                )
                            );

                            if (
                                $heightId <= 0
                                || $heightDisplayName === ''
                            ) {
                                continue;
                            }
                            ?>

                            <option
                                value="<?= esc(
                                            (string) $heightId,
                                            'attr'
                                        ) ?>"
                                <?= $selectedHeightId
                                    === (string) $heightId
                                    ? 'selected'
                                    : '' ?>>

                                <?= esc(
                                    $heightDisplayName
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <?= view(
                        'Components/Forms/FieldError',
                        [
                            'field' => $field,

                            'errorId' =>
                            $configuration['id']
                                . 'Error',

                            'errors' => $errors,
                        ]
                    ) ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>


        <?php if (
            $item ===
            BasicPreferenceItem::MARITAL_STATUS
        ): ?>
            <?= view(
                'Pages/PartnerPreference/Basic/'
                    . '_single_select',
                [
                    'field' =>
                    'marital_status_id',
                    'label' =>
                    'Marital Status',
                    'placeholder' =>
                    'Select marital status',
                    'options' =>
                    $maritalStatuses,
                    'selectedValue' =>
                    $fieldValue(
                        'marital_status_id',
                        $preference['marital_status_id'] ?? ''
                    ),
                    'errors' => $errors,
                ]
            ) ?>
        <?php endif; ?>


        <?php if (
            $item ===
            BasicPreferenceItem::HAVE_CHILDREN
        ): ?>
            <div class="col-12">
                <label
                    for="haveChildren"
                    class="form-labelm">
                    Have Children
                    <span class="text-danger">*</span>
                </label>

                <select
                    id="haveChildren"
                    name="have_children"
                    class="form-select <?= isset(
                                            $errors['have_children']
                                        )
                                            ? 'is-invalid'
                                            : '' ?>"
                    data-choice
                    data-choice-search="false"
                    data-error-required="
                        Please select your preference."
                    required>
                    <option value="">
                        Select preference
                    </option>

                    <option
                        value="0"
                        <?= $fieldValue(
                            'have_children',
                            array_key_exists(
                                'have_children',
                                $preference
                            )
                                && $preference['have_children'] !== null
                                ? (
                                    BooleanValue::fromDatabase(
                                        $preference['have_children']
                                    )
                                    ? '1'
                                    : '0'
                                )
                                : ''
                        ) === '0'
                            ? 'selected'
                            : '' ?>>
                        No
                    </option>

                    <option
                        value="1"
                        <?= $fieldValue(
                            'have_children',
                            array_key_exists(
                                'have_children',
                                $preference
                            )
                                && $preference['have_children'] !== null
                                ? (
                                    BooleanValue::fromDatabase(
                                        $preference['have_children']
                                    )
                                    ? '1'
                                    : '0'
                                )
                                : ''
                        ) === '1'
                            ? 'selected'
                            : '' ?>>
                        Yes
                    </option>
                </select>

                <?= view(
                    'Components/Forms/FieldError',
                    [
                        'field' => 'have_children',
                        'errorId' =>
                        'haveChildrenError',
                        'errors' => $errors,
                    ]
                ) ?>
            </div>
        <?php endif; ?>


        <?php if (
            $item ===
            BasicPreferenceItem::MOTHER_TONGUE
        ): ?>
            <?= view(
                'Pages/PartnerPreference/Basic/'
                    . '_multi_select',
                [
                    'field' =>
                    'mother_tongue_ids',
                    'label' =>
                    'Mother Tongue',
                    'placeholder' =>
                    'Select mother tongues',
                    'options' =>
                    $motherTongues,
                    'selectedValues' =>
                    $selectedMultiValues,
                    'errors' => $errors,
                ]
            ) ?>
        <?php endif; ?>


        <?php if (
            $item ===
            BasicPreferenceItem::PHYSICAL_STATUS
        ): ?>
            <?= view(
                'Pages/PartnerPreference/Basic/'
                    . '_single_select',
                [
                    'field' =>
                    'physical_status_id',
                    'label' =>
                    'Physical Status',
                    'placeholder' =>
                    'Select physical status',
                    'options' =>
                    $physicalStatuses,
                    'selectedValue' =>
                    $fieldValue(
                        'physical_status_id',
                        $preference['physical_status_id'] ?? ''
                    ),
                    'errors' => $errors,
                ]
            ) ?>
        <?php endif; ?>


        <?php if (
            $item ===
            BasicPreferenceItem::EATING_HABITS
        ): ?>
            <?= view(
                'Pages/PartnerPreference/Basic/'
                    . '_multi_select',
                [
                    'field' =>
                    'eating_habit_ids',
                    'label' =>
                    'Eating Habits',
                    'placeholder' =>
                    'Select eating habits',
                    'options' =>
                    $eatingHabits,
                    'selectedValues' =>
                    $selectedMultiValues,
                    'errors' => $errors,
                ]
            ) ?>
        <?php endif; ?>


        <?php if (
            $item ===
            BasicPreferenceItem::DRINKING_HABITS
        ): ?>
            <?= view(
                'Pages/PartnerPreference/Basic/'
                    . '_multi_select',
                [
                    'field' =>
                    'drinking_habit_ids',
                    'label' =>
                    'Drinking Habits',
                    'placeholder' =>
                    'Select drinking habits',
                    'options' =>
                    $drinkingHabits,
                    'selectedValues' =>
                    $selectedMultiValues,
                    'errors' => $errors,
                ]
            ) ?>
        <?php endif; ?>


        <div class="col-12">

            <div
                class="border rounded p-3 bg-light mt-2">

                <div
                    class="fw-semibold text-dark mb-3">

                    Matching Preference
                </div>

                <?php
                $strictMatch = $isCompulsory;
                ?>

                <div class="form-check mb-2">

                    <input
                        class="form-check-input"
                        type="radio"
                        name="is_compulsory"
                        id="preferredMatch"
                        value="0"
                        <?= !$strictMatch
                            ? 'checked'
                            : '' ?>>

                    <label
                        class="form-check-label"
                        for="preferredMatch">

                        Prefer profiles matching this preference

                        <span
                            class="badge
                        bg-success-subtle
                        text-success
                        ms-2">

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
                        <?= $strictMatch
                            ? 'checked'
                            : '' ?>>

                    <label
                        class="form-check-label"
                        for="strictMatch">

                        Show only profiles matching this preference

                        <span
                            class="badge
                        bg-danger-subtle
                        text-danger
                        ms-2">

                            Strict Match

                        </span>

                    </label>

                </div>

                <div
                    class="form-text color-pink mt-3">

                    Recommended provides more matching profiles,
                    while Strict Match only shows profiles that
                    exactly satisfy this preference.

                </div>

            </div>

        </div>


        <div class="col-12">
            <div
                id="preferenceRangeError"
                class="invalid-feedback d-block"
                aria-live="polite"></div>
        </div>


        <div class="col-12">
            <div class="row g-2 mt-4">
                <div
                    class="col-12 col-sm-6 col-md-3
            ms-md-auto order-2 order-sm-1">

                    <a
                        href="<?= url_to(
                                    'web.partner-preference'
                                ) ?>#basic"
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
                        id="savePartnerPreferenceButton">

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

                            <span>
                                Saving...
                            </span>
                        </span>
                    </button>
                </div>
            </div>
        </div>

    </div>
</form>