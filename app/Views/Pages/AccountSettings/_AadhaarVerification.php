<?php

declare(strict_types=1);

use App\Support\DateDisplay;

/**
 * Member Aadhaar Verification settings.
 *
 * @var array<string,mixed> $aadhaarSettings
 * @var array<string,string> $aadhaarValidationErrors
 * @var bool $openAadhaarModal
 */

$aadhaarSettings =
    isset($aadhaarSettings)
    && is_array($aadhaarSettings)
    ? $aadhaarSettings
    : [];

$aadhaarValidationErrors =
    isset($aadhaarValidationErrors)
    && is_array($aadhaarValidationErrors)
    ? $aadhaarValidationErrors
    : [];

$openAadhaarModal =
    ($openAadhaarModal ?? false)
    === true;

$status = mb_strtoupper(
    trim(
        (string) (
            $aadhaarSettings['status']
            ?? 'NOT_ADDED'
        )
    )
);

$rejectionReason = trim(
    (string) (
        $aadhaarSettings['rejectionReason']
        ?? ''
    )
);

$latest =
    isset($aadhaarSettings['latest'])
    && is_array($aadhaarSettings['latest'])
    ? $aadhaarSettings['latest']
    : null;

$history =
    isset($aadhaarSettings['history'])
    && is_array($aadhaarSettings['history'])
    ? $aadhaarSettings['history']
    : [];

$canUpload =
    ($aadhaarSettings['canUpload']
        ?? false)
    === true;

$statusLabel = match ($status) {
    'APPROVED' =>
    'Verified',

    'UNDER_REVIEW' =>
    'Under Review',

    'REJECTED' =>
    'Rejected',

    default =>
    'Not Added',
};

$statusClass = match ($status) {
    'APPROVED' =>
    'bg-success-subtle text-success',

    'UNDER_REVIEW' =>
    'bg-warning-subtle text-warning',

    'REJECTED' =>
    'bg-danger-subtle text-danger',

    default =>
    'bg-secondary-subtle text-body-secondary',
};
?>

<div
    class="d-flex flex-wrap align-items-start
        justify-content-between gap-3 mb-3">

    <div>
        <h2 class="fs-18 fw-semibold mb-1">
            Aadhaar Verification
        </h2>

        <p class="text-muted fs-13 mb-0">
            Verify your identity by submitting your Aadhaar
            document for review.
        </p>
    </div>

    <span
        class="badge <?= esc(
                            $statusClass,
                            'attr'
                        ) ?> p-2">

        <?= esc($statusLabel) ?>
    </span>
</div>


<?php if (
    $status === 'REJECTED'
    && $rejectionReason !== ''
): ?>

    <div
        class="alert alert-warning"
        role="alert">

        <strong>
            Moderator feedback:
        </strong>

        <?= esc($rejectionReason) ?>

    </div>

<?php endif; ?>


<?php if (is_array($latest)): ?>

    <dl class="row fs-13 mb-4">

        <dt class="col-sm-4">
            Submitted
        </dt>

        <dd class="col-sm-8">

            <?= esc(
                DateDisplay::formatUtcDateTime(
                    $latest['uploaded_at']
                        ?? null
                )
            ) ?>

        </dd>

        <?php if (
            !empty($latest['reviewed_at'])
        ): ?>

            <dt class="col-sm-4">
                Reviewed
            </dt>

            <dd class="col-sm-8">

                <?= esc(
                    DateDisplay::formatUtcDateTime(
                        $latest['reviewed_at']
                    )
                ) ?>

            </dd>

        <?php endif; ?>

    </dl>

<?php endif; ?>


<?php if ($status === 'APPROVED'): ?>

    <div
        class="alert alert-success fs-13"
        role="status">

        <div class="d-flex align-items-start gap-2">

            <i
                class="ri-shield-check-line fs-18"
                aria-hidden="true">
            </i>

            <div>
                <strong>
                    Aadhaar verified
                </strong>

                <p class="mb-0 mt-1">
                    Your Aadhaar verification has been
                    approved. No further action is required.
                </p>
            </div>

        </div>

    </div>

<?php elseif (
    $status === 'UNDER_REVIEW'
): ?>

    <div
        class="alert alert-warning fs-13"
        role="status">

        <div class="d-flex align-items-start gap-2">

            <i
                class="ri-time-line fs-18"
                aria-hidden="true">
            </i>

            <div>
                <strong>
                    Aadhaar under review
                </strong>

                <p class="mb-0 mt-1">
                    Your Aadhaar document has been submitted.
                    You can upload another document only if
                    this submission is rejected.
                </p>
            </div>

        </div>

    </div>

