<?php

declare(strict_types=1);

use Config\Site;

/** @var Site $siteConfig */
$siteConfig = config(Site::class);

$messageText = isset($message) && is_string($message) && $message !== ''
    ? $message
    : 'You do not have permission to access this page.';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta name="robots" content="noindex, nofollow">

    <title>Access Denied | <?= esc($siteConfig->name) ?></title>

    <link
        rel="stylesheet"
        href="/assets/css/bootstrap.css"
    >

    <link
        rel="stylesheet"
        href="/assets/css/icons.css"
    >

    <link
        rel="stylesheet"
        href="/assets/css/app.css"
    >
</head>
<body class="error-page">
    <main class="error-page__main">
        <section
            class="error-page__card"
            aria-labelledby="error-title"
        >
            <div class="error-page__visual" aria-hidden="true">
                <span class="error-page__icon">
                    <i class="mdi mdi-shield-lock-outline"></i>
                </span>
            </div>

            <p class="error-page__code" aria-hidden="true">403</p>

            <h1 id="error-title" class="error-page__title">
                Access denied
            </h1>

            <p class="error-page__message">
                <?= esc($messageText) ?>
            </p>

            <div class="error-page__actions">
                <a
                    href="<?= site_url('/') ?>"
                    class="btn btn-primary"
                >
                    <i class="mdi mdi-home-outline me-1"></i>
                    Back to home
                </a>

                <button
                    type="button"
                    class="btn btn-outline-secondary"
                    onclick="history.back();"
                >
                    <i class="mdi mdi-arrow-left me-1"></i>
                    Go back
                </button>
            </div>
        </section>
    </main>

    <footer class="error-page__footer">
        &copy; <?= esc(date('Y')) ?> <?= esc($siteConfig->name) ?>
    </footer>
</body>
</html>