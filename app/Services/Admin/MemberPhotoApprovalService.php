<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\AdminMemberPhotoApprovalModel;
use App\Models\MemberPhotoModel;
use App\Services\Admin\Audit\AdminAuditAction;
use App\Services\Admin\Audit\AdminAuditEvent;
use App\Services\Admin\Audit\AdminAuditService;
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
        private readonly AdminAuditService $auditService,
        private readonly BaseConnection $database
    ) {}

    /**
     * Return AJAX-ready member and photo details.
     *
     * Signed URLs are generated only when the modal is requested.
     *
     * @return array{
     *     member:array<string,mixed>,
     *     photos:list<array<string,mixed>>
     * }
     */
    public function getMemberPhotoReview(
        int $memberId
    ): array {
        if ($memberId <= 0) {
            throw new DomainException(
                'The member identifier is invalid.'
            );
        }

        $member = $this->approvalModel
            ->findMemberSummary($memberId);

        if ($member === null) {
            throw new DomainException(
                'The requested member was not found.'
            );
        }

        $photos = $this->approvalModel
            ->findPhotosForMember($memberId);

        foreach ($photos as &$photo) {
            $photo['signed_url'] = '';

            $objectKey = trim(
                (string) (
                    $photo['medium_object_key']
                    ?? ''
                )
            );

            if ($objectKey === '') {
                $objectKey = trim(
                    (string) (
                        $photo['original_object_key']
                        ?? ''
                    )
                );
            }

            if ($objectKey === '') {
                continue;
            }

            try {
                $photo['signed_url'] =
                    $this->cloudFrontService
                    ->signedUrl(
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

        return [
            'member' => $member,
            'photos' => $photos,
        ];
    }

    /**
     * Approve one pending photo.
     *
     * @return array<string, mixed>
     */
    public function approvePhoto(
        int $photoId,
        int $adminId
    ): array {
        $this->assertIdentifiers(
            $photoId,
            $adminId
        );

        $photo = $this->approvalModel
            ->findPendingPhoto($photoId);

        if ($photo === null) {
            throw new DomainException(
                'This photo is no longer pending approval.'
            );
        }

        $memberId = (int) (
            $photo['member_id'] ?? 0
        );

        $approvedAt = date('Y-m-d H:i:s');

        $this->database->transBegin();

        try {
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
                    'approved_at' => $approvedAt,
                    'rejected_by' => null,
                    'rejected_at' => null,
                    'rejection_reason' => null,
                    'updated_at' => $approvedAt,
                ])
                ->update();

            if (
                $updated !== true
                || $this->database->affectedRows() !== 1
            ) {
                throw new DomainException(
                    'The photo could not be approved.'
                );
            }

            $this->recordModerationAudit(
                action: AdminAuditAction::MEMBER_PHOTO_APPROVED,
                adminId: $adminId,
                memberId: $memberId,
                photoId: $photoId,
                description: 'Administrator approved a member photo.',
                metadata: [
                    'previous_status' => 'PENDING',
                    'new_status' => 'APPROVED',
                    'approved_at' => $approvedAt,
                ]
            );

            if (
                $this->database->transStatus() === false
            ) {
                throw new DomainException(
                    'The photo approval could not be completed.'
                );
            }

            $this->database->transCommit();

            return [
                'photoId' => $photoId,
                'memberId' => $memberId,
                'status' => 'APPROVED',
            ];
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }
    }

    /**
     * Reject one pending photo.
     *
     * Rejection reason storage is retained but the current UI does
     * not ask the administrator to enter a reason.
     *
     * @return array<string, mixed>
     */
    public function rejectPhoto(
        int $photoId,
        int $adminId,
        string $reason = ''
    ): array {
        $this->assertIdentifiers(
            $photoId,
            $adminId
        );

        $reason = trim($reason);

        if (mb_strlen($reason) > 500) {
            throw new DomainException(
                'The rejection reason cannot exceed 500 characters.'
            );
        }

        $photo = $this->approvalModel
            ->findPendingPhoto($photoId);

        if ($photo === null) {
            throw new DomainException(
                'This photo is no longer pending approval.'
            );
        }

        $memberId = (int) (
            $photo['member_id'] ?? 0
        );

        $rejectedAt = date('Y-m-d H:i:s');

        $this->database->transBegin();

        try {
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
                    'rejected_at' => $rejectedAt,
                    'rejection_reason' =>
                    $reason !== ''
                        ? $reason
                        : null,
                    'approved_by' => null,
                    'approved_at' => null,
                    'updated_at' => $rejectedAt,
                ])
                ->update();

            if (
                $updated !== true
                || $this->database->affectedRows() !== 1
            ) {
                throw new DomainException(
                    'The photo could not be rejected.'
                );
            }

            $this->recordModerationAudit(
                action: AdminAuditAction::MEMBER_PHOTO_REJECTED,
                adminId: $adminId,
                memberId: $memberId,
                photoId: $photoId,
                description: 'Administrator rejected a member photo.',
                metadata: [
                    'previous_status' => 'PENDING',
                    'new_status' => 'REJECTED',
                    'rejected_at' => $rejectedAt,
                    'rejection_reason' =>
                    $reason !== ''
                        ? $reason
                        : null,
                ]
            );

            if (
                $this->database->transStatus() === false
            ) {
                throw new DomainException(
                    'The photo rejection could not be completed.'
                );
            }

            $this->database->transCommit();

            return [
                'photoId' => $photoId,
                'memberId' => $memberId,
                'status' => 'REJECTED',
            ];
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }
    }

    /**
     * Approve all currently pending photos for one member.
     *
     * @return array{
     *     memberId:int,
     *     photoIds:list<int>,
     *     approvedCount:int
     * }
     */
    public function approvePendingForMember(
        int $memberId,
        int $adminId
    ): array {
        $this->assertIdentifiers(
            $memberId,
            $adminId
        );

        $photoIds = $this->approvalModel
            ->pendingPhotoIdsForMember($memberId);

        if ($photoIds === []) {
            throw new DomainException(
                'This member has no pending photos.'
            );
        }

        $approvedAt = date('Y-m-d H:i:s');

        $this->database->transBegin();

        try {
            $this->database
                ->table('member_photos')
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

            $approvedCount = $this->database
                ->affectedRows();

            if ($approvedCount <= 0) {
                throw new DomainException(
                    'No pending photos were approved.'
                );
            }

            $this->recordModerationAudit(
                action: AdminAuditAction::MEMBER_PHOTOS_BULK_APPROVED,
                adminId: $adminId,
                memberId: $memberId,
                photoId: null,
                description: 'Administrator approved all pending '
                    . 'photos for a member.',
                metadata: [
                    'photo_ids' => $photoIds,
                    'approved_count' => $approvedCount,
                    'approved_at' => $approvedAt,
                ]
            );

            if (
                $this->database->transStatus() === false
            ) {
                throw new DomainException(
                    'The bulk approval could not be completed.'
                );
            }

            $this->database->transCommit();

            return [
                'memberId' => $memberId,
                'photoIds' => $photoIds,
                'approvedCount' => $approvedCount,
            ];
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }
    }

    private function assertIdentifiers(
        int $targetId,
        int $adminId
    ): void {
        if ($targetId <= 0 || $adminId <= 0) {
            throw new DomainException(
                'The moderation request is invalid.'
            );
        }
    }

    /**
     * Record the moderation event in admin_audit_logs.
     *
     * A failed audit insert causes moderation to roll back so that
     * every completed moderation action remains traceable.
     *
     * @param array<string, mixed> $metadata
     */
    private function recordModerationAudit(
        string $action,
        int $adminId,
        int $memberId,
        ?int $photoId,
        string $description,
        array $metadata
    ): void {
        $this->auditService->record(
            new AdminAuditEvent(
                action: $action,
                outcome: 'SUCCESS',
                actorAdminId: $adminId,
                actorName: (string) (
                    session('admin_user_name') ?? ''
                ),
                actorRole: (string) (
                    session('admin_role') ?? ''
                ),
                targetType: 'MEMBER_PHOTO',
                targetId: $photoId ?? $memberId,
                targetLabel: $photoId !== null
                    ? 'Photo #' . $photoId
                    : 'Member #' . $memberId,
                description: $description,
                metadata: array_merge(
                    $metadata,
                    [
                        'member_id' => $memberId,
                        'photo_id' => $photoId,
                    ]
                )
            )
        );
    }
}
