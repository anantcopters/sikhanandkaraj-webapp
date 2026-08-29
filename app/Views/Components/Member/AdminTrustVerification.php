<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $trustVerification
 */

$trustVerification =
    isset($trustVerification)
    && is_array($trustVerification)
    ? $trustVerification
    : [];

$mobile =
    isset($trustVerification['mobile'])
    && is_array($trustVerification['mobile'])
    ? $trustVerification['mobile']
    : [];

$email =
    isset($trustVerification['email'])
    && is_array($trustVerification['email'])
    ? $trustVerification['email']
    : [];

$aadhaar =
    isset($trustVerification['aadhaar'])
    && is_array($trustVerification['aadhaar'])
    ? $trustVerification['aadhaar']
    : [];

$videoIntroduction =
    isset($trustVerification['videoIntroduction'])
    && is_array(
        $trustVerification['videoIntroduction']
    )
    ? $trustVerification['videoIntroduction']
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
    ($mobile['isVerified'] ?? false)
    === true;

$isEmailVerified =
    ($email['isVerified'] ?? false)
    === true;

$aadhaarStatus = mb_strtoupper(
    trim(
        (string) (
            $aadhaar['status']
            ?? 'NOT_ADDED'
        )
    )
);

$isVideoApproved =
    ($videoIntroduction['isApproved'] ?? false)
    === true;
?>

<section
    class="card border border-danger
        border-opacity-25 shadow-none mb-4"
    aria-labelledby="adminTrustVerificationTitle">

    <div class="card-header bg-transparent">
        <div class="d-flex align-items-center gap-2">
            <span class="avatar-sm">
                <span
                    class="avatar-title rounded-circle
                        bg-primary-subtle text-primary">

                    <i
                        class="ri-shield-check-line fs-18"
                        aria-hidden="true">
                    </i>
                </span>
            </span>

            <div>
                <h2
                    id="adminTrustVerificationTitle"
                    class="fs-16 fw-semibold mb-1">

                    Trust and Verification
                </h2>

                <p class="text-muted fs-12 mb-0">
                    Member authentication and verified
                    profile information.
                </p>
            </div>
        </div>
    </div>

    <div class="card-body">
        <div class="row g-3">
            <div class="col-12 col-md-6">
                <div
                    class="border rounded p-3 h-100
                        d-flex align-items-center
                        justify-content-between gap-2">

                    <span
                        class="d-flex align-items-center
                            gap-2 min-w-0">

                        <i
                            class="ri-smartphone-line
                                text-primary fs-18"
                            aria-hidden="true">
                        </i>

                        <span class="min-w-0">
                            <span
                                class="d-block text-muted fs-12">

                                Mobile
                            </span>

                            <strong
                                class="d-block text-truncate fs-13">

                                <?= esc(
                                    $mobileValue !== ''
                                        ? $mobileValue
                                        : 'Not added'
                                ) ?>
                            </strong>
                        </span>
                    </span>

                    <span
                        class="badge p-2 <?= $isMobileVerified
                                            ? 'bg-success-subtle text-body'
                                            : 'bg-warning-subtle text-body' ?>">

                        <?= $isMobileVerified
                            ? 'Verified'
                            : 'Pending' ?>
                    </span>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div
                    class="border rounded p-3 h-100
                        d-flex align-items-center
                        justify-content-between gap-2">

                    <span
                        class="d-flex align-items-center
                            gap-2 min-w-0">

                        <i
                            class="ri-mail-line
                                text-info fs-18"
                            aria-hidden="true">
                        </i>

                        <span class="min-w-0">
                            <span
                                class="d-block text-muted fs-12">

                                Email
                            </span>

                            <strong
                                class="d-block text-truncate fs-13">

                                <?= esc(
                                    $emailValue !== ''
                                        ? $emailValue
                                        : 'Not added'
                                ) ?>
                            </strong>
                        </span>
                    </span>

                    <span
                        class="badge p-2 <?= $isEmailVerified
                                            ? 'bg-success-subtle text-body'
                                            : 'bg-warning-subtle text-body' ?>">

                        <?= $isEmailVerified
                            ? 'Verified'
                            : 'Pending' ?>
                    </span>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div
                    class="border rounded p-3 h-100
                        d-flex align-items-center
                        justify-content-between gap-2">

                    <span
                        class="d-flex align-items-center gap-2">

                        <i
                            class="ri-fingerprint-line
                                text-warning fs-18"
                            aria-hidden="true">
                        </i>

                        <strong class="fs-13">
                            Aadhaar
                        </strong>
                    </span>

                    <?php if (
                        $aadhaarStatus === 'APPROVED'
                    ): ?>
                        <span
                            class="badge
                                bg-success-subtle
                                text-body p-2">

                            Verified
                        </span>
                    <?php elseif (
                        $aadhaarStatus === 'UNDER_REVIEW'
                    ): ?>
                        <span
                            class="badge
                                bg-warning-subtle
                                text-body p-2">

                            Under Review
                        </span>
                    <?php elseif (
                        $aadhaarStatus === 'REJECTED'
                    ): ?>
                        <span
                            class="badge
                                bg-danger-subtle
                                text-body p-2">

                            Rejected
                        </span>
                    <?php else: ?>
                        <span
                            class="badge
                                bg-secondary-subtle
                                text-body p-2">

                            Not Added
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div
                    class="border rounded p-3 h-100
                        d-flex align-items-center
                        justify-content-between gap-2">

                    <span
                        class="d-flex align-items-center gap-2">

                        <i
                            class="ri-video-line
                                text-danger fs-18"
                            aria-hidden="true">
                        </i>

                        <strong class="fs-13">
                            Live Introduction
                        </strong>
                    </span>

                    <span
                        class="badge p-2 <?= $isVideoApproved
                                            ? 'bg-success-subtle text-body'
                                            : 'bg-secondary-subtle text-body' ?>">

                        <?= $isVideoApproved
                            ? 'Verified'
                            : 'Not Verified' ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</section>