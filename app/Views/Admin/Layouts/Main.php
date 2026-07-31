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

        $fieldOfficerActive =
            str_starts_with(
                $currentPath,
                'admin/field-officers'
            );

        $prelaunchProfileActive =
            str_starts_with(
                $currentPath,
                'admin/prelaunch/profiles'
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
                        href="<?= route_to('admin.dashboard') ?>"
                        class="navbar-brand
        d-inline-flex
        align-items-center
        flex-shrink-0
        me-lg-3
        m-0
        p-0
        text-decoration-none"
                        aria-label="Administrator dashboard">

                        <img
                            src="<?= base_url(
                                        'assets/images/sikhanandkaraj_removebg_2.png'
                                    ) ?>"
                            alt="SikhAnandKaraj"
                            class="public-navbar__logo">
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
                            <li class="nav-item">
                                <a
                                    href="<?= route_to(
                                                'admin.prelaunch.profiles.index'
                                            ) ?>"
                                    class="nav-link
        d-flex align-items-center
        gap-2
        py-1 py-lg-2
        <?= $prelaunchProfileActive
            ? 'active text-primary'
            : '' ?>"
                                    <?= $prelaunchProfileActive
                                        ? 'aria-current="page"'
                                        : '' ?>>

                                    <i
                                        class="ri-profile-line
            fw-normal flex-shrink-0"
                                        aria-hidden="true">
                                    </i>

                                    <span
                                        class="<?= $prelaunchProfileActive
                                                    ? 'fw-semibold'
                                                    : '' ?>">
                                        Pre-launch Profiles
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
                                <li class="nav-item">
                                    <a
                                        href="<?= route_to(
                                                    'admin.field-officers.index'
                                                ) ?>"
                                        class="nav-link
            d-flex align-items-center
            gap-2
            py-1 py-lg-2
            <?= $fieldOfficerActive
                                    ? 'active text-primary'
                                    : '' ?>"
                                        <?= $fieldOfficerActive
                                            ? 'aria-current="page"'
                                            : '' ?>>

                                        <i
                                            class="ri-user-location-line
                fw-normal flex-shrink-0"
                                            aria-hidden="true">
                                        </i>

                                        <span
                                            class="<?= $fieldOfficerActive
                                                        ? 'fw-semibold'
                                                        : '' ?>">
                                            Field Officers
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

    <footer class="mt-5 pt-4 border-top border-secondary-subtle bg-light">

        <div class="container py-3 pt-0">

            <div class="row g-4">

                <div class="col-12 col-md-6 col-xl-3">

                    <div class="d-flex
    align-items-center
    justify-content-center
    gap-3">

                        <i class="ri-shield-check-line fs-3"></i>

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

                    <div class="d-flex
    align-items-center
    justify-content-center
    gap-3">

                        <i class="ri-group-line fs-3"></i>

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

                    <div class="d-flex
    align-items-center
    justify-content-center
    gap-3">

                        <i class="ri-heart-line fs-3"></i>

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

                    <div class="d-flex
    align-items-center
    justify-content-center
    gap-3">

                        <i class="ri-shield-check-line fs-3"></i>

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

            <div class="text-center">

                <small class="text-muted">
                    © <?= esc(date('Y')) ?> SikhAnandKaraj. All rights reserved.
                </small>

            </div>

        </div>

    </footer>
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
    <script src="<?= base_url('assets/js/choices.min.js') ?>"></script>

    <script src="<?= base_url(
                        'assets/js/components/select-choice.js'
                    ) ?>"></script>
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