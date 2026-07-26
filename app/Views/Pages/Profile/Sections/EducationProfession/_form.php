<?php

declare(strict_types=1);

/**
 * Education & Profession add/edit form.
 *
 * @var array<string, mixed>|null $educationProfession
 * @var array<string, string>     $validationErrors
 * @var array<string, mixed>      $masterData
 */

$details = is_array($educationProfession ?? null)
    ? $educationProfession
    : [];

$errors = is_array($validationErrors ?? null)
    ? $validationErrors
    : [];

$resolvedMasterData = is_array($masterData ?? null)
    ? $masterData
    : [];

$educations = is_array(
    $resolvedMasterData['educations'] ?? null
)
    ? $resolvedMasterData['educations']
    : [];

$occupations = is_array(
    $resolvedMasterData['occupations'] ?? null
)
    ? $resolvedMasterData['occupations']
    : [];

$annualIncomes = is_array(
    $resolvedMasterData['annualIncomes'] ?? null
)
    ? $resolvedMasterData['annualIncomes']
    : [];

$employmentTypes = is_array(
    $resolvedMasterData['employmentTypes'] ?? null
)
    ? $resolvedMasterData['employmentTypes']
    : [];

/**
 * Prefer submitted old input over stored database values.
 */
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

/**
 * Mark a select option as selected.
 */
$isSelected = static function (
    string $field,
    string $option,
    mixed $storedValue = ''
) use ($fieldValue): string {
    return trim(
        $fieldValue(
            $field,
            $storedValue
        )
    ) === $option
        ? 'selected'
        : '';
};

$educationHasError = isset(
    $errors['highest_education_id']
);

$educationDetailHasError = isset(
    $errors['education_detail']
);

$collegeHasError = isset(
    $errors['college_institution']
);

$employedInHasError = isset(
    $errors['employed_in']
);

$occupationHasError = isset(
    $errors['occupation_id']
);

$occupationDetailHasError = isset(
    $errors['occupation_detail']
);

$organizationHasError = isset(
    $errors['organization']
);

$annualIncomeHasError = isset(
    $errors['annual_income_id']
);

$isJourney = ($isProfileJourney ?? false) === true;

$formAction = url_to(
    'web.profile.education-profession.update'
);

if ($isJourney) {
    $formAction .= '?journey=1';
}
?>

