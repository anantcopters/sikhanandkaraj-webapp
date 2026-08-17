<?php

declare(strict_types=1);

/**
 * Member Aadhaar upload modal.
 *
 * @var string                $memberName
 * @var string                $profileReference
 * @var array<string, string> $validationErrors
 * @var bool                  $openModal
 * @var string                $rejectionReason
 * @var string                $returnContext
 */

$resolvedMemberName = trim((string) ($memberName ?? 'Member'));
$resolvedReference = trim((string) ($profileReference ?? ''));
$resolvedErrors = isset($validationErrors) && is_array($validationErrors)
    ? $validationErrors
    : [];
$shouldOpen = ($openModal ?? false) === true;
$resolvedRejectionReason = trim((string) ($rejectionReason ?? ''));
$resolvedReturnContext =
    mb_strtoupper(
        trim(
            (string) (
                $returnContext
                ?? 'DASHBOARD'
            )
        )
    );

if (
    !in_array(
        $resolvedReturnContext,
        [
            'DASHBOARD',
            'PROFILE_EDIT',
        ],
        true
    )
) {
    $resolvedReturnContext =
        'DASHBOARD';
}
?>

<div
    class="modal fade"
    id="aadhaarUploadModal"
    tabindex="-1"
    aria-labelledby="aadhaarUploadModalTitle"
    aria-hidden="true"
    data-open-on-load="<?= $shouldOpen ? 'true' : 'false' ?>">

    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form
                method="post"
                action="<?= route_to('web.member.aadhaar.upload') ?>"
                enctype="multipart/form-data"
                data-submit-loader
                data-validate
                novalidate>

                <?= csrf_field() ?>
                <input
                    type="hidden"
                    name="return_context"
                    value="<?= esc(
                                $resolvedReturnContext,
                                'attr'
                            ) ?>">
                <div class="modal-header bg-info-subtle py-2">
                    <div>
                        <h2 id="aadhaarUploadModalTitle" class="modal-title fs-16 fw-semibold mb-1">
                            Upload Aadhaar
                        </h2>
                        <p class="text-muted fs-12 mb-0">
                            The document is visible only to authorized administrators.
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
                    <?php if ($resolvedRejectionReason !== ''): ?>
                        <div class="alert alert-danger" role="alert">
                            <strong>Previous document rejected:</strong>
                            <?= esc($resolvedRejectionReason) ?>
                        </div>
                    <?php endif; ?>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-sm-7">
                            <label for="aadhaarMemberName" class="form-label">Member name</label>
                            <input
                                id="aadhaarMemberName"
                                type="text"
                                class="form-control bg-light"
                                value="<?= esc($resolvedMemberName, 'attr') ?>"
                                readonly>
                        </div>
                        <div class="col-12 col-sm-5">
                            <label for="aadhaarMemberReference" class="form-label">Member ID</label>
                            <input
                                id="aadhaarMemberReference"
                                type="text"
                                class="form-control bg-light"
                                value="<?= esc($resolvedReference, 'attr') ?>"
                                readonly>
                        </div>
                    </div>

                    <label for="aadhaarDocument" class="form-label">
                        Aadhaar document <span class="text-danger">*</span>
                    </label>
                    <input
                        type="file"
                        id="aadhaarDocument"
                        name="aadhaar_document"
                        class="form-control <?= isset($resolvedErrors['aadhaar_document']) ? 'is-invalid' : '' ?>"
                        accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf"
                        data-maximum-file-size="1048576"
                        required>
                    <div class="invalid-feedback" id="aadhaarDocumentError">
                        <?= esc(
                            $resolvedErrors['aadhaar_document']
                                ?? 'Please select a JPG, JPEG, PNG or PDF file smaller than 1 MB.'
                        ) ?>
                    </div>
                    <div class="form-text color-pink">
                        JPG, JPEG, PNG or PDF · smaller than 1 MB
                    </div>

                    <div class="alert alert-warning mt-3 mb-0" role="note">
                        <div class="d-flex gap-2">
                            <i class="ri-lock-line flex-shrink-0" aria-hidden="true"></i>
                            <span class="fs-12">
                                Upload a clear document. Do not upload another person's Aadhaar.
                            </span>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-soft-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" data-submit-button>
                        <span data-submit-idle class="d-inline-flex align-items-center gap-2">
                            <i class="ri-upload-2-line" aria-hidden="true"></i>
                            Upload Aadhaar
                        </span>
                        <span data-submit-loading class="d-none align-items-center gap-2">
                            <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                            Uploading...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>