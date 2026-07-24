<?php

declare(strict_types=1);

/**
 * Member profile completion journey.
 *
 * @var array<string, mixed>       $user
 * @var array<string, mixed>|null  $basicDetails
 * @var array<string, int>         $basicDetailsCompletion
 * @var array<string, mixed>|null  $educationProfession
 * @var array<string, int>         $educationProfessionCompletion
 * @var array<string, mixed>       $overallProfileSummary
 * @var array<string, string>|null $formAlert
 * @var array<int, array<string, string>> $upcomingSections
 */

/**
 * @var list<array<string, mixed>> $lifestyleDetails
 * @var array<string, int> $lifestyleCompletion
 */

$lifestyleDetails = isset($lifestyleDetails)
    && is_array($lifestyleDetails)
    ? $lifestyleDetails
    : [];

$lifestyleCompletion = isset($lifestyleCompletion)
    && is_array($lifestyleCompletion)
    ? $lifestyleCompletion
    : [];

$this->extend('Layouts/Main');
$this->section('content');
?>

<section class="py-3 py-lg-3">
    <div class="container">

        <?= view(
            'Pages/Profile/Partials/_page_header'
        ) ?>

        <?= view(
            'Pages/Profile/Partials/_feedback_alert',
            [
                'formAlert' => $formAlert ?? null,
            ]
        ) ?>

        <div class="row g-4 align-items-start">


            <div class="col-12 col-lg-8">
                <?= view(
                    'Pages/Profile/Partials/_completion_summary',
                    [
                        'overallProfileSummary' =>
                        $overallProfileSummary ?? [],

                        'nextProfileSection' =>
                        $nextProfileSection ?? null,
                    ]
                ) ?>

                <div class="mb-3">
                    <h2 class="fs-18 fw-semibold mb-1">
                        Profile Sections
                    </h2>

                    <p class="text-muted fs-13 mb-0">
                        Complete each section separately.
                    </p>
                </div>

                <?= view(
                    'Pages/Profile/Sections/BasicDetails/_card',
                    [
                        'user' => $user ?? [],

                        'basicDetails' =>
                        $basicDetails ?? [],

                        'basicDetailsCompletion' =>
                        $basicDetailsCompletion ?? [],
                    ]
                ) ?>

                <?= view(
                    'Pages/Profile/Sections/'
                        . 'EducationProfession/_card',
                    [
                        'educationProfession' =>
                        $educationProfession ?? [],

                        'educationProfessionCompletion' =>
                        $educationProfessionCompletion
                            ?? [],
                    ]
                ) ?>

                <?= view(
                    'Pages/Profile/Sections/FamilyDetails/_card',
                    [
                        'familyDetails' =>
                        $familyDetails ?? [],

                        'familyDetailsCompletion' =>
                        $familyDetailsCompletion ?? [],
                    ]
                ) ?>

                <?= view(
                    'Pages/Profile/Sections/'
                        . 'SikhReligiousDetails/_card',
                    [
                        'sikhReligiousDetails' =>
                        $sikhReligiousDetails ?? [],

                        'sikhReligiousDetailsCompletion' =>
                        $sikhReligiousDetailsCompletion ?? [],
                    ]
                ) ?>

                <?= view(
                    'Pages/Profile/Sections/Lifestyle/_card',
                    [
                        'lifestyleDetails' => $lifestyleDetails,
                        'lifestyleCompletion' =>
                        $lifestyleCompletion,
                    ]
                ) ?>

                <?= view(
                    'Pages/Profile/Partials/_section_list',
                    [
                        'upcomingSections' =>
                        $upcomingSections ?? [],
                    ]
                ) ?>
            </div>
            <div class="col-12 col-lg-4">
                <?= view(
                    'Pages/Profile/Partials/_profile_sidebar',
                    [
                        'user' => $user ?? [],

                        'overallProfileSummary' =>
                        $overallProfileSummary ?? [],
                    ]
                ) ?>
            </div>
        </div>
    </div>
</section>

<?php $this->endSection(); ?>