<form
    method="post"
    action="<?= esc($formAction, 'attr') ?>"
    id="educationProfessionForm"
    data-validate
    novalidate>

    <?= csrf_field() ?>

    <div class="row g-3">

        <div class="col-12">
            <h2 class="fs-18 fw-semibold mb-0">
                Education
            </h2>
        </div>

        <div class="col-12 col-md-6">
            <label
                for="highestEducation"
                class="form-label fw-medium">

                Highest Education
                <span class="text-danger">*</span>
            </label>

            <select
                id="highestEducation"
                name="highest_education_id"
                class="form-select <?= $educationHasError
                                        ? 'is-invalid'
                                        : '' ?>"
                <?= $educationHasError
                    ? 'aria-invalid="true"'
                    : '' ?>
                aria-describedby="highestEducationError"
                data-choice
                data-choice-position="bottom"
                data-choice-search-placeholder="Search education"
                data-error-required="Please select your highest education."
                required>

                <option value="">
                    Select highest education
                </option>

                <?php foreach ($educations as $education): ?>
                    <?php
                    $educationId = (string) (
                        $education['id'] ?? ''
                    );
                    ?>

                    <option
                        value="<?= esc(
                                    $educationId,
                                    'attr'
                                ) ?>"
                        <?= $isSelected(
                            'highest_education_id',
                            $educationId,
                            $details['highest_education_id']
                                ?? ''
                        ) ?>>

                        <?= esc(
                            (string) (
                                $education['name'] ?? ''
                            )
                        ) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?= view('Components/Forms/FieldError', [
                'field' => 'highest_education_id',
                'errorId' => 'highestEducationError',
                'errors' => $errors,
            ]) ?>
        </div>

        <div class="col-12 col-md-6">
            <label
                for="collegeInstitution"
                class="form-label fw-medium">

                College / Institution
            </label>

            <input
                type="text"
                id="collegeInstitution"
                name="college_institution"
                class="form-control <?= $collegeHasError
                                        ? 'is-invalid'
                                        : '' ?>"
                value="<?= esc(
                            $fieldValue(
                                'college_institution',
                                $details['college_institution']
                                    ?? ''
                            ),
                            'attr'
                        ) ?>"
                maxlength="200"
                autocomplete="organization"
                aria-describedby="collegeInstitutionError"
                data-error-maxlength="College or institution cannot exceed 200 characters."
                placeholder="Enter college or institution">

            <?= view('Components/Forms/FieldError', [
                'field' => 'college_institution',
                'errorId' => 'collegeInstitutionError',
                'errors' => $errors,
            ]) ?>
        </div>

        <div class="col-12">
            <label
                for="educationDetail"
                class="form-label fw-medium">

                Education in Detail
            </label>

            <textarea
                id="educationDetail"
                name="education_detail"
                class="form-control <?= $educationDetailHasError
                                        ? 'is-invalid'
                                        : '' ?>"
                rows="3"
                maxlength="500"
                aria-describedby="educationDetailError educationDetailHelp"
                data-error-maxlength="Education details cannot exceed 500 characters."
                placeholder="Degree, specialization, certifications or other details"><?= esc(
                                                                                            $fieldValue(
                                                                                                'education_detail',
                                                                                                $details['education_detail'] ?? ''
                                                                                            )
                                                                                        ) ?></textarea>

            <div
                id="educationDetailHelp"
                class="form-text  color-pink">
                Maximum 500 characters.
            </div>

            <?= view('Components/Forms/FieldError', [
                'field' => 'education_detail',
                'errorId' => 'educationDetailError',
                'errors' => $errors,
            ]) ?>
        </div>

        <div class="col-12">
            <hr class="my-1">
        </div>

        <div class="col-12">
            <h2 class="fs-18 fw-semibold mb-0">
                Profession
            </h2>
        </div>

        <div class="col-12 col-md-6">
            <label
                for="employedIn"
                class="form-label fw-medium">

                Employed In
                <span class="text-danger">*</span>
            </label>

            <select
                id="employedIn"
                name="employed_in"
                class="form-select <?= $employedInHasError
                                        ? 'is-invalid'
                                        : '' ?>"
                <?= $employedInHasError
                    ? 'aria-invalid="true"'
                    : '' ?>
                data-choice
                aria-describedby="employedInError"
                data-error-required="Please select where you are employed."
                required>

                <option value="">
                    Select employment type
                </option>

                <?php foreach ($employmentTypes as $type): ?>
                    <?php
                    $typeValue = (string) (
                        $type['value'] ?? ''
                    );
                    ?>

                    <option
                        value="<?= esc(
                                    $typeValue,
                                    'attr'
                                ) ?>"
                        <?= $isSelected(
                            'employed_in',
                            $typeValue,
                            $details['employed_in'] ?? ''
                        ) ?>>

                        <?= esc(
                            (string) (
                                $type['label'] ?? ''
                            )
                        ) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?= view('Components/Forms/FieldError', [
                'field' => 'employed_in',
                'errorId' => 'employedInError',
                'errors' => $errors,
            ]) ?>
        </div>

        <div class="col-12 col-md-6">
            <label
                for="occupation"
                class="form-label fw-medium">

                Occupation
                <span class="text-danger">*</span>
            </label>

            <select
                id="occupation"
                name="occupation_id"
                class="form-select <?= $occupationHasError
                                        ? 'is-invalid'
                                        : '' ?>"
                <?= $occupationHasError
                    ? 'aria-invalid="true"'
                    : '' ?>
                data-choice
                aria-describedby="occupationError"
                data-choice-position="bottom"
                data-choice-search-placeholder="Search occupation"
                data-error-required="Please select your occupation."
                required>

                <option value="">
                    Select occupation
                </option>

                <?php foreach ($occupations as $occupation): ?>
                    <?php
                    $occupationId = (string) (
                        $occupation['id'] ?? ''
                    );

                    $occupationCode = (string) (
                        $occupation['code'] ?? ''
                    );
                    ?>

                    <option
                        value="<?= esc(
                                    $occupationId,
                                    'attr'
                                ) ?>"
                        data-code="<?= esc(
                                        $occupationCode,
                                        'attr'
                                    ) ?>"
                        <?= $isSelected(
                            'occupation_id',
                            $occupationId,
                            $details['occupation_id'] ?? ''
                        ) ?>>

                        <?= esc(
                            (string) (
                                $occupation['name'] ?? ''
                            )
                        ) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?= view('Components/Forms/FieldError', [
                'field' => 'occupation_id',
                'errorId' => 'occupationError',
                'errors' => $errors,
            ]) ?>
        </div>

        <div class="col-12 col-md-6">
            <label
                for="organization"
                class="form-label fw-medium">

                Organization
            </label>

            <input
                type="text"
                id="organization"
                name="organization"
                class="form-control <?= $organizationHasError
                                        ? 'is-invalid'
                                        : '' ?>"
                value="<?= esc(
                            $fieldValue(
                                'organization',
                                $details['organization'] ?? ''
                            ),
                            'attr'
                        ) ?>"
                maxlength="200"
                autocomplete="organization"
                aria-describedby="organizationError"
                data-error-maxlength="Organization cannot exceed 200 characters."
                placeholder="Enter organization name">

            <?= view('Components/Forms/FieldError', [
                'field' => 'organization',
                'errorId' => 'organizationError',
                'errors' => $errors,
            ]) ?>
        </div>

        <div class="col-12 col-md-6">
            <label
                for="annualIncome"
                class="form-label fw-medium">

                Annual Income
            </label>

            <select
                id="annualIncome"
                name="annual_income_id"
                class="form-select <?= $annualIncomeHasError
                                        ? 'is-invalid'
                                        : '' ?>"
                <?= $annualIncomeHasError
                    ? 'aria-invalid="true"'
                    : '' ?>
                data-choice
                aria-describedby="annualIncomeError"
                data-choice-position="bottom"
                data-choice-search-placeholder="Search annual income"
                data-error-required="Please select your annual income.">

                <option value="">
                    Prefer not to specify
                </option>

                <?php foreach ($annualIncomes as $income): ?>
                    <?php
                    $incomeId = (string) (
                        $income['id'] ?? ''
                    );
                    ?>

                    <option
                        value="<?= esc(
                                    $incomeId,
                                    'attr'
                                ) ?>"
                        <?= $isSelected(
                            'annual_income_id',
                            $incomeId,
                            $details['annual_income_id']
                                ?? ''
                        ) ?>>

                        <?= esc(
                            (string) (
                                $income['display_name'] ?? ''
                            )
                        ) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?= view('Components/Forms/FieldError', [
                'field' => 'annual_income_id',
                'errorId' => 'annualIncomeError',
                'errors' => $errors,
            ]) ?>
        </div>

        <div class="col-12">
            <label
                for="occupationDetail"
                class="form-label fw-medium">

                Occupation in Detail
            </label>

            <textarea
                id="occupationDetail"
                name="occupation_detail"
                class="form-control <?= $occupationDetailHasError
                                        ? 'is-invalid'
                                        : '' ?>"
                rows="3"
                maxlength="500"
                aria-describedby="occupationDetailError occupationDetailHelp"
                data-error-maxlength="Occupation details cannot exceed 500 characters."
                placeholder="Role, designation, work profile or business details"><?= esc(
                                                                                        $fieldValue(
                                                                                            'occupation_detail',
                                                                                            $details['occupation_detail'] ?? ''
                                                                                        )
                                                                                    ) ?></textarea>

            <div
                id="occupationDetailHelp"
                class="form-text  color-pink">
                Maximum 500 characters.
            </div>

            <?= view('Components/Forms/FieldError', [
                'field' => 'occupation_detail',
                'errorId' => 'occupationDetailError',
                'errors' => $errors,
            ]) ?>
        </div>

        <div class="row g-2 mt-4">
            <div class="col-12 col-sm-6 col-md-3 ms-md-auto order-2 order-sm-1">
                <a
                    href="<?= url_to('web.profile.edit') ?>"
                    class="btn btn-outline-danger fs-14 fw-medium w-100">
                    Cancel
                </a>
            </div>
            <div class="col-12 col-sm-6 col-md-3 order-1 order-sm-2">
                <button
                    type="submit"
                    class="btn registration-form__submit
                                fs-14 fw-semibold text-uppercase" id="saveEducationProfessionButton">
                    <span class="registration-submit__label">
                        <?= $isJourney
                            ? 'Save and Continue'
                            : 'Save' ?>
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
</form>