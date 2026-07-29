<?php

declare(strict_types=1);

/**
 * @var array<string, string>|null $validationErrors
 */

$errorBag = is_array($validationErrors ?? null)
    ? $validationErrors
    : [];

$profileCreatedFor = (string) old(
    'profile_created_for',
    ''
);

$gender = (string) old(
    'gender',
    ''
);

$fullName = (string) old(
    'full_name',
    ''
);

$dateOfBirth = (string) old(
    'date_of_birth',
    ''
);

$maximumDateOfBirth = date(
    'Y-m-d',
    strtotime('-18 years')
);

$email = (string) old(
    'email',
    ''
);

$mobileNumber = (string) old(
    'mobile_number',
    ''
);

$profileCreatedForError = trim(
    (string) (
        $errorBag['profile_created_for']
        ?? ''
    )
);

$profileCreatedForClass =
    $profileCreatedForError !== ''
    ? 'is-invalid'
    : '';

$genderError = trim(
    (string) (
        $errorBag['gender']
        ?? ''
    )
);

$fullNameError = trim(
    (string) (
        $errorBag['full_name']
        ?? ''
    )
);

$dateOfBirthError = trim(
    (string) (
        $errorBag['date_of_birth']
        ?? ''
    )
);

$emailError = trim(
    (string) (
        $errorBag['email']
        ?? ''
    )
);

$mobileNumberError = trim(
    (string) (
        $errorBag['mobile_number']
        ?? ''
    )
);

$countryCodeError = trim(
    (string) (
        $errorBag['country_code']
        ?? ''
    )
);

$genderClass = $genderError !== ''
    ? 'is-invalid'
    : '';

$fullNameClass = $fullNameError !== ''
    ? 'is-invalid'
    : '';

$dateOfBirthClass =
    $dateOfBirthError !== ''
    ? 'is-invalid'
    : '';

$emailClass = $emailError !== ''
    ? 'is-invalid'
    : '';

$mobileNumberClass =
    $mobileNumberError !== ''
    || $countryCodeError !== ''
    ? 'is-invalid'
    : '';

$mobileDisplayError =
    $mobileNumberError !== ''
    ? $mobileNumberError
    : $countryCodeError;

$profileForOptions = [
    'SELF' => 'Self',
    'SON' => 'Son',
    'DAUGHTER' => 'Daughter',
    'BROTHER' => 'Brother',
    'SISTER' => 'Sister',
    'RELATIVE' => 'Relative',
    'FRIEND' => 'Friend',
];

$genderOptions = [
    'MALE' => 'Male',
    'FEMALE' => 'Female',
];
?>

