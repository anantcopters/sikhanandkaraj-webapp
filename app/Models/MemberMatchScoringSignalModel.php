<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Persistence model for cached intrinsic matchmaking scoring signals.
 *
 * This table must never contain viewer-specific Match Scores.
 *
 * Partner Preference scoring is directional:
 *
 *     viewer -> candidate
 *
 * and therefore belongs to request-time Match Score calculation.
 */
final class MemberMatchScoringSignalModel extends Model
{
    protected $table =
    'member_match_scoring_signals';

    protected $primaryKey =
    'user_id';

    protected $returnType =
    'array';

    protected $useAutoIncrement =
    false;

    protected $allowedFields = [
        'user_id',
        'profile_completion',
        'updated_at',
    ];

    protected $useTimestamps =
    false;

    protected $skipValidation =
    true;

    /**
     * Persist the authoritative completion percentage for one member.
     *
     * PostgreSQL ON CONFLICT keeps this operation atomic and avoids a
     * read-before-write query.
     */
    public function upsertProfileCompletion(
        int $userId,
        int $percentage
    ): void {
        if ($userId <= 0) {
            return;
        }

        $percentage = max(
            0,
            min(
                100,
                $percentage
            )
        );

        $this->db->query(
            '
                INSERT INTO member_match_scoring_signals (
                    user_id,
                    profile_completion,
                    updated_at
                )
                VALUES (?, ?, CURRENT_TIMESTAMP)

                ON CONFLICT (user_id)
                DO UPDATE SET
                    profile_completion =
                        EXCLUDED.profile_completion,

                    updated_at =
                        CURRENT_TIMESTAMP
            ',
            [
                $userId,
                $percentage,
            ]
        );
    }
}
