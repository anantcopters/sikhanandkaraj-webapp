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

                    Discover Members
                </span>

                <h2 class="fw-bold mb-3">
                    Profiles aligned with your preferences
                </h2>

                <p class="text-muted fs-16 mb-0">
                    Register and complete your profile to receive
                    relevant recommendations.
                </p>
            </div>
        </div>

        <div class="row g-4 justify-content-center">
            <?php for ($index = 1; $index <= 4; $index++): ?>
                <div class="col-12 col-sm-6 col-xl-3">
                    <article
                        class="card
                            h-100
                            overflow-hidden
                            border-0
                            shadow-sm">

                        <div
                            class="bg-danger-subtle
                                d-flex
                                align-items-center
                                justify-content-center
                                py-5">

                            <i
                                class="ri-user-3-line
                                    display-3
                                    text-danger
                                    opacity-50"
                                aria-hidden="true">
                            </i>
                        </div>

                        <div class="card-body p-4">
                            <span
                                class="badge
                                    bg-light
                                    text-danger
                                    mb-3">

                                Member Profile
                            </span>

                            <h3 class="fs-18 fw-semibold mb-2">
                                Complete your registration
                            </h3>

                            <p class="text-muted mb-0">
                                Sign in to discover profile information
                                and suitable matches.
                            </p>
                        </div>
                    </article>
                </div>
            <?php endfor; ?>
        </div>
    </div>
</section>