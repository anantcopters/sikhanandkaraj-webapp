<?php

declare(strict_types=1);

use App\Models\FieldOfficerModel;

/**
 * Admin SAK Volunteer listing.
 *
 * Controller supplied variables.
 *
 * @var list<array<string, mixed>>|null $fieldOfficers
 * @var array<string, mixed>|null $formAlert
 */

$resolvedFieldOfficers =
    is_array(
        $fieldOfficers
            ?? null
    )
    ? $fieldOfficers
    : [];

$resolvedFormAlert =
    is_array(
        $formAlert
            ?? null
    )
    ? $formAlert
    : null;

$createUrl =
    route_to(
        'admin.field-officers.create'
    );

$rows = [];

foreach (
    $resolvedFieldOfficers
    as $fieldOfficer
) {
    if (!is_array($fieldOfficer)) {
        continue;
    }

    $fieldOfficerId = max(
        0,
        (int) (
            $fieldOfficer['id']
            ?? 0
        )
    );

    if ($fieldOfficerId <= 0) {
        continue;
    }

    $fullName = trim(
        (string) (
            $fieldOfficer['full_name']
            ?? ''
        )
    );

    $officerCode = trim(
        (string) (
            $fieldOfficer['officer_code']
            ?? ''
        )
    );

    $mobileNumber = trim(
        (string) (
            $fieldOfficer['mobile_number']
            ?? ''
        )
    );

    $location = implode(
        ', ',
        array_filter(
            [
                trim(
                    (string) (
                        $fieldOfficer['city_name']
                        ?? ''
                    )
                ),
                trim(
                    (string) (
                        $fieldOfficer['state_name']
                        ?? ''
                    )
                ),
                trim(
                    (string) (
                        $fieldOfficer['country_name']
                        ?? ''
                    )
                ),
            ],
            static fn(
                string $value
            ): bool => $value !== ''
        )
    );

    if ($location === '') {
        $location = '—';
    }

    $accountStatus = strtoupper(
        trim(
            (string) (
                $fieldOfficer['account_status']
                ?? ''
            )
        )
    );

    $registrationSource = strtoupper(
        trim(
            (string) (
                $fieldOfficer['registration_source']
                ?? ''
            )
        )
    );

    $reviewStatus = strtoupper(
        trim(
            (string) (
                $fieldOfficer['review_status']
                ?? ''
            )
        )
    );

    $isActive =
        $accountStatus
        === FieldOfficerModel
        ::STATUS_ACTIVE;

    $hasUpiId =
        trim(
            (string) (
                $fieldOfficer['upi_id']
                ?? ''
            )
        ) !== '';

    $isSelfRegistration =
        $registrationSource
        === FieldOfficerModel
        ::REGISTRATION_SOURCE_SELF;

    $isPendingRegistration =
        $isSelfRegistration
        && $reviewStatus
        === FieldOfficerModel
        ::REVIEW_STATUS_PENDING;

    $isRejectedRegistration =
        $isSelfRegistration
        && $reviewStatus
        === FieldOfficerModel
        ::REVIEW_STATUS_REJECTED;

    $canUseNormalStatusActions =
        !$isPendingRegistration
        && !$isRejectedRegistration;

    if ($isPendingRegistration) {
        $statusLabel =
            'Pending Review';

        $statusClass =
            'badge bg-warning-subtle text-dark p-2';
    } elseif ($isRejectedRegistration) {
        $statusLabel =
            'Rejected';

        $statusClass =
            'badge bg-danger-subtle text-danger p-2';
    } elseif ($isActive) {
        $statusLabel =
            'Active';

        $statusClass =
            'badge bg-success-subtle text-black p-2';
    } else {
        $statusLabel =
            'Inactive';

        $statusClass =
            'badge bg-secondary-subtle text-black p-2';
    }

    $rows[] = [
        'id' =>
        $fieldOfficerId,

        'fullName' =>
        $fullName,

        'officerCode' =>
        $officerCode,

        'mobileNumber' =>
        $mobileNumber,

        'location' =>
        $location,

        'statusLabel' =>
        $statusLabel,

        'statusClass' =>
        $statusClass,

        'isActive' =>
        $isActive,

        'hasUpiId' =>
        $hasUpiId,

        'isPendingRegistration' =>
        $isPendingRegistration,

        'isRejectedRegistration' =>
        $isRejectedRegistration,

        'canUseNormalStatusActions' =>
        $canUseNormalStatusActions,

        'editUrl' =>
        route_to(
            'admin.field-officers.edit',
            $fieldOfficerId
        ),

        /*
        * Admin profile listing uses the same SAK Volunteer profile
        * association query as the Volunteer portal.
        */
        'profilesUrl' =>
        route_to(
            'admin.field-officers.profiles',
            $fieldOfficerId
        ),

        'approveUrl' =>
        route_to(
            'admin.field-officers.approve-registration',
            $fieldOfficerId
        ),

        'rejectUrl' =>
        route_to(
            'admin.field-officers.reject-registration',
            $fieldOfficerId
        ),

        'activateUrl' =>
        route_to(
            'admin.field-officers.activate',
            $fieldOfficerId
        ),

        'deactivateUrl' =>
        route_to(
            'admin.field-officers.deactivate',
            $fieldOfficerId
        ),

        'activateMessage' =>
        'Do you want to make '
            . $fullName
            . ' active?',

        'deactivateMessage' =>
        'Do you want to make '
            . $fullName
            . ' inactive?',

        'aadhaarDocumentUrl' =>
        trim(
            (string) (
                $fieldOfficer['aadhaar_document']
                ?? ''
            )
        ) !== ''
            ? route_to(
                'admin.field-officers.document',
                $fieldOfficerId,
                'aadhaar'
            )
            : '',

        'panDocumentUrl' =>
        trim(
            (string) (
                $fieldOfficer['pan_document']
                ?? ''
            )
        ) !== ''
            ? route_to(
                'admin.field-officers.document',
                $fieldOfficerId,
                'pan'
            )
            : '',

        'cancelledChequeDocumentUrl' =>
        trim(
            (string) (
                $fieldOfficer['cancelled_cheque_document']
                ?? ''
            )
        ) !== ''
            ? route_to(
                'admin.field-officers.document',
                $fieldOfficerId,
                'cancelled_cheque'
            )
            : '',
    ];
}

