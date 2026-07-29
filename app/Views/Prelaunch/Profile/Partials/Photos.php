<?php

declare(strict_types=1);

/**
 * Prelaunch profile photograph upload.
 *
 * @var array<string, string>|null $validationErrors
 */

$errorBag = is_array(
    $validationErrors
        ?? null
)
    ? $validationErrors
    : [];

$photoNumbers = [
    1,
    2,
    3,
];
?>

<div class="card border border-danger border-opacity-25 shadow-sm mb-3">
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
        <hr class="my-2 mb-2">
        </hr>
        <div class="row g-2 g-md-3 pt-2">
            <?php foreach (
                $photoNumbers as
                $photoNumber
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

                $photoLabel =
                    'Photo '
                    . $photoNumber;

                $previewAlt =
                    $photoLabel
                    . ' preview';

                $photoError = trim(
                    (string) (
                        $errorBag[$fieldName]
                        ?? ''
                    )
                );

                $photoClass =
                    $photoError !== ''
                    ? 'is-invalid'
                    : '';
                ?>

                <div class="col-4">
                    <div class="h-80">
                        <label
                            for="<?= esc(
                                        $fieldName,
                                        'attr'
                                    ) ?>"
                            class="d-block position-relative ratio ratio-4x3 border rounded overflow-hidden bg-light mb-2">

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
                                class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover d-none">

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
                                                                                                                $photoClass,
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
                                required>
                        </label>

                        <div
                            id="<?= esc(
                                    $fieldName
                                        . 'Error',
                                    'attr'
                                ) ?>"
                            class="invalid-feedback text-center"
                            data-validation-error="<?= esc(
                                                        $fieldName,
                                                        'attr'
                                                    ) ?>">
                            <?= esc($photoError) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach ?>
        </div>

        <div class="form-text mt-2 color-pink">
            Maximum file size: 5 MB per photograph.
        </div>

        <div class="form-text mt-1 color-pink">
            Each photograph must be 5 MB or smaller.
        </div>
        <div class="form-text small mt-1 color-pink">
            JPG, PNG or WebP Images allowed
        </div>
    </div>
</div>