<?php

declare(strict_types=1);

/**
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
    ? (int) $photoCount
    : count($photos);

$maximumPhotos = isset($maximumPhotos)
    ? (int) $maximumPhotos
    : 5;

$remainingPhotos = isset($remainingPhotos)
    ? (int) $remainingPhotos
    : max(0, $maximumPhotos - $photoCount);

$validationErrors = isset($validationErrors)
    && is_array($validationErrors)
    ? $validationErrors
    : [];

$formAlert = isset($formAlert) && is_array($formAlert)
    ? $formAlert
    : null;

$this->extend('Layouts/Main');
$this->section('content');
?>

<section class="profile-photos-page py-3 py-lg-4">
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
                        justify-content-between gap-3 mb-3">

                    <div>
                        <a
                            href="<?= url_to('web.profile.edit') ?>"
                            class="d-inline-flex align-items-center
                                gap-1 text-primary fw-medium mb-2">

                            <i
                                class="ri-arrow-left-line"
                                aria-hidden="true">
                            </i>

                            Back to Profile
                        </a>

                        <div
                            class="d-flex align-items-center
                                gap-2 mt-2">

                            <div
                                class="avatar-sm flex-shrink-0"
                                aria-hidden="true">

                                <span
                                    class="avatar-title rounded-circle
                                        bg-danger-subtle text-danger">

                                    <i
                                        class="ri-image-add-line fs-20">
                                    </i>
                                </span>
                            </div>

                            <div>
                                <h1 class="fs-18 fw-semibold mb-1">
                                    Manage Photos
                                </h1>

                                <p class="text-muted fs-13 mb-0">
                                    Upload up to five photos and choose
                                    your main profile photo.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        class="profile-photos-page__count
                            text-md-end">

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
                        <div
                            class="card border border-danger
                                border-opacity-25 shadow-none
                                profile-photo-upload-card">

                            <div class="card-body p-3 p-md-4">

                                <h2 class="fs-16 fw-semibold mb-1">
                                    Add a photo
                                </h2>

                                <p class="text-muted fs-13 mb-3">
                                    JPEG, PNG or WEBP. Maximum 10 MB.
                                    Minimum 400 × 400 pixels.
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

                                        <div
                                            class="profile-photo-dropzone
                                                mb-3"
                                            id="profile-photo-dropzone">

                                            <input
                                                type="file"
                                                name="photo"
                                                id="profile-photo-input"
                                                class="profile-photo-dropzone__input"
                                                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                                required>

                                            <label
                                                for="profile-photo-input"
                                                class="profile-photo-dropzone__label">

                                                <span
                                                    class="profile-photo-dropzone__icon">

                                                    <i
                                                        class="ri-upload-cloud-2-line"
                                                        aria-hidden="true">
                                                    </i>
                                                </span>

                                                <span
                                                    class="fw-semibold
                                                        d-block mb-1">

                                                    Choose a photo
                                                </span>

                                                <span
                                                    class="text-muted
                                                        fs-12 d-block">

                                                    Tap or click to browse
                                                </span>
                                            </label>

                                            <img
                                                src=""
                                                alt="Selected photo preview"
                                                id="profile-photo-preview"
                                                class="profile-photo-dropzone__preview d-none">
                                        </div>

                                        <?php if (
                                            isset(
                                                $validationErrors['photo']
                                            )
                                        ): ?>

                                            <div
                                                class="invalid-feedback
                                                    d-block mb-3">

                                                <?= esc(
                                                    $validationErrors['photo']
                                                ) ?>
                                            </div>

                                        <?php endif; ?>

                                        <p
                                            class="profile-photo-file-name
                                                text-muted fs-12 mb-3"
                                            id="profile-photo-file-name">
                                            No photo selected
                                        </p>

                                        <fieldset class="mb-3">
                                            <legend
                                                class="form-label fs-13
                                                    fw-semibold mb-2">

                                                Who can view this photo?
                                            </legend>

                                            <div
                                                class="profile-photo-visibility-options">

                                                <label
                                                    class="profile-photo-visibility-option">

                                                    <input
                                                        type="radio"
                                                        name="visibility"
                                                        value="PUBLIC"
                                                        <?= old(
                                                            'visibility',
                                                            'PUBLIC'
                                                        ) === 'PUBLIC'
                                                            ? 'checked'
                                                            : '' ?>>

                                                    <span>
                                                        <strong>
                                                            Public
                                                        </strong>

                                                        <small>
                                                            Visible to
                                                            eligible members.
                                                        </small>
                                                    </span>
                                                </label>

                                                <label
                                                    class="profile-photo-visibility-option">

                                                    <input
                                                        type="radio"
                                                        name="visibility"
                                                        value="INTERESTED_MEMBERS"
                                                        <?= old(
                                                            'visibility'
                                                        ) ===
                                                            'INTERESTED_MEMBERS'
                                                            ? 'checked'
                                                            : '' ?>>

                                                    <span>
                                                        <strong>
                                                            Interested
                                                            members only
                                                        </strong>

                                                        <small>
                                                            Restrict viewing
                                                            to members with
                                                            approved interest.
                                                        </small>
                                                    </span>
                                                </label>
                                            </div>
                                        </fieldset>

                                        <?php if (
                                            isset(
                                                $validationErrors['visibility']
                                            )
                                        ): ?>

                                            <div
                                                class="invalid-feedback
                                                    d-block mb-3">

                                                <?= esc(
                                                    $validationErrors['visibility']
                                                ) ?>
                                            </div>

                                        <?php endif; ?>

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
                                                class="form-check-label
                                                    fs-13"
                                                for="profile-photo-primary">

                                                Make this my main photo
                                            </label>
                                        </div>

                                        <div
                                            class="alert alert-warning
                                                py-2 px-3 fs-12 mb-3">

                                            <i
                                                class="ri-time-line me-1"
                                                aria-hidden="true">
                                            </i>

                                            New photos remain pending until
                                            approved by the administrator.
                                        </div>

                                        <button
                                            type="submit"
                                            class="btn btn-primary w-100
                                                d-inline-flex
                                                align-items-center
                                                justify-content-center
                                                gap-2"
                                            id="profile-photo-submit">

                                            <span>
                                                Upload photo
                                            </span>

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
                                        five photos. Delete a photo before
                                        uploading another.
                                    </div>

                                <?php endif; ?>

                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-8">
                        <div
                            class="card border border-danger
                                border-opacity-25 shadow-none">

                            <div class="card-body p-3 p-md-4">

                                <div
                                    class="d-flex align-items-center
                                        justify-content-between
                                        gap-2 mb-3">

                                    <div>
                                        <h2
                                            class="fs-16 fw-semibold mb-1">
                                            Your photos
                                        </h2>

                                        <p class="text-muted fs-13 mb-0">
                                            Select a main photo, update
                                            visibility or delete a photo.
                                        </p>
                                    </div>
                                </div>

                                <?php if ($photos === []): ?>

                                    <div
                                        class="profile-photos-empty
                                            text-center">

                                        <div
                                            class="profile-photos-empty__icon"
                                            aria-hidden="true">

                                            <i
                                                class="ri-image-line">
                                            </i>
                                        </div>

                                        <h3
                                            class="fs-15 fw-semibold mb-1">
                                            No photos uploaded
                                        </h3>

                                        <p
                                            class="text-muted fs-13 mb-0">
                                            Upload your first photo to
                                            improve your profile.
                                        </p>
                                    </div>

                                <?php else: ?>

                                    <div
                                        class="row g-3"
                                        id="member-photo-grid">

                                        <?php foreach (
                                            $photos as $photo
                                        ): ?>

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

                                            $thumbnailUrl = (string) (
                                                $photo['signedUrls']['thumbnailUrl']
                                                ?? ''
                                            );

                                            $statusClass = match ($status) {
                                                'APPROVED' =>
                                                'success',
                                                'REJECTED' =>
                                                'danger',
                                                default =>
                                                'warning',
                                            };

                                            $statusLabel = match ($status) {
                                                'APPROVED' =>
                                                'Approved',
                                                'REJECTED' =>
                                                'Rejected',
                                                default =>
                                                'Pending approval',
                                            };
                                            ?>

                                            <div
                                                class="col-12 col-sm-6">

                                                <article
                                                    class="profile-photo-card">

                                                    <div
                                                        class="profile-photo-card__media">

                                                        <?php if (
                                                            $thumbnailUrl
                                                            !== ''
                                                        ): ?>

                                                            <img
                                                                src="<?= esc(
                                                                            $thumbnailUrl
                                                                        ) ?>"
                                                                alt="Member photo"
                                                                loading="lazy">

                                                        <?php else: ?>

                                                            <div
                                                                class="profile-photo-card__placeholder">

                                                                <i
                                                                    class="ri-image-line"
                                                                    aria-hidden="true">
                                                                </i>
                                                            </div>

                                                        <?php endif; ?>

                                                        <div
                                                            class="profile-photo-card__badges">

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
                                                                        class="ri-star-fill me-1"
                                                                        aria-hidden="true">
                                                                    </i>

                                                                    Main
                                                                </span>

                                                            <?php endif; ?>
                                                        </div>
                                                    </div>

                                                    <div
                                                        class="profile-photo-card__body">

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
                                                                        class="ri-star-line me-1"
                                                                        aria-hidden="true">
                                                                    </i>

                                                                    Make main photo
                                                                </button>
                                                            </form>

                                                        <?php endif; ?>

                                                        <form
                                                            method="post"
                                                            action="<?= url_to(
                                                                        'web.profile.photos.visibility',
                                                                        $photoId
                                                                    ) ?>"
                                                            class="d-flex
                                                                gap-2 mb-2">

                                                            <?= csrf_field() ?>

                                                            <select
                                                                name="visibility"
                                                                class="form-select
                                                                    form-select-sm"
                                                                aria-label="Photo visibility">

                                                                <option
                                                                    value="PUBLIC"
                                                                    <?= $visibility
                                                                        === 'PUBLIC'
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
                                                                    Interested only
                                                                </option>
                                                            </select>

                                                            <button
                                                                type="submit"
                                                                class="btn
                                                                    btn-outline-secondary
                                                                    btn-sm">
                                                                Save
                                                            </button>
                                                        </form>

                                                        <form
                                                            method="post"
                                                            action="<?= url_to(
                                                                        'web.profile.photos.delete',
                                                                        $photoId
                                                                    ) ?>"
                                                            class="profile-photo-delete-form">

                                                            <?= csrf_field() ?>

                                                            <button
                                                                type="submit"
                                                                class="btn
                                                                    btn-outline-danger
                                                                    btn-sm w-100">

                                                                <i
                                                                    class="ri-delete-bin-line me-1"
                                                                    aria-hidden="true">
                                                                </i>

                                                                Delete photo
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

                <div
                    class="alert alert-light border mt-3 mb-0
                        profile-photo-guidelines">

                    <div class="d-flex gap-2">
                        <i
                            class="ri-shield-check-line
                                text-success fs-18"
                            aria-hidden="true">
                        </i>

                        <div>
                            <h2 class="fs-14 fw-semibold mb-1">
                                Photo guidelines
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