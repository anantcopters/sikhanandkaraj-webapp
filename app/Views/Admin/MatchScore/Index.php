<?php

declare(strict_types=1);

use App\Support\DateDisplay;

/**
 * @var array<string, mixed>       $configuration
 * @var list<array<string, mixed>> $configurationHistory
 * @var int                        $maximumCommercialWeight
 * @var array<string, string>      $validationErrors
 * @var array<string, string>|null $formAlert
 */

$configuration =
    isset($configuration)
    && is_array($configuration)
    ? $configuration
    : [];

$currentWeights =
    isset($configuration['weights'])
    && is_array(
        $configuration['weights']
    )
    ? $configuration['weights']
    : [];

$history =
    isset($configurationHistory)
    && is_array(
        $configurationHistory
    )
    ? $configurationHistory
    : [];

$errors =
    isset($validationErrors)
    && is_array($validationErrors)
    ? $validationErrors
    : [];

$formInput =
    session(
        'matchScoreFormInput'
    );

$formInput =
    is_array($formInput)
    ? $formInput
    : [];

$maximumCommercialWeight =
    max(
        0,
        (int) (
            $maximumCommercialWeight
            ?? 20
        )
    );

/*
 * Failed submissions retain their values.
 * Otherwise use the currently active configuration.
 */
$value = static function (
    string $key
) use (
    $formInput,
    $currentWeights
): int {
    if (
        array_key_exists(
            $key,
            $formInput
        )
    ) {
        return (int) $formInput[$key];
    }

    return (int) (
        $currentWeights[$key]
        ?? 0
    );
};

$preferenceWeight =
    $value(
        'preference'
    );

$profileCompletionWeight =
    $value(
        'profileCompletion'
    );

$approvedPhotoWeight =
    $value(
        'approvedPhotos'
    );

$trustWeight =
    $value(
        'trust'
    );

$commercialWeight =
    $value(
        'commercial'
    );

$this->extend(
    'Admin/Layouts/Main'
);

$this->section(
    'content'
);
?>

