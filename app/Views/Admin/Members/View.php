<?php

declare(strict_types=1);

use App\Models\MemberPhotoModel;
use App\Models\UserModel;
use App\Support\BooleanValue;
use App\Support\DateDisplay;

/**
 * Administrator member profile view.
 *
 * Required profile data:
 *
 * @var int                        $memberId
 * @var array<string, mixed>       $adminMember
 * @var array<string, mixed>       $user
 * @var array<string, mixed>|null  $basicDetails
 * @var array<string, mixed>|null  $educationProfession
 * @var array<string, mixed>|null  $familyDetails
 * @var list<array<string, mixed>> $lifestyleDetails
 * @var string                     $aboutMe
 * @var string                     $profileImage
 * @var array<string, mixed>       $overallProfileSummary
 *
 * Administrator photo data:
 *
 * @var list<array{
 *     id:int,
 *     status:string,
 *     thumbnailUrl:string,
 *     isPrimary:bool,
 *     visibility:string,
 *     rejectionReason:string,
 *     createdAt:string
 * }> $adminPhotos
 *
 * @var array<string, string>|null $formAlert
 */

$resolvedMemberId = max(
    0,
    (int) (
        $memberId
        ?? 0
    )
);

$resolvedAdminMember = isset($adminMember)
    && is_array($adminMember)
    ? $adminMember
    : [];

$resolvedUser = isset($user)
    && is_array($user)
    ? $user
    : [];

$resolvedBasicDetails = isset($basicDetails)
    && is_array($basicDetails)
    ? $basicDetails
    : [];

$resolvedEducationProfession = isset($educationProfession)
    && is_array($educationProfession)
    ? $educationProfession
    : [];

$resolvedFamilyDetails = isset($familyDetails)
    && is_array($familyDetails)
    ? $familyDetails
    : [];

$resolvedLifestyleDetails = isset($lifestyleDetails)
    && is_array($lifestyleDetails)
    ? $lifestyleDetails
    : [];

$resolvedAdminPhotos = isset($adminPhotos)
    && is_array($adminPhotos)
    ? $adminPhotos
    : [];

$resolvedOverallSummary = isset($overallProfileSummary)
    && is_array($overallProfileSummary)
    ? $overallProfileSummary
    : [];

$resolvedAboutMe = trim(
    (string) (
        $aboutMe
        ?? ''
    )
);

$resolvedProfileImage = trim(
    (string) (
        $profileImage
        ?? ''
    )
);

/*
 * Resolve the photograph shown in the Admin member summary.
 *
 * Priority:
 *
 * 1. Primary active member photograph.
 * 2. First approved photograph.
 * 3. First active photograph available to Admin.
 * 4. Name initial fallback when no usable photograph exists.
 *
 * Admin photographs already contain short-lived signed CloudFront
 * thumbnail URLs generated through MemberPhotoUrlService.
 */
$adminProfileImage = '';

foreach ($resolvedAdminPhotos as $photo) {
    if (!is_array($photo)) {
        continue;
    }

    $thumbnailUrl = trim(
        (string) (
            $photo['thumbnailUrl']
            ?? ''
        )
    );

    if ($thumbnailUrl === '') {
        continue;
    }

    if (
        ($photo['isPrimary'] ?? false)
        === true
    ) {
        $adminProfileImage =
            $thumbnailUrl;

        break;
    }
}

/*
 * A legacy/member record may not have a primary photo.
 * Prefer an approved photograph next.
 */
if ($adminProfileImage === '') {
    foreach ($resolvedAdminPhotos as $photo) {
        if (!is_array($photo)) {
            continue;
        }

        $thumbnailUrl = trim(
            (string) (
                $photo['thumbnailUrl']
                ?? ''
            )
        );

        $status = mb_strtoupper(
            trim(
                (string) (
                    $photo['status']
                    ?? ''
                )
            )
        );

        if (
            $thumbnailUrl !== ''
            && $status
            === MemberPhotoModel::STATUS_APPROVED
        ) {
            $adminProfileImage =
                $thumbnailUrl;

            break;
        }
    }
}

/*
 * Admin may review pending/rejected retained photographs as well.
 * When no primary or approved photograph exists, use the first
 * administrator-visible active photograph.
 */
if ($adminProfileImage === '') {
    foreach ($resolvedAdminPhotos as $photo) {
        if (!is_array($photo)) {
            continue;
        }

        $thumbnailUrl = trim(
            (string) (
                $photo['thumbnailUrl']
                ?? ''
            )
        );

        if ($thumbnailUrl !== '') {
            $adminProfileImage =
                $thumbnailUrl;

            break;
        }
    }
}

$resolvedFormAlert = isset($formAlert)
    && is_array($formAlert)
    ? $formAlert
    : null;

/*
 * Prefer the administrator member record for account-level information and
 * fall back to the profile summary user record where necessary.
 */
$fullName = trim(
    (string) (
        $resolvedAdminMember['full_name']
        ?? $resolvedUser['full_name']
        ?? ''
    )
);

if ($fullName === '') {
    $fullName = 'Member';
}

$profileReference = trim(
    (string) (
        $resolvedAdminMember['profile_ref_number']
        ?? $resolvedUser['profile_ref_number']
        ?? ''
    )
);

