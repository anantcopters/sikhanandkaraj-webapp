<?php

declare(strict_types=1);

use App\Models\MemberPhotoModel;
use App\Support\DateDisplay;

/**
 * @var array<string, mixed>       $submission
 * @var string                     $documentDownloadUrl
 * @var list<array<string, mixed>> $photos
 * @var list<array<string, mixed>> $history
 * @var array<string, string>      $validationErrors
 * @var string                     $validationWorkflow
 * @var array<string, string>|null $formAlert
 */

$resolvedSubmission =
    isset($submission)
    && is_array($submission)
    ? $submission
    : [];

$resolvedDocumentDownloadUrl = trim(
    (string) (
        $documentDownloadUrl
        ?? ''
    )
);

$resolvedPhotos =
    isset($photos)
    && is_array($photos)
    ? $photos
    : [];

$resolvedHistory =
    isset($history)
    && is_array($history)
    ? $history
    : [];

$errors =
    isset($validationErrors)
    && is_array($validationErrors)
    ? $validationErrors
    : [];

$resolvedValidationWorkflow = trim(
    (string) (
        $validationWorkflow
        ?? ''
    )
);

$reference = trim(
    (string) (
        $resolvedSubmission['profile_ref_number']
        ?? ''
    )
);

/*
 * Aadhaar DOB is separate from the member's matrimonial-profile DOB.
 */
$storedAadhaarDob = trim(
    (string) (
        $resolvedSubmission['aadhaar_date_of_birth']
        ?? ''
    )
);

$dobParts = preg_match(
    '/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/',
    $storedAadhaarDob,
    $matches
) === 1
    ? [
        $matches[1],
        $matches[2],
        $matches[3],
    ]
    : [
        '',
        '',
        '',
    ];

$selectedYear = old(
    'birth_year',
    $dobParts[0]
);

$selectedMonth = old(
    'birth_month',
    $dobParts[1]
);

$selectedDay = old(
    'birth_day',
    $dobParts[2]
);

$maximumBirthYear =
    (int) date('Y') - 18;

$minimumBirthYear =
    $maximumBirthYear - 42;

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

$gender = mb_strtoupper(
    trim(
        (string) (
            $resolvedSubmission['gender']
            ?? ''
        )
    )
);

$genderLabel = match ($gender) {
    'M' => 'Male',
    'F' => 'Female',
    default => '—',
};

$memberProfileDob =
    DateDisplay::formatDate(
        $resolvedSubmission['date_of_birth']
            ?? null
    );

$openRejectModal =
    $resolvedValidationWorkflow
    === 'reject'
    && isset(
        $errors['rejection_reason']
    );

$this->extend(
    'Admin/Layouts/Main'
);

$this->section('content');
?>

