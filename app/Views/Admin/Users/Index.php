<?php

declare(strict_types=1);

use App\Models\AdminUserModel;

/**
 * Administrator records supplied by AdminUserController::index().
 *
 * @var list<array{
 *     id:int|string,
 *     full_name:string,
 *     email_address:string,
 *     mobile_number:string,
 *     role:string,
 *     account_status:string,
 *     created_at:string,
 *     last_login_at?:string|null
 * }> $administrators
 */

if (
    !isset($administrators)
    || !is_array($administrators)
) {
    $administrators = [];
}

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
                        Manage Administrators
                    </h4>

                    <p class="text-muted mb-0 mt-1">
                        Invite, review and manage administrator
                        access.
                    </p>
                </div>

                <div class="page-title-right">
                    <a
                        href="<?= route_to(
                                    'admin.users.create'
                                ) ?>"
                        class="btn btn-primary">
                        <i
                            class="ri-user-add-line"
                            aria-hidden="true">
                        </i>
                        Add Administrator
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

    <div class="card border-danger border-opacity-25">
        <div class="card-body p-0">

            <div class="table-responsive">
                <table
                    class="table table-hover
                    table-nowrap align-middle mb-0">

                    <thead class="table-light">
                        <tr>
                            <th>Administrator</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Added</th>
                            <th class="text-end text-nowrap">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (
                            $administrators === []
                        ): ?>
                            <tr>
                                <td colspan="5">
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
                            $status = (string) (
                                $admin['account_status']
                                ?? ''
                            );

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

                                default => $status,
                            };
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
                                        <?= $badgeClass ?>">
                                        <?= esc($statusLabel) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= esc(
                                        date(
                                            'd M Y',
                                            strtotime(
                                                (string) $admin['created_at']
                                            )
                                        )
                                    ) ?>
                                </td>

                                <td class="text-end text-nowrap">
                                    <?php if (
                                        $status ===
                                        AdminUserModel::STATUS_PENDING
                                    ): ?>
                                        <form
                                            class="d-inline"
                                            action="<?= route_to(
                                                        'admin.users.resend',
                                                        $admin['id']
                                                    ) ?>"
                                            method="post">

                                            <?= csrf_field() ?>

                                            <button
                                                type="submit"
                                                class="btn btn-sm
                                                btn-soft-primary">
                                                Resend Invitation
                                            </button>
                                        </form>
                                    <?php elseif (
                                        $status ===
                                        AdminUserModel::STATUS_VERIFIED
                                    ): ?>
                                        <form
                                            class="d-inline"
                                            action="<?= route_to(
                                                        'admin.users.suspend',
                                                        $admin['id']
                                                    ) ?>"
                                            method="post"
                                            onsubmit="return confirm(
                                            'Suspend this administrator?'
                                        );">

                                            <?= csrf_field() ?>

                                            <button
                                                type="submit"
                                                class="btn btn-sm
                                                btn-soft-danger">
                                                Suspend
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted fs-13">
                                            No actions available
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