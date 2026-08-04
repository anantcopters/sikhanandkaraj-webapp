<?php

declare(strict_types=1);

/**
 * @var string                     $field
 * @var string                     $label
 * @var string                     $placeholder
 * @var list<array<string, mixed>> $options
 * @var string                     $optionValueKey
 * @var string                     $optionLabelKey
 * @var list<int|string>           $selectedValues
 * @var array<string, string>      $errors
 */

$fieldId = lcfirst(
    str_replace(
        ' ',
        '',
        ucwords(
            str_replace(
                '_',
                ' ',
                $field
            )
        )
    )
);

$selectedStrings = array_map(
    static fn(mixed $value): string =>
    (string) $value,
    $selectedValues
);
?>

<div class="col-12">
    <label
        for="<?= esc(
                    $fieldId,
                    'attr'
                ) ?>"
        class="form-labelm">

        <?= esc($label) ?>

        <span class="text-danger">*</span>
    </label>

    <select
        id="<?= esc(
                $fieldId,
                'attr'
            ) ?>"
        name="<?= esc(
                    $field,
                    'attr'
                ) ?>[]"
        class="form-select <?= isset(
                                $errors[$field]
                            )
                                ? 'is-invalid'
                                : '' ?>"
        data-choice
        data-choice-search="true"
        data-choice-position="bottom"
        data-choice-search-placeholder="Search"
        multiple
        required>

        <?php foreach (
            $options as $option
        ): ?>
            <?php
            $value = trim(
                (string) (
                    $option[$optionValueKey] ?? ''
                )
            );

            $text = trim(
                (string) (
                    $option[$optionLabelKey] ?? ''
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
            'field' => $field,

            'errorId' =>
            $fieldId . 'Error',

            'errors' => $errors,
        ]
    ) ?>
</div>