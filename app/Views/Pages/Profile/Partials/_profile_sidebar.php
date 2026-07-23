<?php

declare(strict_types=1);

/**
 * Profile sidebar cards.
 *
 * @var array<string, mixed> $overallProfileSummary
 */

$summary = is_array($overallProfileSummary ?? null)
    ? $overallProfileSummary
    : [];

$hasProfilePhoto = (bool) (
    $summary['hasProfilePhoto'] ?? false
);

$profilePhotoUrl = trim(
    (string) ($summary['profilePhotoUrl'] ?? '')
);

$kundaliCompleted = (bool) (
    $summary['kundaliCompleted'] ?? false
);

$isMobileVerified = (bool) (
    $summary['isMobileVerified'] ?? false
);

$isEmailVerified = (bool) (
    $summary['isEmailVerified'] ?? false
);

$isIdentityVerified = (bool) (
    $summary['isIdentityVerified'] ?? false
);
?>

<div class="d-flex flex-column gap-3">

    <!-- Profile photo -->
    <section
        class="card border border-danger border-opacity-25 shadow-none mb-0"
        aria-labelledby="profilePhotoTitle">

        <div class="card-body p-3">
            <div class="d-flex align-items-start gap-3">

                <div class="avatar-lg flex-shrink-0">
                    <?php if (
                        $hasProfilePhoto
                        && $profilePhotoUrl !== ''
                    ): ?>
                        <img
                            src="<?= esc(
                                        $profilePhotoUrl,
                                        'attr'
                                    ) ?>"
                            alt="Profile photo"
                            class="rounded-circle img-thumbnail
                                object-fit-cover w-100 h-100">
                    <?php else: ?>
                        <span
                            class="avatar-title rounded-circle
                                bg-light text-primary fs-28"
                            aria-hidden="true">
                            <i class="ri-user-3-line"></i>
                        </span>
                    <?php endif; ?>
                </div>

                <div class="flex-grow-1">
                    <div
                        class="d-flex align-items-start
                            justify-content-between gap-2">

                        <div>
                            <h2
                                class="fs-15 fw-semibold mb-1"
                                id="profilePhotoTitle">
                                Profile photo
                            </h2>

                            <p class="text-muted fs-13 mb-0">
                                Add a clear and recent photo to
                                improve profile visibility.
                            </p>
                        </div>

                        <?php if ($hasProfilePhoto): ?>
                            <span
                                class="badge bg-success-subtle
                                    text-success">
                                Added
                            </span>
                        <?php endif; ?>
                    </div>

                    <button
                        type="button"
                        class="btn btn-outline-primary
                            btn-sm d-inline-flex
                            align-items-center gap-1 mt-3"
                        data-bs-toggle="offcanvas"
                        data-bs-target="#profilePhotoOffcanvas"
                        aria-controls="profilePhotoOffcanvas">

                        <i
                            class="<?= $hasProfilePhoto
                                        ? 'ri-image-edit-line'
                                        : 'ri-image-add-line' ?>"
                            aria-hidden="true"></i>

                        <?= $hasProfilePhoto
                            ? 'Change photo'
                            : 'Upload photo' ?>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Kundali details -->
    <section
        class="card border border-danger border-opacity-25 shadow-none mb-0"
        aria-labelledby="kundaliDetailsTitle">

        <div class="card-body p-3">
            <div
                class="d-flex align-items-start
                    justify-content-between gap-3">

                <div class="d-flex align-items-start gap-3">
                    <div class="avatar-sm flex-shrink-0">
                        <span
                            class="avatar-title rounded-circle
                                bg-light text-primary fs-20"
                            aria-hidden="true">
                            <i class="ri-sun-line"></i>
                        </span>
                    </div>

                    <div>
                        <h2
                            class="fs-15 fw-semibold mb-1"
                            id="kundaliDetailsTitle">
                            Kundali details
                        </h2>

                        <p class="text-muted fs-13 mb-0">
                            Add birth time, birth place and
                            manglik information.
                        </p>
                    </div>
                </div>

                <?php if ($kundaliCompleted): ?>
                    <span
                        class="badge bg-success-subtle
                            text-success">
                        Complete
                    </span>
                <?php else: ?>
                    <span
                        class="badge bg-warning-subtle
                            text-warning p-2">
                        Pending
                    </span>
                <?php endif; ?>
            </div>

            <button
                type="button"
                class="btn btn-outline-primary
                    btn-sm w-100 d-inline-flex
                    align-items-center
                    justify-content-center gap-1 mt-3"
                data-bs-toggle="offcanvas"
                data-bs-target="#kundaliDetailsOffcanvas"
                aria-controls="kundaliDetailsOffcanvas">

                <i
                    class="<?= $kundaliCompleted
                                ? 'ri-edit-line'
                                : 'ri-add-line' ?>"
                    aria-hidden="true"></i>

                <?= $kundaliCompleted
                    ? 'Edit kundali details'
                    : 'Add kundali details' ?>
            </button>
        </div>
    </section>

    <!-- Trust and verification -->
    <section
        class="card border border-danger border-opacity-25 shadow-none mb-0"
        aria-labelledby="trustVerificationTitle">

        <div class="card-body p-3">
            <div class="d-flex align-items-start gap-3 mb-3">

                <div class="avatar-sm flex-shrink-0">
                    <span
                        class="avatar-title rounded-circle
                            bg-light text-primary fs-20"
                        aria-hidden="true">
                        <i class="ri-shield-check-line"></i>
                    </span>
                </div>

                <div>
                    <h2
                        class="fs-15 fw-semibold mb-1"
                        id="trustVerificationTitle">
                        Trust and verification
                    </h2>

                    <p class="text-muted fs-13 mb-0">
                        Verified details help other members
                        trust your profile.
                    </p>
                </div>
            </div>

            <div
                class="d-flex align-items-center
                    justify-content-between gap-3
                    py-2 border-top">

                <div class="d-flex align-items-center gap-2">
                    <i
                        class="ri-smartphone-line
                            text-muted fs-18"
                        aria-hidden="true"></i>

                    <span class="fs-13 fw-medium">
                        Mobile number
                    </span>
                </div>

                <?php if ($isMobileVerified): ?>
                    <span
                        class="badge bg-success-subtle
                            text-success">
                        <i
                            class="ri-checkbox-circle-line me-1"
                            aria-hidden="true"></i>
                        Verified
                    </span>
                <?php else: ?>
                    <a
                        href="#"
                        class="fs-12 fw-semibold text-primary">
                        Verify
                    </a>
                <?php endif; ?>
            </div>

            <div
                class="d-flex align-items-center
                    justify-content-between gap-3
                    py-2 border-top">

                <div class="d-flex align-items-center gap-2">
                    <i
                        class="ri-mail-line
                            text-muted fs-18"
                        aria-hidden="true"></i>

                    <span class="fs-13 fw-medium">
                        Email address
                    </span>
                </div>

                <?php if ($isEmailVerified): ?>
                    <span
                        class="badge bg-success-subtle
                            text-success">
                        <i
                            class="ri-checkbox-circle-line me-1"
                            aria-hidden="true"></i>
                        Verified
                    </span>
                <?php else: ?>
                    <a
                        href="#"
                        class="fs-12 fw-semibold text-primary">
                        Verify
                    </a>
                <?php endif; ?>
            </div>

            <div
                class="d-flex align-items-center
                    justify-content-between gap-3
                    pt-2 border-top">

                <div class="d-flex align-items-center gap-2">
                    <i
                        class="ri-id-card-line
                            text-muted fs-18"
                        aria-hidden="true"></i>

                    <span class="fs-13 fw-medium">
                        Identity proof
                    </span>
                </div>

                <?php if ($isIdentityVerified): ?>
                    <span
                        class="badge bg-success-subtle
                            text-success">
                        <i
                            class="ri-checkbox-circle-line me-1"
                            aria-hidden="true"></i>
                        Verified
                    </span>
                <?php else: ?>
                    <button
                        type="button"
                        class="btn btn-link
                            text-primary fs-12 fw-semibold
                            p-0 text-decoration-none">
                        Add
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </section>

</div>