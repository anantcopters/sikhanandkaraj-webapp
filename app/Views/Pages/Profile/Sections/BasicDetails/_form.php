<?php

declare(strict_types=1);

use App\Support\BooleanValue;

/**
 * Basic Details add/edit form.
 *
 * This partial is rendered inside the dedicated Basic Details page.
 *
 * @var array<string, mixed>      $user
 * @var array<string, mixed>|null $basicDetails
 * @var array<string, string>     $validationErrors
 * @var array<string, mixed>      $masterData
 */

$member = is_array($user ?? null)
    ? $user
    : [];

$details = is_array($basicDetails ?? null)
    ? $basicDetails
    : [];

$errors = is_array($validationErrors ?? null)
    ? $validationErrors
    : [];

/**
 * Resolve submitted old input before using stored data.
 */
$fieldValue = static function (
    string $field,
    mixed $storedValue = ''
): string {
    /*
     * Return raw old input here. Output escaping is performed once,
     * at the point where the value is rendered.
     */
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
    return strtoupper(
        trim(
            $fieldValue(
                $field,
                $storedValue
            )
        )
    ) === strtoupper($option)
        ? 'selected'
        : '';
};

/**
 * Convert stored enum values into readable labels.
 */
$formatEnum = static function (
    mixed $value
): string {
    $normalizedValue = strtolower(
        str_replace(
            '_',
            ' ',
            trim((string) $value)
        )
    );

    return $normalizedValue !== ''
        ? ucwords($normalizedValue)
        : 'Not added';
};

/**
 * Display centimetres as feet/inches and centimetres.
 */
$formatHeight = static function (
    int $heightCm
): string {
    $totalInches = (int) round(
        $heightCm / 2.54
    );

    $feet = intdiv(
        $totalInches,
        12
    );

    $inches = $totalInches % 12;

    return sprintf(
        '%d\' %d" (%d cm)',
        $feet,
        $inches,
        $heightCm
    );
};

$maximumDateOfBirth = date(
    'Y-m-d',
    strtotime('-18 years')
);

$resolvedMasterData = is_array($masterData ?? null)
    ? $masterData
    : [];

$maritalStatuses = is_array(
    $resolvedMasterData['maritalStatuses'] ?? null
)
    ? $resolvedMasterData['maritalStatuses']
    : [];

$heights = is_array(
    $resolvedMasterData['heights'] ?? null
)
    ? $resolvedMasterData['heights']
    : [];

$motherTongues = is_array(
    $resolvedMasterData['motherTongues'] ?? null
)
    ? $resolvedMasterData['motherTongues']
    : [];

$drinkingHabits = is_array(
    $resolvedMasterData['drinkingHabits']
        ?? null
)
    ? $resolvedMasterData['drinkingHabits']
    : [];

$eatingHabits = is_array(
    $resolvedMasterData['eatingHabits']
        ?? null
)
    ? $resolvedMasterData['eatingHabits']
    : [];

$physicalStatuses = is_array(
    $resolvedMasterData['physicalStatuses']
        ?? null
)
    ? $resolvedMasterData['physicalStatuses']
    : [];

$states = is_array(
    $resolvedMasterData['states'] ?? null
)
    ? $resolvedMasterData['states']
    : [];

$cities = is_array(
    $resolvedMasterData['cities'] ?? null
)
    ? $resolvedMasterData['cities']
    : [];

$country = is_array(
    $resolvedMasterData['country'] ?? null
)
    ? $resolvedMasterData['country']
    : [];

$countries = is_array(
    $resolvedMasterData['countries'] ?? null
)
    ? $resolvedMasterData['countries']
    : [];

$selectedCountryId = $fieldValue(
    'country_id',
    $details['country_id'] ?? ($country['id'] ?? '')
);

$selectedStateId = $fieldValue(
    'state_id',
    $details['state_id'] ?? ''
);

