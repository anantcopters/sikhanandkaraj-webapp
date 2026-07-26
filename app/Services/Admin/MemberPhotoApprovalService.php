<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\AdminMemberPhotoApprovalModel;
use App\Models\MemberPhotoModel;
use App\Services\Aws\CloudFrontService;
use CodeIgniter\Database\BaseConnection;
use Config\MemberMedia;
use DomainException;
use Throwable;

/**
 * Handles administrator photo review and moderation.
 */
final class MemberPhotoApprovalService
{
    public function __construct(
        private readonly AdminMemberPhotoApprovalModel $approvalModel,
        private readonly MemberPhotoModel $photoModel,
        private readonly CloudFrontService $cloudFrontService,
        private readonly MemberMedia $mediaConfig,
        private readonly BaseConnection $database
    ) {}

    /**
     * Return member photos with short-lived CloudFront URLs.
     *
     * @return list<array<string, mixed>>
     */
    public function getMemberPhotos(
        int $memberId
    ): array {
        $photos = $this->approvalModel
            ->findPhotosForMember($memberId);

        foreach ($photos as &$photo) {
            $objectKey = trim(
                (string) (
                    $photo['medium_object_key']
                    ?? $photo['original_object_key']
                    ?? ''
                )
            );

            $photo['signed_url'] = '';

            if ($objectKey === '') {
                continue;
            }

            try {
                $photo['signed_url'] =
                    $this->cloudFrontService->signedUrl(
                        $objectKey,
                        $this->mediaConfig
                            ->profileUrlTtlSeconds
                    );
            } catch (Throwable $exception) {
                log_message(
                    'error',
                    'Admin photo URL generation failed '
                        . 'for photo {photoId}: {message}',
                    [
                        'photoId' =>
                        (int) ($photo['id'] ?? 0),

                        'message' =>
                        $exception->getMessage(),
                    ]
                );
            }
        }

        unset($photo);

        return $photos;
    }

    /**
     * Approve one pending member photo.
     */
    public function approvePhoto(
        int $photoId,
        int $adminId
    ): void {
        if ($photoId <= 0 || $adminId <= 0) {
            throw new DomainException(
                'The approval request is invalid.'
            );
        }

        $this->database->transBegin();

        try {
            $photo = $this->approvalModel
                ->findPendingPhoto($photoId);

            if ($photo === null) {
                throw new DomainException(
                    'This photo is no longer pending approval.'
                );
            }

            $updated = $this->photoModel
                ->where(
                    'id',
                    $photoId
                )
                ->where(
                    'status',
                    'PENDING'
                )
                ->where(
                    'deleted_at',
                    null
                )
                ->set([
                    'status' => 'APPROVED',
                    'approved_by' => $adminId,
                    'approved_at' =>
                    date('Y-m-d H:i:s'),
                    'rejected_by' => null,
                    'rejected_at' => null,
                    'rejection_reason' => null,
                ])
                ->update();

            if ($updated !== true) {
                throw new DomainException(
                    'The photo could not be approved.'
                );
            }

            $this->database->transCommit();
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }
    }

    /**
     * Reject one pending member photo.
     */
    public function rejectPhoto(
        int $photoId,
        int $adminId,
        string $reason
    ): void {
        if ($photoId <= 0 || $adminId <= 0) {
            throw new DomainException(
                'The rejection request is invalid.'
            );
        }

        $reason = trim($reason);

        if (mb_strlen($reason) > 500) {
            throw new DomainException(
                'The rejection reason cannot exceed 500 characters.'
            );
        }

        $this->database->transBegin();

        try {
            $photo = $this->approvalModel
                ->findPendingPhoto($photoId);

            if ($photo === null) {
                throw new DomainException(
                    'This photo is no longer pending approval.'
                );
            }

            $updated = $this->photoModel
                ->where(
                    'id',
                    $photoId
                )
                ->where(
                    'status',
                    'PENDING'
                )
                ->where(
                    'deleted_at',
                    null
                )
                ->set([
                    'status' => 'REJECTED',
                    'is_primary' => false,
                    'rejected_by' => $adminId,
                    'rejected_at' =>
                    date('Y-m-d H:i:s'),
                    'rejection_reason' =>
                    $reason !== ''
                        ? $reason
                        : null,
                    'approved_by' => null,
                    'approved_at' => null,
                ])
                ->update();

            if ($updated !== true) {
                throw new DomainException(
                    'The photo could not be rejected.'
                );
            }

            $this->database->transCommit();
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }
    }

    /**
     * Approve every currently pending photo for one member.
     *
     * This is used by the row-level quick approval action.
     */
    public function approvePendingForMember(
        int $memberId,
        int $adminId
    ): int {
        if ($memberId <= 0 || $adminId <= 0) {
            throw new DomainException(
                'The member approval request is invalid.'
            );
        }

        if (
            !$this->approvalModel
                ->memberHasPendingPhotos($memberId)
        ) {
            throw new DomainException(
                'This member has no pending photos.'
            );
        }

        $approvedAt = date('Y-m-d H:i:s');

        $this->database->transBegin();

        try {
            $builder = $this->database
                ->table('member_photos');

            $builder
                ->where(
                    'member_id',
                    $memberId
                )
                ->where(
                    'status',
                    'PENDING'
                )
                ->where(
                    'deleted_at',
                    null
                )
                ->update([
                    'status' => 'APPROVED',
                    'approved_by' => $adminId,
                    'approved_at' => $approvedAt,
                    'rejected_by' => null,
                    'rejected_at' => null,
                    'rejection_reason' => null,
                    'updated_at' => $approvedAt,
                ]);

            $affectedRows = $this->database
                ->affectedRows();

            if ($affectedRows <= 0) {
                throw new DomainException(
                    'No pending photos were approved.'
                );
            }

            $this->database->transCommit();

            return $affectedRows;
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }
    }
}
