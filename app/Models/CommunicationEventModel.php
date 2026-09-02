<?php

declare(strict_types=1);

namespace App\Models;
use App\Services\Communication\CommunicationCategory;

use App\Services\Communication\CommunicationEventRegistry;
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
            /*
            * Engagement events already have real producers, but their digest
            * consumer is introduced in the next phase.
            *
            * Do not reserve and acknowledge them through the transitional generic
            * dispatcher.
            */
            $sql =
                '
                    SELECT
                        *
                    FROM
                        communication_events
                    WHERE
                        status = ?
                        AND available_at <= ?
                        AND event_key NOT IN (?, ?)
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

                        CommunicationEventRegistry
                        ::PROFILE_VIEWED,

                        CommunicationEventRegistry
                        ::PROFILE_SHORTLISTED,

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

    /**
     * Release stale Engagement reservations.
     *
     * A digest process may terminate after reserving events but before either:
     *
     * - queueing the digest and marking the events PROCESSED; or
     * - returning the events to PENDING.
     *
     * Without recovery those PROCESSING rows would never be selected again.
     *
     * This follows the same stale-work recovery principle already used by the
     * durable email queue.
     */
    public function releaseStaleEngagementProcessing(
        int $staleMinutes = 10
    ): int {
        $staleMinutes =
            max(
                1,
                min(
                    60,
                    $staleMinutes
                )
            );

        $result =
            $this
            ->db
            ->query(
                '
            UPDATE
                communication_events
            SET
                status = ?,
                processing_started_at = NULL,
                updated_at = CURRENT_TIMESTAMP,
                last_error = ?
            WHERE
                status = ?
                AND processing_started_at IS NOT NULL
                AND processing_started_at
                    <= CURRENT_TIMESTAMP
                    - (? * INTERVAL \'1 minute\')
                AND event_key IN (?, ?)
            ',
                [
                    self::STATUS_PENDING,

                    'Recovered stale Engagement digest reservation.',

                    self::STATUS_PROCESSING,

                    $staleMinutes,

                    CommunicationEventRegistry
                    ::PROFILE_VIEWED,

                    CommunicationEventRegistry
                    ::PROFILE_SHORTLISTED,
                ]
            );

        return max(
            0,
            $this
                ->db
                ->affectedRows()
        );
    }

    /**
     * Consume pending Engagement events for members who explicitly disabled
     * Engagement email.
     *
     * Engagement defaults to OFF when no preference exists, but only explicit
     * OFF rows are consumed here. A missing preference row is left untouched
     * because it may belong to a member whose preference record has not yet
     * been initialized.
     */
    public function consumeOptedOutEngagementEvents(): int
    {
        $this
            ->db
            ->query(
                '
            UPDATE
                communication_events AS event
            SET
                status = ?,
                processed_at = CURRENT_TIMESTAMP,
                processing_started_at = NULL,
                updated_at = CURRENT_TIMESTAMP,
                last_error = ?
            FROM
                member_communication_preferences AS preference
            WHERE
                event.recipient_user_id =
                    preference.user_id
                AND preference.category = ?
                AND preference.channel = ?
                AND preference.frequency = ?
                AND event.status = ?
                AND event.event_key IN (?, ?)
            ',
                [
                    self::STATUS_PROCESSED,

                    'Engagement email skipped by member communication preference.',

                    CommunicationCategory
                    ::ENGAGEMENT,

                    'EMAIL',

                    'OFF',

                    self::STATUS_PENDING,

                    CommunicationEventRegistry
                    ::PROFILE_VIEWED,

                    CommunicationEventRegistry
                    ::PROFILE_SHORTLISTED,
                ]
            );

        return max(
            0,
            $this
                ->db
                ->affectedRows()
        );
    }

    /**
     * Return recipient IDs having pending Engagement events.
     *
     * The member communication preference is applied before LIMIT so members
     * configured for another digest frequency do not consume this worker's
     * recipient batch.
     *
     * Missing preference rows use the existing Engagement default of OFF.
     *
     * @return list<int>
     */
    public function pendingEngagementRecipientIds(
        string $frequency,
        int $limit
    ): array {
        $frequency =
            mb_strtoupper(
                trim(
                    $frequency
                )
            );

        if (
            !in_array(
                $frequency,
                [
                    'DAILY',
                    'WEEKLY',
                ],
                true
            )
        ) {
            throw new \InvalidArgumentException(
                'Engagement digest frequency must be DAILY or WEEKLY.'
            );
        }

        $limit =
            max(
                1,
                min(
                    500,
                    $limit
                )
            );

        $query =
            $this
            ->db
            ->query(
                '
            SELECT DISTINCT
                event.recipient_user_id
            FROM
                communication_events AS event
            INNER JOIN
                member_communication_preferences AS preference
                    ON preference.user_id =
                        event.recipient_user_id
                    AND preference.category = ?
                    AND preference.channel = ?
                    AND preference.frequency = ?
            WHERE
                event.status = ?
                AND event.available_at <= CURRENT_TIMESTAMP
                AND event.event_key IN (?, ?)
            ORDER BY
                event.recipient_user_id ASC
            LIMIT ?
            ',
                [
                    'ENGAGEMENT',
                    'EMAIL',
                    $frequency,
                    self::STATUS_PENDING,
                    CommunicationEventRegistry::PROFILE_VIEWED,
                    CommunicationEventRegistry::PROFILE_SHORTLISTED,
                    $limit,
                ]
            );

        $rows =
            $query
            ->getResultArray();

        $recipientUserIds = [];

        foreach ($rows as $row) {
            $recipientUserId =
                (int) (
                    $row['recipient_user_id']
                    ?? 0
                );

            if ($recipientUserId > 0) {
                $recipientUserIds[] =
                    $recipientUserId;
            }
        }

        return $recipientUserIds;
    }

    /**
     * Reserve all currently pending Engagement events for one recipient.
     *
     * PostgreSQL row locking follows the same SKIP LOCKED pattern already
     * used by the generic communication dispatcher.
     *
     * @return array<int, array<string, mixed>>
     */
    public function reserveEngagementForRecipient(
        int $recipientUserId
    ): array {
        if ($recipientUserId <= 0) {
            return [];
        }

        $database =
            $this->db;

        $database
            ->transBegin();

        try {
            $rows =
                $database
                ->query(
                    '
                SELECT
                    *
                FROM
                    communication_events
                WHERE
                    recipient_user_id = ?
                    AND status = ?
                    AND available_at <= ?
                    AND event_key IN (?, ?)
                ORDER BY
                    id ASC
                FOR UPDATE SKIP LOCKED
                ',
                    [
                        $recipientUserId,

                        self::STATUS_PENDING,

                        gmdate(
                            'Y-m-d H:i:s'
                        ),

                        CommunicationEventRegistry
                        ::PROFILE_VIEWED,

                        CommunicationEventRegistry
                        ::PROFILE_SHORTLISTED,
                    ]
                )
                ->getResultArray();

            if ($rows === []) {
                $database
                    ->transCommit();

                return [];
            }

            $ids =
                array_values(
                    array_map(
                        static fn(array $row): int =>
                        (int) $row['id'],
                        $rows
                    )
                );

            $this
                ->updateStatusForIds(
                    $ids,
                    self::STATUS_PROCESSING,
                    [
                        'processing_started_at' =>
                        gmdate(
                            'Y-m-d H:i:s'
                        ),

                        'last_error' =>
                        null,
                    ]
                );

            if (
                $database
                ->transStatus()
                === false
            ) {
                throw new \RuntimeException(
                    'Engagement events could not be reserved.'
                );
            }

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
            $database
                ->transRollback();

            throw $exception;
        }
    }

    /**
     * Mark digest events processed after the digest has successfully entered
     * the durable email queue.
     *
     * @param list<int> $ids
     */
    public function markProcessedIds(
        array $ids
    ): void {
        $ids =
            $this
            ->normaliseIds(
                $ids
            );

        if ($ids === []) {
            return;
        }

        $this
            ->updateStatusForIds(
                $ids,
                self::STATUS_PROCESSED,
                [
                    'processing_started_at' =>
                    null,

                    'processed_at' =>
                    gmdate(
                        'Y-m-d H:i:s'
                    ),

                    'last_error' =>
                    null,
                ]
            );
    }

    /**
     * Return reserved events to PENDING when the digest could not be queued.
     *
     * @param list<int> $ids
     */
    public function releaseIds(
        array $ids,
        string $error
    ): void {
        $ids =
            $this
            ->normaliseIds(
                $ids
            );

        if ($ids === []) {
            return;
        }

        $this
            ->updateStatusForIds(
                $ids,
                self::STATUS_PENDING,
                [
                    'processing_started_at' =>
                    null,

                    'last_error' =>
                    mb_substr(
                        trim(
                            $error
                        ),
                        0,
                        1000
                    ),
                ]
            );
    }

    /**
     * Mark Engagement events as intentionally consumed when current policy or
     * recipient eligibility means that no email should be generated.
     *
     * These events must not remain PENDING indefinitely. If the member later
     * changes communication preferences or verifies an email address, old
     * Engagement activity must not suddenly generate a stale digest.
     *
     * @param list<int> $ids
     */
    public function markSkippedEngagementIds(
        array $ids,
        string $reason
    ): void {
        $ids =
            $this
            ->normaliseIds(
                $ids
            );

        if ($ids === []) {
            return;
        }

        $this
            ->updateStatusForIds(
                $ids,
                self::STATUS_PROCESSED,
                [
                    'processing_started_at' =>
                    null,

                    'processed_at' =>
                    gmdate(
                        'Y-m-d H:i:s'
                    ),

                    /*
                 * There is intentionally no additional SKIPPED status.
                 *
                 * The existing schema remains unchanged. last_error records
                 * why no downstream email was generated.
                 */
                    'last_error' =>
                    mb_substr(
                        trim(
                            $reason
                        ),
                        0,
                        1000
                    ),
                ]
            );
    }

    /**
     * @param list<int> $ids
     * @param array<string, mixed> $additionalValues
     */
    private function updateStatusForIds(
        array $ids,
        string $status,
        array $additionalValues = []
    ): void {
        $ids =
            $this
            ->normaliseIds(
                $ids
            );

        if ($ids === []) {
            return;
        }

        $values =
            array_merge(
                [
                    'status' =>
                    $status,

                    'updated_at' =>
                    gmdate(
                        'Y-m-d H:i:s'
                    ),
                ],
                $additionalValues
            );

        $builder =
            $this
            ->db
            ->table(
                $this->table
            );

        $builder
            ->whereIn(
                'id',
                $ids
            )
            ->update(
                $values
            );
    }

    /**
     * @return list<int>
     */
    private function normaliseIds(
        array $ids
    ): array {
        return array_values(
            array_unique(
                array_filter(
                    array_map(
                        'intval',
                        $ids
                    ),
                    static fn(int $id): bool =>
                    $id > 0
                )
            )
        );
    }
}
