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
        $resolvedUserId =
            $this->authenticatedUserId();

        $user =
            (new UserModel())->find(
                $resolvedUserId
            );

        if (!is_array($user)) {
            session()->destroy();

            throw PageNotFoundException::forPageNotFound();
        }

        $loggedInUserName =
            trim(
                (string) (
                    $user['full_name']
                    ?? ''
                )
            );

        if ($loggedInUserName === '') {
            $loggedInUserName =
                'Member';
        }

        $profileReference =
            trim(
                (string) (
                    $user['profile_ref_number']
                    ?? ''
                )
            );

        /*
         * ------------------------------------------------------------------
         * Member contacts
         * ------------------------------------------------------------------
         *
         * Mobile and email remain in user_contacts.
         * Do not duplicate either value on users.
         */
        $contactModel =
            new UserContactModel();

        $mobileContact =
            $contactModel->findPrimaryForUser(
                $resolvedUserId,
                UserContactModel::TYPE_MOBILE
            );

        $emailContact =
            $contactModel->findPrimaryForUser(
                $resolvedUserId,
                UserContactModel::TYPE_EMAIL
            );

        $primaryMobile =
            $this->contactValue(
                $mobileContact
            );

        $primaryEmail =
            $this->contactValue(
                $emailContact
            );

        $isMobileVerified =
            $this->isContactVerified(
                $mobileContact
            );

        $isEmailVerified =
            $this->isContactVerified(
                $emailContact
            );

        /*
         * ------------------------------------------------------------------
         * Member identity verification
         * ------------------------------------------------------------------
         *
         * PostgreSQL boolean values must always pass through the project's
         * existing BooleanValue support class.
         */
        $isAadhaarVerified =
            BooleanValue::fromDatabase(
                $user['is_aadhaar_verified']
                    ?? false
            );

        $isSelfieVerified =
            BooleanValue::fromDatabase(
                $user['is_selfie_verified']
                    ?? false
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
        /** @var MemberDashboardDataService $dashboardService */
        $dashboardService =
            service(
                'memberDashboardDataService'
            );

        $dashboardData =
            $dashboardService
            ->getDashboardData(
                $resolvedUserId
            );

        /*
         * Reuse exactly the same profile summary used by profile/edit.
         */
        /** @var MemberProfileSummaryService $profileSummaryService */
        $profileSummaryService =
            service(
                'memberProfileSummaryService'
            );

        $profileSummary =
            $profileSummaryService
            ->getForUser(
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

                    'gender' =>
                    trim(
                        (string) (
                            $user['gender']
                            ?? ''
                        )
                    ),

                    /*
                     * Contact verification.
                     */
                    'primaryMobile' =>
                    $primaryMobile,

                    'isMobileVerified' =>
                    $isMobileVerified,

                    'primaryEmail' =>
                    $primaryEmail,

                    'isEmailVerified' =>
                    $isEmailVerified,

                    /*
                     * Identity verification.
                     */
                    'isAadhaarVerified' =>
                    $isAadhaarVerified,

                    'isSelfieVerified' =>
                    $isSelfieVerified,

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
                        'assets/js/pages/dashboard-matches.js',
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

        $value =
            trim(
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
            $contact['is_verified']
                ?? false
        );
    }
}