<div class="container-fluid">

    <div
        class="page-title-box
            d-sm-flex
            align-items-center
            justify-content-between">

        <div>
            <h4 class="mb-sm-0">
                Review Member Aadhaar
            </h4>

            <p class="text-muted mb-0 mt-1">
                Review the submitted document and member information.
            </p>
        </div>

        <div class="page-title-right mt-3 mt-sm-0">
            <a
                href="<?= route_to(
                            'admin.members.aadhaar-approvals'
                        ) ?>"
                class="btn
                    btn-light
                    d-inline-flex
                    align-items-center
                    gap-1">

                <i
                    class="ri-arrow-left-line"
                    aria-hidden="true"></i>

                Back to Pending Aadhaar
            </a>
        </div>
    </div>

    <?= view(
        'Components/Alerts/FormAlert',
        [
            'alert' =>
            $formAlert ?? null,
        ]
    ) ?>

    <div class="row g-4 align-items-start">

        <!-- Left column: document, gallery and history -->
        <div class="col-12 col-xl-7">

            <!-- Aadhaar download -->
            <div
                class="card
                    border
                    border-danger
                    border-opacity-25
                    mb-4">

                <div class="card-header">
                    <h5 class="card-title mb-1">
                        <i
                            class="ri-file-shield-2-line me-1"
                            aria-hidden="true"></i>

                        Aadhaar Document
                    </h5>

                    <p class="text-muted fs-13 mb-0">
                        Uploaded
                        <?= esc(
                            DateDisplay::formatUtcDateTime(
                                $resolvedSubmission['uploaded_at']
                                    ?? null
                            )
                        ) ?>
                    </p>
                </div>

                <div class="card-body">
                    <p class="text-muted mb-3">
                        The Aadhaar document is not displayed on this page.
                        Use the secure download link when the document is
                        required for review.
                    </p>

                    <?php if (
                        $resolvedDocumentDownloadUrl
                        !== ''
                    ): ?>
                        <a
                            href="<?= esc(
                                        $resolvedDocumentDownloadUrl,
                                        'attr'
                                    ) ?>"
                            class="btn
                                btn-outline-primary
                                d-inline-flex
                                align-items-center
                                gap-1"
                            target="_blank"
                            rel="noopener noreferrer">

                            <i
                                class="ri-download-2-line"
                                aria-hidden="true"></i>

                            Download Aadhaar Document
                        </a>
                    <?php else: ?>
                        <div
                            class="alert
                                alert-warning
                                border-0
                                mb-0"
                            role="alert">

                            The Aadhaar document download is unavailable.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Member photo gallery -->
            <div
                class="card
                    border
                    border-danger
                    border-opacity-25
                    mb-4">

                <div
                    class="card-header
                        d-flex
                        align-items-center
                        justify-content-between
                        gap-2">

                    <div>
                        <h5 class="card-title mb-1">
                            <i
                                class="ri-gallery-line me-1"
                                aria-hidden="true"></i>

                            Member Photographs
                        </h5>

                        <p class="text-muted fs-13 mb-0">
                            Current member photographs and approval status.
                        </p>
                    </div>

                    <span
                        class="badge
                            bg-primary-subtle
                            text-body p-2">

                        <?= esc(
                            (string) count(
                                $resolvedPhotos
                            )
                        ) ?>
                    </span>
                </div>

                <div class="card-body">

                    <?php if (
                        $resolvedPhotos === []
                    ): ?>
                        <div
                            class="border
                                rounded-3
                                text-center
                                text-muted
                                p-4">

                            <i
                                class="ri-image-line
                                    fs-28
                                    d-block
                                    mb-2"
                                aria-hidden="true"></i>

                            <p class="mb-0">
                                No member photographs are available.
                            </p>
                        </div>
                    <?php else: ?>
                        <div
                            class="row
                                flex-nowrap
                                overflow-auto
                                g-3
                                pb-2">

                            <?php foreach (
                                $resolvedPhotos
                                as $index => $photo
                            ): ?>
                                <?php
                                if (!is_array($photo)) {
                                    continue;
                                }

                                $thumbnailUrl = trim(
                                    (string) (
                                        $photo['thumbnailUrl']
                                        ?? ''
                                    )
                                );

                                if ($thumbnailUrl === '') {
                                    continue;
                                }

                                $photoStatus =
                                    mb_strtoupper(
                                        trim(
                                            (string) (
                                                $photo['status']
                                                ?? ''
                                            )
                                        )
                                    );

                                $ribbonClass =
                                    match ($photoStatus) {
                                        MemberPhotoModel
                                        ::STATUS_APPROVED =>
                                        'ribbon-success',

                                        MemberPhotoModel
                                        ::STATUS_REJECTED =>
                                        'ribbon-danger',

                                        MemberPhotoModel
                                        ::STATUS_PENDING =>
                                        'ribbon-warning',

                                        default =>
                                        'ribbon-secondary',
                                    };

                                $statusLabel =
                                    match ($photoStatus) {
                                        MemberPhotoModel
                                        ::STATUS_APPROVED =>
                                        'Approved',

                                        MemberPhotoModel
                                        ::STATUS_REJECTED =>
                                        'Rejected',

                                        MemberPhotoModel
                                        ::STATUS_PENDING =>
                                        'Pending',

                                        default =>
                                        'Unknown',
                                    };
                                ?>

                                <div
                                    class="col-8
                                        col-sm-5
                                        col-md-4
                                        col-lg-3
                                        flex-shrink-0">

                                    <div
                                        class="card
                                            ribbon-box
                                            border
                                            shadow-none
                                            h-100
                                            mb-0">

                                        <div
                                            class="ribbon
                                                ribbon-shape
                                                <?= esc(
                                                    $ribbonClass,
                                                    'attr'
                                                ) ?>">

                                            <?= esc(
                                                $statusLabel
                                            ) ?>
                                        </div>

                                        <div class="card-body p-2 pt-5">
                                            <img
                                                src="<?= esc(
                                                            $thumbnailUrl,
                                                            'attr'
                                                        ) ?>"
                                                alt="<?= esc(
                                                            'Member photograph '
                                                                . ($index + 1),
                                                            'attr'
                                                        ) ?>"
                                                class="img-thumbnail w-100"
                                                loading="lazy"
                                                referrerpolicy="no-referrer">

                                            <?php if (
                                                (
                                                    $photo['isPrimary']
                                                    ?? false
                                                ) === true
                                            ): ?>
                                                <span
                                                    class="badge
                                                        bg-primary-subtle
                                                        text-body p-2
                                                        mt-2">

                                                    <i
                                                        class="ri-star-fill me-1"
                                                        aria-hidden="true"></i>

                                                    Main
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Aadhaar history -->
            <div
                class="card
                    border
                    border-danger
                    border-opacity-25">

                <div class="card-header">
                    <h5 class="card-title mb-1">
                        <i
                            class="ri-history-line me-1"
                            aria-hidden="true"></i>

                        Aadhaar Upload History
                    </h5>

                    <p class="text-muted fs-13 mb-0">
                        Previous Aadhaar submissions and review decisions.
                    </p>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table
                            class="table
                                table-nowrap
                                align-middle
                                mb-0">

                            <thead class="bg-info-subtle">
                                <tr>
                                    <th>Uploaded</th>
                                    <th>Status</th>
                                    <th>Reviewed</th>
                                    <th>Performed By</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php if (
                                    $resolvedHistory === []
                                ): ?>
                                    <tr>
                                        <td
                                            colspan="5"
                                            class="text-center
                                                text-muted
                                                py-4">

                                            No Aadhaar upload history
                                            is available.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach (
                                        $resolvedHistory
                                        as $item
                                    ): ?>
                                        <?php
                                        if (!is_array($item)) {
                                            continue;
                                        }

                                        $historyStatus =
                                            mb_strtoupper(
                                                trim(
                                                    (string) (
                                                        $item['status']
                                                        ?? ''
                                                    )
                                                )
                                            );

                                        $historyBadgeClass =
                                            match ($historyStatus) {
                                                'APPROVED' =>
                                                'bg-success-subtle text-body p-2',

                                                'REJECTED' =>
                                                'bg-danger-subtle text-body p-2',

                                                'UNDER_REVIEW' =>
                                                'bg-warning-subtle text-body p-2',

                                                default =>
                                                'bg-secondary-subtle text-body p-2',
                                            };
                                        ?>

                                        <tr>
                                            <td>
                                                <?= esc(
                                                    DateDisplay
                                                        ::formatUtcDateTime(
                                                            $item['uploaded_at']
                                                                ?? null
                                                        )
                                                ) ?>
                                            </td>

                                            <td>
                                                <span
                                                    class="badge
                                                        <?= esc(
                                                            $historyBadgeClass,
                                                            'attr'
                                                        ) ?>">

                                                    <?= esc(
                                                        ucwords(
                                                            mb_strtolower(
                                                                str_replace(
                                                                    '_',
                                                                    ' ',
                                                                    $historyStatus
                                                                )
                                                            )
                                                        )
                                                    ) ?>
                                                </span>
                                            </td>

                                            <td>
                                                <?= esc(
                                                    DateDisplay
                                                        ::formatUtcDateTime(
                                                            $item['reviewed_at']
                                                                ?? null
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
                                                            $item['rejection_reason']
                                                            ?? ''
                                                        )
                                                    ) ?: '—'
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

        <!-- Right column: member details and review actions -->
        <div class="col-12 col-xl-5">

            <!-- Member Details -->
            <div
                class="card
                    border
                    border-danger
                    border-opacity-25
                    mb-4">

                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i
                            class="ri-user-line me-1"
                            aria-hidden="true"></i>

                        Member Details
                    </h5>
                </div>

                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5">
                            Name
                        </dt>

                        <dd class="col-7">
                            <?= esc(
                                trim(
                                    (string) (
                                        $resolvedSubmission['full_name']
                                        ?? ''
                                    )
                                ) ?: 'Member'
                            ) ?>
                        </dd>

                        <dt class="col-5">
                            Member ID
                        </dt>

                        <dd class="col-7">
                            <?= esc(
                                $reference !== ''
                                    ? $reference
                                    : '—'
                            ) ?>
                        </dd>

                        <dt class="col-5">
                            Date of Birth
                        </dt>

                        <dd class="col-7">
                            <?= esc(
                                $memberProfileDob
                            ) ?>
                        </dd>

                        <dt class="col-5">
                            Gender
                        </dt>

                        <dd class="col-7">
                            <?= esc(
                                $genderLabel
                            ) ?>
                        </dd>

                        <dt class="col-5 mb-0">
                            Location
                        </dt>

                        <dd class="col-7 mb-0">
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

            <!-- Approval form -->
            <div
                class="card
                    border
                    border-danger
                    border-opacity-25">

                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i
                            class="ri-shield-check-line me-1"
                            aria-hidden="true"></i>

                        Aadhaar Review
                    </h5>
                </div>

                <div class="card-body">
                    <form
                        method="post"
                        action="<?= route_to(
                                    'admin.members.'
                                        . 'aadhaar-approvals.approve',
                                    $reference
                                ) ?>"
                        data-validate
                        novalidate
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
                                class="form-control
                <?= isset(
                    $errors['aadhaar_name']
                )
                    ? 'is-invalid'
                    : '' ?>"
                                value="<?= esc(
                                            old(
                                                'aadhaar_name',
                                                (string) (
                                                    $resolvedSubmission['aadhaar_name']
                                                    ?? ''
                                                )
                                            ),
                                            'attr'
                                        ) ?>"
                                minlength="2"
                                maxlength="100"
                                pattern="[\p{L}\p{M} .'\-]+"
                                autocomplete="off"
                                data-error-required="Please enter the name shown on Aadhaar."
                                data-error-minlength="Aadhaar name must contain at least 2 characters."
                                data-error-maxlength="Aadhaar name cannot exceed 100 characters."
                                data-error-pattern="Aadhaar name contains unsupported characters."
                                required>

                            <div
                                id="aadhaarNameError"
                                class="invalid-feedback"
                                data-validation-error="aadhaar_name">

                                <?= esc(
                                    $errors['aadhaar_name']
                                        ?? ''
                                ) ?>
                            </div>
                        </div>

                        <fieldset class="mb-3">
                            <legend class="form-label fs-14">
                                Date of Birth on Aadhaar
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
                                        class="form-select
                        <?= isset(
                            $errors['date_of_birth']
                        )
                            ? 'is-invalid'
                            : '' ?>"
                                        data-error-required="Please select the birth day."
                                        required>

                                        <option value="">
                                            Day
                                        </option>

                                        <?php for (
                                            $day = 1;
                                            $day <= 31;
                                            $day++
                                        ): ?>
                                            <?php
                                            $dayValue = str_pad(
                                                (string) $day,
                                                2,
                                                '0',
                                                STR_PAD_LEFT
                                            );
                                            ?>

                                            <option
                                                value="<?= esc(
                                                            $dayValue,
                                                            'attr'
                                                        ) ?>"
                                                <?= (string) $selectedDay
                                                    === $dayValue
                                                    ? 'selected'
                                                    : '' ?>>

                                                <?= esc($dayValue) ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>

                                    <div
                                        id="aadhaarBirthDayError"
                                        class="invalid-feedback"
                                        data-validation-error="birth_day">

                                        <?= isset(
                                            $errors['date_of_birth']
                                        )
                                            ? esc(
                                                $errors['date_of_birth']
                                            )
                                            : '' ?>
                                    </div>
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
                                        class="form-select"
                                        data-error-required="Please select the birth month."
                                        required>

                                        <option value="">
                                            Month
                                        </option>

                                        <?php foreach (
                                            $months
                                            as $value => $label
                                        ): ?>
                                            <option
                                                value="<?= esc(
                                                            $value,
                                                            'attr'
                                                        ) ?>"
                                                <?= (string) $selectedMonth
                                                    === $value
                                                    ? 'selected'
                                                    : '' ?>>

                                                <?= esc($label) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <div
                                        id="aadhaarBirthMonthError"
                                        class="invalid-feedback"
                                        data-validation-error="birth_month">
                                    </div>
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
                                        class="form-select"
                                        data-error-required="Please select the birth year."
                                        required>

                                        <option value="">
                                            Year
                                        </option>

                                        <?php for (
                                            $year = $maximumBirthYear;
                                            $year >= $minimumBirthYear;
                                            $year--
                                        ): ?>
                                            <option
                                                value="<?= esc(
                                                            (string) $year,
                                                            'attr'
                                                        ) ?>"
                                                <?= (string) $selectedYear
                                                    === (string) $year
                                                    ? 'selected'
                                                    : '' ?>>

                                                <?= esc(
                                                    (string) $year
                                                ) ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>

                                    <div
                                        id="aadhaarBirthYearError"
                                        class="invalid-feedback"
                                        data-validation-error="birth_year">
                                    </div>
                                </div>
                            </div>
                        </fieldset>

                        <div
                            class="d-flex
            flex-wrap
            justify-content-end
            gap-2">

                            <button
                                type="button"
                                class="btn btn-outline-danger"
                                data-bs-toggle="modal"
                                data-bs-target="#rejectAadhaarModal">

                                <i
                                    class="ri-close-circle-line me-1"
                                    aria-hidden="true"></i>

                                Reject
                            </button>

                            <button
                                type="submit"
                                class="btn btn-success"
                                data-submit-button>

                                <span data-submit-idle>
                                    <i
                                        class="ri-checkbox-circle-line me-1"
                                        aria-hidden="true"></i>

                                    Approve
                                </span>

                                <span
                                    data-submit-loading
                                    class="d-none">

                                    <span
                                        class="spinner-border
                        spinner-border-sm
                        me-1"
                                        aria-hidden="true"></span>

                                    Approving...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Rejection modal -->
