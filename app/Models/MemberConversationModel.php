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
