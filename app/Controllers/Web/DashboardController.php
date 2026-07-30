<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Models\UserContactModel;
use App\Models\UserModel;
use App\Services\Dashboard\MemberDashboardDataService;
use App\Services\Profile\MemberProfileSummaryService;
use App\Support\BooleanValue;
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

        $resolvedUserId = (int) $userId;

        $user = (new UserModel())->find(
            $resolvedUserId
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
            (string) (
                $user['profile_ref_number']
                ?? ''
            )
        );

        $contactModel = new UserContactModel();

        $emailContact = $contactModel->findPrimaryForUser(
            $resolvedUserId,
            UserContactModel::TYPE_EMAIL
        );

        $mobileContact = $contactModel->findPrimaryForUser(
            $resolvedUserId,
            UserContactModel::TYPE_MOBILE
        );

        $primaryEmail = $this->contactValue(
            $emailContact
        );

        $primaryMobile = $this->contactValue(
            $mobileContact
        );

        $isEmailVerified = $this->isContactVerified(
            $emailContact
        );

        $isMobileVerified = $this->isContactVerified(
            $mobileContact
        );

        /*
         * Keep shared authenticated-session values current for the header.
         */
        session()->set([
            'auth_user_name' =>
            $loggedInUserName,

            'auth_profile_reference' =>
            $profileReference,
        ]);

        /*
         * Dashboard-specific account and match datasets.
         */
        $dashboardData = (
            new MemberDashboardDataService()
        )->getDashboardData(
            $resolvedUserId
        );

        /*
         * Reuse exactly the same profile summary used by profile/edit.
         */
        /** @var MemberProfileSummaryService $profileSummaryService */
        $profileSummaryService = service(
            'memberProfileSummaryService'
        );

        $profileSummary = $profileSummaryService->getForUser(
            $resolvedUserId
        );

        return view(
            'Pages/Dashboard/Index',
            array_merge(
                [
                    'pageTitle' =>
                    'Member Dashboard',

                    'profileReference' =>
                    $profileReference,

                    'loggedInUserName' =>
                    $loggedInUserName,

                    'primaryEmail' =>
                    $primaryEmail,

                    'primaryMobile' =>
                    $primaryMobile,

                    'isEmailVerified' =>
                    $isEmailVerified,

                    'isMobileVerified' =>
                    $isMobileVerified,

                    /*
                     * Real profile data from the shared profile service.
                     */
                    'profileImage' =>
                    $profileSummary['profileImage'],

                    'profileCompletion' =>
                    $profileSummary['profileCompletion'],

                    'overallProfileSummary' =>
                    $profileSummary['overallProfileSummary'],

                    'profileShortcuts' =>
                    $profileSummary['profileSections'],

                    'nextProfileSection' =>
                    $profileSummary['nextProfileSection'],

                    'pageScripts' => [
                        'assets/js/pages/dashboard-security.js',
                    ],
                ],
                $dashboardData
            )
        );
    }

    /**
     * Resolve a stored contact value.
     *
     * @param array<string, mixed>|null $contact
     */
    private function contactValue(
        ?array $contact
    ): ?string {
        if (!is_array($contact)) {
            return null;
        }

        $value = trim(
            (string) (
                $contact['contact_value']
                ?? ''
            )
        );

        return $value !== ''
            ? $value
            : null;
    }

    /**
     * Resolve the verification state of a contact record.
     *
     * @param array<string, mixed>|null $contact
     */
    private function isContactVerified(
        ?array $contact
    ): bool {
        if (!is_array($contact)) {
            return false;
        }

        return BooleanValue::fromDatabase(
            $contact['is_verified'] ?? false
        );
    }
}
