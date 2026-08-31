<?php

declare(strict_types=1);

namespace App\Services\Email;

use App\Models\UserContactModel;
use Throwable;

final class MemberEmailService
{
    private readonly MemberEmailRecipientService $recipientService;

    public function __construct(
        private readonly EmailRegistry $registry,
        private readonly EmailQueueService $queueService,
        ?MemberEmailRecipientService $recipientService = null
    ) {
        $this->recipientService = $recipientService
            ?? new MemberEmailRecipientService(
                new UserContactModel(db_connect())
            );
    }

    /**
     * Email Verification is the deliberate exception to the verified-email
     * prerequisite because this communication establishes verification.
     */
    public function queueEmailVerification(
        string $recipientEmail,
        string $recipientName,
        string $verificationUrl,
        int $expiresInHours,
        bool $isReplacement,
        int $verificationTokenId
    ): int {
        $definition = $this->registry->get(
            EmailRegistry::MEMBER_EMAIL_VERIFICATION
        );

        return $this->queueService->enqueue(
            recipientEmail: $recipientEmail,
            recipientName: $recipientName,
            subject: $definition->subject,
            viewName: $definition->viewName,
            viewData: [
                'userName' => $recipientName !== '' ? $recipientName : 'Member',
                'emailAddress' => $recipientEmail,
                'verificationUrl' => $verificationUrl,
                'expiresInHours' => $expiresInHours,
                'isReplacement' => $isReplacement,
            ],
            priority: $definition->priority,
            maxAttempts: $definition->maxAttempts,
            referenceType: 'EMAIL_VERIFICATION_TOKEN',
            referenceId: $verificationTokenId
        );
    }

    /**
     * Queue a normal member communication only for a verified primary email.
     *
     * Optional communication failure must never roll back or make the domain
     * operation appear unsuccessful, therefore this boundary returns NULL when
     * the member is ineligible or queueing fails.
     *
     * @param array<string, mixed> $viewData
     */
    public function queueMemberCommunication(
        int $recipientUserId,
        string $definitionKey,
        array $viewData,
        ?string $recipientName = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): ?int {
        $recipient = $this->recipientService->verifiedPrimaryEmail(
            $recipientUserId,
            (string) $recipientName
        );

        if ($recipient === null) {
            return null;
        }

        try {
            $definition = $this->registry->get($definitionKey);

            $viewData['recipientName'] =
                trim((string) ($viewData['recipientName'] ?? '')) !== ''
                    ? trim((string) $viewData['recipientName'])
                    : ($recipient['name'] !== '' ? $recipient['name'] : 'Member');

            return $this->queueService->enqueue(
                recipientEmail: $recipient['email'],
                recipientName: $recipient['name'],
                subject: $definition->subject,
                viewName: $definition->viewName,
                viewData: $viewData,
                priority: $definition->priority,
                maxAttempts: $definition->maxAttempts,
                referenceType: $referenceType,
                referenceId: $referenceId
            );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Optional member email could not be queued. Definition={definition}; RecipientUserId={recipientUserId}; Error={error}',
                [
                    'definition' => $definitionKey,
                    'recipientUserId' => $recipientUserId,
                    'error' => $exception->getMessage(),
                ]
            );

            return null;
        }
    }

    public function queueInterestReceived(
        int $recipientUserId,
        int $interestId
    ): ?int {
        return $this->queueMemberCommunication(
            recipientUserId: $recipientUserId,
            definitionKey: EmailRegistry::MEMBER_INTEREST_RECEIVED,
            viewData: [
                'heading' => 'New Interest',
                'message' => 'A member has shown interest in your profile.',
                'actionUrl' => base_url('member/interest?direction=received'),
                'buttonLabel' => 'View Interest',
            ],
            referenceType: 'MEMBER_INTEREST',
            referenceId: $interestId
        );
    }

    public function queueInterestResponse(
        int $recipientUserId,
        int $interestId,
        bool $accepted
    ): ?int {
        return $this->queueMemberCommunication(
            recipientUserId: $recipientUserId,
            definitionKey: $accepted
                ? EmailRegistry::MEMBER_INTEREST_ACCEPTED
                : EmailRegistry::MEMBER_INTEREST_DECLINED,
            viewData: [
                'heading' => $accepted ? 'Interest Accepted' : 'Interest Update',
                'message' => $accepted
                    ? 'A member has accepted your interest.'
                    : 'A member has declined your interest.',
                'actionUrl' => base_url('member/interest?direction=sent'),
                'buttonLabel' => 'View Interests',
            ],
            referenceType: 'MEMBER_INTEREST',
            referenceId: $interestId
        );
    }
}
