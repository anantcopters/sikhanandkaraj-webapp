<?php

declare(strict_types=1);

use App\Models\AdminUserModel;

/**
 * Variables supplied by AdminDashboardController.
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

$this->extend('Admin/Layouts/Main');

$this->section('content');
?>

<section class="admin-page-section">
    <div class="container-fluid">

        <div class="admin-page-heading">
            <div>
                <p class="admin-page-heading__eyebrow">
                    Administration
                </p>

                <h1>Dashboard</h1>

                <p>
                    Review administrator accounts,
                    verification status and access.
                </p>
            </div>

            <?php if (
                session('admin_role')
                === AdminUserModel::ROLE_SUPER_ADMIN
            ): ?>
                <a
                    href="<?= route_to(
                                'admin.users.create'
                            ) ?>"
                    class="btn registration-form__submit
                        admin-page-heading__action">
                    <i
                        class="ri-user-add-line"
                        aria-hidden="true"></i>

                    Add Administrator
                </a>
            <?php endif; ?>
        </div>

        <?= view(
            'Components/Alerts/FormAlert',
            [
                'alert' => $formAlert,
            ]
        ) ?>

        <div class="row g-3 mb-4">

            <div class="col-6 col-lg-3">
                <div class="admin-summary-card">
                    <div
                        class="admin-summary-card__icon
                            admin-summary-card__icon--total">
                        <i
                            class="ri-group-line"
                            aria-hidden="true"></i>
                    </div>

                    <div>
                        <span>Total Admins</span>

                        <strong>
                            <?= esc(
                                (string) (
                                    $summary['total']
                                    ?? 0
                                )
                            ) ?>
                        </strong>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="admin-summary-card">
                    <div
                        class="admin-summary-card__icon
                            admin-summary-card__icon--pending">
                        <i
                            class="ri-time-line"
                            aria-hidden="true"></i>
                    </div>

                    <div>
                        <span>Not Verified</span>

                        <strong>
                            <?= esc(
                                (string) (
                                    $summary['pending']
                                    ?? $summary['not_verified']
                                    ?? 0
                                )
                            ) ?>
                        </strong>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="admin-summary-card">
                    <div
                        class="admin-summary-card__icon
                            admin-summary-card__icon--verified">
                        <i
                            class="ri-checkbox-circle-line"
                            aria-hidden="true"></i>
                    </div>

                    <div>
                        <span>Verified</span>

                        <strong>
                            <?= esc(
                                (string) (
                                    $summary['verified']
                                    ?? 0
                                )
                            ) ?>
                        </strong>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="admin-summary-card">
                    <div
                        class="admin-summary-card__icon
                            admin-summary-card__icon--suspended">
                        <i
                            class="ri-forbid-line"
                            aria-hidden="true"></i>
                    </div>

                    <div>
                        <span>Suspended</span>

                        <strong>
                            <?= esc(
                                (string) (
                                    $summary['suspended']
                                    ?? 0
                                )
                            ) ?>
                        </strong>
                    </div>
                </div>
            </div>

        </div>

        <div class="admin-panel p-0 overflow-hidden">

            <div
                class="admin-panel__heading
                    d-flex align-items-center
                    justify-content-between gap-3">
                <div>
                    <h2>Administrator Accounts</h2>

                    <p>
                        Current administrators and their
                        account status.
                    </p>
                </div>

                <?php if (
                    session('admin_role')
                    === AdminUserModel::ROLE_SUPER_ADMIN
                ): ?>
                    <a
                        href="<?= route_to(
                                    'admin.users.index'
                                ) ?>"
                        class="btn btn-sm
                            btn-outline-secondary">
                        Manage Admins
                    </a>
                <?php endif; ?>
            </div>

            <div class="table-responsive">
                <table
                    class="table admin-table
                        align-middle mb-0">
                    <thead>
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
                                    <div class="admin-empty-state">
                                        <i
                                            class="ri-user-settings-line"
                                            aria-hidden="true"></i>

                                        <h2>
                                            No administrators found
                                        </h2>

                                        <p>
                                            Add an administrator to
                                            send the first invitation.
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
                                    <div class="admin-user-cell">
                                        <div
                                            class="admin-user-avatar">
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
                                        </div>

                                        <div>
                                            <strong>
                                                <?= esc(
                                                    $admin['full_name']
                                                        ?? ''
                                                ) ?>
                                            </strong>

                                            <span>
                                                Administrator
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <div
                                        class="admin-contact-cell">
                                        <span>
                                            <i
                                                class="ri-mail-line"
                                                aria-hidden="true"></i>

                                            <?= esc(
                                                $admin['email_address']
                                                    ?? ''
                                            ) ?>
                                        </span>

                                        <span>
                                            <i
                                                class="ri-phone-line"
                                                aria-hidden="true"></i>

                                            <?= esc(
                                                $admin['mobile_number']
                                                    ?? ''
                                            ) ?>
                                        </span>
                                    </div>
                                </td>

                                <td>
                                    <span
                                        class="admin-status
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
</section>

<?php $this->endSection(); ?>