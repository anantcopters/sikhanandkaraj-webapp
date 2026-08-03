<?php

declare(strict_types=1);

use App\Models\Prelaunch\PrelaunchPhotoModel;
use App\Models\Prelaunch\PrelaunchProfileModel;

/**
 * @var array<string, mixed>       $profile
 * @var list<array<string, mixed>> $photos
 * @var array<string, mixed>       $photoSummary
 * @var array<string, string>      $validationErrors
 * @var array<string, string>|null $formAlert
 */

$profile = is_array($profile ?? null)
    ? $profile
    : [];

$photos = is_array($photos ?? null)
    ? $photos
    : [];

$errors = is_array(
    $validationErrors ?? null
)
    ? $validationErrors
    : [];

$status = mb_strtoupper(
    trim(
        (string) (
            $profile['status']
            ?? ''
        )
    )
);

$isDraft =
    $status
    === PrelaunchProfileModel::STATUS_DRAFT;

$isRejected =
    $status
    === PrelaunchProfileModel::STATUS_REJECTED;

$isApproved =
    $status
    === PrelaunchProfileModel::STATUS_APPROVED;

$approvedPhotoCount = (int) (
    $photoSummary['approved']
    ?? 0
);

$canApprove =
    $isDraft
    && $approvedPhotoCount >= 1;

$this->extend('Admin/Layouts/Main');
$this->section('content');
?>

