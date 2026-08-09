<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Stores interest explicitly shown by one member in another.
 */
final class MemberInterestModel extends Model
{
    public const STATUS_PENDING =
    'PENDING';

    public const STATUS_ACCEPTED =
    'ACCEPTED';

    public const STATUS_DECLINED =
    'DECLINED';

    protected $table =
    'member_interests';

    protected $primaryKey =
    'id';

    protected $returnType =
    'array';

    protected $useAutoIncrement =
    true;

    protected $protectFields =
    true;

    protected $allowedFields = [
        'from_user_id',
        'to_user_id',
        'status',
        'responded_at',
    ];

    protected $useTimestamps =
    true;

    protected $dateFormat =
    'datetime';

    protected $createdField =
    'created_at';

    protected $updatedField =
    '';

    protected $skipValidation =
    true;

    /**
     * Determine whether one member has shown interest
     * in another member.
     */
    public function hasShown(
        int $fromUserId,
        int $toUserId
    ): bool {
        return $this
            ->where(
                'from_user_id',
                $fromUserId
            )
            ->where(
                'to_user_id',
                $toUserId
            )
            ->countAllResults() > 0;
    }

    /**
     * Determine whether any interest exists between
     * the supplied members.
     */
    public function existsBetween(
        int $firstUserId,
        int $secondUserId
    ): bool {
        return $this
            ->groupStart()
            ->groupStart()
            ->where(
                'from_user_id',
                $firstUserId
            )
            ->where(
                'to_user_id',
                $secondUserId
            )
            ->groupEnd()
            ->orGroupStart()
            ->where(
                'from_user_id',
                $secondUserId
            )
            ->where(
                'to_user_id',
                $firstUserId
            )
            ->groupEnd()
            ->groupEnd()
            ->countAllResults() > 0;
    }

    /**
     * Return the interest from one member to another.
     *
     * @return array<string, mixed>|null
     */
    public function findBetween(
        int $fromUserId,
        int $toUserId
    ): ?array {
        $row = $this
            ->where(
                'from_user_id',
                $fromUserId
            )
            ->where(
                'to_user_id',
                $toUserId
            )
            ->first();

        return is_array($row)
            ? $row
            : null;
    }

    /**
     * Return received interest records newest first.
     *
     * @return list<array<string, mixed>>
     */
    public function receivedFor(
        int $userId
    ): array {
        return $this
            ->where(
                'to_user_id',
                $userId
            )
            ->orderBy(
                'created_at',
                'DESC'
            )
            ->findAll();
    }

    /**
     * Return sent interest records newest first.
     *
     * @return list<array<string, mixed>>
     */
    public function sentFor(
        int $userId
    ): array {
        return $this
            ->where(
                'from_user_id',
                $userId
            )
            ->orderBy(
                'created_at',
                'DESC'
            )
            ->findAll();
    }

    /**
     * @return list<int>
     */
    public function receivedMemberIds(
        int $userId
    ): array {
        $rows = $this
            ->select(
                'from_user_id'
            )
            ->where(
                'to_user_id',
                $userId
            )
            ->orderBy(
                'created_at',
                'DESC'
            )
            ->findAll();

        return array_values(
            array_map(
                static fn(array $row): int =>
                (int) $row['from_user_id'],
                $rows
            )
        );
    }

    /**
     * @return list<int>
     */
    public function sentMemberIds(
        int $userId
    ): array {
        $rows = $this
            ->select(
                'to_user_id'
            )
            ->where(
                'from_user_id',
                $userId
            )
            ->orderBy(
                'created_at',
                'DESC'
            )
            ->findAll();

        return array_values(
            array_map(
                static fn(array $row): int =>
                (int) $row['to_user_id'],
                $rows
            )
        );
    }

    public function countReceived(
        int $userId
    ): int {
        return $this
            ->where(
                'to_user_id',
                $userId
            )
            ->countAllResults();
    }

    public function countSent(
        int $userId
    ): int {
        return $this
            ->where(
                'from_user_id',
                $userId
            )
            ->countAllResults();
    }
}
