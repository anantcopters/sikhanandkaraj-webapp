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
 * Determine the active navigation item.
 */
$currentPath = trim(
    service('uri')->getPath(),
    '/'
);

$homeActive =
    $currentPath === 'dashboard';

$matchesActive =
    $currentPath === 'matches'
    || str_starts_with(
        $currentPath,
        'matches/'
    );

$interestActive =
    $currentPath === 'interests'
    || str_starts_with(
        $currentPath,
        'interests/'
    );

$searchActive =
    $currentPath === 'search'
    || str_starts_with(
        $currentPath,
        'search/'
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
                                'assets/images/logo_sak_bgremove_final.png'
                            ) ?>"
                    alt="Sikhanandkaraj"
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
                                    Edit Profile
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
                                    Edit Preferences
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
                                    Edit Profile
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

                <!-- Existing public header actions -->
                <div class="public-navbar__actions">
                    <?php if (
                        !$hidePublicLoginAction
                    ): ?>
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
                            href="<?= site_url('login') ?>"
                            class="btn
                        public-navbar__login
                        fs-14">
                            Login
                        </a>
                    <?php endif; ?>
                    <a
                        href="tel:+919887005392"
                        class="public-navbar__phone
                        hide-on-mobile"
                        aria-label="Call Sikhanandkaraj at +91 98870 05392">

                        <span
                            class="mdi
                            mdi-phone-outline
                            public-navbar__phone-icon"
                            aria-hidden="true">
                        </span>

                        <span class="public-navbar__phone-number">
                            +91 98870 05392
                        </span>
                    </a>
                </div>

            <?php endif; ?>
        </div>
    </nav>
</header>