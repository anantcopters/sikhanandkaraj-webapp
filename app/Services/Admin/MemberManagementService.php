<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\MemberAccountStatusHistoryModel;
use App\Models\UserModel;
use App\Services\Admin\Audit\AdminAuditEvent;
use App\Services\Admin\Audit\AdminAuditService;
use App\Services\Profile\MemberPhotoUrlService;
use App\Services\Profile\MemberProfileSummaryService;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Exceptions\PageNotFoundException;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * Administrator member listing, profile review and status-transition service.
 */
final class MemberManagementService
{
    public function __construct(
        private readonly BaseConnection $database,
        private readonly UserModel $userModel,
        private readonly MemberAccountStatusHistoryModel $historyModel,
        private readonly MemberProfileSummaryService $profileSummaryService,
        private readonly MemberPhotoUrlService $photoUrlService,
        private readonly AdminAuditService $auditService
    ) {}

    /**
     * Return a searchable and status-filtered member page.
     *
     * @return array{
     *     members:list<array<string, mixed>>,
     *     pager:\CodeIgniter\Pager\Pager,
     *     status:string,
     *     search:string,
     *     perPage:int
     * }
     */
    public function paginatedMembers(
        string $status,
        string $search,
        int $perPage = 10
    ): array {
        $normalizedStatus = mb_strtoupper(
            trim($status)
        );

        if (
            !in_array(
                $normalizedStatus,
                [
                    'ALL',
                    UserModel::STATUS_PENDING,
                    UserModel::STATUS_ACTIVE,
                    UserModel::STATUS_SUSPENDED,
                    UserModel::STATUS_DELETED,
                ],
                true
            )
        ) {
            $normalizedStatus = 'ALL';
        }

        $normalizedSearch = preg_replace(
            '/\s+/u',
            ' ',
            trim($search)
        ) ?? '';

        $normalizedSearch = mb_substr(
            $normalizedSearch,
            0,
            100
        );

        $safePerPage = max(
            5,
            min($perPage, 50)
        );

        $this->userModel
            ->prepareAdminMemberListing(
                $normalizedStatus,
                $normalizedSearch
            );

        $members = $this->userModel
            ->paginate(
                $safePerPage,
                'adminMembers'
            );

        return [
            'members' =>
            is_array($members)
                ? $members
                : [],

            'pager' =>
            $this->userModel->pager,

            'status' =>
            $normalizedStatus,

            'search' =>
            $normalizedSearch,

            'perPage' =>
            $safePerPage,
        ];
    }

    /**
     * Return one administrator-visible member.
     *
     * @return array<string, mixed>
     */
    public function memberForAdmin(
        int $userId
    ): array {
        $member = $this->userModel
            ->findForAdmin(
                $userId
            );

        if (!is_array($member)) {
            throw PageNotFoundException
                ::forPageNotFound();
        }

        return $member;
    }

    /**
     * Return the Admin profile-view contract.
     *
     * @return array<string, mixed>
     */
    public function profilePreview(
        int $userId
    ): array {
        $member = $this->memberForAdmin(
            $userId
        );

        $summary = $this->profileSummaryService
            ->getForUser(
                $userId
            );

        $adminPhotos = $this->photoUrlService
            ->getAdminThumbnailPhotos(
                $userId
            );

        return array_merge(
            $summary,
            [
                'adminMember' =>
                $member,

                'adminPhotos' =>
                $adminPhotos,
            ]
        );
    }

    /**
     * Return administrator-authorized modal photo URLs.
     *
     * @return array{
     *     photoId:int,
     *     originalUrl:string,
     *     mediumUrl:string
     * }
     */
    public function adminPhotoModalUrls(
        int $userId,
        int $photoId
    ): array {
        $this->memberForAdmin(
            $userId
        );

        return $this->photoUrlService
            ->getAdminModalUrls(
                $userId,
                $photoId
            );
    }

    /**
     * Block an active member.
     */
    public function block(
        int $userId,
        string $reason,
        int $adminUserId
    ): void {
        $this->changeStatus(
            userId: $userId,
            expectedStatus: UserModel::STATUS_ACTIVE,
            newStatus: UserModel::STATUS_SUSPENDED,
            action: MemberAccountStatusHistoryModel::ACTION_BLOCK,
            reason: $reason,
            adminUserId: $adminUserId
        );
    }

