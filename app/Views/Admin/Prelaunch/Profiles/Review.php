<?php

declare(strict_types=1);

use App\Models\Prelaunch\PrelaunchPhotoModel;
use App\Models\Prelaunch\PrelaunchProfileModel;

/**
 * Administrator prelaunch-profile review.
 *
 * @var string                     $pageTitle
 * @var array<string, mixed>       $profile
 * @var list<array<string, mixed>> $photos
 * @var array<string, mixed>       $photoSummary
 * @var array<string, string>      $validationErrors
 * @var array<string, string>|null $formAlert
 */

$profile = is_array(
    $profile ?? null
)
    ? $profile
    : [];

$photos = is_array(
    $photos ?? null
)
    ? $photos
    : [];

$photoSummary = is_array(
    $photoSummary ?? null
)
    ? $photoSummary
    : [
        'total' => count($photos),
        'pending' => 0,
        'approved' => 0,
        'rejected' => 0,
        'hasApproved' => false,
    ];

$errors = is_array(
    $validationErrors ?? null
)
    ? $validationErrors
    : [];

$alert = is_array(
    $formAlert ?? null
)
    ? $formAlert
    : null;

$profileId = (int) (
    $profile['id']
    ?? 0
);

$status = mb_strtoupper(
    trim(
        (string) (
            $profile['status']
            ?? ''
        )
    )
);

$isDraft =
    $status
    === PrelaunchProfileModel::STATUS_DRAFT;

$isApproved =
    $status
    === PrelaunchProfileModel::STATUS_APPROVED;

$isRejected =
    $status
    === PrelaunchProfileModel::STATUS_REJECTED;

$approvedPhotoCount = max(
    0,
    (int) (
        $photoSummary['approved']
        ?? 0
    )
);

$pendingPhotoCount = max(
    0,
    (int) (
        $photoSummary['pending']
        ?? 0
    )
);

$rejectedPhotoCount = max(
    0,
    (int) (
        $photoSummary['rejected']
        ?? 0
    )
);

$canApprove =
    $isDraft
    && $approvedPhotoCount >= 1;

/**
 * Return readable text for an optional profile value.
 */
$displayValue = static function (
    mixed $value,
    string $fallback = 'Not added'
): string {
    $text = trim(
        (string) $value
    );

    return $text !== ''
        ? $text
        : $fallback;
};

$statusBadgeClass = match ($status) {
    PrelaunchProfileModel::STATUS_APPROVED =>
    'text-bg-success',

    PrelaunchProfileModel::STATUS_REJECTED =>
    'text-bg-danger',

    default =>
    'text-bg-warning',
};

$location = implode(
    ', ',
    array_filter(
        [
            trim(
                (string) (
                    $profile['city_name']
                    ?? ''
                )
            ),
            trim(
                (string) (
                    $profile['state_name']
                    ?? ''
                )
            ),
            trim(
                (string) (
                    $profile['country_name']
                    ?? ''
                )
            ),
        ],
        static fn(
            string $value
        ): bool => $value !== ''
    )
);

$personalDetails = [
    'Profile Created For' =>
    $profile['profile_created_for']
        ?? '',

    'Gender' =>
    $profile['gender']
        ?? '',

    'Date of Birth' =>
    $profile['date_of_birth']
        ?? '',

    'Marital Status' =>
    $profile['marital_status_name']
        ?? '',

    'Height' =>
    $profile['height_name']
        ?? '',

    'Location' =>
    $location,
];

$professionDetails = [
    'Highest Education' =>
    $profile['education_name']
        ?? '',

    'Employed In' =>
    $profile['employed_in']
        ?? '',

    'Occupation' =>
    $profile['occupation_name']
        ?? '',
];

$familyDetails = [
    "Father's Name" =>
    $profile['father_name']
        ?? '',

    "Mother's Name" =>
    $profile['mother_name']
        ?? '',

    'Community' =>
    $profile['community_name']
        ?? '',

    'Gotra' =>
    $profile['gotra']
        ?? '',

    'Nearest Gurudwara' =>
    $profile['nearest_gurudwara']
        ?? '',
];

