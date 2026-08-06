<?php

declare(strict_types=1);

$currentYear = date('Y');
?>

<footer class="section py-5">
    <div class="container">
        <div class="row pb-4">
            <div class="col-12 col-lg-5">
                <a
                    class="navbar-brand
                d-inline-flex
                align-items-center
                flex-shrink-0
                m-0 p-0"
                    href="<?= site_url('/') ?>"
                    aria-label="Sikhanandkaraj home">

                    <img
                        src="<?= base_url(
                                    'assets/images/sikhanandkaraj_removebg_2.png'
                                ) ?>"
                        alt="Sikhanandkaraj"
                        class="public-navbar__logo">
                </a>

                <p
                    class="
                        lh-lg
                        mb-0">

                    A secure Sikh matrimonial platform helping
                    individuals and families discover meaningful
                    relationships rooted in faith and shared values.
                </p>
                <p class="lh-lg mb-3 color-pink">

                    This website is strictly for matrimonial purpose only and not a dating website.
                </p>
            </div>

            <div class="col-6 col-md-3 col-lg-2">
                <h2 class="fs-15 fw-semibold  mb-3">
                    Explore
                </h2>

                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <a
                            href="<?= site_url('/') ?>"
                            class="
                                text-decoration-none">

                            Home
                        </a>
                    </li>

                    <li class="mb-2">
                        <a
                            href="<?= site_url('login') ?>"
                            class="
                                text-decoration-none">

                            Login
                        </a>
                    </li>

                    <li class="mb-2">
                        <a
                            href="<?= site_url('/') ?>#how-it-works"
                            class="
                                text-decoration-none">

                            How It Works
                        </a>
                    </li>

                    <li>
                        <a
                            href="<?= site_url('/') ?>#how-it-works"
                            class="
                                text-decoration-none">

                            FAQs
                        </a>
                    </li>
                </ul>
            </div>

            <div class="col-6 col-md-3 col-lg-2">
                <h2 class="fs-15 fw-semibold  mb-3">
                    Legal
                </h2>

                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <a
                            href="<?= site_url(
                                        'terms-and-conditions'
                                    ) ?>"
                            class="
                                text-decoration-none">

                            Terms &amp; Conditions
                        </a>
                    </li>

                    <li class="mb-2">
                        <a
                            href="<?= site_url(
                                        'privacy-policy'
                                    ) ?>"
                            class="
                                text-decoration-none">

                            Privacy Policy
                        </a>
                    </li>

                    <li class="mb-2">
                        <a
                            href="<?= site_url(
                                        'privacy-policy'
                                    ) ?>"
                            class="
                                text-decoration-none">

                            Grievances
                        </a>
                    </li>

                    <li class="mb-2">
                        <a
                            href="<?= site_url(
                                        'privacy-policy'
                                    ) ?>"
                            class="
                                text-decoration-none">

                            Fraud Alert
                        </a>
                    </li>

                    <li>
                        <a
                            href="<?= site_url(
                                        'privacy-policy'
                                    ) ?>"
                            class="
                                text-decoration-none">

                            Cookie Policy
                        </a>
                    </li>
                </ul>
            </div>

            <div class="col-6 col-md-3 col-lg-2">
                <h2 class="fs-15 fw-semibold  mb-3">
                    Information
                </h2>

                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <a
                            href="<?= site_url(
                                        'terms-and-conditions'
                                    ) ?>"
                            class="
                                text-decoration-none">

                            About Us
                        </a>
                    </li>

                    <li class="mb-2">
                        <a
                            href="<?= site_url(
                                        'privacy-policy'
                                    ) ?>"
                            class="
                                text-decoration-none">

                            Register Free
                        </a>
                    </li>

                    <li class="mb-2">
                        <a
                            href="<?= site_url(
                                        'privacy-policy'
                                    ) ?>"
                            class="
                                text-decoration-none">

                            Advertise with us
                        </a>
                    </li>

                    <li class="mb-2">
                        <a
                            href="<?= site_url(
                                        'privacy-policy'
                                    ) ?>"
                            class="
                                text-decoration-none">

                            Payment Options
                        </a>
                    </li>

                    <li>
                        <a
                            href="<?= site_url(
                                        'privacy-policy'
                                    ) ?>"
                            class="
                                text-decoration-none">

                            Career
                        </a>
                    </li>
                </ul>
            </div>

            <div class="col-12 col-md-3 col-lg-3">
                <h2 class="fs-15 fw-semibold  mb-3">
                    Our Commitment
                </h2>

                <ul class="list-unstyled mb-0">
                    <li
                        class="d-flex
                            align-items-center
                            gap-2
                            
                            mb-2">

                        <i
                            class="ri-shield-check-line
                                text-danger
                                fs-18"
                            aria-hidden="true">
                        </i>

                        Secure profile access
                    </li>

                    <li
                        class="d-flex
                            align-items-center
                            gap-2
                            
                            mb-2">

                        <i
                            class="ri-user-follow-line
                                text-danger
                                fs-18"
                            aria-hidden="true">
                        </i>

                        Reviewed member profiles
                    </li>

                    <li
                        class="d-flex
                            align-items-center
                            gap-2
                            ">

                        <i
                            class="ri-lock-2-line
                                text-danger
                                fs-18"
                            aria-hidden="true">
                        </i>

                        Privacy-focused experience
                    </li>
                </ul>
            </div>
        </div>

        <div
            class="d-flex
                flex-column
                flex-md-row
                align-items-md-center
                justify-content-between
                gap-2
                border-top
                border-secondary
                py-3">

            <p class=" fs-13 mb-0">
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