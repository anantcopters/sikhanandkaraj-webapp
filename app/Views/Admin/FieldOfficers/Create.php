<?php

declare(strict_types=1);

/**
 * @var array<string, string> $formInput
 * @var array<string, string> $validationErrors
 * @var array<string, string>|null $formAlert
 * @var list<array<string, mixed>> $countries
 * @var list<array<string, mixed>> $states
 * @var list<array<string, mixed>> $cities
 */

$formInput = is_array(
    $formInput ?? null
)
    ? $formInput
    : [];

$validationErrors = is_array(
    $validationErrors ?? null
)
    ? $validationErrors
    : [];

$formAlert = is_array(
    $formAlert ?? null
)
    ? $formAlert
    : null;

$countries = is_array(
    $countries ?? null
)
    ? $countries
    : [];

$states = is_array(
    $states ?? null
)
    ? $states
    : [];

$cities = is_array(
    $cities ?? null
)
    ? $cities
    : [];

$this->extend('Admin/Layouts/Main');
$this->section('content');
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div
                class="page-title-box
                    d-sm-flex
                    align-items-center
                    justify-content-between">

                <div>
                    <h4 class="mb-sm-0">
                        Add Field Officer
                    </h4>

                    <p class="text-muted mb-0 mt-1">
                        A unique Field Officer code will be
                        generated automatically.
                    </p>
                </div>

                <div
                    class="page-title-right
                        mt-3 mt-sm-0">

                    <a
                        href="<?= route_to(
                                    'admin.field-officers.index'
                                ) ?>"
                        class="btn btn-soft-secondary">

                        <i
                            class="ri-arrow-left-line
                                align-middle me-1"
                            aria-hidden="true">
                        </i>

                        Back to Field Officers
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div
            class="col-md-10
                col-lg-8
                col-xl-7">

            <div
                class="card border-danger
                    border-opacity-25">

                <div class="card-header">
                    <h4 class="card-title mb-0">
                        Field Officer Details
                    </h4>
                </div>

                <div class="card-body">
                    <?= view(
                        'Components/Alerts/FormAlert',
                        [
                            'alert' =>
                            $formAlert,
                        ]
                    ) ?>

                    <div
                        class="alert alert-info
                            d-flex align-items-start
                            gap-2"
                        role="alert">

                        <i
                            class="ri-information-line
                                fs-20 flex-shrink-0"
                            aria-hidden="true">
                        </i>

                        <div>
                            A Field Officer created with a valid
                            UPI ID will be active immediately.
                            Without a UPI ID, the Field Officer
                            will be created inactive.
                        </div>
                    </div>

                    <?= view(
                        'Admin/FieldOfficers/_form',
                        [
                            'formInput' =>
                            $formInput,

                            'validationErrors' =>
                            $validationErrors,

                            'countries' =>
                            $countries,

                            'states' =>
                            $states,

                            'cities' =>
                            $cities,

                            'isEdit' =>
                            false,

                            'formAction' =>
                            route_to(
                                'admin.field-officers.store'
                            ),
                        ]
                    ) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $this->endSection(); ?>