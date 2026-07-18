<?php

declare(strict_types=1);

namespace App\Services\Email;

use App\Models\EmailQueueModel;
use CodeIgniter\Database\BaseConnection;
use InvalidArgumentException;
use JsonException;
use RuntimeException;
use Throwable;

final class EmailQueueService
{
    private const DEFAULT_MAX_ATTEMPTS = 3;

    private BaseConnection $database;

    private EmailQueueModel $queueModel;

    public function __construct(
        ?BaseConnection $database = null,
        ?EmailQueueModel $queueModel = null
    ) {
        $this->database = $database ?? db_connect();
        $this->queueModel = $queueModel ?? new EmailQueueModel();
    }

    /**
     * Add an email to the queue without making an SMTP connection.
     *
     * @param array<string, mixed> $viewData
     */
    public function enqueue(
        string $recipientEmail,
        string $recipientName,
        string $subject,
        string $viewName,
        array $viewData = [],
        int $priority = 100,
        int $maxAttempts = self::DEFAULT_MAX_ATTEMPTS,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $availableAt = null
    ): int {
        $recipientEmail = strtolower(trim($recipientEmail));
        $recipientName = trim($recipientName);
        $subject = trim($subject);
        $viewName = trim($viewName);

        if (
            $recipientEmail === ''
            || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)
        ) {
            throw new InvalidArgumentException(
                'A valid recipient email address is required.'
            );
        }

        if ($subject === '') {
            throw new InvalidArgumentException(
                'Email subject cannot be empty.'
            );
        }

        if ($viewName === '') {
            throw new InvalidArgumentException(
                'Email view name cannot be empty.'
            );
        }

        if ($maxAttempts < 1 || $maxAttempts > 10) {
            throw new InvalidArgumentException(
                'Maximum attempts must be between 1 and 10.'
            );
        }

        try {
            $encodedViewData = json_encode(
                $viewData,
                JSON_THROW_ON_ERROR
                    | JSON_UNESCAPED_SLASHES
                    | JSON_UNESCAPED_UNICODE
            );
        } catch (JsonException $exception) {
            throw new InvalidArgumentException(
                'Email view data could not be encoded.',
                0,
                $exception
            );
        }

        $queueId = $this->queueModel->insert([
            'queue_name' => 'default',
            'recipient_email' => $recipientEmail,
            'recipient_name' => $recipientName,
            'subject' => $subject,
            'view_name' => $viewName,
            'view_data' => $encodedViewData,
            'status' => EmailQueueModel::STATUS_PENDING,
            'priority' => max(1, min($priority, 1000)),
            'attempts' => 0,
            'max_attempts' => $maxAttempts,
            'available_at' => $availableAt
                ?? date('Y-m-d H:i:s'),
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
        ], true);

        if (!is_numeric($queueId)) {
            throw new RuntimeException(
                'Email could not be added to the queue.'
            );
        }

