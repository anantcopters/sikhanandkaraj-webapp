<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AdminUserModel;

final class AdminDashboardController extends BaseController
{
    public function index(): string
    {
        $model = new AdminUserModel();

        $administrators =
            $model->listAdministrators();

        $summary = [
            'total' => count($administrators),
            'not_verified' => 0,
            'verified' => 0,
            'suspended' => 0,
        ];

        foreach ($administrators as $admin) {
            $status = $admin['account_status']
                ?? '';

            if (
                $status ===
                AdminUserModel::STATUS_PENDING
            ) {
                $summary['not_verified']++;
            } elseif (
                $status ===
                AdminUserModel::STATUS_VERIFIED
            ) {
                $summary['verified']++;
            } elseif (
                $status ===
                AdminUserModel::STATUS_SUSPENDED
            ) {
                $summary['suspended']++;
            }
        }

        return view(
            'Admin/Dashboard/Index',
            [
                'pageTitle' =>
                'Administrator Dashboard',
                'summary' => $summary,
                'administrators' =>
                $administrators,
            ]
        );
    }
}
