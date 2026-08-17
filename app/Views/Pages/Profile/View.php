<?php

declare(strict_types=1);

use App\Support\BooleanValue;

/**
 * The same profile view is used for member, other-member
 * and administrator profile presentations.
 *
 * Member mode:
 * - uses the normal member layout;
 * - shows profile editing actions;
 * - gallery modal uses approved medium photographs only.
 *
 * Other-member mode:
 * - uses the normal member layout;
 * - enforces member-to-member authorization;
 * - gallery modal uses viewer-authorized medium photographs only.
 *
 * Admin mode:
 * - uses the administrator layout;
 * - hides member editing actions;
 * - administrator media authorization remains separate.
 */
$profileViewMode = mb_strtolower(
    trim(
        (string) (
            $profileViewMode
            ?? 'member'
        )
    )
);

$isOtherMemberProfileView =
    $profileViewMode === 'other-member';

$isAdminProfileView =
    $profileViewMode === 'admin';

$isFieldOfficerProfileView =
    $profileViewMode
    === 'field-officer';

/*
 * Public reference of the member being viewed.
 *
 * This exists only in other-member mode.
 * Own-profile and administrator previews do not supply it.
 */
$viewedProfileReference = trim(
    (string) (
        $viewedProfileReference
        ?? ''
    )
);

$viewedMobile = trim(
    (string) (
        $viewedMobile
        ?? ''
    )
);

$viewedMobileLabel = trim(
    (string) (
        $viewedMobileLabel
        ?? 'Mobile Number'
    )
);

if ($viewedMobileLabel === '') {
    $viewedMobileLabel =
        'Mobile Number';
}

$viewedMaskedMemberMobile = trim(
    (string) (
        $viewedMaskedMemberMobile
        ?? ''
    )
);

$isViewedMaskedMobileVerified =
    ($isViewedMaskedMobileVerified ?? false)
    === true;

$isViewedParentMobile =
    ($isViewedParentMobile ?? false)
    === true;

$viewedEmail = trim(
    (string) (
        $viewedEmail
        ?? ''
    )
);

$isViewedMobileVerified =
    ($isViewedMobileVerified ?? false)
    === true;

$isShortlisted =
    ($isShortlisted ?? false)
    === true;

/*
 * Member-to-member Interest relationship is prepared entirely
 * by MemberInteractionService.
 *
 * The View only selects presentation from this state.
 */
$interestRelationship =
    isset($interestRelationship)
    && is_array(
        $interestRelationship
    )
    ? $interestRelationship
    : [];

$interestState =
    strtoupper(
        trim(
            (string) (
                $interestRelationship['state']
                ?? 'NONE'
            )
        )
    );

$canShowInterest =
    (
        $interestRelationship['canShowInterest']
        ?? false
    ) === true;

$canRespondToInterest =
    (
        $interestRelationship['canRespond']
        ?? false
    ) === true;

$partnerPreferenceMatch =
    isset($partnerPreferenceMatch)
    && is_array(
        $partnerPreferenceMatch
    )
    ? $partnerPreferenceMatch
    : [];

$preferenceMatchPercentage = max(
    0,
    min(
        100,
        (int) (
            $partnerPreferenceMatch['percentage']
            ?? 0
        )
    )
);

$matchedPreferenceCount = max(
    0,
    (int) (
        $partnerPreferenceMatch['matched']
        ?? 0
    )
);

$totalPreferenceCount = max(
    0,
    (int) (
        $partnerPreferenceMatch['total']
        ?? 0
    )
);

$unmatchedPreferenceCount = max(
    0,
    (int) (
        $partnerPreferenceMatch['unmatched']
        ?? 0
    )
);

$profileLayout = match (true) {
    $isAdminProfileView =>
    'Admin/Layouts/Main',

    $isFieldOfficerProfileView =>
    'FieldOfficer/Layouts/Main',

    default =>
    'Layouts/Main',
};

$showMemberActions =
    !$isAdminProfileView
    && !$isOtherMemberProfileView
    && !$isFieldOfficerProfileView;

$adminMemberId = max(
    0,
    (int) (
        $adminMemberId
        ?? 0
    )
);

$fieldOfficerViewedMemberId = max(
    0,
    (int) (
        $fieldOfficerViewedMemberId
        ?? 0
    )
);

$profileBackUrl = trim(
    (string) (
        $profileBackUrl
        ?? (
            $isAdminProfileView
            ? route_to(
                'admin.members.index'
            )
            : url_to(
                'web.profile.edit'
            )
        )
    )
);

$profileBackLabel = trim(
    (string) (
        $profileBackLabel
        ?? (
            $isAdminProfileView
            ? 'Back to Members'
            : 'Back to profile'
        )
    )
);

$profileNoticeTitle = trim(
    (string) (
        $profileNoticeTitle
        ?? (
            $isAdminProfileView
            ? 'Administrator member preview'
            : 'This is your profile preview'
        )
    )
);

$profileNoticeMessage = trim(
    (string) (
        $profileNoticeMessage
        ?? (
            $isAdminProfileView
            ? 'This screen displays the member profile using '
            . 'the same information shown in the member-facing preview.'
            : 'Only approved photos and saved profile '
            . 'details are displayed below.'
        )
    )
);

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
 *
 * @var array{
 *     aadhaar_name:string,
 *     aadhaar_date_of_birth:string
 * }|null $aadhaarVerification
 *
 * @var list<array{
 *     id:int,
 *     thumbnailUrl:string,
 *     isPrimary:bool
 * }> $approvedPhotos
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

$aadhaarVerification =
    isset($aadhaarVerification)
    && is_array($aadhaarVerification)
    ? $aadhaarVerification
    : [];

$aadhaarVerifiedName = preg_replace(
    '/\s+/u',
    ' ',
    trim(
        (string) (
            $aadhaarVerification['aadhaar_name']
            ?? ''
        )
    )
) ?? '';

$aadhaarDateOfBirth = trim(
    (string) (
        $aadhaarVerification['aadhaar_date_of_birth']
        ?? ''
    )
);

$formattedAadhaarDateOfBirth = '';

