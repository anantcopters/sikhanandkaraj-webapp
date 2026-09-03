<?php

declare(strict_types=1);

/**
 * Check whether the current request belongs to an authenticated member.
 */
$isAuthenticated =
    session('is_authenticated') === true
    && is_numeric(session('auth_user_id'));

/**
 * Resolve the logged-in member name.
 *
 * DashboardController may provide $loggedInUserName. Other authenticated
 * pages can safely use the member name stored in the session.
 */
$resolvedLoggedInName = '';

if (
    isset($loggedInUserName)
    && is_string($loggedInUserName)
) {
    $resolvedLoggedInName = trim($loggedInUserName);
}

if ($resolvedLoggedInName === '') {
    $sessionUserName = session('auth_user_name');

    $resolvedLoggedInName = is_string($sessionUserName)
        ? trim($sessionUserName)
        : '';
}

if ($resolvedLoggedInName === '') {
    $resolvedLoggedInName = 'Member';
}

/**
 * Resolve the member profile reference.
 */
$profileReference = session('auth_profile_reference');

$resolvedProfileReference = is_string($profileReference)
    ? trim($profileReference)
    : '';

/**
 * Notification and message counters.
 *
 * These values can be supplied globally through BaseController.
 * Safe defaults ensure the header continues working until backend
 * notification integration is completed.
 */
$unreadNotificationCount = isset($unreadNotificationCount)
    && is_numeric($unreadNotificationCount)
    ? max(0, (int) $unreadNotificationCount)
    : 0;

$unreadMessageCount = isset($unreadMessageCount)
    && is_numeric($unreadMessageCount)
    ? max(0, (int) $unreadMessageCount)
    : 0;

/**
 * Determine the active member-navigation item.
 *
 * Matches deliberately reuses the Search Results pipeline and redirects to:
 *
 *     /search/results?activity=all-matches
 *
 * Therefore pathname alone is not sufficient to determine the active menu.
 * The activity query parameter identifies the semantic Matches context.
 */
$currentPath = trim(
    service('uri')->getPath(),
    '/'
);

$currentActivity = strtolower(
    trim(
        (string) service('request')
            ->getGet('activity')
    )
);

/*
 * Match collections available from the Matches navigation and Dashboard.
 */
$matchActivities = [
    'all-matches',
    'new-profiles',
    'shortlisted-by-you',
    'shortlisted-you',
    'viewed-you',
    'viewed-by-you',
];

/*
 * Matches context.
 *
 * Support both:
 *
 * - direct /matches routes;
 * - the shared Search Results page when opened as All Matches.
 */
$isMatchesContext =
    $currentPath === 'matches'
    || str_starts_with(
        $currentPath,
        'matches/'
    )
    || (
        str_starts_with(
            $currentPath,
            'search/'
        )
        && in_array(
            $currentActivity,
            $matchActivities,
            true
        )
    );

$homeActive =
    $currentPath === 'dashboard';

$matchesActive =
    $isMatchesContext;

$interestActive =
    $currentPath === 'interests'
    || str_starts_with(
        $currentPath,
        'interests/'
    );

/*
 * Search remains active for normal Search pages/results, but must not be
 * highlighted when the shared Search Results screen represents Matches.
 */
$searchActive =
    !$isMatchesContext
    && (
        $currentPath === 'search'
        || str_starts_with(
            $currentPath,
            'search/'
        )
    );

/**
 * Some reusable unauthenticated screens are used outside
 * normal member authentication.
 *
 * SAK Volunteer OTP verification uses the public layout,
 * but must not advertise member Login from its header.
 */
$hidePublicLoginAction =
    ($hidePublicLoginAction ?? false)
    === true;
?>

