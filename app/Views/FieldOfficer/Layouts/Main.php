<?php

declare(strict_types=1);

$pageTitle =
    $pageTitle
    ?? 'Field Officer';

$pageScripts =
    is_array(
        $pageScripts
            ?? null
    )
    ? $pageScripts
    : [];

$isAuthenticated =
    session(
        'fo_is_authenticated'
    ) === true;

$currentPath = trim(
    service('uri')->getPath(),
    '/'
);

$profilesActive =
    str_starts_with(
        $currentPath,
        'field-officer/profiles'
    );
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1">

    <title>
        <?= esc($pageTitle) ?>
        | Sikhanandkaraj
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
        href="<?= base_url(
                    'assets/css/custom.css'
                ) ?>">
</head>

<body>

    <?php if ($isAuthenticated): ?>

        <header
            id="page-topbar"
            class="position-static border-bottom">

            <nav
                class="navbar navbar-expand-lg bg-white py-2"
                aria-label="Field Officer navigation">

                <div
                    class="container-fluid px-3 px-lg-4">

                    <a
                        href="<?= route_to(
                                    'field-officer.dashboard'
                                ) ?>"
                        class="navbar-brand
                d-inline-flex
                align-items-center
                flex-shrink-0
                me-lg-3
                m-0
                p-0
                text-decoration-none">

                        <img
                            src="<?= base_url(
                                        'assets/images/logo_sak_bgremove_final.png'
                                    ) ?>"
                            alt="Sikhanandkaraj"
                            class="public-navbar__logo">
                    </a>

                    <button
                        type="button"
                        class="navbar-toggler"
                        data-bs-toggle="collapse"
                        data-bs-target="#fieldOfficerNavbar"
                        aria-controls="fieldOfficerNavbar"
                        aria-expanded="false"
                        aria-label="Toggle Field Officer navigation">

                        <span
                            class="navbar-toggler-icon">
                        </span>
                    </button>

                    <div
                        class="collapse navbar-collapse"
                        id="fieldOfficerNavbar">

                        <ul
                            class="navbar-nav
                    nav-underline
                    mx-lg-auto
                    gap-2
                    mt-2 mt-lg-0">

                            <!--
                        The only Field Officer business menu item.
                    -->
                            <li class="nav-item">

                                <a
                                    href="<?= route_to(
                                                'field-officer.profiles.index'
                                            ) ?>"
                                    class="nav-link
                            d-flex
                            align-items-center
                            gap-2
                            py-1 py-lg-2
                            <?= $profilesActive
                                ? 'active text-primary'
                                : '' ?>">

                                    <i
                                        class="ri-profile-line"
                                        aria-hidden="true">
                                    </i>

                                    <span
                                        class="<?= $profilesActive
                                                    ? 'fw-semibold'
                                                    : '' ?>">

                                        Profiles Submitted
                                    </span>
                                </a>
                            </li>
                        </ul>

                        <div
                            class="d-flex
                    flex-column
                    flex-lg-row
                    align-items-lg-center
                    gap-2
                    mt-2 mt-lg-0">

                            <span
                                class="text-muted
                        text-truncate
                        mw-100">

                                <i
                                    class="ri-user-location-line
                            me-1"
                                    aria-hidden="true">
                                </i>

                                <?= esc(
                                    (string) session(
                                        'fo_field_officer_name'
                                    )
                                ) ?>
                            </span>

                            <form
                                action="<?= route_to(
                                            'field-officer.logout'
                                        ) ?>"
                                method="post"
                                class="mb-0">

                                <?= csrf_field() ?>

                                <button
                                    type="submit"
                                    class="btn
                            btn-soft-secondary
                            btn-sm
                            w-100">

                                    <i
                                        class="ri-logout-box-r-line
                                me-1"
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

    <main
        class="<?= $isAuthenticated
                    ? 'page-content py-4'
                    : '' ?>">

        <?= $this->renderSection(
            'content'
        ) ?>
    </main>

    <script
        src="<?= base_url(
                    'assets/js/bootstrap.bundle.min.js'
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

    <?php foreach (
        $pageScripts as $script
    ): ?>

        <?php if (
            is_string($script)
            && $script !== ''
        ): ?>

            <script
                src="<?= esc(
                            base_url($script),
                            'attr'
                        ) ?>">
            </script>

        <?php endif; ?>

    <?php endforeach; ?>

</body>

</html>