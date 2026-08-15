<?php

declare(strict_types=1);

use App\Support\DateDisplay;

/**
 * @var array<string, mixed>       $submission
 * @var string                     $documentDownloadUrl
 * @var list<array<string, mixed>> $photos
 * @var list<array<string, mixed>> $history
 * @var array<string, string>      $validationErrors
 * @var array<string, string>|null $formAlert
 */

$resolvedSubmission = isset($submission) && is_array($submission)
    ? $submission
    : [];

$resolvedDocumentDownloadUrl = trim(
    (string) ($documentDownloadUrl ?? '')
);

$resolvedPhotos = isset($photos) && is_array($photos)
    ? $photos
    : [];

$resolvedHistory = isset($history) && is_array($history)
    ? $history
    : [];

$errors = isset($validationErrors) && is_array($validationErrors)
    ? $validationErrors
    : [];

$reference = trim(
    (string) ($resolvedSubmission['profile_ref_number'] ?? '')
);

$storedDob = trim(
    (string) ($resolvedSubmission['aadhaar_date_of_birth'] ?? '')
);

$dobParts = preg_match(
    '/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/',
    $storedDob,
    $matches
) === 1
    ? [$matches[1], $matches[2], $matches[3]]
    : ['', '', ''];

$selectedYear = old('birth_year', $dobParts[0]);
$selectedMonth = old('birth_month', $dobParts[1]);
$selectedDay = old('birth_day', $dobParts[2]);

$maximumBirthYear = (int) date('Y') - 18;
$minimumBirthYear = $maximumBirthYear - 42;

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

$gender = strtoupper(
    trim((string) ($resolvedSubmission['gender'] ?? ''))
);

$memberProfileDob = DateDisplay::formatDate(
    $resolvedSubmission['date_of_birth'] ?? null
);

$openRejectModal = isset($errors['rejection_reason']);

$this->extend('Admin/Layouts/Main');
$this->section('content');
?>