<header class="public-header">
    <nav
        class="navbar public-navbar"
        aria-label="Main website navigation">

        <div
            class="container public-navbar__container
            position-relative
            <?= $isAuthenticated
                ? 'public-navbar__container--authenticated'
                : '' ?>">

            <!-- Website logo -->
            <a
                class="navbar-brand
                d-inline-flex
                align-items-center
                flex-shrink-0
                m-0 p-0"
                href="<?= $isAuthenticated
                            ? url_to('web.dashboard')
                            : site_url('/') ?>"
                aria-label="Sikhanandkaraj home">

                <img
                    src="<?= base_url(
                                'assets/images/logo_sak_header.png'
                            ) ?>"
                    alt="Sikhanandkaraj"
                    width="500"
                    height="88"
                    class="public-navbar__logo">
            </a>

            <?php if ($isAuthenticated): ?>

                <!--
                    Desktop navigation.

                    This menu is shown from the Bootstrap lg breakpoint
                    onwards and remains visually centred in the header.
                -->
                <ul
                    class="navbar-nav
                    nav-underline
                    flex-row
                    align-items-center
                    gap-2
                    position-absolute
                    top-50 start-50
                    translate-middle
                    d-none d-lg-flex gap-4">

                    <!-- Home -->
                    <li class="nav-item">
                        <a
                            href="<?= url_to('web.dashboard') ?>"
                            class="nav-link
                            d-flex
                            align-items-center
                            gap-2
                            py-1 py-lg-2 fs-15
                            <?= $homeActive
                                ? 'active text-primary'
                                : '' ?>"
                            <?= $homeActive
                                ? 'aria-current="page"'
                                : '' ?>>

                            <i
                                class="ri-home-4-line
                                fw-normal
                                flex-shrink-0 text-success"
                                aria-hidden="true">
                            </i>

                            <span
                                class="<?= $homeActive
                                            ? 'fw-semibold'
                                            : 'text-black' ?>">
                                Home
                            </span>
                        </a>
                    </li>

                    <!-- Search -->
                    <li class="nav-item">
                        <a
                            href="<?= url_to('web.search') ?>"
                            class="nav-link
        d-flex
        align-items-center
        gap-2
        py-1 py-lg-2 fs-15
        <?= $searchActive
                    ? 'active text-primary'
                    : '' ?>"
                            <?= $searchActive
                                ? 'aria-current="page"'
                                : '' ?>>

                            <i
                                class="ri-search-line
            fw-normal
            flex-shrink-0 text-info"
                                aria-hidden="true">
                            </i>

                            <span
                                class="<?= $searchActive
                                            ? 'fw-semibold'
                                            : 'text-black' ?>">
                                Search
                            </span>
                        </a>
                    </li>

                    <!-- Matches -->
                    <li class="nav-item">
                        <a
                            href="<?= site_url('matches') ?>"
                            class="nav-link
                            d-flex
                            align-items-center
                            gap-2
                            py-1 py-lg-2 fs-15
                            <?= $matchesActive
                                ? 'active text-primary'
                                : '' ?>"
                            <?= $matchesActive
                                ? 'aria-current="page"'
                                : '' ?>>

                            <i
                                class="ri-heart-3-line
                                fw-normal
                                flex-shrink-0 text-danger"
                                aria-hidden="true">
                            </i>

                            <span
                                class="<?= $matchesActive
                                            ? 'fw-semibold'
                                            : 'text-black' ?>">
                                Matches
                            </span>
                        </a>
                    </li>

                    <!-- Interest -->
                    <li class="nav-item">
                        <a
                            href="<?= site_url('interests') ?>"
                            class="nav-link
                            d-flex
                            align-items-center
                            gap-2
                            py-1 py-lg-2 fs-15
                            <?= $interestActive
                                ? 'active text-primary'
                                : '' ?>"
                            <?= $interestActive
                                ? 'aria-current="page"'
                                : '' ?>>

                            <i
                                class="ri-heart-add-line
                                fw-normal
                                flex-shrink-0 text-warning"
                                aria-hidden="true">
                            </i>

                            <span
                                class="<?= $interestActive
                                            ? 'fw-semibold'
                                            : 'text-black' ?>">
                                Interest
                            </span>
                        </a>
                    </li>


                </ul>

                <div
                    class="public-navbar__actions
                    public-navbar__actions--authenticated
                    ms-auto">

                    <!--
                        Desktop account dropdown.

                        The user icon and surrounding account border have
                        intentionally been removed for desktop screens.
                    -->
                    <div
                        class="dropdown
                        d-none d-lg-block">

                        <button
                            type="button"
                            class="btn
                            shadow-none
                            border-0
                            bg-transparent
                            p-0"
                            id="desktop-member-dropdown"
                            data-bs-display="static"
                            data-bs-toggle="dropdown"
                            aria-haspopup="true"
                            aria-expanded="false">

                            <span class="d-flex align-items-center">

                                <span class="text-start">

                                    <span
                                        class="d-block
                                        fw-medium
                                        user-name-text fs-15">
                                        <?= esc(
                                            $resolvedLoggedInName
                                        ) ?>
                                    </span>

                                    <?php if (
                                        $resolvedProfileReference !== ''
                                    ): ?>
                                        <span
                                            class="d-block
                                            fs-13
                                            user-name-sub-text
                                            text-primary">

                                            <?= esc(
                                                $resolvedProfileReference
                                            ) ?>
                                        </span>
                                    <?php endif; ?>
                                </span>

                                <i
                                    class="ri-arrow-down-s-line
                                    text-muted
                                    ms-2"
                                    aria-hidden="true">
                                </i>
                            </span>
                        </button>

                        <div
                            class="dropdown-menu
                            dropdown-menu-end"
                            aria-labelledby="desktop-member-dropdown">

                            <!-- Edit Profile -->
                            <a
                                class="dropdown-item"
                                href="<?= url_to(
                                            'web.profile.edit'
                                        ) ?>">

                                <i
                                    class="ri-user-settings-line
                                    text-muted
                                    fs-16
                                    align-middle
                                    me-1"
                                    aria-hidden="true">
                                </i>

                                <span class="align-middle">
                                    Your Profile
                                </span>
                            </a>

                            <!-- Edit Preference -->
                            <a
                                class="dropdown-item"
                                href="<?= url_to('web.partner-preference') ?>">

                                <i
                                    class="ri-equalizer-line
                                    text-muted
                                    fs-16
                                    align-middle
                                    me-1"
                                    aria-hidden="true">
                                </i>

                                <span class="align-middle">
                                    Partner Preferences
                                </span>
                            </a>

                            <!-- Account Settings -->
                            <a
                                class="dropdown-item"
                                href="<?= url_to(
                                            'web.account.settings'
                                        ) ?>">

                                <i
                                    class="ri-settings-3-line
                                    text-muted
                                    fs-16
                                    align-middle
                                    me-1"
                                    aria-hidden="true">
                                </i>

                                <span class="align-middle">
                                    Account Settings
                                </span>
                            </a>

                            <div class="dropdown-divider"></div>

                            <!-- Logout -->
                            <form
                                method="post"
                                action="<?= url_to('web.logout') ?>"
                                class="m-0">

                                <?= csrf_field() ?>

                                <button
                                    type="submit"
                                    class="dropdown-item
                                    border-0
                                    bg-transparent
                                    w-100
                                    text-start">

                                    <i
                                        class="ri-logout-box-r-line
                                        text-danger
                                        fs-16
                                        align-middle
                                        me-1"
                                        aria-hidden="true">
                                    </i>

                                    <span class="align-middle">
                                        Logout
                                    </span>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!--
                        Tablet and mobile account dropdown.

                        Only the user icon is displayed in the header.
                        Member name, reference, navigation and account actions
                        are displayed inside the dropdown.
                    -->
                    <div
                        class="dropdown
                        header-item
                        d-lg-none">

                        <button
                            type="button"
                            class="btn
                            btn-icon
                            shadow-none
                            border-0
                            bg-transparent
                            rounded-circle"
                            id="mobile-member-dropdown"
                            data-bs-display="static"
                            data-bs-toggle="dropdown"
                            aria-haspopup="true"
                            aria-expanded="false"
                            aria-label="Open member menu">

                            <i
                                class="ri-user-line fs-2"
                                aria-hidden="true">
                            </i>
                        </button>

                        <div
                            class="dropdown-menu
                            dropdown-menu-end"
                            aria-labelledby="mobile-member-dropdown">

                            <!-- Member identity -->
                            <div class="dropdown-header">

                                <span
                                    class="d-block
                                    fs-14
                                    fw-semibold
                                    text-dark">

                                    <?= esc(
                                        $resolvedLoggedInName
                                    ) ?>
                                </span>

                                <?php if (
                                    $resolvedProfileReference !== ''
                                ): ?>
                                    <span
                                        class="d-block
                                        fs-12
                                        fw-normal
                                        text-muted
                                        mt-1">

                                        <?= esc(
                                            $resolvedProfileReference
                                        ) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="dropdown-divider"></div>

                            <!-- Home -->
                            <a
                                href="<?= url_to(
                                            'web.dashboard'
                                        ) ?>"
                                class="dropdown-item
                                <?= $homeActive
                                    ? 'active'
                                    : '' ?>"
                                <?= $homeActive
                                    ? 'aria-current="page"'
                                    : '' ?>>

                                <i
                                    class="ri-home-4-line
                                    fs-16
                                    align-middle
                                    me-1"
                                    aria-hidden="true">
                                </i>

                                <span class="align-middle">
                                    Home
                                </span>
                            </a>
                            <a
                                href="<?= url_to('web.search') ?>"
                                class="dropdown-item
        <?= $searchActive
                    ? 'active'
                    : '' ?>"
                                <?= $searchActive
                                    ? 'aria-current="page"'
                                    : '' ?>>

                                <i
                                    class="ri-search-line
            fs-16
                                    align-middle
                                    me-1"
                                    aria-hidden="true">
                                </i>

                                <span class="align-middle">
                                    Search
                                </span>
                            </a>
                            <!-- Matches -->
                            <a
                                href="<?= site_url('matches') ?>"
                                class="dropdown-item
                                <?= $matchesActive
                                    ? 'active'
                                    : '' ?>"
                                <?= $matchesActive
                                    ? 'aria-current="page"'
                                    : '' ?>>

                                <i
                                    class="ri-heart-3-line
                                    fs-16
                                    align-middle
                                    me-1"
                                    aria-hidden="true">
                                </i>

                                <span class="align-middle">
                                    Matches
                                </span>
                            </a>

                            <!-- Interest -->
                            <a
                                href="<?= site_url('interests') ?>"
                                class="dropdown-item
                                <?= $interestActive
                                    ? 'active'
                                    : '' ?>"
                                <?= $interestActive
                                    ? 'aria-current="page"'
                                    : '' ?>>

                                <i
                                    class="ri-heart-add-line
                                    fs-16
                                    align-middle
                                    me-1"
                                    aria-hidden="true">
                                </i>

                                <span class="align-middle">
                                    Interest
                                </span>
                            </a>


                            <div class="dropdown-divider"></div>

                            <!-- Edit Profile -->
                            <a
                                class="dropdown-item"
                                href="<?= url_to(
                                            'web.profile.edit'
                                        ) ?>">

                                <i
                                    class="ri-user-settings-line
                                    text-muted
                                    fs-16
                                    align-middle
                                    me-1"
                                    aria-hidden="true">
                                </i>

                                <span class="align-middle">
                                    Your Profile
                                </span>
                            </a>

                            <!-- Account Settings -->
                            <a
                                class="dropdown-item"
                                href="<?= url_to(
                                            'web.account.settings'
                                        ) ?>">

                                <i
                                    class="ri-settings-3-line
                                    text-muted
                                    fs-16
                                    align-middle
                                    me-1"
                                    aria-hidden="true">
                                </i>

                                <span class="align-middle">
                                    Account Settings
                                </span>
                            </a>

                            <div class="dropdown-divider"></div>

                            <!-- Logout -->
                            <form
                                method="post"
                                action="<?= url_to('web.logout') ?>"
                                class="m-0">

                                <?= csrf_field() ?>

                                <button
                                    type="submit"
                                    class="dropdown-item
                                    border-0
                                    bg-transparent
                                    w-100
                                    text-start">

                                    <i
                                        class="ri-logout-box-r-line
                                        text-danger
                                        fs-16
                                        align-middle
                                        me-1"
                                        aria-hidden="true">
                                    </i>

                                    <span class="align-middle">
                                        Logout
                                    </span>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Notification bell -->
                    <a
                        href="<?= site_url('notifications') ?>"
                        class="btn
                        btn-icon
                        btn-topbar
                        btn-ghost-secondary
                        bg-light
                        rounded-circle
                        position-relative"
                        aria-label="View notifications">

                        <i
                            class="ri-notification-3-line fs-22"
                            aria-hidden="true">
                        </i>

                        <?php if (
                            $unreadNotificationCount > 0
                        ): ?>
                            <span
                                class="position-absolute
                                top-0 start-100
                                translate-middle
                                badge
                                rounded-pill
                                bg-danger">

                                <?= esc(
                                    $unreadNotificationCount > 99
                                        ? '99+'
                                        : (string)
                                        $unreadNotificationCount
                                ) ?>

                                <span class="visually-hidden">
                                    unread notifications
                                </span>
                            </span>
                        <?php endif; ?>
                    </a>
                </div>

            <?php else: ?>

                <!-- Public header actions -->
                <div class="public-navbar__actions">

                    <?php if (
                        !$hidePublicLoginAction
                    ): ?>

                        <!-- Quick profile registration -->
                        <button
                            type="button"
                            class="btn btn-danger
                    fs-14 text-nowrap
                    d-inline-flex
                    align-items-center
                    justify-content-center
                    gap-1"
                            data-bs-toggle="modal"
                            data-bs-target="#quickProfileRegistrationModal"
                            aria-haspopup="dialog">

                            <i
                                class="ri-user-add-line"
                                aria-hidden="true">
                            </i>

                            Quick Register Profile

                        </button>

                        <span
                            class="fs-16
                    fw-semibold
                    lh-base
                    text-dark
                    text-nowrap
                    hide-on-mobile-tablet">

                            Already a member?

                        </span>

                        <a
                            href="<?= esc(
                                        url_to(
                                            'web.login'
                                        ),
                                        'attr'
                                    ) ?>"
                            class="btn
                    public-navbar__login
                    fs-14">

                            Login

                        </a>

                    <?php endif; ?>

                    <a
                        href="tel:+919887711226"
                        class="public-navbar__phone
                hide-on-mobile"
                        aria-label="Call Sikhanandkaraj at +91 98877 11226">

                        <span
                            class="mdi
                    mdi-phone-outline
                    public-navbar__phone-icon"
                            aria-hidden="true">
                        </span>

                        <span class="public-navbar__phone-number">
                            +91 9887711226
                        </span>

                    </a>

                </div>

            <?php endif; ?>
        </div>
    </nav>
