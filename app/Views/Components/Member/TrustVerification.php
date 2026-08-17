<?php

declare(strict_types=1);

/**
 * Shared Trust and Verification presentation.
 *
 * Used by:
 *
 * - Member Dashboard
 * - Profile Edit
 *
 * @var array<string, mixed> $trustVerification
 * @var bool                 $showCard
 */

$trustVerification =
    isset($trustVerification)
    && is_array($trustVerification)
    ? $trustVerification
    : [];

$showCard = isset($showCard)
    ? $showCard === true
    : true;

$mobile = isset($trustVerification['mobile'])
    && is_array($trustVerification['mobile'])
    ? $trustVerification['mobile']
    : [];

$email = isset($trustVerification['email'])
    && is_array($trustVerification['email'])
    ? $trustVerification['email']
    : [];

$aadhaar = isset($trustVerification['aadhaar'])
    && is_array($trustVerification['aadhaar'])
    ? $trustVerification['aadhaar']
    : [];

$selfie = isset($trustVerification['selfie'])
    && is_array($trustVerification['selfie'])
    ? $trustVerification['selfie']
    : [];

$mobileValue = trim(
    (string) (
        $mobile['value']
        ?? ''
    )
);

$emailValue = trim(
    (string) (
        $email['value']
        ?? ''
    )
);

$isMobileVerified =
    ($mobile['isVerified'] ?? false) === true;

$isEmailVerified =
    ($email['isVerified'] ?? false) === true;

$aadhaarStatus = mb_strtoupper(
    trim(
        (string) (
            $aadhaar['status']
            ?? 'NOT_ADDED'
        )
    )
);

$aadhaarRejectionReason = trim(
    (string) (
        $aadhaar['rejectionReason']
        ?? ''
    )
);

$isSelfieVerified =
    ($selfie['isVerified'] ?? false) === true;
?>

