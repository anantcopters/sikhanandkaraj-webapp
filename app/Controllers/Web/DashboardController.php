<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Models\UserContactModel;
use App\Support\BooleanValue;
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

        $emailContact = (
            new UserContactModel()
        )->findPrimaryForUser(
            (int) $userId,
            UserContactModel::TYPE_EMAIL
        );

        $primaryEmail = null;
        $isEmailVerified = false;

        if (is_array($emailContact)) {
            $primaryEmail = trim(
                (string) (
                    $emailContact['contact_value']
                    ?? ''
                )
            );

            $isEmailVerified =
                BooleanValue::fromDatabase(
                    $emailContact['is_verified']
                        ?? false
                );
        }

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

                'primaryEmail' => $primaryEmail,

                'isEmailVerified' => $isEmailVerified,

                'pageScripts' => [
                    'assets/js/pages/dashboard-security.js',
                ],
            ]
        );
    }
}