$fieldOfficerDetails = [
    'Name' =>
    $profile['field_officer_name']
        ?? '',

    'Officer Code' =>
    $profile['officer_code']
        ?? '',

    'Account Status' =>
    $profile['field_officer_status']
        ?? '',
];

/**
 * Modals must be rendered outside cards and gallery containers.
 *
 * @var list<array<string, mixed>> $photoModals
 */
$photoModals = [];

$this->extend(
    'Admin/Layouts/Main'
);

$this->section(
    'content'
);
?>

<div class="container-fluid py-3 pt-0">
    <!-- Page heading. -->
    <div
        class="d-flex flex-column
            flex-md-row
            justify-content-between
            align-items-md-center
            gap-3 mb-3">

        <div>
            <a
                href="<?= route_to(
                            'admin.prelaunch.profiles.index'
                        ) ?>"
                class="d-inline-flex
                    align-items-center
                    gap-1 text-primary
                    fw-medium mb-2">

                <i
                    class="ri-arrow-left-line"
                    aria-hidden="true"></i>

                Back to prelaunch profiles
            </a>

            <div
                class="d-flex
                    align-items-center
                    gap-3">

                <div
                    class="avatar-sm rounded-circle
                        bg-danger-subtle
                        text-danger
                        d-flex
                        align-items-center
                        justify-content-center"
                    aria-hidden="true">

                    <i
                        class="ri-user-search-line fs-20">
                    </i>
                </div>

                <div>
                    <h1 class="fs-18 fw-semibold mb-1">
                        <?= esc(
                            $displayValue(
                                $profile['full_name']
                                    ?? '',
                                'Prelaunch Profile'
                            )
                        ) ?>
                    </h1>

                    <div
                        class="d-flex
                            align-items-center
                            flex-wrap gap-2">

                        <span class="text-muted fs-13">
                            <?= esc(
                                $displayValue(
                                    $profile['profile_reference'] ?? ''
                                )
                            ) ?>
                        </span>

                        <span
                            class="badge <?= esc(
                                                $statusBadgeClass,
                                                'attr'
                                            ) ?>">
                            <?= esc($status) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($alert !== null): ?>
        <div
            class="alert alert-<?= esc(
                                    $alert['type']
                                        ?? 'danger',
                                    'attr'
                                ) ?>"
            role="alert">

            <?php if (
                trim(
                    (string) (
                        $alert['title']
                        ?? ''
                    )
                ) !== ''
            ): ?>
                <strong class="d-block mb-1">
                    <?= esc(
                        $alert['title']
                    ) ?>
                </strong>
            <?php endif ?>

            <div>
                <?= esc(
                    $alert['message']
                        ?? ''
                ) ?>
            </div>
        </div>
    <?php endif ?>

    <?php if ($isRejected): ?>
        <div
            class="alert alert-danger"
            role="alert">

            <div class="d-flex gap-2">
                <i
                    class="ri-lock-line fs-18"
                    aria-hidden="true"></i>

                <div>
                    <strong class="d-block mb-1">
                        Profile rejected and locked
                    </strong>

                    Contact information and photo decisions
                    cannot be modified.

                    <?php if (
                        trim(
                            (string) (
                                $profile['rejection_reason'] ?? ''
                            )
                        ) !== ''
                    ): ?>
                        <div class="mt-2">
                            <strong>Reason:</strong>

                            <?= esc(
                                $profile['rejection_reason']
                            ) ?>
                        </div>
                    <?php endif ?>
                </div>
            </div>
        </div>
    <?php endif ?>

    <?php if ($isApproved): ?>
        <div
            class="alert alert-success"
            role="alert">

            <div class="d-flex gap-2">
                <i
                    class="ri-checkbox-circle-line fs-18"
                    aria-hidden="true"></i>

                <div>
                    <strong class="d-block mb-1">
                        Profile migrated successfully
                    </strong>

                    <?php if (
                        (int) (
                            $profile['migrated_user_id'] ?? 0
                        ) > 0
                    ): ?>
                        Member ID:

                        <?= esc(
                            $profile['migrated_user_id']
                        ) ?>
                    <?php endif ?>
                </div>
            </div>
        </div>
    <?php endif ?>

    <!-- Gallery and contact card. -->
    <div class="row g-3 g-lg-4 mb-4">
        <div class="col-12 col-xl-8">
            <article
                class="card border
                    border-danger
                    border-opacity-25
                    shadow-none h-100">

                <div class="card-body p-3 p-md-4">
                    <div
                        class="d-flex
                            justify-content-between
                            align-items-start
                            flex-wrap gap-3 mb-3">

                        <div
                            class="d-flex
                                align-items-start gap-3">

                            <div
                                class="avatar-sm flex-shrink-0"
                                aria-hidden="true">

                                <span
                                    class="avatar-title rounded-circle
                            bg-primary-subtle
                            text-primary fs-20">

                                    <i
                                        class="ri-gallery-line">
                                    </i>
                                </span>
                            </div>

                            <div>
                                <h2
                                    class="mb-1
                                        fs-14 fw-semibold">
                                    Photo Gallery
                                </h2>

                                <p
                                    class="text-muted
                                        mb-0 fs-12">
                                    Open a photo to approve or
                                    reject it.
                                </p>
                            </div>
                        </div>

                        <div
                            class="d-flex
                                flex-wrap gap-2">

                            <span
                                class="badge
                                    text-bg-success p-2 text-black">
                                <?= esc(
                                    (string) $approvedPhotoCount
                                ) ?>
                                Approved
                            </span>

                            <span
                                class="badge
                                    text-bg-warning p-2 text-black">
                                <?= esc(
                                    (string) $pendingPhotoCount
                                ) ?>
                                Pending
                            </span>

                            <?php if (
                                $rejectedPhotoCount > 0
                            ): ?>
                                <span
                                    class="badge
                                        text-bg-danger">
                                    <?= esc(
                                        (string) $rejectedPhotoCount
                                    ) ?>
                                    Rejected
                                </span>
                            <?php endif ?>
                        </div>
                    </div>

                    <hr class="my-2 mb-3">

                    <?php if ($photos === []): ?>
                        <div
                            class="border rounded
                                text-center p-4">

                            <div
                                class="avatar-lg
                                    rounded-circle
                                    bg-light text-muted
                                    d-inline-flex
                                    align-items-center
                                    justify-content-center
                                    mb-3">

                                <i
                                    class="ri-image-line fs-24"
                                    aria-hidden="true"></i>
                            </div>

                            <h3
                                class="fs-15
                                    fw-semibold mb-1">
                                No Photos Found
                            </h3>

                            <p
                                class="text-muted
                                    fs-13 mb-0">
                                This prelaunch profile does
                                not contain photographs.
                            </p>
                        </div>
                    <?php else: ?>
                        <div
                            class="row
                                g-2 g-md-3">

                            <?php foreach (
                                $photos as $photo
                            ): ?>
                                <?php
                                if (!is_array($photo)) {
                                    continue;
                                }

                                $photoId = (int) (
                                    $photo['id']
                                    ?? 0
                                );

                                if ($photoId <= 0) {
                                    continue;
                                }

                                $photoStatus =
                                    mb_strtoupper(
                                        trim(
                                            (string) (
                                                $photo['approval_status']
                                                ?? PrelaunchPhotoModel
                                                ::STATUS_PENDING
                                            )
                                        )
                                    );

                                $ribbonClass = match ($photoStatus) {
                                    PrelaunchPhotoModel
                                    ::STATUS_APPROVED =>
                                    'ribbon-success',

                                    PrelaunchPhotoModel
                                    ::STATUS_REJECTED =>
                                    'ribbon-danger',

                                    default =>
                                    'ribbon-warning',
                                };

                                $ribbonLabel = match ($photoStatus) {
                                    PrelaunchPhotoModel
                                    ::STATUS_APPROVED =>
                                    'Approved',

                                    PrelaunchPhotoModel
                                    ::STATUS_REJECTED =>
                                    'Rejected',

                                    default =>
                                    'Pending Approval',
                                };

                                $modalId =
                                    'prelaunch-photo-modal-'
                                    . $photoId;

                                $photoUrl = route_to(
                                    'admin.prelaunch.photos.view',
                                    $photoId,
                                    'original'
                                );

                                $photoModals[] = [
                                    'id' =>
                                    $photoId,

                                    'modalId' =>
                                    $modalId,

                                    'url' =>
                                    $photoUrl,

                                    'status' =>
                                    $photoStatus,

                                    'statusLabel' =>
                                    $ribbonLabel,

                                    'sequence' =>
                                    (int) (
                                        $photo['sequence_no']
                                        ?? 0
                                    ),

                                    'rejectionReason' =>
                                    trim(
                                        (string) (
                                            $photo['rejection_reason']
                                            ?? ''
                                        )
                                    ),
                                ];
                                ?>

                                <div
                                    class="col-6
                                        col-sm-4
                                        col-lg-3">

                                    <article
                                        class="card
                                            border
                                            border-danger
                                            border-opacity-25
                                            shadow-none
                                            ribbon-box
                                            right
                                            mb-1
                                            h-100">

                                        <div
                                            class="card-body
                                                p-2 pt-5">

                                            <div
                                                class="ribbon
                                                    ribbon-shape
                                                    <?= esc(
                                                        $ribbonClass,
                                                        'attr'
                                                    ) ?>"
                                                aria-label="<?= esc(
                                                                $ribbonLabel,
                                                                'attr'
                                                            ) ?>">

                                                <span>
                                                    <?= esc(
                                                        $ribbonLabel
                                                    ) ?>
                                                </span>
                                            </div>

                                            <button
                                                type="button"
                                                class="prelaunch-admin-photo-button
                                                    border-0
                                                    bg-transparent
                                                    p-0 w-100"
                                                data-bs-toggle="modal"
                                                data-bs-target="#<?= esc(
                                                                        $modalId,
                                                                        'attr'
                                                                    ) ?>"
                                                aria-label="<?= esc(
                                                                'Open photograph '
                                                                    . (
                                                                        $photo['sequence_no']
                                                                        ?? ''
                                                                    ),
                                                                'attr'
                                                            ) ?>">

                                                <span
                                                    class="prelaunch-admin-photo-media
                                                        ratio ratio-1x1
                                                        bg-light
                                                        border rounded
                                                        overflow-hidden
                                                        d-block">

                                                    <img
                                                        src="<?= esc(
                                                                    $photoUrl,
                                                                    'attr'
                                                                ) ?>"
                                                        alt="<?= esc(
                                                                    'Prelaunch photograph '
                                                                        . (
                                                                            $photo['sequence_no']
                                                                            ?? ''
                                                                        ),
                                                                    'attr'
                                                                ) ?>"
                                                        class="w-100 h-100
                                                            object-fit-contain"
                                                        loading="lazy">
                                                </span>
                                            </button>
                                        </div>
                                    </article>
                                </div>
                            <?php endforeach ?>
                        </div>
                    <?php endif ?>
                </div>
            </article>
        </div>

        <div class="col-12 col-xl-4">
            <article
                class="card border
                    border-danger
                    border-opacity-25
                    shadow-none h-100">

                <div class="card-body p-3 p-md-4">
                    <div
                        class="d-flex
                            align-items-start
                            gap-3 mb-3">
                        <div
                            class="avatar-sm flex-shrink-0"
                            aria-hidden="true">

                            <span
                                class="avatar-title rounded-circle
                            bg-primary-subtle
                            text-primary fs-20">

                                <i
                                    class="ri-contacts-book-line">
                                </i>
                            </span>
                        </div>

                        <div>
                            <h2
                                class="mb-1
                                    fs-14 fw-semibold">
                                Contact and Decision
                            </h2>

                            <p
                                class="text-muted
                                    mb-0 fs-12">
                                Confirm contact information
                                before migration.
                            </p>
                        </div>
                    </div>

                    <hr class="my-2 mb-3">

                    <form
                        action="<?= esc(
                                    route_to(
                                        'admin.prelaunch.profiles.approve',
                                        $profileId
                                    ),
                                    'attr'
                                ) ?>"
                        method="post"
                        data-submit-loader>

                        <?= csrf_field() ?>

                        <!--
                            Keep country code and mobile in one input group,
                            matching the prelaunch profile-entry UI.
                        -->
                        <div class="mb-3">
                            <label
                                for="mobile_number"
                                class="form-label">
                                Mobile Number
                            </label>

                            <input
                                type="hidden"
                                id="country_code"
                                name="country_code"
                                value="<?= esc(
                                            old(
                                                'country_code',
                                                $profile['country_code'] ?? '+91'
                                            ),
                                            'attr'
                                        ) ?>">

                            <div
                                class="input-group
                                    has-validation">

                                <span
                                    class="input-group-text">
                                    <?= esc(
                                        old(
                                            'country_code',
                                            $profile['country_code'] ?? '+91'
                                        )
                                    ) ?>
                                </span>

                                <input
                                    type="tel"
                                    id="mobile_number"
                                    name="mobile_number"
                                    class="form-control
                                        <?= (
                                            isset(
                                                $errors['mobile_number']
                                            )
                                            || isset(
                                                $errors['country_code']
                                            )
                                        )
                                            ? 'is-invalid'
                                            : '' ?>"
                                    value="<?= esc(
                                                old(
                                                    'mobile_number',
                                                    $profile['mobile_number'] ?? ''
                                                ),
                                                'attr'
                                            ) ?>"
                                    placeholder="Enter mobile number"
                                    inputmode="numeric"
                                    pattern="[6-9][0-9]{9}"
                                    minlength="10"
                                    maxlength="10"
                                    autocomplete="tel-national"
                                    <?= !$isDraft
                                        ? 'disabled'
                                        : '' ?>
                                    required>

                                <div
                                    class="invalid-feedback">
                                    <?= esc(
                                        $errors['mobile_number']
                                            ?? $errors['country_code']
                                            ?? 'Please enter a valid '
                                            . '10-digit mobile number.'
                                    ) ?>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label
                                for="email"
                                class="form-label">
                                Email

                                <span
                                    class="color-pink fs-12">
                                    (Optional)
                                </span>
                            </label>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-control
                                    <?= isset(
                                        $errors['email']
                                    )
                                        ? 'is-invalid'
                                        : '' ?>"
                                value="<?= esc(
                                            old(
                                                'email',
                                                $profile['email']
                                                    ?? ''
                                            ),
                                            'attr'
                                        ) ?>"
                                placeholder="Enter email address"
                                maxlength="190"
                                autocomplete="email"
                                <?= !$isDraft
                                    ? 'disabled'
                                    : '' ?>>

                            <div
                                class="invalid-feedback">
                                <?= esc(
                                    $errors['email']
                                        ?? 'Please enter a '
                                        . 'valid email address.'
                                ) ?>
                            </div>

                            <div
                                class="form-text
                                    color-pink">
                                Email may remain empty.
                            </div>
                        </div>

                        <?php if ($isDraft): ?>
                            <button
                                type="submit"
                                class="btn
                                    registration-form__submit
                                    w-80
                                    fs-14 fw-semibold
                                    text-uppercase
                                    mb-3"
                                <?= !$canApprove
                                    ? 'disabled'
                                    : '' ?>>

                                <span
                                    data-submit-loader-label
                                    class="d-inline-flex
                                        align-items-center
                                        justify-content-center
                                        gap-2">

                                    <i
                                        class="ri-user-follow-line"
                                        aria-hidden="true"></i>

                                    Save Contact and Approve
                                </span>

                                <span
                                    class="d-none"
                                    data-submit-loader-spinner>

                                    <span
                                        class="spinner-border
                                            spinner-border-sm"
                                        aria-hidden="true">
                                    </span>

                                    Migrating Profile...
                                </span>
                            </button>
                        <?php endif ?>
                    </form>

                    <?php if (
                        $isDraft
                        && !$canApprove
                    ): ?>
                        <div
                            class="alert
                                alert-warning
                                py-2 px-2
                                fw-medium
                                fs-12 mb-3"
                            role="alert">

                            <i
                                class="ri-information-line
                                    me-1"
                                aria-hidden="true"></i>

                            Approve at least one photograph
                            before approving this profile.
                        </div>
                    <?php endif ?>

                    <?php if ($isDraft): ?>
                        <button
                            type="button"
                            class="btn
                                btn-outline-danger
                                w-100
                                d-inline-flex
                                align-items-center
                                justify-content-center
                                gap-2"
                            data-bs-toggle="modal"
                            data-bs-target="#reject-profile-modal">

                            <i
                                class="ri-user-unfollow-line"
                                aria-hidden="true"></i>

                            Reject Profile
                        </button>
                    <?php endif ?>
                </div>
            </article>
        </div>
    </div>

    <!-- Member-style profile detail sections. -->
    <div class="row g-3 g-lg-4">
        <div class="col-12 col-lg-8">
            <?php
            $detailSections = [
                [
                    'title' =>
                    'Personal Details',

                    'description' =>
                    'Basic member information and location.',

                    'icon' =>
                    'ri-user-smile-line',

                    'details' =>
                    $personalDetails,
                ],
                [
                    'title' =>
                    'Education and Profession',

                    'description' =>
                    'Education and current profession details.',

                    'icon' =>
                    'ri-graduation-cap-line',

                    'details' =>
                    $professionDetails,
                ],
                [
                    'title' =>
                    'Family Details',

                    'description' =>
                    'Family, community and religious details.',

                    'icon' =>
                    'ri-group-line',

                    'details' =>
                    $familyDetails,
                ],
            ];
            ?>

            <?php foreach (
                $detailSections
                as $section
            ): ?>
                <article
                    class="card border
                        border-danger
                        border-opacity-25
                        shadow-none mb-3">

                    <div class="card-body p-3 p-md-4">
                        <div
                            class="d-flex
                                align-items-start
                                gap-3 mb-3">

                            <div
                                class="avatar-sm flex-shrink-0"
                                aria-hidden="true">

                                <span
                                    class="avatar-title rounded-circle
                            bg-primary-subtle
                            text-primary fs-20">

                                    <i
                                        class="<?= esc(
                                                    $section['icon'],
                                                    'attr'
                                                ) ?>"></i>
                                </span>
                            </div>

                            <div>
                                <h2
                                    class="mb-1
                                        fs-14 fw-semibold">
                                    <?= esc(
                                        $section['title']
                                    ) ?>
                                </h2>

                                <p
                                    class="text-muted
                                        mb-0 fs-12">
                                    <?= esc(
                                        $section['description']
                                    ) ?>
                                </p>
                            </div>
                        </div>

                        <hr class="my-2 mb-3">

                        <div class="row g-3">
                            <?php foreach (
                                $section['details']
                                as $label => $value
                            ): ?>
                                <div
                                    class="col-12
                                        col-md-6">

                                    <div
                                        class="border-bottom
                                            pb-2 h-100">

                                        <div
                                            class="text-muted
                                                fs-12 mb-1">
                                            <?= esc($label) ?>
                                        </div>

                                        <div
                                            class="fs-13
                                                fw-medium">
                                            <?= esc(
                                                $displayValue(
                                                    $value
                                                )
                                            ) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach ?>
                        </div>
                    </div>
                </article>
            <?php endforeach ?>
        </div>

        <div class="col-12 col-lg-4">
            <article
                class="card border
                    border-danger
                    border-opacity-25
                    shadow-none">

                <div class="card-body p-3 p-md-4">
                    <div
                        class="d-flex
                            align-items-start
                            gap-3 mb-3">
                        <div
                            class="avatar-sm flex-shrink-0"
                            aria-hidden="true">

                            <span
                                class="avatar-title rounded-circle
                            bg-primary-subtle
                            text-primary fs-20">

                                <i
                                    class="ri-user-star-line">
                                </i>
                            </span>
                        </div>

                        <div>
                            <h2
                                class="mb-1
                                    fs-14 fw-semibold">
                                Field Officer
                            </h2>

                            <p
                                class="text-muted
                                    mb-0 fs-12">
                                Officer associated with this
                                prelaunch profile.
                            </p>
                        </div>
                    </div>

                    <hr class="my-2 mb-3">

                    <?php foreach (
                        $fieldOfficerDetails
                        as $label => $value
                    ): ?>
                        <div
                            class="border-bottom
                                pb-2 mb-3">

                            <div
                                class="text-muted
                                    fs-12 mb-1">
                                <?= esc($label) ?>
                            </div>

                            <div
                                class="fs-13 fw-medium">
                                <?= esc(
                                    $displayValue(
                                        $value
                                    )
                                ) ?>
                            </div>
                        </div>
                    <?php endforeach ?>
                </div>
            </article>
        </div>
    </div>
