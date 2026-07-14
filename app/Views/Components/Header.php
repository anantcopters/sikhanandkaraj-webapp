<?php

declare(strict_types=1);

$isAuthenticated =
    session('is_authenticated') === true
    && is_numeric(session('auth_user_id'));

$loggedInName = isset($loggedInUserName)
    && is_string($loggedInUserName)
    && trim($loggedInUserName) !== ''
        ? trim($loggedInUserName)
        : 'Member';
?>

<header class="public-header">
    <nav
        class="navbar public-navbar"
        aria-label="Main website navigation">

        <div class="container public-navbar__container">

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

                    <div class="member-header-user">
                        <span
                            class="mdi mdi-account-circle-outline member-header-user__icon"
                            aria-hidden="true">
                        </span>

                        <div class="member-header-user__details">
                            <span class="member-header-user__label">
                                Welcome
                            </span>

                            <strong class="member-header-user__name">
                                <?= esc($loggedInName) ?>
                            </strong>
                        </div>
                    </div>

                    <form
                        method="post"
                        action="<?= url_to('web.logout') ?>"
                        class="m-0"
                        id="headerLogoutForm">

                        <?= csrf_field() ?>

                        <button
                            type="submit"
                            class="btn member-header-logout"
                            aria-label="Logout">

                            <span
                                class="mdi mdi-logout fs-20"
                                aria-hidden="true">
                            </span>

                            <span class="hide-on-mobile">
                                Logout
                            </span>
                        </button>
                    </form>

                <?php else: ?>

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