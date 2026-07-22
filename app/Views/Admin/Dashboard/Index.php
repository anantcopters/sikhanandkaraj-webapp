<?php

declare(strict_types=1);

use App\Models\AdminUserModel;

/**
 * Variables supplied by AdminDashboardController.
 * 
 * @var bool $isSuperAdmin
 *
 * @var array{
 *     total:int,
 *     pending?:int,
 *     verified:int,
 *     suspended:int
 * } $summary
 *
 * @var list<array<string, mixed>> $administrators
 */

$alert = session('formAlert');

$formAlert = is_array($alert)
    ? $alert
    : null;

$summary = isset($summary)
    && is_array($summary)
    ? $summary
    : [];

$summary = array_merge(
    [
        'total' => 0,
        'pending' => 0,
        'verified' => 0,
        'suspended' => 0,
    ],
    $summary
);

$administrators = isset($administrators)
    && is_array($administrators)
    ? $administrators
    : [];

$isSuperAdmin = isset($isSuperAdmin)
    && $isSuperAdmin === true;

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
                        Administrator Dashboard
                    </h4>

                    <p class="text-muted mb-0 mt-1">
                        Review administrator accounts,
                        verification status and access.
                    </p>
                </div>

                <div class="page-title-right">
                    <?php if ($isSuperAdmin): ?>
                        <a
                            href="<?= route_to(
                                        'admin.users.create'
                                    ) ?>"
                            class="btn btn-primary">
                            <i
                                class="ri-user-add-line"
                                aria-hidden="true"></i>

                            Add Administrator
                        </a>
                    <?php endif; ?>
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
    <?php if ($isSuperAdmin): ?>
        <div class="row g-3 mb-4">

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card card-animate h-100">
                    <div class="card-body">
                        <div
                            class="d-flex align-items-center
                justify-content-between">

                            <div>
                                <p
                                    class="text-uppercase
                        fw-medium text-muted
                        text-truncate mb-0">
                                    Total Administrators
                                </p>

                                <h4 class="fs-22 fw-semibold mb-0 mt-2">
                                    <?= esc(
                                        (string) $summary['total']
                                    ) ?>
                                </h4>
                            </div>

                            <div class="avatar-sm flex-shrink-0">
                                <span
                                    class="avatar-title
                        bg-primary-subtle
                        text-primary rounded-circle
                        fs-3">
                                    <i class="ri-group-line"></i>
                                </span>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card card-animate h-100">
                    <div class="card-body">
                        <div
                            class="d-flex align-items-center
                justify-content-between">

                            <div>
                                <p
                                    class="text-uppercase
            fw-medium text-muted mb-0">
                                    Pending Verification
                                </p>

                                <h4 class="fs-22 fw-semibold mb-0 mt-2">
                                    <?= esc(
                                        (string) $summary['pending']
                                    ) ?>
                                </h4>
                            </div>

                            <div class="avatar-sm flex-shrink-0">
                                <span
                                    class="avatar-title
            bg-warning-subtle
            text-warning rounded-circle fs-3">
                                    <i class="ri-time-line"></i>
                                </span>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card card-animate h-100">
                    <div class="card-body">
                        <div
                            class="d-flex align-items-center
                justify-content-between">

                            <div>
                                <p
                                    class="text-uppercase
            fw-medium text-muted mb-0">
                                    Verified
                                </p>

                                <h4 class="fs-22 fw-semibold mb-0 mt-2">
                                    <?= esc(
                                        (string) $summary['verified']
                                    ) ?>
                                </h4>
                            </div>

                            <div class="avatar-sm flex-shrink-0">
                                <span
                                    class="avatar-title
            bg-success-subtle
            text-success rounded-circle fs-3">
                                    <i class="ri-checkbox-circle-line"></i>
                                </span>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card card-animate h-100">
                    <div class="card-body">
                        <div
                            class="d-flex align-items-center
                justify-content-between">

                            <div>
                                <p
                                    class="text-uppercase
            fw-medium text-muted mb-0">
                                    Suspended
                                </p>

                                <h4 class="fs-22 fw-semibold mb-0 mt-2">
                                    <?= esc(
                                        (string) $summary['suspended']
                                    ) ?>
                                </h4>
                            </div>

                            <div class="avatar-sm flex-shrink-0">
                                <span
                                    class="avatar-title
            bg-danger-subtle
            text-danger rounded-circle fs-3">
                                    <i class="ri-forbid-line"></i>
                                </span>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <div class="avatar-md mx-auto mb-3">
                    <div
                        class="avatar-title rounded-circle
                    bg-primary-subtle text-primary fs-24">
                        <i
                            class="ri-shield-user-line"
                            aria-hidden="true"></i>
                    </div>
                </div>

                <h5 class="mb-1">
                    Welcome to the administration panel
                </h5>

                <p class="text-muted mb-0">
                    Use the available administration tools to
                    manage your assigned responsibilities.
                </p>
            </div>
        </div>
    <?php endif; ?>

    <div class="card">

        <div
            class="card-header
            d-flex align-items-center
            justify-content-between gap-3">

            <div>
                <h4 class="card-title mb-1">
                    Administrator Accounts
                </h4>

                <p class="text-muted mb-0">
                    Current administrators and their
                    account status.
                </p>
            </div>

            <?php if ($isSuperAdmin): ?>
                <a
                    href="<?= route_to(
                                'admin.users.index'
                            ) ?>"
                    class="btn btn-soft-secondary btn-sm">
                    Manage Administrators
                </a>
            <?php endif; ?>

        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table
                    class="table table-hover table-nowrap align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Administrator</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Last Login</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (
                            $administrators === []
                        ): ?>
                            <tr>
                                <td colspan="4">
                                    <div class="text-center py-5">
                                        <div
                                            class="avatar-md mx-auto mb-3">
                                            <div
                                                class="avatar-title
                rounded-circle
                bg-primary-subtle
                text-primary fs-24">
                                                <i class="ri-user-settings-line"></i>
                                            </div>
                                        </div>

                                        <h5 class="mb-1">
                                            No administrators found
                                        </h5>

                                        <p class="text-muted mb-0">
                                            Add an administrator to send
                                            the first invitation.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach (
                            $administrators as $admin
                        ): ?>
                            <?php
                            $status = strtoupper(
                                trim(
                                    (string) (
                                        $admin['account_status']
                                        ?? ''
                                    )
                                )
                            );

                            $badgeClass = match ($status) {
                                AdminUserModel::STATUS_VERIFIED =>
                                'bg-success-subtle text-success',

                                AdminUserModel::STATUS_SUSPENDED =>
                                'bg-danger-subtle text-danger',

                                default =>
                                'bg-warning-subtle text-warning',
                            };

                            $statusLabel = match ($status) {
                                AdminUserModel::STATUS_PENDING =>
                                'Not Verified',

                                AdminUserModel::STATUS_VERIFIED =>
                                'Verified',

                                AdminUserModel::STATUS_SUSPENDED =>
                                'Suspended',

                                default => 'Unknown',
                            };

                            $lastLoginAt = trim(
                                (string) (
                                    $admin['last_login_at']
                                    ?? ''
                                )
                            );
                            ?>

                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">

                                        <div class="flex-shrink-0">
                                            <div class="avatar-sm">
                                                <span
                                                    class="avatar-title
                        rounded-circle
                        bg-primary-subtle
                        text-primary fw-semibold">

                                                    <?= esc(
                                                        mb_strtoupper(
                                                            mb_substr(
                                                                (string) (
                                                                    $admin['full_name']
                                                                    ?? 'A'
                                                                ),
                                                                0,
                                                                1
                                                            )
                                                        )
                                                    ) ?>
                                                </span>
                                            </div>
                                        </div>

                                        <div class="flex-grow-1 ms-2">
                                            <h6 class="mb-0">
                                                <?= esc(
                                                    $admin['full_name']
                                                        ?? ''
                                                ) ?>
                                            </h6>

                                            <p class="text-muted mb-0 fs-12">
                                                Administrator
                                            </p>
                                        </div>

                                    </div>
                                </td>

                                <td>
                                    <div>
                                        <p class="mb-1">
                                            <i
                                                class="ri-mail-line
                    align-middle me-1
                    text-muted">
                                            </i>

                                            <?= esc(
                                                $admin['email_address']
                                                    ?? ''
                                            ) ?>
                                        </p>

                                        <p class="text-muted mb-0">
                                            <i
                                                class="ri-phone-line
                    align-middle me-1">
                                            </i>

                                            <?= esc(
                                                $admin['mobile_number']
                                                    ?? ''
                                            ) ?>
                                        </p>
                                    </div>
                                </td>

                                <td>
                                    <span
                                        class="badge fs-12 p-2 
                                        <?= esc(
                                            $badgeClass,
                                            'attr'
                                        ) ?>">
                                        <?= esc($statusLabel) ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if (
                                        $lastLoginAt !== ''
                                    ): ?>
                                        <?= esc(
                                            date(
                                                'd M Y, h:i A',
                                                strtotime(
                                                    $lastLoginAt
                                                )
                                            )
                                        ) ?>
                                    <?php else: ?>
                                        <span class="text-muted">
                                            Never
                                        </span>
                                    <?php endif; ?>
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