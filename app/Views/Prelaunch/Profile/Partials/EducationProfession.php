<?php

declare(strict_types=1);

/**
 * Prelaunch Education & Profession fields.
 *
 * @var array<string, string>|null            $validationErrors
 * @var array<int, array<string, mixed>>|null $educationGroups
 * @var array<int, array<string, mixed>>|null $occupationGroups
 * @var array<int, array<string, mixed>>|null $employmentTypes
 */

$errorBag = is_array(
    $validationErrors ?? null
)
    ? $validationErrors
    : [];

$groupedEducationOptions = is_array(
    $educationGroups ?? null
)
    ? $educationGroups
    : [];

$groupedOccupationOptions = is_array(
    $occupationGroups ?? null
)
    ? $occupationGroups
    : [];

$employmentOptions = is_array(
    $employmentTypes ?? null
)
    ? $employmentTypes
    : [];

$educationId = (string) old(
    'highest_education_id',
    ''
);

$employmentType = (string) old(
    'employed_in',
    ''
);

$occupationId = (string) old(
    'occupation_id',
    ''
);

$educationError = trim(
    (string) (
        $errorBag['highest_education_id']
        ?? ''
    )
);

$employmentError = trim(
    (string) (
        $errorBag['employed_in']
        ?? ''
    )
);

$occupationError = trim(
    (string) (
        $errorBag['occupation_id']
        ?? ''
    )
);

$educationClass = $educationError !== ''
    ? 'is-invalid'
    : '';

$employmentClass = $employmentError !== ''
    ? 'is-invalid'
    : '';

$occupationClass = $occupationError !== ''
    ? 'is-invalid'
    : '';
?>

