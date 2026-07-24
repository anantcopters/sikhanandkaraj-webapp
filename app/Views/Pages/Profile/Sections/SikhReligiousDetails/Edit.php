<?php

declare(strict_types=1);

/** @var string|null $pageTitle */
/** @var array<string, mixed>|null $user */
/** @var array<string, mixed>|null $sikhReligiousDetails */
/** @var array<string, mixed>|null $sikhReligiousDetailsCompletion */
/** @var array<string, mixed>|null $masterData */
/** @var array<string, string>|null $validationErrors */
/** @var array<string, mixed>|null $formAlert */

$pageTitle = isset($pageTitle)
    ? (string) $pageTitle
    : 'Sikh & Religious Details';

$user = isset($user) && is_array($user)
    ? $user
    : [];

$sikhReligiousDetails = isset($sikhReligiousDetails)
    && is_array($sikhReligiousDetails)
    ? $sikhReligiousDetails
    : [];

$sikhReligiousDetailsCompletion = isset(
    $sikhReligiousDetailsCompletion
)
    && is_array($sikhReligiousDetailsCompletion)
    ? $sikhReligiousDetailsCompletion
    : [];

$masterData = isset($masterData)
    && is_array($masterData)
    ? $masterData
    : [];

$validationErrors = isset($validationErrors)
    && is_array($validationErrors)
    ? $validationErrors
    : [];

$formAlert = isset($formAlert)
    && is_array($formAlert)
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
                <div class="mb-3">
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

                    <div class="d-flex align-items-center gap-2 mt-2">
                        <div
                            class="avatar-sm flex-shrink-0"
                            aria-hidden="true">

                            <span
                                class="avatar-title rounded-circle
                            bg-primary-subtle text-primary">

                                <i class="ri-service-line fs-20"></i>
                            </span>
                        </div>

                        <div>
                            <h2 class="fs-16 fw-semibold mb-1">
                                Sikh &amp; Religious Details
                            </h2>

                            <p class="text-muted fs-13 mb-0">
                                Share your community and birthplace
                                information. Astrological details are
                                optional.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="card border shadow-none mb-0">
                    <div class="card-body p-3 p-md-4">
                        <?= view(
                            'Pages/Profile/Sections/'
                                . 'SikhReligiousDetails/_form',
                            [
                                'sikhReligiousDetails' =>
                                $sikhReligiousDetails,

                                'masterData' =>
                                $masterData,

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