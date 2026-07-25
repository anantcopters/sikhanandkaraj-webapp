<?php

declare(strict_types=1);

/**
 * Member profile completion journey.
 *
 * @var array<string, mixed>              $user
 * @var array<string, mixed>|null         $basicDetails
 * @var array<string, int>                $basicDetailsCompletion
 * @var array<string, mixed>|null         $educationProfession
 * @var array<string, int>                $educationProfessionCompletion
 * @var array<string, mixed>|null         $familyDetails
 * @var array<string, int>                $familyDetailsCompletion
 * @var array<string, mixed>|null         $sikhReligiousDetails
 * @var array<string, int>                $sikhReligiousDetailsCompletion
 * @var list<array<string, mixed>>         $lifestyleDetails
 * @var array<string, int>                $lifestyleCompletion
 * @var array<string, mixed>              $overallProfileSummary
 * @var array<string, string>|null        $formAlert
 * @var array<int, array<string, string>> $upcomingSections
 * @var string                            $aboutMe
 * @var array<string, int>                $aboutMeCompletion
 * @var string                            $profileImage
 */

$user = isset($user) && is_array($user)
    ? $user
    : [];

$basicDetails = isset($basicDetails)
    && is_array($basicDetails)
    ? $basicDetails
    : [];

$basicDetailsCompletion = isset($basicDetailsCompletion)
    && is_array($basicDetailsCompletion)
    ? $basicDetailsCompletion
    : [];

$educationProfession = isset($educationProfession)
    && is_array($educationProfession)
    ? $educationProfession
    : [];

$educationProfessionCompletion = isset(
    $educationProfessionCompletion
) && is_array($educationProfessionCompletion)
    ? $educationProfessionCompletion
    : [];

$familyDetails = isset($familyDetails)
    && is_array($familyDetails)
    ? $familyDetails
    : [];

$familyDetailsCompletion = isset($familyDetailsCompletion)
    && is_array($familyDetailsCompletion)
    ? $familyDetailsCompletion
    : [];

$sikhReligiousDetails = isset($sikhReligiousDetails)
    && is_array($sikhReligiousDetails)
    ? $sikhReligiousDetails
    : [];

$sikhReligiousDetailsCompletion = isset(
    $sikhReligiousDetailsCompletion
) && is_array($sikhReligiousDetailsCompletion)
    ? $sikhReligiousDetailsCompletion
    : [];

$lifestyleDetails = isset($lifestyleDetails)
    && is_array($lifestyleDetails)
    ? $lifestyleDetails
    : [];

$lifestyleCompletion = isset($lifestyleCompletion)
    && is_array($lifestyleCompletion)
    ? $lifestyleCompletion
    : [];

$aboutMe = isset($aboutMe)
    ? trim((string) $aboutMe)
    : '';

$aboutMeCompletion = isset($aboutMeCompletion)
    && is_array($aboutMeCompletion)
    ? $aboutMeCompletion
    : [];

$overallProfileSummary = isset($overallProfileSummary)
    && is_array($overallProfileSummary)
    ? $overallProfileSummary
    : [];

$upcomingSections = isset($upcomingSections)
    && is_array($upcomingSections)
    ? $upcomingSections
    : [];

$profileImage = isset($profileImage)
    ? trim((string) $profileImage)
    : '';

$hasApprovedProfileImage = $profileImage !== '';

$this->extend('Layouts/Main');
$this->section('content');
?>

<section class="py-3 py-lg-3">
    <div class="container">

        <?= view('Pages/Profile/Partials/_page_header') ?>

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
                        $overallProfileSummary,

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

                <div class="card border shadow-none mb-3">
                    <div class="card-body p-3 p-md-4">

                        <div
                            class="d-flex flex-column flex-sm-row
                                align-items-sm-center
                                justify-content-between gap-3">

                            <div class="d-flex align-items-center gap-3">

                                <div class="flex-shrink-0">
                                    <?php if ($hasApprovedProfileImage): ?>
                                        <img
                                            src="<?= esc($profileImage) ?>"
                                            alt="Profile photo"
                                            class="avatar-lg rounded-circle
                                                object-fit-cover border"
                                            loading="lazy">
                                    <?php else: ?>
                                        <div
                                            class="avatar-lg rounded-circle
                                                bg-light border d-flex
                                                align-items-center
                                                justify-content-center
                                                text-muted"
                                            aria-hidden="true">

                                            <i class="ri-user-3-line fs-24"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div>
                                    <div
                                        class="d-flex align-items-center
                                            flex-wrap gap-2 mb-1">

                                        <h3 class="fs-16 fw-semibold mb-0">
                                            Profile Photos
                                        </h3>

                                        <?php if (
                                            $hasApprovedProfileImage
                                        ): ?>
                                            <span
                                                class="badge
                                                    text-bg-success">
                                                Approved
                                            </span>
                                        <?php else: ?>
                                            <span
                                                class="badge
                                                    text-bg-warning">
                                                Pending
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <p class="text-muted fs-13 mb-0">
                                        Upload up to five photos, choose
                                        your main photo and control who can
                                        view each photo.
                                    </p>
                                </div>
                            </div>

                            <div class="flex-shrink-0">
                                <a
                                    href="<?= url_to(
                                                'web.profile.photos'
                                            ) ?>"
                                    class="btn btn-outline-primary btn-sm">

                                    <i
                                        class="ri-image-edit-line me-1"
                                        aria-hidden="true">
                                    </i>

                                    Manage Photos
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <?= view(
                    'Pages/Profile/Sections/BasicDetails/_card',
                    [
                        'user' => $user,

                        'basicDetails' =>
                        $basicDetails,

                        'basicDetailsCompletion' =>
                        $basicDetailsCompletion,
                    ]
                ) ?>

                <?= view(
                    'Pages/Profile/Sections/'
                        . 'EducationProfession/_card',
                    [
                        'educationProfession' =>
                        $educationProfession,

                        'educationProfessionCompletion' =>
                        $educationProfessionCompletion,
                    ]
                ) ?>

                <?= view(
                    'Pages/Profile/Sections/FamilyDetails/_card',
                    [
                        'familyDetails' =>
                        $familyDetails,

                        'familyDetailsCompletion' =>
                        $familyDetailsCompletion,
                    ]
                ) ?>

                <?= view(
                    'Pages/Profile/Sections/'
                        . 'SikhReligiousDetails/_card',
                    [
                        'sikhReligiousDetails' =>
                        $sikhReligiousDetails,

                        'sikhReligiousDetailsCompletion' =>
                        $sikhReligiousDetailsCompletion,
                    ]
                ) ?>

                <?= view(
                    'Pages/Profile/Sections/Lifestyle/_card',
                    [
                        'lifestyleDetails' =>
                        $lifestyleDetails,

                        'lifestyleCompletion' =>
                        $lifestyleCompletion,
                    ]
                ) ?>

                <?php if ($upcomingSections !== []): ?>
                    <?= view(
                        'Pages/Profile/Partials/_section_list',
                        [
                            'upcomingSections' =>
                            $upcomingSections,
                        ]
                    ) ?>
                <?php endif; ?>

            </div>

            <div class="col-12 col-lg-4">
                <?= view(
                    'Pages/Profile/Partials/_profile_sidebar',
                    [
                        'user' => $user,

                        'overallProfileSummary' =>
                        $overallProfileSummary,

                        'aboutMe' => $aboutMe,

                        'aboutMeCompletion' =>
                        $aboutMeCompletion,
                    ]
                ) ?>
            </div>

        </div>
    </div>
</section>

<?php $this->endSection(); ?>