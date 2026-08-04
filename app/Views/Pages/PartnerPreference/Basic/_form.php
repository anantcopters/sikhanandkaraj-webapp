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
    $preference['is_age_compulsory'] ?? false,

    BasicPreferenceItem::HEIGHT =>
    $preference['is_height_compulsory'] ?? false,

    BasicPreferenceItem::MARITAL_STATUS =>
    $preference['is_marital_status_compulsory'] ?? false,

    BasicPreferenceItem::HAVE_CHILDREN =>
    $preference['is_have_children_compulsory'] ?? false,

    BasicPreferenceItem::MOTHER_TONGUE =>
    $preference['is_mother_tongue_compulsory'] ?? false,

    BasicPreferenceItem::PHYSICAL_STATUS =>
    $preference['is_physical_status_compulsory'] ?? false,

    BasicPreferenceItem::EATING_HABITS =>
    $preference['is_eating_habit_compulsory'] ?? false,

    BasicPreferenceItem::DRINKING_HABITS =>
    $preference['is_drinking_habit_compulsory'] ?? false,

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
            <?php foreach (
                [
                    'height_from_id' =>
                    'Height From',
                    'height_to_id' =>
                    'Height To',
                ] as $field => $label
            ): ?>
                <div class="col-12 col-sm-6">
                    <label
                        for="<?= esc(
                                    $field,
                                    'attr'
                                ) ?>"
                        class="form-labelm">
                        <?= esc($label) ?>
                        <span class="text-danger">*</span>
                    </label>

                    <select
                        id="<?= esc($field, 'attr') ?>"
                        name="<?= esc($field, 'attr') ?>"
                        class="form-select <?= isset(
                                                $errors[$field]
                                            )
                                                ? 'is-invalid'
                                                : '' ?>"
                        data-choice
                        data-choice-position="bottom"
                        data-error-required="
                            Please select a height."
                        required>
                        <option value="">
                            Select height
                        </option>

                        <?php foreach (
                            $heights as $height
                        ): ?>
                            <option
                                value="<?= esc(
                                            (string) $height['id'],
                                            'attr'
                                        ) ?>"
                                <?= $fieldValue(
                                    $field,
                                    $preference[$field] ?? ''
                                ) ===
                                    (string) $height['id']
                                    ? 'selected'
                                    : '' ?>>
                                <?= esc(
                                    (string)
                                    $height['name']
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <?= view(
                        'Components/Forms/FieldError',
                        [
                            'field' => $field,
                            'errorId' =>
                            $field . 'Error',
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
            <div class="form-check">
                <input
                    type="checkbox"
                    class="form-check-input"
                    id="isCompulsory"
                    name="is_compulsory"
                    value="1"
                    <?= $isCompulsory
                        ? 'checked'
                        : '' ?>>

                <label
                    class="form-check-label"
                    for="isCompulsory">
                    <?= esc($compulsoryText) ?>
                </label>
            </div>
        </div>


        <div class="col-12">
            <div
                id="preferenceRangeError"
                class="invalid-feedback d-block"
                aria-live="polite"></div>
        </div>


        <div class="col-12">
            <div
                class="d-flex flex-column
                    flex-sm-row gap-2
                    justify-content-end">
                <a
                    href="<?= url_to(
                                'web.partner-preference'
                            ) ?>#basic"
                    class="btn btn-light">
                    Cancel
                </a>

                <button
                    type="submit"
                    class="btn btn-primary
                        registration-form__submit"
                    id="savePartnerPreferenceButton">
                    <span
                        class="registration-submit__label">
                        Save Preference
                    </span>

                    <span
                        class="registration-submit__loading
                            d-none"
                        aria-hidden="true">
                        <span
                            class="spinner-border
                                spinner-border-sm me-2"
                            role="status"></span>
                        Saving...
                    </span>
                </button>
            </div>
        </div>

    </div>
</form>