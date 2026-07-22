<?php

declare(strict_types=1);

/**
 * Member Dashboard
 *
 * Authentication and contact variables.
 *
 * @var string|null $profileReference
 * @var string|null $loggedInUserName
 * @var string|null $primaryEmail
 * @var string|null $primaryMobile
 * @var bool        $isEmailVerified
 * @var bool        $isMobileVerified
 *
 * Dashboard variables supplied by MemberDashboardDataService.
 *
 * @var array<string, string>              $accountPlan
 * @var string|null                        $profileImage
 * @var array<string, int>                 $profileCompletion
 * @var array<int, array<string, string>>  $profileShortcuts
 * @var array<int, array<string, mixed>>   $dailyRecommendations
 * @var array<int, array<string, mixed>>   $allMatches
 * @var array<int, array<string, mixed>>   $newMatches
 * @var array<int, array<string, mixed>>   $profileVisitors
 * @var array<int, array<string, mixed>>   $shortlistedProfiles
 * @var array<int, array<string, mixed>>   $shortlistedByProfiles
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

        <?php if (
            is_string($primaryEmail)
            && $primaryEmail !== ''
            && !$isEmailVerified
        ): ?>
            <div
                class="email-verification-alert mb-4"
                role="alert">

                <div class="email-verification-alert__content">
                    <div class="email-verification-alert__icon">
                        <i
                            class="ri-mail-warning-line"
                            aria-hidden="true">
                        </i>
                    </div>

                    <div>
                        <h2 class="email-verification-alert__title">
                            Verify your email address
                        </h2>

                        <p class="email-verification-alert__message">
                            Your email address
                            <strong><?= esc($primaryEmail) ?></strong>
                            has not been verified. Verify it to keep your
                            account secure and receive important updates.
                        </p>
                    </div>
                </div>

                <form
                    method="post"
                    action="<?= url_to(
                                'web.email.verification.send'
                            ) ?>"
                    class="email-verification-alert__form"
                    id="emailVerificationForm"
                    novalidate>

                    <?= csrf_field() ?>

                    <button
                        type="submit"
                        class="btn email-verification-alert__action"
                        id="emailVerificationSubmit">

                        <span
                            class="email-verification-submit__label fw-normal">

                            Send verification email
                        </span>

                        <span
                            class="registration-submit__loading d-none"
                            aria-hidden="true">

                            <span
                                class="spinner-border spinner-border-sm"
                                role="status"
                                aria-hidden="true">
                            </span>

                            <span>Sending email...</span>
                        </span>
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <aside class="col-12 col-lg-4 col-xl-3">
                <div class="dashboard-sidebar">
                    <div class="card border-0 shadow-sm">
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
                                    class="d-flex align-items-center justify-content-between gap-3 mb-3">

                                    <span
                                        class="d-flex align-items-center gap-2">

                                        <i
                                            class="ri-mail-line fs-18"
                                            aria-hidden="true">
                                        </i>

                                        <span><?php if (
                                                    is_string($primaryEmail)
                                                    && $primaryEmail !== ''
                                                ): ?>
                                                <p
                                                    class="text-break mb-0 fw-medium">

                                                    <?= esc($primaryEmail) ?>
                                                </p>
                                            <?php endif; ?>
                                        </span>
                                    </span>

                                    <?php if ($isEmailVerified): ?>
                                        <span
                                            class="badge bg-success-subtle text-success p-2">

                                            Verified
                                        </span>
                                    <?php else: ?>
                                        <span
                                            class="badge bg-warning-subtle text-warning p-2">

                                            Pending
                                        </span>
                                    <?php endif; ?>
                                </div>



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
                                            class="badge bg-success-subtle text-success p-2">

                                            Verified
                                        </span>
                                    <?php else: ?>
                                        <span
                                            class="badge bg-warning-subtle text-warning p-2">

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

                <section class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <div
                            class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3">

                            <div>
                                <h2 class="fs-18 fw-semibold mb-1">
                                    Complete Your Profile
                                </h2>

                                <p class="text-muted fs-13 mb-0">
                                    A complete profile improves your match
                                    quality and profile visibility.
                                </p>
                            </div>

                            <strong class="fs-18 text-nowrap">
                                <?= esc(
                                    (string) $completionPercentage
                                ) ?>%
                            </strong>
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
                                style="width: <?= esc(
                                                    (string) $completionPercentage,
                                                    'attr'
                                                ) ?>%">
                            </div>
                        </div>

                        <?php if ($totalSteps > 0): ?>
                            <p class="text-muted fs-12 mb-4">
                                <?= esc((string) $completedSteps) ?>
                                of
                                <?= esc((string) $totalSteps) ?>
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
                                ): ?>
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
                                    ?>

                                    <div class="col-12 col-md-4">
                                        <a
                                            href="<?= esc(
                                                        $shortcutUrl,
                                                        'attr'
                                                    ) ?>"
                                            class="card h-80 border text-decoration-none mb-1 <?= $shortcut['class'] ?>">

                                            <div class="card-body p-3">
                                                <div
                                                    class="d-flex align-items-start gap-3">

                                                    <span
                                                        class="dashboard-shortcut-icon bg-light rounded-circle">

                                                        <i
                                                            class="<?= esc(
                                                                        $shortcutIcon,
                                                                        'attr'
                                                                    ) ?>"
                                                            aria-hidden="true">
                                                        </i>
                                                    </span>

                                                    <span>
                                                        <span
                                                            class="d-block fs-14 fw-semibold text-body mb-1">

                                                            <?= esc(
                                                                $shortcutTitle
                                                            ) ?>
                                                        </span>

                                                        <?php if (
                                                            $shortcutDescription
                                                            !== ''
                                                        ): ?>
                                                            <span
                                                                class="d-block text-muted fs-12">

                                                                <?= esc(
                                                                    $shortcutDescription
                                                                ) ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <?php foreach ($matchSections as $section): ?>
                    <?php
                    $sectionProfiles = is_array($section['profiles'])
                        ? $section['profiles']
                        : [];
                    ?>

                    <section class="card border-0 shadow-sm mb-4">
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
                                            class="card dashboard-profile-card border">

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