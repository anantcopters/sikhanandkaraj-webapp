<?php

declare(strict_types=1);

use App\Models\AdminUserModel;

$alert = session('formAlert');

$formAlert = is_array($alert)
    ? $alert
    : null;

$this->extend('Admin/Layouts/Main');
$this->section('content');
?>

<section class="admin-page-section">
    <div class="container-fluid">

        <div class="admin-page-heading">
            <div>
                <p class="admin-page-heading__eyebrow">
                    Access Management
                </p>

                <h1>Administrators</h1>

                <p>
                    Invite, verify and manage administrator access.
                </p>
            </div>

            <a
                href="<?= route_to(
                            'admin.users.create'
                        ) ?>"
                class="btn registration-form__submit
                    admin-page-heading__action">
                <i
                    class="ri-user-add-line"
                    aria-hidden="true">
                </i>
                Add Administrator
            </a>
        </div>

        <?= view(
            'Components/Alerts/FormAlert',
            [
                'alert' => $formAlert,
            ]
        ) ?>

        <div class="admin-panel p-0 overflow-hidden">

            <div class="table-responsive">
                <table
                    class="table admin-table mb-0
                        align-middle">

                    <thead>
                        <tr>
                            <th>Administrator</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Added</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (
                            $administrators === []
                        ): ?>
                            <tr>
                                <td colspan="5">
                                    <div class="admin-empty-state">
                                        <i
                                            class="ri-user-settings-line">
                                        </i>

                                        <h2>
                                            No administrators added
                                        </h2>

                                        <p>
                                            Add the first administrator
                                            and send an email invitation.
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

                            $badgeClass = match ($status) {
                                AdminUserModel::STATUS_VERIFIED =>
                                'admin-status--verified',

                                AdminUserModel::STATUS_SUSPENDED =>
                                'admin-status--suspended',

                                default =>
                                'admin-status--pending',
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
                                    <div class="admin-user-cell">
                                        <div class="admin-user-avatar">
                                            <?= esc(
                                                mb_strtoupper(
                                                    mb_substr(
                                                        (string) $admin['full_name'],
                                                        0,
                                                        1
                                                    )
                                                )
                                            ) ?>
                                        </div>

                                        <div>
                                            <strong>
                                                <?= esc(
                                                    $admin['full_name']
                                                ) ?>
                                            </strong>

                                            <span>Administrator</span>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <div class="admin-contact-cell">
                                        <span>
                                            <i class="ri-mail-line"></i>
                                            <?= esc(
                                                $admin['email_address']
                                            ) ?>
                                        </span>

                                        <span>
                                            <i class="ri-phone-line"></i>
                                            <?= esc(
                                                $admin['mobile_number']
                                            ) ?>
                                        </span>
                                    </div>
                                </td>

                                <td>
                                    <span
                                        class="admin-status
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

                                <td class="text-end">
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
                                                btn-outline-primary">
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
                                                btn-outline-danger">
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
</section>

<?php $this->endSection(); ?>
