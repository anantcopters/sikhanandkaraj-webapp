<?php

declare(strict_types=1);

/**
 * @var array<string, mixed>       $user
 * @var string                     $aboutMe
 * @var array<string, int>         $aboutMeCompletion
 * @var array<string, string>      $validationErrors
 * @var array<string, string>|null $formAlert
 */

$user = isset($user) && is_array($user)
    ? $user
    : [];

$aboutMe = isset($aboutMe)
    ? (string) $aboutMe
    : '';

$aboutMeCompletion = isset($aboutMeCompletion)
    && is_array($aboutMeCompletion)
    ? $aboutMeCompletion
    : [];

$validationErrors = isset($validationErrors)
    && is_array($validationErrors)
    ? $validationErrors
    : [];

$formAlert = isset($formAlert) && is_array($formAlert)
    ? $formAlert
    : null;

$this->extend('Layouts/Main');
$this->section('content');
?>

<section class="py-3 py-lg-4">
    <div class="container">

        <?= view(
            'Pages/Profile/Partials/_feedback_alert',
            [
                'formAlert' => $formAlert,
            ]
        ) ?>

        <div class="row justify-content-center">
            <div class="col-12 col-lg-10 col-xl-8">

                <div class="d-flex align-items-start gap-3 mb-3">
                    <div>
                        <a
                            href="<?= url_to('web.profile.edit') ?>"
                            class="d-inline-flex align-items-center
                                gap-1 text-primary fw-medium mb-2">

                            <i
                                class="ri-arrow-left-line"
                                aria-hidden="true">
                            </i>

                            Back to Profile
                        </a>

                        <div
                            class="d-flex align-items-center
                                gap-2 mt-2">

                            <div
                                class="avatar-sm flex-shrink-0"
                                aria-hidden="true">

                                <span
                                    class="avatar-title rounded-circle
                                        bg-info-subtle text-info">

                                    <i
                                        class="ri-double-quotes-l
                                            fs-20">
                                    </i>
                                </span>
                            </div>

                            <div>
                                <h2 class="fs-16 fw-semibold mb-1">
                                    About Me
                                </h2>

                                <p class="text-muted fs-13 mb-0">
                                    Introduce yourself in your own words.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="card border border-danger
                        border-opacity-25 shadow-none mb-0">

                    <div class="card-body p-3 p-md-4">

                        <?= view(
                            'Pages/Profile/Sections/AboutMe/_form',
                            [
                                'aboutMe' => $aboutMe,
                                'validationErrors' =>
                                $validationErrors,
                            ]
                        ) ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php $this->endSection(); ?>