<?php endif; ?>


<?php if ($canUpload): ?>

    <div
        class="border-top pt-3">

        <div
            class="d-flex
                justify-content-end">

            <button
                type="button"
                class="btn btn-outline-danger"
                data-bs-toggle="modal"
                data-bs-target="#aadhaarUploadModal">

                <i
                    class="ri-upload-2-line me-1"
                    aria-hidden="true">
                </i>

                <?= $status === 'REJECTED'
                    ? 'Re-upload Aadhaar'
                    : 'Upload Aadhaar' ?>

            </button>

        </div>

    </div>

<?php endif; ?>


<!-- ============================================================
     AADHAAR VERIFICATION HISTORY
     ============================================================ -->
<div class="mt-4">

    <div
        class="d-flex
            align-items-center
            justify-content-between
            gap-2
            mb-3">

        <h3 class="fs-16 fw-semibold mb-0">
            Verification History
        </h3>

        <?php if ($history !== []): ?>

            <span
                class="badge
                    bg-light
                    text-body
                    border">

                <?= esc(
                    (string) count($history)
                ) ?>

            </span>

        <?php endif; ?>

    </div>

    <?php if ($history === []): ?>

        <div
            class="border rounded-3
                p-3 text-center">

            <i
                class="ri-history-line
                    text-muted fs-24"
                aria-hidden="true">
            </i>

            <p
                class="text-muted
                    fs-13 mb-0 mt-2">

                No Aadhaar verification history yet.

            </p>

        </div>

    <?php else: ?>

        <div class="table-responsive">

            <table
                class="table
                    table-sm
                    align-middle
                    mb-0">

                <thead>
                    <tr>
                        <th scope="col">
                            Submitted
                        </th>

                        <th scope="col">
                            Status
                        </th>

                        <th scope="col">
                            Reviewed
                        </th>

                        <th scope="col">
                            Feedback
                        </th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach (
                        $history
                        as $historyItem
                    ): ?>

                        <?php
                        if (!is_array($historyItem)) {
                            continue;
                        }

                        $historyStatus =
                            mb_strtoupper(
                                trim(
                                    (string) (
                                        $historyItem['status']
                                        ?? ''
                                    )
                                )
                            );

                        $historyStatusLabel =
                            match ($historyStatus) {
                                'APPROVED' =>
                                'Verified',

                                'UNDER_REVIEW' =>
                                'Under Review',

                                'REJECTED' =>
                                'Rejected',

                                default =>
                                'Unknown',
                            };

                        $historyStatusClass =
                            match ($historyStatus) {
                                'APPROVED' =>
                                'bg-success-subtle text-success',

                                'UNDER_REVIEW' =>
                                'bg-warning-subtle text-warning',

                                'REJECTED' =>
                                'bg-danger-subtle text-danger',

                                default =>
                                'bg-light text-body',
                            };

                        $historyReason = trim(
                            (string) (
                                $historyItem['rejection_reason']
                                ?? ''
                            )
                        );
                        ?>

                        <tr>

                            <td class="fs-13">

                                <?= esc(
                                    DateDisplay::formatUtcDateTime(
                                        $historyItem['uploaded_at']
                                            ?? null
                                    )
                                ) ?>

                            </td>

                            <td>

                                <span
                                    class="badge
                                        <?= esc(
                                            $historyStatusClass,
                                            'attr'
                                        ) ?>">

                                    <?= esc(
                                        $historyStatusLabel
                                    ) ?>

                                </span>

                            </td>

                            <td class="fs-13">

                                <?php if (
                                    !empty($historyItem['reviewed_at'])
                                ): ?>

                                    <?= esc(
                                        DateDisplay::formatUtcDateTime(
                                            $historyItem['reviewed_at']
                                        )
                                    ) ?>

                                <?php else: ?>

                                    <span class="text-muted">
                                        —
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td class="fs-13">

                                <?php if (
                                    $historyReason !== ''
                                ): ?>

                                    <?= esc(
                                        $historyReason
                                    ) ?>

                                <?php else: ?>

                                    <span class="text-muted">
                                        —
                                    </span>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</div>


<!-- ============================================================
     EXISTING AADHAAR UPLOAD MODAL UI
     Same member-facing modal flow, now owned by Account Settings.
     ============================================================ -->
