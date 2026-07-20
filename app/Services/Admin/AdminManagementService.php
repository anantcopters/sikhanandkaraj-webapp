<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\AdminUserModel;
use App\Services\Admin\Audit\AdminAuditService;
use RuntimeException;

final class AdminManagementService
{
    public function __construct(
        private readonly AdminUserModel $adminUserModel,
        private readonly AdminAuditService $auditService
    ) {}

    public function suspend(
        int $adminUserId
    ): void {
        $admin = $this->adminUserModel
            ->find($adminUserId);

        if (!is_array($admin)) {
            $this->auditService->record(
                new AdminAuditEvent(
                    action: AdminAuditAction::ADMIN_SUSPENDED,
                    outcome: 'FAILURE',
                    targetType: 'ADMIN_USER',
                    targetId: $adminUserId,
                    description: 'Suspension failed because the administrator was not found.'
                )
            );

            throw new RuntimeException(
                'Administrator could not be found.'
            );
        }

        if (
            ($admin['role'] ?? null)
            !== AdminUserModel::ROLE_ADMIN
        ) {
            $this->auditService->record(
                new AdminAuditEvent(
                    action: AdminAuditAction::ADMIN_SUSPENDED,
                    outcome: 'DENIED',
                    targetType: 'ADMIN_USER',
                    targetId: $adminUserId,
                    targetLabel: (string) (
                        $admin['email_address']
                        ?? ''
                    ),
                    description: 'Attempt to suspend a non-admin account was denied.'
                )
            );

            throw new RuntimeException(
                'The super administrator cannot be suspended here.'
            );
        }

        if (
            ($admin['account_status'] ?? null)
            !== AdminUserModel::STATUS_VERIFIED
        ) {
            throw new RuntimeException(
                'Only verified administrators can be suspended.'
            );
        }

        $before = [
            'account_status' =>
            $admin['account_status'] ?? null,
        ];

        $updated = $this->adminUserModel->update(
            $adminUserId,
            [
                'account_status' =>
                AdminUserModel::STATUS_SUSPENDED,
            ]
        );

        if ($updated === false) {
            throw new RuntimeException(
                'Administrator could not be suspended.'
            );
        }

        /** @var \App\Services\Admin\Audit\AdminAuditService $audit */
        $audit = service('adminAuditService');

        $audit->record(
            new \App\Services\Admin\Audit\AdminAuditEvent(
                action: \App\Services\Admin\Audit\AdminAuditAction::ADMIN_SUSPENDED,

                targetType: 'ADMIN_USER',

                targetId: $adminUserId,

                targetLabel: (string) (
                    $admin['email_address']
                    ?? ''
                ),

                description: 'Administrator account was suspended.',

                beforeData: [
                    'account_status' =>
                    AdminUserModel::STATUS_VERIFIED,
                ],

                afterData: [
                    'account_status' =>
                    AdminUserModel::STATUS_SUSPENDED,
                ]
            )
        );
    }
}
