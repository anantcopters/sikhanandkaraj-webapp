<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Stores aggregated profile viewing activity.
 */
final class MemberProfileViewModel extends Model
{
    protected $table = 'member_profile_views';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $protectFields = true;

    protected $allowedFields = [
        'viewer_user_id',
        'viewed_user_id',
        'view_count',
        'first_viewed_at',
        'last_viewed_at',
    ];

    protected $useTimestamps = true;

    protected $dateFormat = 'datetime';

    protected $createdField = 'created_at';

    protected $updatedField = 'updated_at';

    protected $skipValidation = true;

    /**
     * Atomic PostgreSQL increment.
     *
     * SQL remains in the model as required by the project rules.
     */
    public function recordView(
        int $viewerUserId,
        int $viewedUserId
    ): void {
        $this->db->query(
            <<<'SQL'
INSERT INTO member_profile_views (
    viewer_user_id,
    viewed_user_id,
    view_count,
    first_viewed_at,
    last_viewed_at,
    created_at,
    updated_at
)
VALUES (
    ?,
    ?,
    1,
    CURRENT_TIMESTAMP,
    CURRENT_TIMESTAMP,
    CURRENT_TIMESTAMP,
    CURRENT_TIMESTAMP
)
ON CONFLICT (
    viewer_user_id,
    viewed_user_id
)
DO UPDATE SET
    view_count =
        member_profile_views.view_count + 1,
    last_viewed_at =
        CURRENT_TIMESTAMP,
    updated_at =
        CURRENT_TIMESTAMP
SQL,
            [
                $viewerUserId,
                $viewedUserId,
            ]
        );
    }

    /**
     * @return list<int>
     */
    public function viewerIdsFor(
        int $viewedUserId
    ): array {
        $rows = $this
            ->select('viewer_user_id')
            ->where(
                'viewed_user_id',
                $viewedUserId
            )
            ->orderBy(
                'last_viewed_at',
                'DESC'
            )
            ->findAll();

        return array_values(
            array_map(
                static fn(array $row): int =>
                (int) $row['viewer_user_id'],
                $rows
            )
        );
    }

    /**
     * @return list<int>
     */
    public function viewedIdsFor(
        int $viewerUserId
    ): array {
        $rows = $this
            ->select('viewed_user_id')
            ->where(
                'viewer_user_id',
                $viewerUserId
            )
            ->orderBy(
                'last_viewed_at',
                'DESC'
            )
            ->findAll();

        return array_values(
            array_map(
                static fn(array $row): int =>
                (int) $row['viewed_user_id'],
                $rows
            )
        );
    }

    public function uniqueViewerCount(
        int $userId
    ): int {
        return $this
            ->where(
                'viewed_user_id',
                $userId
            )
            ->countAllResults();
    }

    public function totalReceivedViews(
        int $userId
    ): int {
        $row = $this
            ->selectSum(
                'view_count',
                'total'
            )
            ->where(
                'viewed_user_id',
                $userId
            )
            ->first();

        return max(
            0,
            (int) ($row['total'] ?? 0)
        );
    }
}
