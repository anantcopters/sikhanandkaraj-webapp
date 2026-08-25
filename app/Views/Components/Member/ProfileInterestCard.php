<?php

declare(strict_types=1);

/**
 * Interest-context member card.
 *
 * Business decisions such as member visibility, blocking, reporting,
 * Interest status, account type, verification state and photo authorization
 * have already been resolved by backend services.
 *
 * This component owns only UI presentation and the existing action forms.
 *
 * @var array<string, mixed> $profile
 * @var string               $activeDirection
 */

$profile =
    isset($profile)
    && is_array($profile)
    ? $profile
    : [];

$activeDirection =
    isset($activeDirection)
    && is_string(
        $activeDirection
    )
    ? strtolower(
        trim(
            $activeDirection
        )
    )
    : 'received';

$reference = trim(
    (string) (
        $profile['referenceId']
        ?? ''
    )
);

$name = trim(
    (string) (
        $profile['name']
        ?? 'Member'
    )
);

if ($name === '') {
    $name = 'Member';
}

$image = trim(
    (string) (
        $profile['image']
        ?? ''
    )
);

$city = trim(
    (string) (
        $profile['city']
        ?? ''
    )
);

$location = trim(
    (string) (
        $profile['location']
        ?? $city
    )
);

$age =
    isset($profile['age'])
    && is_numeric(
        $profile['age']
    )
    ? max(
        0,
        (int) $profile['age']
    )
    : null;

$status = strtoupper(
    trim(
        (string) (
            $profile['status']
            ?? 'PENDING'
        )
    )
);

$profileUrl = trim(
    (string) (
        $profile['profileUrl']
        ?? '#'
    )
);

if ($profileUrl === '') {
    $profileUrl = '#';
}

/*
 * Account type and professional summary come from the shared
 * MemberProfilePresentationService contract.
 */
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

/*
 * Verification values are normalized to booleans by
 * MemberProfilePresentationService.
 */
$verification =
    isset($profile['verification'])
    && is_array(
        $profile['verification']
    )
    ? $profile['verification']
    : [];

$isReceived =
    $activeDirection
    === 'received';

$canRespond =
    $isReceived
    && $status
    === 'PENDING';

/*
 * Format the Interest date for presentation only.
 *
 * No business decision depends on this formatted value.
 */
$interestDate = '';

$createdAt = trim(
    (string) (
        $profile['createdAt']
        ?? ''
    )
);

if ($createdAt !== '') {
    try {
        $interestDate = (
            new DateTimeImmutable(
                $createdAt
            )
        )->format(
            'd M y'
        );
    } catch (Throwable) {
        $interestDate = '';
    }
}

/*
 * The displayed status is intentionally restricted to the existing
 * Interest states.
 */
$badgeClass =
    match ($status) {
        'ACCEPTED' =>
        'bg-success-subtle text-success',

        'DECLINED' =>
        'bg-danger-subtle text-danger',

        default =>
        'bg-warning-subtle text-body',
    };

$statusLabel =
    match ($status) {
        'ACCEPTED' =>
        'Accepted',

        'DECLINED' =>
        'Declined',

        default =>
        'Pending',
    };

$canViewFullProfile =
    ($profile['canViewFullProfile'] ?? false)
    === true;

$canShortlist =
    ($profile['canShortlist'] ?? false)
    === true;

$canReport =
    ($profile['canReport'] ?? false)
    === true;

$canBlock =
    ($profile['canBlock'] ?? false)
    === true;

$isShortlisted =
    ($profile['isShortlisted'] ?? false)
    === true;

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
?>

