<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $user
 * @var array<string, mixed> $basicDetails
 * @var array<string, int>   $basicDetailsCompletion
 * @var array<string, string>$validationErrors
 */

$details = is_array($basicDetails ?? null)
    ? $basicDetails
    : [];

$completion = is_array(
    $basicDetailsCompletion ?? null
)
    ? $basicDetailsCompletion
    : [];

$percentage = max(
    0,
    min(
        100,
        (int) ($completion['percentage'] ?? 0)
    )
);

$completedFields = max(
    0,
    (int) ($completion['completed'] ?? 0)
);

$totalFields = max(
    0,
    (int) ($completion['total'] ?? 0)
);

$remainingFields = max(
    0,
    $totalFields - $completedFields
);

$errors = is_array($validationErrors ?? null)
    ? $validationErrors
    : [];


/**
 * Resolve an old submitted value before falling back to the database value.
 */
$fieldValue = static function (
    string $field,
    mixed $storedValue = ''
): string {
    $oldValue = old($field);

    if ($oldValue !== null) {
        return (string) $oldValue;
    }

    return (string) $storedValue;
};

/**
 * Determine whether a select option should be selected.
 */
$isSelected = static function (
    string $field,
    string $option,
    mixed $storedValue = ''
) use (
    $fieldValue
): string {
    return strtoupper(
        trim($fieldValue($field, $storedValue))
    ) === strtoupper($option)
        ? 'selected'
        : '';
};

/**
 * Convert stored enum values to readable text.
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
 * Display centimetres in feet/inches and centimetres.
 */
$formatHeight = static function (
    mixed $height
): string {
    if (!is_numeric($height)) {
        return 'Not added';
    }

    $heightCm = (int) $height;
    $totalInches = (int) round($heightCm / 2.54);
    $feet = intdiv($totalInches, 12);
    $inches = $totalInches % 12;

    return sprintf(
        '%d\' %d" (%d cm)',
        $feet,
        $inches,
        $heightCm
    );
};

/**
 * Validation failures should reopen the Basic Details editor.
 */
$shouldOpenEditor = $errors !== []
    || session('openProfileSection') === 'basic-details';

