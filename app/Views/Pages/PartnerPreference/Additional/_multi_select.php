<?php

declare(strict_types=1);

/**
 * Reusable searchable multi-select.
 *
 * Used by:
 *
 * - Partner Preference;
 * - Member Search.
 *
 * Supports flat and grouped master data.
 *
 * "Any" keeps every available option selected internally while presenting
 * one compact value to the member.
 *
 * Partner Preference remains required by default.
 * Search explicitly passes required=false.
 *
 * @var string                          $field
 * @var string                          $label
 * @var string                          $placeholder
 * @var list<array<string, mixed>>|null $options
 * @var list<array<string, mixed>>|null $groups
 * @var string|null                     $groupLabelKey
 * @var string|null                     $groupItemsKey
 * @var string                          $optionValueKey
 * @var string                          $optionLabelKey
 * @var list<int|string>                $selectedValues
 * @var array<string, string>           $errors
 * @var bool|null                       $showSelectAll
 * @var bool|null                       $disabled
 * @var bool|null                       $required
 */

/*
 * --------------------------------------------------------------------------
 * Normalize view-local variables
 * --------------------------------------------------------------------------
 */

$resolvedField =
    trim(
        (string) (
            $field
            ?? ''
        )
    );

$resolvedLabel =
    trim(
        (string) (
            $label
            ?? ''
        )
    );

$resolvedPlaceholder =
    trim(
        (string) (
            $placeholder
            ?? ''
        )
    );

$resolvedOptions =
    isset($options)
    && is_array($options)
    ? $options
    : [];

$resolvedGroups =
    isset($groups)
    && is_array($groups)
    ? $groups
    : [];

$resolvedSelectedValues =
    isset($selectedValues)
    && is_array($selectedValues)
    ? array_values(
        $selectedValues
    )
    : [];

$resolvedErrors =
    isset($errors)
    && is_array($errors)
    ? $errors
    : [];

$resolvedValueKey =
    trim(
        (string) (
            $optionValueKey
            ?? 'id'
        )
    );

$resolvedLabelKey =
    trim(
        (string) (
            $optionLabelKey
            ?? 'name'
        )
    );

$resolvedGroupLabelKey =
    trim(
        (string) (
            $groupLabelKey
            ?? 'name'
        )
    );

$resolvedGroupItemsKey =
    trim(
        (string) (
            $groupItemsKey
            ?? 'items'
        )
    );

$resolvedShowAny =
    ($showSelectAll ?? true)
    === true;

$isDisabled =
    ($disabled ?? false)
    === true;

/*
 * Partner Preference historically requires a selection.
 *
 * Search passes false because every Search criterion is optional.
 */
$isRequired =
    ($required ?? true)
    === true;

$fieldId =
    lcfirst(
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

$anyId =
    $fieldId
    . 'Any';

$selectedStrings =
    array_values(
        array_map(
            static fn(
                mixed $value
            ): string =>
            (string) $value,
            $resolvedSelectedValues
        )
    );

/**
 * Render one flat/grouped master option.
 */
$renderOption =
    static function (
        array $option
    ) use (
        $resolvedValueKey,
        $resolvedLabelKey,
        $selectedStrings
    ): void {
        $value =
            trim(
                (string) (
                    $option[$resolvedValueKey]
                    ?? ''
                )
            );

        $text =
            trim(
                (string) (
                    $option[$resolvedLabelKey]
                    ?? ''
                )
            );

        if (
            $value === ''
            || $text === ''
        ) {
            return;
        }
?>

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

        <?= esc(
            $text
        ) ?>

    </option>

<?php
    };
?>

<div
    class="col-12 partner-preference-multi-select"
    data-preference-multi-select>

    <!-- =============================================================
         Label / Any control
         ============================================================= -->

    <div
        class="d-flex align-items-center
            justify-content-between gap-2">

        <label
            for="<?= esc(
                        $fieldId,
                        'attr'
                    ) ?>"
            class="form-labelm mb-2">

            <?= esc(
                $resolvedLabel
            ) ?>

            <?php if (
                $isRequired
            ): ?>

                <span class="text-danger">
                    *
                </span>

            <?php endif; ?>

        </label>

        <?php if (
            $resolvedShowAny
        ): ?>

            <div class="form-check mb-2">

                <input
                    type="checkbox"
                    class="form-check-input"
                    id="<?= esc(
                            $anyId,
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
                                $anyId,
                                'attr'
                            ) ?>">

                    Any
                </label>

            </div>

        <?php endif; ?>

    </div>

    <!-- =============================================================
         Choices multi-select
         ============================================================= -->

    <div
        class="partner-preference-multi-select__control">

        <select
            id="<?= esc(
                    $fieldId,
                    'attr'
                ) ?>"
            name="<?= esc(
                        $resolvedField,
                        'attr'
                    ) ?>[]"
            class="form-select
                <?= isset(
                    $resolvedErrors[$resolvedField]
                )
                    ? 'is-invalid'
                    : '' ?>"
            data-choice
            data-choice-search="true"
            data-choice-position="bottom"
            data-choice-search-placeholder="Search"
            data-choice-placeholder="<?= esc(
                                            $resolvedPlaceholder,
                                            'attr'
                                        ) ?>"
            <?= $isDisabled
                ? 'disabled'
                : '' ?>
            <?= $isRequired
                ? 'required'
                : '' ?>
            multiple>

            <?php if (
                $resolvedGroups !== []
            ): ?>

                <?php foreach (
                    $resolvedGroups
                    as $group
                ): ?>

                    <?php
                    if (!is_array($group)) {
                        continue;
                    }

                    $groupLabel =
                        trim(
                            (string) (
                                $group[$resolvedGroupLabelKey]
                                ?? ''
                            )
                        );

                    $groupItems =
                        isset(
                            $group[$resolvedGroupItemsKey]
                        )
                        && is_array(
                            $group[$resolvedGroupItemsKey]
                        )
                        ? $group[$resolvedGroupItemsKey]
                        : [];

                    if (
                        $groupLabel === ''
                        || $groupItems === []
                    ) {
                        continue;
                    }
                    ?>

                    <optgroup
                        label="<?= esc(
                                    $groupLabel,
                                    'attr'
                                ) ?>">

                        <?php foreach (
                            $groupItems
                            as $option
                        ): ?>

                            <?php
                            if (!is_array($option)) {
                                continue;
                            }

                            $renderOption(
                                $option
                            );
                            ?>

                        <?php endforeach; ?>

                    </optgroup>

                <?php endforeach; ?>

            <?php else: ?>

                <?php foreach (
                    $resolvedOptions
                    as $option
                ): ?>

                    <?php
                    if (!is_array($option)) {
                        continue;
                    }

                    $renderOption(
                        $option
                    );
                    ?>

                <?php endforeach; ?>

            <?php endif; ?>

        </select>

        <?php if (
            $resolvedShowAny
        ): ?>

            <div
                class="partner-preference-any-value"
                aria-hidden="true">

                Any

            </div>

        <?php endif; ?>

    </div>

    <!-- =============================================================
         Validation feedback
         ============================================================= -->

    <?= view(
        'Components/Forms/FieldError',
        [
            'field' =>
            $resolvedField,

            'errorId' =>
            $fieldId
                . 'Error',

            'errors' =>
            $resolvedErrors,
        ]
    ) ?>

</div>