$selectedCityId = $fieldValue(
    'city_id',
    $details['city_id'] ?? ''
);

$dateOfBirthHasError = isset(
    $errors['date_of_birth']
);

$storedDateOfBirth = $fieldValue(
    'date_of_birth',
    $details['date_of_birth'] ?? ''
);

$dateParts = preg_match(
    '/^\d{4}-\d{2}-\d{2}$/',
    $storedDateOfBirth
) === 1
    ? explode('-', $storedDateOfBirth)
    : ['', '', ''];

$selectedBirthYear = $dateParts[0] ?? '';
$selectedBirthMonth = $dateParts[1] ?? '';
$selectedBirthDay = $dateParts[2] ?? '';

$maximumBirthYear = (int) date('Y') - 18;
$minimumBirthYear = $maximumBirthYear - 42;

$fullNameHasError = isset(
    $errors['full_name']
);

$maritalStatusHasError = isset(
    $errors['marital_status_id']
);

$heightHasError = isset(
    $errors['height_id']
);

$motherTongueHasError = isset(
    $errors['mother_tongue_id']
);

$drinkingHabitHasError = isset(
    $errors['drinking_habit_id']
);

$eatingHabitHasError = isset(
    $errors['eating_habit_id']
);

$physicalStatusHasError = isset(
    $errors['physical_status_id']
);

$numberOfChildrenHasError = isset(
    $errors['number_of_children']
);

$childrenLivingTogetherHasError = isset(
    $errors['children_living_together']
);

$stateHasError = isset(
    $errors['state_id']
);

$cityHasError = isset(
    $errors['city_id']
);

$countryHasError = isset(
    $errors['country_id']
);

$isJourney = ($isProfileJourney ?? false) === true;

$formAction = url_to(
    'web.profile.basic-details.update'
);

if ($isJourney) {
    $formAction .= '?journey=1';
}

?>

