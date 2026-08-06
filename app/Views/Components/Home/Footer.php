<?php

declare(strict_types=1);

$currentYear = date('Y');
?>

<footer class="section py-5">
    <div class="container">
        <div class="row g-4 pb-4">
            <!-- Brand and platform information -->
            <div class="col-12 col-lg-4">
                <a
                    class="
                        navbar-brand
                        d-inline-flex
                        align-items-center
                        flex-shrink-0
                        m-0
                        p-0
                    "
                    href="<?= site_url('/') ?>"
                    aria-label="Sikhanandkaraj home">

                    <img
                        src="<?= base_url(
                                    'assets/images/logo_sak_bgremove_final.png'
                                ) ?>"
                        alt="Sikhanandkaraj"
                        class="public-navbar__logo w-75">
                </a>

                <p class="lh-lg mb-0">
                    A secure Sikh matrimonial platform helping
                    individuals and families discover meaningful
                    relationships rooted in faith and shared values.
                </p>

                <p class="lh-lg mb-3 color-pink">
                    This website is strictly for matrimonial purposes
                    only and is not a dating website.
                </p>
            </div>

            <!-- Explore -->
            <div class="col-6 col-md-4 col-lg-2 text-lg-center">
                <h2 class="fs-15 fw-semibold mb-3">
                    Explore
                </h2>

                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <a
                            href="<?= site_url('/') ?>"
                            class="text-decoration-none">
                            Home
                        </a>
                    </li>

                    <li class="mb-2">
                        <a
                            href="<?= site_url('login') ?>"
                            class="text-decoration-none">
                            Login
                        </a>
                    </li>

                    <li class="mb-2">
                        <a
                            href="<?= site_url('/') ?>#how-it-works"
                            class="text-decoration-none">
                            How It Works
                        </a>
                    </li>

                    <li>
                        <a
                            href="<?= site_url('/') ?>#faqs"
                            class="text-decoration-none">
                            FAQs
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Legal -->
            <div class="col-6 col-md-4 col-lg-2 text-lg-center">
                <h2 class="fs-15 fw-semibold mb-3">
                    Legal
                </h2>

                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <a
                            href="<?= site_url(
                                        'terms-and-conditions'
                                    ) ?>"
                            class="text-decoration-none">
                            Terms &amp; Conditions
                        </a>
                    </li>

                    <li class="mb-2">
                        <a
                            href="<?= site_url(
                                        'privacy-policy'
                                    ) ?>"
                            class="text-decoration-none">
                            Privacy Policy
                        </a>
                    </li>

                    <li class="mb-2">
                        <a
                            href="<?= site_url(
                                        'grievances'
                                    ) ?>"
                            class="text-decoration-none">
                            Grievances
                        </a>
                    </li>

                    <li class="mb-2">
                        <a
                            href="<?= site_url(
                                        'fraud-alert'
                                    ) ?>"
                            class="text-decoration-none">
                            Fraud Alert
                        </a>
                    </li>

                    <li>
                        <a
                            href="<?= site_url(
                                        'cookie-policy'
                                    ) ?>"
                            class="text-decoration-none">
                            Cookie Policy
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Information -->
            <div class="col-6 col-md-4 col-lg-2 text-lg-center">
                <h2 class="fs-15 fw-semibold mb-3">
                    Information
                </h2>

                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <a
                            href="<?= site_url(
                                        'terms-and-conditions'
                                    ) ?>"
                            class="text-decoration-none">
                            About Us
                        </a>
                    </li>

                    <li class="mb-2">
                        <a
                            href="<?= site_url(
                                        'privacy-policy'
                                    ) ?>"
                            class="text-decoration-none">
                            Register Free
                        </a>
                    </li>

                    <li class="mb-2">
                        <a
                            href="<?= site_url(
                                        'privacy-policy'
                                    ) ?>"
                            class="text-decoration-none">
                            Advertise with us
                        </a>
                    </li>

                    <li class="mb-2">
                        <a
                            href="<?= site_url(
                                        'privacy-policy'
                                    ) ?>"
                            class="text-decoration-none">
                            Payment Options
                        </a>
                    </li>

                    <li>
                        <a
                            href="<?= site_url(
                                        'privacy-policy'
                                    ) ?>"
                            class="text-decoration-none">
                            Career
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Our Commitment -->
            <div class="col-12 col-md-6 col-lg-2">
                <h2 class="fs-15 fw-semibold mb-3">
                    Our Commitment
                </h2>

                <ul class="list-unstyled mb-0">
                    <li
                        class="
                            d-flex
                            align-items-start
                            gap-2
                            mb-2
                        ">

                        <i
                            class="
                                ri-shield-check-line
                                text-danger
                                fs-18
                            "
                            aria-hidden="true">
                        </i>

                        <span class="mt-1">
                            Secure profile access
                        </span>
                    </li>

                    <li
                        class="
                            d-flex
                            align-items-start
                            gap-2
                            mb-2
                        ">

                        <i
                            class="
                                ri-user-follow-line
                                text-danger
                                fs-18
                            "
                            aria-hidden="true">
                        </i>

                        <span class="mt-1">
                            Reviewed member profiles
                        </span>
                    </li>

                    <li
                        class="
                            d-flex
                            align-items-start
                            gap-2
                        ">

                        <i
                            class="
                                ri-lock-2-line
                                text-danger
                                fs-18
                            "
                            aria-hidden="true">
                        </i>

                        <span class="mt-1">
                            Privacy-focused
                        </span>
                    </li>
                </ul>
            </div>
        </div>

        <div
            class="
                d-flex
                flex-column
                flex-md-row
                align-items-md-center
                justify-content-between
                gap-2
                border-top
                border-secondary
                py-3
            ">

            <p class="fs-13 mb-0">
                &copy;
                <?= esc((string) $currentYear) ?>
                Sikhanandkaraj. All rights reserved.
            </p>

            <p class="fs-13 mb-0">
                United by Faith, Bound by Values.
            </p>
        </div>
    </div>
</footer>