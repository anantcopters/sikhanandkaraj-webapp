<?php

declare(strict_types=1);

$pageTitle = trim(
    (string) (
        $pageTitle
        ?? 'Field Officer'
    )
);

if ($pageTitle === '') {
    $pageTitle =
        'Field Officer';
}

$pageScripts =
    isset($pageScripts)
    && is_array($pageScripts)
    ? $pageScripts
    : [];

$isAuthenticated =
    session(
        'fo_is_authenticated'
    ) === true;

$fieldOfficerName = trim(
    (string) session(
        'fo_field_officer_name'
    )
);

if ($fieldOfficerName === '') {
    $fieldOfficerName =
        'Field Officer';
}

$currentPath = trim(
    service('uri')->getPath(),
    '/'
);

$profilesActive =
    str_starts_with(
        $currentPath,
        'field-officer/profiles'
    );

$dashboardUrl =
    route_to(
        'field-officer.dashboard'
    );

$profilesUrl =
    route_to(
        'field-officer.profiles.index'
    );

$logoutUrl =
    route_to(
        'field-officer.logout'
    );

$logoUrl =
    base_url(
        'assets/images/'
            . 'logo_sak_bgremove_final.png'
    );

$bootstrapCssUrl =
    base_url(
        'assets/css/bootstrap.css'
    );

$iconsCssUrl =
    base_url(
        'assets/css/icons.css'
    );

$appCssUrl =
    base_url(
        'assets/css/app.css'
    );

$customCssUrl =
    base_url(
        'assets/css/custom.css'
    );

$bootstrapJsUrl =
    base_url(
        'assets/js/bootstrap.bundle.min.js'
    );

$choicesJsUrl =
    base_url(
        'assets/js/choices.min.js'
    );

$selectChoiceJsUrl =
    base_url(
        'assets/js/components/select-choice.js'
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
        href="<?= esc(
                    $bootstrapCssUrl,
                    'attr'
                ) ?>">

    <link
        rel="stylesheet"
        href="<?= esc(
                    $iconsCssUrl,
                    'attr'
                ) ?>">

    <link
        rel="stylesheet"
        href="<?= esc(
                    $appCssUrl,
                    'attr'
                ) ?>">

    <link
        rel="stylesheet"
        href="<?= esc(
                    $customCssUrl,
                    'attr'
                ) ?>">

</head>

<body>

    <?php if ($isAuthenticated): ?>

        <header
            id="page-topbar"
            class="position-static
            border-bottom">

            <nav
                class="navbar
                navbar-expand-lg
                bg-white
                py-2"
                aria-label="Field Officer navigation">

                <div
                    class="container-fluid
                    px-3
                    px-lg-4">

                    <a
                        href="<?= esc(
                                    $dashboardUrl,
                                    'attr'
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
                            src="<?= esc(
                                        $logoUrl,
                                        'attr'
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
                        class="collapse
                        navbar-collapse"
                        id="fieldOfficerNavbar">

                        <ul
                            class="navbar-nav
                            nav-underline
                            mx-lg-auto
                            gap-2
                            mt-2
                            mt-lg-0">

                            <li class="nav-item">

                                <a
                                    href="<?= esc(
                                                $profilesUrl,
                                                'attr'
                                            ) ?>"
                                    class="nav-link
                                    d-flex
                                    align-items-center
                                    gap-2
                                    py-1
                                    py-lg-2
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
                            mt-2
                            mt-lg-0">

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
                                    $fieldOfficerName
                                ) ?>

                            </span>

                            <form
                                action="<?= esc(
                                            $logoutUrl,
                                            'attr'
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
        src="<?= esc(
                    $bootstrapJsUrl,
                    'attr'
                ) ?>">
    </script>

    <script
        src="<?= esc(
                    $choicesJsUrl,
                    'attr'
                ) ?>">
    </script>

    <script
        src="<?= esc(
                    $selectChoiceJsUrl,
                    'attr'
                ) ?>">
    </script>

    <?php foreach (
        $pageScripts
        as $script
    ): ?>

        <?php
        $scriptPath = is_string($script)
            ? trim($script)
            : '';

        if ($scriptPath === '') {
            continue;
        }

        $scriptUrl =
            base_url(
                $scriptPath
            );
        ?>

        <script
            src="<?= esc(
                        $scriptUrl,
                        'attr'
                    ) ?>">
        </script>

    <?php endforeach; ?>

</body>

</html>