<?php

declare(strict_types=1);

/**
 * Compact administrator Match card.
 *
 * Presentation deliberately follows the normal ProfileCard layout while
 * keeping administrator-only actions.
 *
 * @var array<string, mixed> $profile
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

$professionalSummary = trim(
    (string) (
        $profile['professionalSummary']
        ?? ''
    )
);

$accountTypeCode = mb_strtoupper(
    trim(
        (string) (
            $profile['accountCode']
            ?? ''
        )
    )
);

$verification =
    isset($profile['verification'])
    && is_array(
        $profile['verification']
    )
    ? $profile['verification']
    : [];

$partnerPreferencePercentage =
    max(
        0,
        min(
            100,
            (int) round(
                (float) (
                    $profile['partnerPreferencePercentage']
                    ?? 0
                )
            )
        )
    );

$matchScore =
    max(
        0.0,
        min(
            100.0,
            (float) (
                $profile['matchScore']
                ?? 0
            )
        )
    );

$safeReference =
    preg_replace(
        '/[^A-Za-z0-9_-]/',
        '',
        $reference
    ) ?? '';

$matchModalId =
    'adminMatchScore'
    . $safeReference;

$preferenceModalId =
    'adminPartnerPreference'
    . $safeReference;
?>

<article
    class="card h-100 border border-danger
        border-opacity-25 shadow-sm
        overflow-hidden">

    <div class="card-body p-3">

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

                <div
                    class="mt-1
                        d-flex
                        justify-content-center">

                    <?= view(
                        'Components/Membership/PlanLogo',
                        [
                            'planCode' =>
                            $accountTypeCode,

                            'width' =>
                            180,
                        ]
                    ) ?>

                </div>

            </div>

            <div class="flex-grow-1 min-w-0">

                <div class="mb-2">

                    <h3
                        class="fs-18
                            fw-semibold
                            mb-1
                            text-truncate">

                        <?= esc($name) ?>

                    </h3>

                    <?php if ($reference !== ''): ?>

                        <div
                            class="text-muted fs-13">

                            <?= esc($reference) ?>

                        </div>

                    <?php endif; ?>

                </div>

                <?php if ($activity !== ''): ?>

                    <div
                        class="d-flex
                            align-items-center
                            gap-1 fs-12
                            text-success mb-2">

                        <i
                            class="ri-checkbox-blank-circle-fill"
                            aria-hidden="true"></i>

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

                        <span aria-hidden="true">
                            ·
                        </span>

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
                            aria-hidden="true"></i>

                        <?= esc($location) ?>

                    </p>

                <?php endif; ?>

                <?php if (
                    $maritalStatus !== ''
                ): ?>

                    <p class="fs-13 mb-2">

                        <?= esc(
                            $maritalStatus
                        ) ?>

                    </p>

                <?php endif; ?>

                <div
                    class="d-flex
                        flex-wrap
                        align-items-center
                        gap-2">

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
                            aria-hidden="true"></i>

                        View Profile

                    </a>

                    <button
                        type="button"
                        class="btn
                            btn-outline-primary
                            btn-sm
                            d-inline-flex
                            align-items-center
                            gap-1"
                        data-bs-toggle="modal"
                        data-bs-target="#<?= esc(
                                                $preferenceModalId,
                                                'attr'
                                            ) ?>">

                        <i
                            class="ri-list-check-3"
                            aria-hidden="true"></i>

                        Partner Preference

                    </button>

                    <button
                        type="button"
                        class="btn
                            btn-danger
                            btn-sm
                            d-inline-flex
                            align-items-center
                            gap-1"
                        data-bs-toggle="modal"
                        data-bs-target="#<?= esc(
                                                $matchModalId,
                                                'attr'
                                            ) ?>">

                        <i
                            class="ri-calculator-line"
                            aria-hidden="true"></i>

                        Match

                    </button>

                </div>

            </div>

        </div>

        <?php if (
            $professionalSummary !== ''
        ): ?>

            <div
                class="d-flex
            align-items-center
            mt-3">

                <div
                    class="avatar-xs
                flex-shrink-0
                me-2">

                    <span
                        class="avatar-title
                    bg-dark-subtle
                    rounded-circle
                    shadow">

                        <i
                            class="ri-briefcase-4-line
                        fs-16
                        text-primary"
                            aria-hidden="true"></i>

                    </span>

                </div>

                <div class="flex-grow-1">

                    <h5
                        class="fs-13
                    mb-0
                    fw-semibold">

                        <?= esc(
                            $professionalSummary
                        ) ?>

                    </h5>

                </div>

            </div>

        <?php endif; ?>


        <!-- Admin-only Match scores -->
        <div
            class="d-flex
        align-items-center
        flex-wrap
        gap-3
        mt-3
        pt-3
        border-top">

            <div
                class="d-flex
            align-items-center
            gap-2">

                <span
                    class="avatar-xs
                flex-shrink-0">

                    <span
                        class="avatar-title
                    bg-primary-subtle
                    text-primary
                    rounded-circle">

                        <i
                            class="ri-calculator-line"
                            aria-hidden="true"></i>

                    </span>

                </span>

                <div>

                    <div
                        class="text-muted
                    fs-11">

                        Match Score

                    </div>

                    <div
                        class="fw-semibold
                    fs-16
                    lh-1">

                        <?= esc(
                            (string) $matchScore
                        ) ?>%

                    </div>

                </div>

            </div>


            <div
                class="d-flex
            align-items-center
            gap-2">

                <span
                    class="avatar-xs
                flex-shrink-0">

                    <span
                        class="avatar-title
                    bg-success-subtle
                    text-success
                    rounded-circle">

                        <i
                            class="ri-list-check-3"
                            aria-hidden="true"></i>

                    </span>

                </span>

                <div>

                    <div
                        class="text-muted
                    fs-11">

                        Partner Preference

                    </div>

                    <div
                        class="fw-semibold
                    fs-16
                    lh-1">

                        <?= esc(
                            (string)
                            $partnerPreferencePercentage
                        ) ?>%

                    </div>

                </div>

            </div>           

        </div>

    </div>

    <?= view(
        'Components/Member/VerificationBadges',
        [
            'verification' =>
            $verification,
        ]
    ) ?>

</article>

<?= view(
    'Admin/Members/Partials/MatchScoreModal',
    [
        'modalId' =>
        $matchModalId,

        'profile' =>
        $profile,
    ]
) ?>

<?= view(
    'Admin/Members/Partials/PartnerPreferenceModal',
    [
        'modalId' =>
        $preferenceModalId,

        'profile' =>
        $profile,
    ]
) ?>