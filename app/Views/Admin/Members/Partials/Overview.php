<?php

declare(strict_types=1);

/**
 * Compact administrator member summary and contact row.
 *
 * @var int      $memberId
 * @var string   $profileImage
 * @var string   $fullName
 * @var string   $profileReference
 * @var string   $accountStatus
 * @var string   $statusBadgeClass
 * @var string   $genderLabel
 * @var string   $profileCreatedFor
 * @var int|null $age
 * @var int      $completionPercentage
 * @var string   $mobileNumber
 * @var bool     $isMobileVerified
 * @var string   $emailAddress
 * @var bool     $isEmailVerified
 * @var string   $currentLocation
 * @var string   $displayAccountCreated
 * @var string   $accountCreatedIso
 * @var bool     $canBlock
 * @var bool     $canUnblock
 */

$resolvedMemberId = max(
    0,
    (int) ($memberId ?? 0)
);

$resolvedProfileImage = trim(
    (string) ($profileImage ?? '')
);

$resolvedFullName = trim(
    (string) ($fullName ?? '')
);

if ($resolvedFullName === '') {
    $resolvedFullName = 'Member';
}

$resolvedProfileReference = trim(
    (string) ($profileReference ?? '')
);

$resolvedAccountStatus = trim(
    (string) ($accountStatus ?? '')
);

$resolvedStatusBadgeClass = trim(
    (string) (
        $statusBadgeClass
        ?? 'bg-secondary-subtle text-secondary'
    )
);

$resolvedGenderLabel = trim(
    (string) ($genderLabel ?? '—')
);

$resolvedProfileCreatedFor = trim(
    (string) ($profileCreatedFor ?? '—')
);

$resolvedAge = isset($age)
    && is_numeric($age)
    ? max(0, (int) $age)
    : null;

$resolvedCompletionPercentage = max(
    0,
    min(
        100,
        (int) ($completionPercentage ?? 0)
    )
);

$resolvedMobileNumber = trim(
    (string) ($mobileNumber ?? '')
);

$resolvedMobileVerified =
    ($isMobileVerified ?? false) === true;

$resolvedEmailAddress = trim(
    (string) ($emailAddress ?? '')
);

$resolvedEmailVerified =
    ($isEmailVerified ?? false) === true;

$resolvedCurrentLocation = trim(
    (string) ($currentLocation ?? '')
);

$resolvedDisplayAccountCreated = trim(
    (string) (
        $displayAccountCreated
        ?? '—'
    )
);

$resolvedAccountCreatedIso = trim(
    (string) ($accountCreatedIso ?? '')
);

$resolvedCanBlock =
    ($canBlock ?? false) === true;

$resolvedCanUnblock =
    ($canUnblock ?? false) === true;
?>