if ($aadhaarDateOfBirth !== '') {
    try {
        $parsedAadhaarDate =
            DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $aadhaarDateOfBirth
            );

        $parseErrors =
            DateTimeImmutable::getLastErrors();

        $hasDateErrors =
            is_array($parseErrors)
            && (
                (
                    $parseErrors['warning_count']
                    ?? 0
                ) > 0
                || (
                    $parseErrors['error_count']
                    ?? 0
                ) > 0
            );

        if (
            $parsedAadhaarDate
            instanceof DateTimeImmutable
            && !$hasDateErrors
            && $parsedAadhaarDate->format(
                'Y-m-d'
            ) === $aadhaarDateOfBirth
        ) {
            $formattedAadhaarDateOfBirth =
                $parsedAadhaarDate->format(
                    'd M Y'
                );
        }
    } catch (Throwable) {
        $formattedAadhaarDateOfBirth = '';
    }
}

$hasApprovedAadhaarIdentity =
    $aadhaarVerifiedName !== ''
    && $formattedAadhaarDateOfBirth !== '';

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

/*
 * The controller/service has already resolved whether an
 * actual profile image can be displayed for this viewing mode.
 *
 * If not, apply the common gender-based presentation fallback.
 */
if ($profileImage === '') {
    helper(
        'member_profile'
    );

    $profileImage =
        member_profile_placeholder(
            $user['gender']
                ?? null
        );
}

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
 * Prepare the thumbnail-only profile gallery.
 *
 * Medium signed URLs are intentionally not included in the
 * initial page response.
 *
 * They are generated lazily after the member opens a slide.
 *
 * Original member photographs are never exposed through this
 * member-facing gallery.
 */
$galleryPhotos = [];

foreach ($approvedPhotos as $photo) {
    if (!is_array($photo)) {
        continue;
    }

    $photoId = (int) (
        $photo['id']
        ?? 0
    );

    $thumbnailUrl = trim(
        (string) (
            $photo['thumbnailUrl']
            ?? ''
        )
    );

    if (
        $photoId <= 0
        || $thumbnailUrl === ''
    ) {
        continue;
    }

    $galleryPhotos[] = [
        'id' => $photoId,

        'thumbnailUrl' =>
        $thumbnailUrl,

        'isPrimary' => (
            $photo['isPrimary']
            ?? false
        ) === true,

        /*
         * This is an authenticated application URL, not a signed S3 or
         * CloudFront original-photo URL.
         */
        'modalUrlEndpoint' =>
        $isFieldOfficerProfileView
            ? url_to(
                'field-officer.profiles.photos.medium-url',
                $fieldOfficerViewedMemberId,
                $photoId
            )
            : (
                $isOtherMemberProfileView
                ? url_to(
                    'web.members.photos.medium-url',
                    $viewedProfileReference,
                    $photoId
                )
                : url_to(
                    'web.profile.photos.medium-url',
                    $photoId
                )
            ),
    ];
}

$this->extend(
    $profileLayout
);
$this->section('content');
?>

