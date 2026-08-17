<?php

declare(strict_types=1);

namespace App\Services\Profile;

use App\Models\UserContactModel;
use App\Models\UserModel;
use App\Services\Account\MemberAccountSettingsService;
use App\Support\BooleanValue;
use CodeIgniter\Exceptions\PageNotFoundException;

final class MemberTrustVerificationService
{
    public function __construct(
        private readonly UserModel $userModel,
        private readonly UserContactModel $contactModel,
        private readonly MemberAadhaarService $aadhaarService,
        private readonly MemberAccountSettingsService
        $accountSettingsService
    ) {}

    /**
     * Return presentation-ready Trust and Verification information.
     *
     * Account Settings remains the authority for email state.
     *
     * @return array<string, mixed>
     */
    public function getForUser(
        int $userId
    ): array {
        $user = $this->userModel->find(
            $userId
        );

        if (!is_array($user)) {
            throw PageNotFoundException
                ::forPageNotFound();
        }

        $mobileContact = $this
            ->contactModel
            ->findPrimaryForUser(
                $userId,
                UserContactModel::TYPE_MOBILE
            );

        /*
         * Do not independently derive email state here.
         *
         * Account Settings handles:
         * - no email;
         * - unverified primary email;
         * - verified primary email;
         * - pending replacement email.
         */
        $accountSettings = $this
            ->accountSettingsService
            ->settingsForUser(
                $userId
            );

        $primaryEmail =
            isset($accountSettings['primaryEmail'])
            && is_array(
                $accountSettings['primaryEmail']
            )
            ? $accountSettings['primaryEmail']
            : null;

        $pendingEmail =
            isset($accountSettings['pendingEmail'])
            && is_array(
                $accountSettings['pendingEmail']
            )
            ? $accountSettings['pendingEmail']
            : null;

        $emailState = $this
            ->emailState(
                $primaryEmail,
                $pendingEmail
            );

        $mobileValue = $this->contactValue(
            $mobileContact
        );

        $aadhaarState = $this
            ->aadhaarService
            ->dashboardState(
                $userId
            );

        $aadhaarStatus = mb_strtoupper(
            trim(
                (string) (
                    $aadhaarState['status']
                    ?? 'NOT_ADDED'
                )
            )
        );

        if ($aadhaarStatus === '') {
            $aadhaarStatus = 'NOT_ADDED';
        }

        $memberName = trim(
            (string) (
                $user['full_name']
                ?? ''
            )
        );

        if ($memberName === '') {
            $memberName = 'Member';
        }

        return [
            'memberName' =>
            $memberName,

            'profileReference' =>
            trim(
                (string) (
                    $user['profile_ref_number']
                    ?? ''
                )
            ),

            'mobile' => [
                'value' =>
                $mobileValue,

                'isAdded' =>
                $mobileValue !== null,

                'isVerified' =>
                $this->contactIsVerified(
                    $mobileContact
                ),
            ],

            /*
             * This state is derived entirely from Account Settings.
             */
            'email' =>
            $emailState,

            'aadhaar' => [
                'status' =>
                $aadhaarStatus,

                'isVerified' =>
                $aadhaarStatus === 'APPROVED',

                'rejectionReason' =>
                trim(
                    (string) (
                        $aadhaarState['rejectionReason'] ?? ''
                    )
                ),
            ],

            'selfie' => [
                'isVerified' =>
                BooleanValue::fromDatabase(
                    $user['is_selfie_verified']
                        ?? false
                ),
            ],
        ];
    }

    /**
     * Build Trust-card email state from Account Settings presentation.
     *
     * Pending replacement has priority so the member sees the email
     * currently awaiting verification.
     *
     * @param array<string, mixed>|null $primaryEmail
     * @param array<string, mixed>|null $pendingEmail
     *
     * @return array{
     *     value:?string,
     *     isAdded:bool,
     *     isVerified:bool,
     *     status:string,
     *     statusLabel:string
     * }
     */
    private function emailState(
        ?array $primaryEmail,
        ?array $pendingEmail
    ): array {
        if (is_array($pendingEmail)) {
            $pendingValue = $this
                ->accountEmailValue(
                    $pendingEmail
                );

            if ($pendingValue !== null) {
                return [
                    'value' =>
                    $pendingValue,

                    'isAdded' =>
                    true,

                    'isVerified' =>
                    false,

                    'status' =>
                    'PENDING',

                    'statusLabel' =>
                    'Verification pending',
                ];
            }
        }

        if (is_array($primaryEmail)) {
            $primaryValue = $this
                ->accountEmailValue(
                    $primaryEmail
                );

            if ($primaryValue !== null) {
                $isVerified =
                    ($primaryEmail['isVerified']
                        ?? false) === true;

                return [
                    'value' =>
                    $primaryValue,

                    'isAdded' =>
                    true,

                    'isVerified' =>
                    $isVerified,

                    'status' =>
                    $isVerified
                        ? 'VERIFIED'
                        : 'PENDING',

                    'statusLabel' =>
                    $isVerified
                        ? 'Verified'
                        : 'Verification pending',
                ];
            }
        }

        return [
            'value' =>
            null,

            'isAdded' =>
            false,

            'isVerified' =>
            false,

            'status' =>
            'NOT_ADDED',

            'statusLabel' =>
            'Not added',
        ];
    }

    /**
     * @param array<string, mixed> $email
     */
    private function accountEmailValue(
        array $email
    ): ?string {
        $value = trim(
            (string) (
                $email['email']
                ?? ''
            )
        );

        return $value !== ''
            ? $value
            : null;
    }

    /**
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
     * PostgreSQL boolean values must use BooleanValue.
     *
     * @param array<string, mixed>|null $contact
     */
    private function contactIsVerified(
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
