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
 * @var string|null $primaryEmail
 * @var bool        $isEmailVerified
 * @var string      $aadhaarStatus
 * @var string      $aadhaarRejectionReason
 * @var array<string, string> $aadhaarValidationErrors
 * @var bool        $openAadhaarModal
 * @var array<string, string>|null $formAlert
 * @var bool        $isSelfieVerified
 *
 * Dashboard-specific variables.
 *
 * @var array<string, string>            $accountPlan
 * @var int                              $minimumMatchPercentage
 * @var int                              $newMatchDays
 * @var list<array<string, mixed>>       $allMatches
 * @var list<array<string, mixed>>       $newMatches
 * @var list<array<string, mixed>>       $profilesShortlistedByYou
 * @var list<array<string, mixed>>       $whoShortlistedYou
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
 * @var array<string, mixed>             $partnerPreferenceSetup
 */

$this->extend('Layouts/Main');
$this->section('content');

helper(
    'member_profile'
);

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

$resolvedGender =
    trim(
        (string) (
            $gender
            ?? ''
        )
    );

$resolvedProfileImage =
    trim(
        (string) $resolvedProfileImage
    );

if ($resolvedProfileImage === '') {
    $resolvedProfileImage =
        member_profile_placeholder(
            $resolvedGender
        );
}

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
 * Partner Preference setup information comes from the matchmaking
 * algorithm itself.
 */
$resolvedPreferenceSetup =
    isset($partnerPreferenceSetup)
    && is_array($partnerPreferenceSetup)
    ? $partnerPreferenceSetup
    : [];

$preferencesConfigured = max(
    0,
    (int) (
        $resolvedPreferenceSetup['configured']
        ?? 0
    )
);

$preferencesAvailable = max(
    0,
    (int) (
        $resolvedPreferenceSetup['available']
        ?? 0
    )
);

$preferenceSetupPercentage = max(
    0,
    min(
        100,
        (int) (
            $resolvedPreferenceSetup['percentage']
            ?? 0
        )
    )
);

$preferencesComplete =
    $preferencesAvailable > 0
    && $preferencesConfigured
    >= $preferencesAvailable;

$preferenceBadgeClass =
    $preferencesComplete
    ? 'bg-success-subtle text-success'
    : 'bg-primary-subtle text-primary';

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

/*
 * Existing Match/Search result destinations.
 *
 * Dashboard sections only provide presentation links. Search and Match
 * services remain authoritative for retrieving and filtering profiles.
 */
$searchResultsUrl = route_to(
    'web.search.results'
);

$matchSections = [
    [
        'key' =>
        'all-matches',

        'title' =>
        'All Matches',

        'description' =>
        'Profiles matching at least '
            . (int) (
                $minimumMatchPercentage
                ?? 30
            )
            . '% of the partner preferences you have set.',

        'profiles' =>
        $allMatches ?? [],

        'emptyMessage' =>
        'No matching profiles are available yet.',

        /*
         * The existing Matches route redirects to the complete All Matches
         * collection through the shared Search Results pipeline.
         */
        'viewAllUrl' =>
        route_to(
            'web.matches'
        ),
    ],

    [
        'key' =>
        'new-matches',

        'title' =>
        'New Matches',

        'description' =>
        'Preference-matched members who joined within the last '
            . (int) (
                $newMatchDays
                ?? 30
            )
            . ' days.',

        'profiles' =>
        $newMatches ?? [],

        'emptyMessage' =>
        'No new matches are available yet.',

        /*
         * MemberSearchService resolves new-profiles from the existing
         * dashboardCollections()['newMatches'] collection.
         */
        'viewAllUrl' =>
        $searchResultsUrl
            . '?'
            . http_build_query([
                'activity' =>
                'new-profiles',
            ]),
    ],

    [
        'key' =>
        'profiles-shortlisted-by-you',

        'title' =>
        'Profiles Shortlisted By You',

        'description' =>
        'Profiles you have saved to your shortlist.',

        'profiles' =>
        $profilesShortlistedByYou ?? [],

        'emptyMessage' =>
        'You have not shortlisted any profile yet.',

        'viewAllUrl' =>
        $searchResultsUrl
            . '?'
            . http_build_query([
                'activity' =>
                'shortlisted-by-you',
            ]),
    ],

    [
        'key' =>
        'who-shortlisted-you',

        'title' =>
        'Who Shortlisted You',

        'description' =>
        'Members who have added your profile to their shortlist.',

        'profiles' =>
        $whoShortlistedYou ?? [],

        'emptyMessage' =>
        'No member has shortlisted your profile yet.',

        /*
         * No View All requested for this section.
         */
        'viewAllUrl' =>
        '',
    ],

    [
        'key' =>
        'profile-visitors',

        'title' =>
        'Who Viewed Your Profile',

        'description' =>
        'Members who have viewed your profile.',

        'profiles' =>
        $profileVisitors ?? [],

        'emptyMessage' =>
        'Your profile has not been viewed yet.',

        /*
         * No View All requested for this section.
         */
        'viewAllUrl' =>
        '',
    ],

    [
        'key' =>
        'profiles-viewed',

        'title' =>
        'Profiles You Viewed',

        'description' =>
        'Profiles you have viewed recently.',

        'profiles' =>
        $profilesViewed ?? [],

        'emptyMessage' =>
        'You have not viewed another profile yet.',

        /*
         * No View All requested for this section.
         */
        'viewAllUrl' =>
        '',
    ],
];
?>

