<?php

declare(strict_types=1);

/**
 * Prelaunch family details.
 *
 * @var array<string, string>|null            $validationErrors
 * @var array<int, array<string, mixed>>|null $communities
 */

$errorBag = is_array(
    $validationErrors
        ?? null
)
    ? $validationErrors
    : [];

$communityOptions = is_array(
    $communities
        ?? null
)
    ? $communities
    : [];

$fatherName = (string) old(
    'father_name',
    ''
);

$motherName = (string) old(
    'mother_name',
    ''
);

$parentContactNumber = preg_replace(
    '/^\+91/',
    '',
    (string) old(
        'parent_contact_number',
        ''
    )
) ?? '';

$gotra = (string) old(
    'gotra',
    ''
);

$gotraMaternal = (string) old(
    'gotra_maternal',
    ''
);


$nearestGurudwara = (string) old(
    'nearest_gurudwara',
    ''
);

$communityId = (string) old(
    'sikh_community_id',
    ''
);

$fatherNameError = trim(
    (string) (
        $errorBag['father_name']
        ?? ''
    )
);

$motherNameError = trim(
    (string) (
        $errorBag['mother_name']
        ?? ''
    )
);

$parentContactNumberError = trim(
    (string) (
        $errorBag['parent_contact_number']
        ?? ''
    )
);

$gotraError = trim(
    (string) (
        $errorBag['gotra']
        ?? ''
    )
);

$gotraMaternalError = trim(
    (string) (
        $errorBag['gotra_maternal']
        ?? ''
    )
);

$nearestGurudwaraError = trim(
    (string) (
        $errorBag['nearest_gurudwara']
        ?? ''
    )
);

$communityError = trim(
    (string) (
        $errorBag['sikh_community_id']
        ?? ''
    )
);

$fatherNameClass =
    $fatherNameError !== ''
    ? 'is-invalid'
    : '';

$motherNameClass =
    $motherNameError !== ''
    ? 'is-invalid'
    : '';

$parentContactNumberClass =
    $parentContactNumberError !== ''
    ? 'is-invalid'
    : '';

$gotraClass =
    $gotraError !== ''
    ? 'is-invalid'
    : '';

$gotraMaternalClass =
    $gotraMaternalError !== ''
    ? 'is-invalid'
    : '';

$nearestGurudwaraClass =
    $nearestGurudwaraError !== ''
    ? 'is-invalid'
    : '';

$communityClass =
    $communityError !== ''
    ? 'is-invalid'
    : '';
?>

