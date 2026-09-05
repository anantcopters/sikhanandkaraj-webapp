<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\MemberConversationModel;
use App\Models\MemberMessageModel;
use App\Models\MemberMessageReportModel;
use App\Services\Admin\Audit\AdminAuditEvent;
use App\Services\Admin\Audit\AdminAuditService;
use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;

final class AdminMemberMessagingService
{
    public function __construct(
        private readonly MemberConversationModel
        $conversationModel,

        private readonly MemberMessageModel
        $messageModel,

        private readonly MemberMessageReportModel
        $reportModel,

        private readonly AdminAuditService
        $auditService,

        private readonly BaseConnection
        $database
    ) {}

    /**
     * @return array<string,int>
     */
    public function summaryForMember(
        int $userId
    ): array {
        return [
            'conversations' =>
            count(
                $this->conversationModel
                    ->forMember(
                        $userId
                    )
            ),

            'sent' =>
            $this->messageModel
                ->where(
                    'sender_user_id',
                    $userId
                )
                ->where(
                    'message_type',
                    MemberMessageModel
                    ::TYPE_MEMBER
                )
                ->countAllResults(),

            'received' =>
            $this->messageModel
                ->where(
                    'recipient_user_id',
                    $userId
                )
                ->where(
                    'message_type',
                    MemberMessageModel
                    ::TYPE_MEMBER
                )
                ->countAllResults(),

            'unread' =>
            $this->messageModel
                ->unreadCount(
                    $userId
                ),

            'reported' =>
            $this->reportModel
                ->where(
                    'reported_user_id',
                    $userId
                )
                ->countAllResults(),

            'removed' =>
            $this->messageModel
                ->where(
                    'sender_user_id',
                    $userId
                )
                ->where(
                    'removed_at !=',
                    null
                )
                ->countAllResults(),
        ];
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function conversationsForMember(
        int $memberId
    ): array {
        if ($memberId <= 0) {
            throw new DomainException(
                'The member could not be found.'
            );
        }

        return $this
            ->conversationModel
            ->forMember(
                $memberId
            );
    }

    /**
     * Admin inspection must never mark member messages read.
     *
     * @return array<string,mixed>
     */
    public function conversation(
        int $conversationId
    ): array {
        $conversation = $this
            ->conversationModel
            ->find(
                $conversationId
            );

        if (!is_array($conversation)) {
            throw new DomainException(
                'The conversation could not be found.'
            );
        }

        $this->auditService
            ->record(
                new AdminAuditEvent(
                    action: 'MEMBER_MESSAGE_CONVERSATION_VIEWED',

                    targetType: 'MEMBER_CONVERSATION',

                    targetId: $conversationId,

                    description: 'Administrator inspected a member conversation.',

                    metadata: [
                        'first_user_id' =>
                        (int) $conversation['first_user_id'],

                        'second_user_id' =>
                        (int) $conversation['second_user_id'],
                    ]
                )
            );

        return [
            'conversation' =>
            $conversation,

            'messages' =>
            $this->messageModel
                ->conversationMessages(
                    $conversationId
                ),
        ];
    }

    public function removeMessage(
        int $messageId,
        string $reason
    ): bool {
        $reason =
            trim($reason);

        if (
            $reason === ''
            || mb_strlen($reason) > 500
        ) {
            throw new DomainException(
                'Please enter a moderation reason of no more than 500 characters.'
            );
        }

        $message = $this
            ->messageModel
            ->find(
                $messageId
            );

        if (
            !is_array($message)
            || (
                $message['message_type']
                ?? ''
            ) !== MemberMessageModel
            ::TYPE_MEMBER
        ) {
            throw new DomainException(
                'The message could not be found.'
            );
        }

        if (
            !empty($message['removed_at'])
        ) {
            return false;
        }

        $adminId =
            is_numeric(
                session(
                    'admin_user_id'
                )
            )
            ? (int) session(
                'admin_user_id'
            )
            : 0;

        if ($adminId <= 0) {
            throw new RuntimeException(
                'Administrator identity could not be resolved.'
            );
        }

        $updated = $this
            ->messageModel
            ->update(
                $messageId,
                [
                    'removed_at' =>
                    date(
                        'Y-m-d H:i:s'
                    ),

                    'removed_by_admin_id' =>
                    $adminId,

                    'removal_reason' =>
                    $reason,
                ]
            );

        if ($updated === false) {
            throw new RuntimeException(
                'The message could not be moderated.'
            );
        }

        /*
         * Reuse the existing Admin audit infrastructure.
         *
         * Do not copy the private message body into the generic audit log.
         */
        $this->auditService
            ->record(
                new AdminAuditEvent(
                    action: 'MEMBER_MESSAGE_MODERATED',

                    targetType: 'MEMBER_MESSAGE',

                    targetId: $messageId,

                    description: 'Member message removed from member-facing presentation.',

                    metadata: [
                        'conversation_id' =>
                        (int) $message['conversation_id'],

                        'reason' =>
                        $reason,
                    ]
                )
            );

        return true;
    }

    public function conversationForMember(
        int $memberId,
        int $conversationId
    ): array {
        $conversation = $this
            ->conversationModel
            ->find(
                $conversationId
            );

        if (
            !is_array($conversation)
            || (
                (int) (
                    $conversation['first_user_id']
                    ?? 0
                ) !== $memberId
                &&
                (int) (
                    $conversation['second_user_id']
                    ?? 0
                ) !== $memberId
            )
        ) {
            throw new DomainException(
                'The conversation could not be found.'
            );
        }

        $this->auditService
            ->record(
                new AdminAuditEvent(
                    action: 'MEMBER_MESSAGE_CONVERSATION_VIEWED',

                    targetType: 'MEMBER_CONVERSATION',

                    targetId: $conversationId,

                    description: 'Administrator inspected a member conversation.',

                    metadata: [
                        'member_context_id' =>
                        $memberId,

                        'first_user_id' =>
                        (int) $conversation['first_user_id'],

                        'second_user_id' =>
                        (int) $conversation['second_user_id'],
                    ]
                )
            );

        return [
            'conversation' =>
            $conversation,

            'messages' =>
            $this->messageModel
                ->conversationMessages(
                    $conversationId
                ),
        ];
    }

    public function removeMessageForMember(
        int $memberId,
        int $messageId,
        string $reason
    ): bool {
        if (
            $memberId <= 0
            || $messageId <= 0
        ) {
            throw new DomainException(
                'The message could not be found.'
            );
        }

        $message = $this
            ->messageModel
            ->find(
                $messageId
            );

        if (!is_array($message)) {
            throw new DomainException(
                'The message could not be found.'
            );
        }

        $conversationId = max(
            0,
            (int) (
                $message['conversation_id']
                ?? 0
            )
        );

        /*
        * Authorize against the member context encoded in the
        * Admin route before allowing moderation.
        */
        $conversation = $this
            ->conversationModel
            ->find(
                $conversationId
            );

        if (
            !is_array($conversation)
            || (
                (int) (
                    $conversation['first_user_id']
                    ?? 0
                ) !== $memberId
                &&
                (int) (
                    $conversation['second_user_id']
                    ?? 0
                ) !== $memberId
            )
        ) {
            throw new DomainException(
                'The message could not be found.'
            );
        }

        return $this
            ->removeMessage(
                $messageId,
                $reason
            );
    }
}
