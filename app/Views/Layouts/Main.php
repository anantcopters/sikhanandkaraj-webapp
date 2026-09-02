<?php

declare(strict_types=1);

/**
 * @var string|null                    $pageTitle
 * @var array<string, mixed>|null      $seo
 * @var bool|null                      $minimalPublicPage
 * @var array<int, string>|null        $pageScripts
 */

$seoData = is_array($seo ?? null)
    ? $seo
    : [];

$documentTitle = trim(
    (string) ($seoData['title'] ?? $pageTitle ?? 'SikhanandKaraj')
);

$metaDescription = trim(
    (string) ($seoData['description'] ?? '')
);

$canonicalUrl = trim(
    (string) ($seoData['canonical'] ?? '')
);

$robotsDirective = trim(
    (string) ($seoData['robots'] ?? 'noindex,nofollow,noarchive')
);

$openGraphType = trim(
    (string) ($seoData['ogType'] ?? 'website')
);

$openGraphImage = trim(
    (string) ($seoData['ogImage'] ?? '')
);

$structuredData = is_array($seoData['structuredData'] ?? null)
    ? $seoData['structuredData']
    : [];

$useMinimalPublicAssets = ($minimalPublicPage ?? false) === true;

$isProductionDeployment = strtolower(
    trim(
        (string) env(
            'APP_DEPLOYMENT',
            'development'
        )
    )
) === 'production';

$googleTagManagerId = $isProductionDeployment
    ? trim(
        (string) env(
            'GOOGLE_TAG_MANAGER_ID',
            ''
        )
    )
    : '';

$googleTagManagerEnabled = preg_match(
    '/^GTM-[A-Z0-9]+$/',
    $googleTagManagerId
) === 1;
?>
?>
<!doctype html>
<html lang="en" dir="ltr">