<section class="py-3 py-lg-4">
    <div class="container">

        <div class="mb-2">
            <a
                href="<?= esc(
                            $profileBackUrl,
                            'attr'
                        ) ?>"
                class="d-inline-flex
            align-items-center
            gap-1 text-primary
            fw-medium mb-2">

                <i
                    class="ri-arrow-left-line"
                    aria-hidden="true">
                </i>

                <?= esc(
                    $profileBackLabel
                ) ?>
            </a>
        </div>
        <?php if (
            !$isOtherMemberProfileView
        ): ?>
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
                        <h2
                            class="fs-15
        fw-semibold mb-1">

                            <?= esc(
                                $profileNoticeTitle
                            ) ?>
                        </h2>

                        <p
                            class="text-muted
        fs-13 mb-0">

                            <?= esc(
                                $profileNoticeMessage
                            ) ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Main profile summary card. -->
        <article
            class="card border border-danger
        border-opacity-25 shadow-sm
        rounded-3 mb-4">

            <div class="card-body p-3 p-lg-4">



                <div
                    class="row g-4
                align-items-center">

                    <!-- =====================================================
                 Profile image
                 ===================================================== -->
                    <div
                        class="col-12
                    col-md-auto
                    text-center">

                        <div
                            class="d-inline-block
                        position-relative
                        overflow-hidden
                        rounded-3
                        bg-light">

                            <img
                                src="<?= esc(
                                            $profileImage,
                                            'attr'
                                        ) ?>"
                                alt="<?= esc(
                                            $fullName
                                                . ' profile photo',
                                            'attr'
                                        ) ?>"
                                class="member-photo-medium"
                                loading="eager">

                        </div>
                    </div>

                    <!-- =====================================================
                 Profile information
                 ===================================================== -->
                    <div
                        class="col-12
        col-md">

                        <div
                            class="h-100
            d-flex
            flex-column
            justify-content-center">

                            <!-- =====================================================
             TOP ROW
             Member summary + member actions
             ===================================================== -->
                            <div class="row g-3 align-items-start">

                                <!-- Member summary -->
                                <div
                                    class="<?= (
                                                $isOtherMemberProfileView
                                                && $viewedProfileReference !== ''
                                            )
                                                ? 'col-12 col-lg-9'
                                                : 'col-12' ?>">

                                    <div
                                        class="pe-lg-3">

                                        <!-- Name -->
                                        <div
                                            class="d-flex
    align-items-center
    justify-content-between
    flex-wrap
    gap-2
    mb-2">

                                            <div
                                                class="d-flex
        align-items-center
        flex-wrap
        gap-2">

                                                <h2
                                                    class="fs-24
            fw-bold
            mb-0">

                                                    <?= esc(
                                                        $fullName
                                                    ) ?>
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
                                                        class="ri-checkbox-circle-fill
                text-success
                fs-18"
                                                        aria-label="Approved profile">
                                                    </i>

                                                <?php endif; ?>

                                            </div>

                                            <?php if ($showMemberActions): ?>

                                                <a
                                                    href="<?= url_to(
                                                                'web.profile.edit'
                                                            ) ?>"
                                                    class="btn
            btn-outline-primary
            d-inline-flex
            align-items-center
            justify-content-center
            gap-1">

                                                    <i
                                                        class="ri-edit-line"
                                                        aria-hidden="true">
                                                    </i>

                                                    Edit My Profile
                                                </a>

                                            <?php endif; ?>

                                        </div>

                                        <!-- Age / Height -->
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

                                                <?= esc(
                                                    $height
                                                ) ?>

                                            <?php endif; ?>

                                        </p>

                                        <!-- Current location -->
                                        <?php if (
                                            $currentLocation !== ''
                                        ): ?>

                                            <p
                                                class="text-muted
                                mb-2">

                                                <i
                                                    class="ri-map-pin-line
                                    me-1"
                                                    aria-hidden="true">
                                                </i>

                                                <?= esc(
                                                    $currentLocation
                                                ) ?>
                                            </p>

                                        <?php endif; ?>

                                        <!-- Community / Gotra -->
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
                                                    aria-hidden="true">
                                                </i>

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

                                        <!-- Profile created by -->
                                        <?php if (
                                            $profileCreatedFor !== ''
                                        ): ?>

                                            <p
                                                class="text-danger
                                fs-14
                                mb-2">

                                                <i
                                                    class="ri-heart-line
                                    me-1
                                    text-muted"
                                                    aria-hidden="true">
                                                </i>

                                                <span
                                                    class="text-muted">

                                                    Profile Created By :
                                                </span>

                                                <?= esc(
                                                    $profileCreatedFor
                                                ) ?>
                                            </p>

                                        <?php endif; ?>

                                        <p
                                            class="text-success
                                fs-14
                                mb-0">

                                            <i
                                                class="ri-pie-chart-line
                                    me-1
                                    text-muted"
                                                aria-hidden="true">
                                            </i>

                                            <span
                                                class="text-muted">

                                                Profile Completion :
                                            </span>
                                            <span class="fw-medium fs-16">
                                                <?= esc(
                                                    (string)
                                                    $completionPercentage
                                                ) ?>%
                                            </span>
                                        </p>

                                    </div>
                                </div>

                                <!-- =================================================
                 ACTIONS - TOP RIGHT
                 ================================================= -->
                                <?php if (
                                    $isOtherMemberProfileView
                                    && $viewedProfileReference !== ''
                                ): ?>

                                    <div
                                        class="col-12
                        col-lg-3">

                                        <div
                                            class="vstack
                            gap-2">

                                            <!-- =================================================
     Interest
     ================================================= -->

                                            <?php if (
                                                $interestState
                                                === 'PENDING_RECEIVED'
                                                && $canRespondToInterest
                                            ): ?>

                                                <!-- =================================================
         Incoming Interest
         ================================================= -->

                                                <div
                                                    class="border
            rounded-3
            bg-warning-subtle
            p-2
            text-center">

                                                    <div
                                                        class="d-flex
                align-items-center
                justify-content-center
                gap-2
                fw-semibold
                text-dark
                mb-1">

                                                        <i
                                                            class="ri-heart-fill
                    text-danger
                    fs-18"
                                                            aria-hidden="true">
                                                        </i>

                                                        Interest Received
                                                    </div>

                                                </div>

                                                <!-- Accept Interest -->
                                                <form
                                                    method="post"
                                                    action="<?= route_to(
                                                                'web.members.interest.accept',
                                                                $viewedProfileReference
                                                            ) ?>"
                                                    data-member-interest-form>

                                                    <?= csrf_field() ?>

                                                    <button
                                                        type="submit"
                                                        class="btn
                btn-success
                w-100
                d-flex
                align-items-center
                justify-content-center
                gap-2">

                                                        <span
                                                            data-member-interest-label>

                                                            <i
                                                                class="ri-thumb-up-fill"
                                                                aria-hidden="true">
                                                            </i>

                                                            Accept Interest
                                                        </span>

                                                        <span
                                                            class="d-none
                    align-items-center"
                                                            data-member-interest-loading>

                                                            <span
                                                                class="spinner-border
                        spinner-border-sm
                        me-1"
                                                                aria-hidden="true">
                                                            </span>

                                                            Saving...
                                                        </span>

                                                    </button>

                                                </form>

                                                <!-- Decline Interest -->
                                                <form
                                                    method="post"
                                                    action="<?= route_to(
                                                                'web.members.interest.decline',
                                                                $viewedProfileReference
                                                            ) ?>"
                                                    data-member-interest-form>

                                                    <?= csrf_field() ?>

                                                    <button
                                                        type="submit"
                                                        class="btn
                btn-outline-danger
                w-100
                d-flex
                align-items-center
                justify-content-center
                gap-2">

                                                        <span
                                                            data-member-interest-label>

                                                            <i
                                                                class="ri-close-line"
                                                                aria-hidden="true">
                                                            </i>

                                                            Decline
                                                        </span>

                                                        <span
                                                            class="d-none
                    align-items-center"
                                                            data-member-interest-loading>

                                                            <span
                                                                class="spinner-border
                        spinner-border-sm
                        me-1"
                                                                aria-hidden="true">
                                                            </span>

                                                            Saving...
                                                        </span>

                                                    </button>

                                                </form>

                                            <?php elseif (
                                                $interestState
                                                === 'PENDING_SENT'
                                            ): ?>

                                                <div
                                                    class="border
            rounded-3
            bg-success-subtle
            text-success
            p-2
            text-center">

                                                    <div
                                                        class="d-flex
                align-items-center
                justify-content-center
                gap-2
                fw-semibold">

                                                        <i
                                                            class="ri-time-line"
                                                            aria-hidden="true">
                                                        </i>

                                                        Interest Sent
                                                    </div>

                                                    <div
                                                        class="fs-12
                text-muted
                mt-1">

                                                        Waiting for a response
                                                    </div>

                                                </div>

                                            <?php elseif (
                                                in_array(
                                                    $interestState,
                                                    [
                                                        'ACCEPTED_SENT',
                                                        'ACCEPTED_RECEIVED',
                                                    ],
                                                    true
                                                )
                                            ): ?>

                                                <div
                                                    class="border
            rounded-3
            bg-success-subtle
            text-success
            p-2
            text-center">

                                                    <div
                                                        class="d-flex
                align-items-center
                justify-content-center
                gap-2
                fw-semibold">

                                                        <i
                                                            class="ri-thumb-up-fill"
                                                            aria-hidden="true">
                                                        </i>

                                                        Interest Accepted
                                                    </div>

                                                </div>

                                            <?php elseif (
                                                in_array(
                                                    $interestState,
                                                    [
                                                        'DECLINED_SENT',
                                                        'DECLINED_RECEIVED',
                                                    ],
                                                    true
                                                )
                                            ): ?>

                                                <div
                                                    class="border
            rounded-3
            bg-light
            text-muted
            p-2
            text-center">

                                                    <div
                                                        class="d-flex
                align-items-center
                justify-content-center
                gap-2
                fw-medium">

                                                        <i
                                                            class="ri-close-circle-line"
                                                            aria-hidden="true">
                                                        </i>

                                                        Interest Declined
                                                    </div>

                                                </div>

                                            <?php elseif (
                                                $canShowInterest
                                            ): ?>

                                                <form
                                                    method="post"
                                                    action="<?= route_to(
                                                                'web.members.interest',
                                                                $viewedProfileReference
                                                            ) ?>"
                                                    data-member-interest-form>

                                                    <?= csrf_field() ?>

                                                    <button
                                                        type="submit"
                                                        class="btn
                btn-primary
                w-100
                d-flex
                align-items-center
                justify-content-center
                gap-2">

                                                        <span
                                                            data-member-interest-label>

                                                            <i
                                                                class="ri-heart-add-line"
                                                                aria-hidden="true">
                                                            </i>

                                                            Show Interest
                                                        </span>

                                                        <span
                                                            class="d-none
                    align-items-center"
                                                            data-member-interest-loading>

                                                            <span
                                                                class="spinner-border
                        spinner-border-sm
                        me-1"
                                                                aria-hidden="true">
                                                            </span>

                                                            Saving...
                                                        </span>

                                                    </button>

                                                </form>

                                            <?php endif; ?>

                                            <!-- ShortList -->
                                            <!-- ShortList -->
                                            <!-- =================================================
     Secondary actions
     ================================================= -->

                                            <div
                                                class="border-top
        pt-3
        mt-1">

                                                <div
                                                    class="d-flex
            gap-2">

                                                    <!-- ShortList -->
                                                    <form
                                                        method="post"
                                                        action="<?= route_to(
                                                                    'web.members.shortlist',
                                                                    $viewedProfileReference
                                                                ) ?>"
                                                        class="flex-fill"
                                                        data-member-shortlist-form>

                                                        <?= csrf_field() ?>

                                                        <button
                                                            type="submit"
                                                            class="btn
                    <?= $isShortlisted
                                        ? 'btn-success'
                                        : 'btn-outline-primary' ?>
                    w-100
                    d-flex
                    align-items-center
                    justify-content-center
                    gap-1">

                                                            <span
                                                                data-member-shortlist-label>

                                                                <i
                                                                    class="<?= $isShortlisted
                                                                                ? 'ri-bookmark-fill'
                                                                                : 'ri-bookmark-line' ?>"
                                                                    aria-hidden="true">
                                                                </i>

                                                                <?= $isShortlisted
                                                                    ? 'Shortlisted'
                                                                    : 'ShortList' ?>

                                                            </span>

                                                            <span
                                                                class="d-none
                        align-items-center"
                                                                data-member-shortlist-loading>

                                                                <span
                                                                    class="spinner-border
                            spinner-border-sm"
                                                                    aria-hidden="true">
                                                                </span>

                                                            </span>

                                                        </button>

                                                    </form>

                                                    <!-- Block -->
                                                    <button
                                                        type="button"
                                                        class="btn
                btn-outline-danger
                flex-fill
                d-flex
                align-items-center
                justify-content-center
                gap-1"
                                                        data-member-block-open>

                                                        <i
                                                            class="ri-forbid-line"
                                                            aria-hidden="true">
                                                        </i>

                                                        Block
                                                    </button>

                                                </div>

                                            </div>

                                        </div>
                                    </div>

                                <?php endif; ?>

                            </div>

                            <!-- =====================================================
     BOTTOM SUMMARY ROW
     ===================================================== -->
                            <div
                                class="row
        g-3
        mt-3
        pt-3
        border-top">

                                <!-- Profile ID -->
                                <div
                                    class="col-12
            col-sm-6
            <?= $isOtherMemberProfileView
                ? 'col-xl-3'
                : 'col-xl-4' ?>">

                                    <div
                                        class="d-flex
                align-items-center
                gap-2">

                                        <span
                                            class="avatar-xs
                        flex-shrink-0">

                                            <span
                                                class="avatar-title
                        rounded-circle
                        bg-primary-subtle
                        text-primary">

                                                <i
                                                    class="ri-fingerprint-line
                            fs-16"
                                                    aria-hidden="true">
                                                </i>
                                            </span>
                                        </span>

                                        <div class="min-w-0">
                                            <div
                                                class="text-muted
                        fs-12">

                                                Profile ID
                                            </div>

                                            <strong
                                                class="fs-14
                        text-break">

                                                <?= esc(
                                                    $displayValue(
                                                        $profileReference
                                                    )
                                                ) ?>
                                            </strong>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($isOtherMemberProfileView): ?>

                                    <!-- Contact number -->
                                    <div
                                        class="col-12
        col-sm-6
        col-xl-3">

                                        <div
                                            class="d-flex
            align-items-start
            gap-2
            h-100">

                                            <span
                                                class="avatar-xs
                        flex-shrink-0">

                                                <span
                                                    class="avatar-title
                    rounded-circle
                    bg-primary-subtle
                    text-primary">

                                                    <i
                                                        class="ri-phone-line
                        fs-16"
                                                        aria-hidden="true">
                                                    </i>
                                                </span>
                                            </span>

                                            <div class="min-w-0 flex-grow-1">

                                                <!-- Primary contact -->
                                                <div
                                                    class="text-muted
                    fs-12">

                                                    <?= $isViewedParentMobile
                                                        ? 'Parents Mobile'
                                                        : 'Mobile Number' ?>
                                                </div>

                                                <div
                                                    class="d-flex
                    align-items-center
                    flex-wrap
                    gap-1">

                                                    <strong
                                                        class="fs-14
                        text-break">

                                                        <?= esc(
                                                            $viewedMobile !== ''
                                                                ? $viewedMobile
                                                                : '-'
                                                        ) ?>
                                                    </strong>

                                                    <?php if (
                                                        !$isViewedParentMobile
                                                        && $viewedMobile !== ''
                                                        && $isViewedMobileVerified
                                                    ): ?>

                                                        <span
                                                            class="badge
                            bg-success-subtle
                            text-success
                            fs-11
                            p-1">

                                                            <i
                                                                class="ri-checkbox-circle-fill"
                                                                aria-hidden="true">
                                                            </i>

                                                            Verified
                                                        </span>

                                                    <?php endif; ?>
                                                </div>

                                                <!-- Female member's masked verified mobile -->
                                                <?php if (
                                                    $isViewedParentMobile
                                                    && $viewedMaskedMemberMobile !== ''
                                                ): ?>

                                                    <div
                                                        class="d-flex
                        align-items-center
                        flex-wrap
                        gap-1
                        mt-1
                        text-muted
                        fs-12">

                                                        <span>
                                                            Member:
                                                        </span>

                                                        <span class="fw-medium text-body">
                                                            <?= esc(
                                                                $viewedMaskedMemberMobile
                                                            ) ?>
                                                        </span>

                                                        <?php if (
                                                            $isViewedMaskedMobileVerified
                                                        ): ?>

                                                            <span
                                                                class="text-success
                                d-inline-flex
                                align-items-center
                                gap-1">

                                                                <i
                                                                    class="ri-checkbox-circle-fill"
                                                                    aria-hidden="true">
                                                                </i>

                                                                Verified
                                                            </span>

                                                        <?php endif; ?>
                                                    </div>

                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Email -->
                                    <div
                                        class="col-12
                col-sm-6
                col-xl-3">

                                        <div
                                            class="d-flex
                    align-items-center
                    gap-2">

                                            <span
                                                class="avatar-xs
                        flex-shrink-0">

                                                <span
                                                    class="avatar-title
                            rounded-circle
                            bg-primary-subtle
                            text-primary">

                                                    <i
                                                        class="ri-mail-line
                                fs-16"
                                                        aria-hidden="true">
                                                    </i>
                                                </span>
                                            </span>

                                            <div class="min-w-0">
                                                <div
                                                    class="text-muted
                            fs-12">

                                                    Email
                                                </div>

                                                <strong
                                                    class="fs-14
                            text-break">

                                                    <?= esc(
                                                        $viewedEmail !== ''
                                                            ? $viewedEmail
                                                            : '-'
                                                    ) ?>
                                                </strong>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Aadhaar verification -->
                                    <div
                                        class="col-12
                col-sm-6
                col-xl-3">

                                        <div
                                            class="d-flex
                    align-items-center
                    gap-2">

                                            <!--
                    Use the existing avatar utility at its smaller size.
                    No new CSS class is required.
                -->
                                            <span
                                                class="avatar-xs
                        flex-shrink-0">

                                                <span
                                                    class="avatar-title
                            rounded-circle
                            <?= $hasApprovedAadhaarIdentity
                                        ? 'bg-success-subtle text-success'
                                        : 'bg-light text-muted' ?>">

                                                    <i
                                                        class="<?= $hasApprovedAadhaarIdentity
                                                                    ? 'ri-shield-check-line'
                                                                    : 'ri-shield-line' ?>
                                fs-16"
                                                        aria-hidden="true">
                                                    </i>
                                                </span>
                                            </span>

                                            <div class="min-w-0">
                                                <div
                                                    class="text-muted
                            fs-12">

                                                    Aadhaar
                                                </div>

                                                <?php if (
                                                    $hasApprovedAadhaarIdentity
                                                ): ?>

                                                    <span
                                                        class="d-inline-flex
                                align-items-center
                                gap-1
                                text-success
                                fw-semibold
                                fs-13">

                                                        <i
                                                            class="ri-checkbox-circle-fill"
                                                            aria-hidden="true">
                                                        </i>

                                                        Verified
                                                    </span>

                                                <?php else: ?>

                                                    <span
                                                        class="text-muted
                                fw-medium
                                fs-13">

                                                        Not added
                                                    </span>

                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                <?php endif; ?>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </article>
        <?php if (
            $hasApprovedAadhaarIdentity
        ): ?>
            <section
                class="card
            border
            border-success
            border-opacity-25
            shadow-sm
            rounded-3
            mb-4"
                aria-labelledby="aadhaarVerifiedDetailsTitle">

                <div
                    class="card-header
                bg-success-subtle
                d-flex
                align-items-center
                justify-content-between
                gap-2">

                    <div
                        class="d-flex
                    align-items-center
                    gap-2">

                        <i
                            class="ri-shield-check-line
                        text-success
                        fs-18"
                            aria-hidden="true"></i>

                        <h2
                            id="aadhaarVerifiedDetailsTitle"
                            class="card-title
                        fs-16
                        fw-semibold
                        mb-0">

                            Aadhaar Verified Details
                        </h2>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row g-3">

                        <div class="col-12 col-md-6">
                            <div
                                class="border-bottom
                            pb-2
                            h-100">

                                <div
                                    class="text-muted
                                fs-12
                                mb-1">

                                    Name on Aadhaar
                                </div>

                                <div
                                    class="fw-medium
                                fs-14
                                d-flex
                                align-items-center
                                gap-1">

                                    <?= esc(
                                        $aadhaarVerifiedName
                                    ) ?>

                                    <i
                                        class="ri-checkbox-circle-fill
                                    text-success"
                                        aria-label="Aadhaar name verified">
                                    </i>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div
                                class="border-bottom
                            pb-2
                            h-100">

                                <div
                                    class="text-muted
                                fs-12
                                mb-1">

                                    Date of Birth on Aadhaar
                                </div>

                                <div
                                    class="fw-medium
                                fs-14
                                d-flex
                                align-items-center
                                gap-1">

                                    <?= esc(
                                        $formattedAadhaarDateOfBirth
                                    ) ?>

                                    <i
                                        class="ri-checkbox-circle-fill
                                    text-success"
                                        aria-label="Aadhaar date of birth verified">
                                    </i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p class="text-muted fs-12 mb-0 mt-3">
                        These details were recorded during Aadhaar verification
                        and cannot be edited from the matrimonial profile.
                    </p>
                </div>
            </section>
        <?php endif; ?>
        <div class="row mb-0">
            <div class="col-12">
                <section
                    class="card border border-danger
        border-opacity-25 shadow-sm
        rounded-3 mb-4">

                    <div class="card-body p-3 p-lg-4">

                        <div
                            class="d-flex align-items-center
                justify-content-between gap-2 mb-3">

                            <div>
                                <h2 class="fs-17 fw-semibold mb-1">
                                    Profile Photos
                                </h2>

                                <p class="text-muted fs-13 mb-0">
                                    Select a photo to view the enlarged gallery.
                                </p>
                            </div>

                            <span class="badge bg-primary p-2 text-white fs-12">
                                <?= esc(
                                    (string) count($galleryPhotos)
                                ) ?>
                            </span>
                        </div>

                        <?php if ($galleryPhotos === []): ?>

                            <div
                                class="border rounded-3
                    text-center p-4 text-muted">

                                <i
                                    class="ri-image-line fs-24"
                                    aria-hidden="true">
                                </i>

                                <p class="fs-13 mb-0 mt-2">
                                    No approved photos are available.
                                </p>
                            </div>

                        <?php else: ?>

                            <div class="row g-2 g-md-3">

                                <?php foreach (
                                    $galleryPhotos as $index => $photo
                                ): ?>

                                    <div
                                        class="col-6 col-md-4 col-lg-3">

                                        <button
                                            type="button"
                                            class="d-block w-100 p-0
                                border rounded-3
                                overflow-hidden bg-light
                                position-relative"
                                            data-profile-gallery-trigger
                                            data-slide-index="<?= esc(
                                                                    (string) $index,
                                                                    'attr'
                                                                ) ?>"
                                            data-bs-toggle="modal"
                                            data-bs-target="#profilePhotoGalleryModal"
                                            aria-label="<?= esc(
                                                            'Open profile photo '
                                                                . ($index + 1),
                                                            'attr'
                                                        ) ?>">

                                            <span
                                                class="ratio ratio-16x9 d-block">

                                                <img
                                                    src="<?= esc(
                                                                $photo['thumbnailUrl'],
                                                                'attr'
                                                            ) ?>"
                                                    alt="<?= esc(
                                                                $fullName
                                                                    . ' profile photo '
                                                                    . ($index + 1),
                                                                'attr'
                                                            ) ?>"
                                                    class="profile-preview-gallery-photo"
                                                    loading="lazy">

                                            </span>

                                            <?php if (
                                                $photo['isPrimary'] === true
                                            ): ?>

                                                <span
                                                    class="badge bg-primary
                                        position-absolute
                                        top-0 start-0 m-2">

                                                    <i
                                                        class="ri-star-fill me-1"
                                                        aria-hidden="true">
                                                    </i>

                                                    Main
                                                </span>

                                            <?php endif; ?>

                                        </button>
                                    </div>

                                <?php endforeach; ?>

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
                <?php if (
                    $isOtherMemberProfileView
                ): ?>

                    <section
                        class="card
                        bg-marketplace
                        bg-primary-subtle
            border
            border-danger
            border-opacity-25
            shadow-sm
            rounded-3
            mb-4">

                        <div
                            class="card-body 
                p-3
                p-lg-4">

                            <!-- Header -->
                            <div
                                class="d-flex
                    flex-column
                    flex-md-row
                    align-items-md-center
                    justify-content-between
                    gap-3
                    mb-4">

                                <div
                                    class="d-flex
                        align-items-center
                        gap-3">

                                    <span
                                        class="avatar-sm
                            flex-shrink-0">

                                        <span
                                            class="avatar-title
                                rounded-circle
                                bg-light
                                text-primary">

                                            <i
                                                class="ri-hearts-line
                                    fs-20"
                                                aria-hidden="true">
                                            </i>

                                        </span>
                                    </span>

                                    <div>

                                        <h2
                                            class="fs-16
                                fw-semibold
                                mb-1">

                                            Partner Preference Match
                                        </h2>

                                        <p
                                            class="text-muted
                                fs-13
                                mb-0">

                                            See how
                                            <?= esc($fullName) ?>
                                            matches your partner preferences.
                                        </p>

                                    </div>
                                </div>

                                <?php if (
                                    $totalPreferenceCount > 0
                                ): ?>

                                    <span
                                        class="badge
                            bg-primary-subtle
                            text-primary
                            fs-14
                            p-2">

                                        <?= esc(
                                            (string)
                                            $preferenceMatchPercentage
                                        ) ?>% Match
                                    </span>

                                <?php endif; ?>

                            </div>

                            <?php if (
                                $totalPreferenceCount > 0
                            ): ?>

                                <!-- Match statement -->
                                <div
                                    class="border
                        rounded-3
                        bg-light
                        p-3
                        p-lg-4
                        mb-3">

                                    <div
                                        class="d-flex
                            flex-column
                            flex-md-row
                            align-items-md-center
                            justify-content-between
                            gap-3
                            mb-3">

                                        <div>

                                            <div
                                                class="fs-16
                                    fw-semibold">

                                                <?= esc($fullName) ?>
                                                matches

                                                <span class="text-primary">
                                                    <?= esc(
                                                        (string)
                                                        $matchedPreferenceCount
                                                    ) ?>/<?= esc(
                                                                (string)
                                                                $totalPreferenceCount
                                                            ) ?>
                                                </span>

                                                of your partner preferences
                                            </div>

                                            <p
                                                class="text-muted
                                    fs-13
                                    mb-0
                                    mt-1">

                                                Based on the structured
                                                Partner Preferences currently set.
                                            </p>

                                        </div>

                                        <div
                                            class="text-md-end">

                                            <div
                                                class="fs-24
                                    fw-bold
                                    text-primary">

                                                <?= esc(
                                                    (string)
                                                    $preferenceMatchPercentage
                                                ) ?>%
                                            </div>

                                            <span
                                                class="text-muted
                                    fs-12">

                                                overall match
                                            </span>

                                        </div>

                                    </div>

                                    <!-- Existing Bootstrap progress -->
                                    <div
                                        class="progress"
                                        role="progressbar"
                                        aria-label="Partner preference match"
                                        aria-valuenow="<?= esc(
                                                            (string)
                                                            $preferenceMatchPercentage,
                                                            'attr'
                                                        ) ?>"
                                        aria-valuemin="0"
                                        aria-valuemax="100">

                                        <div
                                            class="progress-bar"
                                            style="<?= esc(
                                                        'width: '
                                                            . $preferenceMatchPercentage
                                                            . '%;',
                                                        'attr'
                                                    ) ?>">
                                        </div>

                                    </div>

                                </div>

                                <!-- Summary -->
                                <div class="row g-3">

                                    <div
                                        class="col-12
                            col-sm-6">

                                        <div
                                            class="border
                                border border-danger
        border-opacity-25
        rounded-3
                                p-3
                                h-100 bg-dark-subtle">

                                            <div
                                                class="d-flex
                                    align-items-center
                                    gap-3">

                                                <span
                                                    class="avatar-sm
                                        flex-shrink-0">

                                                    <span
                                                        class="avatar-title
                                            rounded-circle
                                            bg-success-subtle
                                            text-success">

                                                        <i
                                                            class="ri-thumb-up-fill
                                                fs-20"
                                                            aria-hidden="true">
                                                        </i>

                                                    </span>
                                                </span>

                                                <div>

                                                    <div
                                                        class="fs-18
                                            fw-semibold
                                            text-success">

                                                        <?= esc(
                                                            (string)
                                                            $matchedPreferenceCount
                                                        ) ?>
                                                    </div>

                                                    <div
                                                        class="text-muted
                                            fs-13">

                                                        Preferences matched
                                                    </div>

                                                </div>

                                            </div>

                                        </div>
                                    </div>

                                    <div
                                        class="col-12
                            col-sm-6">

                                        <div
                                            class="border
                                border border-danger
        border-opacity-25
        rounded-3
                                p-3
                                h-100 bg-dark-subtle">

                                            <div
                                                class="d-flex
                                    align-items-center
                                    gap-3">

                                                <span
                                                    class="avatar-sm
                                        flex-shrink-0">

                                                    <span
                                                        class="avatar-title
                                            rounded-circle
                                            bg-warning-subtle
                                            text-warning">

                                                        <i
                                                            class="ri-information-line
                                                fs-20"
                                                            aria-hidden="true">
                                                        </i>

                                                    </span>
                                                </span>

                                                <div>

                                                    <div
                                                        class="fs-18
                                            fw-semibold">

                                                        <?= esc(
                                                            (string)
                                                            $unmatchedPreferenceCount
                                                        ) ?>
                                                    </div>

                                                    <div
                                                        class="text-muted
                                            fs-13">

                                                        Preferences differ
                                                    </div>

                                                </div>

                                            </div>

                                        </div>
                                    </div>

                                </div>

                            <?php else: ?>

                                <div
                                    class="alert
                        alert-light
                        border
                        mb-0"
                                    role="status">

                                    <div
                                        class="d-flex
                            align-items-start
                            gap-2">

                                        <i
                                            class="ri-information-line
                                fs-18
                                text-muted"
                                            aria-hidden="true">
                                        </i>

                                        <div>

                                            <strong>
                                                Partner Preferences not available
                                            </strong>

                                            <p
                                                class="text-muted
                                    fs-13
                                    mb-0
                                    mt-1">

                                                <?= esc(
                                                    $fullName
                                                ) ?>
                                                has not configured enough
                                                structured Partner Preferences
                                                to calculate a match yet.
                                            </p>

                                        </div>

                                    </div>
                                </div>

                            <?php endif; ?>

                        </div>
                    </section>
                <?php endif; ?>
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
</section>
<?php if ($galleryPhotos !== []): ?>

    <div
        class="modal fade"
        id="profilePhotoGalleryModal"
        tabindex="-1"
        aria-labelledby="profilePhotoGalleryModalLabel"
        aria-hidden="true"
        data-profile-gallery-modal>

        <div
            class="modal-dialog
                modal-dialog-centered
                modal-xl">

            <div class="modal-content">

                <div class="modal-header bg-info-subtle py-2">

                    <div>
                        <h2
                            class="modal-title fs-17"
                            id="profilePhotoGalleryModalLabel">

                            Profile Photos
                        </h2>

                        <p
                            class="text-muted fs-12 mb-0"
                            data-profile-gallery-position>

                            1 of
                            <?= esc(
                                (string) count($galleryPhotos)
                            ) ?>
                        </p>
                    </div>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close">
                    </button>

                </div>

                <div class="modal-body p-0">

                    <div
                        id="profilePhotoGalleryCarousel"
                        class="carousel slide"
                        data-bs-interval="false"
                        data-bs-touch="true"
                        data-profile-gallery-carousel>

                        <div class="carousel-inner">

                            <?php foreach (
                                $galleryPhotos as $index => $photo
                            ): ?>

                                <div
                                    class="carousel-item
                                        <?= $index === 0
                                            ? 'active'
                                            : '' ?>"
                                    data-gallery-slide
                                    data-photo-id="<?= esc(
                                                        (string) $photo['id'],
                                                        'attr'
                                                    ) ?>"
                                    data-modal-url-endpoint="<?= esc(
                                                                    $photo['modalUrlEndpoint'],
                                                                    'attr'
                                                                ) ?>"
                                    data-slide-index="<?= esc(
                                                            (string) $index,
                                                            'attr'
                                                        ) ?>">

                                    <div
                                        class="d-flex align-items-center
                                            justify-content-center
                                            bg-light position-relative
                                            p-3"
                                        style="min-height: 420px;">

                                        <div
                                            class="text-center"
                                            data-slide-loading>

                                            <span
                                                class="spinner-border
                                                    text-primary"
                                                role="status"
                                                aria-hidden="true">
                                            </span>

                                            <p
                                                class="text-muted
                                                    fs-13 mb-0 mt-2">

                                                Loading photo...
                                            </p>
                                        </div>

                                        <div
                                            class="alert alert-danger
                                                d-none m-3"
                                            role="alert"
                                            data-slide-error>
                                        </div>

                                        <img
                                            src=""
                                            alt="<?= esc(
                                                        $fullName
                                                            . ' enlarged profile photo '
                                                            . ($index + 1),
                                                        'attr'
                                                    ) ?>"
                                            class="img-fluid rounded-3
                                                object-fit-contain d-none"
                                            style="max-height: 78vh;"
                                            data-slide-image>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>

                        <?php if (
                            count($galleryPhotos) > 1
                        ): ?>

                            <button
                                class="carousel-control-prev"
                                type="button"
                                data-bs-target="#profilePhotoGalleryCarousel"
                                data-bs-slide="prev"
                                aria-label="Previous photo">

                                <span
                                    class="carousel-control-prev-icon"
                                    aria-hidden="true">
                                </span>

                                <span class="visually-hidden">
                                    Previous
                                </span>
                            </button>

                            <button
                                class="carousel-control-next"
                                type="button"
                                data-bs-target="#profilePhotoGalleryCarousel"
                                data-bs-slide="next"
                                aria-label="Next photo">

                                <span
                                    class="carousel-control-next-icon"
                                    aria-hidden="true">
                                </span>

                                <span class="visually-hidden">
                                    Next
                                </span>
                            </button>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </div>

    </div>

