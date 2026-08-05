<?php

declare(strict_types=1);
?>

<section class="py-5 bg-white">
    <div class="container py-lg-4">
        <div class="row justify-content-center mb-5">
            <div class="col-12 col-lg-8 text-center">
                <span
                    class="badge
                        bg-danger-subtle
                        text-danger
                        text-uppercase
                        mb-3">

                    Why SikhAnandKaraj
                </span>

                <h2 class="fw-bold mb-3">
                    A trusted space for meaningful relationships
                </h2>

                <p class="text-muted fs-16 mb-0">
                    Designed around Sikh values, family participation,
                    privacy and genuine matrimonial intent.
                </p>
            </div>
        </div>

        <div class="row g-4">
            <?php
            $items = [
                [
                    'icon' =>
                    'ri-shield-check-line',

                    'title' =>
                    'Secure & Trusted',

                    'text' =>
                    'Privacy-focused access and secure profile '
                        . 'management throughout your journey.',
                ],
                [
                    'icon' =>
                    'ri-group-line',

                    'title' =>
                    'Family Oriented',

                    'text' =>
                    'A matrimonial experience that respects family, '
                        . 'traditions and shared responsibilities.',
                ],
                [
                    'icon' =>
                    'ri-heart-3-line',

                    'title' =>
                    'Relevant Matches',

                    'text' =>
                    'Discover members through preferences that matter '
                        . 'to you and your family.',
                ],
                [
                    'icon' =>
                    'ri-user-follow-line',

                    'title' =>
                    'Reviewed Profiles',

                    'text' =>
                    'Profile and photograph reviews help maintain '
                        . 'trust across the platform.',
                ],
            ];
            ?>

            <?php foreach ($items as $item): ?>
                <div class="col-12 col-md-6 col-xl-3">
                    <article
                        class="card
                            h-100
                            border
                            border-light
                            shadow-sm
                            text-center">

                        <div class="card-body p-4">
                            <span
                                class="d-inline-flex
                                    align-items-center
                                    justify-content-center
                                    rounded-circle
                                    bg-danger-subtle
                                    text-danger
                                    fs-2
                                    p-3
                                    mb-3">

                                <i
                                    class="<?= esc(
                                                $item['icon'],
                                                'attr'
                                            ) ?>"
                                    aria-hidden="true">
                                </i>
                            </span>

                            <h3 class="fs-18 fw-semibold mb-2">
                                <?= esc($item['title']) ?>
                            </h3>

                            <p class="text-muted mb-0">
                                <?= esc($item['text']) ?>
                            </p>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>