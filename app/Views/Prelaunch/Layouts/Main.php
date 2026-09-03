<?php

declare(strict_types=1);

/**
 * Standalone layout used by the public prelaunch profile form.
 *
 * @var string|null             $pageTitle
 * @var array<int, string>|null $pageScripts
 */

$resolvedPageTitle = trim(
    (string) ($pageTitle ?? '')
);

if ($resolvedPageTitle === '') {
    $resolvedPageTitle = 'Pre-launch Profile';
}

$scriptFiles = is_array($pageScripts ?? null)
    ? $pageScripts
    : [];

$siteName = 'Sikhanandkaraj';

$documentTitle = $resolvedPageTitle
    . ' | '
    . $siteName;

$homeUrl = site_url('/prelaunch/profile');

$logoUrl = base_url(
    'assets/images/logo_sak_header.png'
);

/*
 * Replace these values with the actual business contact number.
 */
$phoneDisplay = '+91 9887711226';
$phoneDialValue = '+919887711226';
$phoneUrl = 'tel:' . $phoneDialValue;
?>

<!doctype html>
<html lang="en">

<head>

    <meta charset="utf-8">

    <link
        rel="apple-touch-icon"
        sizes="180x180"
        href="<?= base_url(
                    'assets/images/favicon/apple-touch-icon.png'
                ) ?>">

    <link
        rel="icon"
        type="image/png"
        sizes="32x32"
        href="<?= base_url(
                    'assets/images/favicon/favicon-32x32.png'
                ) ?>">

    <link
        rel="icon"
        type="image/png"
        sizes="16x16"
        href="<?= base_url(
                    'assets/images/favicon/favicon-16x16.png'
                ) ?>">

    <link
        rel="manifest"
        href="<?= base_url(
                    'assets/images/favicon/site.webmanifest'
                ) ?>">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1">

    <meta name="robots" content="noindex, nofollow, noarchive">

    <title>
        <?= esc($documentTitle) ?>
    </title>

    <link
        rel="stylesheet"
        href="<?= base_url(
                    'assets/css/bootstrap.css'
                ) ?>">

    <link
        rel="stylesheet"
        href="<?= base_url(
                    'assets/css/icons.css'
                ) ?>">

    <link
        rel="stylesheet"
        href="<?= base_url(
                    'assets/vendor/air-datepicker/air-datepicker.css'
                ) ?>">

    <link
        rel="stylesheet"
        href="<?= base_url(
                    'assets/css/app.css'
                ) ?>">

    <link
        rel="stylesheet"
        href="<?= base_url(
                    'assets/css/custom.css'
                ) ?>">

    <?= $this->renderSection('pageStyles') ?>

</head>