$accountStatus = mb_strtoupper(
    trim(
        (string) (
            $resolvedAdminMember['account_status']
            ?? $resolvedUser['account_status']
            ?? ''
        )
    )
);

$gender = mb_strtoupper(
    trim(
        (string) (
            $resolvedAdminMember['gender']
            ?? $resolvedUser['gender']
            ?? ''
        )
    )
);

$genderLabel = match ($gender) {
    'M' => 'Male',
    'F' => 'Female',
    default => $gender !== ''
        ? ucfirst(mb_strtolower($gender))
        : '—',
};

$profileCreatedForCode = mb_strtoupper(
    trim(
        (string) (
            $resolvedAdminMember['profile_created_for']
            ?? $resolvedUser['profile_created_for']
            ?? ''
        )
    )
);

$profileCreatedForLabels = [
    'SELF' => 'Self',
    'PARENT' => 'Parents',
    'SON' => 'Parents',
    'DAUGHTER' => 'Parents',
    'BROTHER' => 'Brother',
    'SISTER' => 'Sister',
    'RELATIVE' => 'Relative',
    'FRIEND' => 'Friend',
    'OTHER' => 'Other',
];

$profileCreatedFor = $profileCreatedForLabels[$profileCreatedForCode] ?? (
    $profileCreatedForCode !== ''
    ? ucwords(
        mb_strtolower(
            str_replace(
                '_',
                ' ',
                $profileCreatedForCode
            )
        )
    )
    : '—'
);

$mobileNumber = trim(
    (string) (
        $resolvedAdminMember['mobile_number']
        ?? ''
    )
);

$emailAddress = trim(
    (string) (
        $resolvedAdminMember['email_address']
        ?? ''
    )
);

$isMobileVerified = BooleanValue::fromDatabase(
    $resolvedAdminMember['is_mobile_verified']
        ?? false
);

$isEmailVerified = BooleanValue::fromDatabase(
    $resolvedAdminMember['is_email_verified']
        ?? false
);

$statusBadgeClass = match ($accountStatus) {
    UserModel::STATUS_ACTIVE =>
    'bg-success-subtle text-success',

    UserModel::STATUS_SUSPENDED =>
    'bg-danger-subtle text-danger',

    UserModel::STATUS_PENDING =>
    'bg-warning-subtle text-dark',

    UserModel::STATUS_DELETED =>
    'bg-secondary-subtle text-secondary',

    default =>
    'bg-secondary-subtle text-secondary',
};

$canBlock =
    $accountStatus
    === UserModel::STATUS_ACTIVE;

$canUnblock =
    $accountStatus
    === UserModel::STATUS_SUSPENDED;

/*
 * Helper used for optional profile values.
 */
$displayValue = static function (
    mixed $value
): string {
    if ($value === null) {
        return '—';
    }

    $normalizedValue = trim(
        (string) $value
    );

    return $normalizedValue !== ''
        ? $normalizedValue
        : '—';
};

/*
 * Date of birth is a calendar date. It must never be converted from UTC.
 */
$dateOfBirth = trim(
    (string) (
        $resolvedBasicDetails['date_of_birth']
        ?? ''
    )
);

$displayDateOfBirth =
    DateDisplay::formatDateOrEmpty(
        $dateOfBirth
    );

/*
 * created_at is a UTC timestamp. Convert it into the configured display
 * timezone before showing it to the administrator.
 */
$displayAccountCreated =
    DateDisplay::formatUtcDateTime(
        $resolvedAdminMember['created_at']
            ?? null
    );

$accountCreatedIso =
    DateDisplay::utcToDisplayIso(
        $resolvedAdminMember['created_at']
            ?? null
    );

$age = null;

