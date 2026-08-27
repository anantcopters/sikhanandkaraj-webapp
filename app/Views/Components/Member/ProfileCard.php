<?php

declare(strict_types=1);

/**
 * Search / Match profile-card presentation.
 *
 * All business decisions are resolved before this component:
 *
 * - member eligibility;
 * - photograph visibility;
 * - Interest state;
 * - membership capabilities;
 * - shortlist relationship state.
 *
 * The component must never inspect plan codes itself.
 *
 * @var array<string,mixed> $profile
 */

$profile =
    isset($profile)
    && is_array($profile)
    ? $profile
    : [];

$name = trim(
    (string) (
        $profile['name']
        ?? 'Member'
    )
);

if ($name === '') {
    $name = 'Member';
}

$reference = trim(
    (string) (
        $profile['referenceId']
        ?? ''
    )
);

$image = trim(
    (string) (
        $profile['image']
        ?? ''
    )
);

$profileUrl = trim(
    (string) (
        $profile['profileUrl']
        ?? ''
    )
);

$interestUrl = trim(
    (string) (
        $profile['interestUrl']
        ?? ''
    )
);

$shortlistUrl = trim(
    (string) (
        $profile['shortlistUrl']
        ?? ''
    )
);

$reportUrl = trim(
    (string) (
        $profile['reportUrl']
        ?? ''
    )
);

$blockUrl = trim(
    (string) (
        $profile['blockUrl']
        ?? ''
    )
);

$canViewFullProfile =
    (
        $profile['canViewFullProfile']
        ?? false
    ) === true;

$canShortlist =
    (
        $profile['canShortlist']
        ?? false
    ) === true;

$canReport =
    (
        $profile['canReport']
        ?? false
    ) === true;

$canBlock =
    (
        $profile['canBlock']
        ?? false
    ) === true;

$isShortlisted =
    (
        $profile['isShortlisted']
        ?? false
    ) === true;

$age =
    is_numeric(
        $profile['age']
            ?? null
    )
    ? max(
        0,
        (int) $profile['age']
    )
    : null;

$height = trim(
    (string) (
        $profile['height']
        ?? ''
    )
);

$location = trim(
    (string) (
        $profile['location']
        ?? ''
    )
);

$maritalStatus = trim(
    (string) (
        $profile['maritalStatus']
        ?? ''
    )
);

$activity = trim(
    (string) (
        $profile['activity']
        ?? ''
    )
);

$accountType = trim(
    (string) (
        $profile['accountType']
        ?? ''
    )
);

$professionalSummary = trim(
    (string) (
        $profile['professionalSummary']
        ?? ''
    )
);

$verification =
    isset($profile['verification'])
    && is_array(
        $profile['verification']
    )
    ? $profile['verification']
    : [];

$interestRelationship =
    isset($profile['interestRelationship'])
    && is_array(
        $profile['interestRelationship']
    )
    ? $profile['interestRelationship']
    : [];

$interestState = mb_strtoupper(
    trim(
        (string) (
            $interestRelationship['state']
            ?? 'NONE'
        )
    )
);

$canShowInterest =
    (
        $interestRelationship['canShowInterest']
        ?? false
    ) === true;

$relationshipLabel =
    match ($interestState) {
        'PENDING_SENT' =>
        'Interest Sent',

        'PENDING_RECEIVED' =>
        'Interest Received',

        'ACCEPTED_SENT',
        'ACCEPTED_RECEIVED' =>
        'Interest Accepted',

        'DECLINED_SENT',
        'DECLINED_RECEIVED' =>
        'Interest Declined',

        default =>
        '',
    };

$relationshipBadgeClass =
    match ($interestState) {
        'ACCEPTED_SENT',
        'ACCEPTED_RECEIVED' =>
        'bg-success-subtle text-success',

        'DECLINED_SENT',
        'DECLINED_RECEIVED' =>
        'bg-danger-subtle text-danger',

        'PENDING_SENT',
        'PENDING_RECEIVED' =>
        'bg-warning-subtle text-body',

        default =>
        'bg-light text-body',
    };

/*
 * Modal IDs use the public profile reference.
 *
 * Numeric database member IDs are never exposed.
 */
$safeModalReference =
    preg_replace(
        '/[^A-Za-z0-9_-]/',
        '',
        $reference
    )
    ?? '';

$reportModalId =
    'profileCardReport'
    . $safeModalReference;

$blockModalId =
    'profileCardBlock'
    . $safeModalReference;
?>