<section class="py-3 py-lg-4">
    <div class="container">

        <?= view(
            'Components/Alerts/FormAlert',
            ['alert' => $formAlert ?? null]
        ) ?>

        <div class="row g-4">
            <aside class="col-12 col-lg-4 col-xl-3">
                <div class="dashboard-sidebar">
                    <div class="card border border-danger border-opacity-25 shadow-sm">
                        <div class="card-body p-3 pb-1 text-center">

                            <div class="member-profile-thumbnail mx-auto mb-2">

                                <img
                                    src="<?= esc(
                                                $resolvedProfileImage,
                                                'attr'
                                            ) ?>"
                                    alt="<?= esc(
                                                $resolvedName
                                                    . ' profile photo',
                                                'attr'
                                            ) ?>">

                            </div>

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
                            <div class="pt-3 text-start">

                                <?= view(
                                    'Components/Member/TrustVerification',
                                    [
                                        'trustVerification' =>
                                        $trustVerification ?? [],

                                        'showCard' =>
                                        false,
                                    ]
                                ) ?>

                            </div>

                        </div>

                        <nav
                            class="list-group list-group-flush"
                            aria-label="Member account">

                            <a
                                href="<?= url_to('web.profile.edit') ?>"
                                class="list-group-item
        list-group-item-action
        d-flex
        align-items-center
        justify-content-between
        gap-3
        py-3">

                                <span
                                    class="d-flex
            align-items-center
            gap-2">

                                    <i
                                        class="ri-user-settings-line fs-18"
                                        aria-hidden="true">
                                    </i>

                                    <span>
                                        Edit Profile
                                    </span>

                                </span>

                                <span
                                    class="badge
            bg-<?= esc(
                    $visibilityClass,
                    'attr'
                ) ?>-subtle
            fs-11
            p-2
            fw-medium
            text-black
            text-nowrap"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="top"
                                    title="<?= esc(
                                                $completionPercentage
                                                    . '% profile completed',
                                                'attr'
                                            ) ?>">

                                    <?= esc(
                                        (string) $completionPercentage
                                    ) ?>% complete

                                </span>

                            </a>

                            <a
                                href="<?= url_to(
                                            'web.partner-preference'
                                        ) ?>"
                                class="list-group-item
        list-group-item-action
        d-flex
        align-items-center
        justify-content-between
        gap-3
        py-3">

                                <span
                                    class="d-flex
            align-items-center
            gap-2">

                                    <i
                                        class="ri-equalizer-line fs-18"
                                        aria-hidden="true">
                                    </i>

                                    <span>
                                        Edit Preferences
                                    </span>

                                </span>

                                <?php if ($preferencesAvailable > 0): ?>

                                    <span
                                        class="badge
                <?= esc(
                                        $preferenceBadgeClass,
                                        'attr'
                                    ) ?>
                fs-11
                p-2
                fw-medium
                text-nowrap"
                                        data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        title="<?= esc(
                                                    $preferenceSetupPercentage
                                                        . '% of partner preferences set',
                                                    'attr'
                                                ) ?>"
                                        aria-label="<?= esc(
                                                        $preferencesConfigured
                                                            . ' of '
                                                            . $preferencesAvailable
                                                            . ' partner preferences set',
                                                        'attr'
                                                    ) ?>">

                                        <?php if ($preferencesComplete): ?>

                                            <i
                                                class="ri-checkbox-circle-line me-1"
                                                aria-hidden="true">
                                            </i>

                                        <?php endif; ?>

                                        <?= esc(
                                            (string)
                                            $preferencesConfigured
                                        ) ?>/<?= esc(
                                                    (string)
                                                    $preferencesAvailable
                                                ) ?> set

                                    </span>

                                <?php endif; ?>

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

                    $sectionViewAllUrl = trim(
                        (string) (
                            $section['viewAllUrl']
                            ?? ''
                        )
                    );
                    ?>

                    <!--
    Stable section ID allows existing Dashboard collections to be opened
    directly from member Quick Links without creating duplicate routes.
