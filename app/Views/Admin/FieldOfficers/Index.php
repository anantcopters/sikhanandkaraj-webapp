<?php

declare(strict_types=1);

$alert = session('formAlert');
$formAlert = is_array($alert)
    ? $alert
    : null;

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
                        Field Officers
                    </h4>

                    <p class="text-muted mb-0 mt-1">
                        Manage Field Officers and their
                        assigned locations.
                    </p>
                </div>

                <div class="page-title-right mt-3 mt-sm-0">
                    <a
                        href="<?= route_to(
                                    'admin.field-officers.create'
                                ) ?>"
                        class="btn registration-form__submit">

                        <i
                            class="ri-user-add-line
                                align-middle me-1">
                        </i>

                        Add Field Officer
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?= view(
        'Components/Alerts/FormAlert',
        [
            'alert' => $formAlert,
        ]
    ) ?>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">
                        Field Officer List
                    </h4>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table
                            class="table
            table-hover
            table-nowrap
            align-middle
            mb-0">

                            <thead class="table-light">
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
                                    $fieldOfficers === []
                                ): ?>
                                    <tr>
                                        <td
                                            colspan="6"
                                            class="text-center
                            text-muted py-4">

                                            No Field Officers
                                            have been added.
                                        </td>
                                    </tr>
                                <?php endif; ?>

                                <?php foreach (
                                    $fieldOfficers
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
                                text-primary">

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
                                            <?php if ($isActive): ?>
                                                <span
                                                    class="badge
                                    bg-success-subtle
                                    text-success">

                                                    Active
                                                </span>
                                            <?php else: ?>
                                                <span
                                                    class="badge
                                    bg-secondary-subtle
                                    text-secondary">

                                                    Inactive
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <td class="text-end">
                                            <div
                                                class="d-inline-flex
                                align-items-center
                                gap-1">

                                                <a
                                                    href="<?= route_to(
                                                                'admin.field-officers.edit',
                                                                (int) $fieldOfficer['id']
                                                            ) ?>"
                                                    class="btn
                                    btn-soft-primary
                                    btn-sm"
                                                    title="Edit Field Officer"
                                                    aria-label="Edit Field Officer">

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
                                                        data-confirm-title="Deactivate Field Officer?"
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
                                                            aria-label="Make Field Officer inactive">

                                                            <i
                                                                class="ri-user-unfollow-line"
                                                                aria-hidden="true">
                                                            </i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <form
                                                        action="<?= route_to(
                                                                    'admin.field-officers.activate',
                                                                    (int) $fieldOfficer['id']
                                                                ) ?>"
                                                        method="post"
                                                        class="d-inline"
                                                        data-confirm-form
                                                        data-confirm-title="Activate Field Officer?"
                                                        data-confirm-message="<?= esc(
                                                                                    $hasUpiId
                                                                                        ? 'Do you want to make '
                                                                                        . (string) $fieldOfficer['full_name']
                                                                                        . ' active?'
                                                                                        : 'This Field Officer does not have a UPI ID. Activation will not be allowed until a valid UPI ID is added.',
                                                                                    'attr'
                                                                                ) ?>"
                                                        data-confirm-button-text="Make Active"
                                                        data-confirm-loading-text="Activating..."
                                                        data-confirm-button-class="btn-success"
                                                        data-confirm-icon="ri-user-follow-line">

                                                        <?= csrf_field() ?>

                                                        <button
                                                            type="submit"
                                                            class="btn
                                            btn-soft-success
                                            btn-sm"
                                                            title="<?= $hasUpiId
                                                                        ? 'Make active'
                                                                        : 'UPI ID required before activation' ?>"
                                                            aria-label="Make Field Officer active">

                                                            <i
                                                                class="ri-user-follow-line"
                                                                aria-hidden="true">
                                                            </i>
                                                        </button>
                                                    </form>
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
    </div>
</div>

<?php $this->endSection(); ?>