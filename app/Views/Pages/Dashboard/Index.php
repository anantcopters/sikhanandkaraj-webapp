<?php

declare(strict_types=1);

/**
 * Member Dashboard
 *
 * Authentication and contact variables.
 *
 * @var string|null $profileReference
 * @var string|null $loggedInUserName
 * @var string|null $primaryMobile
 * @var bool        $isMobileVerified
 *
 * Dashboard-specific variables.
 *
 * @var array<string, string>            $accountPlan
 * @var int                              $minimumMatchPercentage
 * @var int                              $newMatchDays
 * @var list<array<string, mixed>>       $allMatches
 * @var list<array<string, mixed>>       $newMatches
 * @var list<array<string, mixed>>       $interestReceived
 * @var list<array<string, mixed>>       $interestSent
 * @var list<array<string, mixed>>       $profileVisitors
 * @var list<array<string, mixed>>       $profilesViewed
 *
 * Shared profile-summary variables.
 *
 * @var string                            $profileImage
 * @var array<string, mixed>              $profileCompletion
 * @var array<string, mixed>              $overallProfileSummary
 * @var array<int, array<string, mixed>>  $profileShortcuts
 * @var array<string, string>|null        $nextProfileSection
 */

$this->extend('Layouts/Main');
$this->section('content');

/*
 * Normalise primitive values before using them in the view.
 */
$resolvedName = trim(
    (string) ($loggedInUserName ?? '')
);

if ($resolvedName === '') {
    $resolvedName = 'Member';
}

$resolvedReference = trim(
    (string) ($profileReference ?? '')
);

$resolvedProfileImage = trim(
    (string) ($profileImage ?? '')
);

$resolvedPlanName = trim(
    (string) ($accountPlan['name'] ?? 'Free account')
);

$completionPercentage = max(
    0,
    min(
        100,
        (int) ($profileCompletion['percentage'] ?? 0)
    )
);

$completedSteps = max(
    0,
    (int) ($profileCompletion['completedSteps'] ?? 0)
);

$totalSteps = max(
    0,
    (int) ($profileCompletion['totalSteps'] ?? 0)
);

/*
 * Combine the separate service datasets into presentation sections.
 *
 * The service remains responsible for retrieving records. The view only
 * controls labels, descriptions and display ordering.
 */
$matchSections = [
    [
        'key' => 'all-matches',

        'title' => 'All Matches',

        'description' =>
        'Profiles matching at least '
            . (int) ($minimumMatchPercentage ?? 30)
            . '% of the partner preferences you have set.',

        'profiles' =>
        $allMatches ?? [],

        'emptyMessage' =>
        'No matching profiles are available yet.',
    ],

    [
        'key' => 'new-matches',

        'title' => 'New Matches',

        'description' =>
        'Preference-matched members who joined within the last '
            . (int) ($newMatchDays ?? 30)
            . ' days.',

        'profiles' =>
        $newMatches ?? [],

        'emptyMessage' =>
        'No new matches are available yet.',
    ],

    [
        'key' => 'interest-received',

        'title' => 'Interested in You',

        'description' =>
        'Members who have shown interest in your profile.',

        'profiles' =>
        $interestReceived ?? [],

        'emptyMessage' =>
        'No member has shown interest in your profile yet.',
    ],

    [
        'key' => 'interest-sent',

        'title' => 'Interests Sent',

        'description' =>
        'Members you have shown interest in.',

        'profiles' =>
        $interestSent ?? [],

        'emptyMessage' =>
        'You have not shown interest in any member yet.',
    ],

    [
        'key' => 'profile-visitors',

        'title' => 'Who Viewed Your Profile',

        'description' =>
        'Members who have viewed your profile.',

        'profiles' =>
        $profileVisitors ?? [],

        'emptyMessage' =>
        'Your profile has not been viewed yet.',
    ],

    [
        'key' => 'profiles-viewed',

        'title' => 'Profiles You Viewed',

        'description' =>
        'Profiles you have viewed recently.',

        'profiles' =>
        $profilesViewed ?? [],

        'emptyMessage' =>
        'You have not viewed another profile yet.',
    ],
];
?>

