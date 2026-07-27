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
        <?= esc($pageTitle) ?> | SikhAnandKaraj
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
        <?php
        $currentPath = trim(
            service('uri')->getPath(),
            '/'
        );

        $dashboardActive =
            $currentPath === 'admin/dashboard';

        $pendingApprovalActive =
            str_starts_with(
                $currentPath,
                'admin/members/photo-approvals'
            );

        $administratorActive =
            str_starts_with(
                $currentPath,
                'admin/users'
            );

        $isSuperAdmin =
            session('admin_role')
            === \App\Models\AdminUserModel::ROLE_SUPER_ADMIN;
        ?>

        <header
            id="page-topbar"
            class="position-static border-bottom">

            <nav
                class="navbar navbar-expand-lg bg-white py-2"
                aria-label="Administrator navigation">

                <div class="container-fluid px-3 px-lg-4">

                    <a
                        href="<?= route_to(
                                    'admin.dashboard'
                                ) ?>"
                        class="navbar-brand
                        text-decoration-none me-lg-3 py-0">

                        <span
                            class="d-block fs-18
                            fw-semibold text-primary">
                            SikhAnandKaraj
                        </span>

                        <span
                            class="d-block fs-11
                            text-muted text-uppercase">
                            Administration
                        </span>
                    </a>

                    <button
                        type="button"
                        class="navbar-toggler"
                        data-bs-toggle="collapse"
                        data-bs-target="#adminNavbar"
                        aria-controls="adminNavbar"
                        aria-expanded="false"
                        aria-label="Toggle administrator navigation">

                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <div
                        class="collapse navbar-collapse"
                        id="adminNavbar">

                        <ul
                            class="navbar-nav
            nav-underline
            mx-lg-auto
            gap-2
            mt-2 mt-lg-0">

                            <li class="nav-item">
                                <a
                                    href="<?= route_to(
                                                'admin.dashboard'
                                            ) ?>"
                                    class="nav-link
                    d-flex align-items-center
                    gap-2
                    py-1 py-lg-2
                    <?= $dashboardActive
                        ? 'active text-primary'
                        : '' ?>"
                                    <?= $dashboardActive
                                        ? 'aria-current="page"'
                                        : '' ?>>

                                    <i
                                        class="ri-layout-grid-line
                        fw-normal flex-shrink-0"
                                        aria-hidden="true">
                                    </i>

                                    <span
                                        class="<?= $dashboardActive
                                                    ? 'fw-semibold'
                                                    : '' ?>">
                                        Dashboard
                                    </span>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a
                                    href="<?= route_to(
                                                'admin.members'
                                                    . '.photo-approvals'
                                            ) ?>"
                                    class="nav-link
                    d-flex align-items-center
                    gap-2
                    py-1 py-lg-2
                    <?= $pendingApprovalActive
                        ? 'active text-primary'
                        : '' ?>"
                                    <?= $pendingApprovalActive
                                        ? 'aria-current="page"'
                                        : '' ?>>

                                    <i
                                        class="ri-image-line
                        fw-normal flex-shrink-0"
                                        aria-hidden="true">
                                    </i>

                                    <span
                                        class="<?= $pendingApprovalActive
                                                    ? 'fw-semibold'
                                                    : '' ?>">
                                        Pending Approval
                                    </span>
                                </a>
                            </li>

                            <?php if ($isSuperAdmin): ?>
                                <li class="nav-item">
                                    <a
                                        href="<?= route_to(
                                                    'admin.users.index'
                                                ) ?>"
                                        class="nav-link
                        d-flex align-items-center
                        gap-2
                        py-1 py-lg-2
                        <?= $administratorActive
                                    ? 'active text-primary'
                                    : '' ?>"
                                        <?= $administratorActive
                                            ? 'aria-current="page"'
                                            : '' ?>>

                                        <i
                                            class="ri-user-settings-line
                            fw-normal flex-shrink-0"
                                            aria-hidden="true">
                                        </i>

                                        <span
                                            class="<?= $administratorActive
                                                        ? 'fw-semibold'
                                                        : '' ?>">
                                            Administrators
                                        </span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>

                        <div
                            class="d-flex flex-column
            flex-lg-row
            align-items-lg-center
            gap-2
            mt-2 mt-lg-0">

                            <span
                                class="text-muted
                text-truncate
                mw-100
                py-1 py-lg-0">

                                <i
                                    class="ri-user-line
                    fw-normal
                    align-middle me-1"
                                    aria-hidden="true">
                                </i>

                                <?= esc(
                                    (string) session(
                                        'admin_user_name'
                                    )
                                ) ?>
                            </span>

                            <form
                                action="<?= route_to(
                                            'admin.logout'
                                        ) ?>"
                                method="post"
                                class="mb-0">

                                <?= csrf_field() ?>

                                <button
                                    type="submit"
                                    class="btn
                    btn-soft-secondary
                    btn-sm w-100">

                                    <i
                                        class="ri-logout-box-r-line
                        fw-normal
                        align-middle me-1"
                                        aria-hidden="true">
                                    </i>

                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </nav>
        </header>
    <?php endif; ?>

    <?php $isAuthenticated =
        session('admin_is_authenticated') === true;
    ?>

    <main
        class="<?= $isAuthenticated
                    ? 'page-content py-4'
                    : '' ?>">

        <?= $this->renderSection('content') ?>
    </main>
    <?= view('Components/FeedbackModal') ?>
    <?= view('Components/ConfirmationModal') ?>
    <script
        src="<?= base_url(
                    'assets/js/bootstrap.bundle.min.js'
                ) ?>">
    </script>
    <script src="<?= base_url('assets/js/components/feedback-modal.js') ?>"></script>
    <script
        src="<?= base_url(
                    'assets/js/components/confirmation-modal.js'
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