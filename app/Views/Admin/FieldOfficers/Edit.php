<?php

declare(strict_types=1);

/**
 * Admin SAK Volunteer edit screen.
 *
 * Controller supplied variables.
 *
 * @var array<string, mixed>|null $fieldOfficer
 * @var array<string, mixed>|null $formInput
 * @var array<string, string>|null $validationErrors
 * @var array<string, string>|null $formAlert
 * @var list<array<string, mixed>>|null $countries
 * @var list<array<string, mixed>>|null $states
 * @var list<array<string, mixed>>|null $cities
 * @var bool|null $canReplaceDocuments
 */

$fieldOfficer =
    is_array(
        $fieldOfficer
            ?? null
    )
    ? $fieldOfficer
    : [];

$formInput =
    is_array(
        $formInput
            ?? null
    )
    ? $formInput
    : $fieldOfficer;

$validationErrors =
    is_array(
        $validationErrors
            ?? null
    )
    ? $validationErrors
    : [];

$formAlert =
    is_array(
        $formAlert
            ?? null
    )
    ? $formAlert
    : null;

$countries =
    is_array(
        $countries
            ?? null
    )
    ? $countries
    : [];

$states =
    is_array(
        $states
            ?? null
    )
    ? $states
    : [];

$cities =
    is_array(
        $cities
            ?? null
    )
    ? $cities
    : [];

/*
 * UI visibility only.
 *
 * Actual authorization for replacement remains enforced by the
 * superAdmin route filter.
 */
$canReplaceDocuments =
    ($canReplaceDocuments ?? false)
    === true;

$fieldOfficerId =
    max(
        0,
        (int) (
            $fieldOfficer['id']
            ?? 0
        )
    );

$fieldOfficerName =
    trim(
        (string) (
            $fieldOfficer['full_name']
            ?? ''
        )
    );

$officerCode =
    trim(
        (string) (
            $fieldOfficer['officer_code']
            ?? ''
        )
    );

$mobileNumber =
    trim(
        (string) (
            $fieldOfficer['mobile_number']
            ?? ''
        )
    );

$accountStatus =
    strtoupper(
        trim(
            (string) (
                $fieldOfficer['account_status']
                ?? ''
            )
        )
    );

$isInactive =
    $accountStatus
    === \App\Models\FieldOfficerModel
    ::STATUS_INACTIVE;

$hasUpiId =
    trim(
        (string) (
            $fieldOfficer['upi_id']
            ?? ''
        )
    ) !== '';

$displayStatus =
    $accountStatus !== ''
    ? ucfirst(
        strtolower(
            $accountStatus
        )
    )
    : '';

$this->extend(
    'Admin/Layouts/Main'
);

$this->section(
    'content'
);
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
                            $fieldOfficerName
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
                                            $officerCode,
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
                                            $mobileNumber,
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
                                $fieldOfficerId
                            ),
                        ]
                    ) ?>
                </div>
            </div>

            <?php if ($canReplaceDocuments): ?>

                <div
                    class="card border border-danger
            border-opacity-25 mt-4">

                    <div class="card-header">

                        <h4 class="card-title mb-0">
                            Verification Documents
                        </h4>

                    </div>

                    <div class="card-body">

                        <div class="alert alert-warning">
                            Re-uploading changes the current document reference.
                            The previously stored physical file is retained.
                        </div>

                        <?php foreach (
                            [
                                'aadhaar' =>
                                'Aadhaar Card',

                                'pan' =>
                                'PAN Card',

                                'cancelled_cheque' =>
                                'Cancelled Cheque Copy',
                            ]
                            as $documentType => $documentLabel
                        ): ?>

                            <form
                                method="post"
                                enctype="multipart/form-data"
                                action="<?= route_to(
                                            'admin.field-officers.document.replace',
                                            $fieldOfficerId,
                                            $documentType
                                        ) ?>"
                                class="border rounded p-3 mb-3"
                                data-submit-loader>

                                <?= csrf_field() ?>

                                <div
                                    class="row g-2
                            align-items-end">

                                    <div class="col-12 col-md-8">

                                        <label
                                            class="form-label">

                                            <?= esc(
                                                $documentLabel
                                            ) ?>

                                        </label>

                                        <input
                                            type="file"
                                            name="document"
                                            class="form-control"
                                            accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                            required>

                                        <div class="form-text">
                                            PDF, JPG/JPEG or PNG · maximum 1 MB
                                        </div>

                                    </div>

                                    <div class="col-12 col-md-4">

                                        <button
                                            type="submit"
                                            class="btn btn-outline-primary w-100">

                                            <i
                                                class="ri-upload-2-line me-1"
                                                aria-hidden="true">
                                            </i>

                                            Re-upload

                                        </button>

                                    </div>

                                </div>

                            </form>

                        <?php endforeach; ?>

                    </div>

                </div>

            <?php endif; ?>
        </div>
    </div>
</div>

<?php $this->endSection(); ?>