<section class="py-3 py-lg-3">
    <div class="container">

        <div class="row g-4">
            <aside class="col-12 col-lg-4 col-xl-3">
                <div class="dashboard-sidebar">
                    <div class="card border border-danger border-opacity-25 shadow-sm">
                        <div class="card-body p-4 pb-1 text-center">

                            <?php if ($resolvedProfileImage !== ''): ?>
                                <div class="admin-member-profile-photo mx-auto mb-3">
                                    <img
                                        src="<?= esc(
                                                    $resolvedProfileImage,
                                                    'attr'
                                                ) ?>"
                                        alt="<?= esc(
                                                    $resolvedName . ' profile photo',
                                                    'attr'
                                                ) ?>">
                                </div>
                            <?php else: ?>
                                <div
                                    class="admin-member-profile-photo
                admin-member-profile-photo--fallback mx-auto mb-3"
                                    aria-label="<?= esc(
                                                    $resolvedName,
                                                    'attr'
                                                ) ?>">

                                    <span>
                                        <?= esc(
                                            mb_strtoupper(
                                                mb_substr(
                                                    $resolvedName,
                                                    0,
                                                    1
                                                )
                                            )
                                        ) ?>
                                    </span>

                                </div>
                            <?php endif; ?>

                            <h2 class="fs-18 fw-semibold mb-1">
                                <?= esc($resolvedName) ?>
                            </h2>

                            <?php if ($resolvedReference !== ''): ?>
                                <p class="text-muted fs-13 mb-1">
                                    Reference:
                                    <strong>
                                        <?= esc($resolvedReference) ?>
                                    </strong>
                                </p>
                            <?php endif; ?>

                            <p class="text-primary fs-12 mb-4">
                                <?= esc($resolvedPlanName) ?>
                            </p>

                            <div class="border-top pt-3 text-start">
                                <div
                                    class="d-flex align-items-center justify-content-between gap-3 mb-2">

                                    <span
                                        class="d-flex align-items-center gap-2">

                                        <i
                                            class="ri-smartphone-line fs-18"
                                            aria-hidden="true">
                                        </i>

                                        <span><?php if (
                                                    is_string($primaryMobile)
                                                    && $primaryMobile !== ''
                                                ): ?>
                                                <p class="mb-0 fw-medium">
                                                    <?= esc($primaryMobile) ?>
                                                </p>
                                            <?php endif; ?>
                                        </span>
                                    </span>

                                    <?php if ($isMobileVerified): ?>
                                        <span
                                            class="badge bg-success-subtle text-body p-2">

                                            Verified
                                        </span>
                                    <?php else: ?>
                                        <span
                                            class="badge bg-warning-subtle text-body p-2">

                                            Pending
                                        </span>
                                    <?php endif; ?>
                                </div>


                            </div>
                        </div>

                        <nav
                            class="list-group list-group-flush"
                            aria-label="Member account">

                            <a
                                href="<?= url_to('web.profile.edit') ?>"
                                class="list-group-item list-group-item-action d-flex align-items-center gap-2 py-3">

                                <i
                                    class="ri-user-settings-line fs-18"
                                    aria-hidden="true">
                                </i>

                                <span>Edit Profile</span>
                            </a>

                            <a
                                href="<?= url_to('web.partner-preference') ?>"
                                class="list-group-item list-group-item-action d-flex align-items-center gap-2 py-3">

                                <i
                                    class="ri-equalizer-line fs-18"
                                    aria-hidden="true">
                                </i>

                                <span>Edit Preferences</span>
                            </a>

                            <a
                                href="<?= url_to(
                                            'web.account.settings'
                                        ) ?>"
                                class="list-group-item list-group-item-action d-flex align-items-center gap-2 py-3">

                                <i
                                    class="ri-settings-3-line fs-18"
                                    aria-hidden="true">
                                </i>

                                <span>Account Settings</span>
                            </a>
                        </nav>
                    </div>
                </div>
            </aside>

            <div class="col-12 col-lg-8 col-xl-9">

                <?php
                /*
 * Resolve visibility presentation once for both incomplete and
 * complete profile states.
 */
                $visibilityLabel = '';
                $visibilityClass = 'danger';

                if (
                    isset($overallProfileSummary)
                    && is_array($overallProfileSummary)
                ) {
                    $visibilityLabel = trim(
                        (string) (
                            $overallProfileSummary['visibilityLabel']
                            ?? ''
                        )
                    );

                    $visibilityClass = trim(
                        (string) (
                            $overallProfileSummary['visibilityClass']
                            ?? 'danger'
                        )
                    );

                    $supportedVisibilityClasses = [
                        'success',
                        'warning',
                        'danger',
                    ];

                    if (
                        !in_array(
                            $visibilityClass,
                            $supportedVisibilityClasses,
                            true
                        )
                    ) {
                        $visibilityClass = 'danger';
                    }
                }

                $isProfileComplete =
                    $completionPercentage >= 100;
                ?>

                <section
                    class="card border border-danger border-opacity-25 shadow-sm mb-4">

                    <div class="card-body p-4">

                        <?php if ($isProfileComplete): ?>

                            <div class="dashboard-profile-complete">

                                <div
                                    class="d-flex flex-column flex-lg-row
                align-items-lg-center
                justify-content-between
                gap-4">

                                    <div
                                        class="d-flex flex-column flex-sm-row
                    align-items-sm-center
                    gap-4">

                                        <!--
                 * Reuse the existing Profile Edit completion circle.
                 * This keeps completion presentation consistent
                 * throughout the member application.
                 -->
                                        <div
                                            class="profile-completion-circle"
                                            style="--profile-progress:
                        <?= esc(
                                (string) $completionPercentage,
                                'attr'
                            ) ?>;"
                                            role="img"
                                            aria-label="<?= esc(
                                                            $completionPercentage
                                                                . '% profile completed',
                                                            'attr'
                                                        ) ?>">

                                            <div
                                                class="profile-completion-circle__value">

                                                <strong>
                                                    <?= esc(
                                                        (string)
                                                        $completionPercentage
                                                    ) ?>%
                                                </strong>

                                                <span>
                                                    Complete
                                                </span>

                                            </div>
                                        </div>

                                        <div
                                            class="dashboard-profile-complete__content">

                                            <div
                                                class="d-flex
                            align-items-center
                            gap-2 mb-1">

                                                <span
                                                    class="text-success fs-20
                                d-inline-flex
                                align-items-center"
                                                    aria-hidden="true">

                                                    <i
                                                        class="ri-checkbox-circle-fill">
                                                    </i>
                                                </span>

                                                <h2
                                                    class="fs-18 fw-semibold mb-0">

                                                    Profile Complete
                                                </h2>
                                            </div>

                                            <p
                                                class="text-muted fs-13 mb-3">

                                                Your profile is ready
                                                for matchmaking.
                                            </p>

                                            <?php if (
                                                $visibilityLabel !== ''
                                            ): ?>

                                                <span
                                                    class="badge
                                bg-<?= esc(
                                                    $visibilityClass,
                                                    'attr'
                                                ) ?>-subtle
                                text-body
                                p-2 fw-medium">

                                                    <i
                                                        class="ri-eye-line me-1"
                                                        aria-hidden="true">
                                                    </i>

                                                    <?= esc(
                                                        $visibilityLabel
                                                    ) ?>
                                                    visibility
                                                </span>

                                            <?php endif; ?>

                                        </div>
                                    </div>

                                    <div
                                        class="dashboard-profile-complete__actions
                    d-flex flex-column flex-sm-row
                    gap-2 flex-shrink-0">

                                        <a
                                            href="<?= url_to(
                                                        'web.profile.view'
                                                    ) ?>"
                                            class="btn btn-outline-primary
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

                                        <a
                                            href="<?= url_to(
                                                        'web.profile.edit'
                                                    ) ?>"
                                            class="btn btn-outline-danger
                        d-inline-flex
                        align-items-center
                        justify-content-center
                        gap-2">

                                            <i
                                                class="ri-edit-line"
                                                aria-hidden="true">
                                            </i>

                                            Edit Profile
                                        </a>

                                    </div>
                                </div>
                            </div>

                        <?php else: ?>

                            <!-- =========================================================
                 Incomplete profile
                 ========================================================= -->

                            <div
                                class="d-flex flex-column flex-md-row
                    align-items-md-center
                    justify-content-between
                    gap-3 mb-3">

                                <div>
                                    <h2
                                        class="fs-18 fw-semibold mb-1">

                                        Complete Your Profile
                                    </h2>

                                    <p
                                        class="text-muted fs-13 mb-0">

                                        A complete profile improves your
                                        match quality and profile visibility.
                                    </p>
                                </div>

                                <div
                                    class="d-flex align-items-center
                        gap-2 flex-shrink-0">

                                    <?php if (
                                        $visibilityLabel !== ''
                                    ): ?>

                                        <span
                                            class="badge
                                bg-<?= esc(
                                            $visibilityClass,
                                            'attr'
                                        ) ?>-subtle
                                text-body
                                p-2">

                                            <?= esc(
                                                $visibilityLabel
                                            ) ?>
                                            visibility
                                        </span>

                                    <?php endif; ?>

                                    <strong
                                        class="fs-18 text-nowrap">

                                        <?= esc(
                                            (string)
                                            $completionPercentage
                                        ) ?>%
                                    </strong>

                                </div>
                            </div>

                            <!-- Existing linear completion bar remains only
                 while profile is incomplete. -->
                            <div
                                class="progress mb-2"
                                role="progressbar"
                                aria-label="Profile completion"
                                aria-valuenow="<?= esc(
                                                    (string)
                                                    $completionPercentage,
                                                    'attr'
                                                ) ?>"
                                aria-valuemin="0"
                                aria-valuemax="100">

                                <div
                                    class="progress-bar"
                                    style="<?= esc(
                                                'width: '
                                                    . $completionPercentage
                                                    . '%;',
                                                'attr'
                                            ) ?>">
                                </div>
                            </div>

                            <?php if ($totalSteps > 0): ?>

                                <p
                                    class="text-muted fs-12 mb-4">

                                    <?= esc(
                                        (string)
                                        $completedSteps
                                    ) ?>
                                    of
                                    <?= esc(
                                        (string)
                                        $totalSteps
                                    ) ?>
                                    profile steps completed
                                </p>

                            <?php else: ?>

                                <div class="mb-4"></div>

                            <?php endif; ?>

                            <?php if (
                                isset($profileShortcuts)
                                && is_array($profileShortcuts)
                                && $profileShortcuts !== []
                            ): ?>

                                <div class="row g-3">

                                    <?php foreach (
                                        $profileShortcuts
                                        as $shortcut
                                    ): ?>

                                        <?php
                                        $shortcutComplete = (
                                            $shortcut['isComplete']
                                            ?? false
                                        ) === true;

                                        /*
                         * Completed shortcuts do not need to be
                         * shown in the dashboard completion journey.
                         */
                                        if ($shortcutComplete) {
                                            continue;
                                        }

                                        $shortcutTitle = trim(
                                            (string) (
                                                $shortcut['title']
                                                ?? 'Complete profile'
                                            )
                                        );

                                        $shortcutDescription = trim(
                                            (string) (
                                                $shortcut['description']
                                                ?? ''
                                            )
                                        );

                                        $shortcutIcon = trim(
                                            (string) (
                                                $shortcut['icon']
                                                ?? 'ri-edit-line'
                                            )
                                        );

                                        $shortcutUrl = trim(
                                            (string) (
                                                $shortcut['url']
                                                ?? '#'
                                            )
                                        );

                                        if ($shortcutUrl === '') {
                                            $shortcutUrl = '#';
                                        }

                                        $shortcutClass = trim(
                                            (string) (
                                                $shortcut['class']
                                                ?? ''
                                            )
                                        );

                                        $shortcutPercentage = max(
                                            0,
                                            min(
                                                100,
                                                (int) (
                                                    $shortcut['percentage']
                                                    ?? 0
                                                )
                                            )
                                        );

                                        $shortcutStatusLabel = trim(
                                            (string) (
                                                $shortcut['statusLabel']
                                                ?? 'Pending'
                                            )
                                        );
                                        ?>

                                        <div
                                            class="col-12 col-md-6 col-xl-4">

                                            <a
                                                href="<?= esc(
                                                            $shortcutUrl,
                                                            'attr'
                                                        ) ?>"
                                                class="card h-90
                                    border
                                    border-danger
                                    border-opacity-25
                                    text-decoration-none
                                    mb-2
                                    <?= esc(
                                            $shortcutClass,
                                            'attr'
                                        ) ?>">

                                                <div class="card-body p-3">

                                                    <div
                                                        class="d-flex
                                            align-items-start
                                            gap-3">

                                                        <span
                                                            class="dashboard-shortcut-icon
                                                bg-light
                                                rounded-circle
                                                flex-shrink-0">

                                                            <i
                                                                class="<?= esc(
                                                                            $shortcutIcon,
                                                                            'attr'
                                                                        ) ?>"
                                                                aria-hidden="true">
                                                            </i>
                                                        </span>

                                                        <span
                                                            class="flex-grow-1">

                                                            <span
                                                                class="d-flex
                                                    align-items-start
                                                    justify-content-between
                                                    gap-2 mb-1">

                                                                <span
                                                                    class="fs-14
                                                        fw-semibold
                                                        text-body">

                                                                    <?= esc(
                                                                        $shortcutTitle
                                                                    ) ?>
                                                                </span>

                                                                <span
                                                                    class="badge
                                                        bg-warning
                                                        text-body
                                                        flex-shrink-0">

                                                                    <?= esc(
                                                                        $shortcutStatusLabel
                                                                    ) ?>
                                                                </span>

                                                            </span>

                                                            <?php if (
                                                                $shortcutDescription
                                                                !== ''
                                                            ): ?>

                                                                <span
                                                                    class="d-block
                                                        text-muted
                                                        fs-12 mb-2">

                                                                    <?= esc(
                                                                        $shortcutDescription
                                                                    ) ?>
                                                                </span>

                                                            <?php endif; ?>

                                                            <span
                                                                class="text-body
                                                    fs-13
                                                    fw-medium">

                                                                <?= esc(
                                                                    (string)
                                                                    $shortcutPercentage
                                                                ) ?>%
                                                            </span>

                                                        </span>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>

                                    <?php endforeach; ?>

                                </div>

                            <?php endif; ?>

                            <div
                                class="d-flex flex-column
                    flex-sm-row
                    justify-content-end
                    gap-2 mt-0">

                                <?php if (
                                    isset($nextProfileSection)
                                    && is_array(
                                        $nextProfileSection
                                    )
                                    && trim(
                                        (string) (
                                            $nextProfileSection['route']
                                            ?? ''
                                        )
                                    ) !== ''
                                ): ?>

                                    <a
                                        href="<?= url_to(
                                                    (string)
                                                    $nextProfileSection['route']
                                                ) ?>"
                                        class="btn btn-primary">

                                        Continue with

                                        <?= esc(
                                            (string) (
                                                $nextProfileSection['title']
                                                ?? 'Profile'
                                            )
                                        ) ?>

                                        <i
                                            class="ri-arrow-right-line ms-1"
                                            aria-hidden="true">
                                        </i>
                                    </a>

                                <?php endif; ?>

                                <a
                                    href="<?= url_to(
                                                'web.profile.view'
                                            ) ?>"
                                    class="btn btn-outline-primary
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

                            </div>

                        <?php endif; ?>

                    </div>
                </section>

                <?php foreach ($matchSections as $section): ?>
                    <?php
                    $sectionProfiles = isset(
                        $section['profiles']
                    )
                        && is_array(
                            $section['profiles']
                        )
                        ? $section['profiles']
                        : [];

                    $sectionKey = preg_replace(
                        '/[^a-z0-9\-]/',
                        '',
                        strtolower(
                            (string) (
                                $section['key']
                                ?? 'profiles'
                            )
                        )
                    ) ?? 'profiles';
                    ?>

                    <section
                        class="card border border-danger
            border-opacity-25 shadow-sm mb-4">

                        <div class="card-body p-4">

                            <div
                                class="d-flex
                    align-items-start
                    justify-content-between
                    gap-3 mb-3">

                                <div>
                                    <div
                                        class="d-flex
                            align-items-center
                            flex-wrap gap-2 mb-1">

                                        <h2
                                            class="fs-18
                                fw-semibold mb-0">

                                            <?= esc(
                                                $section['title']
                                            ) ?>
                                        </h2>

                                        <span
                                            class="badge
                                bg-primary-subtle
                                text-primary">

                                            <?= esc(
                                                (string)
                                                count(
                                                    $sectionProfiles
                                                )
                                            ) ?>
                                        </span>
                                    </div>

                                    <p
                                        class="text-muted
                            fs-13 mb-0">

                                        <?= esc(
                                            $section['description']
                                        ) ?>
                                    </p>
                                </div>

                                <?php if (
                                    count($sectionProfiles)
                                    > 1
                                ): ?>

                                    <div
                                        class="d-flex
                            align-items-center
                            gap-1 flex-shrink-0">

                                        <button
                                            type="button"
                                            class="btn btn-light
                                btn-sm btn-icon"
                                            aria-label="Previous profiles"
                                            data-profile-scroll-previous
                                            data-profile-scroll-target="<?= esc(
                                                                            $sectionKey,
                                                                            'attr'
                                                                        ) ?>">

                                            <i
                                                class="ri-arrow-left-s-line"
                                                aria-hidden="true">
                                            </i>
                                        </button>

                                        <button
                                            type="button"
                                            class="btn btn-light
                                btn-sm btn-icon"
                                            aria-label="Next profiles"
                                            data-profile-scroll-next
                                            data-profile-scroll-target="<?= esc(
                                                                            $sectionKey,
                                                                            'attr'
                                                                        ) ?>">

                                            <i
                                                class="ri-arrow-right-s-line"
                                                aria-hidden="true">
                                            </i>
                                        </button>
                                    </div>

                                <?php endif; ?>
                            </div>

                            <?php if (
                                $sectionProfiles !== []
                            ): ?>

                                <div
                                    class="dashboard-profile-scroll pb-2"
                                    data-profile-scroll="<?= esc(
                                                                $sectionKey,
                                                                'attr'
                                                            ) ?>">

                                    <?php foreach (
                                        $sectionProfiles
                                        as $profile
                                    ): ?>

                                        <?php
                                        $profileName = trim(
                                            (string) (
                                                $profile['name']
                                                ?? 'Member'
                                            )
                                        );

                                        $profileAge = isset(
                                            $profile['age']
                                        )
                                            && is_numeric(
                                                $profile['age']
                                            )
                                            ? (int) $profile['age']
                                            : null;

                                        $profileCity = trim(
                                            (string) (
                                                $profile['city']
                                                ?? ''
                                            )
                                        );

                                        $profilePhoto = trim(
                                            (string) (
                                                $profile['image']
                                                ?? ''
                                            )
                                        );

                                        $profileUrl = trim(
                                            (string) (
                                                $profile['profileUrl']
                                                ?? '#'
                                            )
                                        );

                                        $matchPercentage =
                                            isset(
                                                $profile['matchPercentage']
                                            )
                                            && is_numeric(
                                                $profile['matchPercentage']
                                            )
                                            ? (int) $profile['matchPercentage']
                                            : null;
                                        ?>

                                        <article
                                            class="card
                                dashboard-profile-card
                                border
                                border-danger
                                border-opacity-25">

                                            <div
                                                class="card-body p-3">

                                                <a
                                                    href="<?= esc(
                                                                $profileUrl,
                                                                'attr'
                                                            ) ?>"
                                                    class="d-block
                                        text-decoration-none">

                                                    <?php if (
                                                        $profilePhoto !== ''
                                                    ): ?>

                                                        <div
                                                            class="admin-member-profile-photo
                                                mx-auto mb-3">

                                                            <img
                                                                src="<?= esc(
                                                                            $profilePhoto,
                                                                            'attr'
                                                                        ) ?>"
                                                                alt="<?= esc(
                                                                            $profileName
                                                                                . ' profile photo',
                                                                            'attr'
                                                                        ) ?>"
                                                                loading="lazy">
                                                        </div>

                                                    <?php else: ?>

                                                        <div
                                                            class="admin-member-profile-photo
                                                admin-member-profile-photo--fallback
                                                mx-auto mb-3"
                                                            aria-label="<?= esc(
                                                                            $profileName,
                                                                            'attr'
                                                                        ) ?>">

                                                            <span>
                                                                <?= esc(
                                                                    mb_strtoupper(
                                                                        mb_substr(
                                                                            $profileName,
                                                                            0,
                                                                            1
                                                                        )
                                                                    )
                                                                ) ?>
                                                            </span>
                                                        </div>

                                                    <?php endif; ?>

                                                    <h3
                                                        class="fs-14
                                            fw-semibold
                                            text-body
                                            text-center
                                            text-truncate
                                            mb-1">

                                                        <?= esc(
                                                            $profileName
                                                        ) ?>
                                                    </h3>

                                                    <p
                                                        class="text-muted
                                            fs-12
                                            text-center
                                            mb-1">

                                                        <?php if (
                                                            $profileAge
                                                            !== null
                                                        ): ?>

                                                            <?= esc(
                                                                (string)
                                                                $profileAge
                                                            ) ?>
                                                            years

                                                        <?php endif; ?>

                                                        <?php if (
                                                            $profileAge
                                                            !== null
                                                            && $profileCity
                                                            !== ''
                                                        ): ?>

                                                            <span
                                                                aria-hidden="true">
                                                                •
                                                            </span>

                                                        <?php endif; ?>

                                                        <?php if (
                                                            $profileCity
                                                            !== ''
                                                        ): ?>

                                                            <?= esc(
                                                                $profileCity
                                                            ) ?>

                                                        <?php endif; ?>
                                                    </p>

                                                    <?php if (
                                                        $matchPercentage
                                                        !== null
                                                    ): ?>

                                                        <p
                                                            class="text-success
                                                fs-12
                                                fw-medium
                                                text-center
                                                mb-0">

                                                            <?= esc(
                                                                (string)
                                                                $matchPercentage
                                                            ) ?>%
                                                            preference match
                                                        </p>

                                                    <?php endif; ?>
                                                </a>
                                            </div>
                                        </article>

                                    <?php endforeach; ?>

                                </div>

                            <?php else: ?>

                                <div
                                    class="text-center py-4">

                                    <i
                                        class="ri-user-search-line
                            fs-32 text-muted"
                                        aria-hidden="true">
                                    </i>

                                    <p
                                        class="text-muted
                            mb-0 mt-2">

                                        <?= esc(
                                            $section['emptyMessage']
                                        ) ?>
                                    </p>
                                </div>

                            <?php endif; ?>

                        </div>
                    </section>

                <?php endforeach; ?>
            </div>
        </div>

        <!-- Required by assets/js/pages/dashboard-security.js. -->
        <form
            method="post"
            action="<?= url_to('web.logout') ?>"
            id="dashboardLogoutForm"
            class="d-none">

            <?= csrf_field() ?>
        </form>
    </div>
</section>

<?php $this->endSection(); ?>