<article
    class="card h-95 border border-danger
        border-opacity-25 shadow-sm
        overflow-hidden">

    <div class="card-body p-3 p-md-4">

        <div
            class="d-flex flex-column
                flex-sm-row gap-3">

            <!-- Member photo, account type and professional summary -->
            <div
                class="d-flex flex-column
        align-items-center
        flex-shrink-0"
                style="width: 160px;">

                <a
                    href="<?= esc(
                                $profileUrl,
                                'attr'
                            ) ?>"
                    class="text-decoration-none">

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

                </a>

                <?php if (
                    $accountType !== ''
                ): ?>

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

                        <?= esc(
                            $accountType
                        ) ?>

                    </span>

                <?php endif; ?>



            </div>

            <!-- Member details and Interest actions -->
            <div class="flex-grow-1 min-w-0">

                <!-- Member identity and Interest status -->
                <div
                    class="d-flex
                        align-items-start
                        justify-content-between
                        gap-2 mb-2">

                    <div class="min-w-0">

                        <h2
                            class="fs-18
                                fw-semibold mb-1
                                text-truncate">

                            <a
                                href="<?= esc(
                                            $profileUrl,
                                            'attr'
                                        ) ?>"
                                class="text-body
                                    text-decoration-none">

                                <?= esc(
                                    $name
                                ) ?>

                            </a>

                        </h2>

                        <?php if (
                            $reference !== ''
                        ): ?>

                            <p
                                class="text-muted
                                    fs-13 mb-0">

                                <?= esc(
                                    $reference
                                ) ?>

                            </p>

                        <?php endif; ?>

                    </div>

                    <span
                        class="badge <?= esc(
                                            $badgeClass,
                                            'attr'
                                        ) ?>
                            border px-2 py-2
                            flex-shrink-0">

                        <?= esc(
                            $statusLabel
                        ) ?>

                    </span>

                </div>

                <!-- Member summary -->
                <div
                    class="d-flex flex-wrap
                        align-items-center
                        gap-2 text-muted
                        fs-13 mb-2">

                    <?php if (
                        $age !== null
                        && $age > 0
                    ): ?>

                        <span>
                            <?= esc(
                                (string) $age
                            ) ?>
                            yrs
                        </span>

                    <?php endif; ?>

                </div>

                <?php if (
                    $location !== ''
                ): ?>

                    <p
                        class="d-flex
                            align-items-center
                            gap-1 text-muted
                            fs-13 mb-2">

                        <i
                            class="ri-map-pin-line
                                text-primary
                                flex-shrink-0"
                            aria-hidden="true">
                        </i>

                        <span>
                            <?= esc(
                                $location
                            ) ?>
                        </span>

                    </p>

                <?php endif; ?>

                <!-- Interest relationship message -->
                <p class="fs-13 mb-3">

                    <?php if (
                        $isReceived
                    ): ?>

                        <strong>
                            <?= esc(
                                $name
                            ) ?>
                        </strong>

                        sent you an interest

                    <?php else: ?>

                        You sent an interest to

                        <strong>
                            <?= esc(
                                $name
                            ) ?>
                        </strong>

                    <?php endif; ?>

                    <?php if (
                        $interestDate !== ''
                    ): ?>

                        <span class="text-muted">
                            ·
                            <?= esc(
                                $interestDate
                            ) ?>
                        </span>

                    <?php endif; ?>

                </p>

                <!-- Pending received Interests can be actioned here -->
                <?php if (
                    $canRespond
                ): ?>

                    <div
                        class="d-flex
                            flex-wrap gap-2">

                        <form
                            method="post"
                            action="<?= esc(
                                        url_to(
                                            'web.interests.received.decline',
                                            $reference
                                        ),
                                        'attr'
                                    ) ?>"
                            data-interest-action-form>

                            <?= csrf_field() ?>

                            <button
                                type="submit"
                                class="btn btn-sm
                                    btn-outline-secondary
                                    d-inline-flex
                                    align-items-center
                                    justify-content-center
                                    gap-2"
                                data-interest-submit>

                                <span
                                    class="registration-submit__idle">

                                    <i
                                        class="ri-close-line"
                                        aria-hidden="true">
                                    </i>

                                    Decline

                                </span>

                                <span
                                    class="registration-submit__loading
                                        d-none">

                                    <span
                                        class="spinner-border
                                            spinner-border-sm"
                                        role="status"
                                        aria-hidden="true">
                                    </span>

                                    Saving...

                                </span>

                            </button>

                        </form>

                        <form
                            method="post"
                            action="<?= esc(
                                        url_to(
                                            'web.interests.received.accept',
                                            $reference
                                        ),
                                        'attr'
                                    ) ?>"
                            data-interest-action-form>

                            <?= csrf_field() ?>

                            <button
                                type="submit"
                                class="btn btn-danger
                                    btn-sm
                                    d-inline-flex
                                    align-items-center
                                    justify-content-center
                                    gap-2"
                                data-interest-submit>

                                <span
                                    class="registration-submit__idle">

                                    <i
                                        class="ri-heart-fill"
                                        aria-hidden="true">
                                    </i>

                                    Accept Interest

                                </span>

                                <span
                                    class="registration-submit__loading
                                        d-none">

                                    <span
                                        class="spinner-border
                                            spinner-border-sm"
                                        role="status"
                                        aria-hidden="true">
                                    </span>

                                    Saving...

                                </span>

                            </button>

                        </form>

                    </div>

                <?php else: ?>

                    <?php if (
                        $canViewFullProfile
                        && $profileUrl !== ''
                    ): ?>

                        <a
                            href="<?= esc(
                                        $profileUrl,
                                        'attr'
                                    ) ?>"
                            class="btn btn-sm
            btn-outline-primary
            d-inline-flex
            align-items-center
            justify-content-center
            gap-2">

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
                            class="btn btn-sm
            btn-outline-primary
            d-inline-flex
            align-items-center
            justify-content-center
            gap-2">

                            <i
                                class="ri-lock-2-line"
                                aria-hidden="true">
                            </i>

                            View Profile — Upgrade

                        </a>

                    <?php endif; ?>

                <?php endif; ?>

            </div>

        </div>
        <?php if (
            $professionalSummary !== ''
        ): ?>



            <div class="d-flex align-items-center mt-3">
                <div class="flex-shrink-0 me-1">
                    <div class="avatar-xs flex-shrink-0 me-1">
                        <span class="avatar-title bg-dark-subtle rounded-circle shadow">
                            <i class="ri-briefcase-4-line fs-16 align-middle text-primary"></i>
                        </span>
                    </div>

                </div>
                <div class="flex-grow-1">
                    <h5 class="fs-13 mb-0 fw-semibold"><?= esc(
                                                            $professionalSummary
                                                        ) ?>
                    </h5>
                </div>
            </div>


        <?php endif; ?>
    </div>

    <!-- Full-width shared verification strip -->
    <?= view(
        'Components/Member/VerificationBadges',
        [
            'verification' =>
            $verification,
        ]
    ) ?>

</article>