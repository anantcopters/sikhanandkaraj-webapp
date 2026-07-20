<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\AdminUserModel;
use RuntimeException;

final class AdminManagementService
{
    public function __construct(
        private readonly AdminUserModel $adminUserModel
    ) {}

    public function suspend(
        int $adminUserId
    ): void {
        $admin = $this->adminUserModel
            ->find($adminUserId);

        if (!is_array($admin)) {
            throw new RuntimeException(
                'Administrator could not be found.'
            );
        }

        if (
            ($admin['role'] ?? null)
            !== AdminUserModel::ROLE_ADMIN
        ) {
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
    }
}