</div>

<!--
    Photo modals are deliberately outside all cards, rows and gallery
    containers. This prevents overflow, transform and stacking-context
    rules from positioning Bootstrap modal content below the page.
-->
<?php foreach (
    $photoModals as $photoModal
): ?>
    <div
        class="modal fade"
        id="<?= esc(
                $photoModal['modalId'],
                'attr'
            ) ?>"
        tabindex="-1"
        aria-labelledby="<?= esc(
                                $photoModal['modalId']
                                    . '-title',
                                'attr'
                            ) ?>"
        aria-hidden="true">

        <div
            class="modal-dialog
                modal-dialog-centered
                modal-lg
                modal-dialog-scrollable">

            <div
                class="modal-content
                    border-0 shadow">

                <div class="modal-header">
                    <div>
                        <h2
                            id="<?= esc(
                                    $photoModal['modalId']
                                        . '-title',
                                    'attr'
                                ) ?>"
                            class="modal-title
                                fs-16 fw-semibold
                                mb-1">
                            Review Photograph
                        </h2>

                        <p
                            class="text-muted
                                fs-12 mb-0">
                            Photograph
                            <?= esc(
                                (string) (
                                    $photoModal['sequence']
                                    ?? ''
                                )
                            ) ?>

                            ·

                            <?= esc(
                                $photoModal['statusLabel']
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

                <div
                    class="modal-body
                        bg-light
                        text-center
                        p-3 p-md-4">

                    <div
                        class="prelaunch-admin-modal-media
                            border rounded
                            bg-white
                            overflow-hidden
                            mx-auto">

                        <img
                            src="<?= esc(
                                        $photoModal['url'],
                                        'attr'
                                    ) ?>"
                            class="w-100 h-100
                                object-fit-contain"
                            alt="<?= esc(
                                        'Prelaunch photograph '
                                            . (
                                                $photoModal['sequence']
                                                ?? ''
                                            ),
                                        'attr'
                                    ) ?>">
                    </div>
                </div>

                <?php if ($isDraft): ?>
                    <div
                        class="modal-footer
                            flex-column
                            flex-md-row
                            align-items-stretch
                            align-items-md-center">

                        <form
                            action="<?= esc(
                                        route_to(
                                            'admin.prelaunch.photos.approve',
                                            (int) $photoModal['id']
                                        ),
                                        'attr'
                                    ) ?>"
                            method="post"
                            class="mb-0"
                            data-submit-loader>

                            <?= csrf_field() ?>

                            <button
                                type="submit"
                                class="btn btn-success
                                    w-100
                                    d-inline-flex
                                    align-items-center
                                    justify-content-center
                                    gap-2">

                                <span
                                    data-submit-loader-label
                                    class="d-inline-flex
                                        align-items-center
                                        gap-2">

                                    <i
                                        class="ri-checkbox-circle-line"
                                        aria-hidden="true"></i>

                                    Approve Photo
                                </span>

                                <span
                                    class="d-none"
                                    data-submit-loader-spinner>

                                    <span
                                        class="spinner-border
                                            spinner-border-sm"
                                        aria-hidden="true">
                                    </span>
                                </span>
                            </button>
                        </form>

                        <form
                            action="<?= esc(
                                        route_to(
                                            'admin.prelaunch.photos.reject',
                                            (int) $photoModal['id']
                                        ),
                                        'attr'
                                    ) ?>"
                            method="post"
                            class="flex-grow-1 mb-0"
                            data-submit-loader>

                            <?= csrf_field() ?>

                            <div
                                class="input-group
                                    has-validation">

                                <input
                                    type="text"
                                    name="rejection_reason"
                                    class="form-control"
                                    minlength="5"
                                    maxlength="500"
                                    placeholder="Enter rejection reason"
                                    required>

                                <button
                                    type="submit"
                                    class="btn
                                        btn-outline-danger">

                                    <span
                                        data-submit-loader-label
                                        class="d-inline-flex
                                            align-items-center
                                            gap-1">

                                        <i
                                            class="ri-close-circle-line"
                                            aria-hidden="true"></i>

                                        Reject Photo
                                    </span>

                                    <span
                                        class="d-none"
                                        data-submit-loader-spinner>

                                        <span
                                            class="spinner-border
                                                spinner-border-sm"
                                            aria-hidden="true">
                                        </span>
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                <?php elseif (
                    $photoModal['status']
                    === PrelaunchPhotoModel
                    ::STATUS_REJECTED
                    && $photoModal['rejectionReason'] !== ''
                ): ?>
                    <div
                        class="modal-footer
                            justify-content-start">

                        <div
                            class="alert
                                alert-danger
                                py-2 mb-0 w-100">

                            <strong>
                                Rejection reason:
                            </strong>

                            <?= esc(
                                $photoModal['rejectionReason']
                            ) ?>
                        </div>
                    </div>
                <?php endif ?>
            </div>
        </div>
    </div>
