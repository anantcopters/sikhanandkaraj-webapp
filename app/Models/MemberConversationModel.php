<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

final class MemberConversationModel extends Model
{
    public const STATUS_ACTIVE =
    'ACTIVE';

    public const STATUS_CLOSED_DECLINED =
    'CLOSED_DECLINED';

    public const STATUS_CLOSED_WITHDRAWN =
    'CLOSED_WITHDRAWN';

    public const CREATED_FROM_MANUAL =
    'MANUAL';

    public const CREATED_FROM_INTEREST =
    'INTEREST';

    protected $table =
    'member_conversations';

    protected $primaryKey =
    'id';

    protected $returnType =
    'array';

    protected $useAutoIncrement =
    true;

    protected $protectFields =
    true;

    protected $allowedFields = [
        'first_user_id',
        'second_user_id',
        'status',
        'created_from',
        'manual_initiated_by_user_id',
        'last_message_at',
        'closed_at',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps =
    false;

    protected $skipValidation =
    true;

    public function between(
        int $firstUserId,
        int $secondUserId
    ): ?array {
        [$firstUserId, $secondUserId] =
            $this->orderedPair(
                $firstUserId,
                $secondUserId
            );

        $row = $this
            ->where(
                'first_user_id',
                $firstUserId
            )
            ->where(
                'second_user_id',
                $secondUserId
            )
            ->first();

        return is_array($row)
            ? $row
            : null;
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function forMember(
        int $userId
    ): array {
        return $this
            ->groupStart()
            ->where(
                'first_user_id',
                $userId
            )
            ->orWhere(
                'second_user_id',
                $userId
            )
            ->groupEnd()
            ->orderBy(
                'last_message_at',
                'DESC'
            )
            ->orderBy(
                'created_at',
                'DESC'
            )
            ->orderBy(
                'id',
                'DESC'
            )
            ->findAll();
    }

    /**
     * Return conversation-list state for one member.
     *
     * Identity/profile presentation is intentionally NOT resolved here.
     * This method owns only messaging persistence data.
     *
     * @return list<array<string,mixed>>
     */
    public function listingForMember(
        int $userId
    ): array {
        return $this
            ->select([
                'member_conversations.*',

                'CASE
                WHEN member_conversations.first_user_id = '
                    . (int) $userId . '
                THEN member_conversations.second_user_id
                ELSE member_conversations.first_user_id
            END AS other_user_id',

                'latest.message_text AS latest_message_text',
                'latest.message_type AS latest_message_type',
                'latest.removed_at AS latest_removed_at',

                'COALESCE(unread.total, 0) AS unread_count',
            ])
            ->join(
                'LATERAL (
                SELECT
                    member_messages.message_text,
                    member_messages.message_type,
                    member_messages.removed_at
                FROM member_messages
                WHERE member_messages.conversation_id
                    = member_conversations.id
                ORDER BY member_messages.id DESC
                LIMIT 1
            ) AS latest',
                'TRUE',
                'left',
                false
            )
            ->join(
                'LATERAL (
                SELECT COUNT(*) AS total
                FROM member_messages
                WHERE member_messages.conversation_id
                    = member_conversations.id
                  AND member_messages.recipient_user_id = '
                    . (int) $userId . '
                  AND member_messages.message_type = \'MEMBER\'
                  AND member_messages.read_at IS NULL
            ) AS unread',
                'TRUE',
                'left',
                false
            )
            ->groupStart()
            ->where(
                'member_conversations.first_user_id',
                $userId
            )
            ->orWhere(
                'member_conversations.second_user_id',
                $userId
            )
            ->groupEnd()
            ->orderBy(
                'member_conversations.last_message_at',
                'DESC'
            )
            ->orderBy(
                'member_conversations.id',
                'DESC'
            )
            ->findAll();
    }

    public function countManualStartedToday(
        int $userId,
        string $dayStart
    ): int {
        return $this
            ->where(
                'manual_initiated_by_user_id',
                $userId
            )
            ->where(
                'created_at >=',
                $dayStart
            )
            ->countAllResults();
    }

    /**
     * @return array{0:int,1:int}
     */
    private function orderedPair(
        int $firstUserId,
        int $secondUserId
    ): array {
        return $firstUserId < $secondUserId
            ? [
                $firstUserId,
                $secondUserId,
            ]
            : [
                $secondUserId,
                $firstUserId,
            ];
    }
}
