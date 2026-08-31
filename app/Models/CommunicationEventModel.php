<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Durable channel-independent communication events.
 *
 * A communication event describes WHAT happened.
 *
 * It deliberately does not describe:
 *
 * - an email provider;
 * - an SMS provider;
 * - a WhatsApp provider;
 * - HTML rendering;
 * - recipient email addresses.
 *
 * Those concerns belong to downstream channel delivery.
 */
final class CommunicationEventModel extends Model
{
    public const STATUS_PENDING =
    'PENDING';

    public const STATUS_PROCESSING =
    'PROCESSING';

    public const STATUS_PROCESSED =
    'PROCESSED';

    public const STATUS_FAILED =
    'FAILED';

    protected $table =
    'communication_events';

    protected $primaryKey =
    'id';

    protected $returnType =
    'array';

    protected $useAutoIncrement =
    true;

    protected $allowedFields =
    [
        'event_key',
        'recipient_user_id',
        'reference_type',
        'reference_id',
        'payload_json',
        'status',
        'attempt_count',
        'available_at',
        'processing_started_at',
        'processed_at',
        'last_error',
    ];

    protected $useTimestamps =
    true;

    protected $createdField =
    'created_at';

    protected $updatedField =
    'updated_at';

    /**
     * Determine whether the same logical event already exists.
     *
     * This is useful to callers, but the database UNIQUE constraint
     * remains the concurrency-safe authority.
     */
    public function hasReference(
        string $eventKey,
        int $recipientUserId,
        string $referenceType,
        int $referenceId
    ): bool {
        if (
            $recipientUserId <= 0
            || $referenceId <= 0
        ) {
            return false;
        }

        return $this
            ->where(
                'event_key',
                trim(
                    $eventKey
                )
            )
            ->where(
                'recipient_user_id',
                $recipientUserId
            )
            ->where(
                'reference_type',
                trim(
                    $referenceType
                )
            )
            ->where(
                'reference_id',
                $referenceId
            )
            ->countAllResults() > 0;
    }

    /**
     * Reserve pending communication events for one dispatcher.
     *
     * PostgreSQL SKIP LOCKED allows more than one dispatcher process
     * without processing the same event concurrently.
     *
     * @return array<int, array<string, mixed>>
     */
    public function reserveBatch(
        int $limit
    ): array {
        $limit =
            max(
                1,
                min(
                    500,
                    $limit
                )
            );

        $database =
            $this->db;

        $database
            ->transBegin();

        try {
            $sql =
                '
            SELECT
                *
            FROM
                communication_events
            WHERE
                status = ?
                AND available_at <= ?
            ORDER BY
                id ASC
            LIMIT ?
            FOR UPDATE SKIP LOCKED
            ';

            $rows =
                $database
                ->query(
                    $sql,
                    [
                        self::STATUS_PENDING,
                        gmdate(
                            'Y-m-d H:i:s'
                        ),
                        $limit,
                    ]
                )
                ->getResultArray();

            if ($rows === []) {
                $database
                    ->transCommit();

                return [];
            }

            $ids =
                array_map(
                    static fn(array $row): int =>
                    (int) $row['id'],
                    $rows
                );

            $placeholders =
                implode(
                    ',',
                    array_fill(
                        0,
                        count(
                            $ids
                        ),
                        '?'
                    )
                );

            $parameters = [
                self::STATUS_PROCESSING,
                gmdate(
                    'Y-m-d H:i:s'
                ),
                gmdate(
                    'Y-m-d H:i:s'
                ),
                ...$ids,
            ];

            $database
                ->query(
                    '
                UPDATE
                    communication_events
                SET
                    status = ?,
                    processing_started_at = ?,
                    updated_at = ?
                WHERE
                    id IN (' . $placeholders . ')
                ',
                    $parameters
                );

            $database
                ->transCommit();

            foreach ($rows as &$row) {
                $row['status'] =
                    self::STATUS_PROCESSING;
            }

            unset(
                $row
            );

            return $rows;
        } catch (\Throwable $exception) {
            if (
                $database
                ->transStatus() !== false
            ) {
                $database
                    ->transRollback();
            }

            throw $exception;
        }
    }
}
