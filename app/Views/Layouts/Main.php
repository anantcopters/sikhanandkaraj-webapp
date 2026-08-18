<!doctype html>
<html lang="en" dir="ltr">

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
        <?= esc($pageTitle ?? 'Sikhanandkaraj') ?>
    </title>

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
    <?= $this->include('Components/Header') ?>

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

    <?= view('Components/ConfirmationModal') ?>

    <script src="<?= base_url('assets/js/jquery.js') ?>"></script>
    <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/choices.min.js') ?>"></script>

    <script src="<?= base_url(
                        'assets/js/components/select-choice.js'
                    ) ?>"></script>
    <script src="<?= base_url(
                        'assets/js/components/form-validator.js'
                    ) ?>"></script>
    <script src="<?= base_url('assets/js/app.js') ?>"></script>
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