<div class="container-fluid py-3 pt-0">
    <section class="py-3">
        <div class="container-fluid">

            <?php if (is_array($formAlert ?? null)): ?>
                <div
                    class="alert alert-<?= esc(
                                            $formAlert['type']
                                                ?? 'danger',
                                            'attr'
                                        ) ?>"
                    role="alert">
                    <strong>
                        <?= esc(
                            $formAlert['title']
                                ?? ''
                        ) ?>
                    </strong>

                    <div>
                        <?= esc(
                            $formAlert['message']
                                ?? ''
                        ) ?>
                    </div>
                </div>
            <?php endif ?>

            <div
                class="d-flex flex-column flex-md-row
                justify-content-between align-items-md-center
                gap-3 mb-3">

                <div>
                    <a
                        href="<?= route_to(
                                    'admin.prelaunch.profiles.index'
                                ) ?>"
                        class="d-inline-flex align-items-center
                        gap-1 text-primary fw-medium mb-2">

                        <i
                            class="ri-arrow-left-line"
                            aria-hidden="true"></i>

                        Back to prelaunch profiles
                    </a>

                    <h1 class="h3 mb-1">
                        <?= esc(
                            $profile['full_name']
                                ?? 'Prelaunch Profile'
                        ) ?>
                    </h1>

                    <div
                        class="d-flex align-items-center
                        flex-wrap gap-2">

                        <span class="text-muted">
                            <?= esc(
                                $profile['profile_reference']
                                    ?? ''
                            ) ?>
                        </span>

                        <span
                            class="badge <?= match ($status) {
                                                'APPROVED' =>
                                                'text-bg-success',
                                                'REJECTED' =>
                                                'text-bg-danger',
                                                default =>
                                                'text-bg-warning',
                                            } ?>">
                            <?= esc($status) ?>
                        </span>
                    </div>
                </div>
            </div>

            <?php if ($isRejected): ?>
                <div
                    class="alert alert-danger"
                    role="alert">
                    <strong>Profile rejected.</strong>

                    This profile is locked. Contact details and
                    photograph decisions can no longer be changed.

                    <?php if (
                        trim(
                            (string) (
                                $profile['rejection_reason']
                                ?? ''
                            )
                        ) !== ''
                    ): ?>
                        <div class="mt-2">
                            Reason:
                            <?= esc(
                                $profile['rejection_reason']
                            ) ?>
                        </div>
                    <?php endif ?>
                </div>
            <?php endif ?>

            <?php if ($isApproved): ?>
                <div
                    class="alert alert-success"
                    role="alert">
                    <strong>
                        Profile migrated successfully.
                    </strong>

                    <?php if (
                        (int) (
                            $profile['migrated_user_id']
                            ?? 0
                        ) > 0
                    ): ?>
                        Member ID:
                        <?= esc(
                            $profile['migrated_user_id']
                        ) ?>.
                    <?php endif ?>
                </div>
            <?php endif ?>

            <div class="row g-4 mb-4">
                <div class="col-12 col-xl-8">
                    <article
                        class="card border shadow-sm
                        rounded-3 h-100">

                        <div
                            class="card-header d-flex
                            justify-content-between
                            align-items-center gap-2">

                            <h2 class="h5 mb-0">
                                Photo Gallery
                            </h2>

                            <span
                                class="badge text-bg-secondary">
                                <?= esc(
                                    $approvedPhotoCount
                                ) ?>
                                approved
                            </span>
                        </div>

                        <div class="card-body">
                            <div
                                class="row g-3"
                                data-prelaunch-gallery>

                                <?php foreach (
                                    $photos as $photo
                                ): ?>
                                    <?php
                                    $photoId = (int) (
                                        $photo['id']
                                        ?? 0
                                    );

                                    if ($photoId <= 0) {
                                        continue;
                                    }

                                    $photoStatus =
                                        mb_strtoupper(
                                            trim(
                                                (string) (
                                                    $photo['approval_status']
                                                    ?? 'PENDING'
                                                )
                                            )
                                        );

                                    $ribbonClass =
                                        match ($photoStatus) {
                                            'APPROVED' =>
                                            'is-approved',
                                            'REJECTED' =>
                                            'is-rejected',
                                            default =>
                                            'is-pending',
                                        };

                                    $modalId =
                                        'prelaunch-photo-modal-'
                                        . $photoId;
                                    ?>

                                    <div
                                        class="col-6 col-md-4">

                                        <button
                                            type="button"
                                            class="prelaunch-review-photo
                                            border-0 bg-transparent
                                            p-0 w-100 text-start"
                                            data-bs-toggle="modal"
                                            data-bs-target="#<?= esc(
                                                                    $modalId,
                                                                    'attr'
                                                                ) ?>">

                                            <span
                                                class="prelaunch-review-photo__frame">

                                                <img
                                                    src="<?= esc(
                                                                route_to(
                                                                    'admin.prelaunch.photos.view',
                                                                    $photoId,
                                                                    'original'
                                                                ),
                                                                'attr'
                                                            ) ?>"
                                                    class="prelaunch-review-photo__image"
                                                    alt="Prelaunch profile photograph"
                                                    loading="lazy">

                                                <span
                                                    class="prelaunch-photo-ribbon
                                                    <?= esc(
                                                        $ribbonClass,
                                                        'attr'
                                                    ) ?>">
                                                    <?= esc(
                                                        $photoStatus
                                                    ) ?>
                                                </span>
                                            </span>
                                        </button>
                                    </div>

                                    <div
                                        class="modal fade"
                                        id="<?= esc(
                                                $modalId,
                                                'attr'
                                            ) ?>"
                                        tabindex="-1"
                                        aria-hidden="true">

                                        <div
                                            class="modal-dialog
                                            modal-dialog-centered
                                            modal-lg">

                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h3
                                                        class="modal-title h5">
                                                        Review Photo
                                                    </h3>

                                                    <button
                                                        type="button"
                                                        class="btn-close"
                                                        data-bs-dismiss="modal"
                                                        aria-label="Close">
                                                    </button>
                                                </div>

                                                <div class="modal-body">
                                                    <img
                                                        src="<?= esc(
                                                                    route_to(
                                                                        'admin.prelaunch.photos.view',
                                                                        $photoId,
                                                                        'original'
                                                                    ),
                                                                    'attr'
                                                                ) ?>"
                                                        class="img-fluid rounded
                                                        d-block mx-auto"
                                                        alt="Prelaunch profile photograph">
                                                </div>

                                                <?php if ($isDraft): ?>
                                                    <div class="modal-footer">
                                                        <form
                                                            action="<?= esc(
                                                                        route_to(
                                                                            'admin.prelaunch.photos.approve',
                                                                            $photoId
                                                                        ),
                                                                        'attr'
                                                                    ) ?>"
                                                            method="post"
                                                            data-submit-loader>

                                                            <?= csrf_field() ?>

                                                            <button
                                                                type="submit"
                                                                class="btn btn-success">
                                                                <span
                                                                    data-submit-loader-label>
                                                                    Approve
                                                                </span>

                                                                <span
                                                                    class="d-none"
                                                                    data-submit-loader-spinner>
                                                                    <span
                                                                        class="spinner-border
                                                                        spinner-border-sm"
                                                                        aria-hidden="true">
                                                                    </span>
                                                                </span>
                                                            </button>
                                                        </form>

                                                        <form
                                                            action="<?= esc(
                                                                        route_to(
                                                                            'admin.prelaunch.photos.reject',
                                                                            $photoId
                                                                        ),
                                                                        'attr'
                                                                    ) ?>"
                                                            method="post"
                                                            class="flex-grow-1"
                                                            data-submit-loader>

                                                            <?= csrf_field() ?>

                                                            <div class="input-group">
                                                                <input
                                                                    type="text"
                                                                    name="rejection_reason"
                                                                    class="form-control"
                                                                    minlength="5"
                                                                    maxlength="500"
                                                                    placeholder="Photo rejection reason"
                                                                    required>

                                                                <button
                                                                    type="submit"
                                                                    class="btn btn-outline-danger">
                                                                    Reject
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                <?php endif ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach ?>
                            </div>
                        </div>
                    </article>
                </div>

                <div class="col-12 col-xl-4">
                    <article
                        class="card border shadow-sm
                        rounded-3 h-100">

                        <div class="card-header">
                            <h2 class="h5 mb-0">
                                Contact and Decision
                            </h2>
                        </div>

                        <div class="card-body">
                            <form
                                action="<?= esc(
                                            route_to(
                                                'admin.prelaunch.profiles.approve',
                                                (int) $profile['id']
                                            ),
                                            'attr'
                                        ) ?>"
                                method="post"
                                data-submit-loader>

                                <?= csrf_field() ?>

                                <div class="mb-3">
                                    <label
                                        for="country_code"
                                        class="form-label">
                                        Country Code
                                    </label>

                                    <input
                                        id="country_code"
                                        name="country_code"
                                        type="text"
                                        class="form-control
                                        <?= isset(
                                            $errors['country_code']
                                        )
                                            ? 'is-invalid'
                                            : '' ?>"
                                        value="<?= esc(
                                                    old(
                                                        'country_code',
                                                        $profile['country_code']
                                                            ?? ''
                                                    ),
                                                    'attr'
                                                ) ?>"
                                        maxlength="8"
                                        <?= !$isDraft
                                            ? 'disabled'
                                            : '' ?>
                                        required>

                                    <?php if (isset(
                                        $errors['country_code']
                                    )): ?>
                                        <div
                                            class="invalid-feedback">
                                            <?= esc(
                                                $errors['country_code']
                                            ) ?>
                                        </div>
                                    <?php endif ?>
                                </div>

                                <div class="mb-3">
                                    <label
                                        for="mobile_number"
                                        class="form-label">
                                        Mobile Number
                                    </label>

                                    <input
                                        id="mobile_number"
                                        name="mobile_number"
                                        type="tel"
                                        class="form-control
                                        <?= isset(
                                            $errors['mobile_number']
                                        )
                                            ? 'is-invalid'
                                            : '' ?>"
                                        value="<?= esc(
                                                    old(
                                                        'mobile_number',
                                                        $profile['mobile_number']
                                                            ?? ''
                                                    ),
                                                    'attr'
                                                ) ?>"
                                        maxlength="15"
                                        <?= !$isDraft
                                            ? 'disabled'
                                            : '' ?>
                                        required>

                                    <?php if (isset(
                                        $errors['mobile_number']
                                    )): ?>
                                        <div
                                            class="invalid-feedback">
                                            <?= esc(
                                                $errors['mobile_number']
                                            ) ?>
                                        </div>
                                    <?php endif ?>
                                </div>

                                <div class="mb-4">
                                    <label
                                        for="email"
                                        class="form-label">
                                        Email
                                        <span class="text-muted">
                                            (optional)
                                        </span>
                                    </label>

                                    <input
                                        id="email"
                                        name="email"
                                        type="email"
                                        class="form-control
                                        <?= isset(
                                            $errors['email']
                                        )
                                            ? 'is-invalid'
                                            : '' ?>"
                                        value="<?= esc(
                                                    old(
                                                        'email',
                                                        $profile['email']
                                                            ?? ''
                                                    ),
                                                    'attr'
                                                ) ?>"
                                        maxlength="254"
                                        <?= !$isDraft
                                            ? 'disabled'
                                            : '' ?>>

                                    <?php if (isset(
                                        $errors['email']
                                    )): ?>
                                        <div
                                            class="invalid-feedback">
                                            <?= esc(
                                                $errors['email']
                                            ) ?>
                                        </div>
                                    <?php endif ?>
                                </div>

                                <?php if ($isDraft): ?>
                                    <button
                                        type="submit"
                                        class="btn btn-success
                                        w-100 mb-3"
                                        <?= !$canApprove
                                            ? 'disabled'
                                            : '' ?>>

                                        <span
                                            data-submit-loader-label>
                                            Save Contact and Approve
                                        </span>

                                        <span
                                            class="d-none"
                                            data-submit-loader-spinner>
                                            <span
                                                class="spinner-border
                                                spinner-border-sm"
                                                aria-hidden="true">
                                            </span>

                                            Migrating Profile...
                                        </span>
                                    </button>
                                <?php endif ?>
                            </form>

                            <?php if (
                                $isDraft
                                && !$canApprove
                            ): ?>
                                <div
                                    class="alert alert-warning
                                    py-2 fs-13">
                                    Approve at least one photograph
                                    before approving this profile.
                                </div>
                            <?php endif ?>

                            <?php if ($isDraft): ?>
                                <button
                                    type="button"
                                    class="btn btn-outline-danger
                                    w-100"
                                    data-bs-toggle="modal"
                                    data-bs-target="#reject-profile-modal">
                                    Reject Profile
                                </button>
                            <?php endif ?>
                        </div>
                    </article>
                </div>
            </div>

            <div class="row g-4 align-items-start">

                <!-- One single card for every section in the left column. -->
                <div class="col-12 col-lg-7">
                    <div
                        class="card border border-danger border-opacity-25 shadow-sm
                        rounded-3 overflow-hidden">

                        <section
                            class="card-body p-3 p-lg-4
                            border-bottom">



                            <div
                                class="d-flex
                                align-items-center gap-2 mb-3">

                                <span
                                    class="d-inline-flex
                                    align-items-center
                                    justify-content-center
                                    rounded-circle
                                    bg-primary-subtle
                                    text-primary"
                                    style="
                                    width: 34px;
                                    height: 34px;
                                ">

                                    <i
                                        class=" fs-18
                                        ri-user-smile-line"
                                        aria-hidden="true"></i>
                                </span>

                                <h2
                                    class="fs-16
                                    fw-semibold mb-0">
                                    About Me
                                </h2>
                            </div>

                            <?php if ($aboutMe !== ''): ?>
                                <p
                                    class="text-body-secondary
                                    lh-lg mb-0">
                                    <?= nl2br(
                                        esc($aboutMe)
                                    ) ?>
                                </p>
                            <?php else: ?>
                                <p class="text-muted mb-0">
                                    About Me has not been added yet.
                                </p>
                            <?php endif; ?>
                        </section>

                        <section
                            class="card-body p-3 p-lg-4
                            border-bottom">

                            <div
                                class="d-flex
                                align-items-center gap-2 mb-3">

                                <span
                                    class="d-inline-flex
                                    align-items-center
                                    justify-content-center
                                    rounded-circle
                                    bg-primary-subtle
                                    text-primary"
                                    style="
                                    width: 34px;
                                    height: 34px;
                                ">

                                    <i
                                        class="fs-18 ri-id-card-line"
                                        aria-hidden="true"></i>
                                </span>

                                <h2
                                    class="fs-16
                                    fw-semibold mb-0">
                                    Basic Details
                                </h2>
                            </div>

                            <div class="row g-3">
                                <?php foreach (
                                    $personalDetails
                                    as $label => $value
                                ): ?>
                                    <div class="col-12 col-sm-6">
                                        <div
                                            class="border-bottom
                                            pb-2 h-100">

                                            <div
                                                class="text-muted
                                                fs-12 mb-1">
                                                <?= esc($label) ?>
                                            </div>

                                            <div
                                                class="fw-medium fs-14">
                                                <?= esc(
                                                    $displayValue(
                                                        $value
                                                    )
                                                ) ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <section
                            class="card-body p-3 p-lg-4
                            border-bottom">

                            <div
                                class="d-flex
                                align-items-center gap-2 mb-3">

                                <span
                                    class="d-inline-flex
                                    align-items-center
                                    justify-content-center
                                    rounded-circle
                                    bg-primary-subtle
                                    text-primary"
                                    style="
                                    width: 34px;
                                    height: 34px;
                                ">

                                    <i
                                        class=" fs-18
                                        ri-briefcase-line"
                                        aria-hidden="true"></i>
                                </span>

                                <h2
                                    class="fs-16
                                    fw-semibold mb-0">
                                    Education & Profession
                                </h2>
                            </div>

                            <div class="row g-3">
                                <?php foreach (
                                    $professionDetails
                                    as $label => $value
                                ): ?>
                                    <div class="col-12 col-sm-6">
                                        <div
                                            class="border-bottom
                                            pb-2 h-100">

                                            <div
                                                class="text-muted
                                                fs-12 mb-1">
                                                <?= esc($label) ?>
                                            </div>

                                            <div
                                                class="fw-medium fs-14">
                                                <?= esc(
                                                    $displayValue(
                                                        $value
                                                    )
                                                ) ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>



                    </div>
                </div>

                <!-- One single card for every section in the right column. -->
                <div class="col-12 col-lg-5">
                    <div
                        class="card border border-danger border-opacity-25 shadow-sm
                        rounded-3 overflow-hidden">

                        <section
                            class="card-body p-3 p-lg-4
                            border-bottom">

                            <div
                                class="d-flex
                                align-items-center gap-2 mb-3">

                                <span
                                    class="d-inline-flex
                                    align-items-center
                                    justify-content-center
                                    rounded-circle
                                    bg-warning-subtle
                                    text-warning"
                                    style="
                                    width: 34px;
                                    height: 34px;
                                ">

                                    <i
                                        class="fs-18 ri-group-line"
                                        aria-hidden="true"></i>
                                </span>

                                <h2
                                    class="fs-16
                                    fw-semibold mb-0">
                                    Family Details
                                </h2>
                            </div>

                            <?php foreach (
                                $familyDetailList
                                as $label => $value
                            ): ?>
                                <div
                                    class="d-flex
                                    justify-content-between
                                    align-items-start
                                    gap-3 py-2
                                    border-bottom">

                                    <span
                                        class="text-muted
                                        fs-13">
                                        <?= esc($label) ?>
                                    </span>

                                    <span
                                        class="fw-medium fs-13
                                        text-end">
                                        <?= esc(
                                            $displayValue($value)
                                        ) ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </section>

                        <section
                            class="card-body p-3 p-lg-4
                            border-bottom">

                            <div
                                class="d-flex
                                align-items-center gap-2 mb-3">

                                <span
                                    class="d-inline-flex
                                    align-items-center
                                    justify-content-center
                                    rounded-circle
                                    bg-primary-subtle
                                    text-primary"
                                    style="
                                    width: 34px;
                                    height: 34px;
                                ">

                                    <i
                                        class="fs-18 
                                        ri-heart-pulse-line"
                                        aria-hidden="true"></i>
                                </span>

                                <h2
                                    class="fs-16
                                    fw-semibold mb-0">
                                    Lifestyle
                                </h2>
                            </div>

                            <?php if (
                                $lifestyleDetails !== []
                            ): ?>
                                <div
                                    class="d-flex
                                    flex-wrap gap-2">

                                    <?php foreach (
                                        $lifestyleDetails
                                        as $detail
                                    ): ?>
                                        <?php
                                        if (!is_array($detail)) {
                                            continue;
                                        }

                                        $label = trim(
                                            (string) (
                                                $detail['option_name']
                                                ?? $detail['name']
                                                ?? ''
                                            )
                                        );

                                        if ($label === '') {
                                            continue;
                                        }
                                        ?>

                                        <span
                                            class="
                                            badge rounded-pill
                                            bg-primary-subtle
                                            text-black
                                            fw-medium p-2">
                                            <?= esc($label) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted mb-0">
                                    Lifestyle preferences have not
                                    been added.
                                </p>
                            <?php endif; ?>
                        </section>

                        <section class="card-body p-3 p-lg-4">
                            <div
                                class="d-flex
                                align-items-center gap-2 mb-3">

                                <span
                                    class="d-inline-flex
                                    align-items-center
                                    justify-content-center
                                    rounded-circle
                                    bg-primary-subtle
                                    text-primary"
                                    style="
                                    width: 34px;
                                    height: 34px;
                                ">

                                    <i
                                        class="fs-18 ri-lock-2-line"
                                        aria-hidden="true"></i>
                                </span>

                                <h2
                                    class="fs-16
                                    fw-semibold mb-0">
                                    Privacy
                                </h2>
                            </div>

                            <p class="text-muted fs-13 mb-0">
                                This profile information is visible
                                only to authenticated members according
                                to the applicable privacy rules.
                            </p>
                        </section>

                    </div>
                </div>

            </div>
        </div>
    </section>

