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
 * @var array<string, string>             $accountPlan
 * @var array<int, array<string, mixed>>  $dailyRecommendations
 * @var array<int, array<string, mixed>>  $allMatches
 * @var array<int, array<string, mixed>>  $newMatches
 * @var array<int, array<string, mixed>>  $profileVisitors
 * @var array<int, array<string, mixed>>  $shortlistedProfiles
 * @var array<int, array<string, mixed>>  $shortlistedByProfiles
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
        'title' => 'Daily Recommendations',
        'description' => 'Profiles selected for you based on your information.',
        'profiles' => $dailyRecommendations ?? [],
        'emptyMessage' => 'No daily recommendations are available yet.',
    ],
    [
        'title' => 'All Matches',
        'description' => 'Members matching your current partner preferences.',
        'profiles' => $allMatches ?? [],
        'emptyMessage' => 'No matching profiles are available yet.',
    ],
    [
        'title' => 'New Matches',
        'description' => 'Recently added profiles that may interest you.',
        'profiles' => $newMatches ?? [],
        'emptyMessage' => 'No new matches are available yet.',
    ],
    [
        'title' => 'Who Viewed Your Profile',
        'description' => 'Members who recently visited your profile.',
        'profiles' => $profileVisitors ?? [],
        'emptyMessage' => 'Your profile has not received any visitors yet.',
    ],
    [
        'title' => 'Profiles Shortlisted By You',
        'description' => 'Members you have saved for later consideration.',
        'profiles' => $shortlistedProfiles ?? [],
        'emptyMessage' => 'You have not shortlisted any profiles yet.',
    ],
    [
        'title' => 'Profiles Who Shortlisted You',
        'description' => 'Members who have shown interest in your profile.',
        'profiles' => $shortlistedByProfiles ?? [],
        'emptyMessage' => 'No member has shortlisted your profile yet.',
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
                                <img
                                    src="<?= esc(
                                                $resolvedProfileImage,
                                                'attr'
                                            ) ?>"
                                    class="dashboard-avatar mx-auto mb-3"
                                    alt="<?= esc(
                                                $resolvedName . ' profile photo',
                                                'attr'
                                            ) ?>">
                            <?php else: ?>
                                <div
                                    class="dashboard-avatar mx-auto mb-3"
                                    aria-hidden="true">

                                    <i class="ri-user-3-line"></i>
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

                            <!--
                                Preference management route has not yet been
                                added to Routes.php.
                            -->
                            <span
                                class="list-group-item d-flex align-items-center gap-2 py-3 text-muted"
                                aria-disabled="true"
                                title="Preference management will be available soon">

                                <i
                                    class="ri-equalizer-line fs-18"
                                    aria-hidden="true">
                                </i>

                                <span>Edit Preferences</span>
                            </span>

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

                <section
                    class="card border border-danger border-opacity-25 shadow-sm mb-4">

                    <div class="card-body p-4">
                        <div
                            class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">

                            <div>
                                <h2 class="fs-18 fw-semibold mb-1">
                                    Complete Your Profile
                                </h2>

                                <p class="text-muted fs-13 mb-0">
                                    A complete profile improves your match
                                    quality and profile visibility.
                                </p>
                            </div>

                            <div
                                class="d-flex align-items-center gap-2 flex-shrink-0">

                                <?php if (
                                    isset($overallProfileSummary)
                                    && is_array($overallProfileSummary)
                                ): ?>
                                    <?php
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

                                    if (!in_array(
                                        $visibilityClass,
                                        $supportedVisibilityClasses,
                                        true
                                    )) {
                                        $visibilityClass = 'danger';
                                    }
                                    ?>

                                    <?php if ($visibilityLabel !== ''): ?>
                                        <span
                                            class="badge bg-<?= esc(
                                                                $visibilityClass,
                                                                'attr'
                                                            ) ?>-subtle text-body p-2">

                                            <?= esc($visibilityLabel) ?>
                                            visibility
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <strong class="fs-18 text-nowrap">
                                    <?= esc(
                                        (string) $completionPercentage
                                    ) ?>%
                                </strong>
                            </div>
                        </div>

                        <div
                            class="progress mb-2"
                            role="progressbar"
                            aria-label="Profile completion"
                            aria-valuenow="<?= esc(
                                                (string) $completionPercentage,
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
                            <p class="text-muted fs-12 mb-4">
                                <?= esc(
                                    (string) $completedSteps
                                ) ?>
                                of
                                <?= esc(
                                    (string) $totalSteps
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
                                    $profileShortcuts as $shortcut
                                ):
                                    if ($shortcut['isComplete'] == false):
                                ?>
                                        <?php
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

                                        $shortcutComplete = (
                                            $shortcut['isComplete']
                                            ?? false
                                        ) === true;

                                        $shortcutStatusLabel = trim(
                                            (string) (
                                                $shortcut['statusLabel']
                                                ?? (
                                                    $shortcutComplete
                                                    ? 'Completed'
                                                    : 'Pending'
                                                )
                                            )
                                        );
                                        ?>

                                        <div class="col-12 col-md-6 col-xl-4">
                                            <a
                                                href="<?= esc(
                                                            $shortcutUrl,
                                                            'attr'
                                                        ) ?>"
                                                class="card h-90 border border-danger border-opacity-25 text-decoration-none mb-2 <?= esc(
                                                                                                                                        $shortcutClass,
                                                                                                                                        'attr'
                                                                                                                                    ) ?>">

                                                <div class="card-body p-3">
                                                    <div
                                                        class="d-flex align-items-start gap-3">

                                                        <span
                                                            class="dashboard-shortcut-icon bg-light rounded-circle flex-shrink-0">

                                                            <i
                                                                class="<?= esc(
                                                                            $shortcutIcon,
                                                                            'attr'
                                                                        ) ?>"
                                                                aria-hidden="true">
                                                            </i>
                                                        </span>

                                                        <span class="flex-grow-1">
                                                            <span
                                                                class="d-flex align-items-start justify-content-between gap-2 mb-1">

                                                                <span
                                                                    class="fs-14 fw-semibold text-body">

                                                                    <?= esc(
                                                                        $shortcutTitle
                                                                    ) ?>
                                                                </span>

                                                                <span
                                                                    class="badge <?= $shortcutComplete
                                                                                        ? 'bg-success'
                                                                                        : 'bg-warning'
                                                                                    ?> text-body flex-shrink-0">

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
                                                                    class="d-block text-muted fs-12 mb-2">

                                                                    <?= esc(
                                                                        $shortcutDescription
                                                                    ) ?>
                                                                </span>
                                                            <?php endif; ?>

                                                            <span
                                                                class="d-flex align-items-center justify-content-between gap-2">

                                                                <span
                                                                    class="text-body fs-13 fw-medium">

                                                                    <?= esc(
                                                                        (string) $shortcutPercentage
                                                                    ) ?>%
                                                                </span>
                                                            </span>
                                                        </span>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div
                            class="d-flex flex-column flex-sm-row justify-content-end gap-2 mt-0">

                            <?php if (
                                isset($nextProfileSection)
                                && is_array($nextProfileSection)
                                && trim(
                                    (string) (
                                        $nextProfileSection['route']
                                        ?? ''
                                    )
                                ) !== ''
                            ): ?>
                                <a
                                    href="<?= url_to(
                                                (string) $nextProfileSection['route']
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
                            <?php else: ?>
                                <a
                                    href="<?= url_to(
                                                'web.profile.edit'
                                            ) ?>"
                                    class="btn btn-outline-danger">

                                    Review Complete Profile

                                    <i
                                        class="ri-edit-line ms-1"
                                        aria-hidden="true">
                                    </i>
                                </a>

                            <?php endif; ?>
                            <a
                                href="<?= url_to('web.profile.view') ?>"
                                class="btn btn-outline-primary waves-effect waves-light shadow-none
        d-inline-flex align-items-center
        justify-content-center gap-2">
                                <i
                                    class="ri-eye-line"
                                    aria-hidden="true"></i>

                                View Profile
                            </a>
                        </div>
                    </div>
                </section>

                <?php foreach ($matchSections as $section): ?>
                    <?php
                    $sectionProfiles = is_array($section['profiles'])
                        ? $section['profiles']
                        : [];
                    ?>

                    <section class="card border border-danger border-opacity-25 shadow-sm mb-4">
                        <div class="card-body p-4">
                            <div
                                class="d-flex align-items-start justify-content-between gap-3 mb-3">

                                <div>
                                    <h2 class="fs-18 fw-semibold mb-1">
                                        <?= esc($section['title']) ?>
                                    </h2>

                                    <p class="text-muted fs-13 mb-0">
                                        <?= esc($section['description']) ?>
                                    </p>
                                </div>


                            </div>

                            <?php if ($sectionProfiles !== []): ?>
                                <div class="dashboard-profile-scroll pb-2">
                                    <?php foreach (
                                        $sectionProfiles as $profile
                                    ): ?>
                                        <?php
                                        $profileName = trim(
                                            (string) (
                                                $profile['name']
                                                ?? 'Member'
                                            )
                                        );

                                        $profileReferenceId = trim(
                                            (string) (
                                                $profile['referenceId']
                                                ?? ''
                                            )
                                        );

                                        $profileAge = max(
                                            0,
                                            (int) (
                                                $profile['age']
                                                ?? 0
                                            )
                                        );

                                        $profileHeight = trim(
                                            (string) (
                                                $profile['height']
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

                                        if ($profileUrl === '') {
                                            $profileUrl = '#';
                                        }
                                        ?>

                                        <article
                                            class="card dashboard-profile-card border border-danger border-opacity-25">

                                            <div class="card-body p-3">
                                                <div
                                                    class="position-relative mx-auto mb-3">

                                                    <?php if (
                                                        $profilePhoto !== ''
                                                    ): ?>
                                                        <img
                                                            src="<?= esc(
                                                                        $profilePhoto,
                                                                        'attr'
                                                                    ) ?>"
                                                            class="dashboard-profile-photo"
                                                            alt="<?= esc(
                                                                        $profileName
                                                                            . ' profile photo',
                                                                        'attr'
                                                                    ) ?>">
                                                    <?php else: ?>
                                                        <div
                                                            class="dashboard-profile-photo"
                                                            aria-hidden="true">

                                                            <i
                                                                class="ri-user-3-line">
                                                            </i>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>

                                                <h3
                                                    class="fs-14 fw-semibold text-center text-truncate mb-1"
                                                    title="<?= esc(
                                                                $profileName,
                                                                'attr'
                                                            ) ?>">

                                                    <?= esc($profileName) ?>
                                                </h3>

                                                <?php if (
                                                    $profileAge > 0
                                                    || $profileHeight !== ''
                                                ): ?>
                                                    <p
                                                        class="text-muted fs-12 text-center mb-2">

                                                        <?php if (
                                                            $profileAge > 0
                                                        ): ?>
                                                            <?= esc(
                                                                (string) $profileAge
                                                            ) ?>
                                                            years
                                                        <?php endif; ?>

                                                        <?php if (
                                                            $profileAge > 0
                                                            && $profileHeight !== ''
                                                        ): ?>
                                                            <span
                                                                aria-hidden="true">
                                                                ,
                                                            </span>
                                                        <?php endif; ?>

                                                        <?php if (
                                                            $profileHeight !== ''
                                                        ): ?>
                                                            <?= esc(
                                                                $profileHeight
                                                            ) ?>
                                                        <?php endif; ?>
                                                    </p>
                                                <?php endif; ?>

                                                <?php if (
                                                    $profileReferenceId !== ''
                                                ): ?>
                                                    <p
                                                        class="fs-12 text-center mb-3">

                                                        <?= esc(
                                                            $profileReferenceId
                                                        ) ?>
                                                    </p>
                                                <?php endif; ?>

                                                <a
                                                    href="<?= esc(
                                                                $profileUrl,
                                                                'attr'
                                                            ) ?>"
                                                    class="btn btn-outline-primary btn-sm w-100">

                                                    View Profile
                                                </a>
                                            </div>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i
                                        class="ri-user-search-line fs-32 text-muted"
                                        aria-hidden="true">
                                    </i>

                                    <p class="text-muted mb-0 mt-2">
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