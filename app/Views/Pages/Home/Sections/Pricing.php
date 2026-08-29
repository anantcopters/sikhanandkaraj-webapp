<?php

declare(strict_types=1);
?>

<section
    class="section bg-light py-5"
    id="plans"
    aria-labelledby="home-membership-plans-title">

    <div class="container">

        <div class="row justify-content-center">

            <div class="col-12 col-lg-8">

                <header class="text-center mb-5">

                    <p
                        class="
                            fs-13
                            fw-semibold
                            text-danger
                            text-uppercase
                            mb-2
                        ">
                        Membership Plans
                    </p>

                    <h2
                        id="home-membership-plans-title"
                        class="
                            fs-28
                            fw-semibold
                            mb-3
                        ">

                        Choose a Plan That Fits
                        Your Search
                    </h2>

                    <p class="text-muted mb-0">
                        More connections. More possibilities.
                        Choose what works for you.
                    </p>

                </header>

            </div>

        </div>

        <?= view(
            'Components/Membership/PlanCards',
            [
                'context' => 'public',
                'plans' => $plans ?? [],
            ]
        ) ?>

    </div>
</section>