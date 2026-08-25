<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $diagnostics
 */

$diagnostics =
    isset($diagnostics)
    && is_array($diagnostics)
    ? $diagnostics
    : [];

if ($diagnostics === []) {
    return;
}

$weights =
    isset($diagnostics['weights'])
    && is_array(
        $diagnostics['weights']
    )
    ? $diagnostics['weights']
    : [];

$profileCompletion =
    max(
        0,
        min(
            100,
            (int) (
                $diagnostics['profileCompletion']
                ?? 0
            )
        )
    );

$approvedPhotoCount =
    max(
        0,
        (int) (
            $diagnostics['approvedPhotoCount']
            ?? 0
        )
    );

$approvedPhotoScore =
    (float) (
        $diagnostics['approvedPhotoScore']
        ?? 0
    );

$trustPoints =
    max(
        0,
        (int) (
            $diagnostics['trustPoints']
            ?? 0
        )
    );

$trustScore =
    (float) (
        $diagnostics['trustScore']
        ?? 0
    );

$commercialPriority =
    max(
        0,
        (int) (
            $diagnostics['commercialPriority']
            ?? 0
        )
    );

$commercialScore =
    (float) (
        $diagnostics['commercialScore']
        ?? 0
    );

$membershipPlan =
    trim(
        (string) (
            $diagnostics['membershipPlanName']
            ?? ''
        )
    );

if ($membershipPlan === '') {
    $membershipPlan =
        'Free';
}

$verificationSignals = [
    [
        'label' =>
        'Mobile',

        'verified' => ($diagnostics['mobileVerified'] ?? false) === true,

        'points' =>
        1,
    ],
    [
        'label' =>
        'Email',

        'verified' => ($diagnostics['emailVerified'] ?? false) === true,

        'points' =>
        1,
    ],
    [
        'label' =>
        'Aadhaar',

        'verified' => ($diagnostics['aadhaarVerified'] ?? false) === true,

        'points' =>
        3,
    ],
    [
        'label' =>
        'Live Introduction',

        'verified' => ($diagnostics['videoVerified'] ?? false) === true,

        'points' =>
        3,
    ],
];
?>

