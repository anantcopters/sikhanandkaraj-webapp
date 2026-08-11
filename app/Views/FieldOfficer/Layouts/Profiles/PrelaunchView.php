<?php

declare(strict_types=1);

$profile =
    is_array(
        $profile
            ?? null
    )
    ? $profile
    : [];

$photos =
    is_array(
        $photos
            ?? null
    )
    ? $photos
    : [];

$profileId = (int) (
    $profile['id']
    ?? 0
);

$location = implode(
    ', ',
    array_filter([
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
    ])
);

$this->extend(
    'FieldOfficer/Layouts/Main'
);

$this->section('content');
?>

<div class="container-fluid">

    <div class="mb-3">

        <a
            href="<?= route_to(
                        'field-officer.profiles.index'
                    ) ?>"
            class="d-inline-flex
            align-items-center
            gap-1
            text-primary
            fw-medium">

            <i
                class="ri-arrow-left-line">
            </i>

            Back to Profiles Submitted
        </a>

    </div>

    <div
        class="alert
        alert-info
        border">

        <i
            class="ri-eye-line me-1">
        </i>

        Read-only prelaunch profile.
        No actions are available.
    </div>

    <div
        class="card
        border
        border-danger
        border-opacity-25">

        <div class="card-body p-3 p-lg-4">

            <div class="row g-4">

                <div
                    class="col-12
                    col-lg-4">

                    <?php if ($photos !== []): ?>

                        <?php
                        $firstPhoto =
                            $photos[0];

                        $photoId = (int) (
                            $firstPhoto['id']
                            ?? 0
                        );
                        ?>

                        <img
                            src="<?= route_to(
                                        'field-officer.profiles.prelaunch.photo',
                                        $profileId,
                                        $photoId
                                    ) ?>"
                            alt="Profile photograph"
                            class="img-fluid rounded-3">

                    <?php else: ?>

                        <div
                            class="bg-light
                            rounded-3
                            p-5
                            text-center
                            text-muted">

                            <i
                                class="ri-user-line
                                fs-32">
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
                            (string) (
                                $profile['full_name'] ?? ''
                            )
                        ) ?>
                    </h2>

                    <p
                        class="text-muted
                        mb-4">

                        <?= esc(
                            (string) (
                                $profile['profile_reference'] ?? ''
                            )
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

                        <?php
                        $details = [
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

                            'Highest Education' =>
                            $profile['education_name']
                                ?? '',

                            'Employed In' =>
                            $profile['employed_in']
                                ?? '',

                            'Occupation' =>
                            $profile['occupation_name']
                                ?? '',

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
                        ?>

                        <?php foreach (
                            $details
                            as $label => $value
                        ): ?>

                            <?php
                            $value = trim(
                                (string) $value
                            );

                            if ($value === '') {
                                continue;
                            }
                            ?>

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