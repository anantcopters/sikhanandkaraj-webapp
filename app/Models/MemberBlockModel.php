<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Stores member-to-member blocks.
 *
 * This is unrelated to the administrator account suspension workflow.
 */
final class MemberBlockModel extends Model
{
    protected $table = 'member_blocks';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $protectFields = true;

    protected $allowedFields = [
        'blocker_user_id',
        'blocked_user_id',
        'comment',
    ];

    protected $useTimestamps = true;

    protected $dateFormat = 'datetime';

    protected $createdField = 'created_at';

    protected $updatedField = 'updated_at';

    protected $skipValidation = true;

    public function existsBetween(
        int $firstUserId,
        int $secondUserId
    ): bool {
        if (
            $firstUserId <= 0
            || $secondUserId <= 0
        ) {
            return false;
        }

        return $this
            ->groupStart()
            ->groupStart()
            ->where(
                'blocker_user_id',
                $firstUserId
            )
            ->where(
                'blocked_user_id',
                $secondUserId
            )
            ->groupEnd()
            ->orGroupStart()
            ->where(
                'blocker_user_id',
                $secondUserId
            )
            ->where(
                'blocked_user_id',
                $firstUserId
            )
            ->groupEnd()
            ->groupEnd()
            ->countAllResults() > 0;
    }

    public function blockerHasBlocked(
        int $blockerUserId,
        int $blockedUserId
    ): bool {
        return $this
            ->where(
                'blocker_user_id',
                $blockerUserId
            )
            ->where(
                'blocked_user_id',
                $blockedUserId
            )
            ->countAllResults() > 0;
    }

    public function countBlockedBy(
        int $userId
    ): int {
        return $this
            ->where(
                'blocker_user_id',
                $userId
            )
            ->countAllResults();
    }
}
