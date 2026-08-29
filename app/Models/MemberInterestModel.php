<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Stores directional member Interest activity.
 *
 * Interest lifecycle:
 *
 * PENDING
 *     Interest sent and awaiting recipient response.
 *
 * ACCEPTED
 *     Recipient accepted the Interest.
 *
 * DECLINED
 *     Recipient declined the Interest.
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
     * Determine whether this directional Interest exists.
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
     * Determine whether an Interest exists in either direction.
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
     * Return one directional Interest.
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
     * Return all Interest rows between one viewer and a candidate collection.
     *
     * Search/Dashboard presentation must not execute two Interest queries for
     * every displayed profile.
     *
     * Both directions are loaded in one query:
     *
     * viewer -> candidate
     * candidate -> viewer
     *
     * The service remains responsible for interpreting the rows into the
     * existing Interest relationship states.
     *
     * @param list<int> $targetUserIds
     *
     * @return list<array<string, mixed>>
     */
    public function findRelationshipsForViewer(
        int $viewerUserId,
        array $targetUserIds
    ): array {
        $targetUserIds = array_values(
            array_unique(
                array_filter(
                    array_map(
                        'intval',
                        $targetUserIds
                    ),
                    static fn(int $userId): bool =>
                    $userId > 0
                        && $userId !== $viewerUserId
                )
            )
        );

        if (
            $viewerUserId <= 0
            || $targetUserIds === []
        ) {
            return [];
        }

        return $this
            ->select([
                'id',
                'from_user_id',
                'to_user_id',
                'status',
                'responded_at',
                'created_at',
            ])
            ->groupStart()
            ->groupStart()
            ->where(
                'from_user_id',
                $viewerUserId
            )
            ->whereIn(
                'to_user_id',
                $targetUserIds
            )
            ->groupEnd()
            ->orGroupStart()
            ->whereIn(
                'from_user_id',
                $targetUserIds
            )
            ->where(
                'to_user_id',
                $viewerUserId
            )
            ->groupEnd()
            ->groupEnd()
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

    public function acceptedBetween(
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
            ->where(
                'status',
                self::STATUS_ACCEPTED
            )
            ->countAllResults() > 0;
    }

    /**
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
                static fn(
                    array $row
                ): int =>
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
                static fn(
                    array $row
                ): int =>
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