$maximumDateOfBirth = date(
    'Y-m-d',
    strtotime('-18 years')
);
?>
<!-- Basic Details editor -->
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

                <!-- Full name -->
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
                                            ) ? 'is-invalid' : '' ?>"
                        id="fullName"
                        name="full_name"
                        value="<?= esc(
                                    $fieldValue(
                                        'full_name',
                                        $user['full_name'] ?? ''
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
                            <?= esc($errors['full_name']) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Read-only registration values -->
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
                                        $user['gender'] ?? ''
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
                                        $user['profile_created_for']
                                            ?? ''
                                    ),
                                    'attr'
                                ) ?>"
                        readonly>
                </div>

                <!-- Date of birth -->
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
                                            ) ? 'is-invalid' : '' ?>"
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

                <!-- Marital status -->
                <div class="col-12 col-md-6">
                    <label
                        for="maritalStatus"
                        class="form-label fw-medium">
                        Marital status
                        <span class="text-danger">*</span>
                    </label>

                    <select
                        class="form-select <?= isset(
                                                $errors['marital_status']
                                            ) ? 'is-invalid' : '' ?>"
                        id="maritalStatus"
                        name="marital_status"
                        required>
                        <option value="">
                            Select marital status
                        </option>

                        <option
                            value="NEVER_MARRIED"
                            <?= $isSelected(
                                'marital_status',
                                'NEVER_MARRIED',
                                $details['marital_status'] ?? ''
                            ) ?>>
                            Never married
                        </option>

                        <option
                            value="DIVORCED"
                            <?= $isSelected(
                                'marital_status',
                                'DIVORCED',
                                $details['marital_status'] ?? ''
                            ) ?>>
                            Divorced
                        </option>

                        <option
                            value="WIDOWED"
                            <?= $isSelected(
                                'marital_status',
                                'WIDOWED',
                                $details['marital_status'] ?? ''
                            ) ?>>
                            Widowed
                        </option>

                        <option
                            value="ANNULLED"
                            <?= $isSelected(
                                'marital_status',
                                'ANNULLED',
                                $details['marital_status'] ?? ''
                            ) ?>>
                            Marriage annulled
                        </option>

                        <option
                            value="AWAITING_DIVORCE"
                            <?= $isSelected(
                                'marital_status',
                                'AWAITING_DIVORCE',
                                $details['marital_status'] ?? ''
                            ) ?>>
                            Awaiting divorce
                        </option>
                    </select>

                    <?php if (
                        isset($errors['marital_status'])
                    ): ?>
                        <div class="invalid-feedback">
                            <?= esc(
                                $errors['marital_status']
                            ) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Height -->
                <div class="col-12 col-md-6">
                    <label
                        for="heightCm"
                        class="form-label fw-medium">
                        Height
                        <span class="text-danger">*</span>
                    </label>

                    <select
                        class="form-select <?= isset(
                                                $errors['height_cm']
                                            ) ? 'is-invalid' : '' ?>"
                        id="heightCm"
                        name="height_cm"
                        required>
                        <option value="">
                            Select height
                        </option>

                        <?php for (
                            $heightCm = 120;
                            $heightCm <= 220;
                            $heightCm++
                        ): ?>
                            <option
                                value="<?= esc(
                                            (string) $heightCm,
                                            'attr'
                                        ) ?>"
                                <?= $isSelected(
                                    'height_cm',
                                    (string) $heightCm,
                                    $details['height_cm'] ?? ''
                                ) ?>>
                                <?= esc(
                                    $formatHeight($heightCm)
                                ) ?>
                            </option>
                        <?php endfor; ?>
                    </select>

                    <?php if (
                        isset($errors['height_cm'])
                    ): ?>
                        <div class="invalid-feedback">
                            <?= esc($errors['height_cm']) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Mother tongue -->
                <div class="col-12 col-md-6">
                    <label
                        for="motherTongue"
                        class="form-label fw-medium">
                        Mother tongue
                        <span class="text-danger">*</span>
                    </label>

                    <select
                        class="form-select <?= isset(
                                                $errors['mother_tongue']
                                            ) ? 'is-invalid' : '' ?>"
                        id="motherTongue"
                        name="mother_tongue"
                        required>
                        <option value="">
                            Select mother tongue
                        </option>

                        <?php
                        $motherTongues = [
                            'PUNJABI' => 'Punjabi',
                            'HINDI' => 'Hindi',
                            'ENGLISH' => 'English',
                            'URDU' => 'Urdu',
                            'OTHER' => 'Other',
                        ];
                        ?>

                        <?php foreach (
                            $motherTongues as $value => $label
                        ): ?>
                            <option
                                value="<?= esc(
                                            $value,
                                            'attr'
                                        ) ?>"
                                <?= $isSelected(
                                    'mother_tongue',
                                    $value,
                                    $details['mother_tongue'] ?? ''
                                ) ?>>
                                <?= esc($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <?php if (
                        isset($errors['mother_tongue'])
                    ): ?>
                        <div class="invalid-feedback">
                            <?= esc(
                                $errors['mother_tongue']
                            ) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- City -->
                <div class="col-12 col-md-6">
                    <label
                        for="currentCity"
                        class="form-label fw-medium">
                        Current city
                        <span class="text-danger">*</span>
                    </label>

                    <input
                        type="text"
                        class="form-control <?= isset(
                                                $errors['current_city']
                                            ) ? 'is-invalid' : '' ?>"
                        id="currentCity"
                        name="current_city"
                        value="<?= esc(
                                    $fieldValue(
                                        'current_city',
                                        $details['current_city'] ?? ''
                                    ),
                                    'attr'
                                ) ?>"
                        maxlength="100"
                        autocomplete="address-level2"
                        required>

                    <?php if (
                        isset($errors['current_city'])
                    ): ?>
                        <div class="invalid-feedback">
                            <?= esc(
                                $errors['current_city']
                            ) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- State -->
                <div class="col-12 col-md-6">
                    <label
                        for="currentState"
                        class="form-label fw-medium">
                        Current state
                        <span class="text-danger">*</span>
                    </label>

                    <input
                        type="text"
                        class="form-control <?= isset(
                                                $errors['current_state']
                                            ) ? 'is-invalid' : '' ?>"
                        id="currentState"
                        name="current_state"
                        value="<?= esc(
                                    $fieldValue(
                                        'current_state',
                                        $details['current_state'] ?? ''
                                    ),
                                    'attr'
                                ) ?>"
                        maxlength="100"
                        autocomplete="address-level1"
                        required>

                    <?php if (
                        isset($errors['current_state'])
                    ): ?>
                        <div class="invalid-feedback">
                            <?= esc(
                                $errors['current_state']
                            ) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Country -->
                <div class="col-12">
                    <label
                        for="countryCode"
                        class="form-label fw-medium">
                        Country
                        <span class="text-danger">*</span>
                    </label>

                    <select
                        class="form-select <?= isset(
                                                $errors['country_code']
                                            ) ? 'is-invalid' : '' ?>"
                        id="countryCode"
                        name="country_code"
                        autocomplete="country"
                        required>
                        <?php
                        $countries = [
                            'IN' => 'India',
                            'CA' => 'Canada',
                            'GB' => 'United Kingdom',
                            'US' => 'United States',
                            'AU' => 'Australia',
                            'NZ' => 'New Zealand',
                        ];
                        ?>

                        <?php foreach (
                            $countries as $code => $country
                        ): ?>
                            <option
                                value="<?= esc(
                                            $code,
                                            'attr'
                                        ) ?>"
                                <?= $isSelected(
                                    'country_code',
                                    $code,
                                    $details['country_code']
                                        ?? 'IN'
                                ) ?>>
                                <?= esc($country) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <?php if (
                        isset($errors['country_code'])
                    ): ?>
                        <div class="invalid-feedback">
                            <?= esc(
                                $errors['country_code']
                            ) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sticky form actions -->
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