<div class="container-fluid">
    <a
        href="<?= route_to('admin.members.aadhaar-approvals') ?>"
        class="d-inline-flex align-items-center gap-1 mb-3">
        <i class="ri-arrow-left-line" aria-hidden="true"></i>
        Back to pending Aadhaar
    </a>

    <?= view(
        'Components/Alerts/FormAlert',
        ['alert' => $formAlert ?? null]
    ) ?>

    <div class="row g-4">
        <div class="col-12 col-xl-7">
            <div class="card border border-danger border-opacity-25">
                <div class="card-header">
                    <h1 class="fs-18 fw-semibold mb-1">
                        Aadhaar Document
                    </h1>

                    <p class="text-muted fs-13 mb-0">
                        Uploaded
                        <?= esc(
                            DateDisplay::formatUtcDateTime(
                                $resolvedSubmission['uploaded_at'] ?? null
                            )
                        ) ?>
                    </p>
                </div>

                <div class="card-body">
                    <p class="text-muted mb-3">
                        The Aadhaar document is not displayed on this page.
                        Use the secure link below when it is required for
                        review.
                    </p>

                    <a
                        href="<?= esc(
                                    $resolvedDocumentDownloadUrl,
                                    'attr'
                                ) ?>"
                        class="btn btn-outline-primary" target="_new">
                        <i
                            class="ri-download-2-line me-1"
                            aria-hidden="true"></i>
                        Download Aadhaar document
                    </a>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="card border border-danger border-opacity-25 mb-4">
                <div class="card-header">
                    <h2 class="fs-16 fw-semibold mb-0">
                        Member Details
                    </h2>
                </div>

                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5">Name</dt>
                        <dd class="col-7">
                            <?= esc(
                                (string) (
                                    $resolvedSubmission['full_name']
                                    ?? 'Member'
                                )
                            ) ?>
                        </dd>

                        <dt class="col-5">Member ID</dt>
                        <dd class="col-7"><?= esc($reference) ?></dd>

                        <dt class="col-5">Date of birth</dt>
                        <dd class="col-7">
                            <?= esc($memberProfileDob) ?>
                        </dd>

                        <dt class="col-5">Gender</dt>
                        <dd class="col-7">
                            <?php if ($gender === 'M'): ?>
                                Male
                            <?php elseif ($gender === 'F'): ?>
                                Female
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </dd>

                        <dt class="col-5">Location</dt>
                        <dd class="col-7">
                            <?= esc(
                                trim(
                                    (string) (
                                        $resolvedSubmission['location']
                                        ?? ''
                                    )
                                ) ?: '—'
                            ) ?>
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card border border-danger border-opacity-25">
                <div class="card-header">
                    <h2 class="fs-16 fw-semibold mb-0">
                        Approve Aadhaar
                    </h2>
                </div>

                <div class="card-body">
                    <form
                        method="post"
                        action="<?= route_to(
                                    'admin.members.aadhaar-approvals.approve',
                                    $reference
                                ) ?>"
                        data-submit-loader
                        data-confirm-form
                        data-confirm-title="Approve Aadhaar?"
                        data-confirm-message="Approve this Aadhaar and save its name and date of birth as separate verification details?"
                        data-confirm-button-text="Approve"
                        data-confirm-loading-text="Approving..."
                        data-confirm-button-class="btn-success"
                        data-confirm-icon="ri-checkbox-circle-line">

                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label
                                for="aadhaarName"
                                class="form-label">
                                Name on Aadhaar
                                <span class="text-danger">*</span>
                            </label>

                            <input
                                type="text"
                                id="aadhaarName"
                                name="aadhaar_name"
                                class="form-control <?= isset(
                                                        $errors['aadhaar_name']
                                                    ) ? 'is-invalid' : '' ?>"
                                value="<?= esc(
                                            old(
                                                'aadhaar_name',
                                                (string) (
                                                    $resolvedSubmission['aadhaar_name'] ?? ''
                                                )
                                            ),
                                            'attr'
                                        ) ?>"
                                maxlength="100"
                                required>

                            <div class="invalid-feedback">
                                <?= esc(
                                    $errors['aadhaar_name']
                                        ?? 'Please enter the name shown on Aadhaar.'
                                ) ?>
                            </div>
                        </div>

                        <fieldset class="mb-3">
                            <legend class="form-label fs-14">
                                Date of birth on Aadhaar
                                <span class="text-danger">*</span>
                            </legend>

                            <div class="row g-2">
                                <div class="col-4">
                                    <label
                                        for="aadhaarBirthDay"
                                        class="visually-hidden">
                                        Birth day
                                    </label>

                                    <select
                                        id="aadhaarBirthDay"
                                        name="birth_day"
                                        class="form-select <?= isset(
                                                                $errors['date_of_birth']
                                                            ) ? 'is-invalid' : '' ?>"
                                        required>
                                        <option value="">Day</option>

                                        <?php for (
                                            $day = 1;
                                            $day <= 31;
                                            $day++
                                        ): ?>
                                            <?php
                                            $value = str_pad(
                                                (string) $day,
                                                2,
                                                '0',
                                                STR_PAD_LEFT
                                            );
                                            ?>
                                            <option
                                                value="<?= $value ?>"
                                                <?= $selectedDay === $value
                                                    ? 'selected'
                                                    : '' ?>>
                                                <?= $value ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>

                                <div class="col-4">
                                    <label
                                        for="aadhaarBirthMonth"
                                        class="visually-hidden">
                                        Birth month
                                    </label>

                                    <select
                                        id="aadhaarBirthMonth"
                                        name="birth_month"
                                        class="form-select <?= isset(
                                                                $errors['date_of_birth']
                                                            ) ? 'is-invalid' : '' ?>"
                                        required>
                                        <option value="">Month</option>

                                        <?php foreach (
                                            $months as $value => $label
                                        ): ?>
                                            <option
                                                value="<?= $value ?>"
                                                <?= $selectedMonth === $value
                                                    ? 'selected'
                                                    : '' ?>>
                                                <?= esc($label) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-4">
                                    <label
                                        for="aadhaarBirthYear"
                                        class="visually-hidden">
                                        Birth year
                                    </label>

                                    <select
                                        id="aadhaarBirthYear"
                                        name="birth_year"
                                        class="form-select <?= isset(
                                                                $errors['date_of_birth']
                                                            ) ? 'is-invalid' : '' ?>"
                                        required>
                                        <option value="">Year</option>

                                        <?php for (
                                            $year = $maximumBirthYear;
                                            $year >= $minimumBirthYear;
                                            $year--
                                        ): ?>
                                            <option
                                                value="<?= $year ?>"
                                                <?= (string) $selectedYear
                                                    === (string) $year
                                                    ? 'selected'
                                                    : '' ?>>
                                                <?= $year ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>

                            <?php if (
                                isset($errors['date_of_birth'])
                            ): ?>
                                <div class="text-danger fs-12 mt-1">
                                    <?= esc(
                                        $errors['date_of_birth']
                                    ) ?>
                                </div>
                            <?php endif; ?>
                        </fieldset>

                        <button
                            type="submit"
                            class="btn btn-success w-100"
                            data-submit-button>
                            <span data-submit-idle>
                                <i
                                    class="ri-checkbox-circle-line me-1"
                                    aria-hidden="true"></i>
                                Approve Aadhaar
                            </span>

                            <span
                                data-submit-loading
                                class="d-none">
                                <span
                                    class="spinner-border spinner-border-sm me-1"
                                    aria-hidden="true"></span>
                                Approving...
                            </span>
                        </button>
                    </form>
                </div>
            </div>

            <div class="card border border-danger border-opacity-25 mt-4">
                <div class="card-header">
                    <h2 class="fs-16 fw-semibold mb-0">
                        Reject Aadhaar
                    </h2>
                </div>

                <div class="card-body">
                    <p class="text-muted">
                        Reject this submission and allow the member to
                        upload another Aadhaar document.
                    </p>

                    <button
                        type="button"
                        class="btn btn-outline-danger w-100"
                        data-bs-toggle="modal"
                        data-bs-target="#rejectAadhaarModal">
                        <i
                            class="ri-close-circle-line me-1"
                            aria-hidden="true"></i>
                        Reject Aadhaar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card border border-danger border-opacity-25 mt-4">
        <div class="card-header">
            <h2 class="fs-16 fw-semibold mb-0">
                Member Photos
            </h2>
        </div>

        <div class="card-body">
            <div class="row g-3">
                <?php if ($resolvedPhotos === []): ?>
                    <p class="text-muted mb-0">
                        No uploaded member photos.
                    </p>
                <?php endif; ?>

                <?php foreach ($resolvedPhotos as $photo): ?>
                    <div class="col-6 col-md-4 col-xl-2">
                        <img
                            src="<?= esc(
                                        (string) (
                                            $photo['thumbnailUrl'] ?? ''
                                        ),
                                        'attr'
                                    ) ?>"
                            alt="Member profile photo"
                            class="img-fluid rounded border"
                            referrerpolicy="no-referrer">

                        <span
                            class="badge bg-secondary-subtle text-body-secondary mt-1">
                            <?= esc(
                                (string) ($photo['status'] ?? '')
                            ) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="card border border-danger border-opacity-25 mt-4">
        <div class="card-header">
            <h2 class="fs-16 fw-semibold mb-0">
                Aadhaar Upload History
            </h2>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-nowrap align-middle mb-0">
                    <thead class="bg-info-subtle">
                        <tr>
                            <th>Uploaded</th>
                            <th>Status</th>
                            <th>Reviewed</th>
                            <th>Performed by</th>
                            <th>Reason</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($resolvedHistory as $item): ?>
                            <tr>
                                <td>
                                    <?= esc(
                                        DateDisplay::formatUtcDateTime(
                                            $item['uploaded_at'] ?? null
                                        )
                                    ) ?>
                                </td>

                                <td>
                                    <?= esc(
                                        str_replace(
                                            '_',
                                            ' ',
                                            (string) (
                                                $item['status'] ?? ''
                                            )
                                        )
                                    ) ?>
                                </td>

                                <td>
                                    <?= esc(
                                        DateDisplay::formatUtcDateTime(
                                            $item['reviewed_at'] ?? null
                                        )
                                    ) ?>
                                </td>

                                <td>
                                    <?= esc(
                                        trim(
                                            (string) (
                                                $item['reviewer_name']
                                                ?? ''
                                            )
                                        ) ?: '—'
                                    ) ?>
                                </td>

                                <td>
                                    <?= esc(
                                        trim(
                                            (string) (
                                                $item['rejection_reason'] ?? ''
                                            )
                                        ) ?: '—'
                                    ) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div
    class="modal fade"
    id="rejectAadhaarModal"
    tabindex="-1"
    aria-labelledby="rejectAadhaarModalLabel"
    aria-hidden="true"
    data-open-on-load="<?= $openRejectModal ? 'true' : 'false' ?>">

    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2
                    class="modal-title fs-18"
                    id="rejectAadhaarModalLabel">
                    Reject Aadhaar
                </h2>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>

            <form
                method="post"
                action="<?= route_to(
                            'admin.members.aadhaar-approvals.reject',
                            $reference
                        ) ?>"
                data-submit-loader
                data-confirm-form
                data-confirm-title="Reject Aadhaar?"
                data-confirm-message="Reject this document and allow the member to upload again?"
                data-confirm-button-text="Reject"
                data-confirm-loading-text="Rejecting..."
                data-confirm-button-class="btn-danger"
                data-confirm-icon="ri-close-circle-line">

                <?= csrf_field() ?>

                <div class="modal-body">
                    <label
                        for="aadhaarRejectionReason"
                        class="form-label">
                        Rejection reason
                        <span class="text-danger">*</span>
                    </label>

                    <textarea
                        id="aadhaarRejectionReason"
                        name="rejection_reason"
                        class="form-control <?= isset(
                                                $errors['rejection_reason']
                                            ) ? 'is-invalid' : '' ?>"
                        rows="3"
                        maxlength="500"
                        required><?= esc(
                                        old('rejection_reason')
                                    ) ?></textarea>

                    <div class="invalid-feedback">
                        <?= esc(
                            $errors['rejection_reason']
                                ?? 'Please enter a rejection reason.'
                        ) ?>
                    </div>
                </div>

                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="btn btn-danger"
                        data-submit-button>
                        <span data-submit-idle>
                            <i
                                class="ri-close-circle-line me-1"
                                aria-hidden="true"></i>
                            Reject Aadhaar
                        </span>

                        <span
                            data-submit-loading
                            class="d-none">
                            <span
                                class="spinner-border spinner-border-sm me-1"
                                aria-hidden="true"></span>
                            Rejecting...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>