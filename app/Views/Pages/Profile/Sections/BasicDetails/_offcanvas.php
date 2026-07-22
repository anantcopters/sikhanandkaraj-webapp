<?php

declare(strict_types=1);

/**
 * Basic Details editing offcanvas.
 *
 * @var array<string, mixed>      $user
 * @var array<string, mixed>|null $basicDetails
 * @var array<string, string>     $validationErrors
 * @var array<string, string>     $masterData
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

$shouldOpenEditor = $errors !== []
    || session('openProfileSection')
    === 'basic-details';

/**
 * Resolve submitted old input before using stored data.
 */
$fieldValue = static function (
    string $field,
    mixed $storedValue = ''
): string {
    $oldValue = old($field);

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
?>

<div
    class="offcanvas offcanvas-end profile-offcanvas"
    tabindex="-1"
    id="basicDetailsOffcanvas"
    aria-labelledby="basicDetailsOffcanvasLabel"
    data-open-on-error="<?= $shouldOpenEditor
                            ? 'true'
                            : 'false' ?>">
    <div class="offcanvas-header border-bottom">
        <div>
            <span
                class="text-primary text-uppercase
                    fs-11 fw-semibold d-block mb-1">
                Profile section
            </span>

            <h2
                class="offcanvas-title fs-20 fw-semibold"
                id="basicDetailsOffcanvasLabel">
                Basic Details
            </h2>

            <p class="text-muted fs-13 mb-0 mt-1">
                Fields marked with an asterisk (*) are required.
            </p>
        </div>

        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="offcanvas"
            aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">
        <form
            method="post"
            action="<?= url_to(
                        'web.profile.basic-details.update'
                    ) ?>"
            id="basicDetailsForm"
            novalidate>
            <?= csrf_field() ?>

            <div class="row g-3">
                <div class="col-12">
                    <label
                        for="fullName"
                        class="form-label fw-medium">
                        Full name

                        <span class="text-danger">*</span>
                    </label>

                    <input
                        type="text"
                        class="form-control <?= isset(
                                                $errors['full_name']
                                            )
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
                        maxlength="100"
                        autocomplete="name"
                        required>

                    <?php if (
                        isset($errors['full_name'])
                    ): ?>
                        <div class="invalid-feedback">
                            <?= esc(
                                $errors['full_name']
                            ) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-12 col-md-6">
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

                <div class="col-12 col-md-6">
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

                <div class="col-12 col-md-6">
                    <label
                        for="dateOfBirth"
                        class="form-label fw-medium">
                        Date of birth

                        <span class="text-danger">*</span>
                    </label>

                    <input
                        type="date"
                        class="form-control <?= isset(
                                                $errors['date_of_birth']
                                            )
                                                ? 'is-invalid'
                                                : '' ?>"
                        id="dateOfBirth"
                        name="date_of_birth"
                        value="<?= esc(
                                    $fieldValue(
                                        'date_of_birth',
                                        $details['date_of_birth'] ?? ''
                                    ),
                                    'attr'
                                ) ?>"
                        max="<?= esc(
                                    $maximumDateOfBirth,
                                    'attr'
                                ) ?>"
                        autocomplete="bday"
                        required>

                    <?php if (
                        isset($errors['date_of_birth'])
                    ): ?>
                        <div class="invalid-feedback">
                            <?= esc(
                                $errors['date_of_birth']
                            ) ?>
                        </div>
                    <?php else: ?>
                        <div
                            class="form-text"
                            id="memberAgePreview"
                            aria-live="polite"></div>
                    <?php endif; ?>
                </div>

                <div class="col-12 col-md-6">
                    <label
                        for="maritalStatusId"
                        class="form-label fw-medium">
                        Marital status
                        <span class="text-danger">*</span>
                    </label>

                    <select
                        class="form-select <?= isset(
                                                $errors['marital_status_id']
                                            ) ? 'is-invalid' : '' ?>"
                        id="maritalStatusId"
                        name="marital_status_id"
                        data-choice
                        data-choice-search="false"
                        required>
                        <option value="">
                            Select marital status
                        </option>

                        <?php foreach (
                            $maritalStatuses as $status
                        ): ?>
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
                                <?= esc(
                                    (string) $status['name']
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <?php if (
                        isset($errors['marital_status_id'])
                    ): ?>
                        <div class="invalid-feedback d-block">
                            <?= esc(
                                $errors['marital_status_id']
                            ) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-12 col-md-6">
                    <label
                        for="heightId"
                        class="form-label fw-medium">
                        Height
                        <span class="text-danger">*</span>
                    </label>

                    <select
                        class="form-select <?= isset(
                                                $errors['height_id']
                                            ) ? 'is-invalid' : '' ?>"
                        id="heightId"
                        name="height_id"
                        data-choice
                        data-choice-search="true"
                        data-choice-search-placeholder="Search height"
                        required>
                        <option value="">
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

                    <?php if (
                        isset($errors['height_id'])
                    ): ?>
                        <div class="invalid-feedback d-block">
                            <?= esc($errors['height_id']) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-12 col-md-6">
                    <label
                        for="motherTongueId"
                        class="form-label fw-medium">
                        Mother tongue
                        <span class="text-danger">*</span>
                    </label>

                    <select
                        class="form-select <?= isset(
                                                $errors['mother_tongue_id']
                                            ) ? 'is-invalid' : '' ?>"
                        id="motherTongueId"
                        name="mother_tongue_id"
                        data-choice
                        required>
                        <option value="">
                            Select mother tongue
                        </option>

                        <?php foreach (
                            $motherTongues as $tongue
                        ): ?>
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
                                <?= esc(
                                    (string) $tongue['name']
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <?php if (
                        isset($errors['mother_tongue_id'])
                    ): ?>
                        <div class="invalid-feedback d-block">
                            <?= esc(
                                $errors['mother_tongue_id']
                            ) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-12 col-md-6">
                    <label
                        for="stateId"
                        class="form-label fw-medium">
                        State
                        <span class="text-danger">*</span>
                    </label>

                    <select
                        class="form-select <?= isset(
                                                $errors['state_id']
                                            ) ? 'is-invalid' : '' ?>"
                        id="stateId"
                        name="state_id"
                        data-choice
                        data-choice-search="true"
                        data-choice-search-placeholder="Search state"
                        required>
                        <option value="">
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
                                <?= esc(
                                    (string) $state['name']
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <?php if (
                        isset($errors['state_id'])
                    ): ?>
                        <div class="invalid-feedback d-block">
                            <?= esc($errors['state_id']) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-12 col-md-6">
                    <label
                        for="cityId"
                        class="form-label fw-medium">
                        City
                        <span class="text-danger">*</span>
                    </label>

                    <select
                        class="form-select <?= isset(
                                                $errors['city_id']
                                            ) ? 'is-invalid' : '' ?>"
                        id="cityId"
                        name="city_id"
                        data-choice
                        data-choice-search="true"
                        data-choice-search-placeholder="Search city"
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
                        <option value="">
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
                                <?= esc(
                                    (string) $city['name']
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <?php if (
                        isset($errors['city_id'])
                    ): ?>
                        <div class="invalid-feedback d-block">
                            <?= esc($errors['city_id']) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-12 col-md-6">
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

            <div class="profile-offcanvas__footer mt-4">
                <div class="row g-2">
                    <div class="col-5">
                        <button
                            type="button"
                            class="btn btn-light border w-100"
                            data-bs-dismiss="offcanvas">
                            Cancel
                        </button>
                    </div>

                    <div class="col-7">
                        <button
                            type="submit"
                            class="registration-form__submit
                                fs-14 fw-semibold"
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
            </div>
        </form>
    </div>
</div>