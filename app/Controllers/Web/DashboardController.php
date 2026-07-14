<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use App\Controllers\BaseController;

/**
 * Displays the authenticated member dashboard.
 */
final class DashboardController extends BaseController
{
    public function index(): string
    {
        $userId = session('auth_user_id');

        if (!is_numeric($userId)) {
            throw PageNotFoundException::forPageNotFound();
        }

        $userModel = new UserModel();

        $user = $userModel->find(
            (int) $userId
        );

        if (!is_array($user)) {
            session()->destroy();

            throw PageNotFoundException::forPageNotFound();
        }

        return view(
            'Pages/Dashboard/Index',
            [
                'pageTitle' => 'Dashboard',

                'profileReference' =>
                $user['profile_ref_number']
                    ?? null,

                'loggedInUserName' =>
                $user['full_name']
                    ?? 'Member',

                'pageScripts' => [
                    'assets/js/pages/dashboard-security.js',
                ],
            ]
        );
    }
}