<article
    class="card h-95 border border-danger
        border-opacity-25 shadow-sm
        overflow-hidden">

    <div class="card-body p-3 p-md-4">

        <div
            class="d-flex flex-column
                flex-sm-row gap-3">

            <div
                class="d-flex flex-column
                    align-items-center
                    flex-shrink-0"
                style="width: 160px;">

                <div class="member-profile-thumbnail">

                    <img
                        src="<?= esc(
                                    $image,
                                    'attr'
                                ) ?>"
                        alt="<?= esc(
                                    $name
                                        . ' profile photo',
                                    'attr'
                                ) ?>"
                        loading="lazy">

                </div>

                <?php if ($accountType !== ''): ?>

                    <span
                        class="badge rounded
                            bg-primary-subtle
                            text-primary
                            border border-primary
                            border-opacity-25
                            mt-3 px-2 py-2 fs-12">

                        <i
                            class="ri-vip-crown-line
                                me-1 fs-14"
                            aria-hidden="true">
                        </i>

                        <?= esc($accountType) ?>

                    </span>

                <?php endif; ?>

            </div>

            <div class="flex-grow-1 min-w-0">

                <div
                    class="d-flex
                        align-items-start
                        justify-content-between
                        gap-2 mb-2">

                    <div class="min-w-0">

                        <h3
                            class="fs-18
                                fw-semibold mb-1
                                text-truncate">

                            <?= esc($name) ?>

                        </h3>

                        <?php if ($reference !== ''): ?>

                            <div
                                class="text-muted
                                    fs-13">

                                <?= esc($reference) ?>

                            </div>

                        <?php endif; ?>

                    </div>

                    <div
                        class="d-flex
                            align-items-center
                            gap-2">

                        <?php if (
                            $relationshipLabel !== ''
                        ): ?>

                            <span
                                class="badge <?= esc(
                                                    $relationshipBadgeClass,
                                                    'attr'
                                                ) ?>
                                    border px-2 py-2">

                                <?= esc(
                                    $relationshipLabel
                                ) ?>

                            </span>

                        <?php endif; ?>

                        <?php if (
                            $canReport
                            || $canBlock
                            || $canShortlist
                            || $isShortlisted
                        ): ?>

                            <div class="dropdown">

                                <button
                                    type="button"
                                    class="btn btn-info
                                        btn-sm btn-icon"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                    aria-label="Profile actions">

                                    <i
                                        class="ri-more-2-fill"
                                        aria-hidden="true">
                                    </i>

                                </button>

                                <div
                                    class="dropdown-menu
                                        dropdown-menu-end
                                        shadow-sm">

                                    <?php if (
                                        $isShortlisted
                                        || $canShortlist
                                    ): ?>

                                        <form
                                            method="post"
                                            action="<?= esc(
                                                        $shortlistUrl,
                                                        'attr'
                                                    ) ?>">

                                            <?= csrf_field() ?>

                                            <button
                                                type="submit"
                                                class="dropdown-item
                                                    d-flex
                                                    align-items-center
                                                    gap-2">

                                                <i
                                                    class="<?= $isShortlisted
                                                                ? 'ri-bookmark-fill'
                                                                : 'ri-bookmark-line' ?>"
                                                    aria-hidden="true">
                                                </i>

                                                <?= $isShortlisted
                                                    ? 'Remove from Shortlist'
                                                    : 'Shortlist Profile' ?>

                                            </button>

                                        </form>

                                    <?php elseif (!$canShortlist): ?>

                                        <a
                                            href="<?= route_to(
                                                        'web.account.settings.section',
                                                        'plans'
                                                    ) ?>"
                                            class="dropdown-item
                                                d-flex
                                                align-items-center
                                                gap-2">

                                            <i
                                                class="ri-lock-2-line"
                                                aria-hidden="true">
                                            </i>

                                            Shortlist — Upgrade

                                        </a>

                                    <?php endif; ?>

                                    <?php if ($canReport): ?>

                                        <button
                                            type="button"
                                            class="dropdown-item
                                                d-flex
                                                align-items-center
                                                gap-2"
                                            data-bs-toggle="modal"
                                            data-bs-target="#<?= esc(
                                                                    $reportModalId,
                                                                    'attr'
                                                                ) ?>">

                                            <i
                                                class="ri-flag-line"
                                                aria-hidden="true">
                                            </i>

                                            Report Profile

                                        </button>

                                    <?php endif; ?>

                                    <?php if ($canBlock): ?>

                                        <button
                                            type="button"
                                            class="dropdown-item
                                                d-flex
                                                align-items-center
                                                gap-2 text-danger"
                                            data-bs-toggle="modal"
                                            data-bs-target="#<?= esc(
                                                                    $blockModalId,
                                                                    'attr'
                                                                ) ?>">

                                            <i
                                                class="ri-forbid-line"
                                                aria-hidden="true">
                                            </i>

                                            Block Profile

                                        </button>

                                    <?php endif; ?>

                                </div>

                            </div>

                        <?php endif; ?>

                    </div>

                </div>

                <?php if ($activity !== ''): ?>

                    <div
                        class="d-flex
                            align-items-center
                            gap-1 fs-12
                            text-success mb-2">

                        <i
                            class="ri-checkbox-blank-circle-fill"
                            aria-hidden="true">
                        </i>

                        <?= esc($activity) ?>

                    </div>

                <?php endif; ?>

                <div
                    class="d-flex flex-wrap
                        align-items-center
                        gap-2 fs-13
                        text-muted mb-2">

                    <?php if (
                        $age !== null
                        && $age > 0
                    ): ?>

                        <span>
                            <?= esc(
                                (string) $age
                            ) ?> yrs
                        </span>

                    <?php endif; ?>

                    <?php if ($height !== ''): ?>

                        <span aria-hidden="true">·</span>

                        <span>
                            <?= esc($height) ?>
                        </span>

                    <?php endif; ?>

                </div>

                <?php if ($location !== ''): ?>

                    <p
                        class="d-flex
                            align-items-center
                            gap-1 text-muted
                            fs-13 mb-2">

                        <i
                            class="ri-map-pin-line
                                text-primary"
                            aria-hidden="true">
                        </i>

                        <?= esc($location) ?>

                    </p>

                <?php endif; ?>

                <?php if ($maritalStatus !== ''): ?>

                    <p class="fs-13 mb-3">
                        <?= esc($maritalStatus) ?>
                    </p>

                <?php endif; ?>

                <div
                    class="d-flex flex-wrap
                        align-items-center gap-2">

                    <?php if (
                        $canViewFullProfile
                        && $profileUrl !== ''
                    ): ?>

                        <a
                            href="<?= esc(
                                        $profileUrl,
                                        'attr'
                                    ) ?>"
                            class="btn
                                btn-outline-primary
                                btn-sm
                                d-inline-flex
                                align-items-center
                                gap-1">

                            <i
                                class="ri-eye-line"
                                aria-hidden="true">
                            </i>

                            View Profile

                        </a>

                    <?php else: ?>

                        <a
                            href="<?= route_to(
                                        'web.account.settings.section',
                                        'plans'
                                    ) ?>"
                            class="btn
                                btn-outline-primary
                                btn-sm
                                d-inline-flex
                                align-items-center fs-12
                                gap-1">

                            <i
                                class="ri-lock-2-line"
                                aria-hidden="true">
                            </i>

                            UPGRADE to View Profile

                        </a>

                    <?php endif; ?>

                    <?php if (
                        $canShowInterest
                        && $interestUrl !== ''
                    ): ?>

                        <form
                            method="post"
                            action="<?= esc(
                                        $interestUrl,
                                        'attr'
                                    ) ?>"
                            data-member-interest-form>
                            <input
                                type="hidden"
                                name="action_source"
                                value="card">

                            <?= csrf_field() ?>

                            <button
                                type="submit"
                                class="btn btn-danger
                                    btn-sm
                                    d-inline-flex
                                    align-items-center
                                    gap-2 fs-12">

                                <span
                                    data-member-interest-label>

                                    <i
                                        class="ri-heart-add-line"
                                        aria-hidden="true">
                                    </i>

                                    Show Interest

                                </span>

                                <span
                                    class="d-none
                                        align-items-center
                                        gap-2"
                                    data-member-interest-loading>

                                    <span
                                        class="spinner-border
                                            spinner-border-sm"
                                        aria-hidden="true">
                                    </span>

                                    Sending...

                                </span>

                            </button>

                        </form>

                    <?php endif; ?>

                </div>

            </div>

        </div>

        <?php if (
            $professionalSummary !== ''
        ): ?>

            <div
                class="d-flex
                    align-items-center mt-3">

                <div
                    class="avatar-xs
                        flex-shrink-0 me-2">

                    <span
                        class="avatar-title
                            bg-dark-subtle
                            rounded-circle shadow">

                        <i
                            class="ri-briefcase-4-line
                                fs-16 text-primary"
                            aria-hidden="true">
                        </i>

                    </span>

                </div>

                <div class="flex-grow-1">

                    <h5
                        class="fs-13
                            mb-0 fw-semibold">

                        <?= esc(
                            $professionalSummary
                        ) ?>

                    </h5>

                </div>

            </div>

        <?php endif; ?>

    </div>

    <?= view(
        'Components/Member/VerificationBadges',
        [
            'verification' =>
            $verification,
        ]
    ) ?>

</article>

<?php if (
    $canReport
    && $reportUrl !== ''
): ?>

    <?= view(
        'Components/Member/ProfileReportModal',
        [
            'modalId' =>
            $reportModalId,

            'profileReference' =>
            $reference,

            'actionUrl' =>
            $reportUrl,

            'reportCaptcha' =>
            $reportCaptcha
                ?? '',
        ]
    ) ?>

<?php endif; ?>

<?php if (
    $canBlock
    && $blockUrl !== ''
): ?>

    <?= view(
        'Components/Member/ProfileBlockModal',
        [
            'modalId' =>
            $blockModalId,

            'profileReference' =>
            $reference,

            'actionUrl' =>
            $blockUrl,

            'blockCaptcha' =>
            $blockCaptcha
                ?? '',
        ]
    ) ?>

<?php endif; ?>