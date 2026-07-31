<?php

declare(strict_types=1);

/**
 * @var string|null                $pageTitle
 * @var array<string, string>|null $formAlert
 */

$resolvedAlert = is_array(
    $formAlert ?? null
)
    ? $formAlert
    : null;

$this->extend('Layouts/Main');
$this->section('content');
?>

<section class="py-4">
    <div class="container">
        <div
            class="row justify-content-center align-items-center auth-content-height">

            <div
                class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">

                <div class="card border-0 shadow-lg mb-0">
                    <div class="card-body p-4 p-md-5 pt-md-4">
                        <div class="text-center mb-4">
                            <h1 class="fs-24 fw-semibold mb-2">
                                Welcome Back
                            </h1>

                            <p class="text-muted mb-0">
                                Select how you would like to login
                            </p>
                        </div>

                        <?= view(
                            'Components/Alerts/FormAlert',
                            [
                                'alert' =>
                                $resolvedAlert,
                            ]
                        ) ?>

                        <div class="d-grid gap-3">
                            <a
                                href="<?= route_to(
                                            'web.login.password'
                                        ) ?>"
                                class="btn btn-outline-success fs-16 fw-semibold text-uppercase">

                                <i
                                    class="ri-lock-password-line me-2"
                                    aria-hidden="true"></i>

                                Login with Password
                            </a>

                            <a
                                href="<?= route_to(
                                            'web.login.otp'
                                        ) ?>"
                                class="btn btn-outline-primary fs-16 fw-semibold text-uppercase">

                                <i
                                    class="ri-smartphone-line me-2"
                                    aria-hidden="true"></i>

                                Login with OTP
                            </a>
                        </div>

                        <div class="mt-4 text-center">
                            <p class="mb-0 text-muted">
                                Don&apos;t have a profile?

                                <a
                                    href="<?= route_to(
                                                'web.home'
                                            ) ?>"
                                    class="fw-semibold text-primary text-decoration-underline">

                                    Register Free
                                </a>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <p class="text-muted mb-0 fs-13">
                        <i
                            class="ri-shield-check-line text-primary me-1"
                            aria-hidden="true"></i>

                        Your login information is securely protected.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<?= $this->endSection() ?>