</header>
<?php if (
    !$isAuthenticated
    && !$hidePublicLoginAction
): ?>

    <!-- Quick profile registration information modal -->
    <div
        class="modal fade"
        id="quickProfileRegistrationModal"
        tabindex="-1"
        aria-labelledby="quickProfileRegistrationModalTitle"
        aria-describedby="quickProfileRegistrationModalDescription"
        aria-hidden="true">

        <div
            class="modal-dialog
                modal-dialog-centered
                modal-md">

            <div class="modal-content border-0 shadow">

                <div class="modal-body p-4 text-center">

                    <div
                        class="avatar-md
                            rounded-circle
                            bg-primary-subtle
                            text-primary
                            d-inline-flex
                            align-items-center
                            justify-content-center
                            mb-3"
                        aria-hidden="true">

                        <i
                            class="ri-profile-line fs-30">
                        </i>

                    </div>

                    <h2
                        class="fs-18
                            fw-semibold
                            mb-2"
                        id="quickProfileRegistrationModalTitle">

                        Quick Profile Registration

                    </h2>

                    <p
                        class="text-muted
                            fs-13 mb-3"
                        id="quickProfileRegistrationModalDescription">

                        Submit your basic profile details for review.
                        Our team will verify your profile and contact
                        you if any clarification or additional
                        information is required.

                    </p>

                    <div
                        class="alert
                            alert-info
                            text-start
                            fs-13 mb-4"
                        role="note">

                        <div
                            class="d-flex
                                align-items-start
                                gap-2">

                            <i
                                class="ri-information-line
                                    fs-18
                                    flex-shrink-0"
                                aria-hidden="true">
                            </i>

                            <span>
                                Please provide an active mobile number
                                so our team can contact you when needed.
                            </span>

                        </div>

                    </div>

                    <div
                        class="d-flex
                            flex-column-reverse
                            flex-sm-row
                            justify-content-center
                            gap-2">

                        <button
                            type="button"
                            class="btn btn-light
                                flex-fill"
                            data-bs-dismiss="modal">

                            Not Now

                        </button>

                        <a
                            href="<?= esc(
                                        url_to(
                                            'prelaunch.profile.index'
                                        ),
                                        'attr'
                                    ) ?>"
                            class="btn btn-danger
                                flex-fill
                                d-inline-flex
                                align-items-center
                                justify-content-center
                                gap-1">

                            <span>
                                Continue
                            </span>

                            <i
                                class="ri-arrow-right-line"
                                aria-hidden="true">
                            </i>

                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

<?php endif; ?>