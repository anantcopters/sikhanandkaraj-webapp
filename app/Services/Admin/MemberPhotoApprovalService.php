<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\AdminMemberPhotoApprovalModel;
use App\Models\MemberPhotoModel;
use App\Services\Admin\Audit\AdminAuditAction;
use App\Services\Admin\Audit\AdminAuditEvent;
use App\Services\Admin\Audit\AdminAuditService;
use App\Services\Aws\CloudFrontService;
use App\Models\MemberNotificationModel;
use App\Services\Notification\MemberNotificationService;
use CodeIgniter\Database\BaseConnection;
use App\Support\BooleanValue;
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
        private readonly MemberNotificationService $notificationService,
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
            $photoId = (int) (
                $photo['id'] ?? 0
            );

            /*
            * PostgreSQL boolean values may be returned by the driver as
            * booleans, integers or the strings "t"/"f". Normalize the value
            * before JSON encoding so JavaScript receives true or false.
            */
            $photo['is_primary'] = BooleanValue::fromDatabase(
                $photo['is_primary'] ?? false
            );

            $photo['signed_url'] = '';

            /*
            * Provide explicit moderation endpoints to the AJAX client.
            *
            * Without these URLs, a dynamically generated form has an empty
            * action and the browser submits it to the current listing URL.
            */
            $photo['approve_url'] =
                $photoId > 0
                ? route_to(
                    'admin.members.photos.approve',
                    $photoId
                )
                : '';

            $photo['reject_url'] =
                $photoId > 0
                ? route_to(
                    'admin.members.photos.reject',
                    $photoId
                )
                : '';

            $objectKey = trim(
                (string) (
                    $photo['medium_object_key']
                    ?? ''
                )
            );

            /*
            * Administrative photo moderation must use the medium variant.
            * Do not fall back to the original because it may be unnecessarily
            * large and is reserved for explicit high-resolution requirements.
            */
            if ($objectKey === '') {
                log_message(
                    'error',
                    'Medium photo object key is unavailable for '
                        . 'photo {photoId}.',
                    [
                        'photoId' => $photoId,
                    ]
                );

                continue;
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
     * Reject one pending member photo and notify its owner.
     *
     * The photo update, audit record and member notification are committed
     * together. If any operation fails, the complete transaction is rolled back.
     *
     * Rejection reason storage is retained, although the current administrator
     * UI does not require a reason.
     *
     * @return array{
     *     photoId:int,
     *     memberId:int,
     *     notificationId:int,
     *     status:string
     * }
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

        /*
     * member_photos.member_id stores the owning users.id value.
     * It is therefore also the recipient_user_id used by
     * member_notifications.
     */
        $memberId = (int) (
            $photo['member_id'] ?? 0
        );

        if ($memberId <= 0) {
            throw new DomainException(
                'The photo owner could not be identified.'
            );
        }

        $rejectedAt = date('Y-m-d H:i:s');

        /*
     * The current administrator UI does not collect a reason. Use a helpful
     * generic message in that case, while supporting a supplied reason for
     * future moderation workflows.
     */
        $notificationMessage = $reason !== ''
            ? 'Your profile photo was not approved. Reason: '
            . $reason
            : 'Your profile photo was not approved. Please upload a clear, '
            . 'recent photo that follows the profile photo guidelines.';

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

            /*
         * actor_user_id is intentionally omitted.
         *
         * member_notifications.actor_user_id references users.id, whereas
         * $adminId belongs to admin_users. Saving the administrator ID in
         * actor_user_id would violate the foreign-key relationship.
         */
            $notificationId = $this->notificationService
                ->create([
                    'recipientUserId' => $memberId,
                    'type' =>
                    MemberNotificationModel::TYPE_PHOTO_REJECTED,
                    'title' => 'Profile photo not approved',
                    'message' => $notificationMessage,
                    'entityType' => 'MEMBER_PHOTO',
                    'entityId' => $photoId,
                    'targetUrl' => '/profile/photos',
                ]);

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
                'notificationId' => $notificationId,
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
