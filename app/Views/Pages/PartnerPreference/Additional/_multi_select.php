<?php

declare(strict_types=1);

/**
 * Reusable searchable multi-select with a Select All option.
 *
 * @var string                     $field
 * @var string                     $label
 * @var string                     $placeholder
 * @var list<array<string, mixed>> $options
 * @var string                     $optionValueKey
 * @var string                     $optionLabelKey
 * @var list<int|string>           $selectedValues
 * @var array<string, string>      $errors
 * @var bool|null                  $showSelectAll
 * @var bool|null                  $disabled
 */

$resolvedField = trim(
    (string) ($field ?? '')
);

$resolvedLabel = trim(
    (string) ($label ?? '')
);

$resolvedPlaceholder = trim(
    (string) ($placeholder ?? '')
);

$resolvedOptions = is_array(
    $options ?? null
)
    ? $options
    : [];

$resolvedSelectedValues = is_array(
    $selectedValues ?? null
)
    ? $selectedValues
    : [];

$resolvedErrors = is_array(
    $errors ?? null
)
    ? $errors
    : [];

$resolvedValueKey = trim(
    (string) ($optionValueKey ?? 'id')
);

$resolvedLabelKey = trim(
    (string) ($optionLabelKey ?? 'name')
);

$resolvedShowSelectAll =
    ($showSelectAll ?? true) === true;

$isDisabled =
    ($disabled ?? false) === true;

$fieldId = lcfirst(
    str_replace(
        ' ',
        '',
        ucwords(
            str_replace(
                '_',
                ' ',
                $resolvedField
            )
        )
    )
);

$selectAllId =
    $fieldId . 'SelectAll';

$selectedStrings = array_values(
    array_map(
        static fn(mixed $value): string =>
        (string) $value,
        $resolvedSelectedValues
    )
);
?>

<div class="col-12">
    <label
        for="<?= esc(
                    $fieldId,
                    'attr'
                ) ?>"
        class="form-labelm">

        <?= esc($resolvedLabel) ?>

        <span class="text-danger">*</span>
    </label>

    <?php if ($resolvedShowSelectAll): ?>
        <div
            class="d-flex align-items-center
                justify-content-end mb-2">

            <div class="form-check">
                <input
                    type="checkbox"
                    class="form-check-input"
                    id="<?= esc(
                            $selectAllId,
                            'attr'
                        ) ?>"
                    data-select-all-target="<?= esc(
                                                $fieldId,
                                                'attr'
                                            ) ?>"
                    <?= $isDisabled
                        ? 'disabled'
                        : '' ?>>

                <label
                    class="form-check-label
                        fs-13 fw-medium"
                    for="<?= esc(
                                $selectAllId,
                                'attr'
                            ) ?>">

                    Select All
                </label>
            </div>
        </div>
    <?php endif; ?>

    <select
        id="<?= esc(
                $fieldId,
                'attr'
            ) ?>"
        name="<?= esc(
                    $resolvedField,
                    'attr'
                ) ?>[]"
        class="form-select <?= isset(
                                $resolvedErrors[$resolvedField]
                            )
                                ? 'is-invalid'
                                : '' ?>"
        data-choice
        data-choice-search="true"
        data-choice-position="bottom"
        data-choice-search-placeholder="Search"
        data-error-required="<?= esc(
                                    'Please select at least one '
                                        . strtolower(
                                            $resolvedLabel
                                        )
                                        . '.',
                                    'attr'
                                ) ?>"
        <?= $isDisabled
            ? 'disabled'
            : '' ?>
        multiple
        required>

        <?php foreach (
            $resolvedOptions as $option
        ): ?>
            <?php
            $value = trim(
                (string) (
                    $option[$resolvedValueKey] ?? ''
                )
            );

            $text = trim(
                (string) (
                    $option[$resolvedLabelKey] ?? ''
                )
            );
            ?>

            <?php if (
                $value !== ''
                && $text !== ''
            ): ?>
                <option
                    value="<?= esc(
                                $value,
                                'attr'
                            ) ?>"
                    <?= in_array(
                        $value,
                        $selectedStrings,
                        true
                    )
                        ? 'selected'
                        : '' ?>>

                    <?= esc($text) ?>
                </option>
            <?php endif; ?>
        <?php endforeach; ?>
    </select>

    <?= view(
        'Components/Forms/FieldError',
        [
            'field' =>
            $resolvedField,

            'errorId' =>
            $fieldId . 'Error',

            'errors' =>
            $resolvedErrors,
        ]
    ) ?>
</div>