        return (int) $queueId;
    }

    /**
     * Atomically reserve pending emails.
     *
     * PostgreSQL SKIP LOCKED prevents two workers from reserving the
     * same records.
     *
     * @return list<QueuedEmail>
     */
    public function reserveBatch(
        int $limit,
        string $workerName
    ): array {
        $limit = max(1, min($limit, 20));
        $workerName = substr(trim($workerName), 0, 100);

        $this->releaseStaleJobs();

        $sql = <<<'SQL'
            WITH selected_jobs AS
            (
                SELECT id
                FROM email_queue
                WHERE status = 'PENDING'
                  AND available_at <= CURRENT_TIMESTAMP
                  AND attempts < max_attempts
                ORDER BY
                    priority ASC,
                    available_at ASC,
                    id ASC
                FOR UPDATE SKIP LOCKED
                LIMIT ?
            )
            UPDATE email_queue AS queue
            SET
                status = 'PROCESSING',
                attempts = queue.attempts + 1,
                locked_at = CURRENT_TIMESTAMP,
                locked_by = ?,
                updated_at = CURRENT_TIMESTAMP
            FROM selected_jobs
            WHERE queue.id = selected_jobs.id
            RETURNING queue.*;
        SQL;

        $this->database->transBegin();

        try {
            $query = $this->database->query(
                $sql,
                [
                    $limit,
                    $workerName,
                ]
            );

            $rows = $query->getResultArray();

            if (!$this->database->transStatus()) {
                throw new RuntimeException(
                    'Email queue reservation transaction failed.'
                );
            }

            $this->database->transCommit();
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw new RuntimeException(
                'Unable to reserve queued emails.',
                0,
                $exception
            );
        }

        $emails = [];

        foreach ($rows as $row) {
            $viewData = json_decode(
                (string) ($row['view_data'] ?? '{}'),
                true
            );

            $emails[] = new QueuedEmail(
                id: (int) $row['id'],
                recipientEmail: (string) $row['recipient_email'],
                recipientName: (string) ($row['recipient_name'] ?? ''),
                subject: (string) $row['subject'],
                viewName: (string) $row['view_name'],
                viewData: is_array($viewData) ? $viewData : [],
                attemptNumber: (int) $row['attempts'],
                maxAttempts: (int) $row['max_attempts']
            );
        }

        return $emails;
    }

    public function markSent(int $queueId): void
    {
        $this->queueModel->update($queueId, [
            'status' => EmailQueueModel::STATUS_SENT,
            'sent_at' => date('Y-m-d H:i:s'),
            'failed_at' => null,
            'locked_at' => null,
            'locked_by' => null,
            'last_error' => null,
        ]);
    }

    public function markFailed(
        QueuedEmail $email,
        string $error
    ): string {
        $error = $this->truncateError($error);

        if ($email->attemptNumber >= $email->maxAttempts) {
            $this->queueModel->update($email->id, [
                'status' => EmailQueueModel::STATUS_FAILED,
                'failed_at' => date('Y-m-d H:i:s'),
                'locked_at' => null,
                'locked_by' => null,
                'last_error' => $error,
            ]);

            return EmailQueueModel::STATUS_FAILED;
        }

        $retryDelayMinutes = $this->retryDelayMinutes(
            $email->attemptNumber
        );

        $this->queueModel->update($email->id, [
            'status' => EmailQueueModel::STATUS_PENDING,
            'available_at' => date(
                'Y-m-d H:i:s',
                strtotime("+{$retryDelayMinutes} minutes")
            ),
            'locked_at' => null,
            'locked_by' => null,
            'last_error' => $error,
        ]);

        return 'RETRY';
    }

    /**
     * Recover records abandoned after a worker crash.
     */
    private function releaseStaleJobs(): void
    {
        $this->database->query(
            <<<'SQL'
                UPDATE email_queue
                SET
                    status = CASE
                        WHEN attempts >= max_attempts
                            THEN 'FAILED'
                        ELSE 'PENDING'
                    END,
                    failed_at = CASE
                        WHEN attempts >= max_attempts
                            THEN CURRENT_TIMESTAMP
                        ELSE failed_at
                    END,
                    available_at = CASE
                        WHEN attempts < max_attempts
                            THEN CURRENT_TIMESTAMP
                        ELSE available_at
                    END,
                    locked_at = NULL,
                    locked_by = NULL,
                    last_error = COALESCE(
                        last_error,
                        'Worker lock expired before completion.'
                    ),
                    updated_at = CURRENT_TIMESTAMP
                WHERE status = 'PROCESSING'
                  AND locked_at <
                      CURRENT_TIMESTAMP - INTERVAL '10 minutes'
            SQL
        );
    }

    /**
     * Retry after 2, 10, then 30 minutes.
     */
    private function retryDelayMinutes(
        int $attemptNumber
    ): int {
        return match ($attemptNumber) {
            1 => 2,
            2 => 10,
            default => 30,
        };
    }

    private function truncateError(string $error): string
    {
        $error = trim($error);

        if ($error === '') {
            return 'Unknown email delivery failure.';
        }

        return mb_substr($error, 0, 5000);
    }

    public function releaseForRetry(
        QueuedEmail $email,
        string $error
    ): void {
        $this->queueModel->update($email->id, [
            'status' => EmailQueueModel::STATUS_PENDING,
            'available_at' => date(
                'Y-m-d H:i:s',
                strtotime('+2 minutes')
            ),
            'locked_at' => null,
            'locked_by' => null,
            'last_error' => $this->truncateError($error),
        ]);
    }
}