<div
    class="card border border-danger
        border-opacity-25 shadow-sm mb-3">

    <div class="card-body p-3 p-md-4">
        <div class="d-flex align-items-start gap-3 mb-3">
            <div class="fs-3 text-primary">
                <i
                    class="ri-graduation-cap-line"
                    aria-hidden="true"></i>
            </div>

            <div>
                <h5 class="mb-1 fs-14 fw-semibold">
                    Education and profession
                </h5>

                <p class="text-muted mb-0 fs-12">
                    Provide the member’s highest qualification
                    and current employment information.
                </p>
            </div>
        </div>

        <hr class="my-2 mb-2">

        <div class="row g-3 pt-2">
            <div class="col-12 col-md-6">
                <label
                    for="highest_education_id"
                    class="form-label">

                    Highest education
                </label>

                <select
                    id="highest_education_id"
                    name="highest_education_id"
                    class="form-select <?= esc(
                                            $educationClass,
                                            'attr'
                                        ) ?>"
                    <?= $educationError !== ''
                        ? 'aria-invalid="true"'
                        : '' ?>
                    aria-describedby="highest_education_idError"
                    data-choice
                    data-choice-position="bottom"
                    data-choice-search="true"
                    data-choice-search-placeholder="Search education"
                    data-error-required="Please select your highest education."
                    required>

                    <option value="">
                        Select education
                    </option>

                    <?php foreach (
                        $groupedEducationOptions as
                        $educationGroup
                    ): ?>
                        <?php
                        if (!is_array($educationGroup)) {
                            continue;
                        }

                        $groupName = trim(
                            (string) (
                                $educationGroup['name']
                                ?? ''
                            )
                        );

                        $groupEducations = is_array(
                            $educationGroup['educations']
                                ?? null
                        )
                            ? $educationGroup['educations']
                            : [];

                        if (
                            $groupName === ''
                            || $groupEducations === []
                        ) {
                            continue;
                        }
                        ?>

                        <optgroup
                            label="<?= esc(
                                        $groupName,
                                        'attr'
                                    ) ?>">

                            <?php foreach (
                                $groupEducations as
                                $educationOption
                            ): ?>
                                <?php
                                if (!is_array($educationOption)) {
                                    continue;
                                }

                                $optionId = (string) (
                                    $educationOption['id']
                                    ?? ''
                                );

                                $optionName = trim(
                                    (string) (
                                        $educationOption['name']
                                        ?? ''
                                    )
                                );

                                if (
                                    $optionId === ''
                                    || $optionName === ''
                                ) {
                                    continue;
                                }

                                $optionSelected =
                                    $educationId === $optionId;
                                ?>

                                <option
                                    value="<?= esc(
                                                $optionId,
                                                'attr'
                                            ) ?>"
                                    <?= $optionSelected
                                        ? 'selected'
                                        : '' ?>>

                                    <?= esc($optionName) ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>

                <div
                    id="highest_education_idError"
                    class="invalid-feedback"
                    data-validation-error="highest_education_id">

                    <?= esc($educationError) ?>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <label
                    for="employed_in"
                    class="form-label">

                    Employed in
                </label>

                <select
                    id="employed_in"
                    name="employed_in"
                    class="form-select <?= esc(
                                            $employmentClass,
                                            'attr'
                                        ) ?>"
                    <?= $employmentError !== ''
                        ? 'aria-invalid="true"'
                        : '' ?>
                    aria-describedby="employed_inError"
                    data-choice
                    data-choice-search="false"
                    data-choice-position="bottom"
                    data-error-required="Please select employment details."
                    required>

                    <option value="">
                        Select employment type
                    </option>

                    <?php foreach (
                        $employmentOptions as
                        $employmentOption
                    ): ?>
                        <?php
                        if (!is_array($employmentOption)) {
                            continue;
                        }

                        $optionValue = trim(
                            (string) (
                                $employmentOption['value']
                                ?? ''
                            )
                        );

                        $optionLabel = trim(
                            (string) (
                                $employmentOption['label']
                                ?? $employmentOption['name']
                                ?? ''
                            )
                        );

                        $optionValue = trim(
                            (string) (
                                $employmentOption['value']
                                ?? ''
                            )
                        );

                        $optionLabel = trim(
                            (string) (
                                $employmentOption['label']
                                ?? $employmentOption['name']
                                ?? ''
                            )
                        );

                        if (
                            $optionValue === ''
                            || $optionLabel === ''
                        ) {
                            continue;
                        }

                        if (
                            $optionValue === ''
                            || $optionLabel === ''
                        ) {
                            continue;
                        }

                        $optionSelected =
                            $employmentType
                            === $optionValue;
                        ?>

                        <option
                            value="<?= esc(
                                        $optionValue,
                                        'attr'
                                    ) ?>"
                            <?= $optionSelected
                                ? 'selected'
                                : '' ?>>

                            <?= esc($optionLabel) ?>
                        </option>
                    <?php endforeach ?>
                </select>

                <div
                    id="employed_inError"
                    class="invalid-feedback"
                    data-validation-error="employed_in">

                    <?= esc($employmentError) ?>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <label
                    for="occupation_id"
                    class="form-label">

                    Occupation
                </label>

                <select
                    id="occupation_id"
                    name="occupation_id"
                    class="form-select <?= esc(
                                            $occupationClass,
                                            'attr'
                                        ) ?>"
                    <?= $occupationError !== ''
                        ? 'aria-invalid="true"'
                        : '' ?>
                    aria-describedby="occupation_idError"
                    data-choice
                    data-choice-position="bottom"
                    data-choice-search="true"
                    data-choice-search-placeholder="Search occupation"
                    data-error-required="Please select occupation."
                    required>

                    <option value="">
                        Select occupation
                    </option>

                    <?php foreach (
                        $groupedOccupationOptions as
                        $occupationGroup
                    ): ?>
                        <?php
                        if (!is_array($occupationGroup)) {
                            continue;
                        }

                        $groupName = trim(
                            (string) (
                                $occupationGroup['name']
                                ?? ''
                            )
                        );

                        $groupOccupations = is_array(
                            $occupationGroup['occupations']
                                ?? null
                        )
                            ? $occupationGroup['occupations']
                            : [];

                        if (
                            $groupName === ''
                            || $groupOccupations === []
                        ) {
                            continue;
                        }
                        ?>

                        <optgroup
                            label="<?= esc(
                                        $groupName,
                                        'attr'
                                    ) ?>">

                            <?php foreach (
                                $groupOccupations as
                                $occupationOption
                            ): ?>
                                <?php
                                if (
                                    !is_array(
                                        $occupationOption
                                    )
                                ) {
                                    continue;
                                }

                                $optionId = (string) (
                                    $occupationOption['id']
                                    ?? ''
                                );

                                $optionCode = trim(
                                    (string) (
                                        $occupationOption['code']
                                        ?? ''
                                    )
                                );

                                $optionName = trim(
                                    (string) (
                                        $occupationOption['name']
                                        ?? ''
                                    )
                                );

                                if (
                                    $optionId === ''
                                    || $optionName === ''
                                ) {
                                    continue;
                                }

                                $optionSelected =
                                    $occupationId
                                    === $optionId;
                                ?>

                                <option
                                    value="<?= esc(
                                                $optionId,
                                                'attr'
                                            ) ?>"
                                    data-code="<?= esc(
                                                    $optionCode,
                                                    'attr'
                                                ) ?>"
                                    <?= $optionSelected
                                        ? 'selected'
                                        : '' ?>>

                                    <?= esc($optionName) ?>
                                </option>
                            <?php endforeach ?>
                        </optgroup>
                    <?php endforeach ?>
                </select>

                <div
                    id="occupation_idError"
                    class="invalid-feedback"
                    data-validation-error="occupation_id">

                    <?= esc($occupationError) ?>
                </div>
            </div>
        </div>
    </div>
</div>