<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\MemberAccountStatusHistoryModel;
use App\Models\UserModel;
use App\Services\Admin\Audit\AdminAuditAction;
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
     * Persist one locked, constraint-backed member status transition.
     *
     * The business transaction is completed first. A successful audit is then
     * written after commit so an audit-table problem cannot roll back a valid
     * member status change.
     */
    private function changeStatus(
        int $userId,
        string $expectedStatus,
        string $newStatus,
        string $action,
        string $reason,
        int $adminUserId
    ): void {
        $isBlockAction =
            $action
            === MemberAccountStatusHistoryModel::ACTION_BLOCK;

        $successAuditAction = $isBlockAction
            ? AdminAuditAction::MEMBER_BLOCKED
            : AdminAuditAction::MEMBER_UNBLOCKED;

        $deniedAuditAction = $isBlockAction
            ? AdminAuditAction::MEMBER_BLOCK_DENIED
            : AdminAuditAction::MEMBER_UNBLOCK_DENIED;

        $failedAuditAction = $isBlockAction
            ? AdminAuditAction::MEMBER_BLOCK_FAILED
            : AdminAuditAction::MEMBER_UNBLOCK_FAILED;

        /*
     * Reject malformed identifiers before opening a transaction.
     */
        if (
            $userId <= 0
            || $adminUserId <= 0
        ) {
            $this->auditService->record(
                new AdminAuditEvent(
                    action: $deniedAuditAction,
                    outcome: 'DENIED',
                    actorAdminId: $adminUserId > 0
                        ? $adminUserId
                        : null,
                    targetType: 'MEMBER',
                    targetId: $userId > 0
                        ? $userId
                        : null,
                    description: 'Member account status change was denied because '
                        . 'the member or administrator identifier was invalid.',
                    metadata: [
                        'requested_action' => $action,
                    ]
                )
            );

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
            $this->auditService->record(
                new AdminAuditEvent(
                    action: $deniedAuditAction,
                    outcome: 'DENIED',
                    actorAdminId: $adminUserId,
                    targetType: 'MEMBER',
                    targetId: $userId,
                    description: 'Member account status change was denied because '
                        . 'the supplied reason was invalid.',
                    metadata: [
                        'requested_action' => $action,
                        'reason_provided' =>
                        $normalizedReason !== '',
                        'reason_length' =>
                        mb_strlen($normalizedReason),
                    ]
                )
            );

            throw new DomainException(
                'Please enter a reason of no more than 64 characters.'
            );
        }

        /**
         * Retain safe target information for the post-commit audit record.
         *
         * @var array<string, mixed>|null $member
         */
        $member = null;

        $currentStatus = null;

        $historyId = null;

        $transactionCompleted = false;

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
                /*
             * Roll back before writing the denial audit. The audit entry must
             * not be part of the business transaction being rolled back.
             */
                $this->database->transRollback();

                $this->auditService->record(
                    new AdminAuditEvent(
                        action: $deniedAuditAction,
                        outcome: 'DENIED',
                        actorAdminId: $adminUserId,
                        targetType: 'MEMBER',
                        targetId: $userId,
                        targetLabel: (string) (
                            $member['profile_ref_number']
                            ?? ''
                        ),
                        description: $isBlockAction
                            ? 'Member block was denied because the account was not active.'
                            : 'Member unblock was denied because the account was not suspended.',
                        beforeData: [
                            'account_status' =>
                            $currentStatus,
                        ],
                        metadata: [
                            'expected_status' =>
                            $expectedStatus,
                            'requested_status' =>
                            $newStatus,
                            'reason' =>
                            $normalizedReason,
                        ]
                    )
                );

                throw new DomainException(
                    $isBlockAction
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
            $transactionCompleted = true;
        } catch (DomainException $exception) {
            /*
         * A status mismatch has already rolled back and audited above.
         */
            if (!$transactionCompleted) {
                $this->database->transRollback();
            }

            throw $exception;
        } catch (PageNotFoundException $exception) {
            $this->database->transRollback();

            $this->auditService->record(
                new AdminAuditEvent(
                    action: $failedAuditAction,
                    outcome: 'FAILURE',
                    actorAdminId: $adminUserId,
                    targetType: 'MEMBER',
                    targetId: $userId,
                    description: 'Member account status change failed because '
                        . 'the member was not found.',
                    metadata: [
                        'requested_action' =>
                        $action,
                        'reason' =>
                        $normalizedReason,
                    ]
                )
            );

            throw $exception;
        } catch (Throwable $exception) {
            $this->database->transRollback();

            $this->auditService->record(
                new AdminAuditEvent(
                    action: $failedAuditAction,
                    outcome: 'FAILURE',
                    actorAdminId: $adminUserId,
                    targetType: 'MEMBER',
                    targetId: $userId,
                    targetLabel: is_array($member)
                        ? (string) (
                            $member['profile_ref_number']
                            ?? ''
                        )
                        : null,
                    description: 'Member account status change failed.',
                    beforeData: $currentStatus !== null
                        ? [
                            'account_status' =>
                            $currentStatus,
                        ]
                        : null,
                    metadata: [
                        'requested_action' =>
                        $action,
                        'requested_status' =>
                        $newStatus,
                        'reason' =>
                        $normalizedReason,
                        /*
                     * Store the exception class for diagnostics but do not
                     * expose SQL, stack traces or internal exception messages.
                     */
                        'exception_class' =>
                        $exception::class,
                    ]
                )
            );

            throw $exception;
        }

        /*
     * Record success only after the member and history transactions commit.
     */
        $this->auditService->record(
            new AdminAuditEvent(
                action: $successAuditAction,
                outcome: 'SUCCESS',
                actorAdminId: $adminUserId,
                targetType: 'MEMBER',
                targetId: $userId,
                targetLabel: (string) (
                    $member['profile_ref_number']
                    ?? ''
                ),
                description: $isBlockAction
                    ? 'Member account was blocked by an administrator.'
                    : 'Member account was unblocked by an administrator.',
                beforeData: [
                    'account_status' =>
                    $currentStatus,
                ],
                afterData: [
                    'account_status' =>
                    $newStatus,
                ],
                metadata: [
                    'reason' =>
                    $normalizedReason,
                    'status_history_id' =>
                    is_numeric($historyId)
                        ? (int) $historyId
                        : null,
                ]
            )
        );
    }
}
