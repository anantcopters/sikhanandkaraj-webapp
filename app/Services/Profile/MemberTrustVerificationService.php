<?php

declare(strict_types=1);

namespace App\Services\Profile;

use App\Models\UserContactModel;
use App\Models\UserModel;
use App\Support\BooleanValue;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Builds the authenticated member's Trust and Verification dataset.
 *
 * Dashboard and Profile Edit must use this service so contact,
 * Aadhaar and selfie verification states cannot drift between screens.
 */
final class MemberTrustVerificationService
{
    public function __construct(
        private readonly UserModel $userModel,
        private readonly UserContactModel $contactModel,
        private readonly MemberAadhaarService $aadhaarService
    ) {}

    /**
     * Return presentation-ready Trust and Verification information.
     *
     * @return array{
     *     memberName:string,
     *     profileReference:string,
     *     mobile:array{
     *         value:?string,
     *         isAdded:bool,
     *         isVerified:bool
     *     },
     *     email:array{
     *         value:?string,
     *         isAdded:bool,
     *         isVerified:bool
     *     },
     *     aadhaar:array{
     *         status:string,
     *         isVerified:bool,
     *         rejectionReason:string
     *     },
     *     selfie:array{
     *         isVerified:bool
     *     }
     * }
     */
    public function getForUser(
        int $userId
    ): array {
        $user = $this->userModel->find(
            $userId
        );

        if (!is_array($user)) {
            throw PageNotFoundException::forPageNotFound();
        }

        $mobileContact = $this
            ->contactModel
            ->findPrimaryForUser(
                $userId,
                UserContactModel::TYPE_MOBILE
            );

        $emailContact = $this
            ->contactModel
            ->findPrimaryForUser(
                $userId,
                UserContactModel::TYPE_EMAIL
            );

        $mobileValue = $this->contactValue(
            $mobileContact
        );

        $emailValue = $this->contactValue(
            $emailContact
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

            'email' => [
                'value' =>
                $emailValue,

                'isAdded' =>
                $emailValue !== null,

                'isVerified' =>
                $this->contactIsVerified(
                    $emailContact
                ),
            ],

            'aadhaar' => [
                'status' =>
                $aadhaarStatus,

                'isVerified' =>
                $aadhaarStatus === 'APPROVED',

                'rejectionReason' =>
                trim(
                    (string) (
                        $aadhaarState['rejectionReason']
                        ?? ''
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
     * PostgreSQL boolean values must use the project's BooleanValue support.
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
