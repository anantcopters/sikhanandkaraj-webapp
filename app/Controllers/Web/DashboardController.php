<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Services\Dashboard\MemberDashboardDataService;
use App\Services\Profile\MemberProfileSummaryService;
use App\Services\Profile\MemberTrustVerificationService;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Displays the authenticated member dashboard.
 */
final class DashboardController extends BaseController
{
    public function index(): string
    {
        $userId = $this->authenticatedUserId();

        $user = (new UserModel())->find(
            $userId
        );

        if (!is_array($user)) {
            session()->destroy();

            throw PageNotFoundException::forPageNotFound();
        }

        /** @var MemberTrustVerificationService $trustService */
        $trustService = service(
            'memberTrustVerificationService'
        );

        $trustVerification = $trustService
            ->getForUser(
                $userId
            );

        $loggedInUserName = trim(
            (string) (
                $trustVerification['memberName']
                ?? 'Member'
            )
        );

        $profileReference = trim(
            (string) (
                $trustVerification['profileReference']
                ?? ''
            )
        );

        session()->set([
            'auth_user_name' =>
            $loggedInUserName,

            'auth_profile_reference' =>
            $profileReference,
        ]);

        /** @var MemberDashboardDataService $dashboardService */
        $dashboardService = service(
            'memberDashboardDataService'
        );

        $dashboardData = $dashboardService
            ->getDashboardData(
                $userId
            );

        /** @var MemberProfileSummaryService $profileSummaryService */
        $profileSummaryService = service(
            'memberProfileSummaryService'
        );

        $profileSummary = $profileSummaryService
            ->getForUser(
                $userId
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

                    'gender' =>
                    trim(
                        (string) (
                            $user['gender']
                            ?? ''
                        )
                    ),

                    'trustVerification' =>
                    $trustVerification,

                    'aadhaarValidationErrors' =>
                    session(
                        'aadhaarValidationErrors'
                    ) ?? [],

                    'openAadhaarModal' =>
                    session(
                        'openAadhaarModal'
                    ) === true,

                    'formAlert' =>
                    $this->readFormAlert(),

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
                        'assets/js/components/submit-loader.js',
                        'assets/js/pages/dashboard-security.js',
                        'assets/js/pages/dashboard-matches.js',
                        'assets/js/pages/member-aadhaar.js',
                    ],
                ],
                $dashboardData
            )
        );
    }
}
