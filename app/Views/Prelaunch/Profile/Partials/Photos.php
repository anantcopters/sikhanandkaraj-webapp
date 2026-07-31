<?php

declare(strict_types=1);

/**
 * Prelaunch profile photograph upload section.
 *
 * Supports:
 * - Client-side validation messages through data-validation-error.
 * - Server-side CI4 validation messages after redirect.
 * - Accessible error relationships through aria-describedby.
 *
 * @var array<string, string>|null $validationErrors
 * @var array<string, string>|null $validationErrors
 * @var int|null $maximumPhotoSizeKilobytes
 */

$errorBag = is_array(
    $validationErrors ?? null
)
    ? $validationErrors
    : [];

$photoNumbers = [
    1,
    2,
    3,
];

/*
 * Detect whether any photograph has a server-side validation error.
 * This allows us to show a section-level alert in addition to the
 * individual field message.
 */
$hasPhotoErrors = false;

foreach ($photoNumbers as $photoNumber) {
    $fieldName = 'photo_' . $photoNumber;

    if (
        trim(
            (string) (
                $errorBag[$fieldName]
                ?? ''
            )
        ) !== ''
    ) {
        $hasPhotoErrors = true;
        break;
    }
}

$maximumPhotoSizeKb = max(
    1,
    (int) (
        $maximumPhotoSizeKilobytes
        ?? 18432
    )
);

$maximumPhotoSizeBytes =
    $maximumPhotoSizeKb * 1024;

$maximumPhotoSizeMb =
    (int) ceil(
        $maximumPhotoSizeKb / 1024
    );
?>

<div
    class="card border border-danger border-opacity-25 shadow-sm mb-3"
    id="photographs-section">

    <div class="card-body p-3 p-md-4">
        <div class="d-flex align-items-start gap-3 mb-3">
            <div class="fs-3 text-primary">
                <i
                    class="ri-image-add-line"
                    aria-hidden="true"></i>
            </div>

            <div>
                <h5 class="mb-1 fs-14 fw-semibold">
                    Photographs
                </h5>

                <p class="text-muted mb-0 fs-12">
                    Upload exactly three recent photographs.
                    Tap a placeholder to select an image.
                </p>
            </div>
        </div>

        <?php if ($hasPhotoErrors): ?>
            <div
                class="alert alert-danger py-2 px-3 mb-3"
                role="alert"
                aria-live="polite">

                <div class="d-flex align-items-start gap-2">
                    <i
                        class="ri-error-warning-line"
                        aria-hidden="true"></i>

                    <div class="small">
                        Please correct the photograph errors shown below.
                    </div>
                </div>
            </div>
        <?php endif ?>

        <hr class="my-2">

        <div class="row g-2 g-md-3 pt-2">
            <?php foreach (
                $photoNumbers as $photoNumber
            ): ?>
                <?php
                $fieldName =
                    'photo_'
                    . $photoNumber;

                $previewId =
                    'photo-preview-'
                    . $photoNumber;

                $placeholderId =
                    $previewId
                    . '-placeholder';

                $errorId =
                    $fieldName
                    . 'Error';

                $photoLabel =
                    'Photo '
                    . $photoNumber;

                $previewAlt =
                    $photoLabel
                    . ' preview';

                /*
                 * Read the exact CI4 validation error associated
                 * with the current uploaded photograph.
                 */
                $photoError = trim(
                    (string) (
                        $errorBag[$fieldName]
                        ?? ''
                    )
                );

                $hasPhotoError =
                    $photoError !== '';

                /*
                 * Apply is-invalid to the input so the field retains
                 * the standard Bootstrap invalid state.
                 */
                $photoInputClass =
                    $hasPhotoError
                    ? 'is-invalid'
                    : '';

                /*
                 * Bootstrap normally displays invalid-feedback using
                 * a sibling selector. Since the input is nested inside
                 * the label, use d-block when a server error exists.
                 *
                 * JavaScript can still populate and display the same
                 * container for client-side errors.
                 */
                $feedbackClass =
                    $hasPhotoError
                    ? 'invalid-feedback d-block text-center'
                    : 'invalid-feedback text-center';
                ?>

                <div class="col-4">
                    <div class="h-100">
                        <label
                            for="<?= esc(
                                        $fieldName,
                                        'attr'
                                    ) ?>"
                            class="d-block position-relative ratio ratio-4x3 border rounded overflow-hidden bg-light mb-2 <?= $hasPhotoError
                                                                                                                                ? 'border-danger'
                                                                                                                                : '' ?>">

                            <span
                                id="<?= esc(
                                        $placeholderId,
                                        'attr'
                                    ) ?>"
                                class="d-flex flex-column align-items-center justify-content-center text-center p-1">

                                <i
                                    class="ri-image-add-line fs-2 text-muted"
                                    aria-hidden="true"></i>

                                <span class="small fw-semibold">
                                    <?= esc($photoLabel) ?>
                                </span>

                                <span class="small text-muted d-none d-sm-block">
                                    Tap to add
                                </span>
                            </span>

                            <img
                                id="<?= esc(
                                        $previewId,
                                        'attr'
                                    ) ?>"
                                src=""
                                alt="<?= esc(
                                            $previewAlt,
                                            'attr'
                                        ) ?>"
                                class="position-absolute top-0 start-0 w-100 h-100 d-none">

                            <input
                                type="file"
                                id="<?= esc(
                                        $fieldName,
                                        'attr'
                                    ) ?>"
                                name="<?= esc(
                                            $fieldName,
                                            'attr'
                                        ) ?>"
                                class="position-absolute top-0 start-0 w-100 h-100 opacity-0 js-photo-input <?= esc(
                                                                                                                $photoInputClass,
                                                                                                                'attr'
                                                                                                            ) ?>"
                                data-maximum-file-size="<?= esc(
                                                            (string) $maximumPhotoSizeBytes,
                                                            'attr'
                                                        ) ?>"
                                data-maximum-file-size-label="<?= esc(
                                                                    $maximumPhotoSizeMb . ' MB',
                                                                    'attr'
                                                                ) ?>"
                                data-preview-target="<?= esc(
                                                            $previewId,
                                                            'attr'
                                                        ) ?>"
                                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                aria-label="<?= esc(
                                                'Select '
                                                    . strtolower(
                                                        $photoLabel
                                                    ),
                                                'attr'
                                            ) ?>"
                                aria-describedby="<?= esc(
                                                        $errorId,
                                                        'attr'
                                                    ) ?>"
                                <?= $hasPhotoError
                                    ? 'aria-invalid="true"'
                                    : '' ?>
                                required>
                        </label>

                        <div
                            id="<?= esc(
                                    $errorId,
                                    'attr'
                                ) ?>"
                            class="<?= esc(
                                        $feedbackClass,
                                        'attr'
                                    ) ?>"
                            data-validation-error="<?= esc(
                                                        $fieldName,
                                                        'attr'
                                                    ) ?>"
                            <?= $hasPhotoError
                                ? 'role="alert"'
                                : '' ?>>

                            <?= esc($photoError) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach ?>
        </div>

        <div class="form-text mt-2 color-pink">
            Maximum file size:
            <?= esc(
                (string) $maximumPhotoSizeMb
            ) ?>
            MB per photograph.
        </div>

        <div class="form-text small mt-1 color-pink">
            JPG, PNG or WebP images are allowed.
        </div>
    </div>
</div>