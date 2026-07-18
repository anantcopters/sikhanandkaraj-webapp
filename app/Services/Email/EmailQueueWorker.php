<?php

declare(strict_types=1);

namespace App\Services\Email;

use App\Models\EmailQueueAttemptModel;
use RuntimeException;
use Throwable;

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
            $queueService ?? new EmailQueueService();

        $this->mailService =
            $mailService ?? new MailService();

        $this->attemptModel =
            $attemptModel ?? new EmailQueueAttemptModel();
    }

    /**
     * @return array{reserved:int,sent:int,retried:int,failed:int}
     */
    public function process(
        int $limit = 5,
        ?string $workerName = null
    ): array {
        $workerName ??= $this->buildWorkerName();

        $emails = $this->queueService->reserveBatch(
            $limit,
            $workerName
        );

        $result = [
            'reserved' => count($emails),
            'sent' => 0,
            'retried' => 0,
            'failed' => 0,
        ];

        foreach ($emails as $email) {
            $startedAt = microtime(true);
            $startedDate = date('Y-m-d H:i:s');

            $attemptId = $this->attemptModel->insert([
                'email_queue_id' => $email->id,
                'attempt_number' => $email->attemptNumber,
                'status' => 'STARTED',
                'started_at' => $startedDate,
                'worker_name' => $workerName,
            ], true);

            try {
                $this->mailService->sendTemplate(
                    $email->recipientEmail,
                    $email->recipientName,
                    $email->subject,
                    $email->viewName,
                    $email->viewData
                );

                $this->queueService->markSent(
                    $email->id
                );

                $this->completeAttempt(
                    (int) $attemptId,
                    'SENT',
                    $startedAt
                );

                $result['sent']++;
            } catch (Throwable $exception) {
                $error = $exception->getMessage();

                $queueStatus =
                    $this->queueService->markFailed(
                        $email,
                        $error
                    );

                $attemptStatus =
                    $queueStatus === 'RETRY'
                    ? 'RETRY'
                    : 'FAILED';

                $this->completeAttempt(
                    (int) $attemptId,
                    $attemptStatus,
                    $startedAt,
                    $error,
                    $this->mailService->getLastDebugOutput()
                );

                if ($attemptStatus === 'RETRY') {
                    $result['retried']++;
                } else {
                    $result['failed']++;
                }

                log_message(
                    'error',
                    'Queued email {queueId} attempt {attempt} '
                        . 'finished with {status}: {error}',
                    [
                        'queueId' => $email->id,
                        'attempt' => $email->attemptNumber,
                        'status' => $attemptStatus,
                        'error' => $error,
                    ]
                );
            }
        }

        return $result;
    }

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
            (microtime(true) - $startedAt) * 1000
        );

        $this->attemptModel->update($attemptId, [
            'status' => $status,
            'completed_at' => date('Y-m-d H:i:s'),
            'duration_ms' => $durationMs,
            'error_message' => $error === null
                ? null
                : mb_substr($error, 0, 5000),
            'smtp_debug' => $smtpDebug === null
                ? null
                : mb_substr($smtpDebug, 0, 10000),
        ]);
    }

    private function buildWorkerName(): string
    {
        $hostname = gethostname();

        return substr(
            ($hostname !== false ? $hostname : 'unknown')
                . ':'
                . getmypid(),
            0,
            100
        );
    }
}
