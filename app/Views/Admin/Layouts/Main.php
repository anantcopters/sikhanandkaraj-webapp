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

<body class="admin-body">

    <?php if (
        session('admin_is_authenticated') === true
    ): ?>
        <header class="admin-header">
            <div class="container-fluid">
                <div
                    class="d-flex align-items-center
                        justify-content-between gap-3">

                    <a
                        href="<?= route_to(
                                    'admin.dashboard'
                                ) ?>"
                        class="admin-brand">
                        Sikh Anand Karaj
                        <span>Administration</span>
                    </a>

                    <div
                        class="d-flex align-items-center gap-3">

                        <span
                            class="d-none d-md-inline text-muted">
                            <?= esc(
                                session(
                                    'admin_user_name'
                                )
                            ) ?>
                        </span>

                        <form
                            action="<?= route_to(
                                        'admin.logout'
                                    ) ?>"
                            method="post">
                            <?= csrf_field() ?>

                            <button
                                class="btn btn-outline-secondary
                                    btn-sm"
                                type="submit">
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>
    <?php endif; ?>

    <main>
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