<?php

declare(strict_types=1);

/**
 * Full Profile access lock.
 *
 * Business rules are resolved by ProfileAccessPolicy. This View receives
 * presentation-safe text only and must not attempt to derive membership or
 * privacy state itself.
 *
 * @var string $message
 */

$message = trim(
    (string) (
        $message
        ?? 'This Full Profile is currently unavailable.'
    )
);
?>

<?= $this->extend(
    'Layouts/Main'
) ?>

<?= $this->section(
    'content'
) ?>
<section class="py-3 py-lg-3">
    <div class="container">

        <div class="row justify-content-center">

            <div class="col-12 col-lg-8 col-xl-6">

                <div class="card border border-danger border-opacity-25">

                    <div class="card-body text-center p-4 p-lg-5">

                        <div class="mb-4">

                            <i
                                class="ri-lock-2-line fs-48 text-danger"
                                aria-hidden="true">
                            </i>

                        </div>

                        <h4 class="mb-3">
                            Full Profile Unavailable
                        </h4>

                        <p class="text-muted mb-4">
                            <?= esc(
                                $message
                            ) ?>
                        </p>

                        <a
                            href="<?= route_to(
                                        'web.dashboard'
                                    ) ?>"
                            class="btn btn-outline-secondary">

                            <i
                                class="ri-arrow-left-line me-1"
                                aria-hidden="true">
                            </i>

                            Back to Dashboard

                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>
</section>
<?= $this->endSection() ?>