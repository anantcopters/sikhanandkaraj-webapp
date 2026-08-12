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
 * Real document replacement authorization remains enforced
 * by the superAdmin route filter.
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

    <!-- =========================================================
         Page heading
         ========================================================= -->
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
                        Update SAK Volunteer details and
                        verification information.
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

    <!-- =========================================================
         Main SAK Volunteer edit card
         ========================================================= -->
    <div class="row justify-content-center">

        <div
            class="col-md-10
                col-lg-8
                col-xl-7">

            <div
                class="card
                    border
                    border-danger
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

                    <!-- =================================================
                         Read-only SAK Volunteer identity
                         ================================================= -->
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
                                            $displayStatus,
                                            'attr'
                                        ) ?>"
                                readonly>

                        </div>

                    </div>

                    <?php if (
                        $canReplaceDocuments
                    ): ?>

                        <!-- =========================================================
         Super Admin verification-document replacement
         ========================================================= -->

                        <div class="border rounded p-3 mb-4">

                            <div class="mb-3">

                                <h5 class="fs-15 mb-1">
                                    Verification Documents
                                </h5>

                                <p class="text-muted fs-13 mb-0">
                                    Upload a new document only when replacement is required.
                                    The previously stored physical file will be retained.
                                </p>

                            </div>

                            <div class="row g-3">

                                <!-- Aadhaar Card -->
                                <div class="col-12 col-md-4">

                                    <form
                                        method="post"
                                        enctype="multipart/form-data"
                                        action="<?= route_to(
                                                    'admin.field-officers.document.replace',
                                                    $fieldOfficerId,
                                                    'aadhaar'
                                                ) ?>"
                                        data-submit-loader>

                                        <?= csrf_field() ?>

                                        <label
                                            for="fieldOfficerReplaceAadhaar"
                                            class="form-label">

                                            Aadhaar Card

                                        </label>

                                        <input
                                            type="file"
                                            id="fieldOfficerReplaceAadhaar"
                                            name="document"
                                            class="form-control"
                                            accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                            required>

                                        <div class="form-text color-pink">
                                            PDF, JPG/JPEG or PNG · maximum 1 MB
                                        </div>

                                        <button
                                            type="submit"
                                            class="btn
                            btn-outline-primary
                            w-100
                            mt-2">

                                            <span class="registration-submit__idle">

                                                <i
                                                    class="ri-upload-2-line me-1"
                                                    aria-hidden="true">
                                                </i>

                                                Re-upload

                                            </span>

                                            <span
                                                class="registration-submit__loading
                                d-none">

                                                <span
                                                    class="spinner-border
                                    spinner-border-sm"
                                                    aria-hidden="true">
                                                </span>

                                                Uploading...

                                            </span>

                                        </button>

                                    </form>

                                </div>

                                <!-- PAN Card -->
                                <div class="col-12 col-md-4">

                                    <form
                                        method="post"
                                        enctype="multipart/form-data"
                                        action="<?= route_to(
                                                    'admin.field-officers.document.replace',
                                                    $fieldOfficerId,
                                                    'pan'
                                                ) ?>"
                                        data-submit-loader>

                                        <?= csrf_field() ?>

                                        <label
                                            for="fieldOfficerReplacePan"
                                            class="form-label">

                                            PAN Card

                                        </label>

                                        <input
                                            type="file"
                                            id="fieldOfficerReplacePan"
                                            name="document"
                                            class="form-control"
                                            accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                            required>

                                        <div class="form-text color-pink">
                                            PDF, JPG/JPEG or PNG · maximum 1 MB
                                        </div>

                                        <button
                                            type="submit"
                                            class="btn
                            btn-outline-primary
                            w-100
                            mt-2">

                                            <span class="registration-submit__idle">

                                                <i
                                                    class="ri-upload-2-line me-1"
                                                    aria-hidden="true">
                                                </i>

                                                Re-upload

                                            </span>

                                            <span
                                                class="registration-submit__loading
                                d-none">

                                                <span
                                                    class="spinner-border
                                    spinner-border-sm"
                                                    aria-hidden="true">
                                                </span>

                                                Uploading...

                                            </span>

                                        </button>

                                    </form>

                                </div>

                                <!-- Cancelled Cheque -->
                                <div class="col-12 col-md-4">

                                    <form
                                        method="post"
                                        enctype="multipart/form-data"
                                        action="<?= route_to(
                                                    'admin.field-officers.document.replace',
                                                    $fieldOfficerId,
                                                    'cancelled_cheque'
                                                ) ?>"
                                        data-submit-loader>

                                        <?= csrf_field() ?>

                                        <label
                                            for="fieldOfficerReplaceCheque"
                                            class="form-label">

                                            Cancelled Cheque Copy

                                        </label>

                                        <input
                                            type="file"
                                            id="fieldOfficerReplaceCheque"
                                            name="document"
                                            class="form-control"
                                            accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                            required>

                                        <div class="form-text color-pink">
                                            PDF, JPG/JPEG or PNG · maximum 1 MB
                                        </div>

                                        <button
                                            type="submit"
                                            class="btn
                            btn-outline-primary
                            w-100
                            mt-2">

                                            <span class="registration-submit__idle">

                                                <i
                                                    class="ri-upload-2-line me-1"
                                                    aria-hidden="true">
                                                </i>

                                                Re-upload

                                            </span>

                                            <span
                                                class="registration-submit__loading
                                d-none">

                                                <span
                                                    class="spinner-border
                                    spinner-border-sm"
                                                    aria-hidden="true">
                                                </span>

                                                Uploading...

                                            </span>

                                        </button>

                                    </form>

                                </div>

                            </div>

                        </div>

                    <?php endif; ?>

                    <!-- =================================================
                         Existing editable SAK Volunteer form
                         ================================================= -->
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

        </div>

    </div>

</div>

<?php $this->endSection(); ?>