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

$videoIntroduction =
    isset(
        $trustVerification['videoIntroduction']
    )
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

$emailStatus = mb_strtoupper(
    trim(
        (string) (
            $email['status']
            ?? 'NOT_ADDED'
        )
    )
);

if (
    !in_array(
        $emailStatus,
        [
            'VERIFIED',
            'PENDING',
            'NOT_ADDED',
        ],
        true
    )
) {
    $emailStatus = 'NOT_ADDED';
}

$emailStatusLabel = trim(
    (string) (
        $email['statusLabel']
        ?? ''
    )
);

if ($emailStatusLabel === '') {
    $emailStatusLabel = match ($emailStatus) {
        'VERIFIED' =>
        'Verified',

        'PENDING' =>
        'Verification pending',

        default =>
        'Not added',
    };
}

$emailSettingsUrl = route_to(
    'web.account.settings.section',
    'email'
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

$aadhaarSettingsUrl = route_to(
    'web.account.settings.section',
    'aadhaar-verification'
);

$isVideoIntroductionApproved =
    (
        $videoIntroduction['isApproved']
        ?? false
    ) === true;

$videoIntroductionSettingsUrl = route_to(
    'web.account.settings.section',
    'video-introduction'
);
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
        <!-- Email verification -->
        <a
            href="<?= esc(
                        $emailSettingsUrl,
                        'attr'
                    ) ?>"
            class="d-flex
        align-items-center
        justify-content-between
        gap-3
        py-2
        border-top
        text-body
        text-decoration-none"
            aria-label="<?= esc(
                            'Manage email address. Current status: '
                                . $emailStatusLabel,
                            'attr'
                        ) ?>">

            <span
                class="d-flex
            align-items-center
            gap-2
            min-w-0">

                <i
                    class="ri-mail-line
                text-info
                fs-18
                flex-shrink-0"
                    aria-hidden="true">
                </i>

                <span class="min-w-0">
                    <span class="d-block text-muted fs-12">
                        Email
                    </span>

                    <span
                        class="d-block
                    fw-medium
                    text-truncate
                    fs-13"
                        <?php if (
                            $emailValue !== ''
                        ): ?>
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

            <span
                class="d-inline-flex
            align-items-center
            gap-1
            flex-shrink-0">

                <?php if (
                    $emailStatus === 'VERIFIED'
                ): ?>
                    <span
                        class="badge
                    bg-success-subtle
                    text-success
                    fs-11
                    p-2">

                        Verified
                    </span>
                <?php elseif (
                    $emailStatus === 'PENDING'
                ): ?>
                    <span
                        class="badge
                    bg-warning-subtle
                    text-warning
                    fs-11
                    p-2">

                        Pending
                    </span>
                <?php else: ?>
                    <span
                        class="badge
                    bg-secondary-subtle
                    text-body-secondary
                    fs-11
                    p-2">

                        Not added
                    </span>
                <?php endif; ?>
            </span>
        </a>

        <!-- Aadhaar verification -->
        <a
            href="<?= esc(
                        $aadhaarSettingsUrl,
                        'attr'
                    ) ?>"
            class="d-flex
        align-items-center
        justify-content-between
        gap-3
        py-2
        border-top
        text-body
        text-decoration-none"
            aria-label="<?= esc(
                            'Manage Aadhaar verification. Current status: '
                                . match ($aadhaarStatus) {
                                    'APPROVED' =>
                                    'Verified',

                                    'UNDER_REVIEW' =>
                                    'Under Review',

                                    'REJECTED' =>
                                    'Rejected',

                                    default =>
                                    'Not Added',
                                },
                            'attr'
                        ) ?>">

            <span
                class="d-flex
            align-items-center
            gap-2">

                <i
                    class="ri-fingerprint-line
                text-warning
                fs-18"
                    aria-hidden="true">
                </i>

                <span class="fw-medium fs-13">
                    Aadhaar
                </span>

            </span>

            <span
                class="d-inline-flex
            align-items-center
            gap-1">

                <?php if (
                    $aadhaarStatus === 'APPROVED'
                ): ?>

                    <span
                        class="badge
                    bg-success-subtle
                    text-success
                    fs-11
                    p-2">

                        Verified

                    </span>

                <?php elseif (
                    $aadhaarStatus === 'UNDER_REVIEW'
                ): ?>

                    <span
                        class="badge
                    bg-warning-subtle
                    text-warning
                    fs-11
                    p-2">

                        Under Review

                    </span>

                <?php elseif (
                    $aadhaarStatus === 'REJECTED'
                ): ?>

                    <span
                        class="badge
                    bg-danger-subtle
                    text-danger
                    fs-11
                    p-2">

                        Rejected

                    </span>

                <?php else: ?>

                    <button
                        type="button"
                        class="btn btn-sm btn-outline-primary">

                        Add
                    </button>

                <?php endif; ?>


            </span>

        </a>

        <!-- Video Introduction -->
        <a
            href="<?= esc(
                        $videoIntroductionSettingsUrl,
                        'attr'
                    ) ?>"
            class="d-flex align-items-center
        justify-content-between gap-3
        pt-2 border-top text-body
        text-decoration-none">

            <span
                class="d-flex align-items-center gap-2">

                <i
                    class="ri-video-line
                text-danger fs-18"
                    aria-hidden="true">
                </i>

                <span class="fw-medium fs-13">
                    Video Introduction
                </span>
            </span>

            <?php if (
                $isVideoIntroductionApproved
            ): ?>
                <span
                    class="badge bg-success-subtle
                text-success fs-11 p-2">

                    Approved
                </span>
            <?php else: ?>
                <button
                    type="button"
                    class="btn btn-sm btn-outline-primary">

                    Add
                </button>
            <?php endif; ?>
        </a>


        <?php if ($showCard): ?>
        </div>
    </section>
<?php endif; ?>