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

/*
 * setVar() places the value in the shared CodeIgniter view renderer.
 * A normal PHP variable declared here is not reliably inherited by the
 * extended layout.
 */
$this->setVar(
    'footerView',
    'Components/Home/Footer'
)->extend(
    'Layouts/Main'
);
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
    'Pages/Home/Sections/Pricing'
) ?>

<?= $this->include(
    'Pages/Home/Sections/FAQ'
) ?>

<?php $this->endSection(); ?>