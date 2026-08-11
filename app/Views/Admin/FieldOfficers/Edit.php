<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $fieldOfficer
 * @var array<string, mixed> $formInput
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
    : $fieldOfficer;

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

$isInactive =
    (string) (
        $fieldOfficer['account_status'] ?? ''
    )
    === \App\Models\FieldOfficerModel::STATUS_INACTIVE;

$hasUpiId =
    trim(
        (string) (
            $fieldOfficer['upi_id'] ?? ''
        )
    ) !== '';

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
                        Edit SAK Volunteer
                    </h4>

                    <p class="text-muted mb-0 mt-1">
                        Only location, address and UPI ID
                        can be changed.
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

                        Back to SAK Volunteers
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
                class="card border border-danger
                    border-opacity-25">

                <div class="card-header">
                    <h4 class="card-title mb-0">
                        <?= esc(
                            (string) $fieldOfficer['full_name']
                        ) ?>
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

                    <?php if (
                        $isInactive
                        && !$hasUpiId
                    ): ?>
                        <div
                            class="alert alert-warning
                                d-flex align-items-start
                                gap-2"
                            role="alert">

                            <i
                                class="ri-information-line
                                    fs-20 flex-shrink-0"
                                aria-hidden="true">
                            </i>

                            <div>
                                <h6
                                    class="alert-heading
                                        mb-1">

                                    UPI ID required for activation
                                </h6>

                                <p class="mb-0">
                                    Add a valid UPI ID before
                                    activating this SAK Volunteer.
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-4">
                            <label
                                for="fieldOfficerCode"
                                class="form-label">

                                SAK Volunteer Code
                            </label>

                            <input
                                type="text"
                                id="fieldOfficerCode"
                                class="form-control bg-light"
                                value="<?= esc(
                                            (string) $fieldOfficer['officer_code'],
                                            'attr'
                                        ) ?>"
                                readonly>
                        </div>

                        <div class="col-12 col-md-4">
                            <label
                                for="fieldOfficerReadonlyMobile"
                                class="form-label">

                                Mobile Number
                            </label>

                            <input
                                type="text"
                                id="fieldOfficerReadonlyMobile"
                                class="form-control bg-light"
                                value="<?= esc(
                                            (string) $fieldOfficer['mobile_number'],
                                            'attr'
                                        ) ?>"
                                readonly>
                        </div>

                        <div class="col-12 col-md-4">
                            <label
                                for="fieldOfficerStatus"
                                class="form-label">

                                Status
                            </label>

                            <input
                                type="text"
                                id="fieldOfficerStatus"
                                class="form-control bg-light"
                                value="<?= esc(
                                            ucfirst(
                                                strtolower(
                                                    (string) $fieldOfficer['account_status']
                                                )
                                            ),
                                            'attr'
                                        ) ?>"
                                readonly>
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
                            true,

                            'formAction' =>
                            route_to(
                                'admin.field-officers.update',
                                (int) $fieldOfficer['id']
                            ),
                        ]
                    ) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $this->endSection(); ?>