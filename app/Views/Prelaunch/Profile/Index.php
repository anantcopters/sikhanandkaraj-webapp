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
                            <p class="registration-form__terms fs-12 text-muted mb-0 mt-2">
                                I agree to the
                                <a href="<?= site_url('terms-and-conditions') ?>">
                                    T&amp;C
                                </a>
                                and
                                <a href="<?= site_url('privacy-policy') ?>">
                                    Privacy Policy
                                </a>
                            </p>
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
                        id="save-prelaunch-profile">

                        <span class="d-inline-flex align-items-center gap-2">
                            <i
                                class="ri-save-line fs-18"
                                aria-hidden="true"></i>

                            Save Profile
                        </span>
                    </button>
                </div>
            </form>
            <div
                class="modal fade"
                id="prelaunchSavingModal"
                tabindex="-1"
                aria-labelledby="prelaunchSavingModalTitle"
                aria-describedby="prelaunchSavingModalMessage"
                aria-hidden="true">

                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-body p-4 p-md-5 text-center">
                            <div class="mb-3">
                                <span
                                    class="spinner-border text-primary"
                                    role="status"
                                    aria-hidden="true">
                                </span>
                            </div>

                            <h2
                                id="prelaunchSavingModalTitle"
                                class="h5 fw-semibold mb-2">
                                Saving profile
                            </h2>

                            <p
                                id="prelaunchSavingModalMessage"
                                class="text-muted mb-3"
                                aria-live="polite">
                                Saving profile details…
                            </p>

                            <div
                                class="progress mb-3"
                                role="presentation"
                                aria-hidden="true">

                                <div
                                    id="prelaunchSavingProgressBar"
                                    class="progress-bar"
                                    style="width: 18%">
                                </div>
                            </div>

                            <p class="small text-muted mb-0">
                                Large photographs may take a little longer to
                                process. Please keep this page open.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>