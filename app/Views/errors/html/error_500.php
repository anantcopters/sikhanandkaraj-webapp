<?php

declare(strict_types=1);

use Config\Site;

/** @var Site $siteConfig */
$siteConfig = config(Site::class);
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

    <title>Server Error | <?= esc($siteConfig->name) ?></title>

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
                    <i class="mdi mdi-server-off"></i>
                </span>
            </div>

            <p class="error-page__code" aria-hidden="true">500</p>

            <h1 id="error-title" class="error-page__title">
                Something went wrong
            </h1>

            <p class="error-page__message">
                We could not complete your request because of an unexpected
                server error. Please try again after a few moments.
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
                    onclick="location.reload();"
                >
                    <i class="mdi mdi-refresh me-1"></i>
                    Try again
                </button>
            </div>
        </section>
    </main>

    <footer class="error-page__footer">
        &copy; <?= esc(date('Y')) ?> <?= esc($siteConfig->name) ?>
    </footer>
</body>
</html>