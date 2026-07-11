<?php

declare(strict_types=1);

/**
 * @var string|null $pageTitle
 */
$resolvedPageTitle = isset($pageTitle) && is_string($pageTitle)
    ? $pageTitle
    : 'Sikh Anand Karaj';

$this->extend('Layouts/Main');
$this->section('content');
?>

<section class="container py-5">
    <div class="text-center">
        <h1><?= esc($resolvedPageTitle) ?></h1>

        <p class="lead">
            A trusted matrimonial platform for the Sikh community.
        </p>
    </div>
</section>

<?php $this->endSection(); ?>