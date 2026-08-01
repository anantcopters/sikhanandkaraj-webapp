<?php

declare(strict_types=1);

use App\Support\BooleanValue;

/**
 * Authenticated member profile preview.
 *
 * @var array<string, mixed>       $user
 * @var array<string, mixed>|null  $basicDetails
 * @var array<string, mixed>|null  $educationProfession
 * @var array<string, mixed>|null  $familyDetails
 * @var list<array<string, mixed>> $lifestyleDetails
 * @var string                     $aboutMe
 * @var string                     $profileImage
 * @var array<string, mixed>       $overallProfileSummary
 * @var list<array{
 *     id:int,
 *     mediumUrl:string,
 *     modalUrl:string,
 *     isPrimary:bool
 * }>                              $approvedPhotos
 */

$user = isset($user) && is_array($user)
    ? $user
    : [];

$basicDetails = isset($basicDetails)
    && is_array($basicDetails)
    ? $basicDetails
    : [];

$educationProfession = isset($educationProfession)
    && is_array($educationProfession)
    ? $educationProfession
    : [];

$familyDetails = isset($familyDetails)
    && is_array($familyDetails)
    ? $familyDetails
    : [];

$lifestyleDetails = isset($lifestyleDetails)
    && is_array($lifestyleDetails)
    ? $lifestyleDetails
    : [];

$approvedPhotos = isset($approvedPhotos)
    && is_array($approvedPhotos)
    ? $approvedPhotos
    : [];

$overallProfileSummary = isset($overallProfileSummary)
    && is_array($overallProfileSummary)
    ? $overallProfileSummary
    : [];

$aboutMe = trim(
    (string) ($aboutMe ?? '')
);

$profileImage = trim(
    (string) ($profileImage ?? '')
);

$fullName = trim(
    (string) ($user['full_name'] ?? '')
);

if ($fullName === '') {
    $fullName = 'Member Profile';
}

$profileReference = trim(
    (string) (
        $user['profile_ref_number']
        ?? ''
    )
);

$profileCreatedForCode = strtoupper(
    trim(
        (string) (
            $user['profile_created_for']
            ?? ''
        )
    )
);

$profileCreatedForLabels = [
    'SELF'     => 'Self',
    'PARENT'   => 'Parents',
    'SON'      => 'Parents',
    'DAUGHTER' => 'Parents',
    'BROTHER'  => 'Brother',
    'SISTER'   => 'Sister',
    'RELATIVE' => 'Relative',
    'FRIEND'   => 'Friend',
    'OTHER'    => 'Other',
];

$profileCreatedFor =
    $profileCreatedForLabels[$profileCreatedForCode]
    ?? ucwords(
        strtolower(
            str_replace(
                '_',
                ' ',
                $profileCreatedForCode
            )
        )
    );

$gender = ucfirst(
    strtolower(
        trim(
            (string) ($user['gender'] ?? '')
        )
    )
);

$dateOfBirth = trim(
    (string) (
        $basicDetails['date_of_birth']
        ?? ''
    )
);

$formattedDateOfBirth = '';

if ($dateOfBirth !== '') {
    try {
        $formattedDateOfBirth = (
            new DateTimeImmutable($dateOfBirth)
        )->format('d M Y');
    } catch (Throwable) {
        $formattedDateOfBirth = '';
    }
}

$age = null;

if ($dateOfBirth !== '') {
    try {
        $birthDate = new DateTimeImmutable(
            $dateOfBirth
        );

        $today = new DateTimeImmutable('today');

        if ($birthDate <= $today) {
            $age = $birthDate
                ->diff($today)
                ->y;
        }
    } catch (Throwable) {
        $age = null;
    }
}

$height = trim(
    (string) (
        $basicDetails['height_display_name']
        ?? ''
    )
);

$community = trim(
    (string) (
        $familyDetails['community_name']
        ?? ''
    )
);

$gotra = trim(
    (string) (
        $familyDetails['gotra']
        ?? ''
    )
);

