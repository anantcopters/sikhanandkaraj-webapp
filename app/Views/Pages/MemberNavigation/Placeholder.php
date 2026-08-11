<?php

declare(strict_types=1);

/**
 * @var string $heading
 * @var string $icon
 * @var string $description
 */

$this->extend('Layouts/Main');
$this->section('content');
?>

<section class="member-page py-4 py-lg-5">
    <div class="container">
        <div class="page-title-box mb-4">
            <h1 class="fs-24 fw-semibold mb-1">
                <?= esc($heading) ?>
            </h1>

            <p class="text-muted mb-0">
                <?= esc($description) ?>
            </p>
        </div>

        <div class="card border border-danger border-opacity-25 shadow-sm">
            <div class="card-body text-center py-5">
                <div
                    class="avatar-lg mx-auto mb-3"
                    aria-hidden="true">

                    <span
                        class="avatar-title rounded-circle
                        bg-primary-subtle text-primary fs-1">

                        <i class="<?= esc(
                                        $icon,
                                        'attr'
                                    ) ?>"></i>
                    </span>
                </div>

                <h2 class="fs-20 fw-semibold mb-2">
                    <?= esc($heading) ?>
                </h2>

                <p class="text-muted mb-0">
                    <?= esc($description) ?>
                </p>
            </div>
        </div>
    </div>
</section>

<?= $this->endSection() ?>