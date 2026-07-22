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
// $userInitial = mb_strtoupper(
//     mb_substr($resolvedLoggedInName, 0, 1)
// );
?>

<header class="public-header">
    <nav
        class="navbar public-navbar"
        aria-label="Main website navigation">

        <div class="container public-navbar__container
                    <?= $isAuthenticated
                        ? 'public-navbar__container--authenticated'
                        : '' ?>">

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

            <div class="public-navbar__actions
                <?= $isAuthenticated
                    ? 'public-navbar__actions--authenticated'
                    : '' ?>">

                <?php if ($isAuthenticated): ?>

                    <?php
                    $profileReference = session(
                        'auth_profile_reference'
                    );

                    $resolvedProfileReference =
                        is_string($profileReference)
                        ? trim($profileReference)
                        : '';
                    ?>

                    <div class="dropdown header-item topbar-user">

                        <button
                            type="button"
                            class="btn shadow-none"
                            id="page-header-user-dropdown"
                            data-bs-display="static"
                            data-bs-toggle="dropdown"
                            aria-haspopup="true"
                            aria-expanded="false">

                            <span class="d-flex align-items-center">
                                <span
                                    class="avatar-sm flex-shrink-0 d-inline-flex align-items-center justify-content-center"
                                    aria-hidden="true">

                                    <i
                                        class="ri-user-line fs-2 lh-1"
                                        aria-hidden="true"></i>
                                </span>

                                <span class="text-start ms-xl-2">

                                    <span
                                        class="d-none d-xl-inline-block ms-1 fw-medium user-name-text">

                                        <?= esc($resolvedLoggedInName) ?>
                                    </span>

                                    <?php if (
                                        $resolvedProfileReference !== ''
                                    ): ?>
                                        <span
                                            class="d-none d-xl-block ms-1 fs-12 user-name-sub-text text-muted">

                                            <?= esc($resolvedProfileReference) ?>
                                        </span>
                                    <?php endif; ?>

                                </span>

                                <span
                                    class="mdi mdi-chevron-down d-none d-xl-inline-block ms-2 text-muted"
                                    aria-hidden="true">
                                </span>
                            </span>
                        </button>

                        <div
                            class="dropdown-menu dropdown-menu-end"
                            aria-labelledby="page-header-user-dropdown">

                            <h6 class="dropdown-header fs-14">
                                Welcome
                                <?= esc($resolvedLoggedInName) ?>!
                            </h6>

                            <a
                                class="dropdown-item"
                                href="<?= url_to('web.dashboard') ?>">

                                <i
                                    class="mdi mdi-view-dashboard-outline text-muted fs-16 align-middle me-1"
                                    aria-hidden="true"></i>

                                <span class="align-middle">
                                    Dashboard
                                </span>
                            </a>

                            <a
                                class="dropdown-item"
                                href="<?= url_to('web.profile.edit') ?>">

                                <i
                                    class="mdi mdi-account-edit-outline text-muted fs-16 align-middle me-1"
                                    aria-hidden="true"></i>

                                <span class="align-middle">
                                    Edit Profile
                                </span>
                            </a>

                            <a
                                class="dropdown-item"
                                href="<?= url_to('web.account.settings') ?>">

                                <i
                                    class="mdi mdi-cog-outline text-muted fs-16 align-middle me-1"
                                    aria-hidden="true"></i>

                                <span class="align-middle">
                                    Account Settings
                                </span>
                            </a>

                            <div class="dropdown-divider"></div>
                            <form
                                method="post"
                                action="<?= url_to('web.logout') ?>"
                                class="m-0">

                                <?= csrf_field() ?>

                                <button
                                    type="submit"
                                    class="dropdown-item border-0 bg-transparent w-100 text-start">

                                    <i
                                        class="mdi mdi-logout text-danger fs-16 align-middle me-1"
                                        aria-hidden="true"></i>

                                    <span class="align-middle">
                                        Logout
                                    </span>
                                </button>
                            </form>

                        </div>
                    </div>

                <?php else: ?>

                    <!-- Public header -->
                    <span class="fs-16 fw-semibold lh-base text-dark text-nowrap hide-on-mobile-tablet">
                        Already a member
                    </span>

                    <a
                        href="<?= site_url('login') ?>"
                        class="btn public-navbar__login fs-14">
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