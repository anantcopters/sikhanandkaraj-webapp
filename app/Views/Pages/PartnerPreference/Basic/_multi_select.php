<?php

declare(strict_types=1);

/**
 * @var string                     $field
 * @var string                     $label
 * @var string                     $placeholder
 * @var list<array<string, mixed>> $options
 * @var list<int>                  $selectedValues
 * @var array<string, string>      $errors
 */

$fieldId = lcfirst(
    str_replace(
        ' ',
        '',
        ucwords(
            str_replace('_', ' ', $field)
        )
    )
);
?>

<div class="col-12">
    <label
        for="<?= esc($fieldId, 'attr') ?>"
        class="form-labelm">
        <?= esc($label) ?>
        <span class="text-danger">*</span>
    </label>

    <select
        id="<?= esc($fieldId, 'attr') ?>"
        name="<?= esc($field, 'attr') ?>[]"
        class="form-select <?= isset($errors[$field])
                                ? 'is-invalid'
                                : '' ?>"
        data-choice
        data-choice-remove-item
        data-choice-position="bottom"
        data-choice-search-placeholder="Search"
        data-error-required="<?= esc(
                                    'Please select at least one '
                                        . strtolower($label) . '.',
                                    'attr'
                                ) ?>"
        multiple
        required>

        <?php foreach ($options as $option): ?>
            <?php
            $optionId = (int) $option['id'];
            ?>

            <option
                value="<?= esc(
                            (string) $optionId,
                            'attr'
                        ) ?>"
                <?= in_array(
                    $optionId,
                    $selectedValues,
                    true
                )
                    ? 'selected'
                    : '' ?>>
                <?= esc((string) $option['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <?= view(
        'Components/Forms/FieldError',
        [
            'field' => $field,
            'errorId' => $fieldId . 'Error',
            'errors' => $errors,
        ]
    ) ?>
</div>