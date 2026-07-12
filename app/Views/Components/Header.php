<?php

declare(strict_types=1);
?>

<header class="public-header">
    <nav
        class="navbar public-navbar"
        aria-label="Main website navigation"
    >
        <div class="container public-navbar__container">

            <a
                class="navbar-brand public-navbar__brand"
                href="<?= site_url('/') ?>"
                aria-label="Sikh Anand Karaj home"
            >
                <img
                    src="<?= base_url('assets/images/sikhanandkaraj_removebg_2.png') ?>"
                    alt="Sikh Anand Karaj"
                    class="public-navbar__logo"
                >
            </a>

            <div class="public-navbar__actions">

                <span class="public-navbar__member-text hide-on-mobile-tablet">
                    Already a member?
                </span>

                <a
                    href="<?= site_url('login') ?>"
                    class="btn public-navbar__login"
                >
                    Login
                </a>

                <a
                    href="tel:+919887005392"
                    class="public-navbar__phone hide-on-mobile"
                    aria-label="Call Sikh Anand Karaj at +91 98870 05320"
                >
                    <span
                        class="mdi mdi-phone-outline public-navbar__phone-icon"
                        aria-hidden="true"
                    ></span>

                    <span class="public-navbar__phone-number">
                        +91 98870 05320
                    </span>
                </a>

            </div>
        </div>
    </nav>
</header>