    /**
     * Unblock a suspended member.
     */
    public function unblock(
        int $userId,
        string $reason,
        int $adminUserId
    ): void {
        $this->changeStatus(
            userId: $userId,
            expectedStatus: UserModel::STATUS_SUSPENDED,
            newStatus: UserModel::STATUS_ACTIVE,
            action: MemberAccountStatusHistoryModel::ACTION_UNBLOCK,
            reason: $reason,
            adminUserId: $adminUserId
        );
    }

    /**
     * Return block/unblock history.
     *
     * @return array{
     *     member:array<string, mixed>,
     *     history:list<array<string, mixed>>
     * }
     */
    public function history(
        int $userId
    ): array {
        return [
            'member' =>
            $this->memberForAdmin(
                $userId
            ),

            'history' =>
            $this->historyModel
                ->forUser(
                    $userId
                ),
        ];
    }

    /**
     * Persist one locked, constraint-backed status transition.
     */
    private function changeStatus(
        int $userId,
        string $expectedStatus,
        string $newStatus,
        string $action,
        string $reason,
        int $adminUserId
    ): void {
        if (
            $userId <= 0
            || $adminUserId <= 0
        ) {
            throw new DomainException(
                'A valid member and administrator are required.'
            );
        }

        $normalizedReason = preg_replace(
            '/\s+/u',
            ' ',
            trim($reason)
        ) ?? '';

        if (
            $normalizedReason === ''
            || mb_strlen($normalizedReason) > 64
        ) {
            throw new DomainException(
                'Please enter a reason of no more than 64 characters.'
            );
        }

        $member = null;

        $this->database->transBegin();

        try {
            $member = $this->userModel
                ->findForStatusUpdate(
                    $userId
                );

            if (!is_array($member)) {
                throw PageNotFoundException
                    ::forPageNotFound();
            }

            $currentStatus = mb_strtoupper(
                trim(
                    (string) (
                        $member['account_status']
                        ?? ''
                    )
                )
            );

            if ($currentStatus !== $expectedStatus) {
                throw new DomainException(
                    $action
                        === MemberAccountStatusHistoryModel::ACTION_BLOCK
                        ? 'Only an active member can be blocked.'
                        : 'Only a blocked member can be unblocked.'
                );
            }

            if (
                $this->userModel->update(
                    $userId,
                    [
                        'account_status' =>
                        $newStatus,
                    ]
                ) === false
            ) {
                throw new RuntimeException(
                    'The member status could not be updated.'
                );
            }

            $historyId = $this->historyModel
                ->insert(
                    [
                        'user_id' =>
                        $userId,

                        'action' =>
                        $action,

                        'previous_status' =>
                        $currentStatus,

                        'new_status' =>
                        $newStatus,

                        'reason' =>
                        $normalizedReason,

                        'changed_by_admin_id' =>
                        $adminUserId,

                        'changed_at' =>
                        gmdate('Y-m-d H:i:s')
                            . '+00:00',
                    ],
                    true
                );

            if (!is_numeric($historyId)) {
                throw new RuntimeException(
                    'The member status history could not be recorded.'
                );
            }

            if (
                $this->database->transStatus()
                === false
            ) {
                throw new RuntimeException(
                    'The member status transaction failed.'
                );
            }

            $this->database->transCommit();
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }

        $this->auditService->record(
            new AdminAuditEvent(
                action: 'MEMBER_' . $action,

                outcome: 'SUCCESS',

                actorAdminId: $adminUserId,

                targetType: 'MEMBER',

                targetId: $userId,

                targetLabel: (string) (
                    $member['profile_ref_number']
                    ?? ''
                ),

                description: sprintf(
                    'Member account changed from %s to %s.',
                    $expectedStatus,
                    $newStatus
                ),

                beforeData: [
                    'account_status' =>
                    $expectedStatus,
                ],

                afterData: [
                    'account_status' =>
                    $newStatus,
                ],

                metadata: [
                    'reason' =>
                    $normalizedReason,
                ]
            )
        );
    }
}
