<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

final class MemberMessageModel extends Model
{
    public const TYPE_MEMBER =
    'MEMBER';

    public const TYPE_SYSTEM =
    'SYSTEM';

    public const EVENT_INTEREST_SENT =
    'INTEREST_SENT';

    public const EVENT_INTEREST_ACCEPTED =
    'INTEREST_ACCEPTED';

    public const EVENT_INTEREST_DECLINED =
    'INTEREST_DECLINED';

    public const EVENT_INTEREST_WITHDRAWN =
    'INTEREST_WITHDRAWN';

    protected $table =
    'member_messages';

    protected $primaryKey =
    'id';

    protected $returnType =
    'array';

    protected $useAutoIncrement =
    true;

    protected $protectFields =
    true;

    protected $allowedFields = [
        'conversation_id',
        'sender_user_id',
        'recipient_user_id',
        'message_type',
        'event_type',
        'interest_id',
        'message_text',
        'client_request_id',
        'read_at',
        'removed_at',
        'removed_by_admin_id',
        'removal_reason',
        'created_at',
    ];

    protected $useTimestamps =
    false;

    protected $skipValidation =
    true;

    /**
     * @return list<array<string,mixed>>
     */
    public function conversationMessages(
        int $conversationId,
        int $limit = 100
    ): array {
        $rows = $this
            ->where(
                'conversation_id',
                $conversationId
            )
            ->orderBy(
                'id',
                'DESC'
            )
            ->findAll(
                min(
                    100,
                    max(
                        1,
                        $limit
                    )
                )
            );

        return array_reverse(
            $rows
        );
    }

    public function unreadCount(
        int $recipientUserId
    ): int {
        return $this
            ->where(
                'recipient_user_id',
                $recipientUserId
            )
            ->where(
                'message_type',
                self::TYPE_MEMBER
            )
            ->where(
                'read_at',
                null
            )
            ->countAllResults();
    }

    public function unreadConversationCount(
        int $recipientUserId
    ): int {
        $row = $this
            ->select(
                'COUNT(DISTINCT conversation_id) AS total',
                false
            )
            ->where(
                'recipient_user_id',
                $recipientUserId
            )
            ->where(
                'message_type',
                self::TYPE_MEMBER
            )
            ->where(
                'read_at',
                null
            )
            ->first();

        return max(
            0,
            (int) (
                $row['total']
                ?? 0
            )
        );
    }

    public function markConversationRead(
        int $conversationId,
        int $recipientUserId
    ): bool {
        return $this
            ->where(
                'conversation_id',
                $conversationId
            )
            ->where(
                'recipient_user_id',
                $recipientUserId
            )
            ->where(
                'message_type',
                self::TYPE_MEMBER
            )
            ->where(
                'read_at',
                null
            )
            ->set(
                'read_at',
                date('Y-m-d H:i:s')
            )
            ->update();
    }

    public function countOutgoingSince(
        int $senderUserId,
        string $since
    ): int {
        return $this
            ->where(
                'sender_user_id',
                $senderUserId
            )
            ->where(
                'message_type',
                self::TYPE_MEMBER
            )
            ->where(
                'created_at >=',
                $since
            )
            ->countAllResults();
    }

    public function countOutgoingToMemberSince(
        int $senderUserId,
        int $recipientUserId,
        string $since
    ): int {
        return $this
            ->where(
                'sender_user_id',
                $senderUserId
            )
            ->where(
                'recipient_user_id',
                $recipientUserId
            )
            ->where(
                'message_type',
                self::TYPE_MEMBER
            )
            ->where(
                'created_at >=',
                $since
            )
            ->countAllResults();
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function latestManualMessages(
        int $conversationId,
        int $limit
    ): array {
        return $this
            ->where(
                'conversation_id',
                $conversationId
            )
            ->where(
                'message_type',
                self::TYPE_MEMBER
            )
            ->orderBy(
                'id',
                'DESC'
            )
            ->findAll(
                max(
                    1,
                    $limit
                )
            );
    }

    public function recipientHasReplied(
        int $conversationId,
        int $recipientUserId
    ): bool {
        return $this
            ->where(
                'conversation_id',
                $conversationId
            )
            ->where(
                'message_type',
                self::TYPE_MEMBER
            )
            ->where(
                'sender_user_id',
                $recipientUserId
            )
            ->countAllResults() > 0;
    }

    /**
     * @return array{
     *     messages:list<array<string,mixed>>,
     *     nextBeforeId:int|null
     * }
     */
    public function conversationPage(
        int $conversationId,
        ?int $beforeId = null,
        int $limit = 50
    ): array {
        $limit = min(
            100,
            max(
                1,
                $limit
            )
        );

        $builder = $this
            ->where(
                'conversation_id',
                $conversationId
            );

        if (
            $beforeId !== null
            && $beforeId > 0
        ) {
            $builder->where(
                'id <',
                $beforeId
            );
        }

        $rows = $builder
            ->orderBy(
                'id',
                'DESC'
            )
            ->findAll(
                $limit + 1
            );

        $hasMore =
            count($rows) > $limit;

        if ($hasMore) {
            array_pop($rows);
        }

        $nextBeforeId =
            $hasMore
            && $rows !== []
            ? (int) end($rows)['id']
            : null;

        return [
            'messages' =>
            array_reverse(
                $rows
            ),

            'nextBeforeId' =>
            $nextBeforeId,
        ];
    }
}
