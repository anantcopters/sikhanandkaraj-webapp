<?php

declare(strict_types=1);

/**
 * @var list<array<string, mixed>> $profiles
 * @var string $selectedStatus
 * @var array<string, string>|null $formAlert
 */

$resolvedProfiles = is_array(
    $profiles ?? null
)
    ? $profiles
    : [];

$resolvedFormAlert = is_array(
    $formAlert ?? null
)
    ? $formAlert
    : null;

$resolvedStatus = in_array(
    $selectedStatus ?? '',
    [
        'DRAFT',
        'APPROVED',
        'REJECTED',
    ],
    true
)
    ? $selectedStatus
    : 'DRAFT';

$this->extend('Admin/Layouts/Main');
$this->section('content');
?>

<div class="container-fluid">

    <!-- Page heading -->
    <div class="row">
        <div class="col-12">

            <div
                class="page-title-box
                d-sm-flex
                align-items-center
                justify-content-between">

                <div>
                    <h4 class="mb-sm-0">
                        Pre-launch Profiles
                    </h4>

                    <p class="text-muted mb-0 mt-1">
                        Review submitted profile details
                        and photographs.
                    </p>
                </div>

                <!-- Status filter -->
                <div class="page-title-right mt-3 mt-sm-0">

                    <form
                        method="get"
                        action="<?= route_to(
                                    'admin.prelaunch.profiles.index'
                                ) ?>">

                        <label
                            for="prelaunch-status-filter"
                            class="visually-hidden">
                            Filter profiles by status
                        </label>

                        <select
                            id="prelaunch-status-filter"
                            class="form-select"
                            name="status"
                            onchange="this.form.submit()">

                            <?php foreach (
                                [
                                    'DRAFT' =>
                                    'Draft',

                                    'APPROVED' =>
                                    'Approved',

                                    'REJECTED' =>
                                    'Rejected',
                                ] as $value => $label
                            ): ?>

                                <option
                                    value="<?= esc(
                                                $value,
                                                'attr'
                                            ) ?>"
                                    <?= $resolvedStatus === $value
                                        ? 'selected'
                                        : '' ?>>

                                    <?= esc($label) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </form>

                </div>

            </div>

        </div>
    </div>

    <!-- Shared form alert -->
    <?= view(
        'Components/Alerts/FormAlert',
        [
            'alert' =>
            $resolvedFormAlert,
        ]
    ) ?>

    <!-- Profile list -->
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
                                Reference
                            </th>

                            <th scope="col">
                                Member
                            </th>

                            <th scope="col">
                                Contact
                            </th>

                            <th scope="col">
                                Location
                            </th>

                            <th scope="col">
                                Field Officer
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
                            $resolvedProfiles === []
                        ): ?>

                            <tr>
                                <td
                                    colspan="7"
                                    class="text-center
                                    text-muted
                                    py-4">

                                    No pre-launch profiles
                                    found for the selected
                                    status.

                                </td>
                            </tr>

                        <?php endif; ?>

                        <?php foreach (
                            $resolvedProfiles
                            as $profile
                        ): ?>

                            <?php
                            $profileId = (int) (
                                $profile['id']
                                ?? 0
                            );

                            $reference = trim(
                                (string) (
                                    $profile['profile_reference']
                                    ?? ''
                                )
                            );

                            $fullName = trim(
                                (string) (
                                    $profile['full_name']
                                    ?? ''
                                )
                            );

                            $gender = trim(
                                (string) (
                                    $profile['gender']
                                    ?? ''
                                )
                            );

                            $email = trim(
                                (string) (
                                    $profile['email']
                                    ?? ''
                                )
                            );

                            $countryCode = trim(
                                (string) (
                                    $profile['country_code']
                                    ?? ''
                                )
                            );

                            $mobileNumber = trim(
                                (string) (
                                    $profile['mobile_number']
                                    ?? ''
                                )
                            );

                            $locationParts = array_filter([
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
                            ]);

                            $location = implode(
                                ', ',
                                $locationParts
                            );

                            $fieldOfficerName = trim(
                                (string) (
                                    $profile['field_officer_name']
                                    ?? ''
                                )
                            );

                            $officerCode = trim(
                                (string) (
                                    $profile['officer_code']
                                    ?? ''
                                )
                            );

                            $status = mb_strtoupper(
                                trim(
                                    (string) (
                                        $profile['status']
                                        ?? 'DRAFT'
                                    )
                                )
                            );

                            $statusClass = match ($status) {
                                'APPROVED' =>
                                'bg-success-subtle text-black',

                                'REJECTED' =>
                                'bg-danger-subtle text-danger',

                                default =>
                                'bg-warning-subtle text-black',
                            };
                            ?>

                            <tr>

                                <!-- Reference -->
                                <td>
                                    <span
                                        class="badge
                                        bg-primary-subtle
                                        text-primary
                                        p-2">

                                        <?= esc(
                                            $reference !== ''
                                                ? $reference
                                                : '—'
                                        ) ?>

                                    </span>
                                </td>

                                <!-- Member -->
                                <td>
                                    <span class="fw-semibold">
                                        <?= esc(
                                            $fullName !== ''
                                                ? $fullName
                                                : '—'
                                        ) ?>
                                    </span>

                                    <?php if (
                                        $gender !== ''
                                    ): ?>
                                        <div
                                            class="small
                                            text-muted">

                                            <?= esc(
                                                ucfirst(
                                                    mb_strtolower(
                                                        $gender
                                                    )
                                                )
                                            ) ?>

                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Contact -->
                                <td>
                                    <div>
                                        <?= esc(
                                            $email !== ''
                                                ? $email
                                                : '—'
                                        ) ?>
                                    </div>

                                    <?php if (
                                        $mobileNumber !== ''
                                    ): ?>
                                        <div
                                            class="small
                                            text-muted">

                                            <?= esc(
                                                trim(
                                                    $countryCode
                                                        . ' '
                                                        . $mobileNumber
                                                )
                                            ) ?>

                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Location -->
                                <td>
                                    <?= esc(
                                        $location !== ''
                                            ? $location
                                            : '—'
                                    ) ?>
                                </td>

                                <!-- Field Officer -->
                                <td>
                                    <?= esc(
                                        $fieldOfficerName !== ''
                                            ? $fieldOfficerName
                                            : '—'
                                    ) ?>

                                    <?php if (
                                        $officerCode !== ''
                                    ): ?>
                                        <div
                                            class="small
                                            text-muted">

                                            <?= esc(
                                                $officerCode
                                            ) ?>

                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Status -->
                                <td>
                                    <span
                                        class="badge
                                        <?= esc(
                                            $statusClass,
                                            'attr'
                                        ) ?>
                                        p-2">

                                        <?= esc(
                                            ucfirst(
                                                mb_strtolower(
                                                    $status
                                                )
                                            )
                                        ) ?>

                                    </span>
                                </td>

                                <!-- Action -->
                                <td class="text-end">
                                    <a
                                        href="<?= route_to(
                                                    'admin.prelaunch.profiles.review',
                                                    $profileId
                                                ) ?>"
                                        class="btn
                                        btn-soft-primary
                                        btn-sm"
                                        title="Review profile"
                                        aria-label="<?= esc(
                                                        'Review profile '
                                                            . $fullName,
                                                        'attr'
                                                    ) ?>">

                                        <i
                                            class="ri-eye-line"
                                            aria-hidden="true">
                                        </i>

                                    </a>
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