</div>
<?php if ($isDraft): ?>
    <div
        class="modal fade"
        id="reject-profile-modal"
        tabindex="-1"
        aria-hidden="true">

        <div
            class="modal-dialog
                modal-dialog-centered">

            <div class="modal-content">
                <form
                    action="<?= esc(
                                route_to(
                                    'admin.prelaunch.profiles.reject',
                                    (int) $profile['id']
                                ),
                                'attr'
                            ) ?>"
                    method="post"
                    data-submit-loader>

                    <?= csrf_field() ?>

                    <div class="modal-header">
                        <h2 class="modal-title h5">
                            Reject Profile
                        </h2>

                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close">
                        </button>
                    </div>

                    <div class="modal-body">
                        <label
                            for="profile_rejection_reason"
                            class="form-label">
                            Rejection reason
                        </label>

                        <textarea
                            id="profile_rejection_reason"
                            name="rejection_reason"
                            class="form-control"
                            rows="4"
                            minlength="5"
                            maxlength="1000"
                            required></textarea>

                        <p class="text-muted fs-13 mt-2 mb-0">
                            A rejected profile is permanently
                            locked from further review.
                        </p>
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
                            class="btn btn-danger">
                            <span
                                data-submit-loader-label>
                                Reject Profile
                            </span>

                            <span
                                class="d-none"
                                data-submit-loader-spinner>
                                <span
                                    class="spinner-border
                                        spinner-border-sm"
                                    aria-hidden="true">
                                </span>

                                Rejecting...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif ?>
<?= $this->endSection() ?>