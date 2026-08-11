<?php

declare(strict_types=1);

$formAlert =
    is_array(
        $formAlert
            ?? null
    )
    ? $formAlert
    : null;

$submittedProfileCount =
    max(
        0,
        (int) (
            $submittedProfileCount
            ?? 0
        )
    );

$this->extend(
    'FieldOfficer/Layouts/Main'
);

$this->section('content');
?>

<div class="container-fluid">

    <div class="row">

        <div class="col-12">

            <div
                class="page-title-box
                d-sm-flex
                align-items-sm-center
                justify-content-between">

                <div>
                    <h4 class="mb-sm-0">
                        Field Officer Dashboard
                    </h4>

                    <p
                        class="text-muted mb-0">

                        Welcome,
                        <?= esc(
                            (string) session(
                                'fo_field_officer_name'
                            )
                        ) ?>
                    </p>
                </div>
            </div>

        </div>
    </div>

    <?= view(
        'Components/Alerts/FormAlert',
        [
            'alert' =>
            $formAlert,
        ]
    ) ?>

    <div class="row">

        <div
            class="col-12
            col-md-6
            col-xl-4">

            <a
                href="<?= route_to(
                            'field-officer.profiles.index'
                        ) ?>"
                class="text-decoration-none">

                <div
                    class="card
                    border
                    border-danger
                    border-opacity-25">

                    <div class="card-body">

                        <div
                            class="d-flex
                            align-items-center
                            justify-content-between">

                            <div>

                                <p
                                    class="text-muted
                                    mb-1">

                                    Profiles Submitted
                                </p>

                                <h3 class="mb-0">

                                    <?= esc(
                                        (string)
                                        $submittedProfileCount
                                    ) ?>
                                </h3>

                            </div>

                            <div
                                class="avatar-sm">

                                <div
                                    class="avatar-title
                                    rounded-circle
                                    bg-primary-subtle
                                    text-primary
                                    fs-22">

                                    <i
                                        class="ri-profile-line">
                                    </i>

                                </div>
                            </div>

                        </div>

                    </div>
                </div>
            </a>

        </div>
    </div>

</div>

<?php $this->endSection(); ?>