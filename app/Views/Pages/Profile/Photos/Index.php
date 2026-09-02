<?php

declare(strict_types=1);

use App\Support\BooleanValue;

/**
 * Member photo-management page.
 *
 * @var array<string, mixed>       $user
 * @var list<array<string, mixed>> $photos
 * @var int                        $photoCount
 * @var int                        $maximumPhotos
 * @var int                        $remainingPhotos
 * @var array<string, string>      $validationErrors
 * @var int $approvedPhotoCount
 * @var array<string, string>|null $formAlert
 */

$user = isset($user) && is_array($user)
    ? $user
    : [];

$photos = isset($photos) && is_array($photos)
    ? $photos
    : [];

$photoCount = isset($photoCount)
    ? max(0, (int) $photoCount)
    : count($photos);

$approvedPhotoCount = isset($approvedPhotoCount)
    ? max(0, (int) $approvedPhotoCount)
    : 0;

$maximumPhotos = isset($maximumPhotos)
    ? max(1, (int) $maximumPhotos)
    : 5;

$remainingPhotos = isset($remainingPhotos)
    ? max(0, (int) $remainingPhotos)
    : max(0, $maximumPhotos - $photoCount);

$maximumPhotoSizeKb = isset(
    $maximumPhotoSizeKilobytes
)
    ? max(
        1,
        (int) $maximumPhotoSizeKilobytes
    )
    : 10240;

$maximumPhotoSizeBytes =
    $maximumPhotoSizeKb * 1024;

$maximumPhotoSizeMb = max(
    1,
    (int) ceil(
        $maximumPhotoSizeKb / 1024
    )
);

$minimumPhotoWidth = isset(
    $minimumPhotoWidth
)
    ? max(
        1,
        (int) $minimumPhotoWidth
    )
    : 300;

$minimumPhotoHeight = isset(
    $minimumPhotoHeight
)
    ? max(
        1,
        (int) $minimumPhotoHeight
    )
    : 300;

$recommendedPhotoWidth = isset(
    $recommendedPhotoWidth
)
    ? max(
        $minimumPhotoWidth,
        (int) $recommendedPhotoWidth
    )
    : 600;

$recommendedPhotoHeight = isset(
    $recommendedPhotoHeight
)
    ? max(
        $minimumPhotoHeight,
        (int) $recommendedPhotoHeight
    )
    : 600;

$validationErrors = isset($validationErrors)
    && is_array($validationErrors)
    ? $validationErrors
    : [];

$formAlert = isset($formAlert)
    && is_array($formAlert)
    ? $formAlert
    : null;

$this->extend('Layouts/Main');
$this->section('content');
?>

