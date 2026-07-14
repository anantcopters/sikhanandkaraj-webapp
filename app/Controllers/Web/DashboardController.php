<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;

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

        $loggedInUserName = trim(
            (string) ($user['full_name'] ?? '')
        );

        if ($loggedInUserName === '') {
            $loggedInUserName = 'Member';
        }

        $profileReference = trim(
            (string) ($user['profile_ref_number'] ?? '')
        );

        /**
         * Refresh the shared authenticated-session values so the
         * header on subsequent pages displays current information.
         */
        session()->set([
            'auth_user_name' => $loggedInUserName,
            'auth_profile_reference' => $profileReference,
        ]);

        return view(
            'Pages/Dashboard/Index',
            [
                'pageTitle' => 'Dashboard',

                'profileReference' =>
                $profileReference,

                'loggedInUserName' =>
                $loggedInUserName,

                'pageScripts' => [
                    'assets/js/pages/dashboard-security.js',
                ],
            ]
        );
    }
}