<?php endforeach ?>

<?php if ($isDraft): ?>
    <div
        class="modal fade"
        id="reject-profile-modal"
        tabindex="-1"
        aria-labelledby="reject-profile-modal-title"
        aria-hidden="true">

        <div
            class="modal-dialog
                modal-dialog-centered">

            <div
                class="modal-content
                    border-0 shadow">

                <form
                    action="<?= esc(
                                route_to(
                                    'admin.prelaunch.profiles.reject',
                                    $profileId
                                ),
                                'attr'
                            ) ?>"
                    method="post"
                    data-submit-loader>

                    <?= csrf_field() ?>

                    <div class="modal-header">
                        <div>
                            <h2
                                id="reject-profile-modal-title"
                                class="modal-title
                                    fs-16 fw-semibold
                                    mb-1">
                                Reject Profile
                            </h2>

                            <p
                                class="text-muted
                                    fs-12 mb-0">
                                This action locks the
                                prelaunch profile.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close">
                        </button>
                    </div>

                    <div class="modal-body">
                        <label
                            for="profile_rejection_reason"
                            class="form-label">
                            Rejection reason
                        </label>

                        <textarea
                            id="profile_rejection_reason"
                            name="rejection_reason"
                            class="form-control"
                            rows="4"
                            minlength="5"
                            maxlength="1000"
                            placeholder="Enter profile rejection reason"
                            required></textarea>

                        <div
                            class="form-text
                                color-pink">
                            Rejected profiles cannot be
                            edited or reviewed again.
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
                            class="btn btn-danger
                                d-inline-flex
                                align-items-center
                                gap-2">

                            <span
                                data-submit-loader-label
                                class="d-inline-flex
                                    align-items-center
                                    gap-2">

                                <i
                                    class="ri-user-unfollow-line"
                                    aria-hidden="true"></i>

                                Reject Profile
                            </span>

                            <span
                                class="d-none"
                                data-submit-loader-spinner>

                                <span
                                    class="spinner-border
                                        spinner-border-sm"
                                    aria-hidden="true">
                                </span>

                                Rejecting...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif ?>

<?= $this->endSection() ?>