<div class="card">

    <div class="card-header">

        <div
            class="d-flex
                align-items-center
                justify-content-between
                gap-3">

            <div>
                <h5 class="card-title mb-1">
                    Match Score Diagnostics
                </h5>

                <p class="text-muted mb-0">
                    Ranking inputs currently available for this member.
                </p>
            </div>

            <span
                class="badge
                    bg-primary-subtle
                    text-primary">

                Admin Only
            </span>

        </div>

    </div>

    <div class="card-body">

        <div
            class="alert
                alert-info
                d-flex
                align-items-start
                gap-2"
            role="alert">

            <i
                class="ri-information-line
                    fs-20"
                aria-hidden="true"></i>

            <div>
                A single final Match Score is not shown here because
                Partner Preference compatibility is calculated from the
                viewing member to this candidate. The final score therefore
                changes by viewer.
            </div>

        </div>

        <div class="row g-3">

            <div class="col-md-6 col-xl-3">

                <div
                    class="border
                        rounded
                        p-3
                        h-100">

                    <div
                        class="d-flex
                            align-items-center
                            justify-content-between
                            mb-2">

                        <span class="text-muted">
                            Profile Completion
                        </span>

                        <i
                            class="ri-profile-line
                                text-primary"
                            aria-hidden="true"></i>

                    </div>

                    <div class="fs-4 fw-semibold">
                        <?= esc(
                            (string)
                            $profileCompletion
                        ) ?>%
                    </div>

                    <div class="small text-muted mt-1">
                        Weight:
                        <?= esc(
                            (string) (
                                $weights['profileCompletion']
                                ?? 0
                            )
                        ) ?>%
                    </div>

                </div>
            </div>

            <div class="col-md-6 col-xl-3">

                <div
                    class="border
                        rounded
                        p-3
                        h-100">

                    <div
                        class="d-flex
                            align-items-center
                            justify-content-between
                            mb-2">

                        <span class="text-muted">
                            Approved Photos
                        </span>

                        <i
                            class="ri-image-line
                                text-primary"
                            aria-hidden="true"></i>

                    </div>

                    <div class="fs-4 fw-semibold">
                        <?= esc(
                            number_format(
                                $approvedPhotoScore,
                                2
                            )
                        ) ?>%
                    </div>

                    <div class="small text-muted mt-1">
                        <?= esc(
                            (string)
                            $approvedPhotoCount
                        ) ?>
                        approved · Weight:
                        <?= esc(
                            (string) (
                                $weights['approvedPhotos']
                                ?? 0
                            )
                        ) ?>%
                    </div>

                </div>
            </div>

            <div class="col-md-6 col-xl-3">

                <div
                    class="border
                        rounded
                        p-3
                        h-100">

                    <div
                        class="d-flex
                            align-items-center
                            justify-content-between
                            mb-2">

                        <span class="text-muted">
                            Trust
                        </span>

                        <i
                            class="ri-shield-check-line
                                text-success"
                            aria-hidden="true"></i>

                    </div>

                    <div class="fs-4 fw-semibold">
                        <?= esc(
                            number_format(
                                $trustScore,
                                2
                            )
                        ) ?>%
                    </div>

                    <div class="small text-muted mt-1">
                        <?= esc(
                            (string)
                            $trustPoints
                        ) ?>/8 points · Weight:
                        <?= esc(
                            (string) (
                                $weights['trust']
                                ?? 0
                            )
                        ) ?>%
                    </div>

                </div>
            </div>

            <div class="col-md-6 col-xl-3">

                <div
                    class="border
                        rounded
                        p-3
                        h-100">

                    <div
                        class="d-flex
                            align-items-center
                            justify-content-between
                            mb-2">

                        <span class="text-muted">
                            Membership Priority
                        </span>

                        <i
                            class="ri-vip-crown-line
                                text-primary"
                            aria-hidden="true"></i>

                    </div>

                    <div class="fs-4 fw-semibold">
                        <?= esc(
                            number_format(
                                $commercialScore,
                                2
                            )
                        ) ?>%
                    </div>

                    <div class="small text-muted mt-1">
                        <?= esc(
                            $membershipPlan
                        ) ?>
                        · Priority
                        <?= esc(
                            (string)
                            $commercialPriority
                        ) ?>/3
                        · Weight:
                        <?= esc(
                            (string) (
                                $weights['commercial']
                                ?? 0
                            )
                        ) ?>%
                    </div>

                </div>
            </div>

        </div>

        <hr>

        <div class="row">

            <div class="col-lg-7">

                <h6 class="mb-3">
                    Trust Signals
                </h6>

                <div class="row g-2">

                    <?php foreach (
                        $verificationSignals
                        as $signal
                    ): ?>

                        <div class="col-sm-6">

                            <div
                                class="d-flex
                                    align-items-center
                                    justify-content-between
                                    border
                                    rounded
                                    px-3
                                    py-2">

                                <div
                                    class="d-flex
                                        align-items-center
                                        gap-2">

                                    <i
                                        class="<?= $signal['verified']
                                                    ? 'ri-checkbox-circle-fill text-success'
                                                    : 'ri-close-circle-line text-muted' ?>"
                                        aria-hidden="true"></i>

                                    <span>
                                        <?= esc(
                                            $signal['label']
                                        ) ?>
                                    </span>

                                </div>

                                <span class="small text-muted">
                                    <?= $signal['verified']
                                        ? '+'
                                        . esc(
                                            (string)
                                            $signal['points']
                                        )
                                        : '+0' ?>
                                </span>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

            <div class="col-lg-5 mt-4 mt-lg-0">

                <h6 class="mb-3">
                    Active Ranking Weights
                </h6>

                <div
                    class="d-flex
                        justify-content-between
                        py-1">

                    <span class="text-muted">
                        Partner Preference
                    </span>

                    <strong>
                        <?= esc(
                            (string) (
                                $weights['preference']
                                ?? 0
                            )
                        ) ?>%
                    </strong>

                </div>

                <div
                    class="d-flex
                        justify-content-between
                        py-1">

                    <span class="text-muted">
                        Profile Completion
                    </span>

                    <strong>
                        <?= esc(
                            (string) (
                                $weights['profileCompletion']
                                ?? 0
                            )
                        ) ?>%
                    </strong>

                </div>

                <div
                    class="d-flex
                        justify-content-between
                        py-1">

                    <span class="text-muted">
                        Approved Photos
                    </span>

                    <strong>
                        <?= esc(
                            (string) (
                                $weights['approvedPhotos']
                                ?? 0
                            )
                        ) ?>%
                    </strong>

                </div>

                <div
                    class="d-flex
                        justify-content-between
                        py-1">

                    <span class="text-muted">
                        Trust
                    </span>

                    <strong>
                        <?= esc(
                            (string) (
                                $weights['trust']
                                ?? 0
                            )
                        ) ?>%
                    </strong>

                </div>

                <div
                    class="d-flex
                        justify-content-between
                        py-1">

                    <span class="text-muted">
                        Membership Priority
                    </span>

                    <strong>
                        <?= esc(
                            (string) (
                                $weights['commercial']
                                ?? 0
                            )
                        ) ?>%
                    </strong>

                </div>

            </div>

        </div>

    </div>
</div>