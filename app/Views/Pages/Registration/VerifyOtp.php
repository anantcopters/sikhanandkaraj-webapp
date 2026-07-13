<?php

declare(strict_types=1);

/**
 * @var string|null $profileReference
 */

$formAlert = session('formAlert');

$formAlert = is_array($formAlert)
    ? $formAlert
    : null;

$this->extend('Layouts/Main');
$this->section('content');
?>

<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-7 col-lg-5">

                <?= view('Components/Alerts/FormAlert', [
                    'alert' => $formAlert,
                ]) ?>

                <div class="card">
                    <div class="card-body p-4 text-center">
                        <h1 class="fs-22 mb-3">
                            Verify OTP
                        </h1>

                        <p class="text-muted mb-2">
                            An OTP has been generated for your
                            registered mobile number.
                        </p>

                        <?php if (
                            is_string($profileReference)
                            && $profileReference !== ''
                        ): ?>
                            <p class="mb-0">
                                Profile reference:
                                <strong>
                                    <?= esc($profileReference) ?>
                                </strong>
                            </p>
                        <?php endif; ?>

                        <p class="text-muted mt-3 mb-0">
                            OTP form will be added in the next step.
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<?php $this->endSection(); ?>