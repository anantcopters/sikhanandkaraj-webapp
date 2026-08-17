<?php

declare(strict_types=1);

$this->extend('Layouts/Main');
$this->section('content');
?>

<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-xl-6">

                <div
                    class="card border border-danger
                        border-opacity-25 shadow-sm">

                    <div class="card-body text-center p-4 p-lg-5">

                        <span class="avatar-lg d-inline-block mb-3">
                            <span
                                class="avatar-title rounded-circle
                                    bg-warning-subtle text-warning">

                                <i
                                    class="ri-vip-crown-line fs-30"
                                    aria-hidden="true">
                                </i>
                            </span>
                        </span>

                        <h1 class="fs-22 fw-semibold">
                            Paid membership required
                        </h1>

                        <p class="text-muted">
                            This member has chosen to share their
                            complete profile only with paid members.
                        </p>

                        <div
                            class="d-flex flex-wrap
                                justify-content-center gap-2">

                            <a
                                href="<?= route_to(
                                            'web.account.settings.section',
                                            'plans'
                                        ) ?>"
                                class="btn btn-primary">

                                View Plans
                            </a>

                            <a
                                href="<?= route_to(
                                            'web.dashboard'
                                        ) ?>"
                                class="btn btn-outline-secondary">

                                Back to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php $this->endSection(); ?>