if ($dateOfBirth !== '') {
    try {
        $birthDate = DateTimeImmutable
            ::createFromFormat(
                '!Y-m-d',
                mb_substr(
                    $dateOfBirth,
                    0,
                    10
                )
            );

        $displayDateConfig = config(
            \Config\DateDisplay::class
        );

        $today = new DateTimeImmutable(
            'today',
            new DateTimeZone(
                $displayDateConfig
                    ->timezone
            )
        );

        if (
            $birthDate
            instanceof DateTimeImmutable
            && $birthDate <= $today
        ) {
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
        $resolvedBasicDetails['height_display_name']
        ?? $resolvedBasicDetails['height_name']
        ?? ''
    )
);

$currentLocation = implode(
    ', ',
    array_filter([
        trim(
            (string) (
                $resolvedBasicDetails['city_name']
                ?? ''
            )
        ),
        trim(
            (string) (
                $resolvedBasicDetails['state_name']
                ?? ''
            )
        ),
        trim(
            (string) (
                $resolvedBasicDetails['country_name']
                ?? ''
            )
        ),
    ])
);

$familyLocation = implode(
    ', ',
    array_filter([
        trim(
            (string) (
                $resolvedFamilyDetails['city_name']
                ?? ''
            )
        ),
        trim(
            (string) (
                $resolvedFamilyDetails['state_name']
                ?? ''
            )
        ),
        trim(
            (string) (
                $resolvedFamilyDetails['country_name']
                ?? ''
            )
        ),
    ])
);

$community = trim(
    (string) (
        $resolvedFamilyDetails['community_name']
        ?? ''
    )
);

$gotra = trim(
    (string) (
        $resolvedFamilyDetails['gotra']
        ?? ''
    )
);

$employmentLabel = trim(
    (string) (
        $resolvedEducationProfession['employed_in_name']
        ?? $resolvedEducationProfession['employment_type_name']
        ?? $resolvedEducationProfession['employed_in']
        ?? ''
    )
);

/*
 * Display lists intentionally mirror the information available on the member
 * preview but remain owned by the Admin view.
 */
$personalDetails = [
    'Profile Created For' =>
    $profileCreatedFor,

    'Gender' =>
    $genderLabel,

    'Date of Birth' =>
    $displayDateOfBirth,

    'Age' =>
    $age !== null
        ? $age . ' Years'
        : '',

    'Height' =>
    $height,

    'Marital Status' =>
    $resolvedBasicDetails['marital_status_name']
        ?? '',

    'Mother Tongue' =>
    $resolvedBasicDetails['mother_tongue_name']
        ?? '',

    'Number of Children' =>
    $resolvedBasicDetails['number_of_children']
        ?? '',

    'Children Living Together' =>
    $resolvedBasicDetails['children_living_together_label']
        ?? $resolvedBasicDetails['children_living_together']
        ?? '',

    'Drinking Habit' =>
    $resolvedBasicDetails['drinking_habit_name']
        ?? '',

    'Eating Habit' =>
    $resolvedBasicDetails['eating_habit_name']
        ?? '',

    'Physical Status' =>
    $resolvedBasicDetails['physical_status_name']
        ?? '',

    'Country' =>
    $resolvedBasicDetails['country_name']
        ?? '',

    'State' =>
    $resolvedBasicDetails['state_name']
        ?? '',

    'City' =>
    $resolvedBasicDetails['city_name']
        ?? '',
];

$professionDetails = [
    'Highest Education' =>
    $resolvedEducationProfession['highest_education_name']
        ?? '',

    'Education in Detail' =>
    $resolvedEducationProfession['education_detail']
        ?? '',

    'College / Institution' =>
    $resolvedEducationProfession['college_institution']
        ?? '',

    'Employed In' =>
    $employmentLabel,

    'Occupation' =>
    $resolvedEducationProfession['occupation_name']
        ?? '',

    'Occupation in Detail' =>
    $resolvedEducationProfession['occupation_detail']
        ?? '',

    'Organization' =>
    $resolvedEducationProfession['organization']
        ?? '',

    'Annual Income' =>
    $resolvedEducationProfession['annual_income_display_name']
        ?? '',
];

$familyDetailList = [
    "Father's Name" =>
    $resolvedFamilyDetails['father_name']
        ?? '',

    "Father's Occupation" =>
    $resolvedFamilyDetails['father_occupation_name']
        ?? '',

    "Mother's Name" =>
    $resolvedFamilyDetails['mother_name']
        ?? '',

    "Mother's Occupation" =>
    $resolvedFamilyDetails['mother_occupation_name']
        ?? '',

    'Number of Brothers' =>
    $resolvedFamilyDetails['brothers_count']
        ?? '',

    'Number of Sisters' =>
    $resolvedFamilyDetails['sisters_count']
        ?? '',

    'Family Type' =>
    $resolvedFamilyDetails['family_type_name']
        ?? '',

    'Family Status' =>
    $resolvedFamilyDetails['family_status_name']
        ?? '',

    'Family Values' =>
    $resolvedFamilyDetails['family_value_name']
        ?? '',

    'Community' =>
    $community,

    'Gotra' =>
    $gotra,

    'Family Location' =>
    $familyLocation,

    'Nearest Gurudwara' =>
    $resolvedFamilyDetails['nearest_gurudwara']
        ?? '',

    'Reference Person 1' =>
    $resolvedFamilyDetails['reference_person_1']
        ?? '',

    'Reference Person 2' =>
    $resolvedFamilyDetails['reference_person_2']
        ?? '',
];

/*
 * Group active photographs by moderation status.
 *
 * Unknown statuses are not displayed because the database status list should
 * remain explicit. DELETED photos are excluded by the service/model query.
 */
$approvedPhotos = [];
$pendingPhotos = [];
$rejectedPhotos = [];

foreach ($resolvedAdminPhotos as $photo) {
    if (!is_array($photo)) {
        continue;
    }

    $photoId = max(
        0,
        (int) (
            $photo['id']
            ?? 0
        )
    );

    $photoStatus = mb_strtoupper(
        trim(
            (string) (
                $photo['status']
                ?? ''
            )
        )
    );

    $thumbnailUrl = trim(
        (string) (
            $photo['thumbnailUrl']
            ?? ''
        )
    );

    /*
     * Do not show an unusable placeholder for a DB row whose S3 thumbnail
     * no longer exists or could not be signed.
     */
    if (
        $photoId <= 0
        || $thumbnailUrl === ''
    ) {
        continue;
    }

    $resolvedPhoto = [
        'id' =>
        $photoId,

        'status' =>
        $photoStatus,

        'thumbnailUrl' =>
        $thumbnailUrl,

        'isPrimary' => ($photo['isPrimary'] ?? false)
            === true,

        'visibility' =>
        trim(
            (string) (
                $photo['visibility']
                ?? ''
            )
        ),

        'rejectionReason' =>
        trim(
            (string) (
                $photo['rejectionReason']
                ?? ''
            )
        ),

        'createdAt' =>
        trim(
            (string) (
                $photo['createdAt']
                ?? ''
            )
        ),

        'modalUrlEndpoint' =>
        route_to(
            'admin.members.photos.modal-urls',
            $resolvedMemberId,
            $photoId
        ),
    ];

    if (
        $photoStatus
        === MemberPhotoModel::STATUS_APPROVED
    ) {
        $approvedPhotos[] =
            $resolvedPhoto;

        continue;
    }

    if (
        $photoStatus
        === MemberPhotoModel::STATUS_PENDING
    ) {
        $pendingPhotos[] =
            $resolvedPhoto;

        continue;
    }

    if (
        $photoStatus
        === MemberPhotoModel::STATUS_REJECTED
    ) {
        $rejectedPhotos[] =
            $resolvedPhoto;
    }
}

$photoGroups = [
    [
        'title' =>
        'Approved Photos',

        'description' =>
        'Photos currently approved for member-facing display.',

        'icon' =>
        'ri-checkbox-circle-line',

        'badgeClass' =>
        'bg-success-subtle text-success',

        'emptyMessage' =>
        'No approved photographs are available.',

        'photos' =>
        $approvedPhotos,
    ],
    [
        'title' =>
        'Pending Approval',

        'description' =>
        'Uploaded photographs waiting for administrator review.',

        'icon' =>
        'ri-time-line',

        'badgeClass' =>
        'bg-warning-subtle text-dark',

        'emptyMessage' =>
        'No photographs are pending approval.',

        'photos' =>
        $pendingPhotos,
    ],
    [
        'title' =>
        'Rejected Photos',

        'description' =>
        'Rejected photographs retained in the database and private S3 storage.',

        'icon' =>
        'ri-close-circle-line',

        'badgeClass' =>
        'bg-danger-subtle text-danger',

        'emptyMessage' =>
        'No retained rejected photographs are available.',

        'photos' =>
        $rejectedPhotos,
    ],
];

$this->extend(
    'Admin/Layouts/Main'
);

$this->section('content');
?>

<div class="container-fluid">

    <!-- Page heading -->
    <div class="row">
        <div class="col-12">
            <div
                class="page-title-box
                    d-sm-flex
                    align-items-center
                    justify-content-between">

                <div>
                    <h4 class="mb-sm-0">


                        Member Profile
                    </h4>

                    <p class="text-muted mb-0 mt-1">
                        Review member details, photographs and account status.
                    </p>
                </div>

                <div class="page-title-right mt-3 mt-sm-0">
                    <a
                        href="<?= route_to(
                                    'admin.members.index'
                                ) ?>"
                        class="btn
                            btn-light
                            d-inline-flex
                            align-items-center
                            gap-1">

                        <i
                            class="ri-arrow-left-line"
                            aria-hidden="true"></i>

                        Back to Members
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?= view(
        'Components/Alerts/FormAlert',
        [
            'alert' =>
            $resolvedFormAlert,
        ]
    ) ?>

    <!-- Member account summary -->
    <div
        class="card
            border
            border-danger
            border-opacity-25
            mb-4">

        <div class="card-body p-3 p-lg-4">
            <div class="row g-4 align-items-center">

                <div class="col-12 col-md-auto">

                    <?php if ($adminProfileImage !== ''): ?>

                        <div class="admin-member-profile-photo">
                            <img
                                src="<?= esc(
                                            $adminProfileImage,
                                            'attr'
                                        ) ?>"
                                alt="<?= esc(
                                            $fullName
                                                . ' profile photo',
                                            'attr'
                                        ) ?>">
                        </div>

                    <?php else: ?>

                        <div
                            class="admin-member-profile-photo
                admin-member-profile-photo--fallback"
                            aria-label="<?= esc(
                                            $fullName,
                                            'attr'
                                        ) ?>">

                            <span>
                                <?= esc(
                                    mb_strtoupper(
                                        mb_substr(
                                            $fullName,
                                            0,
                                            1
                                        )
                                    )
                                ) ?>
                            </span>

                        </div>

                    <?php endif; ?>

                </div>

                <div class="col-12 col-md">
                    <div
                        class="d-flex
                            flex-column
                            flex-lg-row
                            align-items-lg-center
                            justify-content-between
                            gap-3">

                        <div>
                            <div
                                class="d-flex
                                    align-items-center
                                    flex-wrap
                                    gap-2
                                    mb-2">

                                <h2 class="fs-22 fw-semibold mb-0">
                                    <?= esc($fullName) ?>
                                </h2>

                                <span
                                    class="badge
                                        <?= esc(
                                            $statusBadgeClass,
                                            'attr'
                                        ) ?>">

                                    <?= esc(
                                        $accountStatus !== ''
                                            ? $accountStatus
                                            : 'UNKNOWN'
                                    ) ?>
                                </span>
                            </div>

                            <div
                                class="d-flex
                                    flex-wrap
                                    align-items-center
                                    gap-2
                                    text-muted">

                                <span
                                    class="badge
                                        bg-primary-subtle
                                        text-primary p-2">

                                    <?= esc(
                                        $profileReference !== ''
                                            ? $profileReference
                                            : 'No Reference'
                                    ) ?>
                                </span>

                                <span>
                                    <?= esc($genderLabel) ?>
                                </span>

                                <span aria-hidden="true">
                                    •
                                </span>

                                <span>
                                    Created for
                                    <?= esc($profileCreatedFor) ?>
                                </span>

                                <?php if (
                                    $age !== null
                                ): ?>
                                    <span aria-hidden="true">
                                        •
                                    </span>

                                    <span>
                                        <?= esc(
                                            (string) $age
                                        ) ?>
                                        Years
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div
                            class="d-flex
        flex-wrap
        align-items-center
        gap-2">

                            <button
                                type="button"
                                class="btn
            btn-soft-info
            d-inline-flex
            align-items-center
            gap-1"
                                data-member-history
                                data-history-url="<?= esc(
                                                        route_to(
                                                            'admin.members.history',
                                                            $resolvedMemberId
                                                        ),
                                                        'attr'
                                                    ) ?>">

                                <i
                                    class="ri-history-line"
                                    aria-hidden="true"></i>

                                History
                            </button>

                            <?php if ($canBlock): ?>
                                <button
                                    type="button"
                                    class="btn
                btn-danger
                d-inline-flex
                align-items-center
                gap-1"
                                    data-member-status
                                    data-action="BLOCK"
                                    data-member-name="<?= esc(
                                                            $fullName,
                                                            'attr'
                                                        ) ?>"
                                    data-member-code="<?= esc(
                                                            $profileReference,
                                                            'attr'
                                                        ) ?>"
                                    data-form-action="<?= esc(
                                                            route_to(
                                                                'admin.members.block',
                                                                $resolvedMemberId
                                                            ),
                                                            'attr'
                                                        ) ?>">

                                    <i
                                        class="ri-forbid-line"
                                        aria-hidden="true"></i>

                                    Block Member
                                </button>
                            <?php elseif ($canUnblock): ?>
                                <button
                                    type="button"
                                    class="btn
                btn-success
                d-inline-flex
                align-items-center
                gap-1"
                                    data-member-status
                                    data-action="UNBLOCK"
                                    data-member-name="<?= esc(
                                                            $fullName,
                                                            'attr'
                                                        ) ?>"
                                    data-member-code="<?= esc(
                                                            $profileReference,
                                                            'attr'
                                                        ) ?>"
                                    data-form-action="<?= esc(
                                                            route_to(
                                                                'admin.members.unblock',
                                                                $resolvedMemberId
                                                            ),
                                                            'attr'
                                                        ) ?>">

                                    <i
                                        class="ri-checkbox-circle-line"
                                        aria-hidden="true"></i>

                                    Unblock Member
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact and profile completion -->
    <div class="row g-4 mb-4">

        <div class="col-12 col-lg-7">
            <div
                class="card
                    border
                    border-danger
                    border-opacity-25
                    h-100">

                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i
                            class="ri-contacts-line me-1"
                            aria-hidden="true"></i>

                        Contact Information
                    </h5>
                </div>

                <div class="card-body">
                    <div class="row g-3">

                        <div class="col-12 col-md-6">
                            <div class="border-bottom pb-2 h-100">
                                <div class="text-muted fs-12 mb-1">
                                    Mobile Number
                                </div>

                                <div
                                    class="d-flex
                                        align-items-center
                                        gap-2
                                        fw-medium">

                                    <span>
                                        <?= esc(
                                            $mobileNumber !== ''
                                                ? $mobileNumber
                                                : '—'
                                        ) ?>
                                    </span>

                                    <i
                                        class="<?= $isMobileVerified
                                                    ? 'ri-checkbox-circle-fill text-success'
                                                    : 'ri-close-circle-fill text-danger' ?>"
                                        aria-label="<?= $isMobileVerified
                                                        ? 'Mobile verified'
                                                        : 'Mobile not verified' ?>">
                                    </i>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="border-bottom pb-2 h-100">
                                <div class="text-muted fs-12 mb-1">
                                    Email Address
                                </div>

                                <div
                                    class="d-flex
                                        align-items-center
                                        gap-2
                                        fw-medium">

                                    <span>
                                        <?= esc(
                                            $emailAddress !== ''
                                                ? $emailAddress
                                                : 'Not added'
                                        ) ?>
                                    </span>

                                    <?php if (
                                        $emailAddress !== ''
                                    ): ?>
                                        <i
                                            class="<?= $isEmailVerified
                                                        ? 'ri-checkbox-circle-fill text-success'
                                                        : 'ri-close-circle-fill text-danger' ?>"
                                            aria-label="<?= $isEmailVerified
                                                            ? 'Email verified'
                                                            : 'Email not verified' ?>">
                                        </i>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="border-bottom pb-2 h-100">
                                <div class="text-muted fs-12 mb-1">
                                    Current Location
                                </div>

                                <div class="fw-medium">
                                    <?= esc(
                                        $displayValue(
                                            $currentLocation
                                        )
                                    ) ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="border-bottom pb-2 h-100">
                                <div class="text-muted fs-12 mb-1">
                                    Account Created
                                </div>

                                <div class="fw-medium">
                                    <?php if (
                                        $accountCreatedIso !== ''
                                    ): ?>
                                        <time
                                            datetime="<?= esc(
                                                            $accountCreatedIso,
                                                            'attr'
                                                        ) ?>">

                                            <?= esc(
                                                $displayAccountCreated
                                            ) ?>
                                        </time>
                                    <?php else: ?>
                                        <?= esc(
                                            $displayAccountCreated
                                        ) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div
                class="card
                    border
                    border-danger
                    border-opacity-25
                    h-100">

                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i
                            class="ri-pie-chart-line me-1"
                            aria-hidden="true"></i>

                        Profile Completion
                    </h5>
                </div>

                <div class="card-body">
                    <?php
                    $completionPercentage = max(
                        0,
                        min(
                            100,
                            (int) (
                                $resolvedOverallSummary['percentage']
                                ?? $resolvedOverallSummary['completionPercentage']
                                ?? 0
                            )
                        )
                    );
                    ?>

                    <div
                        class="d-flex
                            align-items-center
                            justify-content-between
                            mb-2">

                        <span class="text-muted">
                            Completion
                        </span>

                        <span class="fw-semibold">
                            <?= esc(
                                (string) $completionPercentage
                            ) ?>%
                        </span>
                    </div>

                    <div
                        class="progress mb-3"
                        role="progressbar"
                        aria-label="Profile completion"
                        aria-valuenow="<?= esc(
                                            (string) $completionPercentage,
                                            'attr'
                                        ) ?>"
                        aria-valuemin="0"
                        aria-valuemax="100">

                        <div
                            class="progress-bar"
                            style="width: <?= esc(
                                                (string) $completionPercentage,
                                                'attr'
                                            ) ?>%">
                        </div>
                    </div>

                    <p class="text-muted mb-0">
                        Completion is calculated through the existing
                        member-profile summary service.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile image -->
    <div
        class="card
        border
        border-danger
        border-opacity-25
        mb-4">

        <div
            class="card-header
            d-flex
            flex-column
            flex-sm-row
            align-items-sm-center
            justify-content-between
            gap-2">

            <div>
                <h5 class="card-title mb-1">
                    <i
                        class="ri-image-2-line me-1"
                        aria-hidden="true"></i>

                    Member Photographs
                </h5>

                <p class="text-muted fs-13 mb-0">
                    Approved, pending and retained rejected photographs.
                </p>
            </div>

            <span class="badge bg-primary-subtle text-primary">
                <?= esc(
                    (string) count(
                        $resolvedAdminPhotos
                    )
                ) ?>
            </span>
        </div>

        <div class="card-body">
            <?php if (
                $resolvedAdminPhotos === []
            ): ?>
                <div
                    class="border
                    rounded-3
                    text-center
                    text-muted
                    p-4">

                    <i
                        class="ri-image-line
                        fs-28
                        d-block
                        mb-2"
                        aria-hidden="true"></i>

                    <p class="mb-0">
                        No retained member photographs are available.
                    </p>
                </div>
            <?php else: ?>
                <div
                    class="row
        flex-nowrap
        overflow-auto
        g-3
        pb-2">

                    <?php foreach (
                        $resolvedAdminPhotos
                        as $index => $photo
                    ): ?>
                        <?php
                        if (!is_array($photo)) {
                            continue;
                        }

                        $photoId = (int) (
                            $photo['id']
                            ?? 0
                        );

                        $status = mb_strtoupper(
                            trim(
                                (string) (
                                    $photo['status']
                                    ?? ''
                                )
                            )
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

                        $ribbonClass = match ($status) {
                            MemberPhotoModel::STATUS_APPROVED =>
                            'ribbon-success',

                            MemberPhotoModel::STATUS_REJECTED =>
                            'ribbon-danger',

                            MemberPhotoModel::STATUS_PENDING =>
                            'ribbon-warning',

                            default =>
                            'ribbon-secondary',
                        };

                        $statusLabel = match ($status) {
                            MemberPhotoModel::STATUS_APPROVED =>
                            'Approved',

                            MemberPhotoModel::STATUS_REJECTED =>
                            'Rejected',

                            MemberPhotoModel::STATUS_PENDING =>
                            'Pending',

                            default =>
                            'Unknown',
                        };
                        ?>

                        <div
                            class="col-8
                col-sm-5
                col-md-4
                col-lg-3
                col-xl-2
                flex-shrink-0">

                            <div
                                class="card
                    ribbon-box
                    border
                    shadow-none
                    h-100
                    mb-0">

                                <div
                                    class="ribbon
                        ribbon-shape
                        <?= esc(
                            $ribbonClass,
                            'attr'
                        ) ?>">

                                    <?= esc($statusLabel) ?>
                                </div>

                                <div class="card-body p-2 pt-5">
                                    <button
                                        type="button"
                                        class="btn
                            border-0
                            p-0
                            w-100"
                                        data-admin-photo
                                        data-modal-url-endpoint="<?= esc(
                                                                        route_to(
                                                                            'admin.members.photos.modal-urls',
                                                                            $resolvedMemberId,
                                                                            $photoId
                                                                        ),
                                                                        'attr'
                                                                    ) ?>"
                                        data-photo-title="<?= esc(
                                                                $fullName
                                                                    . ' photograph '
                                                                    . ($index + 1),
                                                                'attr'
                                                            ) ?>">

                                        <img
                                            src="<?= esc(
                                                        $thumbnailUrl,
                                                        'attr'
                                                    ) ?>"
                                            alt="<?= esc(
                                                        $fullName
                                                            . ' '
                                                            . mb_strtolower(
                                                                $statusLabel
                                                            )
                                                            . ' photograph',
                                                        'attr'
                                                    ) ?>"
                                            class="img-thumbnail w-100"
                                            loading="lazy">
                                    </button>

                                    <?php if (
                                        ($photo['isPrimary'] ?? false)
                                        === true
                                    ): ?>
                                        <span
                                            class="badge
                                bg-primary-subtle
                                text-primary
                                mt-2">

                                            <i
                                                class="ri-star-fill me-1"
                                                aria-hidden="true"></i>

                                            Main
                                        </span>
                                    <?php endif; ?>

                                    <?php if (
                                        $status
                                        === MemberPhotoModel::STATUS_REJECTED
                                    ): ?>
                                        <div class="mt-2">
                                            <div class="text-muted fs-12">
                                                Rejection reason
                                            </div>

                                            <div class="fs-12">
                                                <?= esc(
                                                    trim(
                                                        (string) (
                                                            $photo['rejectionReason']
                                                            ?? ''
                                                        )
                                                    ) !== ''
                                                        ? $photo['rejectionReason']
                                                        : 'No reason recorded.'
                                                ) ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-4 align-items-start">

        <!-- Left profile column -->
        <div class="col-12 col-lg-7">
            <div
                class="card
                    border
                    border-danger
                    border-opacity-25
                    overflow-hidden">

                <!-- About Me -->
                <section
                    class="card-body
                        p-3
                        p-lg-4
                        border-bottom">

                    <div
                        class="d-flex
                            align-items-center
                            gap-2
                            mb-3">

                        <span
                            class="avatar-sm
                                d-inline-flex
                                align-items-center
                                justify-content-center">

                            <span
                                class="avatar-title
                                    rounded-circle
                                    bg-primary-subtle
                                    text-primary">

                                <i
                                    class="ri-user-smile-line"
                                    aria-hidden="true"></i>
                            </span>
                        </span>

                        <h2 class="fs-16 fw-semibold mb-0">
                            About Me
                        </h2>
                    </div>

                    <?php if (
                        $resolvedAboutMe !== ''
                    ): ?>
                        <p
                            class="text-body-secondary
                                lh-lg
                                mb-0">

                            <?= nl2br(
                                esc(
                                    $resolvedAboutMe
                                )
                            ) ?>
                        </p>
                    <?php else: ?>
                        <p class="text-muted mb-0">
                            About Me has not been added.
                        </p>
                    <?php endif; ?>
                </section>

                <!-- Basic Details -->
                <section
                    class="card-body
                        p-3
                        p-lg-4
                        border-bottom">

                    <div
                        class="d-flex
                            align-items-center
                            gap-2
                            mb-3">

                        <span class="avatar-sm">
                            <span
                                class="avatar-title
                                    rounded-circle
                                    bg-primary-subtle
                                    text-primary">

                                <i
                                    class="ri-id-card-line"
                                    aria-hidden="true"></i>
                            </span>
                        </span>

                        <h2 class="fs-16 fw-semibold mb-0">
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
                                        pb-2
                                        h-100">

                                    <div
                                        class="text-muted
                                            fs-12
                                            mb-1">

                                        <?= esc($label) ?>
                                    </div>

                                    <div class="fw-medium fs-14">
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

                <!-- Education and Profession -->
                <section
                    class="card-body
                        p-3
                        p-lg-4">

                    <div
                        class="d-flex
                            align-items-center
                            gap-2
                            mb-3">

                        <span class="avatar-sm">
                            <span
                                class="avatar-title
                                    rounded-circle
                                    bg-primary-subtle
                                    text-primary">

                                <i
                                    class="ri-graduation-cap-line"
                                    aria-hidden="true"></i>
                            </span>
                        </span>

                        <h2 class="fs-16 fw-semibold mb-0">
                            Education and Profession
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
                                        pb-2
                                        h-100">

                                    <div
                                        class="text-muted
                                            fs-12
                                            mb-1">

                                        <?= esc($label) ?>
                                    </div>

                                    <div class="fw-medium fs-14">
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

        <!-- Right profile column -->
        <div class="col-12 col-lg-5">
            <div
                class="card
                    border
                    border-danger
                    border-opacity-25
                    overflow-hidden">

                <!-- Family Details -->
                <section
                    class="card-body
                        p-3
                        p-lg-4
                        border-bottom">

                    <div
                        class="d-flex
                            align-items-center
                            gap-2
                            mb-3">

                        <span class="avatar-sm">
                            <span
                                class="avatar-title
                                    rounded-circle
                                    bg-primary-subtle
                                    text-primary">

                                <i
                                    class="ri-group-line"
                                    aria-hidden="true"></i>
                            </span>
                        </span>

                        <h2 class="fs-16 fw-semibold mb-0">
                            Family Details
                        </h2>
                    </div>

                    <div class="row g-3">
                        <?php foreach (
                            $familyDetailList
                            as $label => $value
                        ): ?>
                            <div class="col-12">
                                <div class="border-bottom pb-2">
                                    <div
                                        class="text-muted
                                            fs-12
                                            mb-1">

                                        <?= esc($label) ?>
                                    </div>

                                    <div class="fw-medium fs-14">
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

                <!-- Lifestyle -->
                <section
                    class="card-body
                        p-3
                        p-lg-4">

                    <div
                        class="d-flex
                            align-items-center
                            gap-2
                            mb-3">

                        <span class="avatar-sm">
                            <span
                                class="avatar-title
                                    rounded-circle
                                    bg-primary-subtle
                                    text-primary">

                                <i
                                    class="ri-heart-pulse-line"
                                    aria-hidden="true"></i>
                            </span>
                        </span>

                        <h2 class="fs-16 fw-semibold mb-0">
                            Lifestyle
                        </h2>
                    </div>

                    <?php if (
                        $resolvedLifestyleDetails === []
                    ): ?>
                        <p class="text-muted mb-0">
                            Lifestyle details have not been added.
                        </p>
                    <?php else: ?>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach (
                                $resolvedLifestyleDetails
                                as $lifestyle
                            ): ?>
                                <?php
                                if (!is_array($lifestyle)) {
                                    continue;
                                }

                                $lifestyleLabel = trim(
                                    (string) (
                                        $lifestyle['option_name']
                                        ?? $lifestyle['name']
                                        ?? $lifestyle['label']
                                        ?? ''
                                    )
                                );

                                if (
                                    $lifestyleLabel === ''
                                ) {
                                    continue;
                                }
                                ?>

                                <span
                                    class="badge
                                        bg-primary-subtle
                                        text-primary
                                        p-2">

                                    <?= esc(
                                        $lifestyleLabel
                                    ) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </div>