$currentLocation = implode(
    ', ',
    array_filter(
        [
            trim(
                (string) (
                    $basicDetails['city_name']
                    ?? ''
                )
            ),
            trim(
                (string) (
                    $basicDetails['state_name']
                    ?? ''
                )
            ),
            trim(
                (string) (
                    $basicDetails['country_name']
                    ?? ''
                )
            ),
        ],
        static fn(string $value): bool =>
        $value !== ''
    )
);

$familyLocation = implode(
    ', ',
    array_filter(
        [
            trim(
                (string) (
                    $familyDetails['city_name']
                    ?? ''
                )
            ),
            trim(
                (string) (
                    $familyDetails['state_name']
                    ?? ''
                )
            ),
            trim(
                (string) (
                    $familyDetails['country_name']
                    ?? ''
                )
            ),
        ],
        static fn(string $value): bool =>
        $value !== ''
    )
);

$employmentLabels = [
    'GOVERNMENT_PSU' => 'Government / PSU',
    'PRIVATE' => 'Private',
    'BUSINESS' => 'Business',
    'DEFENSE' => 'Defence',
    'SELF_EMPLOYED' => 'Self Employed',
    'NOT_WORKING' => 'Not Working',
];

$employmentCode = strtoupper(
    trim(
        (string) (
            $educationProfession['employed_in']
            ?? ''
        )
    )
);

$employmentLabel = $employmentLabels[$employmentCode] ?? '';

$completionPercentage = max(
    0,
    min(
        100,
        (int) (
            $overallProfileSummary['percentage']
            ?? 0
        )
    )
);

/**
 * Return a readable value for profile presentation.
 */
$displayValue = static function (
    mixed $value,
    string $fallback = 'Not added'
): string {
    $text = trim((string) $value);

    return $text !== ''
        ? $text
        : $fallback;
};

$childrenLivingTogether = '';

if (
    array_key_exists(
        'children_living_together',
        $basicDetails
    )
    && $basicDetails['children_living_together']
    !== null
) {
    $childrenLivingTogether =
        BooleanValue::fromDatabase(
            $basicDetails['children_living_together']
        )
        ? 'Yes'
        : 'No';
}

$isNeverMarried = strtoupper(
    trim(
        (string) (
            $basicDetails['marital_status_code']
            ?? ''
        )
    )
) === 'NEVER_MARRIED';

if (
    !$isNeverMarried
    && strtoupper(
        trim(
            (string) (
                $basicDetails['marital_status_name']
                ?? ''
            )
        )
    ) === 'NEVER MARRIED'
) {
    $isNeverMarried = true;
}

$personalDetails = [
    'Gender' =>
    $gender,

    'Date of Birth' =>
    $formattedDateOfBirth,

    'Age' =>
    $age !== null
        ? $age . ' Years'
        : '',

    'Marital Status' =>
    $basicDetails['marital_status_name']
        ?? '',

    'Number of Children' =>
    $isNeverMarried
        ? ''
        : (
            $basicDetails['number_of_children']
            ?? ''
        ),

    'Children Living Together' =>
    $isNeverMarried
        ? ''
        : $childrenLivingTogether,

    'Height' =>
    $height,

    'Mother Tongue' =>
    $basicDetails['mother_tongue_name']
        ?? '',

    'Drinking Habit' =>
    $basicDetails['drinking_habit_name']
        ?? '',

    'Eating Habit' =>
    $basicDetails['eating_habit_name']
        ?? '',

    'Physical Status' =>
    $basicDetails['physical_status_name']
        ?? '',

    'Country' =>
    $basicDetails['country_name']
        ?? '',

    'State' =>
    $basicDetails['state_name']
        ?? '',

    'City' =>
    $basicDetails['city_name']
        ?? '',
];

$professionDetails = [
    'Highest Education' =>
    $educationProfession['highest_education_name'] ?? '',

    'Education in Detail' =>
    $educationProfession['education_detail'] ?? '',

    'College / Institution' =>
    $educationProfession['college_institution'] ?? '',

    'Employed In' =>
    $employmentLabel,

    'Occupation' =>
    $educationProfession['occupation_name'] ?? '',

    'Occupation in Detail' =>
    $educationProfession['occupation_detail'] ?? '',

    'Organization' =>
    $educationProfession['organization'] ?? '',

    'Annual Income' =>
    $educationProfession['annual_income_display_name'] ?? '',
];

