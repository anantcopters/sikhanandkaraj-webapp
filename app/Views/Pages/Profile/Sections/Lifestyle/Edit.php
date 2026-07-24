<?php

declare(strict_types=1);

/**
 * @var string                     $pageTitle
 * @var array<string, mixed>       $user
 * @var list<array<string, mixed>> $categories
 * @var array<int, list<array<string, mixed>>> $optionsByCategory
 * @var list<int>                  $selectedOptionIds
 * @var array<string, int>         $lifestyleCompletion
 * @var array<string, string>      $validationErrors
 * @var array<string, string>|null $formAlert
 */

$pageTitle = isset($pageTitle)
    ? (string) $pageTitle
    : 'Lifestyle';

$user = isset($user) && is_array($user)
    ? $user
    : [];

$categories = isset($categories) && is_array($categories)
    ? $categories
    : [];

$optionsByCategory = isset($optionsByCategory)
    && is_array($optionsByCategory)
    ? $optionsByCategory
    : [];

$selectedOptionIds = isset($selectedOptionIds)
    && is_array($selectedOptionIds)
    ? $selectedOptionIds
    : [];

$lifestyleCompletion = isset($lifestyleCompletion)
    && is_array($lifestyleCompletion)
    ? $lifestyleCompletion
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
                                        bg-primary-subtle text-primary">

                                    <i
                                        class="ri-heart-pulse-line fs-20">
                                    </i>
                                </span>
                            </div>

                            <div>
                                <h2 class="fs-16 fw-semibold mb-1">
                                    Lifestyle
                                </h2>

                                <p class="text-muted fs-13 mb-0">
                                    Share your interests, entertainment,
                                    fitness and food choices.
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
                            'Pages/Profile/Sections/Lifestyle/_form',
                            [
                                'categories' => $categories,
                                'optionsByCategory' =>
                                $optionsByCategory,
                                'selectedOptionIds' =>
                                $selectedOptionIds,
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