<body>

    <header class="public-header">

        <nav
            class="navbar public-navbar"
            aria-label="Prelaunch profile header">

            <div
                class="
                    container
                    d-flex
                    align-items-center
                    justify-content-between
                ">

                <a
                    class="
                        navbar-brand
                        d-inline-flex
                        align-items-center
                        flex-shrink-0
                        m-0
                        p-0
                    "
                    href="<?= esc(
                                $homeUrl,
                                'attr'
                            ) ?>"
                    aria-label="<?= esc(
                                    $siteName,
                                    'attr'
                                ) ?>">

                    <img
                        src="<?= esc(
                                    $logoUrl,
                                    'attr'
                                ) ?>"
                        alt="<?= esc(
                                    $siteName,
                                    'attr'
                                ) ?>"
                        class="public-navbar__logo">

                </a>

                <a
                    href="<?= esc(
                                $phoneUrl,
                                'attr'
                            ) ?>"
                    class="public-navbar__phone"
                    aria-label="Call Sikhanandkaraj at <?= esc(
                                                            $phoneDisplay
                                                        ) ?>">

                    <span
                        class="
                            mdi
                            mdi-phone-outline
                            public-navbar__phone-icon
                        "
                        aria-hidden="true">
                    </span>

                    <span class="public-navbar__phone-number">
                        <?= esc($phoneDisplay) ?>
                    </span>

                </a>

            </div>

        </nav>

    </header>

    <main class="light-yellowish">
        <?= $this->renderSection('content') ?>
    </main>

    <footer
        class="
            pt-4
            border-top
            border-secondary-subtle
            bg-light
        ">

        <div class="container py-3 pt-0">

            <div class="row g-4">

                <div class="col-12 col-md-6 col-xl-3">

                    <div class="
                        d-flex
                        trust-feature
                        align-items-center
                        gap-3
                    ">

                        <i class="
                            ri-shield-check-line
                            fs-3
                        "></i>

                        <div>

                            <div class="fw-semibold fs-14">
                                Secure & Trusted
                            </div>

                            <small class="text-muted">
                                Your safety is our priority
                            </small>

                        </div>

                    </div>

                </div>

                <div class="col-12 col-md-6 col-xl-3">

                    <div class="
                        d-flex
                        trust-feature
                        align-items-center
                        gap-3
                    ">

                        <i class="
                            ri-group-line
                            fs-3
                        "></i>

                        <div>

                            <div class="fw-semibold fs-14">
                                Family Oriented
                            </div>

                            <small class="text-muted">
                                Built for families, by families
                            </small>

                        </div>

                    </div>

                </div>

                <div class="col-12 col-md-6 col-xl-3">

                    <div class="
                        d-flex
                        trust-feature
                        align-items-center
                        gap-3
                    ">

                        <i class="
                            ri-heart-line
                            fs-3
                        "></i>

                        <div>

                            <div class="fw-semibold fs-14">
                                Smart Matches
                            </div>

                            <small class="text-muted">
                                AI powered better matches
                            </small>

                        </div>

                    </div>

                </div>

                <div class="col-12 col-md-6 col-xl-3">

                    <div class="
                        d-flex
                        trust-feature
                        align-items-center
                        gap-3
                    ">

                        <i class="
                            ri-shield-check-line
                            fs-3
                        "></i>

                        <div>

                            <div class="fw-semibold fs-14">
                                Verified Profiles
                            </div>

                            <small class="text-muted">
                                100% verified for trust
                            </small>

                        </div>

                    </div>

                </div>

            </div>

            <hr class="my-4">

            <div class="
                d-flex
                flex-column
                flex-md-row
                align-items-center
                justify-content-center
                gap-2
            ">

                <small class="text-muted">
                    © <?= esc(date('Y')) ?>
                    Sikhanandkaraj. All rights reserved.
                </small>

                <?= view(
                    'Components/ReleaseVersion'
                ) ?>

            </div>

        </div>

    </footer>

    <script
        src="<?= base_url(
                    'assets/js/jquery.js'
                ) ?>">
    </script>

    <script
        src="<?= base_url(
                    'assets/js/bootstrap.bundle.min.js'
                ) ?>">
    </script>

    <script
        src="<?= base_url(
                    'assets/vendor/air-datepicker/air-datepicker.js'
                ) ?>">
    </script>

    <script
        src="<?= base_url(
                    'assets/js/choices.min.js'
                ) ?>">
    </script>

    <script
        src="<?= base_url(
                    'assets/js/components/select-choice.js'
                ) ?>">
    </script>

    <script
        src="<?= base_url(
                    'assets/js/components/form-validator.js'
                ) ?>">
    </script>

    <script
        src="<?= base_url(
                    'assets/js/app.js'
                ) ?>">
    </script>

    <script
        src="<?= base_url(
                    'assets/js/components/feedback-modal.js'
                ) ?>">
    </script>

    <script
        src="<?= base_url(
                    'assets/js/components/confirmation-modal.js'
                ) ?>">
    </script>

    <?php foreach ($scriptFiles as $scriptFile): ?>
        <?php
        $normalizedScriptFile = trim(
            (string) $scriptFile
        );

        if ($normalizedScriptFile === '') {
            continue;
        }

        $scriptUrl = base_url(
            $normalizedScriptFile
        );
        ?>

        <script
            src="<?= esc(
                        $scriptUrl,
                        'attr'
                    ) ?>">
        </script>

    <?php endforeach ?>

    <?= $this->renderSection('pageScripts') ?>

</body>

</html>