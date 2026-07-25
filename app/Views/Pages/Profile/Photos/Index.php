<?php

declare(strict_types=1);

/**
 * Member photo-management page.
 *
 * @var array<string, mixed>       $user
 * @var list<array<string, mixed>> $photos
 * @var int                        $photoCount
 * @var int                        $maximumPhotos
 * @var int                        $remainingPhotos
 * @var array<string, string>      $validationErrors
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

$maximumPhotos = isset($maximumPhotos)
    ? max(1, (int) $maximumPhotos)
    : 5;

$remainingPhotos = isset($remainingPhotos)
    ? max(0, (int) $remainingPhotos)
    : max(0, $maximumPhotos - $photoCount);

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

<section class="py-3 py-lg-4">
    <div class="container">

        <?= view(
            'Pages/Profile/Partials/_feedback_alert',
            [
                'formAlert' => $formAlert,
            ]
        ) ?>

        <div class="row justify-content-center">
            <div class="col-12 col-xl-10">

                <div
                    class="d-flex flex-column flex-md-row
                        align-items-md-center
                        justify-content-between gap-3 mb-4">

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
                    </div>
                </div>

                <div class="row g-3 g-lg-4">

                    <div class="col-12 col-lg-4">
                        <div class="card border shadow-none">
                            <div class="card-body p-3 p-md-4">

                                <h2 class="fs-16 fw-semibold mb-1">
                                    Add a Photo
                                </h2>

                                <p class="text-muted fs-13 mb-3">
                                    JPEG, PNG or WEBP. Maximum 10 MB.
                                    Minimum size 400 × 400 pixels.
                                </p>

                                <?php if ($remainingPhotos > 0): ?>

                                    <form
                                        method="post"
                                        action="<?= url_to(
                                                    'web.profile.photos.upload'
                                                ) ?>"
                                        enctype="multipart/form-data"
                                        id="profile-photo-upload-form"
                                        novalidate>

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
                                                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                                required>

                                            <?php if (
                                                isset(
                                                    $validationErrors['photo']
                                                )
                                            ): ?>
                                                <div
                                                    class="invalid-feedback">
                                                    <?= esc(
                                                        $validationErrors['photo']
                                                    ) ?>
                                                </div>
                                            <?php endif; ?>

                                            <div
                                                class="form-text"
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

                                        <fieldset class="mb-3">
                                            <legend
                                                class="form-label
                                                    fw-semibold fs-13 mb-2">
                                                Who can view this photo?
                                            </legend>

                                            <div class="form-check mb-2">
                                                <input
                                                    class="form-check-input"
                                                    type="radio"
                                                    name="visibility"
                                                    value="PUBLIC"
                                                    id="photo-visibility-public"
                                                    <?= old(
                                                        'visibility',
                                                        'PUBLIC'
                                                    ) === 'PUBLIC'
                                                        ? 'checked'
                                                        : '' ?>>

                                                <label
                                                    class="form-check-label"
                                                    for="photo-visibility-public">

                                                    <span
                                                        class="fw-semibold
                                                            fs-13 d-block">
                                                        Public
                                                    </span>

                                                    <span
                                                        class="text-muted
                                                            fs-12">
                                                        Visible to eligible
                                                        members.
                                                    </span>
                                                </label>
                                            </div>

                                            <div class="form-check">
                                                <input
                                                    class="form-check-input"
                                                    type="radio"
                                                    name="visibility"
                                                    value="INTERESTED_MEMBERS"
                                                    id="photo-visibility-interested"
                                                    <?= old(
                                                        'visibility'
                                                    ) ===
                                                        'INTERESTED_MEMBERS'
                                                        ? 'checked'
                                                        : '' ?>>

                                                <label
                                                    class="form-check-label"
                                                    for="photo-visibility-interested">

                                                    <span
                                                        class="fw-semibold
                                                            fs-13 d-block">
                                                        Interested Members
                                                    </span>

                                                    <span
                                                        class="text-muted
                                                            fs-12">
                                                        Visible only after an
                                                        approved interest.
                                                    </span>
                                                </label>
                                            </div>

                                            <?php if (
                                                isset(
                                                    $validationErrors['visibility']
                                                )
                                            ): ?>
                                                <div
                                                    class="invalid-feedback
                                                        d-block mt-2">
                                                    <?= esc(
                                                        $validationErrors['visibility']
                                                    ) ?>
                                                </div>
                                            <?php endif; ?>
                                        </fieldset>

                                        <div class="form-check mb-3">
                                            <input
                                                class="form-check-input"
                                                type="checkbox"
                                                name="is_primary"
                                                value="1"
                                                id="profile-photo-primary"
                                                <?= old('is_primary')
                                                    ? 'checked'
                                                    : '' ?>>

                                            <label
                                                class="form-check-label fs-13"
                                                for="profile-photo-primary">
                                                Make this my main photo
                                            </label>
                                        </div>

                                        <div
                                            class="alert alert-warning
                                                py-2 px-3 fs-12 mb-3"
                                            role="alert">

                                            <i
                                                class="ri-time-line me-1"
                                                aria-hidden="true">
                                            </i>

                                            New photos remain pending until
                                            administrator approval.
                                        </div>

                                        <button
                                            type="submit"
                                            class="btn btn-primary w-100
                                                d-inline-flex
                                                align-items-center
                                                justify-content-center gap-2"
                                            id="profile-photo-submit">

                                            <span>Upload Photo</span>

                                            <span
                                                class="spinner-border
                                                    spinner-border-sm d-none"
                                                id="profile-photo-spinner"
                                                aria-hidden="true">
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
                        <div class="card border shadow-none">
                            <div class="card-body p-3 p-md-4">

                                <div class="mb-3">
                                    <h2 class="fs-16 fw-semibold mb-1">
                                        Your Photos
                                    </h2>

                                    <p class="text-muted fs-13 mb-0">
                                        Select a main photo, update its
                                        visibility or delete it.
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

                                    <div class="row g-3">

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

                                            $isPrimary = (bool) (
                                                $photo['is_primary']
                                                ?? false
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
                                            ?>

                                            <div
                                                class="col-12 col-sm-6">

                                                <article
                                                    class="card h-100
                                                        border shadow-none">

                                                    <div
                                                        class="card-body p-3">

                                                        <div
                                                            class="ratio
                                                                ratio-1x1
                                                                bg-light
                                                                border rounded
                                                                overflow-hidden
                                                                mb-3">

                                                            <?php if (
                                                                $thumbnailUrl
                                                                !== ''
                                                            ): ?>
                                                                <img
                                                                    src="<?= esc(
                                                                                $thumbnailUrl
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
                                                                        class="ri-image-line
                                                                            fs-24"
                                                                        aria-hidden="true">
                                                                    </i>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>

                                                        <div
                                                            class="d-flex
                                                                flex-wrap
                                                                gap-2 mb-3">

                                                            <span
                                                                class="badge
                                                                    text-bg-<?= esc(
                                                                                $statusClass
                                                                            ) ?>">
                                                                <?= esc(
                                                                    $statusLabel
                                                                ) ?>
                                                            </span>

                                                            <?php if (
                                                                $isPrimary
                                                            ): ?>
                                                                <span
                                                                    class="badge
                                                                        text-bg-primary">

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

                                                        <?php if (
                                                            !$isPrimary
                                                        ): ?>
                                                            <form
                                                                method="post"
                                                                action="<?= url_to(
                                                                            'web.profile.photos.primary',
                                                                            $photoId
                                                                        ) ?>"
                                                                class="mb-2">

                                                                <?= csrf_field() ?>

                                                                <button
                                                                    type="submit"
                                                                    class="btn
                                                                        btn-outline-primary
                                                                        btn-sm w-100">

                                                                    <i
                                                                        class="ri-star-line
                                                                            me-1"
                                                                        aria-hidden="true">
                                                                    </i>

                                                                    Make Main Photo
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>

                                                        <form
                                                            method="post"
                                                            action="<?= url_to(
                                                                        'web.profile.photos.visibility',
                                                                        $photoId
                                                                    ) ?>"
                                                            class="mb-2">

                                                            <?= csrf_field() ?>

                                                            <div
                                                                class="input-group
                                                                    input-group-sm">

                                                                <select
                                                                    name="visibility"
                                                                    class="form-select"
                                                                    aria-label="Photo visibility">

                                                                    <option
                                                                        value="PUBLIC"
                                                                        <?= $visibility
                                                                            ===
                                                                            'PUBLIC'
                                                                            ? 'selected'
                                                                            : '' ?>>
                                                                        Public
                                                                    </option>

                                                                    <option
                                                                        value="INTERESTED_MEMBERS"
                                                                        <?= $visibility
                                                                            ===
                                                                            'INTERESTED_MEMBERS'
                                                                            ? 'selected'
                                                                            : '' ?>>
                                                                        Interested Only
                                                                    </option>
                                                                </select>

                                                                <button
                                                                    type="submit"
                                                                    class="btn
                                                                        btn-outline-secondary">
                                                                    Save
                                                                </button>
                                                            </div>
                                                        </form>

                                                        <form
                                                            method="post"
                                                            action="<?= url_to(
                                                                        'web.profile.photos.delete',
                                                                        $photoId
                                                                    ) ?>"
                                                            data-photo-delete-form>

                                                            <?= csrf_field() ?>

                                                            <button
                                                                type="submit"
                                                                class="btn
                                                                    btn-outline-danger
                                                                    btn-sm w-100">

                                                                <i
                                                                    class="ri-delete-bin-line
                                                                        me-1"
                                                                    aria-hidden="true">
                                                                </i>

                                                                Delete Photo
                                                            </button>
                                                        </form>

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

                <div class="alert alert-light border mt-3 mb-0">
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

            </div>
        </div>
    </div>
</section>

<?php $this->endSection(); ?>