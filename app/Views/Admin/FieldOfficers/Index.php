<?php

declare(strict_types=1);

/**
 * @var list<array<string, mixed>> $fieldOfficers
 * @var array<string, string>|null $formAlert
 */

$resolvedFieldOfficers = is_array(
    $fieldOfficers ?? null
)
    ? $fieldOfficers
    : [];

$resolvedFormAlert = is_array(
    $formAlert ?? null
)
    ? $formAlert
    : null;

$reviewStatus = strtoupper(
    trim(
        (string) (
            $fieldOfficer['review_status']
            ?? ''
        )
    )
);

$isSelfRegistration =
    strtoupper(
        trim(
            (string) (
                $fieldOfficer['registration_source']
                ?? ''
            )
        )
    )
    === 'SELF';

$isPendingRegistration =
    $isSelfRegistration
    && $reviewStatus === 'PENDING';

$this->extend('Admin/Layouts/Main');
$this->section('content');
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div
                class="page-title-box
                    d-sm-flex align-items-center
                    justify-content-between">

                <div>
                    <h4 class="mb-sm-0">
                        SAK Volunteers
                    </h4>

                    <p class="text-muted mb-0 mt-1">
                        Manage SAK Volunteers and their
                        assigned locations.
                    </p>
                </div>

                <div class="page-title-right">
                    <a
                        href="<?= route_to(
                                    'admin.field-officers.create'
                                ) ?>"
                        class="btn btn-primary">

                        <i
                            class="ri-user-add-line
                                align-middle me-1">
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
            'alert' => $resolvedFormAlert,
        ]
    ) ?>


    <div class="card border border-danger border-opacity-25">
        <div class="card-body p-0">

            <div class="table-responsive">
                <table
                    class="table table-hover
                    table-nowrap align-middle mb-0">

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

                            <th
                                scope="col"
                                class="text-end">
                                Action
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (
                            $resolvedFieldOfficers === []
                        ): ?>
                            <tr>
                                <td
                                    colspan="6"
                                    class="text-center
                            text-muted py-4">

                                    No SAK Volunteers
                                    have been added.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach (
                            $resolvedFieldOfficers
                            as $fieldOfficer
                        ): ?>
                            <?php
                            $locationParts = array_filter([
                                trim(
                                    (string) (
                                        $fieldOfficer['city_name'] ?? ''
                                    )
                                ),
                                trim(
                                    (string) (
                                        $fieldOfficer['state_name'] ?? ''
                                    )
                                ),
                                trim(
                                    (string) (
                                        $fieldOfficer['country_name'] ?? ''
                                    )
                                ),
                            ]);

                            $location = implode(
                                ', ',
                                $locationParts
                            );

                            $isActive =
                                (string) (
                                    $fieldOfficer['account_status'] ?? ''
                                )
                                === \App\Models\FieldOfficerModel
                                ::STATUS_ACTIVE;

                            $hasUpiId =
                                trim(
                                    (string) (
                                        $fieldOfficer['upi_id'] ?? ''
                                    )
                                ) !== '';
                            ?>

                            <tr>
                                <td>
                                    <span class="fw-semibold">
                                        <?= esc(
                                            (string) $fieldOfficer['full_name']
                                        ) ?>
                                    </span>
                                </td>

                                <td>
                                    <span
                                        class="badge
                                bg-primary-subtle
                                text-primary p-2">

                                        <?= esc(
                                            (string) $fieldOfficer['officer_code']
                                        ) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= esc(
                                        (string) $fieldOfficer['mobile_number']
                                    ) ?>
                                </td>

                                <td>
                                    <?= esc(
                                        $location !== ''
                                            ? $location
                                            : '—'
                                    ) ?>
                                </td>

                                <td>
                                    <?php if ($isPendingRegistration): ?>

                                        <span
                                            class="badge
        bg-warning-subtle
        text-dark
        p-2">

                                            Pending Review
                                        </span>

                                    <?php elseif (
                                        $reviewStatus === 'REJECTED'
                                    ): ?>

                                        <span
                                            class="badge
        bg-danger-subtle
        text-danger
        p-2">

                                            Rejected
                                        </span>

                                    <?php elseif ($isActive): ?>

                                        <span
                                            class="badge
        bg-success-subtle
        text-black
        p-2">

                                            Active
                                        </span>

                                    <?php else: ?>

                                        <span
                                            class="badge
        bg-secondary-subtle
        text-black
        p-2">

                                            Inactive
                                        </span>

                                    <?php endif; ?>
                                </td>

                                <td class="text-end">
                                    <div
                                        class="d-inline-flex
                                align-items-center
                                gap-1">
                                        <?php if ($isPendingRegistration): ?>

                                            <form
                                                action="<?= route_to(
                                                            'admin.field-officers.approve-registration',
                                                            (int) $fieldOfficer['id']
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
                                                    title="Approve SAK Volunteer"
                                                    aria-label="Approve SAK Volunteer">

                                                    <i
                                                        class="ri-checkbox-circle-line"
                                                        aria-hidden="true">
                                                    </i>

                                                </button>

                                            </form>

                                            <form
                                                action="<?= route_to(
                                                            'admin.field-officers.reject-registration',
                                                            (int) $fieldOfficer['id']
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
                                                    title="Reject SAK Volunteer"
                                                    aria-label="Reject SAK Volunteer">

                                                    <i
                                                        class="ri-close-circle-line"
                                                        aria-hidden="true">
                                                    </i>

                                                </button>

                                            </form>

                                        <?php endif; ?>
                                        <a
                                            href="<?= route_to(
                                                        'admin.field-officers.edit',
                                                        (int) $fieldOfficer['id']
                                                    ) ?>"
                                            class="btn
                                    btn-soft-primary
                                    btn-sm"
                                            title="Edit SAK Volunteer"
                                            aria-label="Edit SAK Volunteer">

                                            <i
                                                class="ri-edit-line"
                                                aria-hidden="true">
                                            </i>
                                        </a>

                                        <?php if ($isActive): ?>
                                            <form
                                                action="<?= route_to(
                                                            'admin.field-officers.deactivate',
                                                            (int) $fieldOfficer['id']
                                                        ) ?>"
                                                method="post"
                                                class="d-inline"
                                                data-confirm-form
                                                data-confirm-title="Deactivate SAK Volunteer?"
                                                data-confirm-message="<?= esc(
                                                                            'Do you want to make '
                                                                                . (string) $fieldOfficer['full_name']
                                                                                . ' inactive?',
                                                                            'attr'
                                                                        ) ?>"
                                                data-confirm-button-text="Make Inactive"
                                                data-confirm-loading-text="Deactivating..."
                                                data-confirm-button-class="btn-warning"
                                                data-confirm-icon="ri-user-unfollow-line">

                                                <?= csrf_field() ?>

                                                <button
                                                    type="submit"
                                                    class="btn
                                            btn-soft-warning
                                            btn-sm"
                                                    title="Make inactive"
                                                    aria-label="Make SAK Volunteer inactive">

                                                    <i
                                                        class="ri-user-unfollow-line"
                                                        aria-hidden="true">
                                                    </i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <?php if ($hasUpiId): ?>
                                                <form
                                                    action="<?= route_to(
                                                                'admin.field-officers.activate',
                                                                (int) $fieldOfficer['id']
                                                            ) ?>"
                                                    method="post"
                                                    class="d-inline"
                                                    data-confirm-form
                                                    data-confirm-title="Activate SAK Volunteer?"
                                                    data-confirm-message="<?= esc(
                                                                                'Do you want to make '
                                                                                    . (string) $fieldOfficer['full_name']
                                                                                    . ' active?',
                                                                                'attr'
                                                                            ) ?>"
                                                    data-confirm-button-text="Make Active"
                                                    data-confirm-loading-text="Activating..."
                                                    data-confirm-button-class="btn-success"
                                                    data-confirm-icon="ri-user-follow-line">

                                                    <?= csrf_field() ?>

                                                    <button
                                                        type="submit"
                                                        class="btn btn-soft-success btn-sm"
                                                        title="Make active"
                                                        aria-label="Make SAK Volunteer active">

                                                        <i
                                                            class="ri-user-follow-line"
                                                            aria-hidden="true">
                                                        </i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <button
                                                    type="button"
                                                    class="btn btn-soft-secondary btn-sm"
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