<div class="card border border-danger border-opacity-25 shadow-sm mb-3">
    <div class="card-body p-3 p-md-4">
        <div class="d-flex align-items-start gap-3 mb-3">
            <div class="fs-3 text-primary">
                <i
                    class="ri-home-heart-line"
                    aria-hidden="true">
                </i>
            </div>

            <div>
                <h5 class="mb-1 fs-14 fw-semibold">
                    Family details
                </h5>

                <p class="text-muted mb-0 fs-12">
                    Add parent names, community, Gotra and nearest
                    Gurudwara information.
                </p>
            </div>
        </div>

        <hr class="my-2 mb-2">

        <div class="row g-3 pt-2">
            <div class="col-12 col-md-6">
                <label
                    for="father_name"
                    class="form-label">

                    Father’s name
                </label>

                <input
                    type="text"
                    id="father_name"
                    name="father_name"
                    class="form-control <?= esc(
                                            $fatherNameClass,
                                            'attr'
                                        ) ?>"
                    value="<?= esc(
                                $fatherName,
                                'attr'
                            ) ?>"
                    aria-describedby="father_nameError"
                    placeholder="Enter father’s name"
                    minlength="2"
                    maxlength="100"
                    autocomplete="off"
                    data-error-required="Please enter father’s name."
                    data-error-minlength="Father’s name must contain at least 2 characters."
                    data-error-maxlength="Father’s name cannot exceed 100 characters."
                    required>

                <div
                    id="father_nameError"
                    class="invalid-feedback"
                    data-validation-error="father_name">

                    <?= esc($fatherNameError) ?>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <label
                    for="mother_name"
                    class="form-label">

                    Mother’s name
                </label>

                <input
                    type="text"
                    id="mother_name"
                    name="mother_name"
                    class="form-control <?= esc(
                                            $motherNameClass,
                                            'attr'
                                        ) ?>"
                    value="<?= esc(
                                $motherName,
                                'attr'
                            ) ?>"
                    aria-describedby="mother_nameError"
                    placeholder="Enter mother’s name"
                    minlength="2"
                    maxlength="100"
                    autocomplete="off"
                    data-error-required="Please enter mother’s name."
                    data-error-minlength="Mother’s name must contain at least 2 characters."
                    data-error-maxlength="Mother’s name cannot exceed 100 characters."
                    required>

                <div
                    id="mother_nameError"
                    class="invalid-feedback"
                    data-validation-error="mother_name">

                    <?= esc($motherNameError) ?>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <label
                    for="parent_contact_number"
                    class="form-label">

                    Parent/Guardian Contact Number
                </label>

                <div class="input-group has-validation">
                    <span class="input-group-text">
                        +91
                    </span>

                    <input
                        type="tel"
                        id="parent_contact_number"
                        name="parent_contact_number"
                        class="form-control <?= esc(
                                                $parentContactNumberClass,
                                                'attr'
                                            ) ?>"
                        value="<?= esc(
                                    $parentContactNumber,
                                    'attr'
                                ) ?>"
                        placeholder="Enter parent contact number"
                        inputmode="numeric"
                        pattern="[6-9][0-9]{9}"
                        minlength="10"
                        maxlength="10"
                        autocomplete="tel"
                        required
                        aria-describedby="parent_contact_numberHelp parent_contact_numberError"
                        data-error-required="Please enter a contact number for either parent/guardian."
                        data-error-pattern="Please enter a valid 10-digit Indian parent/guardian contact number."
                        data-error-minlength="Parent contact number must contain 10 digits."
                        data-error-maxlength="Parent contact number must contain 10 digits.">

                    <div
                        id="parent_contact_numberError"
                        class="invalid-feedback"
                        data-validation-error="parent_contact_number">

                        <?= esc(
                            $parentContactNumberError
                        ) ?>
                    </div>
                </div>

                <div
                    id="parent_contact_numberHelp"
                    class="form-text color-pink">
                    Enter the mobile number of either parent/guardian, when available.
                </div>
            </div>

            <div class="col-12 col-md-6">
                <label
                    for="sikh_community_id"
                    class="form-label">

                    Community
                </label>

                <select
                    id="sikh_community_id"
                    name="sikh_community_id"
                    class="form-select <?= esc(
                                            $communityClass,
                                            'attr'
                                        ) ?>"
                    data-choice
                    data-choice-search="true"
                    data-choice-position="bottom"
                    data-error-required="Please select your community."
                    required>

                    <option value="">
                        Select community
                    </option>

                    <?php foreach (
                        $communityOptions as $communityOption
                    ): ?>
                        <?php
                        if (!is_array($communityOption)) {
                            continue;
                        }

                        $optionId = (string) (
                            $communityOption['id']
                            ?? ''
                        );

                        $optionName = trim(
                            (string) (
                                $communityOption['name']
                                ?? $communityOption['label']
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
                            $communityId === $optionId;
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
                </select>

                <div
                    id="sikh_community_idError"
                    class="invalid-feedback"
                    data-validation-error="sikh_community_id">

                    <?= esc($communityError) ?>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <label
                    for="gotra"
                    class="form-label">

                    Father Gotra
                </label>

                <input
                    type="text"
                    id="gotra"
                    name="gotra"
                    class="form-control <?= esc(
                                            $gotraClass,
                                            'attr'
                                        ) ?>"
                    value="<?= esc(
                                $gotra,
                                'attr'
                            ) ?>"
                    aria-describedby="gotraError"
                    placeholder="Enter Father Gotra"
                    minlength="2"
                    maxlength="100"
                    autocomplete="off"
                    data-error-required="Please enter Father Gotra."
                    data-error-minlength="Father Gotra must contain at least 2 characters."
                    data-error-maxlength="Father Gotra cannot exceed 100 characters."
                    data-error-pattern="Father Gotra may contain letters, spaces, apostrophes, full stops and hyphens only."
                    required>

                <div
                    id="gotraError"
                    class="invalid-feedback"
                    data-validation-error="gotra">

                    <?= esc($gotraError) ?>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <label
                    for="gotra_maternal"
                    class="form-label">

                    Mother Gotra (Maternal Side)
                </label>

                <input
                    type="text"
                    id="gotra_maternal"
                    name="gotra_maternal"
                    class="form-control <?= esc(
                                            $gotraMaternalClass,
                                            'attr'
                                        ) ?>"
                    value="<?= esc(
                                $gotraMaternal,
                                'attr'
                            ) ?>"
                    aria-describedby="gotra_maternalError"
                    placeholder="Enter Gotra (Maternal Side)"
                    minlength="2"
                    maxlength="100"
                    autocomplete="off"
                    data-error-required="Please enter Mother Gotra (Maternal Side)."
                    data-error-minlength="Mother Gotra (Maternal Side) must contain at least 2 characters."
                    data-error-maxlength="Mother Gotra (Maternal Side) cannot exceed 100 characters."
                    data-error-pattern="Mother Gotra (Maternal Side) may contain letters, spaces, apostrophes, full stops and hyphens only."
                    required>

                <div
                    id="gotra_maternalError"
                    class="invalid-feedback"
                    data-validation-error="gotra_maternal">

                    <?= esc($gotraMaternalError) ?>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <label
                    for="nearest_gurudwara"
                    class="form-label">

                    Nearest Gurudwara
                </label>

                <input
                    type="text"
                    id="nearest_gurudwara"
                    name="nearest_gurudwara"
                    class="form-control <?= esc(
                                            $nearestGurudwaraClass,
                                            'attr'
                                        ) ?>"
                    value="<?= esc(
                                $nearestGurudwara,
                                'attr'
                            ) ?>"
                    aria-describedby="nearest_gurudwaraHelp nearest_gurudwaraError"
                    placeholder="Enter the nearest Gurudwara name or location"
                    maxlength="300"
                    autocomplete="off"
                    required
                    data-error-required="Please enter the nearest Gurudwara name or location."
                    data-error-maxlength="Nearest Gurudwara cannot exceed 300 characters.">

                <div
                    id="nearest_gurudwaraHelp"
                    class="form-text color-pink">
                    Enter the Gurudwara name and locality.
                </div>

                <div
                    id="nearest_gurudwaraError"
                    class="invalid-feedback"
                    data-validation-error="nearest_gurudwara">

                    <?= esc($nearestGurudwaraError) ?>
                </div>
            </div>

        </div>
    </div>
</div>