<?php

declare(strict_types=1);

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

$stateHasError = isset(
    $errors['state_id']
);

$cityHasError = isset(
    $errors['city_id']
);

$countryHasError = isset(
    $errors['country_id']
);
?>




<form
    method="post"
    action="<?= url_to(
                'web.profile.basic-details.update'
            ) ?>"
    id="basicDetailsForm"
    data-validate
    novalidate>
    <?= csrf_field() ?>

    <div class="row g-3">
        <div class="col-12 col-sm-8">
            <label
                for="fullName"
                class="form-label fw-medium">
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
                class="form-label fw-medium">
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

            <div class="form-text">
                Contact support if this is incorrect.
            </div>
        </div>


        <div class="col-12 col-sm-4 col-lg-4">
            <label
                for="profileCreatedFor"
                class="form-label fw-medium">
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
            <label class="form-label fw-medium">
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
                class="form-text"
                id="memberAgePreview"
                aria-live="polite"></div>
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <label
                for="maritalStatusId"
                class="form-label fw-medium">
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

        <div class="col-12 col-sm-6 col-lg-4">
            <label
                for="heightId"
                class="form-label fw-medium">
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
                class="form-label fw-medium">
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
                for="stateId"
                class="form-label fw-medium">
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
                class="form-label fw-medium">
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

        <div class="col-12 col-sm-6 col-lg-4">
            <label
                for="countryName"
                class="form-label fw-medium">
                Country
            </label>

            <input
                type="text"
                class="form-control bg-light"
                id="countryName"
                value="<?= esc(
                            (string) ($country['name'] ?? 'India'),
                            'attr'
                        ) ?>"
                readonly>

            <input
                type="hidden"
                name="country_id"
                value="<?= esc(
                            (string) ($country['id'] ?? ''),
                            'attr'
                        ) ?>">

            <?php if (
                isset($errors['country_id'])
            ): ?>
                <div class="invalid-feedback d-block">
                    <?= esc($errors['country_id']) ?>
                </div>
            <?php endif; ?>
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
                <span
                    class="registration-submit__label">
                    Save Details
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