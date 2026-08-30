<?php

declare(strict_types=1);

namespace App\Services\Email;

final class MemberEmailService
{
    public function __construct(
        private readonly EmailRegistry $registry,
        private readonly EmailQueueService $queueService
    ) {}

    public function queueEmailVerification(
        string $recipientEmail,
        string $recipientName,
        string $verificationUrl,
        int $expiresInHours,
        bool $isReplacement,
        int $verificationTokenId
    ): int {
        $definition =
            $this->registry->get(
                EmailRegistry
                ::MEMBER_EMAIL_VERIFICATION
            );

        return $this->queueService
            ->enqueue(
                recipientEmail: $recipientEmail,

                recipientName: $recipientName,

                subject: $definition->subject,

                viewName: $definition->viewName,

                viewData: [
                    'userName' =>
                    $recipientName !== ''
                        ? $recipientName
                        : 'Member',

                    'emailAddress' =>
                    $recipientEmail,

                    'verificationUrl' =>
                    $verificationUrl,

                    'expiresInHours' =>
                    $expiresInHours,

                    'isReplacement' =>
                    $isReplacement,
                ],

                priority: $definition->priority,

                maxAttempts: $definition->maxAttempts,

                referenceType: 'EMAIL_VERIFICATION_TOKEN',

                referenceId: $verificationTokenId
            );
    }
}
