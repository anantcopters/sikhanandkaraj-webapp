<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Persisted member overrides for optional communication.
 *
 * IMPORTANT:
 *
 * This model only stores preference data.
 *
 * It does NOT decide whether a communication is essential or optional.
 * That decision belongs to CommunicationPolicyService.
 */
final class MemberCommunicationPreferenceModel extends Model
{
    public const CHANNEL_EMAIL =
    'EMAIL';

    public const CHANNEL_SMS =
    'SMS';

    public const CHANNEL_WHATSAPP =
    'WHATSAPP';

    public const FREQUENCY_IMMEDIATE =
    'IMMEDIATE';

    public const FREQUENCY_DAILY =
    'DAILY';

    public const FREQUENCY_WEEKLY =
    'WEEKLY';

    public const FREQUENCY_OFF =
    'OFF';

    protected $table =
    'member_communication_preferences';

    protected $primaryKey =
    'id';

    protected $returnType =
    'array';

    protected $useAutoIncrement =
    true;

    protected $allowedFields =
    [
        'user_id',
        'category',
        'channel',
        'is_enabled',
        'frequency',
    ];

    protected $useTimestamps =
    true;

    protected $createdField =
    'created_at';

    protected $updatedField =
    'updated_at';

    /**
     * Find one explicit member preference.
     *
     * Absence is intentional and means:
     *
     * "Use the central communication policy default."
     */
    public function findPreference(
        int $userId,
        string $category,
        string $channel
    ): ?array {
        if ($userId <= 0) {
            return null;
        }

        $row =
            $this
            ->where(
                'user_id',
                $userId
            )
            ->where(
                'category',
                mb_strtoupper(
                    trim(
                        $category
                    )
                )
            )
            ->where(
                'channel',
                mb_strtoupper(
                    trim(
                        $channel
                    )
                )
            )
            ->first();

        return is_array($row)
            ? $row
            : null;
    }

    /**
     * Store one member-controlled preference.
     *
     * PostgreSQL ON CONFLICT keeps this atomic and avoids the
     * read-then-insert race.
     */
    public function savePreference(
        int $userId,
        string $category,
        string $channel,
        bool $isEnabled,
        string $frequency
    ): bool {
        if ($userId <= 0) {
            return false;
        }

        $category =
            mb_strtoupper(
                trim(
                    $category
                )
            );

        $channel =
            mb_strtoupper(
                trim(
                    $channel
                )
            );

        $frequency =
            mb_strtoupper(
                trim(
                    $frequency
                )
            );

        $now =
            date(
                'Y-m-d H:i:s'
            );

        $sql =
            '
            INSERT INTO member_communication_preferences
            (
                user_id,
                category,
                channel,
                is_enabled,
                frequency,
                created_at,
                updated_at
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?
            )

            ON CONFLICT
            (
                user_id,
                category,
                channel
            )

            DO UPDATE SET
                is_enabled =
                    EXCLUDED.is_enabled,

                frequency =
                    EXCLUDED.frequency,

                updated_at =
                    EXCLUDED.updated_at
            ';

        return $this
            ->db
            ->query(
                $sql,
                [
                    $userId,
                    $category,
                    $channel,
                    $isEnabled,
                    $frequency,
                    $now,
                    $now,
                ]
            ) !== false;
    }
}
