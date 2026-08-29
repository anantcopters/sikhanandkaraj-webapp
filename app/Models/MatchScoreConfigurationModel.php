<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Persistence model for global Match Score configuration.
 *
 * Configuration rows are immutable historical snapshots.
 *
 * When Superadmin changes the weights, the service deactivates the current
 * row and inserts a new row rather than overwriting history.
 */
final class MatchScoreConfigurationModel extends Model
{
    protected $table =
    'match_score_configurations';

    protected $primaryKey =
    'id';

    protected $returnType =
    'array';

    protected $useAutoIncrement =
    true;

    protected $allowedFields = [
        'preference_weight',
        'profile_completion_weight',
        'approved_photo_weight',
        'trust_weight',
        'commercial_weight',
        'is_active',
        'created_by_admin_id',
        'created_at',
    ];

    protected $useTimestamps =
    false;

    protected $skipValidation =
    true;

    /**
     * Return the currently effective Match Score configuration.
     *
     * @return array<string, mixed>|null
     */
    public function activeConfiguration(): ?array
    {
        $row = $this
            ->where(
                'is_active',
                true
            )
            ->orderBy(
                'id',
                'DESC'
            )
            ->first();

        return is_array($row)
            ? $row
            : null;
    }

    /**
     * Return configuration history newest first.
     *
     * @return list<array<string, mixed>>
     */
    public function configurationHistory(
        int $limit = 50
    ): array {
        $limit = max(
            1,
            min(
                $limit,
                100
            )
        );

        return $this
            ->orderBy(
                'id',
                'DESC'
            )
            ->findAll(
                $limit
            );
    }
}
