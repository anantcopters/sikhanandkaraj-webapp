<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Stores profiles shortlisted by one member.
 *
 * Shortlisting is directional:
 *
 * user_id -> shortlisted_user_id
 */
final class MemberShortlistModel extends Model
{
    protected $table = 'member_shortlists';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $protectFields = true;

    protected $allowedFields = [
        'user_id',
        'shortlisted_user_id',
    ];

    protected $useTimestamps = true;

    protected $dateFormat = 'datetime';

    protected $createdField = 'created_at';

    /*
     * The table intentionally has no updated_at column.
     *
     * A shortlist row is either present or absent.
     */
    protected $updatedField = '';

    protected $skipValidation = true;

    /**
     * Determine whether one member has shortlisted another.
     */
    public function hasShortlisted(
        int $userId,
        int $shortlistedUserId
    ): bool {
        return $this
            ->where(
                'user_id',
                $userId
            )
            ->where(
                'shortlisted_user_id',
                $shortlistedUserId
            )
            ->countAllResults() > 0;
    }

    /**
     * Return the subset of supplied members currently shortlisted by the viewer.
     *
     * Card collections should use this batch lookup instead of calling
     * hasShortlisted() once per candidate.
     *
     * @param int $userId
     *
     * @return list<int>
     */
    public function shortlistedIdsFromCandidates(
        int $userId,
        array $candidateUserIds
    ): array {
        $candidateUserIds = array_values(
            array_unique(
                array_filter(
                    array_map(
                        'intval',
                        $candidateUserIds
                    ),
                    static fn(int $candidateUserId): bool =>
                    $candidateUserId > 0
                        && $candidateUserId !== $userId
                )
            )
        );

        if (
            $userId <= 0
            || $candidateUserIds === []
        ) {
            return [];
        }

        $rows = $this
            ->select(
                'shortlisted_user_id'
            )
            ->where(
                'user_id',
                $userId
            )
            ->whereIn(
                'shortlisted_user_id',
                $candidateUserIds
            )
            ->findAll();

        return array_values(
            array_unique(
                array_map(
                    static fn(array $row): int =>
                    (int) (
                        $row['shortlisted_user_id']
                        ?? 0
                    ),
                    $rows
                )
            )
        );
    }

    /**
     * Remove one directional shortlist relationship.
     */
    public function removeShortlist(
        int $userId,
        int $shortlistedUserId
    ): bool {
        return $this
            ->where(
                'user_id',
                $userId
            )
            ->where(
                'shortlisted_user_id',
                $shortlistedUserId
            )
            ->delete();
    }

    /**
     * Remove shortlist relationships in either direction.
     *
     * Used when one member blocks another.
     */
    public function removeBetween(
        int $firstUserId,
        int $secondUserId
    ): bool {
        return $this
            ->groupStart()
            ->groupStart()
            ->where(
                'user_id',
                $firstUserId
            )
            ->where(
                'shortlisted_user_id',
                $secondUserId
            )
            ->groupEnd()
            ->orGroupStart()
            ->where(
                'user_id',
                $secondUserId
            )
            ->where(
                'shortlisted_user_id',
                $firstUserId
            )
            ->groupEnd()
            ->groupEnd()
            ->delete();
    }

    /**
     * Return profiles shortlisted by one member.
     *
     * @return list<int>
     */
    public function shortlistedMemberIds(
        int $userId
    ): array {
        $rows = $this
            ->select(
                'shortlisted_user_id'
            )
            ->where(
                'user_id',
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
                (int) $row['shortlisted_user_id'],
                $rows
            )
        );
    }

    /**
     * Return members who shortlisted this member.
     *
     * The relationship is directional:
     *
     * other member -> current member
     *
     * @return list<int>
     */
    public function shortlistedByMemberIds(
        int $userId
    ): array {
        $rows = $this
            ->select(
                'user_id'
            )
            ->where(
                'shortlisted_user_id',
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
                (int) $row['user_id'],
                $rows
            )
        );
    }
}
