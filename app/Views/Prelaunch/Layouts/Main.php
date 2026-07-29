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

$siteName = 'SikhAnandKaraj';

$documentTitle = $resolvedPageTitle
    . ' | '
    . $siteName;

$homeUrl = site_url('/');

$logoUrl = base_url(
    'assets/images/sikhanandkaraj_removebg_2.png'
);

/*
 * Replace these values with the actual business contact number.
 */
$phoneDisplay = '+91 98870 05320';
$phoneDialValue = '+919887005320';
$phoneUrl = 'tel:' . $phoneDialValue;
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= base_url('assets/images/favicon/apple-touch-icon.png') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('assets/images/favicon/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= base_url('assets/images/favicon/favicon-16x16.png') ?>">
    <link rel="manifest" href="<?= base_url('assets/images/favicon/site.webmanifest') ?>">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1">


    <title>
        <?= esc($documentTitle) ?>
    </title>

    <link
        rel="stylesheet"
        href="<?= base_url('assets/css/bootstrap.css') ?>">


    <link
        rel="stylesheet"
        href="<?= base_url('assets/css/icons.css') ?>">

    <link
        rel="stylesheet"
        href="<?= base_url('assets/css/app.css') ?>">
    <link
        rel="stylesheet"
        href="<?= base_url('assets/css/custom.css') ?>">

    <?= $this->renderSection('pageStyles') ?>
</head>

<body>
    <header class="border-bottom bg-white">
        <nav
            class="navbar py-2"
            aria-label="Prelaunch profile header">
            <div
                class="container d-flex align-items-center justify-content-between">
                <a
                    class="navbar-brand
                d-inline-flex
                align-items-center
                flex-shrink-0
                m-0 p-0"
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
                    class="d-inline-flex align-items-center gap-2 text-decoration-none fw-semibold">
                    <i
                        class="ri-phone-line"
                        aria-hidden="true"></i>

                    <span>
                        <?= esc($phoneDisplay) ?>
                    </span>
                </a>
            </div>
        </nav>
    </header>

    <main>
        <?= $this->renderSection('content') ?>
    </main>

    <script src="<?= base_url('assets/js/jquery.js') ?>"></script>
    <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/choices.min.js') ?>"></script>

    <script src="<?= base_url(
                        'assets/js/components/select-choice.js'
                    ) ?>"></script>
    <script src="<?= base_url(
                        'assets/js/components/form-validator.js'
                    ) ?>"></script>
    <script src="<?= base_url('assets/js/app.js') ?>"></script>
    <script src="<?= base_url('assets/js/components/feedback-modal.js') ?>"></script>
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
                    ) ?>"></script>
    <?php endforeach ?>

    <?= $this->renderSection('pageScripts') ?>
</body>

</html>