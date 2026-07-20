<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Administration';
$pageScripts = $pageScripts ?? [];
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
        <?= esc($pageTitle) ?> | Sikh Anand Karaj
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
                    'assets/css/app.css'
                ) ?>">

    <link
        rel="stylesheet"
        href="<?= base_url('assets/css/custom.css') ?>">

</head>

<body>

    <?php if (
        session('admin_is_authenticated') === true
    ): ?>
        <header class="page-topbar">
            <div class="layout-width">
                <div class="navbar public-navbar">
                    <div class="container public-navbar__container">

                        <div class="d-flex align-items-center">
                            <a
                                href="<?= route_to(
                                            'admin.dashboard'
                                        ) ?>"
                                class="navbar-brand-box text-decoration-none">

                                <span class="fs-18 fw-semibold text-primary">
                                    Sikh Anand Karaj
                                </span>

                                <span
                                    class="d-block fs-11 text-muted
                                text-uppercase">
                                    Administration
                                </span>
                            </a>
                        </div>

                        <div class="d-flex align-items-center gap-2">

                            <span
                                class="d-none d-md-inline-block
                            text-muted">
                                <?= esc(
                                    session('admin_user_name')
                                ) ?>
                            </span>

                            <form
                                action="<?= route_to(
                                            'admin.logout'
                                        ) ?>"
                                method="post">
                                <?= csrf_field() ?>

                                <button
                                    type="submit"
                                    class="btn btn-soft-secondary btn-sm">
                                    <i
                                        class="ri-logout-box-r-line
                                    align-middle me-1">
                                    </i>
                                    Logout
                                </button>
                            </form>

                        </div>
                    </div>
                </div>
            </div>
        </header>
    <?php endif; ?>

    <main
        class="<?= session(
                    'admin_is_authenticated'
                ) === true
                    ? 'page-content'
                    : '' ?>">
        <?= $this->renderSection('content') ?>
    </main>

    <script
        src="<?= base_url(
                    'assets/js/bootstrap.bundle.min.js'
                ) ?>">
    </script>

    <?php
    /**
     * Load JavaScript required only by the current page.
     *
     * The controller supplies pageScripts as an array of paths relative
     * to the public directory.
     */
    $resolvedPageScripts = isset($pageScripts) && is_array($pageScripts)
        ? $pageScripts
        : [];
    ?>

    <?php foreach ($resolvedPageScripts as $script): ?>
        <?php if (is_string($script) && $script !== ''): ?>
            <script src="<?= esc(base_url($script), 'attr') ?>"></script>
        <?php endif; ?>
    <?php endforeach; ?>
</body>

</html>