<div class="card border border-danger border-opacity-25 shadow-sm mb-3">
    <div class="card-body p-3 p-md-4">
        <div class="d-flex align-items-start gap-3 mb-3">
            <div class="fs-3 text-primary">
                <i
                    class="ri-user-heart-line"
                    aria-hidden="true"></i>
            </div>

            <div>
                <h5 class="mb-1 fs-14 fw-semibold">
                    Member details
                </h5>

                <p class="text-muted mb-0 fs-12">
                    Tell us who the profile is for and provide
                    the member’s primary contact information.
                </p>
            </div>
        </div>
        <hr class="my-2 mb-2">
        </hr>
        <div class="row g-3 pt-2">
            <div class="col-12 col-md-6">
                <label
                    for="profile_created_for"
                    class="form-label">
                    Profile created for
                </label>

                <select
                    id="profile_created_for"
                    name="profile_created_for"
                    class="form-select <?= esc(
                                            $profileCreatedForClass,
                                            'attr'
                                        ) ?>"
                    aria-describedby="profile_created_forError"
                    data-choice
                    data-choices
                    data-choice-search="false"
                    data-choice-position="bottom"
                    data-error-required="Please select who this profile is for."
                    required>
                    <option value="">
                        Select
                    </option>

                    <?php foreach (
                        $profileForOptions as
                        $profileForValue =>
                        $profileForLabel
                    ): ?>
                        <?php
                        $profileForSelected =
                            $profileCreatedFor
                            === $profileForValue;
                        ?>

                        <option
                            value="<?= esc(
                                        $profileForValue,
                                        'attr'
                                    ) ?>"
                            <?= $profileForSelected
                                ? 'selected'
                                : '' ?>>
                            <?= esc($profileForLabel) ?>
                        </option>
                    <?php endforeach ?>
                </select>

                <div
                    id="profile_created_forError"
                    class="invalid-feedback"
                    data-validation-error="profile_created_for">
                    <?= esc($profileCreatedForError) ?>
                </div>
            </div>

            <?php
            /*
            * Gender remains visible for relationships where gender cannot be
            * inferred. For Son, Brother, Daughter and Sister, JavaScript hides
            * the field and assigns the corresponding gender automatically.
            */
            $genderMustBeSelected = in_array(
                $profileCreatedFor,
                [
                    '',
                    'SELF',
                    'RELATIVE',
                    'FRIEND',
                ],
                true
            );

            $genderContainerClass =
                $genderMustBeSelected
                ? ''
                : 'd-none';
            ?>

            <div
                id="gender-container"
                class="col-12 col-md-6 <?= esc(
                                            $genderContainerClass,
                                            'attr'
                                        ) ?>"
                data-validation-group="gender">
                <label
                    for="gender"
                    class="form-label">
                    Gender
                </label>

                <select
                    id="gender"
                    name="gender"
                    class="form-select <?= esc(
                                            $genderClass,
                                            'attr'
                                        ) ?>"
                    aria-describedby="genderError"
                    data-error-required="Please select gender."
                    data-choice
                    data-choices
                    data-choice-search="false"
                    data-choice-position="bottom"
                    <?= $genderMustBeSelected
                        ? 'required'
                        : '' ?>>
                    <option value="">
                        Select gender
                    </option>

                    <?php foreach (
                        $genderOptions as
                        $genderValue =>
                        $genderLabel
                    ): ?>
                        <?php
                        $genderSelected =
                            $gender === $genderValue;
                        ?>

                        <option
                            value="<?= esc(
                                        $genderValue,
                                        'attr'
                                    ) ?>"
                            <?= $genderSelected
                                ? 'selected'
                                : '' ?>>
                            <?= esc($genderLabel) ?>
                        </option>
                    <?php endforeach ?>
                </select>

                <div
                    id="genderError"
                    class="invalid-feedback"
                    data-validation-error="gender">
                    <?= esc($genderError) ?>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <label
                    for="full_name"
                    class="form-label">
                    Full name
                </label>

                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    class="form-control <?= esc(
                                            $fullNameClass,
                                            'attr'
                                        ) ?>"
                    value="<?= esc(
                                $fullName,
                                'attr'
                            ) ?>"
                    aria-describedby="full_nameError"
                    placeholder="Enter full name"
                    minlength="2"
                    maxlength="100"
                    autocomplete="name"
                    data-error-required="Please enter full name."
                    data-error-minlength="Full name must contain at least 2 characters."
                    data-error-maxlength="Full name cannot exceed 100 characters."
                    required>

                <div
                    id="full_nameError"
                    class="invalid-feedback"
                    data-validation-error="full_name">
                    <?= esc($fullNameError) ?>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <label
                    for="date_of_birth"
                    class="form-label">
                    Date of birth
                </label>


                <input
                    type="text"
                    id="date_of_birth"
                    name="date_of_birth"
                    class="form-control pe-5 <?= esc(
                                                    $dateOfBirthClass,
                                                    'attr'
                                                ) ?>"
                    value="<?= esc(
                                $dateOfBirth,
                                'attr'
                            ) ?>"
                    placeholder="Select date of birth"
                    autocomplete="off"
                    aria-describedby="date_of_birthError member-age-preview"
                    data-date-picker
                    data-date-format="Y-m-d"
                    data-alt-format="d M, Y"
                    data-date-max="<?= esc(
                                        $maximumDateOfBirth,
                                        'attr'
                                    ) ?>"
                    data-date-allow-input="true"
                    data-minimum-age="18"
                    data-error-required="Please select the member’s date of birth."
                    required>




                <div
                    id="date_of_birthError"
                    class="invalid-feedback"
                    data-validation-error="date_of_birth">
                    <?= esc($dateOfBirthError) ?>
                </div>

                <div
                    id="member-age-preview"
                    class="form-text"
                    aria-live="polite"></div>
            </div>

            <div class="col-12 col-md-6">
                <label
                    for="email"
                    class="form-label">
                    Email
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control <?= esc(
                                            $emailClass,
                                            'attr'
                                        ) ?>"
                    value="<?= esc(
                                $email,
                                'attr'
                            ) ?>"
                    aria-describedby="emailError"
                    placeholder="Enter email"
                    maxlength="128"
                    autocomplete="email"
                    data-error-required="Please enter email address."
                    data-error-email="Please enter a valid email address."
                    data-error-maxlength="Email address cannot exceed 128 characters."
                    required>

                <div
                    id="emailError"
                    class="invalid-feedback"
                    data-validation-error="email">
                    <?= esc($emailError) ?>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <label
                    for="mobile_number"
                    class="form-label">
                    Mobile number
                </label>

                <input
                    type="hidden"
                    id="country_code"
                    name="country_code"
                    value="+91">

                <div class="input-group">
                    <span class="input-group-text">
                        +91
                    </span>

                    <input
                        type="tel"
                        id="mobile_number"
                        name="mobile_number"
                        class="form-control <?= esc(
                                                $mobileNumberClass,
                                                'attr'
                                            ) ?>"
                        value="<?= esc(
                                    $mobileNumber,
                                    'attr'
                                ) ?>"
                        aria-describedby="mobile_numberError"
                        placeholder="Enter mobile number"
                        inputmode="numeric"
                        pattern="[6-9][0-9]{9}"
                        minlength="10"
                        maxlength="10"
                        autocomplete="tel-national"
                        data-error-required="Please enter mobile number."
                        data-error-pattern="Please enter a valid 10-digit Indian mobile number."
                        data-error-minlength="Please enter a 10-digit mobile number."
                        data-error-maxlength="Please enter a 10-digit mobile number."
                        required>
                </div>

                <div
                    id="mobile_numberError"
                    class="invalid-feedback"
                    data-validation-error="mobile_number">
                    <?= esc($mobileDisplayError) ?>
                </div>
            </div>
        </div>
    </div>
</div>