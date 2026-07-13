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
        <?= esc($pageTitle ?? 'Sikh Anand Karaj') ?>
    </title>

    <link
        rel="stylesheet"
        href="<?= base_url('assets/css/bootstrap.css') ?>">

    <link
        rel="stylesheet"
        href="<?= base_url('assets/css/choices.min.css') ?>">

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

    <?= $this->include('Components/Footer') ?>

    <script src="<?= base_url('assets/js/jquery.js') ?>"></script>
    <script src="<?= base_url('assets/js/bootstrap.js') ?>"></script>
    <script src="<?= base_url('assets/js/choices.min.js') ?>"></script>

    <script src="<?= base_url(
                        'assets/js/components/select-choice.js'
                    ) ?>"></script>
    <script src="<?= base_url('assets/js/app.js') ?>"></script>
</body>

</html>