<div class="container-fluid">

    <div class="row">
        <div class="col-12">

            <div
                class="page-title-box
                    d-sm-flex
                    align-items-center
                    justify-content-between">

                <div>
                    <h4 class="mb-sm-0">
                        Match Score Configuration
                    </h4>

                    <p class="text-muted mb-0 mt-1">
                        Configure the weighted ranking used by member Match
                        Score ordering.
                    </p>
                </div>

            </div>
        </div>
    </div>

    <?= view(
        'Components/Alerts/FormAlert',
        [
            'alert' =>
            $formAlert
                ?? null,
        ]
    ) ?>

    <div class="row">

        <div class="col-xl-8">

            <div class="card">

                <div class="card-header">
                    <div
                        class="d-flex
                            align-items-center
                            justify-content-between
                            gap-3">

                        <div>
                            <h5 class="card-title mb-1">
                                Active Weights
                            </h5>

                            <p class="text-muted mb-0">
                                All five components must total exactly 100%.
                            </p>
                        </div>

                        <span
                            class="badge
                                bg-success-subtle
                                text-body p-2">
                            Active
                        </span>

                    </div>
                </div>

                <div class="card-body">

                    <?php if (
                        ($configuration['persisted'] ?? false)
                        !== true
                    ): ?>

                        <div
                            class="alert
                                alert-info
                                d-flex
                                align-items-start
                                gap-2"
                            role="alert">

                            <i
                                class="ri-information-line
                                    fs-20"
                                aria-hidden="true"></i>

                            <div>
                                No persisted configuration exists.
                                The documented application defaults are
                                currently active.
                            </div>
                        </div>

                    <?php endif; ?>

                    <form
                        method="post"
                        action="<?= route_to(
                                    'admin.match-score.update'
                                ) ?>"
                        data-match-score-form
                        data-submit-form
                        novalidate>

                        <?= csrf_field() ?>

                        <div class="row g-3">

                            <div class="col-md-6">

                                <label
                                    for="preferenceWeight"
                                    class="form-label">

                                    Partner Preference
                                </label>

                                <div class="input-group">
                                    <input
                                        type="number"
                                        class="form-control
                                            <?= isset(
                                                $errors['preference']
                                            )
                                                ? 'is-invalid'
                                                : '' ?>"
                                        id="preferenceWeight"
                                        name="preference"
                                        min="0"
                                        max="100"
                                        step="1"
                                        required
                                        data-match-score-weight
                                        value="<?= esc(
                                                    (string)
                                                    $preferenceWeight,
                                                    'attr'
                                                ) ?>">

                                    <span class="input-group-text">
                                        %
                                    </span>

                                    <?php if (
                                        isset(
                                            $errors['preference']
                                        )
                                    ): ?>
                                        <div class="invalid-feedback">
                                            <?= esc(
                                                $errors['preference']
                                            ) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="form-text">
                                    Viewer-specific Partner Preference
                                    compatibility.
                                </div>

                            </div>

                            <div class="col-md-6">

                                <label
                                    for="profileCompletionWeight"
                                    class="form-label">

                                    Profile Completion
                                </label>

                                <div class="input-group">
                                    <input
                                        type="number"
                                        class="form-control
                                            <?= isset(
                                                $errors['profileCompletion']
                                            )
                                                ? 'is-invalid'
                                                : '' ?>"
                                        id="profileCompletionWeight"
                                        name="profileCompletion"
                                        min="0"
                                        max="100"
                                        step="1"
                                        required
                                        data-match-score-weight
                                        value="<?= esc(
                                                    (string)
                                                    $profileCompletionWeight,
                                                    'attr'
                                                ) ?>">

                                    <span class="input-group-text">
                                        %
                                    </span>

                                    <?php if (
                                        isset(
                                            $errors['profileCompletion']
                                        )
                                    ): ?>
                                        <div class="invalid-feedback">
                                            <?= esc(
                                                $errors['profileCompletion']
                                            ) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="form-text">
                                    Authoritative cached profile completion.
                                </div>

                            </div>

                            <div class="col-md-6">

                                <label
                                    for="approvedPhotoWeight"
                                    class="form-label">

                                    Approved Photos
                                </label>

                                <div class="input-group">
                                    <input
                                        type="number"
                                        class="form-control
                                            <?= isset(
                                                $errors['approvedPhotos']
                                            )
                                                ? 'is-invalid'
                                                : '' ?>"
                                        id="approvedPhotoWeight"
                                        name="approvedPhotos"
                                        min="0"
                                        max="100"
                                        step="1"
                                        required
                                        data-match-score-weight
                                        value="<?= esc(
                                                    (string)
                                                    $approvedPhotoWeight,
                                                    'attr'
                                                ) ?>">

                                    <span class="input-group-text">
                                        %
                                    </span>

                                    <?php if (
                                        isset(
                                            $errors['approvedPhotos']
                                        )
                                    ): ?>
                                        <div class="invalid-feedback">
                                            <?= esc(
                                                $errors['approvedPhotos']
                                            ) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="form-text">
                                    Normalized to maximum contribution at
                                    three approved photos.
                                </div>

                            </div>

                            <div class="col-md-6">

                                <label
                                    for="trustWeight"
                                    class="form-label">

                                    Trust &amp; Verification
                                </label>

                                <div class="input-group">
                                    <input
                                        type="number"
                                        class="form-control
                                            <?= isset(
                                                $errors['trust']
                                            )
                                                ? 'is-invalid'
                                                : '' ?>"
                                        id="trustWeight"
                                        name="trust"
                                        min="0"
                                        max="100"
                                        step="1"
                                        required
                                        data-match-score-weight
                                        value="<?= esc(
                                                    (string)
                                                    $trustWeight,
                                                    'attr'
                                                ) ?>">

                                    <span class="input-group-text">
                                        %
                                    </span>

                                    <?php if (
                                        isset(
                                            $errors['trust']
                                        )
                                    ): ?>
                                        <div class="invalid-feedback">
                                            <?= esc(
                                                $errors['trust']
                                            ) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="form-text">
                                    Mobile, Email, Aadhaar and Live
                                    Introduction verification.
                                </div>

                            </div>

                            <div class="col-md-6">

                                <label
                                    for="commercialWeight"
                                    class="form-label">

                                    Membership Priority
                                </label>

                                <div class="input-group">
                                    <input
                                        type="number"
                                        class="form-control
                                            <?= isset(
                                                $errors['commercial']
                                            )
                                                ? 'is-invalid'
                                                : '' ?>"
                                        id="commercialWeight"
                                        name="commercial"
                                        min="0"
                                        max="<?= esc(
                                                    (string)
                                                    $maximumCommercialWeight,
                                                    'attr'
                                                ) ?>"
                                        step="1"
                                        required
                                        data-match-score-weight
                                        value="<?= esc(
                                                    (string)
                                                    $commercialWeight,
                                                    'attr'
                                                ) ?>">

                                    <span class="input-group-text">
                                        %
                                    </span>

                                    <?php if (
                                        isset(
                                            $errors['commercial']
                                        )
                                    ): ?>
                                        <div class="invalid-feedback">
                                            <?= esc(
                                                $errors['commercial']
                                            ) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="form-text">
                                    Maximum allowed weight:
                                    <?= esc(
                                        (string)
                                        $maximumCommercialWeight
                                    ) ?>%.
                                </div>

                            </div>

                            <div class="col-md-6">

                                <label class="form-label">
                                    Total Weight
                                </label>

                                <div
                                    class="form-control
                                        bg-light
                                        d-flex
                                        align-items-center
                                        justify-content-between"
                                    data-match-score-total-wrapper>

                                    <span>
                                        Total
                                    </span>

                                    <strong>
                                        <span
                                            data-match-score-total>
                                            <?= esc(
                                                (string) (
                                                    $preferenceWeight
                                                    + $profileCompletionWeight
                                                    + $approvedPhotoWeight
                                                    + $trustWeight
                                                    + $commercialWeight
                                                )
                                            ) ?>
                                        </span>%
                                    </strong>
                                </div>

                                <div
                                    class="<?= isset(
                                                $errors['total']
                                            )
                                                ? 'text-danger'
                                                : 'form-text' ?>"
                                    data-match-score-total-message>

                                    <?= isset(
                                        $errors['total']
                                    )
                                        ? esc(
                                            $errors['total']
                                        )
                                        : 'The total must equal exactly 100%.' ?>
                                </div>

                            </div>

                        </div>

                        <div
                            class="d-flex
                                justify-content-end
                                mt-4">

                            <button
                                type="submit"
                                class="btn
                                    registration-form__submit
                                    fs-14
                                    fw-medium
                                    text-uppercase w-25"
                                data-submit-button>

                                <span
                                    class="registration-submit__idle"
                                    data-submit-idle>

                                    <i
                                        class="mdi
                                            mdi-cloud-upload-outline
                                            fs-20"
                                        aria-hidden="true"></i>

                                    Save Configuration
                                </span>

                                <span
                                    class="registration-submit__loading
                                        d-none"
                                    data-submit-loading>

                                    <span
                                        class="spinner-border
                                            spinner-border-sm"
                                        role="status"
                                        aria-hidden="true"></span>

                                    Saving...
                                </span>

                            </button>

                        </div>

                    </form>

                </div>
            </div>

        </div>

        <div class="col-xl-4">

            <div class="card">

                <div class="card-header">
                    <h5 class="card-title mb-0">
                        Scoring Rules
                    </h5>
                </div>

                <div class="card-body">

                    <div class="d-flex gap-2 mb-3">
                        <i
                            class="ri-information-line
                                text-primary
                                fs-20"
                            aria-hidden="true"></i>

                        <p class="text-muted mb-0">
                            Match Score affects ordering only after candidate
                            eligibility and compulsory Partner Preference rules
                            have been applied.
                        </p>
                    </div>

                    <div class="d-flex gap-2 mb-3">
                        <i
                            class="ri-shield-check-line
                                text-success
                                fs-20"
                            aria-hidden="true"></i>

                        <p class="text-muted mb-0">
                            Membership priority cannot make an otherwise
                            ineligible member eligible.
                        </p>
                    </div>

                    <div class="d-flex gap-2">
                        <i
                            class="ri-user-heart-line
                                text-primary
                                fs-20"
                            aria-hidden="true"></i>

                        <p class="text-muted mb-0">
                            Final Match Score is viewer-specific because
                            Partner Preference compatibility is directional.
                        </p>
                    </div>

                </div>
            </div>

        </div>

    </div>

    <div class="row">
        <div class="col-12">

            <div class="card">

                <div class="card-header">
                    <h5 class="card-title mb-1">
                        Configuration History
                    </h5>

                    <p class="text-muted mb-0">
                        Previous configurations are retained and never
                        overwritten.
                    </p>
                </div>

                <div class="card-body p-0">

                    <div class="table-responsive">

                        <table
                            class="table
                                table-hover
                                align-middle
                                mb-0">

                            <thead class="table-light">
                                <tr>
                                    <th>Status</th>
                                    <th>Preference</th>
                                    <th>Completion</th>
                                    <th>Photos</th>
                                    <th>Trust</th>
                                    <th>Membership</th>
                                    <th>Admin ID</th>
                                    <th>Changed</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php if ($history === []): ?>

                                    <tr>
                                        <td
                                            colspan="8"
                                            class="text-center
                                                text-muted
                                                py-4">

                                            No configuration history is
                                            available.

                                        </td>
                                    </tr>

                                <?php else: ?>

                                    <?php foreach (
                                        $history
                                        as $historyRow
                                    ): ?>

                                        <?php
                                        $historyWeights =
                                            is_array(
                                                $historyRow['weights']
                                                    ?? null
                                            )
                                            ? $historyRow['weights']
                                            : [];

                                        $displayChangedAt =
                                            DateDisplay::formatUtcDateTime(
                                                $historyRow['createdAt']
                                                    ?? null
                                            );
                                        ?>

                                        <tr>

                                            <td>
                                                <?php if (
                                                    ($historyRow['isActive']
                                                        ?? false)
                                                    === true
                                                ): ?>
                                                    <span
                                                        class="badge
                                                            bg-success-subtle
                                                            text-body p-2">
                                                        Active
                                                    </span>
                                                <?php else: ?>
                                                    <span
                                                        class="badge
                                                            bg-info-subtle
                                                            text-body p-2">
                                                        Previous
                                                    </span>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <?= esc(
                                                    (string) (
                                                        $historyWeights['preference']
                                                        ?? 0
                                                    )
                                                ) ?>%
                                            </td>

                                            <td>
                                                <?= esc(
                                                    (string) (
                                                        $historyWeights['profileCompletion']
                                                        ?? 0
                                                    )
                                                ) ?>%
                                            </td>

                                            <td>
                                                <?= esc(
                                                    (string) (
                                                        $historyWeights['approvedPhotos']
                                                        ?? 0
                                                    )
                                                ) ?>%
                                            </td>

                                            <td>
                                                <?= esc(
                                                    (string) (
                                                        $historyWeights['trust']
                                                        ?? 0
                                                    )
                                                ) ?>%
                                            </td>

                                            <td>
                                                <?= esc(
                                                    (string) (
                                                        $historyWeights['commercial']
                                                        ?? 0
                                                    )
                                                ) ?>%
                                            </td>

                                            <td>
                                                <?= esc(
                                                    (string) (
                                                        $historyRow['createdByAdminId']
                                                        ?? '—'
                                                    )
                                                ) ?>
                                            </td>

                                            <td>
                                                <?= esc(
                                                    $displayChangedAt
                                                        !== ''
                                                        ? $displayChangedAt
                                                        : '—'
                                                ) ?>
                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                <?php endif; ?>

                            </tbody>
                        </table>

                    </div>
                </div>
            </div>

        </div>
    </div>

</div>

<?php
$this->endSection();
