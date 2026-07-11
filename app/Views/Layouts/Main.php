<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        <?= esc($pageTitle ?? 'Sikh Anand Karaj') ?>
    </title>

    <link
        rel="stylesheet"
        href="<?= base_url('assets/css/bootstrap.css') ?>"
    >

    <link
        rel="stylesheet"
        href="<?= base_url('assets/css/icons.css') ?>"
    >

    <link
        rel="stylesheet"
        href="<?= base_url('assets/css/app.css') ?>"
    >
</head>
<body>
    <?= $this->include('Components/Header') ?>

    <main>
        <?= $this->renderSection('content') ?>
    </main>

    <?= $this->include('Components/Footer') ?>

    <script src="<?= base_url('assets/js/jquery.js') ?>"></script>
    <script src="<?= base_url('assets/js/bootstrap.js') ?>"></script>
    <script src="<?= base_url('assets/js/app.js') ?>"></script>
</body>
</html>