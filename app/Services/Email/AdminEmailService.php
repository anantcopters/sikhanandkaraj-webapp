<?php

declare(strict_types=1);

namespace App\Services\Email;

final class AdminEmailService
{
    private const PROFILE_NOTIFICATION_EMAIL =
    'info@sikhanandkaraj.com';

    private const PROFILE_NOTIFICATION_NAME =
    'Sikhanandkaraj';

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

    /**
     * Queue an internal notification after a new profile has been
     * successfully created.
     *
     * This method deliberately uses the existing durable email queue.
     * Profile creation must never depend on an SMTP connection.
     */
    public function queueNewProfileCreated(
        string $fullName,
        string $gender,
        string $mobileNumber,
        string $profileReference,
        string $source,
        int $referenceId
    ): int {
        $definition =
            $this->registry->get(
                EmailRegistry
                ::ADMIN_NEW_PROFILE_CREATED
            );

        $resolvedGender =
            mb_strtoupper(
                trim($gender)
            );

        $genderDisplay =
            match ($resolvedGender) {
                'MALE' =>
                'Male',

                'FEMALE' =>
                'Female',

                default =>
                ucfirst(
                    mb_strtolower(
                        trim($gender)
                    )
                ),
            };

        $resolvedSource =
            mb_strtoupper(
                trim($source)
            );

        $sourceDisplay =
            match ($resolvedSource) {
                'REGISTRATION' =>
                'Registration',

                'PRELAUNCH' =>
                'Pre-launch Profile',

                default =>
                trim($source),
            };

        return $this->queueService
            ->enqueue(
                recipientEmail: self::PROFILE_NOTIFICATION_EMAIL,

                recipientName: self::PROFILE_NOTIFICATION_NAME,

                subject: $definition->subject,

                viewName: $definition->viewName,

                viewData: [
                    'fullName' =>
                    trim($fullName),

                    'gender' =>
                    $genderDisplay,

                    'mobileNumber' =>
                    trim($mobileNumber),

                    'profileReference' =>
                    trim($profileReference),

                    'source' =>
                    $sourceDisplay,
                ],

                priority: $definition->priority,

                maxAttempts: $definition->maxAttempts,

                referenceType: 'NEW_PROFILE_' . $resolvedSource,

                referenceId: $referenceId
            );
    }
}
