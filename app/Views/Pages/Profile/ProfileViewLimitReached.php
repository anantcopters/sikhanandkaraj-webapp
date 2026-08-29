<?php

declare(strict_types=1);

/**
 * Paid-member Full Profile quota lock.
 *
 * @var string $message
 */

$message = trim(
    (string) (
        $message
        ?? 'Your Full Profile viewing limit has been reached.'
    )
);
?>

<?= $this->extend(
    'Layouts/Main'
) ?>

<?= $this->section(
    'content'
) ?>

<div class="container-fluid">

    <div class="row justify-content-center">

        <div class="col-12 col-lg-8 col-xl-6">

            <div class="card">

                <div class="card-body text-center p-4 p-lg-5">

                    <div class="mb-4">

                        <i
                            class="ri-eye-off-line fs-48 text-muted"
                            aria-hidden="true">
                        </i>

                    </div>

                    <h4 class="mb-3">
                        Profile View Limit Reached
                    </h4>

                    <p class="text-muted mb-4">
                        <?= esc(
                            $message
                        ) ?>
                    </p>

                    <div
                        class="
                            d-flex
                            flex-wrap
                            justify-content-center
                            gap-2
                        ">

                        <a
                            href="<?= route_to(
                                        'web.dashboard'
                                    ) ?>"
                            class="btn btn-outline-secondary">

                            Back to Dashboard

                        </a>

                        <a
                            href="<?= route_to(
                                        'web.account.settings.section',
                                        'plans'
                                    ) ?>"
                            class="btn btn-danger">

                            View Membership

                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

<?= $this->endSection() ?>