<?php

declare(strict_types=1);

namespace App\Services\Email;

use InvalidArgumentException;

final class TestEmailService
{
    public function __construct(
        private readonly EmailRegistry $registry,
        private readonly EmailQueueService $queueService
    ) {}

    public function queue(
        string $definitionKey,
        string $recipientEmail
    ): int {
        $recipientEmail =
            mb_strtolower(
                trim($recipientEmail)
            );

        if (
            $recipientEmail === ''
            || filter_var(
                $recipientEmail,
                FILTER_VALIDATE_EMAIL
            ) === false
        ) {
            throw new InvalidArgumentException(
                'A valid test email address is required.'
            );
        }

        $definition =
            $this->registry->get(
                $definitionKey
            );

        return $this->queueService
            ->enqueue(
                recipientEmail: $recipientEmail,

                recipientName: 'SikhanandKaraj QA',

                subject: '[TEST] '
                    . $definition->subject,

                viewName: $definition->viewName,

                viewData: $definition->previewData,

                priority: $definition->priority,

                maxAttempts: $definition->maxAttempts,

                referenceType: 'EMAIL_TEMPLATE_TEST',

                referenceId: null
            );
    }
}