<div
    class="modal fade"
    id="rejectAadhaarModal"
    tabindex="-1"
    aria-labelledby="rejectAadhaarModalLabel"
    aria-hidden="true"
    data-open-on-load="<?= $openRejectModal
                            ? 'true'
                            : 'false' ?>">

    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <form
                method="post"
                action="<?= route_to(
                            'admin.members.'
                                . 'aadhaar-approvals.reject',
                            $reference
                        ) ?>"
                data-validate
                novalidate
                data-submit-loader
                data-confirm-form
                data-confirm-title="Reject Aadhaar?"
                data-confirm-message="Reject this document and allow the member to upload another Aadhaar document?"
                data-confirm-button-text="Reject"
                data-confirm-loading-text="Rejecting..."
                data-confirm-button-class="btn-danger"
                data-confirm-icon="ri-close-circle-line">

                <?= csrf_field() ?>

                <div class="modal-header bg-info-subtle py-2">
                    <div>
                        <h5
                            class="modal-title mb-1"
                            id="rejectAadhaarModalLabel">

                            Reject Aadhaar
                        </h5>

                        <p class="text-muted fs-13 mb-0">
                            Member ID:
                            <?= esc(
                                $reference !== ''
                                    ? $reference
                                    : '—'
                            ) ?>
                        </p>
                    </div>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close">
                    </button>
                </div>

                <div class="modal-body">
                    <p class="text-muted">
                        Enter the reason for rejecting this Aadhaar
                        submission.
                    </p>

                    <label
                        for="aadhaarRejectionReason"
                        class="form-label">

                        Rejection Reason
                        <span class="text-danger">*</span>
                    </label>

                    <textarea
                        id="aadhaarRejectionReason"
                        name="rejection_reason"
                        class="form-control
                <?= isset(
                    $errors['rejection_reason']
                )
                    ? 'is-invalid'
                    : '' ?>"
                        rows="4"
                        minlength="3"
                        maxlength="500"
                        data-error-required="Please enter a rejection reason."
                        data-error-minlength="Rejection reason must contain at least 3 characters."
                        data-error-maxlength="Rejection reason cannot exceed 500 characters."
                        required><?= esc(
                                        old('rejection_reason')
                                    ) ?></textarea>

                    <div
                        id="aadhaarRejectionReasonError"
                        class="invalid-feedback"
                        data-validation-error="rejection_reason">

                        <?= esc(
                            $errors['rejection_reason']
                                ?? ''
                        ) ?>
                    </div>

                    <div class="form-text color-pink">
                        Enter between 3 and 500 characters.
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

                            Reject
                        </span>

                        <span
                            data-submit-loading
                            class="d-none">

                            <span
                                class="spinner-border
                        spinner-border-sm
                        me-1"
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