$this->extend(
    'Admin/Layouts/Main'
);

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
                        SAK Volunteers
                    </h4>

                    <p
                        class="text-muted
                        mb-0
                        mt-1">

                        Manage SAK Volunteers and their
                        assigned locations.

                    </p>

                </div>

                <div class="page-title-right">

                    <a
                        href="<?= esc(
                                    $createUrl,
                                    'attr'
                                ) ?>"
                        class="btn
                        btn-primary">

                        <i
                            class="ri-user-add-line
                            align-middle
                            me-1"
                            aria-hidden="true">
                        </i>

                        Add SAK Volunteer

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

    <div
        class="card
        border
        border-danger
        border-opacity-25">

        <div class="card-body p-0">

            <div class="table-responsive">

                <table
                    class="table
                    table-hover
                    table-nowrap
                    align-middle
                    mb-0">

                    <thead class="bg-info-subtle">

                        <tr>

                            <th scope="col">
                                Name
                            </th>

                            <th scope="col">
                                Code
                            </th>

                            <th scope="col">
                                Mobile
                            </th>

                            <th scope="col">
                                Location
                            </th>

                            <th scope="col">
                                Status
                            </th>

                            <th scope="col">
                                Documents
                            </th>


                            <th
                                scope="col"
                                class="text-end">

                                Action

                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php if ($rows === []): ?>

                            <tr>

                                <td
                                    colspan="7"
                                    class="text-center
                                    text-muted
                                    py-4">

                                    No SAK Volunteers
                                    have been added.

                                </td>

                            </tr>

                        <?php endif; ?>

                        <?php foreach (
                            $rows
                            as $row
                        ): ?>

                            <tr>

                                <td>

                                    <span class="fw-semibold">

                                        <?= esc(
                                            $row['fullName']
                                        ) ?>

                                    </span>

                                </td>

                                <td>

                                    <span
                                        class="badge
                                        bg-primary-subtle
                                        text-body
                                        p-2">

                                        <?= esc(
                                            $row['officerCode']
                                        ) ?>

                                    </span>

                                </td>

                                <td>

                                    <?= esc(
                                        $row['mobileNumber']
                                    ) ?>

                                </td>

                                <td>

                                    <?= esc(
                                        $row['location']
                                    ) ?>

                                </td>

                                <td>

                                    <span
                                        class="<?= esc(
                                                    $row['statusClass'],
                                                    'attr'
                                                ) ?>">

                                        <?= esc(
                                            $row['statusLabel']
                                        ) ?>

                                    </span>

                                </td>

                                <td>

                                    <div
                                        class="d-flex
        flex-column
        align-items-start
        gap-1">

                                        <?php if (
                                            $row['aadhaarDocumentUrl'] !== ''
                                        ): ?>

                                            <a
                                                href="<?= esc(
                                                            $row['aadhaarDocumentUrl'],
                                                            'attr'
                                                        ) ?>"
                                                class="text-decoration-none"
                                                title="Download Aadhaar Card">

                                                <i
                                                    class="ri-download-2-line me-1"
                                                    aria-hidden="true">
                                                </i>

                                                Aadhaar

                                            </a>

                                        <?php else: ?>

                                            <span class="text-muted">
                                                Aadhaar —
                                            </span>

                                        <?php endif; ?>

                                        <?php if (
                                            $row['panDocumentUrl'] !== ''
                                        ): ?>

                                            <a
                                                href="<?= esc(
                                                            $row['panDocumentUrl'],
                                                            'attr'
                                                        ) ?>"
                                                class="text-decoration-none"
                                                title="Download PAN Card">

                                                <i
                                                    class="ri-download-2-line me-1"
                                                    aria-hidden="true">
                                                </i>

                                                PAN

                                            </a>

                                        <?php else: ?>

                                            <span class="text-muted">
                                                PAN —
                                            </span>

                                        <?php endif; ?>

                                        <?php if (
                                            $row['cancelledChequeDocumentUrl'] !== ''
                                        ): ?>

                                            <a
                                                href="<?= esc(
                                                            $row['cancelledChequeDocumentUrl'],
                                                            'attr'
                                                        ) ?>"
                                                class="text-decoration-none"
                                                title="Download Cancelled Cheque">

                                                <i
                                                    class="ri-download-2-line me-1"
                                                    aria-hidden="true">
                                                </i>

                                                Cheque

                                            </a>

                                        <?php else: ?>

                                            <span class="text-muted">
                                                Cheque —
                                            </span>

                                        <?php endif; ?>

                                    </div>

                                </td>

                                <td class="text-end">

                                    <div
                                        class="d-inline-flex
                                        align-items-center
                                        gap-1">

                                        <?php if (
                                            $row['isPendingRegistration']
                                        ): ?>

                                            <form
                                                action="<?= esc(
                                                            $row['approveUrl'],
                                                            'attr'
                                                        ) ?>"
                                                method="post"
                                                class="d-inline"
                                                data-confirm-form
                                                data-confirm-title="Approve SAK Volunteer?"
                                                data-confirm-message="Approve this SAK Volunteer registration?"
                                                data-confirm-button-text="Approve"
                                                data-confirm-loading-text="Approving..."
                                                data-confirm-button-class="btn-success"
                                                data-confirm-icon="ri-checkbox-circle-line">

                                                <?= csrf_field() ?>

                                                <button
                                                    type="submit"
                                                    class="btn
    btn-soft-success
    btn-sm"
                                                    data-bs-toggle="tooltip"
                                                    data-bs-placement="top"
                                                    title="Approve SAK Volunteer"
                                                    aria-label="Approve SAK Volunteer">

                                                    <i
                                                        class="ri-checkbox-circle-line"
                                                        aria-hidden="true">
                                                    </i>

                                                </button>

                                            </form>

                                            <form
                                                action="<?= esc(
                                                            $row['rejectUrl'],
                                                            'attr'
                                                        ) ?>"
                                                method="post"
                                                class="d-inline"
                                                data-confirm-form
                                                data-confirm-title="Reject SAK Volunteer?"
                                                data-confirm-message="Reject this SAK Volunteer registration?"
                                                data-confirm-button-text="Reject"
                                                data-confirm-loading-text="Rejecting..."
                                                data-confirm-button-class="btn-danger"
                                                data-confirm-icon="ri-close-circle-line">

                                                <?= csrf_field() ?>

                                                <button
                                                    type="submit"
                                                    class="btn
    btn-soft-danger
    btn-sm"
                                                    data-bs-toggle="tooltip"
                                                    data-bs-placement="top"
                                                    title="Reject SAK Volunteer"
                                                    aria-label="Reject SAK Volunteer">

                                                    <i
                                                        class="ri-close-circle-line"
                                                        aria-hidden="true">
                                                    </i>

                                                </button>
                                            </form>

                                        <?php endif; ?>
                                        <!-- View profiles connected with this SAK Volunteer. -->
                                        <a
                                            href="<?= esc(
                                                        $row['profilesUrl'],
                                                        'attr'
                                                    ) ?>"
                                            class="btn
    btn-soft-info
    btn-sm"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            title="View connected profiles"
                                            aria-label="View profiles connected with this SAK Volunteer">

                                            <i
                                                class="ri-group-line"
                                                aria-hidden="true">
                                            </i>

                                        </a>
                                        <!-- Edit -->
                                        <a
                                            href="<?= esc(
                                                        $row['editUrl'],
                                                        'attr'
                                                    ) ?>"
                                            class="btn
    btn-soft-primary
    btn-sm"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            title="Edit SAK Volunteer"
                                            aria-label="Edit SAK Volunteer">

                                            <i
                                                class="ri-edit-line"
                                                aria-hidden="true">
                                            </i>

                                        </a>

                                        <?php if (
                                            $row['canUseNormalStatusActions']
                                        ): ?>

                                            <?php if (
                                                $row['isActive']
                                            ): ?>

                                                <form
                                                    action="<?= esc(
                                                                $row['deactivateUrl'],
                                                                'attr'
                                                            ) ?>"
                                                    method="post"
                                                    class="d-inline"
                                                    data-confirm-form
                                                    data-confirm-title="Deactivate SAK Volunteer?"
                                                    data-confirm-message="<?= esc(
                                                                                $row['deactivateMessage'],
                                                                                'attr'
                                                                            ) ?>"
                                                    data-confirm-button-text="Make Inactive"
                                                    data-confirm-loading-text="Deactivating..."
                                                    data-confirm-button-class="btn-warning"
                                                    data-confirm-icon="ri-user-unfollow-line">

                                                    <?= csrf_field() ?>

                                                    <!-- Deactivate -->
                                                    <button
                                                        type="submit"
                                                        class="btn
    btn-soft-warning
    btn-sm"
                                                        data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="Make inactive"
                                                        aria-label="Make SAK Volunteer inactive">

                                                        <i
                                                            class="ri-user-unfollow-line"
                                                            aria-hidden="true">
                                                        </i>

                                                    </button>

                                                </form>

                                            <?php elseif (
                                                $row['hasUpiId']
                                            ): ?>

                                                <form
                                                    action="<?= esc(
                                                                $row['activateUrl'],
                                                                'attr'
                                                            ) ?>"
                                                    method="post"
                                                    class="d-inline"
                                                    data-confirm-form
                                                    data-confirm-title="Activate SAK Volunteer?"
                                                    data-confirm-message="<?= esc(
                                                                                $row['activateMessage'],
                                                                                'attr'
                                                                            ) ?>"
                                                    data-confirm-button-text="Make Active"
                                                    data-confirm-loading-text="Activating..."
                                                    data-confirm-button-class="btn-success"
                                                    data-confirm-icon="ri-user-follow-line">

                                                    <?= csrf_field() ?>

                                                    <!-- Activate -->
                                                    <button
                                                        type="submit"
                                                        class="btn
    btn-soft-success
    btn-sm"
                                                        data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="Make active"
                                                        aria-label="Make SAK Volunteer active">

                                                        <i
                                                            class="ri-user-follow-line"
                                                            aria-hidden="true">
                                                        </i>

                                                    </button>

                                                </form>

                                            <?php else: ?>

                                                <!-- UPI required -->
                                                <button
                                                    type="button"
                                                    class="btn
    btn-soft-secondary
    btn-sm"
                                                    data-bs-toggle="tooltip"
                                                    data-bs-placement="top"
                                                    title="UPI ID required"
                                                    aria-label="UPI ID required before activation"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#fieldOfficerUpiRequiredModal">

                                                    <i
                                                        class="ri-information-line"
                                                        aria-hidden="true">
                                                    </i>

                                                </button>

                                            <?php endif; ?>

                                        <?php endif; ?>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

<?php $this->endSection(); ?>