$familyDetailList = [
    "Father's Name" =>
    $familyDetails['father_name']
        ?? '',

    "Father's Occupation" =>
    $familyDetails['father_occupation_name'] ?? '',

    "Mother's Name" =>
    $familyDetails['mother_name']
        ?? '',

    "Mother's Occupation" =>
    $familyDetails['mother_occupation_name'] ?? '',

    'Number of Brothers' =>
    $familyDetails['brothers_count']
        ?? '',

    'Number of Sisters' =>
    $familyDetails['sisters_count']
        ?? '',

    'Family Type' =>
    $familyDetails['family_type_name']
        ?? '',

    'Family Status' =>
    $familyDetails['family_status_name'] ?? '',

    'Family Values' =>
    $familyDetails['family_value_name'] ?? '',

    'Community' => $community,
    'Gotra' => $gotra,
    'Family Location' => $familyLocation,

    'Nearest Gurudwara' =>
    $familyDetails['nearest_gurudwara'] ?? '',

    'Reference Person 1' =>
    $familyDetails['reference_person_1'] ?? '',

    'Reference Person 2' =>
    $familyDetails['reference_person_2'] ?? '',
];

/*
 * Prepare one normalized photo collection for the profile gallery.
 *
 * The controller has already:
 *
 * - filtered non-approved photos;
 * - required a medium URL;
 * - resolved the modal URL;
 * - normalized the primary-photo flag.
 *
 * The view performs only defensive presentation normalization.
 */
$galleryPhotos = [];

foreach ($approvedPhotos as $photo) {
    if (!is_array($photo)) {
        continue;
    }

    $mediumUrl = trim(
        (string) (
            $photo['mediumUrl']
            ?? ''
        )
    );

    $modalUrl = trim(
        (string) (
            $photo['modalUrl']
            ?? ''
        )
    );

    if ($mediumUrl === '') {
        continue;
    }

    /*
     * A modal can still display the medium photo if its explicitly
     * authorized original URL was unavailable.
     */
    if ($modalUrl === '') {
        $modalUrl = $mediumUrl;
    }

    $galleryPhotos[] = [
        'id' => (int) (
            $photo['id']
            ?? 0
        ),

        'mediumUrl' => $mediumUrl,

        'modalUrl' => $modalUrl,

        'isPrimary' => (
            $photo['isPrimary']
            ?? false
        ) === true,
    ];
}

$this->extend('Layouts/Main');
$this->section('content');
?>