<head>
    <?php if ($googleTagManagerEnabled): ?>
        <!-- Google Tag Manager -->
        <script>
            (
                function(w, d, s, l, i) {
                    w[l] = w[l] || [];
                    w[l].push({
                        'gtm.start': new Date().getTime(),
                        event: 'gtm.js'
                    });

                    var f = d.getElementsByTagName(s)[0],
                        j = d.createElement(s),
                        dl = l !== 'dataLayer' ?
                        '&l=' + l :
                        '';

                    j.async = true;
                    j.src =
                        'https://www.googletagmanager.com/gtm.js?id=' +
                        i +
                        dl;

                    f.parentNode.insertBefore(j, f);
                }
            )(
                window,
                document,
                'script',
                'dataLayer',
                '<?= esc(
                        $googleTagManagerId,
                        'js'
                    ) ?>'
            );
        </script>
        <!-- End Google Tag Manager -->
    <?php endif; ?>
    <meta charset="utf-8">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= base_url('assets/images/favicon/apple-touch-icon.png') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('assets/images/favicon/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= base_url('assets/images/favicon/favicon-16x16.png') ?>">
    <link rel="manifest" href="<?= base_url('assets/images/favicon/site.webmanifest') ?>">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1">

    <meta
        name="robots"
        content="<?= esc($robotsDirective, 'attr') ?>">

    <?php if ($metaDescription !== ''): ?>
        <meta
            name="description"
            content="<?= esc($metaDescription, 'attr') ?>">
    <?php endif; ?>

    <?php if ($canonicalUrl !== ''): ?>
        <link
            rel="canonical"
            href="<?= esc($canonicalUrl, 'attr') ?>">

        <meta
            property="og:url"
            content="<?= esc($canonicalUrl, 'attr') ?>">
    <?php endif; ?>

    <meta
        property="og:type"
        content="<?= esc($openGraphType, 'attr') ?>">

    <meta
        property="og:site_name"
        content="SikhanandKaraj">

    <meta
        property="og:title"
        content="<?= esc($documentTitle, 'attr') ?>">

    <?php if ($metaDescription !== ''): ?>
        <meta
            property="og:description"
            content="<?= esc($metaDescription, 'attr') ?>">
    <?php endif; ?>

    <?php if ($openGraphImage !== ''): ?>
        <meta
            property="og:image"
            content="<?= esc($openGraphImage, 'attr') ?>">
    <?php endif; ?>

    <meta
        name="twitter:card"
        content="summary">

    <meta
        name="twitter:title"
        content="<?= esc($documentTitle, 'attr') ?>">

    <?php if ($metaDescription !== ''): ?>
        <meta
            name="twitter:description"
            content="<?= esc($metaDescription, 'attr') ?>">
    <?php endif; ?>

    <?php if ($openGraphImage !== ''): ?>
        <meta
            name="twitter:image"
            content="<?= esc($openGraphImage, 'attr') ?>">
    <?php endif; ?>

    <title>
        <?= esc($documentTitle) ?>
    </title>

    <?php foreach ($structuredData as $schema): ?>
        <?php if (is_array($schema) && $schema !== []): ?>
            <script type="application/ld+json">
                <?= json_encode(
                    $schema,
                    JSON_UNESCAPED_SLASHES
                        | JSON_UNESCAPED_UNICODE
                        | JSON_HEX_TAG
                        | JSON_HEX_AMP
                        | JSON_HEX_APOS
                        | JSON_HEX_QUOT
                ) ?>
            </script>
        <?php endif; ?>
    <?php endforeach; ?>

    <link
        rel="stylesheet"
        href="<?= base_url('assets/css/bootstrap.css') ?>">


    <link
        rel="stylesheet"
        href="<?= base_url('assets/css/icons.css') ?>">

    <link
        rel="stylesheet"
        href="<?= base_url('assets/css/app.css') ?>">
    <link
        rel="stylesheet"
        href="<?= base_url('assets/css/custom.css') ?>">
</head>

<body>

    <body>
        <?php if ($googleTagManagerEnabled): ?>
            <!-- Google Tag Manager (noscript) -->
            <noscript>
                <iframe
                    src="https://www.googletagmanager.com/ns.html?id=<?= esc(
                                                                            $googleTagManagerId,
                                                                            'attr'
                                                                        ) ?>"
                    height="0"
                    width="0"
                    style="display:none;visibility:hidden">
                </iframe>
            </noscript>
            <!-- End Google Tag Manager (noscript) -->
        <?php endif; ?>

        <?= $this->include('Components/Header') ?>
        <?= $this->include('Components/Header') ?>
        <?= view(
            'Components/Member/PaidAadhaarReminder'
        ) ?>

        <main>
            <?= $this->renderSection('content') ?>
        </main>
        <?php

        $resolvedFooterView = isset($footerView)
            && is_string($footerView)
            && trim($footerView) !== ''
            ? trim($footerView)
            : 'Components/Footer';

        ?>

        <?= $this->include(
            $resolvedFooterView
        ) ?>

        <?= view('Components/FeedbackModal') ?>

        <?= view(
            'Components/VideoIntroduction/PlaybackModal'
        ) ?>

        <?= view('Components/ConfirmationModal') ?>

        <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
        <script src="<?= base_url('assets/js/app.js') ?>"></script>
        <script src="<?= base_url('assets/js/components/feedback-modal.js') ?>"></script>
        <script
            src="<?= base_url(
                        'assets/js/components/confirmation-modal.js'
                    ) ?>">
        </script>

        <?php if (!$useMinimalPublicAssets): ?>
            <script src="<?= base_url('assets/js/choices.min.js') ?>"></script>

            <script src="<?= base_url(
                                'assets/js/components/select-choice.js'
                            ) ?>"></script>
            <script src="<?= base_url(
                                'assets/js/components/form-validator.js'
                            ) ?>"></script>
            <script src="<?= base_url(
                                'assets/js/components/video-introduction-modal.js'
                            ) ?>"></script>
        <?php endif; ?>
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
                <?php
                /*
         * Page-specific JavaScript files can change independently of the
         * common layout. Use the file modification time as a cache version so
         * browsers do not continue running an older dependent-dropdown script
         * after deployment.
         *
         * This does not run Git or any external command at request time.
         */
                $scriptPath =
                    FCPATH
                    . ltrim(
                        $script,
                        '/'
                    );

                $scriptVersion =
                    is_file($scriptPath)
                    ? (string) filemtime(
                        $scriptPath
                    )
                    : '';

                $scriptUrl =
                    base_url($script)
                    . (
                        $scriptVersion !== ''
                        ? '?v='
                        . rawurlencode(
                            $scriptVersion
                        )
                        : ''
                    );
                ?>

                <script
                    src="<?= esc(
                                $scriptUrl,
                                'attr'
                            ) ?>">
                </script>
            <?php endif; ?>
        <?php endforeach; ?>

    </body>

</html>