<form
    method="post"
    action="<?= esc($formAction, 'attr') ?>"
    id="basicDetailsForm"
    data-validate
    novalidate>
    <?= csrf_field() ?>

    <div class="row g-3">
        <div class="col-12 col-sm-8">
            <label
                for="fullName"
                class="form-labelm">
                Full name

                <span class="text-danger">*</span>
            </label>

            <input
                type="text"
                class="form-control <?= $fullNameHasError
                                        ? 'is-invalid'
                                        : '' ?>"
                id="fullName"
                name="full_name"
                value="<?= esc(
                            $fieldValue(
                                'full_name',
                                $member['full_name'] ?? ''
                            ),
                            'attr'
                        ) ?>"
                <?= $fullNameHasError
                    ? 'aria-invalid="true"'
                    : '' ?>
                aria-describedby="fullNameError"
                minlength="2"
                maxlength="100"
                autocomplete="name"
                data-error-required="Please enter your full name."
                data-error-minlength="Full name must contain at least 2 characters."
                data-error-maxlength="Full name cannot exceed 100 characters."
                required>

            <?= view('Components/Forms/FieldError', [
                'field' => 'full_name',
                'errorId' => 'fullNameError',
                'errors' => $errors,
            ]) ?>
        </div>
        <div class="col-12 col-sm-4">
            <label
                for="memberGender"
                class="form-labelm">
                Gender
            </label>

            <input
                type="text"
                class="form-control bg-light"
                id="memberGender"
                value="<?= esc(
                            $formatEnum(
                                $member['gender'] ?? ''
                            ),
                            'attr'
                        ) ?>"
                readonly>

            <div class="form-text color-pink">
                Contact support if this is incorrect.
            </div>
        </div>


        <div class="col-12 col-sm-4 col-lg-4">
            <label
                for="profileCreatedFor"
                class="form-labelm">
                Profile created for
            </label>

            <input
                type="text"
                class="form-control bg-light"
                id="profileCreatedFor"
                value="<?= esc(
                            $formatEnum(
                                $member['profile_created_for'] ?? ''
                            ),
                            'attr'
                        ) ?>"
                readonly>
        </div>

        <div class="col-12 col-sm-8 col-lg-8">
            <label class="form-labelm">
                Date of birth
                <span class="text-danger">*</span>
            </label>

            <div
                class="row g-2"
                data-validation-group="date_of_birth">

                <div class="col-4">
                    <label
                        for="birthDay"
                        class="visually-hidden">
                        Birth day
                    </label>

                    <select
                        id="birthDay"
                        name="birth_day"
                        class="form-select <?= $dateOfBirthHasError
                                                ? 'is-invalid'
                                                : '' ?>"
                        <?= $dateOfBirthHasError
                            ? 'aria-invalid="true"'
                            : '' ?>
                        aria-describedby="dateOfBirthError"
                        data-error-required="Please select day."
                        data-validation-ignore
                        required>

                        <option value="">
                            Day
                        </option>

                        <?php for ($day = 1; $day <= 31; $day++): ?>
                            <?php
                            $dayValue = str_pad(
                                (string) $day,
                                2,
                                '0',
                                STR_PAD_LEFT
                            );
                            ?>

                            <option
                                value="<?= esc(
                                            $dayValue,
                                            'attr'
                                        ) ?>"
                                <?= $dayValue === $selectedBirthDay
                                    ? 'selected'
                                    : '' ?>>
                                <?= esc($dayValue) ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="col-4">
                    <label
                        for="birthMonth"
                        class="visually-hidden">
                        Birth month
                    </label>

                    <select
                        id="birthMonth"
                        name="birth_month"
                        class="form-select <?= $dateOfBirthHasError
                                                ? 'is-invalid'
                                                : '' ?>"
                        <?= $dateOfBirthHasError
                            ? 'aria-invalid="true"'
                            : '' ?>
                        aria-describedby="dateOfBirthError"
                        data-error-required="Please select month."
                        data-validation-ignore
                        required>

                        <option value="">
                            Month
                        </option>

                        <?php
                        $months = [
                            '01' => 'Jan',
                            '02' => 'Feb',
                            '03' => 'Mar',
                            '04' => 'Apr',
                            '05' => 'May',
                            '06' => 'Jun',
                            '07' => 'Jul',
                            '08' => 'Aug',
                            '09' => 'Sep',
                            '10' => 'Oct',
                            '11' => 'Nov',
                            '12' => 'Dec',
                        ];
                        ?>

                        <?php foreach ($months as $value => $label): ?>
                            <option
                                value="<?= esc($value, 'attr') ?>"
                                <?= $value === $selectedBirthMonth
                                    ? 'selected'
                                    : '' ?>>
                                <?= esc($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-4">
                    <label
                        for="birthYear"
                        class="visually-hidden">
                        Birth year
                    </label>

                    <select
                        id="birthYear"
                        name="birth_year"
                        class="form-select <?= $dateOfBirthHasError
                                                ? 'is-invalid'
                                                : '' ?>"
                        <?= $dateOfBirthHasError
                            ? 'aria-invalid="true"'
                            : '' ?>
                        aria-describedby="dateOfBirthError"
                        data-choice-position="bottom"
                        data-choice-search-placeholder="Search year"
                        data-error-required="Please select year."
                        data-validation-ignore
                        required>

                        <option value="">
                            Year
                        </option>

                        <?php for (
                            $year = $maximumBirthYear;
                            $year >= $minimumBirthYear;
                            $year--
                        ): ?>
                            <option
                                value="<?= esc(
                                            (string) $year,
                                            'attr'
                                        ) ?>"
                                <?= (string) $year === $selectedBirthYear
                                    ? 'selected'
                                    : '' ?>>
                                <?= esc((string) $year) ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <input
                type="hidden"
                id="dateOfBirth"
                name="date_of_birth"
                value="<?= esc(
                            $storedDateOfBirth,
                            'attr'
                        ) ?>">

            <?= view('Components/Forms/FieldError', [
                'field' => 'date_of_birth',
                'errorId' => 'dateOfBirthError',
                'errors' => $errors,
            ]) ?>

            <div
                class="form-text color-pink"
                id="memberAgePreview"
                aria-live="polite"></div>
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <label
                for="maritalStatusId"
                class="form-labelm">
                Marital status
                <span class="text-danger">*</span>
            </label>

            <select
                class="form-select <?= $maritalStatusHasError
                                        ? 'is-invalid'
                                        : '' ?>"
                id="maritalStatusId"
                name="marital_status_id"
                <?= $maritalStatusHasError
                    ? 'aria-invalid="true"'
                    : '' ?>
                data-choice
                aria-describedby="maritalStatusIdError"
                data-choice-search="false"
                data-choice-position="bottom"
                data-error-required="Please select your marital status."
                required>
                <option
                    value=""
                    <?= $fieldValue(
                        'marital_status_id',
                        $details['marital_status_id'] ?? ''
                    ) === ''
                        ? 'selected'
                        : '' ?>>
                    Select marital status
                </option>

                <?php foreach ($maritalStatuses as $status): ?>
                    <option
                        value="<?= esc(
                                    (string) $status['id'],
                                    'attr'
                                ) ?>"
                        data-marital-status-code="<?= esc(
                                                        strtoupper(
                                                            trim(
                                                                (string) (
                                                                    $status['code']
                                                                    ?? ''
                                                                )
                                                            )
                                                        ),
                                                        'attr'
                                                    ) ?>"
                        <?= $isSelected(
                            'marital_status_id',
                            (string) $status['id'],
                            $details['marital_status_id'] ?? ''
                        ) ?>>
                        <?= esc((string) $status['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?= view('Components/Forms/FieldError', [
                'field' => 'marital_status_id',
                'errorId' => 'maritalStatusIdError',
                'errors' => $errors,
            ]) ?>
        </div>

        <div
            class="col-12 col-sm-6 col-lg-4"
            data-children-details>

            <label
                for="numberOfChildren"
                class="form-labelm">
                Number of children
            </label>

            <input
                type="number"
                id="numberOfChildren"
                name="number_of_children"
                class="form-control <?= $numberOfChildrenHasError
                                        ? 'is-invalid'
                                        : '' ?>"
                value="<?= esc(
                            $fieldValue(
                                'number_of_children',
                                $details['number_of_children']
                                    ?? ''
                            ),
                            'attr'
                        ) ?>"
                min="1"
                max="99"
                step="1"
                inputmode="numeric"
                aria-describedby="numberOfChildrenError"
                data-error-min="Number of children must be between 1 and 99."
                data-error-max="Number of children must be between 1 and 99.">

            <?= view(
                'Components/Forms/FieldError',
                [
                    'field' =>
                    'number_of_children',

                    'errorId' =>
                    'numberOfChildrenError',

                    'errors' =>
                    $errors,
                ]
            ) ?>
        </div>

        <div
            class="col-12 col-sm-6 col-lg-4"
            data-children-details>

            <fieldset>
                <label class="form-labelm">
                    Children living together
                </label>

                <?php
                $storedChildrenLivingTogether =
                    $details['children_living_together']
                    ?? null;

                $livingTogetherValue =
                    $fieldValue(
                        'children_living_together',
                        $storedChildrenLivingTogether === null
                            ? ''
                            : (
                                BooleanValue::fromDatabase(
                                    $storedChildrenLivingTogether
                                )
                                ? '1'
                                : '0'
                            )
                    );
                ?>

                <div class="d-flex flex-wrap gap-3 pt-1">
                    <div class="form-check">
                        <input
                            type="radio"
                            id="childrenLivingTogetherYes"
                            name="children_living_together"
                            class="form-check-input <?= $childrenLivingTogetherHasError
                                                        ? 'is-invalid'
                                                        : '' ?>"
                            value="1"
                            <?= $livingTogetherValue === '1'
                                ? 'checked'
                                : '' ?>>

                        <label
                            for="childrenLivingTogetherYes"
                            class="form-check-label">
                            Yes
                        </label>
                    </div>

                    <div class="form-check">
                        <input
                            type="radio"
                            id="childrenLivingTogetherNo"
                            name="children_living_together"
                            class="form-check-input <?= $childrenLivingTogetherHasError
                                                        ? 'is-invalid'
                                                        : '' ?>"
                            value="0"
                            <?= $livingTogetherValue === '0'
                                ? 'checked'
                                : '' ?>>

                        <label
                            for="childrenLivingTogetherNo"
                            class="form-check-label">
                            No
                        </label>
                    </div>
                </div>

                <?= view(
                    'Components/Forms/FieldError',
                    [
                        'field' =>
                        'children_living_together',

                        'errorId' =>
                        'childrenLivingTogetherError',

                        'errors' =>
                        $errors,
                    ]
                ) ?>
            </fieldset>
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <label
                for="heightId"
                class="form-labelm">
                Height
                <span class="text-danger">*</span>
            </label>

            <select
                class="form-select <?= $heightHasError
                                        ? 'is-invalid'
                                        : '' ?>"
                id="heightId"
                name="height_id"
                <?= $heightHasError
                    ? 'aria-invalid="true"'
                    : '' ?>
                data-choice
                aria-describedby="heightIdError"
                data-choice-search="true"
                data-choice-search-placeholder="Search height"
                data-error-required="Please select your height."
                data-choice-position="bottom"
                required>
                <option
                    value=""
                    <?= $fieldValue(
                        'height_id',
                        $details['height_id'] ?? ''
                    ) === ''
                        ? 'selected'
                        : '' ?>>
                    Select height
                </option>

                <?php foreach ($heights as $height): ?>
                    <option
                        value="<?= esc(
                                    (string) $height['id'],
                                    'attr'
                                ) ?>"
                        <?= $isSelected(
                            'height_id',
                            (string) $height['id'],
                            $details['height_id'] ?? ''
                        ) ?>>
                        <?= esc(
                            (string) $height['display_name']
                        ) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?= view('Components/Forms/FieldError', [
                'field' => 'height_id',
                'errorId' => 'heightIdError',
                'errors' => $errors,
            ]) ?>
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <label
                for="motherTongueId"
                class="form-labelm">
                Mother tongue
                <span class="text-danger">*</span>
            </label>

            <select
                class="form-select <?= $motherTongueHasError
                                        ? 'is-invalid'
                                        : '' ?>"
                <?= $motherTongueHasError
                    ? 'aria-invalid="true"'
                    : '' ?>
                aria-describedby="motherTongueIdError"
                id="motherTongueId"
                name="mother_tongue_id"
                data-choice
                data-choice-position="bottom"
                data-error-required="Please select your mother tongue."
                required>
                <option
                    value=""
                    <?= $fieldValue(
                        'mother_tongue_id',
                        $details['mother_tongue_id'] ?? ''
                    ) === ''
                        ? 'selected'
                        : '' ?>>
                    Select mother tongue
                </option>

                <?php foreach ($motherTongues as $tongue): ?>
                    <option
                        value="<?= esc(
                                    (string) $tongue['id'],
                                    'attr'
                                ) ?>"
                        <?= $isSelected(
                            'mother_tongue_id',
                            (string) $tongue['id'],
                            $details['mother_tongue_id'] ?? ''
                        ) ?>>
                        <?= esc((string) $tongue['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?= view('Components/Forms/FieldError', [
                'field' => 'mother_tongue_id',
                'errorId' => 'motherTongueIdError',
                'errors' => $errors,
            ]) ?>
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <label
                for="drinkingHabitId"
                class="form-labelm">
                Drinking habit
            </label>

            <select
                id="drinkingHabitId"
                name="drinking_habit_id"
                class="form-select <?= $drinkingHabitHasError
                                        ? 'is-invalid'
                                        : '' ?>"
                data-choice
                data-choice-search="false"
                data-choice-position="bottom"
                aria-describedby="drinkingHabitIdError">

                <option value="">
                    Select drinking habit
                </option>

                <?php foreach ($drinkingHabits as $habit): ?>
                    <option
                        value="<?= esc(
                                    (string) $habit['id'],
                                    'attr'
                                ) ?>"
                        <?= $isSelected(
                            'drinking_habit_id',
                            (string) $habit['id'],
                            $details['drinking_habit_id']
                                ?? ''
                        ) ?>>
                        <?= esc(
                            (string) $habit['name']
                        ) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?= view(
                'Components/Forms/FieldError',
                [
                    'field' =>
                    'drinking_habit_id',

                    'errorId' =>
                    'drinkingHabitIdError',

                    'errors' =>
                    $errors,
                ]
            ) ?>
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <label
                for="eatingHabitId"
                class="form-labelm">
                Eating habit
            </label>

            <select
                id="eatingHabitId"
                name="eating_habit_id"
                class="form-select <?= $eatingHabitHasError
                                        ? 'is-invalid'
                                        : '' ?>"
                data-choice
                data-choice-search="false"
                data-choice-position="bottom"
                aria-describedby="eatingHabitIdError">

                <option value="">
                    Select eating habit
                </option>

                <?php foreach ($eatingHabits as $habit): ?>
                    <option
                        value="<?= esc(
                                    (string) $habit['id'],
                                    'attr'
                                ) ?>"
                        <?= $isSelected(
                            'eating_habit_id',
                            (string) $habit['id'],
                            $details['eating_habit_id']
                                ?? ''
                        ) ?>>
                        <?= esc(
                            (string) $habit['name']
                        ) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?= view(
                'Components/Forms/FieldError',
                [
                    'field' =>
                    'eating_habit_id',

                    'errorId' =>
                    'eatingHabitIdError',

                    'errors' =>
                    $errors,
                ]
            ) ?>
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <label
                for="physicalStatusId"
                class="form-labelm">
                Physical status
            </label>

            <select
                id="physicalStatusId"
                name="physical_status_id"
                class="form-select <?= $physicalStatusHasError
                                        ? 'is-invalid'
                                        : '' ?>"
                data-choice
                data-choice-search="false"
                data-choice-position="bottom"
                aria-describedby="physicalStatusIdError">

                <option value="">
                    Select physical status
                </option>

                <?php foreach (
                    $physicalStatuses as $status
                ): ?>
                    <option
                        value="<?= esc(
                                    (string) $status['id'],
                                    'attr'
                                ) ?>"
                        <?= $isSelected(
                            'physical_status_id',
                            (string) $status['id'],
                            $details['physical_status_id']
                                ?? ''
                        ) ?>>
                        <?= esc(
                            (string) $status['name']
                        ) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?= view(
                'Components/Forms/FieldError',
                [
                    'field' =>
                    'physical_status_id',

                    'errorId' =>
                    'physicalStatusIdError',

                    'errors' =>
                    $errors,
                ]
            ) ?>
        </div>
        <div class="col-12">
            <hr class="my-2 mb-3">

            <h2 class="fs-16 fw-semibold mb-0 mt-2 text-secondary-emphasis">
                Current Location
            </h2>
        </div>
        <div class="col-12 col-sm-6 col-lg-4">
            <label
                for="countryId"
                class="form-labelm">
                Country
                <span class="text-danger">*</span>
            </label>

            <select
                id="countryId"
                name="country_id"
                class="form-select <?= isset($errors['country_id'])
                                        ? 'is-invalid'
                                        : '' ?>"
                data-choice
                data-choice-search="false"
                data-states-url="<?= esc(
                                        site_url('profile/master/states'),
                                        'attr'
                                    ) ?>"
                data-error-required="Please select your country."
                required>
                <option value="">Select country</option>
                <?php foreach ($countries as $countryOption): ?>
                    <?php
                    $optionId = (string) ($countryOption['id'] ?? '');
                    ?>
                    <option
                        value="<?= esc($optionId, 'attr') ?>"
                        <?= $selectedCountryId === $optionId ? 'selected' : '' ?>>
                        <?= esc((string) ($countryOption['name'] ?? '')) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?php if (
                isset($errors['country_id'])
            ): ?>
                <div class="invalid-feedback d-block">
                    <?= esc($errors['country_id']) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <label
                for="stateId"
                class="form-labelm">
                State
                <span class="text-danger">*</span>
            </label>

            <select
                class="form-select <?= $stateHasError
                                        ? 'is-invalid'
                                        : '' ?>"
                id="stateId"
                name="state_id"
                <?= $stateHasError
                    ? 'aria-invalid="true"'
                    : '' ?>
                aria-describedby="stateIdError"
                data-choice
                data-choice-search="true"
                data-choice-search-placeholder="Search state"
                data-choice-position="bottom"
                data-error-required="Please select your state."
                <?= $selectedCountryId === '' ? 'disabled' : '' ?>
                required>
                <option
                    value=""
                    <?= $selectedStateId === ''
                        ? 'selected'
                        : '' ?>>
                    Select state
                </option>

                <?php foreach ($states as $state): ?>
                    <option
                        value="<?= esc(
                                    (string) $state['id'],
                                    'attr'
                                ) ?>"
                        <?= $isSelected(
                            'state_id',
                            (string) $state['id'],
                            $details['state_id'] ?? ''
                        ) ?>>
                        <?= esc((string) $state['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?= view('Components/Forms/FieldError', [
                'field' => 'state_id',
                'errorId' => 'stateIdError',
                'errors' => $errors,
            ]) ?>
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <label
                for="cityId"
                class="form-labelm">
                City
                <span class="text-danger">*</span>
            </label>

            <select
                class="form-select <?= $cityHasError
                                        ? 'is-invalid'
                                        : '' ?>"
                id="cityId"
                name="city_id"
                <?= $cityHasError
                    ? 'aria-invalid="true"'
                    : '' ?>
                aria-describedby="cityIdError"
                data-choice
                data-choice-search="true"
                data-choice-search-placeholder="Search city"
                data-choice-position="bottom"
                data-error-required="Please select your city."
                data-cities-url="<?= esc(
                                        site_url('profile/master/cities'),
                                        'attr'
                                    ) ?>"
                data-selected-city="<?= esc(
                                        $selectedCityId,
                                        'attr'
                                    ) ?>"
                <?= $selectedStateId === ''
                    ? 'disabled'
                    : '' ?>
                required>
                <option
                    value=""
                    <?= $selectedCityId === ''
                        ? 'selected'
                        : '' ?>>
                    <?= $selectedStateId === ''
                        ? 'Select state first'
                        : 'Select city' ?>
                </option>

                <?php foreach ($cities as $city): ?>
                    <option
                        value="<?= esc(
                                    (string) $city['id'],
                                    'attr'
                                ) ?>"
                        <?= $isSelected(
                            'city_id',
                            (string) $city['id'],
                            $details['city_id'] ?? ''
                        ) ?>>
                        <?= esc((string) $city['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?= view('Components/Forms/FieldError', [
                'field' => 'city_id',
                'errorId' => 'cityIdError',
                'errors' => $errors,
            ]) ?>
        </div>


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
                                fs-14 fw-semibold text-uppercase"
                id="saveBasicDetailsButton">
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
</form>