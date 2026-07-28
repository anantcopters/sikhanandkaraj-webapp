<?php

declare(strict_types=1);

$errors = session('validationErrors');
$validationErrors = is_array($errors)
    ? $errors
    : [];

$input = session('fieldOfficerFormInput');

$formInput = is_array($input)
    ? array_merge($fieldOfficer, $input)
    : $fieldOfficer;

$alert = session('formAlert');
$formAlert = is_array($alert)
    ? $alert
    : null;

$this->extend('Admin/Layouts/Main');
$this->section('content');
?>
<?php
$isInactive =
    (string) $fieldOfficer['account_status']
    === \App\Models\FieldOfficerModel::STATUS_INACTIVE;

$hasUpiId =
    trim(
        (string) (
            $fieldOfficer['upi_id'] ?? ''
        )
    ) !== '';
?>

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
            <h6 class="alert-heading mb-1">
                UPI ID required for activation
            </h6>

            <p class="mb-0">
                Add a valid UPI ID before attempting
                to activate this Field Officer.
            </p>
        </div>
    </div>
<?php endif; ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div
                class="page-title-box
                    d-sm-flex align-items-center
                    justify-content-between">

                <div>
                    <h4 class="mb-sm-0">
                        Edit Field Officer
                    </h4>

                    <p class="text-muted mb-0 mt-1">
                        Only location, address and UPI ID
                        can be changed.
                    </p>
                </div>

                <div class="page-title-right mt-3 mt-sm-0">
                    <a
                        href="<?= route_to(
                                    'admin.field-officers.index'
                                ) ?>"
                        class="btn btn-soft-secondary">

                        <i
                            class="ri-arrow-left-line
                                align-middle me-1">
                        </i>

                        Back to Field Officers
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8 col-xl-7">
            <div
                class="card border-danger
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
                            'alert' => $formAlert,
                        ]
                    ) ?>

                    <div class="row g-3 mb-4">
                        <div class="row g-3 mb-4">
                            <div class="col-12 col-md-4">
                                <label class="form-label">
                                    Field Officer Code
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    value="<?= esc(
                                                (string) $fieldOfficer['officer_code'],
                                                'attr'
                                            ) ?>"
                                    readonly>
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label">
                                    Mobile Number
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    value="<?= esc(
                                                (string) $fieldOfficer['mobile_number'],
                                                'attr'
                                            ) ?>"
                                    readonly>
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label">
                                    Status
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
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
                    </div>

                    <?= view(
                        'Admin/FieldOfficers/_Form',
                        [
                            'formInput' => $formInput,
                            'validationErrors' =>
                            $validationErrors,
                            'countries' =>
                            $countries ?? [],
                            'states' => $states ?? [],
                            'cities' => $cities ?? [],
                            'isEdit' => true,
                            'formAction' => route_to(
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