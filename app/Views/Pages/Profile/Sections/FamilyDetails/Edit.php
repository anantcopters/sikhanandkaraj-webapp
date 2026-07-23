<?php

declare(strict_types=1);

$this->extend('Layouts/Main');
$this->section('content');
?>

<section class="py-3 py-lg-4">
    <div class="container">

        <?= view(
            'Pages/Profile/Partials/_feedback_alert',
            [
                'formAlert' => $formAlert ?? null,
            ]
        ) ?>

        <div class="row justify-content-center">
            <div class="col-12 col-lg-10 col-xl-8">

                <div class="d-flex align-items-start gap-3 mb-3">
                    <div>
                        <a
                            href="<?= url_to(
                                        'web.profile.edit'
                                    ) ?>"
                            class="d-inline-flex align-items-center
                                gap-1 text-primary fw-medium mb-2">

                            <i
                                class="ri-arrow-left-line"
                                aria-hidden="true">
                            </i>

                            Back to Profile
                        </a>

                        <h1 class="fs-22 fw-semibold mb-1">
                            Family Details
                        </h1>

                        <p class="text-muted mb-0">
                            Add your family background and location.
                            Fields marked with an asterisk (*) are required.
                        </p>
                    </div>
                </div>

                <div class="card border border-danger border-opacity-25 shadow-none mb-0">
                    <div class="card-body p-3 p-md-4">

                        <?= view(
                            'Pages/Profile/Sections/'
                                . 'FamilyDetails/_form',
                            [
                                'familyDetails' =>
                                $familyDetails ?? [],

                                'masterData' =>
                                $masterData ?? [],

                                'validationErrors' =>
                                $validationErrors ?? [],
                            ]
                        ) ?>

                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<?php $this->endSection(); ?>