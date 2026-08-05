<?php

declare(strict_types=1);

$steps = [
    [
        'number' =>
        '1',

        'title' =>
        'Register',

        'text' =>
        'Create your account using a valid mobile number.',
    ],
    [
        'number' =>
        '2',

        'title' =>
        'Complete Profile',

        'text' =>
        'Add the information needed for meaningful recommendations.',
    ],
    [
        'number' =>
        '3',

        'title' =>
        'Explore',

        'text' =>
        'Review compatible member profiles securely.',
    ],
    [
        'number' =>
        '4',

        'title' =>
        'Connect',

        'text' =>
        'Express interest and take the conversation forward.',
    ],
];
?>

<section
    id="how-it-works"
    class="py-5 bg-white">

    <div class="container py-lg-4">
        <div class="row justify-content-center mb-5">
            <div class="col-12 col-lg-8 text-center">
                <span
                    class="badge
                        bg-danger-subtle
                        text-danger
                        text-uppercase
                        mb-3">

                    How It Works
                </span>

                <h2 class="fw-bold mb-0">
                    Simple, respectful and transparent
                </h2>
            </div>
        </div>

        <div class="row g-4">
            <?php foreach ($steps as $step): ?>
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
                                    bg-danger
                                    text-white
                                    fs-18
                                    fw-semibold
                                    p-3
                                    mb-3">

                                <?= esc($step['number']) ?>
                            </span>

                            <h3 class="fs-18 fw-semibold mb-2">
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