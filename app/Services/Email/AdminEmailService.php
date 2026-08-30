<?php

declare(strict_types=1);

namespace App\Services\Email;

final class AdminEmailService
{
    public function __construct(
        private readonly EmailRegistry $registry,
        private readonly EmailQueueService $queueService
    ) {}

    public function queueInvitation(
        string $recipientEmail,
        string $adminName,
        string $invitationUrl,
        int $expiresInHours,
        int $invitationId
    ): int {
        $definition =
            $this->registry->get(
                EmailRegistry
                ::ADMIN_INVITATION
            );

        return $this->queueService
            ->enqueue(
                recipientEmail: $recipientEmail,

                recipientName: $adminName,

                subject: $definition->subject,

                viewName: $definition->viewName,

                viewData: [
                    'adminName' =>
                    $adminName,

                    'invitationUrl' =>
                    $invitationUrl,

                    'expiresInHours' =>
                    $expiresInHours,
                ],

                priority: $definition->priority,

                maxAttempts: $definition->maxAttempts,

                referenceType: 'ADMIN_INVITATION',

                referenceId: $invitationId
            );
    }
}
