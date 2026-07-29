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

$profileCreatedForClass =
    $profileCreatedForError !== ''
    ? 'is-invalid'
    : '';

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
        <hr class="my-2 mb-2"></hr>
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
                    class="form-select js-choice <?= esc(
                                                        $profileCreatedForClass,
                                                        'attr'
                                                    ) ?>"
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
                            <?= esc(
                                $profileForLabel
                            ) ?>
                        </option>
                    <?php endforeach ?>
                </select>

                <?php if (
                    $profileCreatedForError !== ''
                ): ?>
                    <div class="invalid-feedback">
                        <?= esc(
                            $profileCreatedForError
                        ) ?>
                    </div>
                <?php endif ?>
            </div>

            <div class="col-12 col-md-6">
                <label
                    for="gender"
                    class="form-label">
                    Gender
                </label>

                <select
                    id="gender"
                    name="gender"
                    class="form-select js-choice <?= esc(
                                                        $genderClass,
                                                        'attr'
                                                    ) ?>"
                    required>
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

                <?php if ($genderError !== ''): ?>
                    <div class="invalid-feedback">
                        <?= esc($genderError) ?>
                    </div>
                <?php endif ?>
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
                    maxlength="100"
                    autocomplete="name"
                    required>

                <?php if ($fullNameError !== ''): ?>
                    <div class="invalid-feedback">
                        <?= esc($fullNameError) ?>
                    </div>
                <?php endif ?>
            </div>

            <div class="col-12 col-md-6">
                <label
                    for="date_of_birth"
                    class="form-label">
                    Date of birth
                </label>

                <input
                    type="date"
                    id="date_of_birth"
                    name="date_of_birth"
                    class="form-control <?= esc(
                                            $dateOfBirthClass,
                                            'attr'
                                        ) ?>"
                    value="<?= esc(
                                $dateOfBirth,
                                'attr'
                            ) ?>"
                    required>

                <?php if (
                    $dateOfBirthError !== ''
                ): ?>
                    <div class="invalid-feedback">
                        <?= esc(
                            $dateOfBirthError
                        ) ?>
                    </div>
                <?php endif ?>
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
                    maxlength="190"
                    autocomplete="email"
                    required>

                <?php if ($emailError !== ''): ?>
                    <div class="invalid-feedback">
                        <?= esc($emailError) ?>
                    </div>
                <?php endif ?>
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
                        inputmode="numeric"
                        pattern="[0-9]{10}"
                        maxlength="10"
                        autocomplete="tel-national"
                        required>
                </div>

                <?php if (
                    $mobileDisplayError !== ''
                ): ?>
                    <div class="text-danger small mt-1">
                        <?= esc(
                            $mobileDisplayError
                        ) ?>
                    </div>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>