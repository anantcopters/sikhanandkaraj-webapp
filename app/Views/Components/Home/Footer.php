<?php

declare(strict_types=1);

$currentYear = date('Y');
?>

<footer class="bg-dark text-white pt-5">
    <div class="container">
        <div class="row g-4 pb-4">
            <div class="col-12 col-lg-5">
                <a
                    href="<?= site_url('/') ?>"
                    class="d-inline-block
                        text-white
                        fs-24
                        fw-bold
                        text-decoration-none
                        mb-3">

                    SikhAnandKaraj
                </a>

                <p
                    class="text-white-50
                        lh-lg
                        mb-0">

                    A secure Sikh matrimonial platform helping
                    individuals and families discover meaningful
                    relationships rooted in faith and shared values.
                </p>
            </div>

            <div class="col-6 col-md-4 col-lg-2">
                <h2 class="fs-15 fw-semibold text-white mb-3">
                    Explore
                </h2>

                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <a
                            href="<?= site_url('/') ?>"
                            class="text-white-50
                                text-decoration-none">

                            Home
                        </a>
                    </li>

                    <li class="mb-2">
                        <a
                            href="<?= site_url('login') ?>"
                            class="text-white-50
                                text-decoration-none">

                            Login
                        </a>
                    </li>

                    <li>
                        <a
                            href="<?= site_url('/') ?>#how-it-works"
                            class="text-white-50
                                text-decoration-none">

                            How It Works
                        </a>
                    </li>
                </ul>
            </div>

            <div class="col-6 col-md-4 col-lg-2">
                <h2 class="fs-15 fw-semibold text-white mb-3">
                    Legal
                </h2>

                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <a
                            href="<?= site_url(
                                        'terms-and-conditions'
                                    ) ?>"
                            class="text-white-50
                                text-decoration-none">

                            Terms &amp; Conditions
                        </a>
                    </li>

                    <li>
                        <a
                            href="<?= site_url(
                                        'privacy-policy'
                                    ) ?>"
                            class="text-white-50
                                text-decoration-none">

                            Privacy Policy
                        </a>
                    </li>
                </ul>
            </div>

            <div class="col-12 col-md-4 col-lg-3">
                <h2 class="fs-15 fw-semibold text-white mb-3">
                    Our Commitment
                </h2>

                <ul class="list-unstyled mb-0">
                    <li
                        class="d-flex
                            align-items-center
                            gap-2
                            text-white-50
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
                            text-white-50
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
                            text-white-50">

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

            <p class="text-white-50 fs-13 mb-0">
                &copy;
                <?= esc((string) $currentYear) ?>
                SikhAnandKaraj. All rights reserved.
            </p>

            <p class="text-white-50 fs-13 mb-0">
                United by faith, bound by values.
            </p>
        </div>
    </div>
</footer>