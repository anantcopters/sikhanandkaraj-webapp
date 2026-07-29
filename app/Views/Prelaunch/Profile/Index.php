<?php

declare(strict_types=1);

/**
 * @var array<string, string>|null $validationErrors
 * @var array<string, string>|null $formAlert
 */

$errorBag = is_array($validationErrors ?? null)
    ? $validationErrors
    : [];

$alertData = is_array($formAlert ?? null)
    ? $formAlert
    : [];

$alertType = trim(
    (string) ($alertData['type'] ?? '')
);

$alertTitle = trim(
    (string) ($alertData['title'] ?? '')
);

$alertMessage = trim(
    (string) ($alertData['message'] ?? '')
);

$hasAlert = $alertMessage !== '';

if ($alertType === '') {
    $alertType = 'danger';
}

$alertClass = 'alert alert-'
    . $alertType;

$formAction = route_to(
    'prelaunch.profile.store'
);

$consentValue = (string) old(
    'consent',
    ''
);

$consentError = trim(
    (string) ($errorBag['consent'] ?? '')
);

$consentClass = $consentError !== ''
    ? 'is-invalid'
    : '';

$consentChecked = $consentValue === '1';

$this->extend('Prelaunch/Layouts/Main');
$this->section('content');
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-9">
            <?php if ($hasAlert): ?>
                <div
                    class="<?= esc(
                                $alertClass,
                                'attr'
                            ) ?>"
                    role="alert">
                    <?php if ($alertTitle !== ''): ?>
                        <strong class="d-block mb-1">
                            <?= esc($alertTitle) ?>
                        </strong>
                    <?php endif ?>

                    <div>
                        <?= esc($alertMessage) ?>
                    </div>
                </div>
            <?php endif ?>

            <form
                action="<?= esc(
                            $formAction,
                            'attr'
                        ) ?>"
                method="post"
                enctype="multipart/form-data"
                id="prelaunch-profile-form"
                data-submit-loader
                novalidate>
                <?= csrf_field() ?>

                <?= $this->include(
                    'Prelaunch/Profile/Partials/MemberDetails'
                ) ?>

                <?= $this->include(
                    'Prelaunch/Profile/Partials/BasicDetails'
                ) ?>

                <?= $this->include(
                    'Prelaunch/Profile/Partials/EducationProfession'
                ) ?>

                <?= $this->include(
                    'Prelaunch/Profile/Partials/FamilyDetails'
                ) ?>

                <?= $this->include(
                    'Prelaunch/Profile/Partials/Photos'
                ) ?>

                <?= $this->include(
                    'Prelaunch/Profile/Partials/FieldOfficer'
                ) ?>

                <div class="card border border-danger border-opacity-25 shadow-sm mb-3">
                    <div class="card-body p-3 p-md-4">
                        <div class="form-check">
                            <input
                                type="checkbox"
                                id="consent"
                                name="consent"
                                class="form-check-input <?= esc(
                                                            $consentClass,
                                                            'attr'
                                                        ) ?>"
                                value="1"
                                <?= $consentChecked
                                    ? 'checked'
                                    : '' ?>
                                required>

                            <label
                                class="form-check-label"
                                for="consent">
                                The member has consented to the
                                collection and review of these details.
                            </label>

                            <?php if ($consentError !== ''): ?>
                                <div class="invalid-feedback">
                                    <?= esc($consentError) ?>
                                </div>
                            <?php endif ?>
                        </div>
                    </div>
                </div>

                <button
                    type="submit"
                    class="registration-form__submit"
                    id="save-prelaunch-profile"
                    disabled>
                    <span data-submit-label>
                        Save Draft Profile
                    </span>

                    <span
                        class="registration-submit__loading d-none"
                        data-submit-loading>
                        <span
                            class="spinner-border spinner-border-sm"
                            aria-hidden="true"></span>

                        Saving...
                    </span>
                </button>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>