</div>

<!-- Block/unblock modal -->
<div
    class="modal fade"
    id="member-status-modal"
    tabindex="-1"
    aria-labelledby="member-status-modal-title"
    aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form
                method="post"
                id="member-status-form"
                data-validate
                novalidate>

                <?= csrf_field() ?>

                <input
                    type="hidden"
                    name="return_url"
                    value="<?= esc(
                                current_url(),
                                'attr'
                            ) ?>">

                <div class="modal-header bg-info-subtle py-2">
                    <div>
                        <h5
                            class="modal-title mb-1"
                            id="member-status-modal-title">
                            Change Member Status
                        </h5>

                        <div
                            id="member-status-identity"
                            class="fs-13">
                        </div>
                    </div>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close">
                    </button>
                </div>

                <div class="modal-body">
                    <div
                        class="alert
                            alert-success
                            border-0"
                        role="status">

                        <div class="fw-semibold">
                            Member
                        </div>

                        <div id="member-status-member-name">
                            —
                        </div>

                        <div
                            class="text-muted fs-13"
                            id="member-status-member-code">
                            —
                        </div>
                    </div>

                    <p
                        id="member-status-message"
                        class="text-muted">
                    </p>

                    <label
                        for="member-status-reason"
                        class="form-label">
                        Reason
                    </label>

                    <input
                        type="text"
                        id="member-status-reason"
                        name="reason"
                        class="form-control"
                        maxlength="64"
                        required
                        aria-describedby="
                            member-status-reason-help
                            member-status-reason-error">

                    <div
                        id="member-status-reason-help"
                        class="form-text color-pink">
                        Maximum 64 characters.
                    </div>

                    <div
                        id="member-status-reason-error"
                        class="invalid-feedback">
                        Please enter a reason of no more than
                        64 characters.
                    </div>
                </div>

                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button
                        type="submit"
                        id="member-status-submit"
                        class="btn btn-primary">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Status history modal -->
