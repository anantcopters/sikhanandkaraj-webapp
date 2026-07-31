<?php

declare(strict_types=1);

/**
 * Prelaunch profile entry form.
 *
 * All view data is normalized into local variables before
 * rendering the HTML markup.
 *
 * @var array<string, string>|null $validationErrors
 * @var array<string, string>|null $formAlert
 */

$errorBag = is_array(
    $validationErrors
        ?? null
)
    ? $validationErrors
    : [];

$alertData = is_array(
    $formAlert
        ?? null
)
    ? $formAlert
    : [];

$alertType = trim(
    (string) (
        $alertData['type']
        ?? ''
    )
);

$alertTitle = trim(
    (string) (
        $alertData['title']
        ?? ''
    )
);

$alertMessage = trim(
    (string) (
        $alertData['message']
        ?? ''
    )
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
    (string) (
        $errorBag['consent']
        ?? ''
    )
);

$consentClass = $consentError !== ''
    ? 'is-invalid'
    : '';

$consentChecked =
    $consentValue === '1';

$consentFeedback =
    $consentError !== ''
    ? $consentError
    : 'Please confirm that the member has provided consent.';

$this->extend(
    'Prelaunch/Layouts/Main'
);

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
                data-validate
                data-submit-loader
                novalidate>
                <?= csrf_field('prelaunch-csrf-token') ?>

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
                                data-error-required="<?= esc(
                                                            $consentFeedback,
                                                            'attr'
                                                        ) ?>"
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

                            <div
                                id="consentError"
                                class="invalid-feedback"
                                data-validation-error="consent">
                                <?= esc($consentError) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-column flex-md-row align-items-end align-items-md-center justify-content-between gap-3">
                    <div class="text-danger fw-medium"
                        role="alert"
                        aria-live="polite">

                        <?php if ($alertMessage !== ''): ?>
                            <i class="ri-error-warning-line me-1"
                                aria-hidden="true"></i>

                            <?= esc($alertMessage) ?>
                        <?php endif ?>
                    </div>
                    <button
                        type="submit"
                        class="btn registration-form__submit w-auto px-3 py-2 fs-14 fw-medium text-uppercase"
                        id="save-prelaunch-profile"
                        data-submit-button>
                        <span
                            class="d-inline-flex align-items-center gap-2"
                            data-submit-idle>
                            <i
                                class="ri-save-line fs-18"
                                aria-hidden="true"></i>

                            Save Draft Profile
                        </span>

                        <span
                            class="registration-submit__loading d-none align-items-center gap-2"
                            data-submit-loading
                            aria-hidden="true">
                            <span
                                class="spinner-border spinner-border-sm"
                                aria-hidden="true"></span>

                            Saving...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>