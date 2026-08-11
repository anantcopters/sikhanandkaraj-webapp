<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Stores immutable member block and unblock history.
 */
final class MemberAccountStatusHistoryModel extends Model
{
    public const ACTION_BLOCK = 'BLOCK';

    public const ACTION_UNBLOCK = 'UNBLOCK';

    protected $table = 'member_account_status_history';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'user_id',
        'action',
        'previous_status',
        'new_status',
        'reason',
        'changed_by_admin_id',
        'changed_at',
    ];

    protected $useTimestamps = false;

    protected $skipValidation = true;

    /**
     * Return status-change history for one member.
     *
     * @return list<array<string, mixed>>
     */
    public function forUser(
        int $userId,
        int $limit = 100
    ): array {
        if ($userId <= 0) {
            return [];
        }

        $safeLimit = max(
            1,
            min($limit, 100)
        );

        return $this
            ->select([
                'member_account_status_history.id',
                'member_account_status_history.action',
                'member_account_status_history.previous_status',
                'member_account_status_history.new_status',
                'member_account_status_history.reason',
                'member_account_status_history.changed_at',
                'member_account_status_history.changed_by_admin_id',
                'admin_users.full_name AS admin_name',
                'admin_users.role AS admin_role',
            ])
            ->join(
                'admin_users',
                'admin_users.id = '
                    . 'member_account_status_history.changed_by_admin_id',
                'left'
            )
            ->where(
                'member_account_status_history.user_id',
                $userId
            )
            ->orderBy(
                'member_account_status_history.changed_at',
                'DESC'
            )
            ->orderBy(
                'member_account_status_history.id',
                'DESC'
            )
            ->findAll($safeLimit);
    }
}
