<?php

declare(strict_types=1);

/**
 * @var string|null                $pageTitle
 * @var array<string, string>|null $validationErrors
 * @var array<string, string>|null $formAlert
 */

$validationErrors = isset($validationErrors)
    && is_array($validationErrors)
    ? $validationErrors
    : [];

$formAlert = isset($formAlert)
    && is_array($formAlert)
    ? $formAlert
    : null;

$footerView = 'Components/Home/Footer';
$this->extend('Layouts/Main');
$this->section('content');
?>

<?= view(
    'Pages/Home/Sections/Hero',
    [
        'validationErrors' =>
        $validationErrors,

        'formAlert' =>
        $formAlert,
    ]
) ?>

<?= $this->include(
    'Pages/Home/Sections/WhyChooseUs'
) ?>

<?= $this->include(
    'Pages/Home/Sections/MatrimonyJourney'
) ?>

<?= $this->include(
    'Pages/Home/Sections/FeaturedProfiles'
) ?>

<?= $this->include(
    'Pages/Home/Sections/SuccessStories'
) ?>

<?= $this->include(
    'Pages/Home/Sections/HowItWorks'
) ?>

<?= $this->include(
    'Pages/Home/Sections/AppDownload'
) ?>

<?= $this->include(
    'Pages/Home/Sections/FAQ'
) ?>

<?= $this->include(
    'Pages/Home/Sections/ContactUs'
) ?>

<?php $this->endSection(); ?>