<?php endif; ?>

<?php if (
    $isOtherMemberProfileView
    && $viewedProfileReference !== ''
): ?>

    <?php
    $blockErrors = isset(
        $validationErrors
    )
        && is_array(
            $validationErrors
        )
        ? $validationErrors
        : [];

    $blockCommentError = trim(
        (string) (
            $blockErrors['comment']
            ?? ''
        )
    );
    ?>

    <div
        class="modal fade"
        id="memberBlockModal"
        tabindex="-1"
        aria-labelledby="memberBlockModalTitle"
        aria-hidden="true"
        data-reopen-member-block="<?= (
                                        session(
                                            'reopenMemberBlockModal'
                                        ) === true
                                    )
                                        ? '1'
                                        : '0' ?>">

        <div
            class="modal-dialog
                modal-dialog-centered">

            <div class="modal-content">

                <form
                    method="post"
                    action="<?= route_to(
                                'web.members.block',
                                $viewedProfileReference
                            ) ?>"
                    data-member-block-form>

                    <?= csrf_field() ?>

                    <div
                        class="modal-header bg-info-subtle py-2">

                        <h2
                            class="modal-title
                                fs-18"
                            id="memberBlockModalTitle">

                            Block the Member
                        </h2>

                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close">
                        </button>

                        <button
                            type="button"
                            class="btn btn-outline-warning
        flex-fill
        d-flex
        align-items-center
        justify-content-center
        gap-1"
                            data-member-report-open>

                            <i
                                class="ri-flag-line"
                                aria-hidden="true">
                            </i>

                            Report
                        </button>
                    </div>

                    <div
                        class="modal-body">

                        <p
                            class="text-muted
                                fs-13">

                            This member will no longer
                            appear in your matches,
                            interests, views or searches.
                        </p>

                        <label
                            for="member-block-comment"
                            class="form-label">

                            Comment
                            <span
                                class="text-danger">
                                *
                            </span>
                        </label>

                        <textarea
                            id="member-block-comment"
                            name="comment"
                            class="form-control<?= (
                                                    $blockCommentError !== ''
                                                )
                                                    ? ' is-invalid'
                                                    : '' ?>"
                            rows="4"
                            maxlength="250"
                            required
                            aria-describedby="member-block-comment-error"><?= esc(
                                                                                old('comment')
                                                                            ) ?></textarea>

                        <div
                            id="member-block-comment-error"
                            class="invalid-feedback">

                            <?= esc(
                                $blockCommentError
                                    !== ''
                                    ? $blockCommentError
                                    : 'Please enter a comment.'
                            ) ?>
                        </div>

                        <div
                            class="form-text">

                            Maximum 250 characters.
                        </div>
                    </div>

                    <div
                        class="modal-footer">

                        <button
                            type="button"
                            class="btn btn-light"
                            data-bs-dismiss="modal">

                            Cancel
                        </button>

                        <button
                            type="submit"
                            class="btn btn-danger
                                d-inline-flex
                                align-items-center
                                justify-content-center
                                gap-2"
                            data-member-block-submit>

                            <span
                                data-member-block-label>

                                Block Member
                            </span>

                            <span
                                class="d-none
                                    align-items-center"
                                data-member-block-loading>

                                <span
                                    class="spinner-border
                                        spinner-border-sm
                                        me-1"
                                    aria-hidden="true">
                                </span>

                                Saving...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php if (
        $isOtherMemberProfileView
        && isset($memberActionNotice)
        && is_array(
            $memberActionNotice
        )
    ): ?>

        <span
            class="d-none"
            data-member-action-notice
            data-notice-title="<?= esc(
                                    (string) (
                                        $memberActionNotice['title']
                                        ?? ''
                                    ),
                                    'attr'
                                ) ?>"
            data-notice-message="<?= esc(
                                        (string) (
                                            $memberActionNotice['message']
                                            ?? ''
                                        ),
                                        'attr'
                                    ) ?>">
        </span>

    <?php endif; ?>
<?php endif; ?>
<?php if ($isOtherMemberProfileView): ?>
    <?= view(
        'Pages/Profile/_ReportProfileModal',
        [
            'viewedProfileReference' =>
            $viewedProfileReference,

            'reportCaptcha' =>
            $reportCaptcha ?? '',

            'reportValidationErrors' =>
            $reportValidationErrors ?? [],

            'reopenReportModal' =>
            $reopenReportModal ?? false,
        ]
    ) ?>
<?php endif; ?>
<?php $this->endSection(); ?>