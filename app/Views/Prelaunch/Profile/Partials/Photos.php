<?php

declare(strict_types=1);

/**
 * @var array<string, string>|null $validationErrors
 */

$errorBag = is_array($validationErrors ?? null)
    ? $validationErrors
    : [];

$photoNumbers = [1, 2, 3];
?>

<div class="card border border-danger border-opacity-25 shadow-sm mb-3">
    <div class="card-body p-3 p-md-4">
        <div class="d-flex align-items-start gap-3 mb-4">
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
                    A thumbnail appears immediately after selection.
                </p>
            </div>
        </div>

        <div class="row g-3">
            <?php foreach (
                $photoNumbers as $photoNumber
            ): ?>
                <?php
                $fieldName = 'photo_'
                    . $photoNumber;

                $previewId = 'photo-preview-'
                    . $photoNumber;

                $placeholderId = $previewId
                    . '-placeholder';

                $photoLabel = 'Photo '
                    . $photoNumber;

                $previewAlt = $photoLabel
                    . ' preview';

                $photoError = trim(
                    (string) (
                        $errorBag[$fieldName]
                        ?? ''
                    )
                );

                $photoClass = $photoError !== ''
                    ? 'is-invalid'
                    : '';
                ?>

                <div class="col-12 col-md-4">
                    <div class="border rounded p-3 h-100">
                        <div
                            class="ratio ratio-1x1 bg-light rounded overflow-hidden mb-3">
                            <div
                                class="d-flex align-items-center justify-content-center">
                                <i
                                    id="<?= esc(
                                            $placeholderId,
                                            'attr'
                                        ) ?>"
                                    class="ri-image-line fs-1 text-muted"
                                    aria-hidden="true"></i>

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
                                    class="w-100 h-100 object-fit-fill d-none">
                            </div>
                        </div>

                        <label
                            for="<?= esc(
                                        $fieldName,
                                        'attr'
                                    ) ?>"
                            class="form-label">
                            <?= esc($photoLabel) ?>
                        </label>

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
                            class="form-control js-photo-input <?= esc(
                                                                    $photoClass,
                                                                    'attr'
                                                                ) ?>"
                            data-preview-target="<?= esc(
                                                        $previewId,
                                                        'attr'
                                                    ) ?>"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            required>

                        <div class="form-text color-pink">
                            JPG, PNG or WebP. Maximum 5 MB.
                        </div>

                        <?php if ($photoError !== ''): ?>
                            <div class="invalid-feedback">
                                <?= esc($photoError) ?>
                            </div>
                        <?php endif ?>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    </div>
</div>