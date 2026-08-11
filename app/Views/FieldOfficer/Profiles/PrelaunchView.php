<?php

declare(strict_types=1);

$pageTitle = trim(
    (string) (
        $pageTitle
        ?? 'Prelaunch Profile'
    )
);

$profile =
    isset($profile)
    && is_array($profile)
    ? $profile
    : [];

$photos =
    isset($photos)
    && is_array($photos)
    ? $photos
    : [];

$profileId = max(
    0,
    (int) (
        $profile['id']
        ?? 0
    )
);

$fullName = trim(
    (string) (
        $profile['full_name']
        ?? ''
    )
);

if ($fullName === '') {
    $fullName =
        'Member Profile';
}

$profileReference = trim(
    (string) (
        $profile['profile_reference']
        ?? ''
    )
);

$cityName = trim(
    (string) (
        $profile['city_name']
        ?? ''
    )
);

$stateName = trim(
    (string) (
        $profile['state_name']
        ?? ''
    )
);

$countryName = trim(
    (string) (
        $profile['country_name']
        ?? ''
    )
);

$location = implode(
    ', ',
    array_filter(
        [
            $cityName,
            $stateName,
            $countryName,
        ],
        static fn(
            string $value
        ): bool => $value !== ''
    )
);

$backUrl =
    route_to(
        'field-officer.profiles.index'
    );

$primaryPhotoUrl = '';

if ($photos !== []) {
    $firstPhoto =
        isset($photos[0])
        && is_array($photos[0])
        ? $photos[0]
        : [];

    $photoId = max(
        0,
        (int) (
            $firstPhoto['id']
            ?? 0
        )
    );

    if (
        $profileId > 0
        && $photoId > 0
    ) {
        $primaryPhotoUrl =
            route_to(
                'field-officer.profiles.prelaunch.photo',
                $profileId,
                $photoId
            );
    }
}

$profileDetails = [
    'Gender' =>
    trim(
        (string) (
            $profile['gender']
            ?? ''
        )
    ),

    'Date of Birth' =>
    trim(
        (string) (
            $profile['date_of_birth']
            ?? ''
        )
    ),

    'Marital Status' =>
    trim(
        (string) (
            $profile['marital_status_name']
            ?? ''
        )
    ),

    'Height' =>
    trim(
        (string) (
            $profile['height_name']
            ?? ''
        )
    ),

    'Highest Education' =>
    trim(
        (string) (
            $profile['education_name']
            ?? ''
        )
    ),

    'Employed In' =>
    trim(
        (string) (
            $profile['employed_in']
            ?? ''
        )
    ),

    'Occupation' =>
    trim(
        (string) (
            $profile['occupation_name']
            ?? ''
        )
    ),

    "Father's Name" =>
    trim(
        (string) (
            $profile['father_name']
            ?? ''
        )
    ),

    "Mother's Name" =>
    trim(
        (string) (
            $profile['mother_name']
            ?? ''
        )
    ),

    'Community' =>
    trim(
        (string) (
            $profile['community_name']
            ?? ''
        )
    ),

    'Gotra' =>
    trim(
        (string) (
            $profile['gotra']
            ?? ''
        )
    ),

    'Nearest Gurudwara' =>
    trim(
        (string) (
            $profile['nearest_gurudwara']
            ?? ''
        )
    ),
];

$profileDetails = array_filter(
    $profileDetails,
    static fn(
        string $value
    ): bool => $value !== ''
);

$this->extend(
    'FieldOfficer/Layouts/Main'
);

$this->section('content');
?>

<div class="container-fluid">

    <div class="mb-3">

        <a
            href="<?= esc(
                        $backUrl,
                        'attr'
                    ) ?>"
            class="d-inline-flex
            align-items-center
            gap-1
            text-primary
            fw-medium">

            <i
                class="ri-arrow-left-line"
                aria-hidden="true">
            </i>

            Back to Profiles Submitted

        </a>

    </div>

    <div
        class="alert
        alert-info
        border">

        <i
            class="ri-eye-line
            me-1"
            aria-hidden="true">
        </i>

        Read-only prelaunch profile.
        No actions are available.

    </div>

    <div
        class="card
        border
        border-danger
        border-opacity-25">

        <div
            class="card-body
            p-3
            p-lg-4">

            <div class="row g-4">

                <div
                    class="col-12
                    col-lg-4">

                    <?php if (
                        $primaryPhotoUrl !== ''
                    ): ?>

                        <img
                            src="<?= esc(
                                        $primaryPhotoUrl,
                                        'attr'
                                    ) ?>"
                            alt="Profile photograph"
                            class="img-fluid
                            rounded-3">

                    <?php else: ?>

                        <div
                            class="bg-light
                            rounded-3
                            p-5
                            text-center
                            text-muted">

                            <i
                                class="ri-user-line
                                fs-32"
                                aria-hidden="true">
                            </i>

                            <div class="mt-2">
                                No photograph available
                            </div>

                        </div>

                    <?php endif; ?>

                </div>

                <div
                    class="col-12
                    col-lg-8">

                    <h2
                        class="fs-24
                        fw-bold
                        mb-1">

                        <?= esc(
                            $fullName
                        ) ?>

                    </h2>

                    <p
                        class="text-muted
                        mb-4">

                        <?= esc(
                            $profileReference
                        ) ?>

                        <?php if (
                            $location !== ''
                        ): ?>

                            • <?= esc(
                                    $location
                                ) ?>

                        <?php endif; ?>

                    </p>

                    <div class="row g-3">

                        <?php foreach (
                            $profileDetails
                            as $label => $value
                        ): ?>

                            <div
                                class="col-12
                                col-md-6">

                                <div
                                    class="border
                                    rounded
                                    p-3
                                    h-100">

                                    <div
                                        class="small
                                        text-muted
                                        mb-1">

                                        <?= esc(
                                            $label
                                        ) ?>

                                    </div>

                                    <div
                                        class="fw-medium">

                                        <?= esc(
                                            $value
                                        ) ?>

                                    </div>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                </div>

            </div>

        </div>
    </div>

</div>

<?php $this->endSection(); ?>