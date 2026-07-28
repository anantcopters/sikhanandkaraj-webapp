<?php

declare(strict_types=1);

/**
 * Education and Profession section for the standalone
 * pre-launch profile collection form.
 *
 * @var array<string, string>              $validationErrors
 * @var array<int, array<string, mixed>>   $educations
 * @var array<int, array<string, mixed>>   $occupations
 * @var array<int, array<string, mixed>>   $employmentTypes
 */

$errors = is_array($validationErrors ?? null)
    ? $validationErrors
    : [];

$educationOptions = is_array($educations ?? null)
    ? $educations
    : [];

$occupationOptions = is_array($occupations ?? null)
    ? $occupations
    : [];

$employmentTypeOptions = is_array(
    $employmentTypes ?? null
)
    ? $employmentTypes
    : [];

$selectedEducationId = (string) old(
    'highest_education_id',
    ''
);

$selectedEmploymentType = (string) old(
    'employed_in',
    ''
);

$selectedOccupationId = (string) old(
    'occupation_id',
    ''
);
?>

<fieldset class="mb-4">
    <legend class="h5 mb-3">
        Education and profession
    </legend>

    <div class="row g-3">
        <div class="col-12 col-md-6">
            <label
                for="highest_education_id"
                class="form-label">
                Highest education

                <span
                    class="text-danger"
                    aria-hidden="true">
                    *
                </span>
            </label>

            <select
                id="highest_education_id"
                name="highest_education_id"
                class="form-select <?= isset(
                                        $errors['highest_education_id']
                                    )
                                        ? 'is-invalid'
                                        : '' ?>"
                required>
                <option value="">
                    Select highest education
                </option>

                <?php foreach (
                    $educationOptions as $education
                ): ?>
                    <?php
                    if (!is_array($education)) {
                        continue;
                    }

                    $educationId = (string) (
                        $education['id'] ?? ''
                    );

                    $educationName = (string) (
                        $education['name']
                        ?? $education['label']
                        ?? ''
                    );

                    if (
                        $educationId === ''
                        || $educationName === ''
                    ) {
                        continue;
                    }
                    ?>

                    <option
                        value="<?= esc(
                                    $educationId,
                                    'attr'
                                ) ?>"
                        <?= $selectedEducationId
                            === $educationId
                            ? 'selected'
                            : '' ?>>
                        <?= esc($educationName) ?>
                    </option>
                <?php endforeach ?>
            </select>

            <div class="invalid-feedback">
                <?= esc(
                    $errors['highest_education_id']
                        ?? 'Please select highest education.'
                ) ?>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <label
                for="employed_in"
                class="form-label">
                Employed in

                <span
                    class="text-danger"
                    aria-hidden="true">
                    *
                </span>
            </label>

            <select
                id="employed_in"
                name="employed_in"
                class="form-select <?= isset(
                                        $errors['employed_in']
                                    )
                                        ? 'is-invalid'
                                        : '' ?>"
                required>
                <option value="">
                    Select employment type
                </option>

                <?php foreach (
                    $employmentTypeOptions as $employmentType
                ): ?>
                    <?php
                    if (!is_array($employmentType)) {
                        continue;
                    }

                    $employmentValue = (string) (
                        $employmentType['value']
                        ?? ''
                    );

                    $employmentLabel = (string) (
                        $employmentType['label']
                        ?? $employmentType['name']
                        ?? ''
                    );

                    /*
                     * Current validation expects DEFENSE while the master
                     * service currently returns DEFENSE. Normalize the value
                     * until both locations use one shared constant.
                     */
                    if ($employmentValue === 'DEFENSE') {
                        $employmentValue = 'DEFENSE';
                    }

                    if (
                        $employmentValue === ''
                        || $employmentLabel === ''
                    ) {
                        continue;
                    }
                    ?>

                    <option
                        value="<?= esc(
                                    $employmentValue,
                                    'attr'
                                ) ?>"
                        <?= $selectedEmploymentType
                            === $employmentValue
                            ? 'selected'
                            : '' ?>>
                        <?= esc($employmentLabel) ?>
                    </option>
                <?php endforeach ?>
            </select>

            <div class="invalid-feedback">
                <?= esc(
                    $errors['employed_in']
                        ?? 'Please select employment type.'
                ) ?>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <label
                for="occupation_id"
                class="form-label">
                Occupation

                <span
                    class="text-danger"
                    aria-hidden="true">
                    *
                </span>
            </label>

            <select
                id="occupation_id"
                name="occupation_id"
                class="form-select <?= isset(
                                        $errors['occupation_id']
                                    )
                                        ? 'is-invalid'
                                        : '' ?>"
                required>
                <option value="">
                    Select occupation
                </option>

                <?php foreach (
                    $occupationOptions as $occupation
                ): ?>
                    <?php
                    if (!is_array($occupation)) {
                        continue;
                    }

                    $occupationId = (string) (
                        $occupation['id'] ?? ''
                    );

                    $occupationName = (string) (
                        $occupation['name']
                        ?? $occupation['label']
                        ?? ''
                    );

                    if (
                        $occupationId === ''
                        || $occupationName === ''
                    ) {
                        continue;
                    }
                    ?>

                    <option
                        value="<?= esc(
                                    $occupationId,
                                    'attr'
                                ) ?>"
                        <?= $selectedOccupationId
                            === $occupationId
                            ? 'selected'
                            : '' ?>>
                        <?= esc($occupationName) ?>
                    </option>
                <?php endforeach ?>
            </select>

            <div class="invalid-feedback">
                <?= esc(
                    $errors['occupation_id']
                        ?? 'Please select occupation.'
                ) ?>
            </div>
        </div>
    </div>
</fieldset>