<section class="py-3 py-lg-4">
    <div class="container">

        <div class="mb-2">
            <a
                href="<?= url_to(
                            'web.profile.edit'
                        ) ?>"
                class="d-inline-flex align-items-center
                    gap-1 text-primary fw-medium mb-2">

                <i
                    class="ri-arrow-left-line"
                    aria-hidden="true"></i>

                Back to profile
            </a>
        </div>

        <div
            class="profile-preview-notice
                border rounded-3 p-3 mb-3">

            <div class="d-flex align-items-start gap-3">
                <span
                    class="d-inline-flex align-items-center
                        justify-content-center rounded-circle
                        bg-warning-subtle text-warning
                        flex-shrink-0"
                    style="width: 40px; height: 40px;">

                    <i
                        class="ri-eye-line fs-18"
                        aria-hidden="true"></i>
                </span>

                <div>
                    <h2 class="fs-15 fw-semibold mb-1">
                        This is your profile preview
                    </h2>

                    <p class="text-muted fs-13 mb-0">
                        Only approved photos and saved profile
                        details are displayed below.
                    </p>
                </div>
            </div>
        </div>

        <!-- Main profile summary card. -->
        <article
            class="card border border-danger border-opacity-25 shadow-sm
                rounded-3 mb-4">

            <div
                class="card-body
                    position-relative p-3 p-lg-4">

                <a
                    href="<?= url_to(
                                'web.profile.edit'
                            ) ?>"
                    class="btn btn-outline-primary
        d-inline-flex align-items-center
        justify-content-center gap-1
        position-absolute top-0 end-0
        mt-3 me-3 mt-lg-4 me-lg-4
        profile-preview-edit-button">

                    <i
                        class="ri-edit-line"
                        aria-hidden="true"></i>

                    Edit My Profile
                </a>



                <div class="row g-4 pt-5 pt-md-0">

                    <div
                        class="col-12
                            col-md-5 col-lg-4">

                        <div
                            class="position-relative
                                overflow-hidden rounded-3
                                bg-light">

                            <?php if ($profileImage !== ''): ?>

                                <img
                                    src="<?= esc(
                                                $profileImage,
                                                'attr'
                                            ) ?>"
                                    alt="<?= esc(
                                                $fullName . ' profile photo',
                                                'attr'
                                            ) ?>"
                                    class="w-100 profile-preview-main-photo
            object-fit-cover"
                                    loading="eager">

                            <?php else: ?>

                                <div
                                    class="ratio ratio-4x3
            d-flex align-items-center
            justify-content-center
            text-muted"
                                    aria-label="Profile photo unavailable">

                                    <div
                                        class="d-flex flex-column
                align-items-center
                justify-content-center">

                                        <i
                                            class="ri-user-3-line fs-32"
                                            aria-hidden="true"></i>

                                        <span class="fs-13 mt-2">
                                            No approved profile photo
                                        </span>
                                    </div>
                                </div>

                            <?php endif; ?>
                        </div>
                    </div>

                    <div
                        class="col-12
                            col-md-7 col-lg-8">

                        <div
                            class="h-100 d-flex flex-column
                                justify-content-center">

                            <div class="pe-md-3 pe-lg-5 mb-4">
                                <div
                                    class="d-flex
                                        align-items-center
                                        flex-wrap gap-2 mb-2">

                                    <h2
                                        class="fs-24
                                            fw-bold mb-0">
                                        <?= esc($fullName) ?>
                                    </h2>

                                    <?php if (
                                        strtoupper(
                                            trim(
                                                (string) (
                                                    $user['account_status']
                                                    ?? ''
                                                )
                                            )
                                        ) === 'APPROVED'
                                    ): ?>
                                        <i
                                            class="
                                                ri-checkbox-circle-fill
                                                text-success fs-18"
                                            aria-label="
                                                Approved profile">
                                        </i>
                                    <?php endif; ?>
                                </div>

                                <p class="fs-14 mb-2">
                                    <?php if (
                                        $age !== null
                                    ): ?>
                                        <?= esc(
                                            (string) $age
                                        ) ?> Years
                                    <?php endif; ?>

                                    <?php if (
                                        $age !== null
                                        && $height !== ''
                                    ): ?>
                                        <span class="mx-1">
                                            •
                                        </span>
                                    <?php endif; ?>

                                    <?php if (
                                        $height !== ''
                                    ): ?>
                                        <?= esc($height) ?>
                                    <?php endif; ?>
                                </p>

                                <?php if (
                                    $currentLocation !== ''
                                ): ?>
                                    <p
                                        class="text-muted
                                            mb-2">

                                        <i
                                            class="ri-map-pin-line
                                                me-1"
                                            aria-hidden="true"></i>

                                        <?= esc(
                                            $currentLocation
                                        ) ?>
                                    </p>
                                <?php endif; ?>
                                <?php if (
                                    $community !== ''
                                    || $gotra !== ''
                                ): ?>
                                    <p
                                        class="text-muted
                                            mb-2">

                                        <i
                                            class="ri-community-line
                                                me-1"
                                            aria-hidden="true"></i>

                                        <?= esc(
                                            implode(
                                                ' • ',
                                                array_filter(
                                                    [
                                                        $community,
                                                        $gotra !== ''
                                                            ? 'Gotra: '
                                                            . $gotra
                                                            : '',
                                                    ]
                                                )
                                            )
                                        ) ?>
                                    </p>
                                <?php endif; ?>
                                <?php if (
                                    $profileCreatedFor !== ''
                                ): ?>
                                    <p
                                        class="text-danger fs-14
                                            mb-0">

                                        <i
                                            class="ri-heart-line
                                                me-1 text-muted"
                                            aria-hidden="true"></i>
                                        <span class="text-muted">Profile Created By : </span>
                                        <?= esc(
                                            $profileCreatedFor
                                        ) ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <div
                                class="row g-3
                                    border-top pt-0">

                                <div class="col-12 col-sm-6">
                                    <div
                                        class="d-flex
                                            align-items-start gap-3">

                                        <span
                                            class="d-inline-flex
                                                align-items-center
                                                justify-content-center
                                                rounded-circle
                                                bg-primary-subtle
                                                text-primary
                                                flex-shrink-0"
                                            style="
                                                width: 38px;
                                                height: 38px;
                                            ">

                                            <i
                                                class="
                                                    ri-fingerprint-line fs-20"
                                                aria-hidden="true">
                                            </i>
                                        </span>

                                        <div>
                                            <div
                                                class="
                                                    text-muted fs-12">
                                                Profile ID
                                            </div>

                                            <strong class="fs-14">
                                                <?= esc(
                                                    $displayValue(
                                                        $profileReference
                                                    )
                                                ) ?>
                                            </strong>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 col-sm-6">
                                    <div
                                        class="d-flex
                                            align-items-start gap-3">

                                        <span
                                            class="d-inline-flex
                                                align-items-center
                                                justify-content-center
                                                rounded-circle
                                                bg-primary-subtle
                                                text-primary
                                                flex-shrink-0"
                                            style="
                                                width: 38px;
                                                height: 38px;
                                            ">

                                            <i
                                                class="
                                                    ri-pie-chart-line fs-20"
                                                aria-hidden="true">
                                            </i>
                                        </span>

                                        <div>
                                            <div
                                                class="
                                                    text-muted fs-12">
                                                Profile Completion
                                            </div>

                                            <strong
                                                class="fs-13
                                                        text-success">
                                                <?= $completionPercentage ?>%
                                            </strong>
                                        </div>
                                    </div>
                                </div>

                                

                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </article>
        <div class="row mb-0">
            <div class="col-12">
                <section
                    class="card border border-danger border-opacity-25 shadow-sm
                rounded-3">

                    <div class="card-body p-3 p-lg-4">

                        <div
                            class="d-flex
                        align-items-center gap-2 mb-3">

                            <span
                                class="d-inline-flex
                            align-items-center
                            justify-content-center
                            rounded-circle
                            bg-primary-subtle
                            text-primary"
                                style="
                            width: 34px;
                            height: 34px;
                        ">

                                <i
                                    class="ri-image-2-line"
                                    aria-hidden="true"></i>
                            </span>

                            <h2
                                class="fs-16
                            fw-semibold mb-0">
                                Gallery
                            </h2>
                        </div>

                        <?php
                        $visiblePhotoCount = 0;
                        ?>

                        <?php if ($galleryPhotos !== []): ?>

                            <div class="row g-2 g-md-3">

                                <?php foreach (
                                    $galleryPhotos as $photoIndex => $galleryPhoto
                                ): ?>

                                    <?php
                                    $mediumUrl = (string) (
                                        $galleryPhoto['mediumUrl']
                                        ?? ''
                                    );

                                    $modalUrl = (string) (
                                        $galleryPhoto['modalUrl']
                                        ?? $mediumUrl
                                    );

                                    $isPrimary = (
                                        $galleryPhoto['isPrimary']
                                        ?? false
                                    ) === true;
                                    ?>

                                    <div class="col-6 col-md-4 col-lg-3">

                                        <button
                                            type="button"
                                            class="profile-preview-gallery-button
                        d-block w-100 p-0 border rounded-3
                        overflow-hidden bg-light
                        position-relative"
                                            data-bs-toggle="modal"
                                            data-bs-target="#profilePhotoModal"
                                            data-profile-photo-index="<?= esc(
                                                                            (string) $photoIndex,
                                                                            'attr'
                                                                        ) ?>"
                                            aria-label="<?= esc(
                                                            'Open photo '
                                                                . ($photoIndex + 1)
                                                                . ' in enlarged view',
                                                            'attr'
                                                        ) ?>">

                                            <span
                                                class="ratio ratio-1x1 d-block">

                                                <img
                                                    src="<?= esc(
                                                                $mediumUrl,
                                                                'attr'
                                                            ) ?>"
                                                    alt="<?= esc(
                                                                $fullName
                                                                    . ' profile photo '
                                                                    . ($photoIndex + 1),
                                                                'attr'
                                                            ) ?>"
                                                    class="w-100 h-100
                                object-fit-cover"
                                                    loading="lazy">

                                            </span>

                                            <?php if ($isPrimary): ?>

                                                <span
                                                    class="badge bg-primary
                                position-absolute
                                top-0 start-0 m-2">

                                                    <i
                                                        class="ri-star-fill me-1"
                                                        aria-hidden="true"></i>

                                                    Main
                                                </span>

                                            <?php endif; ?>

                                        </button>

                                        <input
                                            type="hidden"
                                            value="<?= esc(
                                                        $modalUrl,
                                                        'attr'
                                                    ) ?>"
                                            data-profile-modal-url="<?= esc(
                                                                        (string) $photoIndex,
                                                                        'attr'
                                                                    ) ?>">

                                    </div>

                                <?php endforeach; ?>

                            </div>

                        <?php else: ?>

                            <div
                                class="border rounded-3
            text-center p-4 text-muted">

                                <i
                                    class="ri-image-line fs-24"
                                    aria-hidden="true"></i>

                                <p class="fs-13 mb-0 mt-2">
                                    No approved photos are available.
                                </p>
                            </div>

                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </div>
        <div class="row g-4 align-items-start">

            <!-- One single card for every section in the left column. -->
            <div class="col-12 col-lg-7">
                <div
                    class="card border border-danger border-opacity-25 shadow-sm
                        rounded-3 overflow-hidden">

                    <section
                        class="card-body p-3 p-lg-4
                            border-bottom">



                        <div
                            class="d-flex
                                align-items-center gap-2 mb-3">

                            <span
                                class="d-inline-flex
                                    align-items-center
                                    justify-content-center
                                    rounded-circle
                                    bg-primary-subtle
                                    text-primary"
                                style="
                                    width: 34px;
                                    height: 34px;
                                ">

                                <i
                                    class=" fs-18
                                        ri-user-smile-line"
                                    aria-hidden="true"></i>
                            </span>

                            <h2
                                class="fs-16
                                    fw-semibold mb-0">
                                About Me
                            </h2>
                        </div>

                        <?php if ($aboutMe !== ''): ?>
                            <p
                                class="text-body-secondary
                                    lh-lg mb-0">
                                <?= nl2br(
                                    esc($aboutMe)
                                ) ?>
                            </p>
                        <?php else: ?>
                            <p class="text-muted mb-0">
                                About Me has not been added yet.
                            </p>
                        <?php endif; ?>
                    </section>

                    <section
                        class="card-body p-3 p-lg-4
                            border-bottom">

                        <div
                            class="d-flex
                                align-items-center gap-2 mb-3">

                            <span
                                class="d-inline-flex
                                    align-items-center
                                    justify-content-center
                                    rounded-circle
                                    bg-primary-subtle
                                    text-primary"
                                style="
                                    width: 34px;
                                    height: 34px;
                                ">

                                <i
                                    class="fs-18 ri-id-card-line"
                                    aria-hidden="true"></i>
                            </span>

                            <h2
                                class="fs-16
                                    fw-semibold mb-0">
                                Basic Details
                            </h2>
                        </div>

                        <div class="row g-3">
                            <?php foreach (
                                $personalDetails
                                as $label => $value
                            ): ?>
                                <div class="col-12 col-sm-6">
                                    <div
                                        class="border-bottom
                                            pb-2 h-100">

                                        <div
                                            class="text-muted
                                                fs-12 mb-1">
                                            <?= esc($label) ?>
                                        </div>

                                        <div
                                            class="fw-medium fs-14">
                                            <?= esc(
                                                $displayValue(
                                                    $value
                                                )
                                            ) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <section
                        class="card-body p-3 p-lg-4
                            border-bottom">

                        <div
                            class="d-flex
                                align-items-center gap-2 mb-3">

                            <span
                                class="d-inline-flex
                                    align-items-center
                                    justify-content-center
                                    rounded-circle
                                    bg-primary-subtle
                                    text-primary"
                                style="
                                    width: 34px;
                                    height: 34px;
                                ">

                                <i
                                    class=" fs-18
                                        ri-briefcase-line"
                                    aria-hidden="true"></i>
                            </span>

                            <h2
                                class="fs-16
                                    fw-semibold mb-0">
                                Education & Profession
                            </h2>
                        </div>

                        <div class="row g-3">
                            <?php foreach (
                                $professionDetails
                                as $label => $value
                            ): ?>
                                <div class="col-12 col-sm-6">
                                    <div
                                        class="border-bottom
                                            pb-2 h-100">

                                        <div
                                            class="text-muted
                                                fs-12 mb-1">
                                            <?= esc($label) ?>
                                        </div>

                                        <div
                                            class="fw-medium fs-14">
                                            <?= esc(
                                                $displayValue(
                                                    $value
                                                )
                                            ) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>



                </div>
            </div>

            <!-- One single card for every section in the right column. -->
            <div class="col-12 col-lg-5">
                <div
                    class="card border border-danger border-opacity-25 shadow-sm
                        rounded-3 overflow-hidden">

                    <section
                        class="card-body p-3 p-lg-4
                            border-bottom">

                        <div
                            class="d-flex
                                align-items-center gap-2 mb-3">

                            <span
                                class="d-inline-flex
                                    align-items-center
                                    justify-content-center
                                    rounded-circle
                                    bg-warning-subtle
                                    text-warning"
                                style="
                                    width: 34px;
                                    height: 34px;
                                ">

                                <i
                                    class="fs-18 ri-group-line"
                                    aria-hidden="true"></i>
                            </span>

                            <h2
                                class="fs-16
                                    fw-semibold mb-0">
                                Family Details
                            </h2>
                        </div>

                        <?php foreach (
                            $familyDetailList
                            as $label => $value
                        ): ?>
                            <div
                                class="d-flex
                                    justify-content-between
                                    align-items-start
                                    gap-3 py-2
                                    border-bottom">

                                <span
                                    class="text-muted
                                        fs-13">
                                    <?= esc($label) ?>
                                </span>

                                <span
                                    class="fw-medium fs-13
                                        text-end">
                                    <?= esc(
                                        $displayValue($value)
                                    ) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </section>

                    <section
                        class="card-body p-3 p-lg-4
                            border-bottom">

                        <div
                            class="d-flex
                                align-items-center gap-2 mb-3">

                            <span
                                class="d-inline-flex
                                    align-items-center
                                    justify-content-center
                                    rounded-circle
                                    bg-primary-subtle
                                    text-primary"
                                style="
                                    width: 34px;
                                    height: 34px;
                                ">

                                <i
                                    class="fs-18 
                                        ri-heart-pulse-line"
                                    aria-hidden="true"></i>
                            </span>

                            <h2
                                class="fs-16
                                    fw-semibold mb-0">
                                Lifestyle
                            </h2>
                        </div>

                        <?php if (
                            $lifestyleDetails !== []
                        ): ?>
                            <div
                                class="d-flex
                                    flex-wrap gap-2">

                                <?php foreach (
                                    $lifestyleDetails
                                    as $detail
                                ): ?>
                                    <?php
                                    if (!is_array($detail)) {
                                        continue;
                                    }

                                    $label = trim(
                                        (string) (
                                            $detail['option_name']
                                            ?? $detail['name']
                                            ?? ''
                                        )
                                    );

                                    if ($label === '') {
                                        continue;
                                    }
                                    ?>

                                    <span
                                        class="
                                            badge rounded-pill
                                            bg-primary-subtle
                                            text-black
                                            fw-medium p-2">
                                        <?= esc($label) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-0">
                                Lifestyle preferences have not
                                been added.
                            </p>
                        <?php endif; ?>
                    </section>

                    <section class="card-body p-3 p-lg-4">
                        <div
                            class="d-flex
                                align-items-center gap-2 mb-3">

                            <span
                                class="d-inline-flex
                                    align-items-center
                                    justify-content-center
                                    rounded-circle
                                    bg-primary-subtle
                                    text-primary"
                                style="
                                    width: 34px;
                                    height: 34px;
                                ">

                                <i
                                    class="fs-18 ri-lock-2-line"
                                    aria-hidden="true"></i>
                            </span>

                            <h2
                                class="fs-16
                                    fw-semibold mb-0">
                                Privacy
                            </h2>
                        </div>

                        <p class="text-muted fs-13 mb-0">
                            This profile information is visible
                            only to authenticated members according
                            to the applicable privacy rules.
                        </p>
                    </section>

                </div>
            </div>

        </div>
    </div>