-->
                    <section
                        id="<?= esc(
                                $sectionKey,
                                'attr'
                            ) ?>"
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
                                            (<?= esc(
                                                    (string)
                                                    count(
                                                        $sectionProfiles
                                                    )
                                                ) ?>)
                                        </h2>
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

                                    <?php if (
                                        $sectionViewAllUrl !== ''
                                        || count($sectionProfiles) > 1
                                    ): ?>

                                        <div
                                            class="d-flex
            align-items-center
            gap-2 flex-shrink-0">

                                            <?php if (
                                                $sectionViewAllUrl !== ''
                                            ): ?>

                                                <a
                                                    href="<?= esc(
                                                                $sectionViewAllUrl,
                                                                'attr'
                                                            ) ?>"
                                                    class="btn btn-outline-primary
                    btn-sm d-inline-flex
                    align-items-center gap-1">

                                                    <span>
                                                        View All
                                                    </span>

                                                    <i
                                                        class="ri-arrow-right-line"
                                                        aria-hidden="true">
                                                    </i>
                                                </a>

                                            <?php endif; ?>

                                            <?php if (
                                                count($sectionProfiles) > 1
                                            ): ?>

                                                <div
                                                    class="d-flex
                    align-items-center gap-1">

                                                    <button
                                                        type="button"
                                                        class="btn btn-light
                        btn-sm btn-icon"
                                                        aria-label="<?= esc(
                                                                        'Previous '
                                                                            . (
                                                                                $section['title']
                                                                                ?? 'profiles'
                                                                            ),
                                                                        'attr'
                                                                    ) ?>"
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
                                                        aria-label="<?= esc(
                                                                        'Next '
                                                                            . (
                                                                                $section['title']
                                                                                ?? 'profiles'
                                                                            ),
                                                                        'attr'
                                                                    ) ?>"
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

                                    <?php endif; ?>

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

                                        <?= view(
                                            'Components/Member/ProfileThumbnail',
                                            [
                                                'profile' =>
                                                $profile,
                                            ]
                                        ) ?>

                                    <?php endforeach; ?>

                                </div>

                            <?php else: ?>

                                <div
                                    class="text-center py-4">

                                    <i
                                        class="ri-user-search-line
                            fs-32 text-danger fs-16"
                                        aria-hidden="true">
                                    </i>

                                    <p
                                        class="text-danger
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

<?php
$dashboardTrustVerification =
    isset($trustVerification)
    && is_array($trustVerification)
    ? $trustVerification
    : [];

$dashboardAadhaar =
    isset($dashboardTrustVerification['aadhaar'])
    && is_array($dashboardTrustVerification['aadhaar'])
    ? $dashboardTrustVerification['aadhaar']
    : [];
?>

<?= view(
    'Pages/Dashboard/_AadhaarUploadModal',
    [
        'memberName' =>
        $dashboardTrustVerification['memberName']
            ?? $resolvedName,

        'profileReference' =>
        $dashboardTrustVerification['profileReference']
            ?? $resolvedReference,

        'validationErrors' =>
        $aadhaarValidationErrors
            ?? [],

        'openModal' =>
        $openAadhaarModal
            ?? false,

        'rejectionReason' =>
        $dashboardAadhaar['rejectionReason']
            ?? '',

        'returnContext' =>
        'DASHBOARD',
    ]
) ?>

<?php $this->endSection(); ?>