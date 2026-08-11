<?php

declare(strict_types=1);

namespace App\Services\Email;

use App\Models\EmailQueueAttemptModel;
use App\Support\InfrastructureErrorContext;
use RuntimeException;
use Throwable;

/**
 * Processes reserved email queue records.
 */
final class EmailQueueWorker
{
    private EmailQueueService $queueService;

    private MailService $mailService;

    private EmailQueueAttemptModel $attemptModel;

    public function __construct(
        ?EmailQueueService $queueService = null,
        ?MailService $mailService = null,
        ?EmailQueueAttemptModel $attemptModel = null
    ) {
        $this->queueService =
            $queueService
            ?? new EmailQueueService();

        $this->mailService =
            $mailService
            ?? new MailService();

        $this->attemptModel =
            $attemptModel
            ?? new EmailQueueAttemptModel();
    }

    /**
     * Process one bounded queue batch.
     *
     * @return array{
     *     reserved:int,
     *     sent:int,
     *     retried:int,
     *     failed:int
     * }
     */
    public function process(
        int $limit = 5,
        ?string $workerName = null
    ): array {
        $resolvedWorkerName = trim(
            $workerName
                ?? $this->buildWorkerName()
        );

        if ($resolvedWorkerName === '') {
            throw new RuntimeException(
                'The email worker name is unavailable.'
            );
        }

        $resolvedWorkerName = mb_substr(
            $resolvedWorkerName,
            0,
            100
        );

        /*
         * Reservation failure aborts the batch and is logged by the outer
         * script boundary.
         */
        $emails = $this->queueService
            ->reserveBatch(
                $limit,
                $resolvedWorkerName
            );

        $result = [
            'reserved' =>
            count($emails),

            'sent' =>
            0,

            'retried' =>
            0,

            'failed' =>
            0,
        ];

        foreach ($emails as $email) {
            $startedAt = microtime(true);

            $attemptId = $this->attemptModel
                ->insert(
                    [
                        'email_queue_id' =>
                        $email->id,

                        'attempt_number' =>
                        $email->attemptNumber,

                        'status' =>
                        'STARTED',

                        'started_at' =>
                        date(
                            'Y-m-d H:i:s'
                        ),

                        'worker_name' =>
                        $resolvedWorkerName,
                    ],
                    true
                );

            if (!is_numeric($attemptId)) {
                /*
                 * Do not send SMTP without a matching attempt record.
                 *
                 * This failure is swallowed so the worker can continue with
                 * other messages, therefore the worker owns the error log.
                 */
                service(
                    'applicationErrorLogger'
                )->error(
                    'Email queue attempt record could not be created.',
                    InfrastructureErrorContext::forOperation(
                        operation: 'email_queue_attempt_create',

                        component: self::class,

                        method: __FUNCTION__,

                        additionalContext: [
                            'email_queue_id' =>
                            $email->id,

                            'attempt_number' =>
                            $email->attemptNumber,

                            'worker_name' =>
                            $resolvedWorkerName,
                        ]
                    ),
                    'error'
                );

                try {
                    $this->queueService
                        ->releaseForRetry(
                            $email,
                            'Email attempt record could not be created.'
                        );

                    $result['retried']++;
                } catch (Throwable $exception) {
                    /*
                     * Queue-state recovery failed. This can leave the record in
                     * PROCESSING and requires operational attention.
                     */
                    service(
                        'applicationErrorLogger'
                    )->exception(
                        $exception,
                        'critical',
                        InfrastructureErrorContext::forOperation(
                            operation: 'email_queue_attempt_recovery',

                            component: self::class,

                            method: __FUNCTION__,

                            additionalContext: [
                                'email_queue_id' =>
                                $email->id,

                                'attempt_number' =>
                                $email->attemptNumber,

                                'worker_name' =>
                                $resolvedWorkerName,
                            ]
                        )
                    );

                    $result['failed']++;
                }

                continue;
            }

            try {
                $this->mailService
                    ->sendTemplate(
                        $email->recipientEmail,
                        $email->recipientName,
                        $email->subject,
                        $email->viewName,
                        $email->viewData
                    );

                $this->queueService
                    ->markSent(
                        $email->id
                    );

                $this->completeAttempt(
                    (int) $attemptId,
                    'SENT',
                    $startedAt
                );

                $result['sent']++;
            } catch (Throwable $exception) {
                $safeError = mb_substr(
                    trim(
                        $exception->getMessage()
                    ),
                    0,
                    5000
                );

                try {
                    $queueStatus =
                        $this->queueService
                        ->markFailed(
                            $email,
                            $safeError
                        );

                    $attemptStatus =
                        $queueStatus === 'RETRY'
                        ? 'RETRY'
                        : 'FAILED';

                    $this->completeAttempt(
                        (int) $attemptId,
                        $attemptStatus,
                        $startedAt,
                        $safeError,
                        $this->mailService
                            ->getLastDebugOutput()
                    );

                    if (
                        $attemptStatus === 'RETRY'
                    ) {
                        /*
                         * Intermediate delivery failures are preserved in
                         * email_queue and email_queue_attempts. Avoid creating
                         * an application_error_logs row for every retry.
                         */
                        $result['retried']++;
                    } else {
                        /*
                         * The terminal failure was already logged by
                         * EmailQueueService::markFailed().
                         */
                        $result['failed']++;
                    }
                } catch (Throwable $stateException) {
                    /*
                     * Failure to update queue or attempt state is separate from
                     * the original SMTP failure and requires an error record.
                     */
                    service(
                        'applicationErrorLogger'
                    )->exception(
                        $stateException,
                        'critical',
                        InfrastructureErrorContext::forOperation(
                            operation: 'email_queue_failure_state_update',

                            component: self::class,

                            method: __FUNCTION__,

                            additionalContext: [
                                'email_queue_id' =>
                                $email->id,

                                'attempt_id' =>
                                (int) $attemptId,

                                'attempt_number' =>
                                $email->attemptNumber,

                                'worker_name' =>
                                $resolvedWorkerName,

                                'recipient_domain' =>
                                InfrastructureErrorContext
                                    ::emailDomain(
                                        $email
                                            ->recipientEmail
                                    ),

                                'view_name' =>
                                mb_substr(
                                    $email->viewName,
                                    0,
                                    255
                                ),
                            ]
                        )
                    );

                    $result['failed']++;
                }
            }
        }

        return $result;
    }

