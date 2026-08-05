<?php

declare(strict_types=1);

$steps = [
    [
        'number' =>
        '01',

        'icon' =>
        'ri-user-add-line',

        'title' =>
        'Create Your Profile',

        'text' =>
        'Add your personal, family, educational and '
            . 'professional details.',
    ],
    [
        'number' =>
        '02',

        'icon' =>
        'ri-search-eye-line',

        'title' =>
        'Discover Matches',

        'text' =>
        'Review relevant profiles based on your preferences '
            . 'and expectations.',
    ],
    [
        'number' =>
        '03',

        'icon' =>
        'ri-heart-3-line',

        'title' =>
        'Begin a Conversation',

        'text' =>
        'Express interest and move forward respectfully when '
            . 'both members are comfortable.',
    ],
];
?>

<section class="py-5 bg-light">
    <div class="container py-lg-4">
        <div class="row justify-content-center mb-5">
            <div class="col-12 col-lg-8 text-center">
                <span
                    class="badge
                        bg-danger-subtle
                        text-danger
                        text-uppercase
                        mb-3">

                    Matrimony Journey
                </span>

                <h2 class="fw-bold mb-3">
                    Built for every stage of your search
                </h2>

                <p class="text-muted fs-16 mb-0">
                    Create your profile, express your preferences and
                    connect with suitable members.
                </p>
            </div>
        </div>

        <div class="row g-4">
            <?php foreach ($steps as $step): ?>
                <div class="col-12 col-lg-4">
                    <article class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div
                                class="d-flex
                                    align-items-center
                                    justify-content-between
                                    mb-4">

                                <span
                                    class="badge
                                        rounded-pill
                                        bg-danger
                                        fs-14">

                                    <?= esc($step['number']) ?>
                                </span>

                                <i
                                    class="<?= esc(
                                                $step['icon'],
                                                'attr'
                                            ) ?> fs-1 text-danger"
                                    aria-hidden="true">
                                </i>
                            </div>

                            <h3 class="fs-20 fw-semibold mb-3">
                                <?= esc($step['title']) ?>
                            </h3>

                            <p class="text-muted mb-0">
                                <?= esc($step['text']) ?>
                            </p>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>