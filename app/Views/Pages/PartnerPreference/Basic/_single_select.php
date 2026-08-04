<?php

declare(strict_types=1);

/**
 * @var string                         $field
 * @var string                         $label
 * @var string                         $placeholder
 * @var list<array<string, mixed>>     $options
 * @var string                         $selectedValue
 * @var array<string, string>          $errors
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
        name="<?= esc($field, 'attr') ?>"
        class="form-select <?= isset($errors[$field])
                                ? 'is-invalid'
                                : '' ?>"
        data-choice
        data-choice-position="bottom"
        data-error-required="<?= esc(
                                    'Please select ' . strtolower($label) . '.',
                                    'attr'
                                ) ?>"
        required>
        <option value="">
            <?= esc($placeholder) ?>
        </option>

        <?php foreach ($options as $option): ?>
            <option
                value="<?= esc(
                            (string) $option['id'],
                            'attr'
                        ) ?>"
                <?= $selectedValue ===
                    (string) $option['id']
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