<?php if ($showCard): ?>
    <section
        class="card border border-danger
        border-opacity-25 shadow-none mb-0"
        id="trust-verification"
        aria-labelledby="trustVerificationTitle">

        <div class="card-body p-3">
            <div class="d-flex align-items-start gap-3 mb-3">
                <div class="avatar-sm flex-shrink-0">
                    <span
                        class="avatar-title rounded-circle
                        bg-primary-subtle text-primary fs-20"
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
        <?php endif; ?>

        <!-- Mobile verification -->
        <div
            class="d-flex align-items-center
                justify-content-between gap-3
                py-2 border-top">

            <span
                class="d-flex align-items-center
                    gap-2 min-w-0">

                <i
                    class="ri-smartphone-line
                        text-primary fs-18 flex-shrink-0"
                    aria-hidden="true">
                </i>

                <span class="min-w-0">
                    <span class="d-block text-muted fs-12">
                        Mobile
                    </span>

                    <span
                        class="d-block fw-medium
                            text-truncate fs-13">

                        <?= esc(
                            $mobileValue !== ''
                                ? $mobileValue
                                : 'Not added'
                        ) ?>
                    </span>
                </span>
            </span>

            <?php if ($isMobileVerified): ?>
                <span
                    class="badge bg-success-subtle
                        text-success fs-11 p-2
                        flex-shrink-0">

                    Verified
                </span>
            <?php else: ?>
                <span
                    class="badge bg-warning-subtle
                        text-warning fs-11 p-2
                        flex-shrink-0">

                    Pending
                </span>
            <?php endif; ?>
        </div>

        <!-- Email verification -->
        <div
            class="d-flex align-items-center
                justify-content-between gap-3
                py-2 border-top">

            <span
                class="d-flex align-items-center
                    gap-2 min-w-0">

                <i
                    class="ri-mail-line
                        text-info fs-18 flex-shrink-0"
                    aria-hidden="true">
                </i>

                <span class="min-w-0">
                    <span class="d-block text-muted fs-12">
                        Email
                    </span>

                    <span
                        class="d-block fw-medium
                            text-truncate fs-13"
                        <?php if ($emailValue !== ''): ?>
                        title="<?= esc(
                                    $emailValue,
                                    'attr'
                                ) ?>"
                        <?php endif; ?>>

                        <?= esc(
                            $emailValue !== ''
                                ? $emailValue
                                : 'Not added'
                        ) ?>
                    </span>
                </span>
            </span>

            <?php if ($isEmailVerified): ?>
                <span
                    class="badge bg-success-subtle
                        text-success fs-11 p-2
                        flex-shrink-0">

                    Verified
                </span>
            <?php elseif ($emailValue !== ''): ?>
                <form
                    method="post"
                    action="<?= url_to(
                                'web.email.verification.send'
                            ) ?>"
                    id="emailVerificationForm">

                    <?= csrf_field() ?>

                    <button
                        type="submit"
                        class="btn btn-sm btn-outline-primary"
                        id="emailVerificationSubmit">

                        <span
                            class="email-verification-submit__label">

                            Verify
                        </span>

                        <span
                            class="registration-submit__loading
                                d-none"
                            aria-hidden="true">

                            <span
                                class="spinner-border
                                    spinner-border-sm">
                            </span>
                        </span>
                    </button>
                </form>
            <?php else: ?>
                <span
                    class="badge bg-secondary-subtle
                        text-body-secondary fs-11 p-2
                        flex-shrink-0">

                    Not added
                </span>
            <?php endif; ?>
        </div>

        <!-- Aadhaar verification -->
        <div
            class="d-flex align-items-center
                justify-content-between gap-3
                py-2 border-top">

            <span
                class="d-flex align-items-center gap-2">

                <i
                    class="ri-fingerprint-line
                        text-warning fs-18"
                    aria-hidden="true">
                </i>

                <span class="fw-medium fs-13">
                    Aadhaar
                </span>
            </span>

            <?php if ($aadhaarStatus === 'APPROVED'): ?>
                <span
                    class="badge bg-success-subtle
                        text-success fs-11 p-2">

                    Verified
                </span>
            <?php elseif ($aadhaarStatus === 'UNDER_REVIEW'): ?>
                <span
                    class="badge bg-warning-subtle
                        text-warning fs-11 p-2">

                    Under Review
                </span>
            <?php elseif ($aadhaarStatus === 'REJECTED'): ?>
                <span class="d-inline-flex align-items-center gap-1">
                    <span
                        <?php if ($aadhaarRejectionReason !== ''): ?>
                        data-bs-toggle="tooltip"
                        data-bs-placement="top"
                        title="Reupload Aadhaar"
                        <?php endif; ?>>

                        <span
                            class="badge bg-danger-subtle
                                text-danger fs-11 p-2">

                            Rejected
                        </span>
                    </span>

                    <button
                        type="button"
                        class="btn btn-sm btn-outline-danger"
                        data-bs-toggle="modal"
                        data-bs-target="#aadhaarUploadModal"
                        aria-label="Reupload Aadhaar"
                        title="Reupload Aadhaar">

                        <i
                            class="ri-upload-2-fill"
                            aria-hidden="true">
                        </i>
                    </button>
                </span>
            <?php else: ?>
                <button
                    type="button"
                    class="btn btn-sm btn-outline-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#aadhaarUploadModal">

                    Add
                </button>
            <?php endif; ?>
        </div>

        <!-- Selfie verification -->
        <div
            class="d-flex align-items-center
                justify-content-between gap-3
                pt-2 border-top">

            <span
                class="d-flex align-items-center gap-2">

                <i
                    class="ri-camera-lens-line
                        text-danger fs-18"
                    aria-hidden="true">
                </i>

                <span class="fw-medium fs-13">
                    Selfie
                </span>
            </span>

            <?php if ($isSelfieVerified): ?>
                <span
                    class="badge bg-success-subtle
                        text-success fs-11 p-2">

                    Verified
                </span>
            <?php else: ?>
                <span
                    class="badge bg-warning-subtle
                        text-warning fs-11 p-2">

                    Pending
                </span>
            <?php endif; ?>
        </div>

        <?php if ($showCard): ?>
        </div>
    </section>
<?php endif; ?>