<?php

declare(strict_types=1);

/**
 * @var array<string, string>|null            $validationErrors
 * @var array<int, array<string, mixed>>|null $educations
 * @var array<int, array<string, mixed>>|null $occupations
 * @var array<int, array<string, mixed>>|null $employmentTypes
 */

$errorBag = is_array($validationErrors ?? null)
    ? $validationErrors
    : [];

$educationOptions = is_array($educations ?? null)
    ? $educations
    : [];

$occupationOptions = is_array($occupations ?? null)
    ? $occupations
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

$employmentClass =
    $employmentError !== ''
    ? 'is-invalid'
    : '';

$occupationClass =
    $occupationError !== ''
    ? 'is-invalid'
    : '';
?>

<div class="card border border-danger border-opacity-25 shadow-sm mb-3">
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
        </hr>
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
                    class="form-select js-choice <?= esc(
                                                        $educationClass,
                                                        'attr'
                                                    ) ?>"
                    required>
                    <option value="">
                        Select education
                    </option>

                    <?php foreach (
                        $educationOptions as
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
                                ?? $educationOption['label']
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
                    <?php endforeach ?>
                </select>

                <?php if (
                    $educationError !== ''
                ): ?>
                    <div class="invalid-feedback">
                        <?= esc($educationError) ?>
                    </div>
                <?php endif ?>
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
                    class="form-select js-choice <?= esc(
                                                        $employmentClass,
                                                        'attr'
                                                    ) ?>"
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

                        if ($optionValue === 'DEFENSE') {
                            $optionValue = 'DEFENCE';
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

                <?php if (
                    $employmentError !== ''
                ): ?>
                    <div class="invalid-feedback">
                        <?= esc($employmentError) ?>
                    </div>
                <?php endif ?>
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
                    class="form-select js-choice <?= esc(
                                                        $occupationClass,
                                                        'attr'
                                                    ) ?>"
                    required>
                    <option value="">
                        Select occupation
                    </option>

                    <?php foreach (
                        $occupationOptions as
                        $occupationOption
                    ): ?>
                        <?php
                        if (!is_array($occupationOption)) {
                            continue;
                        }

                        $optionId = (string) (
                            $occupationOption['id']
                            ?? ''
                        );

                        $optionName = trim(
                            (string) (
                                $occupationOption['name']
                                ?? $occupationOption['label']
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
                            $occupationId === $optionId;
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
                    <?php endforeach ?>
                </select>

                <?php if (
                    $occupationError !== ''
                ): ?>
                    <div class="invalid-feedback">
                        <?= esc($occupationError) ?>
                    </div>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>