<section class="py-3 py-lg-3">
    <div class="container">



        <div class="row justify-content-center">
            <div class="col-12 col-xl-10">

                <div
                    class="d-flex flex-column flex-md-row
                        align-items-md-center
                        justify-content-between gap-3 mb-3">

                    <div>
                        <a
                            href="<?= url_to('web.profile.edit') ?>"
                            class="d-inline-flex align-items-center
                                gap-1 text-primary fw-medium mb-3">

                            <i
                                class="ri-arrow-left-line"
                                aria-hidden="true">
                            </i>

                            Back to Profile
                        </a>

                        <div class="d-flex align-items-center gap-3">
                            <div
                                class="avatar-sm rounded-circle
                                    bg-danger-subtle text-danger
                                    d-flex align-items-center
                                    justify-content-center"
                                aria-hidden="true">

                                <i class="ri-image-add-line fs-20"></i>
                            </div>

                            <div>
                                <h1 class="fs-18 fw-semibold mb-1">
                                    Manage Photos
                                </h1>

                                <p class="text-muted fs-13 mb-0">
                                    Upload up to five photos and select
                                    your main profile photo.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="text-md-end">
                        <span class="fw-semibold">
                            <?= esc((string) $photoCount) ?>
                            of
                            <?= esc((string) $maximumPhotos) ?>
                        </span>

                        <span class="text-muted fs-13 d-block">
                            photos uploaded
                        </span>

                        <span class="text-success fs-12 d-block mt-1">
                            <?= esc((string) $approvedPhotoCount) ?>
                            <?= $approvedPhotoCount === 1
                                ? 'photo approved'
                                : 'photos approved' ?>
                        </span>
                    </div>
                </div>
                <div class="alert alert-light border mt-0 mb-3">
                    <div class="d-flex gap-2">

                        <i
                            class="ri-shield-check-line
                                text-success fs-18"
                            aria-hidden="true">
                        </i>

                        <div>
                            <h2 class="fs-14 fw-semibold mb-1">
                                Photo Guidelines
                            </h2>

                            <p class="text-muted fs-12 mb-0">
                                Upload a recent, clear photograph showing
                                your face. Avoid group photos, screenshots,
                                contact details, offensive content and
                                heavily edited images.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="row g-3 g-lg-4">

                    <div class="col-12 col-lg-4">
                        <div class="card border border-danger border-opacity-25 shadow-none">
                            <div class="card-body p-3 p-md-4">

                                <h2 class="fs-16 fw-semibold mb-1">
                                    Add a Photo
                                </h2>

                                <p class="text-muted fs-13 mb-2">
                                    JPEG or PNG.
                                    Maximum
                                    <?= esc(
                                        (string) $maximumPhotoSizeMb
                                    ) ?>
                                    MB.
                                    Minimum
                                    <?= esc(
                                        (string) $minimumPhotoWidth
                                    ) ?>
                                    ×
                                    <?= esc(
                                        (string) $minimumPhotoHeight
                                    ) ?>
                                    pixels.
                                </p>

                                <p class="form-text color-pink mb-3">
                                    For best quality, upload a photo at least
                                    <?= esc(
                                        (string) $recommendedPhotoWidth
                                    ) ?>
                                    ×
                                    <?= esc(
                                        (string) $recommendedPhotoHeight
                                    ) ?>
                                    pixels.
                                </p>

                                <?php if ($remainingPhotos > 0): ?>

                                    <form
                                        method="post"
                                        action="<?= url_to(
                                                    'web.profile.photos.upload'
                                                ) ?>"
                                        enctype="multipart/form-data"
                                        id="profile-photo-upload-form"
                                        novalidate
                                        data-profile-photo-form>

                                        <?= csrf_field() ?>

                                        <div class="mb-3">
                                            <label
                                                for="profile-photo-input"
                                                class="form-label
                                                    fw-semibold fs-13">
                                                Select Photo
                                            </label>

                                            <input
                                                type="file"
                                                name="photo"
                                                id="profile-photo-input"
                                                class="form-control
                                                    <?= isset(
                                                        $validationErrors['photo']
                                                    )
                                                        ? 'is-invalid'
                                                        : '' ?>"
                                                accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                                                data-maximum-file-size="<?= esc(
                                                                            (string) $maximumPhotoSizeBytes,
                                                                            'attr'
                                                                        ) ?>"

                                                data-maximum-file-size-label="<?= esc(
                                                                                    $maximumPhotoSizeMb . ' MB',
                                                                                    'attr'
                                                                                ) ?>"

                                                data-minimum-width="<?= esc(
                                                                        (string) $minimumPhotoWidth,
                                                                        'attr'
                                                                    ) ?>"

                                                data-minimum-height="<?= esc(
                                                                            (string) $minimumPhotoHeight,
                                                                            'attr'
                                                                        ) ?>"
                                                required>

                                            <div
                                                class="invalid-feedback"
                                                id="profile-photo-error">

                                                <?= esc(
                                                    $validationErrors['photo']
                                                        ?? 'Please select a valid JPEG or PNG photo.'
                                                ) ?>
                                            </div>

                                            <div
                                                class="form-text color-pink"
                                                id="profile-photo-file-name">
                                                No photo selected
                                            </div>
                                        </div>

                                        <div
                                            id="profile-photo-preview-wrapper"
                                            class="border rounded p-2 mb-3
                                                text-center d-none">

                                            <img
                                                src=""
                                                alt="Selected photo preview"
                                                id="profile-photo-preview"
                                                class="img-fluid rounded">
                                        </div>

                                        <div class="mb-3">
                                            <label
                                                for="profile-photo-visibility"
                                                class="form-label">

                                                Who can view this photo?
                                            </label>

                                            <select
                                                name="visibility"
                                                id="profile-photo-visibility"
                                                class="form-select
            <?= isset($validationErrors['visibility'])
                                        ? 'is-invalid'
                                        : '' ?>"
                                                data-choice
                                                data-choice-search="false"
                                                required>

                                                <option value="">
                                                    Select photo visibility
                                                </option>

                                                <option
                                                    value="PUBLIC"
                                                    <?= old(
                                                        'visibility',
                                                        'PUBLIC'
                                                    ) === 'PUBLIC'
                                                        ? 'selected'
                                                        : '' ?>>

                                                    Public
                                                </option>

                                                <option
                                                    value="INTERESTED_MEMBERS"
                                                    <?= old('visibility') ===
                                                        'INTERESTED_MEMBERS'
                                                        ? 'selected'
                                                        : '' ?>>

                                                    Interested members only
                                                </option>
                                            </select>

                                            <div class="form-text color-pink">
                                                Public photos are visible to eligible members.
                                                Interested-only photos are shown after an approved
                                                interest.
                                            </div>

                                            <div class="invalid-feedback">
                                                <?= esc(
                                                    $validationErrors['visibility']
                                                        ?? 'Please select who can view this photo.'
                                                ) ?>
                                            </div>
                                        </div>

                                        <div class="form-check mb-2">
                                            <input
                                                class="form-check-input"
                                                type="checkbox"
                                                name="make_primary"
                                                value="1"
                                                id="profile-photo-primary"
                                                <?= old('make_primary')
                                                    ? 'checked'
                                                    : '' ?>>

                                            <label
                                                class="form-check-label fs-13 fw-medium"
                                                for="profile-photo-primary">

                                                Set as my main profile photo
                                            </label>
                                        </div>

                                        <p class="fs-12 mb-3 color-pink">
                                            Only one photo can be your main profile photo.
                                            Selecting another photo as main will replace the
                                            current selection.
                                        </p>

                                        <div
                                            class="alert alert-warning
                                                py-2 px-2 fw-medium fs-12 mb-3"
                                            role="alert">

                                            <i
                                                class="ri-time-line me-1"
                                                aria-hidden="true">
                                            </i>

                                            New photos remain pending until
                                            approval.
                                        </div>
                                        <button
                                            type="submit"
                                            class="btn registration-form__submit
                                fs-14 fw-semibold text-uppercase"
                                            id="profile-photo-submit">
                                            <span
                                                class="registration-submit__label">
                                                Upload Photo
                                            </span>

                                            <span
                                                class="registration-submit__loading
                                    d-none"
                                                aria-hidden="true">
                                                <span
                                                    class="spinner-border
                                        spinner-border-sm"
                                                    role="status"
                                                    aria-hidden="true"></span>

                                                <span>
                                                    Uploading photo...
                                                </span>
                                            </span>
                                        </button>

                                    </form>

                                <?php else: ?>

                                    <div
                                        class="alert alert-info mb-0"
                                        role="status">

                                        <i
                                            class="ri-information-line me-1"
                                            aria-hidden="true">
                                        </i>

                                        You have uploaded the maximum of
                                        <?= esc(
                                            (string) $maximumPhotos
                                        ) ?>
                                        photos. Delete a photo before
                                        uploading another.
                                    </div>

                                <?php endif; ?>

                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-8">
                        <div class="card border border-danger border-opacity-25 shadow-none">
                            <div class="card-body p-3 p-md-4">
                                <?= view(
                                    'Pages/Profile/Partials/_feedback_alert',
                                    [
                                        'formAlert' => $formAlert,
                                    ]
                                ) ?>
                                <div class="mb-3">
                                    <h2 class="fs-16 fw-semibold mb-1">
                                        Your Photos
                                    </h2>

                                    <p class="text-muted fs-13 mb-0">
                                        Only one photo can be selected as your main profile photo.
                                        Selecting a different main photo will replace the current one.
                                    </p>
                                </div>

                                <?php if ($photos === []): ?>

                                    <div
                                        class="border rounded
                                            text-center p-4 p-md-5">

                                        <div
                                            class="avatar-lg rounded-circle
                                                bg-light text-muted
                                                d-inline-flex
                                                align-items-center
                                                justify-content-center mb-3"
                                            aria-hidden="true">

                                            <i
                                                class="ri-image-line fs-24">
                                            </i>
                                        </div>

                                        <h3 class="fs-15 fw-semibold mb-1">
                                            No Photos Uploaded
                                        </h3>

                                        <p class="text-muted fs-13 mb-0">
                                            Upload your first clear and
                                            recent photograph.
                                        </p>
                                    </div>

                                <?php else: ?>

                                    <div class="row g-2 g-md-3">

                                        <?php foreach ($photos as $photo): ?>
                                            <?php
                                            $photoId = (int) (
                                                $photo['id'] ?? 0
                                            );

                                            $status = strtoupper(
                                                (string) (
                                                    $photo['status']
                                                    ?? 'PENDING'
                                                )
                                            );

                                            $visibility = strtoupper(
                                                (string) (
                                                    $photo['visibility']
                                                    ?? 'PUBLIC'
                                                )
                                            );

                                            $isPrimary = BooleanValue::fromDatabase(
                                                $photo['is_primary'] ?? false
                                            );

                                            $thumbnailUrl = trim(
                                                (string) (
                                                    $photo['signedUrls']['thumbnailUrl']
                                                    ?? ''
                                                )
                                            );

                                            $statusClass = match ($status) {
                                                'APPROVED' => 'success',
                                                'REJECTED' => 'danger',
                                                default => 'warning',
                                            };

                                            $statusLabel = match ($status) {
                                                'APPROVED' => 'Approved',
                                                'REJECTED' => 'Rejected',
                                                default => 'Pending Approval',
                                            };

                                            $ribbonClass = match ($status) {
                                                'APPROVED' => 'ribbon-success',
                                                'REJECTED' => 'ribbon-danger',
                                                default => 'ribbon-warning',
                                            };

                                            $ribbonLabel = match ($status) {
                                                'APPROVED' => 'Approved',
                                                'REJECTED' => 'Rejected',
                                                default => 'Pending Approval',
                                            };
                                            ?>

                                            <div
                                                class="col-12 col-sm-6 col-xl-4">

                                                <article
                                                    class="card border border-danger border-opacity-25
        shadow-none ribbon-box right mb-1">

                                                    <div
                                                        class="card-body p-2 pt-5">
                                                        <div
                                                            class="ribbon ribbon-shape <?= esc(
                                                                                            $ribbonClass,
                                                                                            'attr'
                                                                                        ) ?>"
                                                            aria-label="<?= esc(
                                                                            $ribbonLabel,
                                                                            'attr'
                                                                        ) ?>">

                                                            <span>
                                                                <?= esc($ribbonLabel) ?>
                                                            </span>
                                                        </div>
                                                        <div
                                                            class="profile-photo-card__media
        ratio ratio-1x1
        bg-light
        border rounded
        overflow-hidden
        mb-3">

                                                            <?php if ($thumbnailUrl !== ''): ?>

                                                                <img
                                                                    src="<?= esc(
                                                                                $thumbnailUrl,
                                                                                'attr'
                                                                            ) ?>"
                                                                    alt="Member photo"
                                                                    class="w-100 h-100
                object-fit-cover"
                                                                    loading="lazy">

                                                            <?php else: ?>

                                                                <div
                                                                    class="d-flex
                align-items-center
                justify-content-center
                text-muted">

                                                                    <i
                                                                        class="ri-image-line fs-24"
                                                                        aria-hidden="true">
                                                                    </i>
                                                                </div>

                                                            <?php endif; ?>

                                                            <!--
        Image actions are displayed on hover for pointer devices.
        They remain visible on touch devices where hover is unavailable.
    -->
                                                            <div
                                                                class="profile-photo-card__overlay
            d-flex
            align-items-end
            justify-content-center
            gap-2
            p-2">

                                                                <?php if ($isPrimary): ?>

                                                                    <span
                                                                        class="btn btn-success btn-sm
                    d-inline-flex
                    align-items-center
                    justify-content-center
                    gap-1"
                                                                        aria-label="This is your main profile photo">

                                                                        <i
                                                                            class="ri-star-fill"
                                                                            aria-hidden="true">
                                                                        </i>

                                                                        Main
                                                                    </span>

                                                                <?php else: ?>

                                                                    <form
                                                                        method="post"
                                                                        action="<?= url_to(
                                                                                    'web.profile.photos.primary',
                                                                                    $photoId
                                                                                ) ?>"
                                                                        class="mb-0"
                                                                        data-photo-primary-form>

                                                                        <?= csrf_field() ?>

                                                                        <button
                                                                            type="submit"
                                                                            class="btn btn-light btn-sm
                        d-inline-flex
                        align-items-center
                        justify-content-center
                        gap-1"
                                                                            title="Set as main photo"
                                                                            aria-label="Set this image as main profile photo"
                                                                            data-photo-primary-button>

                                                                            <span
                                                                                class="d-inline-flex
                            align-items-center
                            gap-1"
                                                                                data-primary-label>

                                                                                <i
                                                                                    class="ri-star-line"
                                                                                    aria-hidden="true">
                                                                                </i>

                                                                                Set Main
                                                                            </span>

                                                                            <span
                                                                                class="d-none
                            align-items-center
                            gap-1"
                                                                                data-primary-loading
                                                                                aria-live="polite">

                                                                                <span
                                                                                    class="spinner-border
                                spinner-border-sm"
                                                                                    aria-hidden="true">
                                                                                </span>

                                                                                Setting...
                                                                            </span>
                                                                        </button>
                                                                    </form>

                                                                <?php endif; ?>

                                                                <form
                                                                    method="post"
                                                                    action="<?= url_to(
                                                                                'web.profile.photos.delete',
                                                                                $photoId
                                                                            ) ?>"
                                                                    class="mb-0"
                                                                    data-photo-delete-form
                                                                    data-confirm-form
                                                                    data-confirm-title="Delete this photo?"
                                                                    data-confirm-message="This photo will be permanently removed from your profile. This action cannot be undone."
                                                                    data-confirm-button-text="Delete Photo"
                                                                    data-confirm-loading-text="Deleting photo..."
                                                                    data-confirm-button-class="btn-danger"
                                                                    data-confirm-icon="ri-delete-bin-line">

                                                                    <?= csrf_field() ?>

                                                                    <button
                                                                        type="submit"
                                                                        class="btn btn-danger btn-sm
                    d-inline-flex
                    align-items-center
                    justify-content-center
                    gap-1"
                                                                        title="Delete photo"
                                                                        aria-label="Delete this photo"
                                                                        data-photo-delete-button>

                                                                        <span
                                                                            class="d-inline-flex
                        align-items-center
                        gap-1"
                                                                            data-delete-label>

                                                                            <i
                                                                                class="ri-delete-bin-line"
                                                                                aria-hidden="true">
                                                                            </i>

                                                                            Delete
                                                                        </span>

                                                                        <span
                                                                            class="d-none
                        align-items-center
                        gap-1"
                                                                            data-delete-loading
                                                                            aria-live="polite">

                                                                            <span
                                                                                class="spinner-border
                            spinner-border-sm"
                                                                                aria-hidden="true">
                                                                            </span>

                                                                            Deleting...
                                                                        </span>
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </div>

                                                        <div
                                                            class="d-flex
                                                                flex-wrap
                                                                gap-2 mb-3">



                                                            <?php if (
                                                                $isPrimary
                                                            ): ?>
                                                                <span
                                                                    class="badge
                                                                        text-body p-2 bg-primary">

                                                                    <i
                                                                        class="ri-star-fill
                                                                            me-1"
                                                                        aria-hidden="true">
                                                                    </i>

                                                                    Main
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>

                                                        <div
                                                            class="d-flex
                                                                align-items-center
                                                                justify-content-between
                                                                gap-2 mb-3">

                                                            <div>
                                                                <span
                                                                    class="text-muted
                                                                        fs-12 d-block">
                                                                    Visibility
                                                                </span>

                                                                <span
                                                                    class="fw-medium
                                                                        fs-13">
                                                                    <?= $visibility
                                                                        ===
                                                                        'INTERESTED_MEMBERS'
                                                                        ? 'Interested members only'
                                                                        : 'Public' ?>
                                                                </span>
                                                            </div>

                                                            <i
                                                                class="<?= $visibility
                                                                            ===
                                                                            'INTERESTED_MEMBERS'
                                                                            ? 'ri-lock-line'
                                                                            : 'ri-global-line' ?>
                                                                    fs-18
                                                                    text-muted"
                                                                aria-hidden="true">
                                                            </i>
                                                        </div>

                                                        <!-- Photo visibility form -->
                                                        <form
                                                            method="post"
                                                            action="<?= url_to(
                                                                        'web.profile.photos.visibility',
                                                                        $photoId
                                                                    ) ?>"
                                                            id="photo-visibility-form-<?= esc(
                                                                                            (string) $photoId,
                                                                                            'attr'
                                                                                        ) ?>"
                                                            class="mb-0"
                                                            data-photo-visibility-form>

                                                            <?= csrf_field() ?>

                                                            <label
                                                                for="photo-visibility-<?= esc(
                                                                                            (string) $photoId,
                                                                                            'attr'
                                                                                        ) ?>"
                                                                class="form-label">

                                                                Photo visibility
                                                            </label>

                                                            <select
                                                                name="visibility"
                                                                id="photo-visibility-<?= esc(
                                                                                            (string) $photoId,
                                                                                            'attr'
                                                                                        ) ?>"
                                                                class="form-select"
                                                                data-photo-visibility-choice
                                                                data-choice-search="false"
                                                                required>

                                                                <option
                                                                    value="PUBLIC"
                                                                    <?= $visibility === 'PUBLIC'
                                                                        ? 'selected'
                                                                        : '' ?>>

                                                                    Public
                                                                </option>

                                                                <option
                                                                    value="INTERESTED_MEMBERS"
                                                                    <?= $visibility === 'INTERESTED_MEMBERS'
                                                                        ? 'selected'
                                                                        : '' ?>>

                                                                    Interested members only
                                                                </option>
                                                            </select>

                                                            <button
                                                                type="submit"
                                                                class="btn btn-outline-secondary
        btn-sm w-100 mt-2
        d-inline-flex
        align-items-center
        justify-content-center
        gap-1"
                                                                data-photo-visibility-button>

                                                                <span
                                                                    class="d-inline-flex
            align-items-center
            gap-1"
                                                                    data-visibility-label>

                                                                    <i
                                                                        class="ri-save-line"
                                                                        aria-hidden="true">
                                                                    </i>

                                                                    Save Visibility
                                                                </span>

                                                                <span
                                                                    class="d-none
            align-items-center
            gap-1"
                                                                    data-visibility-loading
                                                                    aria-live="polite">

                                                                    <span
                                                                        class="spinner-border
                spinner-border-sm"
                                                                        aria-hidden="true">
                                                                    </span>

                                                                    Saving...
                                                                </span>
                                                            </button>
                                                        </form>

                                                        <!-- Photo actions -->


                                                    </div>
                                                </article>
                                            </div>

                                        <?php endforeach; ?>

                                    </div>

                                <?php endif; ?>

                            </div>
                        </div>
                    </div>
                </div>



            </div>
        </div>
    </div>
</section>

<?php $this->endSection(); ?>