<?php

declare(strict_types=1);

namespace App\Services\Prelaunch;

use App\Models\Prelaunch\PrelaunchPhotoModel;
use App\Models\Prelaunch\PrelaunchProfileModel;
use App\Services\Admin\Audit\AdminAuditEvent;
use App\Services\Admin\Audit\AdminAuditService;
use App\Services\Admin\Audit\AdminAuditAction;
use CodeIgniter\Database\BaseConnection;
use RuntimeException;
use Throwable;

/**
 * Handles admin review, photo decisions and contact corrections.
 */
final class PrelaunchAdminReviewService
{
    public function __construct(
        private readonly PrelaunchProfileModel $profileModel,
        private readonly PrelaunchPhotoModel $photoModel,
        private readonly AdminAuditService $auditService,
        private readonly BaseConnection $database
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function listProfiles(
        ?string $status = null
    ): array {
        return $this->profileModel
            ->listForAdmin($status);
    }

    /**
     * Return all local variables required by the review screen.
     *
     * @return array{
     *     profile: array<string, mixed>,
     *     photos: list<array<string, mixed>>,
     *     photoSummary: array{
     *         total: int,
     *         pending: int,
     *         approved: int,
     *         rejected: int,
     *         allApproved: bool
     *     }
     * }
     */
    public function reviewData(
        int $profileId
    ): array {
        $profile = $this->profileModel
            ->findForAdmin(
                $profileId
            );

        if ($profile === null) {
            throw new RuntimeException(
                'The pre-launch profile was not found.'
            );
        }

        $photos = $this->photoModel
            ->findByProfile(
                $profileId
            );

        $approvedCount = 0;
        $rejectedCount = 0;
        $pendingCount = 0;

        foreach ($photos as $photo) {
            $status = (string) (
                $photo['approval_status']
                ?? ''
            );

            if (
                $status
                === PrelaunchPhotoModel::STATUS_APPROVED
            ) {
                $approvedCount++;
                continue;
            }

            if (
                $status
                === PrelaunchPhotoModel::STATUS_REJECTED
            ) {
                $rejectedCount++;
                continue;
            }

            $pendingCount++;
        }

        return [
            'profile' =>
            $profile,

            'photos' =>
            $photos,

            'photoSummary' => [
                'total' =>
                count($photos),

                'pending' =>
                $pendingCount,

                'approved' =>
                $approvedCount,

                'rejected' =>
                $rejectedCount,

                'allApproved' =>
                count($photos) === 3
                    && $approvedCount === 3,
            ],
        ];
    }

    public function approveProfile(
        int $profileId,
        int $adminUserId
    ): void {
        $profile = $this->requireDraftProfile(
            $profileId
        );

        $photos = $this->photoModel
            ->findByProfile($profileId);

        if (count($photos) !== 3) {
            throw new RuntimeException(
                'The profile must contain exactly three photographs.'
            );
        }

        foreach ($photos as $photo) {
            if (
                (string) $photo['approval_status']
                !== PrelaunchPhotoModel::STATUS_APPROVED
            ) {
                throw new RuntimeException(
                    'All three photographs must be approved first.'
                );
            }
        }

        $this->profileModel->update(
            $profileId,
            [
                'status' =>
                PrelaunchProfileModel::STATUS_APPROVED,

                'reviewed_by' =>
                $adminUserId,

                'reviewed_at' =>
                date('Y-m-d H:i:s'),

                'rejection_reason' =>
                null,
            ]
        );

        $this->recordAudit(
            AdminAuditAction::PRELAUNCH_PROFILE_APPROVED,
            $profileId,
            (string) $profile['profile_reference'],
            [
                'status' =>
                $profile['status'],
            ],
            [
                'status' =>
                PrelaunchProfileModel::STATUS_APPROVED,
            ],
            'The pre-launch profile was approved.'
        );
    }

    public function rejectProfile(
        int $profileId,
        string $reason,
        int $adminUserId
    ): void {
        $profile = $this->requireDraftProfile(
            $profileId
        );

        $reason = trim($reason);

        if (mb_strlen($reason) < 5) {
            throw new RuntimeException(
                'Please enter a meaningful rejection reason.'
            );
        }

        $this->profileModel->update(
            $profileId,
            [
                'status' =>
                PrelaunchProfileModel::STATUS_REJECTED,

                'reviewed_by' =>
                $adminUserId,

                'reviewed_at' =>
                date('Y-m-d H:i:s'),

                'rejection_reason' =>
                $reason,
            ]
        );

        $this->recordAudit(
            AdminAuditAction::PRELAUNCH_PROFILE_REJECTED,
            $profileId,
            (string) $profile['profile_reference'],
            [
                'status' =>
                $profile['status'],
            ],
            [
                'status' =>
                PrelaunchProfileModel::STATUS_REJECTED,
                'rejection_reason' => $reason,
            ],
            'The pre-launch profile was rejected.'
        );
    }

    public function updatePhotoStatus(
        int $photoId,
        string $status,
        ?string $reason,
        int $adminUserId
    ): void {
        $allowed = [
            PrelaunchPhotoModel::STATUS_APPROVED,
            PrelaunchPhotoModel::STATUS_REJECTED,
        ];

        if (!in_array($status, $allowed, true)) {
            throw new RuntimeException(
                'Invalid photograph decision.'
            );
        }

        $photo = $this->photoModel->find($photoId);

        if (!is_array($photo)) {
            throw new RuntimeException(
                'The photograph was not found.'
            );
        }

        $profile = $this->requireDraftProfile(
            (int) $photo['prelaunch_profile_id']
        );

        $reason = trim((string) $reason);

        if (
            $status === PrelaunchPhotoModel::STATUS_REJECTED
            && mb_strlen($reason) < 5
        ) {
            throw new RuntimeException(
                'Please provide a photograph rejection reason.'
            );
        }

        $this->photoModel->update(
            $photoId,
            [
                'approval_status' =>
                $status,

                'rejection_reason' =>
                $status
                    === PrelaunchPhotoModel::STATUS_REJECTED
                    ? $reason
                    : null,

                'reviewed_by' =>
                $adminUserId,

                'reviewed_at' =>
                date('Y-m-d H:i:s'),
            ]
        );

        $this->recordAudit(
            AdminAuditAction::PRELAUNCH_PHOTO_STATUS_CHANGED,
            $photoId,
            (string) $profile['profile_reference'],
            [
                'approval_status' =>
                $photo['approval_status'],
            ],
            [
                'approval_status' =>
                $status,
            ],
            'A pre-launch profile photograph decision was recorded.'
        );
    }

    /**
     * Admin may change only contact fields.
     *
     * @param array<string, mixed> $input
     */
    public function updateContact(
        int $profileId,
        array $input,
        int $adminUserId
    ): void {
        $profile = $this->profileModel->find(
            $profileId
        );

        if (!is_array($profile)) {
            throw new RuntimeException(
                'The pre-launch profile was not found.'
            );
        }

        $email = mb_strtolower(
            trim((string) ($input['email'] ?? ''))
        );

        $countryCode = trim(
            (string) ($input['country_code'] ?? '')
        );

        $mobileNumber = preg_replace(
            '/\D+/',
            '',
            (string) ($input['mobile_number'] ?? '')
        ) ?? '';

        if (
            $this->profileModel->emailExists(
                $email,
                $profileId
            )
        ) {
            throw new RuntimeException(
                'Another pre-launch profile already uses this email.'
            );
        }

        if (
            $this->profileModel->mobileExists(
                $countryCode,
                $mobileNumber,
                $profileId
            )
        ) {
            throw new RuntimeException(
                'Another pre-launch profile already uses this mobile number.'
            );
        }

        $beforeData = [
            'email' =>
            $this->maskEmail(
                (string) $profile['email']
            ),

            'country_code' =>
            $profile['country_code'],

            'mobile_number' =>
            $this->maskMobile(
                (string) $profile['mobile_number']
            ),
        ];

        $afterData = [
            'email' =>
            $this->maskEmail($email),

            'country_code' =>
            $countryCode,

            'mobile_number' =>
            $this->maskMobile($mobileNumber),
        ];

        $this->database->transBegin();

        try {
            $this->profileModel->update(
                $profileId,
                [
                    'email' => $email,
                    'country_code' => $countryCode,
                    'mobile_number' => $mobileNumber,
                ]
            );

            if (
                $this->database->transStatus()
                === false
            ) {
                throw new RuntimeException(
                    'The contact update transaction failed.'
                );
            }

            $this->database->transCommit();

            $this->recordAudit(
                AdminAuditAction::PRELAUNCH_PROFILE_CONTACT_UPDATED,
                $profileId,
                (string) $profile['profile_reference'],
                $beforeData,
                $afterData,
                'Administrator updated the pre-launch profile contact details.'
            );
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function requireDraftProfile(
        int $profileId
    ): array {
        $profile = $this->profileModel->find(
            $profileId
        );

        if (!is_array($profile)) {
            throw new RuntimeException(
                'The pre-launch profile was not found.'
            );
        }

        if (
            (string) $profile['status']
            !== PrelaunchProfileModel::STATUS_DRAFT
        ) {
            throw new RuntimeException(
                'Only DRAFT profiles can be reviewed.'
            );
        }

        return $profile;
    }

    /**
     * Record a pre-launch administrator action using the existing
     * centralized administrator audit service.
     *
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     */
    private function recordAudit(
        string $action,
        int $targetId,
        string $targetLabel,
        array $before,
        array $after,
        string $description
    ): void {
        $this->auditService->record(
            new AdminAuditEvent(
                action: $action,
                targetType: 'PRELAUNCH_PROFILE',
                targetId: $targetId,
                targetLabel: $targetLabel,
                description: $description,
                beforeData: $before,
                afterData: $after
            )
        );
    }

    private function maskEmail(
        string $email
    ): string {
        [$name, $domain] = array_pad(
            explode('@', $email, 2),
            2,
            ''
        );

        return mb_substr($name, 0, 2)
            . '***@'
            . $domain;
    }

    private function maskMobile(
        string $mobile
    ): string {
        return str_repeat(
            '*',
            max(0, strlen($mobile) - 4)
        ) . substr($mobile, -4);
    }
}
