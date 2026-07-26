<?php

declare(strict_types=1);

/**
 * Education & Profession add/edit page.
 *
 * @var array<string, mixed>       $user
 * @var array<string, mixed>|null  $educationProfession
 * @var array<string, int>         $educationProfessionCompletion
 * @var array<string, mixed>       $masterData
 * @var array<string, string>      $validationErrors
 * @var array<string, string>|null $formAlert
 */

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
                                aria-hidden="true"></i>

                            Back to Profile
                        </a>
                        <div class="d-flex align-items-center gap-2 mt-2">
                            <div
                                class="avatar-sm flex-shrink-0"
                                aria-hidden="true">

                                <span
                                    class="avatar-title rounded-circle
                            bg-primary-subtle text-primary">

                                    <i class="ri-graduation-cap-line fs-20"></i>
                                </span>
                            </div>

                            <div>
                                <h2 class="fs-16 fw-semibold mb-1">
                                    Education &amp; Profession
                                </h2>

                                <p class="text-muted fs-13 mb-0">
                                    Add your educational and professional
                                    information. Fields marked with an
                                    asterisk (*) are required.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border border-danger border-opacity-25 shadow-none mb-0">
                    <div class="card-body p-3 p-md-4">

                        <?= view(
                            'Pages/Profile/Sections/'
                                . 'EducationProfession/_form',
                            [
                                'educationProfession' =>
                                $educationProfession ?? [],

                                'masterData' =>
                                $masterData ?? [],

                                'validationErrors' =>
                                $validationErrors ?? [],

                                'isProfileJourney' =>
                                $isProfileJourney ?? false,
                            ]
                        ) ?>

                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<?php $this->endSection(); ?>