<div class="row g-4 mb-4">

    <div class="col-12 col-xl-6">

        <section
            class="card border border-danger
                border-opacity-25 h-100 mb-0">

            <div class="card-body p-3">

                <div
                    class="d-flex flex-column
                        flex-sm-row gap-3">

                    <div
                        class="member-profile-thumbnail
                            flex-shrink-0">

                        <img
                            src="<?= esc(
                                        $resolvedProfileImage,
                                        'attr'
                                    ) ?>"
                            alt="<?= esc(
                                        $resolvedFullName
                                            . ' profile photo',
                                        'attr'
                                    ) ?>">
                    </div>

                    <div class="flex-grow-1 min-w-0">

                        <div
                            class="d-flex align-items-center
                                flex-wrap gap-2 mb-2">

                            <h2
                                class="fs-20
                                    fw-semibold mb-0">

                                <?= esc(
                                    $resolvedFullName
                                ) ?>
                            </h2>

                            <span
                                class="badge <?= esc(
                                                    $resolvedStatusBadgeClass,
                                                    'attr'
                                                ) ?>">

                                <?= esc(
                                    $resolvedAccountStatus !== ''
                                        ? $resolvedAccountStatus
                                        : 'UNKNOWN'
                                ) ?>
                            </span>
                        </div>

                        <div
                            class="d-flex flex-wrap
                                align-items-center gap-2
                                text-muted fs-13 mb-3">

                            <span
                                class="badge bg-primary-subtle
                                    text-primary">

                                <?= esc(
                                    $resolvedProfileReference !== ''
                                        ? $resolvedProfileReference
                                        : 'No Reference'
                                ) ?>
                            </span>

                            <span>
                                <?= esc(
                                    $resolvedGenderLabel
                                ) ?>
                            </span>

                            <span aria-hidden="true">
                                •
                            </span>

                            <span>
                                Created for
                                <?= esc(
                                    $resolvedProfileCreatedFor
                                ) ?>
                            </span>

                            <?php if (
                                $resolvedAge !== null
                            ): ?>

                                <span aria-hidden="true">
                                    •
                                </span>

                                <span>
                                    <?= esc(
                                        (string) $resolvedAge
                                    ) ?>
                                    Years
                                </span>

                            <?php endif; ?>
                        </div>

                        <div
                            class="d-flex align-items-center
                                justify-content-between gap-3
                                border-top border-bottom
                                py-2 mb-3">

                            <span class="text-muted fs-13">
                                Profile Completion
                            </span>

                            <span
                                class="fw-semibold
                                    text-primary fs-18">

                                <?= esc(
                                    (string)
                                    $resolvedCompletionPercentage
                                ) ?>%
                            </span>
                        </div>

                        <div class="d-flex flex-wrap gap-2">

                            <button
                                type="button"
                                class="btn btn-soft-info
                                    d-inline-flex
                                    align-items-center gap-1"
                                data-member-history
                                data-history-url="<?= esc(
                                                        route_to(
                                                            'admin.members.history',
                                                            $resolvedMemberId
                                                        ),
                                                        'attr'
                                                    ) ?>">

                                <i
                                    class="ri-history-line"
                                    aria-hidden="true">
                                </i>

                                History
                            </button>

                            <?php if (
                                $resolvedCanBlock
                            ): ?>

                                <button
                                    type="button"
                                    class="btn btn-danger
                                        d-inline-flex
                                        align-items-center gap-1"
                                    data-member-status
                                    data-action="BLOCK"
                                    data-member-name="<?= esc(
                                                            $resolvedFullName,
                                                            'attr'
                                                        ) ?>"
                                    data-member-code="<?= esc(
                                                            $resolvedProfileReference,
                                                            'attr'
                                                        ) ?>"
                                    data-form-action="<?= esc(
                                                            route_to(
                                                                'admin.members.block',
                                                                $resolvedMemberId
                                                            ),
                                                            'attr'
                                                        ) ?>">

                                    <i
                                        class="ri-forbid-line"
                                        aria-hidden="true">
                                    </i>

                                    Block Member
                                </button>

                            <?php elseif (
                                $resolvedCanUnblock
                            ): ?>

                                <button
                                    type="button"
                                    class="btn btn-success
                                        d-inline-flex
                                        align-items-center gap-1"
                                    data-member-status
                                    data-action="UNBLOCK"
                                    data-member-name="<?= esc(
                                                            $resolvedFullName,
                                                            'attr'
                                                        ) ?>"
                                    data-member-code="<?= esc(
                                                            $resolvedProfileReference,
                                                            'attr'
                                                        ) ?>"
                                    data-form-action="<?= esc(
                                                            route_to(
                                                                'admin.members.unblock',
                                                                $resolvedMemberId
                                                            ),
                                                            'attr'
                                                        ) ?>">

                                    <i
                                        class="ri-checkbox-circle-line"
                                        aria-hidden="true">
                                    </i>

                                    Unblock Member
                                </button>

                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="col-12 col-xl-6">

        <section
            class="card border border-danger
                border-opacity-25 h-100 mb-0">

            <div class="card-header">

                <h2 class="card-title fs-16 mb-0">

                    <i
                        class="ri-contacts-line me-1"
                        aria-hidden="true">
                    </i>

                    Contact Information
                </h2>
            </div>

            <div class="card-body">

                <div class="row g-3">

                    <div class="col-12 col-sm-6">

                        <div
                            class="border-bottom
                                pb-2 h-100">

                            <div
                                class="text-muted
                                    fs-12 mb-1">

                                Mobile Number
                            </div>

                            <div
                                class="d-flex
                                    align-items-center
                                    gap-2 fw-medium">

                                <span>
                                    <?= esc(
                                        $resolvedMobileNumber !== ''
                                            ? $resolvedMobileNumber
                                            : '—'
                                    ) ?>
                                </span>

                                <i
                                    class="<?= $resolvedMobileVerified
                                                ? 'ri-checkbox-circle-fill text-success'
                                                : 'ri-close-circle-fill text-danger' ?>"
                                    aria-label="<?= $resolvedMobileVerified
                                                    ? 'Mobile verified'
                                                    : 'Mobile not verified' ?>">
                                </i>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6">

                        <div
                            class="border-bottom
                                pb-2 h-100">

                            <div
                                class="text-muted
                                    fs-12 mb-1">

                                Email Address
                            </div>

                            <div
                                class="d-flex
                                    align-items-center
                                    gap-2 fw-medium">

                                <span>
                                    <?= esc(
                                        $resolvedEmailAddress !== ''
                                            ? $resolvedEmailAddress
                                            : 'Not added'
                                    ) ?>
                                </span>

                                <?php if (
                                    $resolvedEmailAddress !== ''
                                ): ?>

                                    <i
                                        class="<?= $resolvedEmailVerified
                                                    ? 'ri-checkbox-circle-fill text-success'
                                                    : 'ri-close-circle-fill text-danger' ?>"
                                        aria-label="<?= $resolvedEmailVerified
                                                        ? 'Email verified'
                                                        : 'Email not verified' ?>">
                                    </i>

                                <?php endif; ?>

                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6">

                        <div
                            class="border-bottom
                                pb-2 h-100">

                            <div
                                class="text-muted
                                    fs-12 mb-1">

                                Current Location
                            </div>

                            <div class="fw-medium">
                                <?= esc(
                                    $resolvedCurrentLocation !== ''
                                        ? $resolvedCurrentLocation
                                        : '—'
                                ) ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6">

                        <div
                            class="border-bottom
                                pb-2 h-100">

                            <div
                                class="text-muted
                                    fs-12 mb-1">

                                Account Created
                            </div>

                            <div class="fw-medium">

                                <?php if (
                                    $resolvedAccountCreatedIso !== ''
                                ): ?>

                                    <time
                                        datetime="<?= esc(
                                                        $resolvedAccountCreatedIso,
                                                        'attr'
                                                    ) ?>">

                                        <?= esc(
                                            $resolvedDisplayAccountCreated
                                        ) ?>
                                    </time>

                                <?php else: ?>

                                    <?= esc(
                                        $resolvedDisplayAccountCreated
                                    ) ?>

                                <?php endif; ?>

                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>
    </div>

</div>