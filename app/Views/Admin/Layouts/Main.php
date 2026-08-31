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
        <?= esc($pageTitle) ?> | Sikhanandkaraj
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

        /*
     * Individual destination states.
     */
        $dashboardActive =
            $currentPath
            === 'admin/dashboard';

        $photoApprovalsActive =
            str_starts_with(
                $currentPath,
                'admin/members/photo-approvals'
            );

        $aadhaarApprovalsActive =
            str_starts_with(
                $currentPath,
                'admin/members/aadhaar-approvals'
            );

        $videoIntroductionApprovalsActive =
            str_starts_with(
                $currentPath,
                'admin/video-introductions'
            );

        $prelaunchProfilesActive =
            str_starts_with(
                $currentPath,
                'admin/prelaunch/profiles'
            );

        $membersActive =
            str_starts_with(
                $currentPath,
                'admin/members'
            )
            && !$photoApprovalsActive
            && !$aadhaarApprovalsActive
            && !$videoIntroductionApprovalsActive;

        $administratorsActive =
            str_starts_with(
                $currentPath,
                'admin/users'
            );

        $matchScoreActive =
            str_starts_with(
                $currentPath,
                'admin/match-score'
            );

        $emailPreviewActive =
            str_starts_with(
                $currentPath,
                'admin/email-preview'
            );

        $communicationOperationsActive =
            str_starts_with(
                $currentPath,
                'admin/users/communication-operations'
            )
            || str_starts_with(
                $currentPath,
                'admin/communication-operations'
            );

        $sakVolunteersActive =
            str_starts_with(
                $currentPath,
                'admin/field-officers'
            );

        /*
        * Parent dropdown states.
        */
        $memberGroupActive =
            $membersActive
            || $prelaunchProfilesActive
            || $sakVolunteersActive;

        $approvalGroupActive =
            $photoApprovalsActive
            || $aadhaarApprovalsActive
            || $videoIntroductionApprovalsActive;

        $administrationGroupActive =
            $administratorsActive
            || $matchScoreActive
            || $emailPreviewActive
            || $communicationOperationsActive;

        $profileReportsActive =
            str_starts_with(
                $currentPath,
                'admin/support/profile-reports'
            );

        $contactRequestsActive =
            str_starts_with(
                $currentPath,
                'admin/support/contact-requests'
            );

        $supportGroupActive =
            $profileReportsActive
            || $contactRequestsActive;

        $isSuperAdmin =
            session('admin_role')
            === \App\Models\AdminUserModel
            ::ROLE_SUPER_ADMIN;

        $adminUserName = trim(
            (string) session(
                'admin_user_name'
            )
        );

        if ($adminUserName === '') {
            $adminUserName =
                'Administrator';
        }

        $adminRoleLabel =
            $isSuperAdmin
            ? 'Super Administrator'
            : 'Administrator';
        ?>

        <header
            id="page-topbar"
            class="position-static border-bottom">

            <nav
                class="navbar
                navbar-expand-lg
                bg-white
                py-2"
                aria-label="Administrator navigation">

                <div class="container-fluid px-3 px-lg-4">

                    <!-- Application logo -->
                    <a
                        href="<?= route_to(
                                    'admin.dashboard'
                                ) ?>"
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
                                        'assets/images/'
                                            . 'logo_sak_bgremove_final.png'
                                    ) ?>"
                            alt="Sikhanandkaraj"
                            class="public-navbar__logo">
                    </a>

                    <!-- Mobile navigation toggle -->
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

                        <!-- Primary navigation -->
                        <ul
                            class="navbar-nav
                            nav-underline
                            mx-lg-auto
                            gap-lg-2
                            mt-2
                            mt-lg-0">

                            <!-- Dashboard -->
                            <li class="nav-item">
                                <a
                                    href="<?= route_to(
                                                'admin.dashboard'
                                            ) ?>"
                                    class="nav-link
                                    d-flex
                                    align-items-center
                                    gap-2
                                    py-2
                                    <?= $dashboardActive
                                        ? 'active text-primary'
                                        : '' ?>"
                                    <?= $dashboardActive
                                        ? 'aria-current="page"'
                                        : '' ?>>

                                    <i
                                        class="ri-layout-grid-line
                                        fw-normal
                                        flex-shrink-0"
                                        aria-hidden="true"></i>

                                    <span
                                        class="<?= $dashboardActive
                                                    ? 'fw-semibold'
                                                    : '' ?>">

                                        Dashboard
                                    </span>
                                </a>
                            </li>

                            <!-- Members dropdown -->
                            <li class="nav-item dropdown">
                                <a
                                    href="#"
                                    class="nav-link
                                    dropdown-toggle
                                    d-flex
                                    align-items-center
                                    gap-2
                                    py-2
                                    <?= $memberGroupActive
                                        ? 'active text-primary fw-semibold'
                                        : '' ?>"
                                    id="adminMembersDropdown"
                                    role="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                    <?= $memberGroupActive
                                        ? 'aria-current="page"'
                                        : '' ?>>

                                    <i
                                        class="ri-team-line
                                        fw-normal
                                        flex-shrink-0"
                                        aria-hidden="true"></i>

                                    <span>Members</span>
                                </a>

                                <ul
                                    class="dropdown-menu"
                                    aria-labelledby="adminMembersDropdown">

                                    <li>
                                        <a
                                            href="<?= route_to(
                                                        'admin.members.index'
                                                    ) ?>"
                                            class="dropdown-item
                                            d-flex
                                            align-items-center
                                            gap-2
                                            <?= $membersActive
                                                ? 'active'
                                                : '' ?>"
                                            <?= $membersActive
                                                ? 'aria-current="page"'
                                                : '' ?>>

                                            <i
                                                class="ri-team-line"
                                                aria-hidden="true"></i>

                                            All Members
                                        </a>
                                    </li>

                                    <li>
                                        <a
                                            href="<?= route_to(
                                                        'admin.prelaunch.'
                                                            . 'profiles.index'
                                                    ) ?>"
                                            class="dropdown-item
                                            d-flex
                                            align-items-center
                                            gap-2
                                            <?= $prelaunchProfilesActive
                                                ? 'active'
                                                : '' ?>"
                                            <?= $prelaunchProfilesActive
                                                ? 'aria-current="page"'
                                                : '' ?>>

                                            <i
                                                class="ri-profile-line"
                                                aria-hidden="true"></i>

                                            Pre-launch Profiles
                                        </a>
                                    </li>
                                    <li>
                                        <a
                                            href="<?= route_to(
                                                        'admin.'
                                                            . 'field-officers.index'
                                                    ) ?>"
                                            class="dropdown-item
                                                d-flex
                                                align-items-center
                                                gap-2
                                                <?= $sakVolunteersActive
                                                    ? 'active'
                                                    : '' ?>"
                                            <?= $sakVolunteersActive
                                                ? 'aria-current="page"'
                                                : '' ?>>

                                            <i
                                                class="ri-user-location-line"
                                                aria-hidden="true"></i>

                                            SAK Volunteers
                                        </a>
                                    </li>
                                </ul>
                            </li>

                            <!-- Approvals dropdown -->
                            <li class="nav-item dropdown">
                                <a
                                    href="#"
                                    class="nav-link
                                    dropdown-toggle
                                    d-flex
                                    align-items-center
                                    gap-2
                                    py-2
                                    <?= $approvalGroupActive
                                        ? 'active text-primary fw-semibold'
                                        : '' ?>"
                                    id="adminApprovalsDropdown"
                                    role="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                    <?= $approvalGroupActive
                                        ? 'aria-current="page"'
                                        : '' ?>>

                                    <i
                                        class="ri-checkbox-circle-line
                                        fw-normal
                                        flex-shrink-0"
                                        aria-hidden="true"></i>

                                    <span>Approvals</span>
                                </a>

                                <ul
                                    class="dropdown-menu"
                                    aria-labelledby="adminApprovalsDropdown">

                                    <li>
                                        <a
                                            href="<?= route_to(
                                                        'admin.members.'
                                                            . 'photo-approvals'
                                                    ) ?>"
                                            class="dropdown-item
                                            d-flex
                                            align-items-center
                                            gap-2
                                            <?= $photoApprovalsActive
                                                ? 'active'
                                                : '' ?>"
                                            <?= $photoApprovalsActive
                                                ? 'aria-current="page"'
                                                : '' ?>>

                                            <i
                                                class="ri-image-line"
                                                aria-hidden="true"></i>

                                            Photo Approvals
                                        </a>
                                    </li>

                                    <li>
                                        <a
                                            href="<?= route_to(
                                                        'admin.members.'
                                                            . 'aadhaar-approvals'
                                                    ) ?>"
                                            class="dropdown-item
                                            d-flex
                                            align-items-center
                                            gap-2
                                            <?= $aadhaarApprovalsActive
                                                ? 'active'
                                                : '' ?>"
                                            <?= $aadhaarApprovalsActive
                                                ? 'aria-current="page"'
                                                : '' ?>>

                                            <i
                                                class="ri-fingerprint-line"
                                                aria-hidden="true"></i>

                                            Aadhaar Approvals
                                        </a>
                                    </li>
                                    <li>
                                        <a
                                            href="<?= route_to(
                                                        'admin.members.video-introductions'
                                                    ) ?>"
                                            class="dropdown-item d-flex
            align-items-center gap-2
            <?= $videoIntroductionApprovalsActive
                ? 'active'
                : '' ?>"
                                            <?= $videoIntroductionApprovalsActive
                                                ? 'aria-current="page"'
                                                : '' ?>>

                                            <i
                                                class="ri-video-line"
                                                aria-hidden="true">
                                            </i>

                                            Video Introductions
                                        </a>
                                    </li>
                                </ul>
                            </li>

                            <!-- Member Support dropdown -->
                            <li class="nav-item dropdown">
                                <a
                                    href="#"
                                    class="nav-link
            dropdown-toggle
            d-flex
            align-items-center
            gap-2
            py-2
            <?= $supportGroupActive
                ? 'active text-primary fw-semibold'
                : '' ?>"
                                    id="adminSupportDropdown"
                                    role="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                    <?= $supportGroupActive
                                        ? 'aria-current="page"'
                                        : '' ?>>

                                    <i
                                        class="ri-customer-service-2-line
                fw-normal
                flex-shrink-0"
                                        aria-hidden="true">
                                    </i>

                                    <span>Member Support</span>
                                </a>

                                <ul
                                    class="dropdown-menu"
                                    aria-labelledby="adminSupportDropdown">

                                    <li>
                                        <a
                                            href="<?= route_to(
                                                        'admin.support.reports'
                                                    ) ?>"
                                            class="dropdown-item
                    d-flex
                    align-items-center
                    gap-2
                    <?= $profileReportsActive
                        ? 'active'
                        : '' ?>">

                                            <i
                                                class="ri-flag-line"
                                                aria-hidden="true">
                                            </i>

                                            Reported Profiles
                                        </a>
                                    </li>

                                    <li>
                                        <a
                                            href="<?= route_to(
                                                        'admin.support.contacts'
                                                    ) ?>"
                                            class="dropdown-item
                    d-flex
                    align-items-center
                    gap-2
                    <?= $contactRequestsActive
                        ? 'active'
                        : '' ?>">

                                            <i
                                                class="ri-mail-line"
                                                aria-hidden="true">
                                            </i>

                                            Contact Requests
                                        </a>
                                    </li>
                                </ul>
                            </li>

                            <!-- Super Admin-only administration dropdown -->
                            <?php if ($isSuperAdmin): ?>
                                <li class="nav-item dropdown">
                                    <a
                                        href="#"
                                        class="nav-link
                                        dropdown-toggle
                                        d-flex
                                        align-items-center
                                        gap-2
                                        py-2
                                        <?= $administrationGroupActive
                                            ? 'active text-primary fw-semibold'
                                            : '' ?>"
                                        id="adminAdministrationDropdown"
                                        role="button"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false"
                                        <?= $administrationGroupActive
                                            ? 'aria-current="page"'
                                            : '' ?>>

                                        <i
                                            class="ri-settings-3-line
                                            fw-normal
                                            flex-shrink-0"
                                            aria-hidden="true"></i>

                                        <span>Administration</span>
                                    </a>

                                    <ul
                                        class="dropdown-menu"
                                        aria-labelledby="adminAdministrationDropdown">

                                        <li>
                                            <a
                                                href="<?= route_to(
                                                            'admin.users.index'
                                                        ) ?>"
                                                class="dropdown-item
                                                d-flex
                                                align-items-center
                                                gap-2
                                                <?= $administratorsActive
                                                    ? 'active'
                                                    : '' ?>"
                                                <?= $administratorsActive
                                                    ? 'aria-current="page"'
                                                    : '' ?>>

                                                <i
                                                    class="ri-user-settings-line"
                                                    aria-hidden="true"></i>

                                                Administrators
                                            </a>
                                        </li>
                                        <li>
                                            <a
                                                href="<?= route_to(
                                                            'admin.match-score.index'
                                                        ) ?>"
                                                class="dropdown-item
            d-flex
            align-items-center
            gap-2
            <?= $matchScoreActive
                                    ? 'active'
                                    : '' ?>"
                                                <?= $matchScoreActive
                                                    ? 'aria-current="page"'
                                                    : '' ?>>

                                                <i
                                                    class="ri-bar-chart-box-line"
                                                    aria-hidden="true"></i>

                                                Match Score
                                            </a>
                                        </li>

                                        <li>
                                            <a
                                                href="<?= route_to(
                                                            'admin.email-preview.index'
                                                        ) ?>"
                                                class="dropdown-item
            d-flex
            align-items-center
            gap-2
            <?= $emailPreviewActive
                                    ? 'active'
                                    : '' ?>"
                                                <?= $emailPreviewActive
                                                    ? 'aria-current="page"'
                                                    : '' ?>>

                                                <i
                                                    class="ri-mail-settings-line"
                                                    aria-hidden="true">
                                                </i>

                                                Email Preview Centre
                                            </a>
                                        </li>
                                        <li>
                                            <a
                                                href="<?= route_to(
                                                            'admin.communication-operations.index'
                                                        ) ?>"
                                                class="dropdown-item
        d-flex
        align-items-center
        gap-2
        <?= $communicationOperationsActive
                                    ? 'active'
                                    : '' ?>"
                                                <?= $communicationOperationsActive
                                                    ? 'aria-current="page"'
                                                    : '' ?>>

                                                <i
                                                    class="ri-mail-check-line"
                                                    aria-hidden="true">
                                                </i>

                                                Communication Operations
                                            </a>
                                        </li>
                                    </ul>
                                </li>

                            <?php endif; ?>
                        </ul>

                        <!-- Administrator identity and logout -->
                        <div
                            class="d-flex
                            flex-column
                            flex-lg-row
                            align-items-lg-center
                            gap-2
                            mt-3
                            mt-lg-0
                            ms-lg-3">

                            <div
                                class="d-flex
                                align-items-center
                                gap-2
                                text-muted
                                py-1
                                py-lg-0"
                                title="<?= esc(
                                            $adminUserName
                                                . ' · '
                                                . $adminRoleLabel,
                                            'attr'
                                        ) ?>">

                                <i
                                    class="ri-user-line
                                    fw-normal
                                    flex-shrink-0"
                                    aria-hidden="true"></i>

                                <div class="lh-sm">
                                    <div
                                        class="text-body
                                        text-truncate
                                        fw-medium"
                                        style="max-width: 160px;">

                                        <?= esc(
                                            $adminUserName
                                        ) ?>
                                    </div>

                                    <small class="text-muted">
                                        <?= esc(
                                            $adminRoleLabel
                                        ) ?>
                                    </small>
                                </div>
                            </div>

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
                                    btn-sm
                                    w-100
                                    d-inline-flex
                                    align-items-center
                                    justify-content-center
                                    gap-1">

                                    <i
                                        class="ri-logout-box-r-line"
                                        aria-hidden="true"></i>

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
    <?= view(
        'Components/VideoIntroduction/PlaybackModal'
    ) ?>
    <?= view('Components/ConfirmationModal') ?>

    <footer class="mt-5 pt-4 border-top border-secondary-subtle light-yellowish">

        <div class="container py-3 pt-0">

            <div class="row g-4">

                <div class="col-12 col-md-6 col-xl-3">

                    <div class="
                        d-flex
                        trust-feature
                        align-items-center
                        gap-3
                    ">

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

            <div
                class="d-flex
    flex-column
    flex-md-row
    align-items-center
    justify-content-center
    gap-2">

                <small class="text-muted">
                    © <?= esc(
                            date('Y')
                        ) ?>
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
                    'assets/js/bootstrap.bundle.min.js'
                ) ?>">
    </script>
    <script src="<?= base_url('assets/js/components/feedback-modal.js') ?>"></script>
    <script src="<?= base_url(
                        'assets/js/components/video-introduction-modal.js'
                    ) ?>"></script>
    <script src="<?= base_url('assets/js/app.js') ?>"></script>
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