<div
    class="modal fade"
    id="member-history-modal"
    tabindex="-1"
    aria-labelledby="member-history-modal-title"
    aria-hidden="true">

    <div
        class="modal-dialog
            modal-lg
            modal-dialog-centered
            modal-dialog-scrollable">

        <div class="modal-content">
            <div class="modal-header bg-info-subtle py-2">
                <h5
                    class="modal-title"
                    id="member-history-modal-title">

                    Member Status History
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close">
                </button>
            </div>

            <div
                class="modal-body"
                id="member-history-content">

                <div class="text-center text-muted py-4">
                    Select History to load account changes.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Photo preview modal -->
<div
    class="modal fade"
    id="admin-photo-modal"
    tabindex="-1"
    aria-labelledby="admin-photo-modal-title"
    aria-hidden="true">

    <div
        class="modal-dialog
            modal-lg
            modal-dialog-centered">

        <div class="modal-content">
            <div class="modal-header bg-info-subtle py-2">
                <h5
                    class="modal-title"
                    id="admin-photo-modal-title">

                    Member Photograph
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close">
                </button>
            </div>

            <div class="modal-body text-center">
                <div
                    id="admin-photo-loading"
                    class="text-muted py-5">

                    <span
                        class="spinner-border
                            spinner-border-sm
                            me-2"
                        aria-hidden="true">
                    </span>

                    Loading photograph...
                </div>

                <div
                    id="admin-photo-error"
                    class="alert alert-danger d-none"
                    role="alert">
                </div>

                <img
                    id="admin-photo-image"
                    src=""
                    alt=""
                    class="img-fluid rounded d-none">
            </div>
        </div>
    </div>
</div>

<?php
$this->endSection();
