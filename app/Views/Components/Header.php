<?php

declare(strict_types=1);

/**
 * Determine whether the current request belongs to an authenticated user.
 */
$isAuthenticated =
    session('is_authenticated') === true
    && is_numeric(session('auth_user_id'));

/**
 * The dashboard controller supplies loggedInUserName.
 *
 * Other authenticated pages may not supply it, so use the session value
 * or a safe fallback.
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
 * Display the first letter inside the avatar.
 */
$userInitial = mb_strtoupper(
    mb_substr($resolvedLoggedInName, 0, 1)
);
?>

<header class="public-header">
    <nav
        class="navbar public-navbar"
        aria-label="Main website navigation">

        <div class="container public-navbar__container">

            <!-- Website logo -->
            <a
                class="navbar-brand d-inline-flex align-items-center flex-shrink-0 m-0 p-0"
                href="<?= $isAuthenticated
                            ? url_to('web.dashboard')
                            : site_url('/') ?>"
                aria-label="Sikh Anand Karaj home">

                <img
                    src="<?= base_url(
                                'assets/images/sikhanandkaraj_removebg_2.png'
                            ) ?>"
                    alt="Sikh Anand Karaj"
                    class="public-navbar__logo">
            </a>

            <div class="public-navbar__actions">

                <?php if ($isAuthenticated): ?>

                    <!-- Logged-in user dropdown -->
                    <div class="dropdown member-profile-dropdown">

                        <button
                            type="button"
                            class="btn member-profile-dropdown__toggle"
                            id="memberProfileDropdown"
                            data-bs-toggle="dropdown"
                            data-bs-auto-close="outside"
                            aria-expanded="false">

                            <!-- User avatar -->
                            <span
                                class="member-profile-dropdown__avatar"
                                aria-hidden="true">
                                <?= esc($userInitial) ?>
                            </span>

                            <!-- User details -->
                            <span class="member-profile-dropdown__details">
                                <span class="member-profile-dropdown__welcome">
                                    Welcome
                                </span>

                                <strong class="member-profile-dropdown__name">
                                    <?= esc($resolvedLoggedInName) ?>
                                </strong>
                            </span>

                            <span
                                class="mdi mdi-chevron-down member-profile-dropdown__arrow"
                                aria-hidden="true">
                            </span>
                        </button>

                        <div
                            class="dropdown-menu dropdown-menu-end member-profile-menu"
                            aria-labelledby="memberProfileDropdown">

                            <!-- Dropdown heading -->
                            <div class="member-profile-menu__header">
                                <span
                                    class="member-profile-menu__avatar"
                                    aria-hidden="true">
                                    <?= esc($userInitial) ?>
                                </span>

                                <div class="member-profile-menu__identity">
                                    <strong class="member-profile-menu__name">
                                        <?= esc($resolvedLoggedInName) ?>
                                    </strong>

                                    <?php
                                    $profileReference = session(
                                        'auth_profile_reference'
                                    );
                                    ?>

                                    <?php if (
                                        is_string($profileReference)
                                        && $profileReference !== ''
                                    ): ?>
                                        <span class="member-profile-menu__reference">
                                            <?= esc($profileReference) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="dropdown-divider"></div>

                            <!-- Dashboard -->
                            <a
                                href="<?= url_to('web.dashboard') ?>"
                                class="dropdown-item member-profile-menu__item">

                                <span
                                    class="mdi mdi-view-dashboard-outline"
                                    aria-hidden="true">
                                </span>

                                <span>Dashboard</span>
                            </a>

                            <!-- Edit profile -->
                            <a
                                href="<?= url_to('web.profile.edit') ?>"
                                class="dropdown-item member-profile-menu__item">

                                <span
                                    class="mdi mdi-account-edit-outline"
                                    aria-hidden="true">
                                </span>

                                <span>Edit Profile</span>
                            </a>

                            <!-- Account settings -->
                            <a
                                href="<?= url_to('web.account.settings') ?>"
                                class="dropdown-item member-profile-menu__item">

                                <span
                                    class="mdi mdi-cog-outline"
                                    aria-hidden="true">
                                </span>

                                <span>Account Settings</span>
                            </a>

                            <div class="dropdown-divider"></div>

                            <!--
                                Logout must remain a POST request.

                                Do not use an ordinary anchor for logout because
                                logout changes session state and must remain CSRF
                                protected.
                            -->
                            <form
                                method="post"
                                action="<?= url_to('web.logout') ?>"
                                class="m-0">

                                <?= csrf_field() ?>

                                <button
                                    type="submit"
                                    class="dropdown-item member-profile-menu__item member-profile-menu__logout">

                                    <span
                                        class="mdi mdi-logout"
                                        aria-hidden="true">
                                    </span>

                                    <span>Logout</span>
                                </button>
                            </form>
                        </div>
                    </div>

                <?php else: ?>

                    <!-- Public header -->
                    <span class="fs-16 fw-semibold lh-base text-dark text-nowrap hide-on-mobile-tablet">
                        Already a member?
                    </span>

                    <a
                        href="<?= site_url('login') ?>"
                        class="btn public-navbar__login">
                        Login
                    </a>

                    <a
                        href="tel:+919887005392"
                        class="public-navbar__phone hide-on-mobile"
                        aria-label="Call Sikh Anand Karaj at +91 98870 05320">

                        <span
                            class="mdi mdi-phone-outline public-navbar__phone-icon"
                            aria-hidden="true">
                        </span>

                        <span class="public-navbar__phone-number">
                            +91 98870 05320
                        </span>
                    </a>

                <?php endif; ?>

            </div>
        </div>
    </nav>
</header>