    /**
     * Complete one email delivery-attempt record.
     */
    private function completeAttempt(
        int $attemptId,
        string $status,
        float $startedAt,
        ?string $error = null,
        ?string $smtpDebug = null
    ): void {
        if ($attemptId <= 0) {
            throw new RuntimeException(
                'Email attempt log could not be created.'
            );
        }

        $durationMs = (int) round(
            (
                microtime(true)
                - $startedAt
            ) * 1000
        );

        $updated = $this->attemptModel
            ->update(
                $attemptId,
                [
                    'status' =>
                    $status,

                    'completed_at' =>
                    date(
                        'Y-m-d H:i:s'
                    ),

                    'duration_ms' =>
                    $durationMs,

                    'error_message' =>
                    $error === null
                        ? null
                        : mb_substr(
                            $error,
                            0,
                            5000
                        ),

                    /*
                     * SMTP diagnostics remain in the dedicated attempt table.
                     * They are not copied into application_error_logs.
                     */
                    'smtp_debug' =>
                    $smtpDebug === null
                        ? null
                        : mb_substr(
                            $smtpDebug,
                            0,
                            10000
                        ),
                ]
            );

        if ($updated === false) {
            throw new RuntimeException(
                'The email attempt record could not be completed.'
            );
        }
    }

    /**
     * Build a bounded worker name for queue locking and diagnostics.
     */
    private function buildWorkerName(): string
    {
        $hostname = gethostname();

        return mb_substr(
            sprintf(
                '%s:%d',
                $hostname !== false
                    ? $hostname
                    : 'unknown',
                getmypid()
            ),
            0,
            100
        );
    }
}