</section><?php if ($galleryPhotos !== []): ?>

    <div
        class="modal fade"
        id="profilePhotoModal"
        tabindex="-1"
        aria-labelledby="profilePhotoModalLabel"
        aria-hidden="true">

        <div
            class="modal-dialog
                modal-dialog-centered
                modal-xl">

            <div class="modal-content">

                <div class="modal-header">

                    <h2
                        class="modal-title fs-17"
                        id="profilePhotoModalLabel">

                        Profile Photo
                    </h2>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close">
                    </button>

                </div>

                <div class="modal-body p-2 p-md-3">

                    <div
                        class="d-flex align-items-center
                            justify-content-center
                            bg-light rounded-3"
                        style="min-height: 320px;">

                        <img
                            src=""
                            alt="Enlarged member profile photo"
                            class="img-fluid rounded-3
                                object-fit-contain"
                            style="max-height: 80vh;"
                            data-profile-modal-image>

                    </div>

                </div>

            </div>

        </div>

    </div>

<?php endif; ?>
<?php if ($galleryPhotos !== []): ?>

    <script>
        document.addEventListener(
            'DOMContentLoaded',
            function() {
                'use strict';

                const modalElement = document.getElementById(
                    'profilePhotoModal'
                );

                if (!modalElement) {
                    return;
                }

                const modalImage = modalElement.querySelector(
                    '[data-profile-modal-image]'
                );

                if (!modalImage) {
                    return;
                }

                modalElement.addEventListener(
                    'show.bs.modal',
                    function(event) {
                        const trigger = event.relatedTarget;

                        if (!(trigger instanceof HTMLElement)) {
                            return;
                        }

                        const photoIndex = trigger.dataset
                            .profilePhotoIndex;

                        if (typeof photoIndex !== 'string') {
                            return;
                        }

                        const urlField = document.querySelector(
                            '[data-profile-modal-url="' +
                            CSS.escape(photoIndex) +
                            '"]'
                        );

                        if (!(urlField instanceof HTMLInputElement)) {
                            return;
                        }

                        const modalUrl = urlField.value.trim();

                        if (modalUrl === '') {
                            return;
                        }

                        /*
                         * The original URL is assigned only after the member
                         * explicitly opens the modal. Gallery images continue
                         * to use the medium variant.
                         */
                        modalImage.src = modalUrl;
                    }
                );

                modalElement.addEventListener(
                    'hidden.bs.modal',
                    function() {
                        /*
                         * Remove the original signed URL from the active image
                         * element after closing the modal.
                         */
                        modalImage.removeAttribute('src');
                    }
                );
            }
        );
    </script>

<?php endif; ?>
<?php $this->endSection(); ?>