<div
    class="modal fade"
    id="aadhaarUploadModal"
    tabindex="-1"
    aria-labelledby="aadhaarUploadModalLabel"
    aria-hidden="true">

    <div
        class="modal-dialog
            modal-dialog-centered">

        <div class="modal-content">

            <div
                class="modal-header
                    bg-info-subtle
                    py-2">

                <div>
                    <h2
                        class="modal-title fs-17"
                        id="aadhaarUploadModalLabel">

                        <?= $status === 'REJECTED'
                            ? 'Re-upload Aadhaar'
                            : 'Upload Aadhaar' ?>

                    </h2>

                    <p
                        class="text-muted
                            fs-12 mb-0">

                        Upload a clear Aadhaar document
                        for verification.

                    </p>
                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close">
                </button>

            </div>

            <form
                method="post"
                action="<?= route_to(
                            'web.member.aadhaar.upload'
                        ) ?>"
                enctype="multipart/form-data"
                data-validate
                data-submit-loader
                novalidate>

                <?= csrf_field() ?>

                <input
                    type="hidden"
                    name="return_context"
                    value="ACCOUNT_SETTINGS">

                <div class="modal-body">

                    <div class="mb-3">

                        <label
                            for="aadhaarDocument"
                            class="form-label">

                            Aadhaar Document

                            <span class="text-danger">
                                *
                            </span>

                        </label>

                        <input
                            type="file"
                            id="aadhaarDocument"
                            name="aadhaar_document"
                            class="form-control
                                <?= isset(
                                    $aadhaarValidationErrors['aadhaar_document']
                                )
                                    ? 'is-invalid'
                                    : '' ?>"
                            accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf"
                            data-error-required="Please select your Aadhaar document."
                            required>

                        <div
                            class="invalid-feedback
                                <?= isset(
                                    $aadhaarValidationErrors['aadhaar_document']
                                )
                                    ? 'd-block'
                                    : '' ?>"
                            data-validation-error="aadhaar_document">

                            <?= esc(
                                $aadhaarValidationErrors['aadhaar_document']
                                    ?? ''
                            ) ?>

                        </div>

                        <div class="form-text color-pink">
                            JPG, JPEG, PNG or PDF. Maximum
                            file size 1 MB.
                        </div>

                    </div>

                    <div
                        class="alert
                            alert-warning
                            border
                            fs-13
                            mb-0">

                        <div
                            class="d-flex
                                align-items-start
                                gap-2">

                            <i
                                class="ri-shield-check-line
                                    text-primary
                                    fs-18"
                                aria-hidden="true">
                            </i>

                            <div class="text-muted">
                                Your Aadhaar document is used
                                only for profile verification
                                and is not displayed to other
                                members.
                            </div>

                        </div>

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
                        class="btn btn-success"
                        data-submit-button>

                        <span data-submit-idle>
                            <i
                                class="ri-upload-line me-1"
                                aria-hidden="true"></i>

                            Upload Aadhaar
                        </span>

                        <span
                            data-submit-loading
                            class="d-none">

                            <span
                                class="spinner-border
                        spinner-border-sm
                        me-1"
                                aria-hidden="true"></span>

                            Uploading...
                        </span>
                    </button>



                    <!-- <button
                        type="submit"
                        class="btn
            registration-form__submit
            fs-14
            fw-medium
            text-uppercase
            w-50"
                        data-submit-button>

                        <span
                            class="registration-submit__idle"
                            data-submit-idle>

                            <i
                                class="mdi
                    mdi-cloud-upload-outline
                    fs-20"
                                aria-hidden="true">
                            </i>

                            Upload Aadhaar

                        </span>

                        <span
                            class="registration-submit__loading
                d-none"
                            data-submit-loading>

                            <span
                                class="spinner-border
                    spinner-border-sm"
                                role="status"
                                aria-hidden="true">
                            </span>

                            Uploading...

                        </span>

                    </button> -->

                </div>

            </form>

        </div>

    </div>

</div>


<?php if ($openAadhaarModal): ?>

    <script>
        document.addEventListener(
            'DOMContentLoaded',
            function() {
                const modalElement =
                    document.getElementById(
                        'aadhaarUploadModal'
                    );

                if (
                    modalElement &&
                    window.bootstrap
                ) {
                    bootstrap.Modal
                        .getOrCreateInstance(
                            modalElement
                        )
                        .show();
                }
            }
        );
    </script>

<?php endif; ?>