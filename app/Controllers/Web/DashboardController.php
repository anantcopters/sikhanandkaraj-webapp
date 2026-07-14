<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;

/**
 * Displays the authenticated member dashboard.
 */
final class DashboardController extends BaseController
{
    public function index(): string
    {
        return view('Pages/Dashboard/Index', [
            'pageTitle' => 'Dashboard',
            'profileReference' =>
                session('auth_profile_reference'),
            'pageScripts' => [
                'assets/js/pages/dashboard-security.js',
            ],
        ]);
    }
}

