<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Administration';
$pageScripts = $pageScripts ?? [];
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">

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

    <?php foreach ($pageScripts as $script): ?>
        <script
            src="<?= base_url($script) ?>">
        </script>
    <?php endforeach; ?>
</body>

</html>
