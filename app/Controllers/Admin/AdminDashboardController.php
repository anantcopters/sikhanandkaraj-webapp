<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AdminUserModel;

final class AdminDashboardController extends BaseController
{
    public function index(): string
    {
        $isSuperAdmin =
            session('admin_role')
            === AdminUserModel::ROLE_SUPER_ADMIN;

        /*
     * A regular administrator must not retrieve administrator
     * account information or KPI totals from the database.
     */
        if (!$isSuperAdmin) {
            return view(
                'Admin/Dashboard/Index',
                [
                    'pageTitle' => 'Administrator Dashboard',
                    'isSuperAdmin' => false,
                ]
            );
        }

        $model = new AdminUserModel();

        $administrators = $model->listAdministrators();

        $summary = [
            'total' => count($administrators),
            'pending' => 0,
            'verified' => 0,
            'suspended' => 0,
        ];

        foreach ($administrators as $admin) {
            $status = strtoupper(
                trim(
                    (string) (
                        $admin['account_status']
                        ?? ''
                    )
                )
            );

            if ($status === AdminUserModel::STATUS_PENDING) {
                $summary['pending']++;

                continue;
            }

            if ($status === AdminUserModel::STATUS_VERIFIED) {
                $summary['verified']++;

                continue;
            }

            if ($status === AdminUserModel::STATUS_SUSPENDED) {
                $summary['suspended']++;
            }
        }

        return view(
            'Admin/Dashboard/Index',
            [
                'pageTitle' => 'Administrator Dashboard',
                'isSuperAdmin' => true,
                'summary' => $summary,
                'administrators' => $administrators,
            ]
        );
    }
}
