<?php

declare(strict_types=1);

namespace App\Services\Email;

use Throwable;

final class MemberEmailService
{
    public function __construct(
        private readonly EmailRegistry $registry,
        private readonly EmailQueueService $queueService,
        private readonly MemberEmailRecipientService $recipientService
    ) {}

    /**
     * Email Verification is deliberately different
     * from normal member communication.
     *
     * The destination is not verified yet, therefore
     * it must not pass through verifiedPrimaryEmail().
     */
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

    /**
     * Queue an Interest Received email.
     *
     * Failure to queue an optional external
     * communication must never fail the
     * matrimonial action.
     */
    public function queueInterestReceived(
        int $recipientUserId,
        string $recipientName,
        int $interestId
    ): ?int {
        return $this->queueMemberCommunication(
            recipientUserId: $recipientUserId,

            recipientName: $recipientName,

            definitionKey: EmailRegistry
            ::MEMBER_INTEREST_RECEIVED,

            viewData: [
                'heading' =>
                'You received a new Interest',

                'message' =>
                'A member has shown Interest in your profile.',

                'actionUrl' =>
                base_url(
                    'members/interests'
                ),

                'actionLabel' =>
                'View Interest',
            ],

            referenceType: 'MEMBER_INTEREST',

            referenceId: $interestId
        );
    }

    /**
     * Queue an Interest response email.
     */
    public function queueInterestResponse(
        int $recipientUserId,
        string $recipientName,
        int $interestId,
        string $status
    ): ?int {
        $status =
            strtoupper(
                trim($status)
            );

        $accepted =
            $status === 'ACCEPTED';

        $definitionKey =
            $accepted
            ? EmailRegistry
            ::MEMBER_INTEREST_ACCEPTED
            : EmailRegistry
            ::MEMBER_INTEREST_DECLINED;

        return $this->queueMemberCommunication(
            recipientUserId: $recipientUserId,

            recipientName: $recipientName,

            definitionKey: $definitionKey,

            viewData: [
                'heading' =>
                $accepted
                    ? 'Your Interest was accepted'
                    : 'An Interest has been updated',

                'message' =>
                $accepted
                    ? 'A member has accepted your Interest.'
                    : 'A member has declined your Interest.',

                'actionUrl' =>
                base_url(
                    'members/interests'
                ),

                'actionLabel' =>
                $accepted
                    ? 'View Interest'
                    : 'View Interests',
            ],

            referenceType: 'MEMBER_INTEREST',

            referenceId: $interestId
        );
    }

    public function queuePhotoRejected(
        int $recipientUserId,
        string $recipientName,
        int $photoId,
        string $reason = ''
    ): ?int {
        $reason =
            trim($reason);

        return $this->queueMemberCommunication(
            recipientUserId: $recipientUserId,

            recipientName: $recipientName,

            definitionKey: EmailRegistry
            ::MEMBER_PHOTO_REJECTED,

            viewData: [
                'heading' =>
                'Your profile photo was not approved',

                'message' =>
                'One of your profile photos was not approved. '
                    . 'Please review the photo guidelines and '
                    . 'upload a suitable replacement.',

                'reason' =>
                $reason,

                'actionUrl' =>
                base_url(
                    'profile/photos'
                ),

                'actionLabel' =>
                'Review Profile Photos',
            ],

            referenceType: 'MEMBER_PHOTO',

            referenceId: $photoId
        );
    }

    public function queueAadhaarApproved(
        int $recipientUserId,
        string $recipientName,
        int $submissionId
    ): ?int {
        return $this->queueMemberCommunication(
            recipientUserId: $recipientUserId,

            recipientName: $recipientName,

            definitionKey: EmailRegistry
            ::MEMBER_AADHAAR_APPROVED,

            viewData: [
                'heading' =>
                'Your Aadhaar verification is approved',

                'message' =>
                'Your Aadhaar verification has been '
                    . 'reviewed and approved.',

                'reason' =>
                '',

                'actionUrl' =>
                base_url(
                    'account-settings/aadhaar'
                ),

                'actionLabel' =>
                'View Verification Status',
            ],

            referenceType: 'AADHAAR_SUBMISSION',

            referenceId: $submissionId
        );
    }

    public function queueAadhaarRejected(
        int $recipientUserId,
        string $recipientName,
        int $submissionId,
        string $reason
    ): ?int {
        return $this->queueMemberCommunication(
            recipientUserId: $recipientUserId,

            recipientName: $recipientName,

            definitionKey: EmailRegistry
            ::MEMBER_AADHAAR_REJECTED,

            viewData: [
                'heading' =>
                'Your Aadhaar verification was not approved',

                'message' =>
                'Your Aadhaar verification has been '
                    . 'reviewed and was not approved.',

                'reason' =>
                trim($reason),

                'actionUrl' =>
                base_url(
                    'account-settings/aadhaar'
                ),

                'actionLabel' =>
                'Review Aadhaar Verification',
            ],

            referenceType: 'AADHAAR_SUBMISSION',

            referenceId: $submissionId
        );
    }

    public function queueVideoModeration(
        int $recipientUserId,
        string $recipientName,
        int $videoId,
        string $status,
        string $reason = ''
    ): ?int {
        $status =
            mb_strtoupper(
                trim($status)
            );

        $reason =
            trim($reason);

        [
            $definitionKey,
            $heading,
            $message,
            $actionLabel,
        ] = match ($status) {
            'APPROVED' => [
                EmailRegistry
                ::MEMBER_VIDEO_APPROVED,

                'Your Video Introduction is approved',

                'Your Video Introduction has been '
                    . 'reviewed and approved. It will follow '
                    . 'the privacy setting selected for your profile.',

                'View Video Introduction',
            ],

            'REJECTED' => [
                EmailRegistry
                ::MEMBER_VIDEO_REJECTED,

                'Your Video Introduction was not approved',

                'Your Video Introduction has been reviewed '
                    . 'and was not approved.',

                'Review Video Introduction',
            ],

            'RESUBMISSION_REQUESTED' => [
                EmailRegistry
                ::MEMBER_VIDEO_RESUBMISSION_REQUESTED,

                'Please resubmit your Video Introduction',

                'Our verification team has requested a new '
                    . 'Video Introduction from you.',

                'Record Video Introduction',
            ],

            default =>
            throw new \InvalidArgumentException(
                'Unsupported Video Introduction moderation status.'
            ),
        };

        return $this->queueMemberCommunication(
            recipientUserId: $recipientUserId,

            recipientName: $recipientName,

            definitionKey: $definitionKey,

            viewData: [
                'heading' =>
                $heading,

                'message' =>
                $message,

                'reason' =>
                $status === 'APPROVED'
                    ? ''
                    : $reason,

                'actionUrl' =>
                base_url(
                    'account-settings/video-introduction'
                ),

                'actionLabel' =>
                $actionLabel,
            ],

            referenceType: 'VIDEO_INTRODUCTION',

            referenceId: $videoId
        );
    }

    /**
     * Central boundary for normal member email.
     *
     * Only the current verified primary EMAIL is
     * eligible for normal application communication.
     *
     * @param array<string, mixed> $viewData
     */
    private function queueMemberCommunication(
        int $recipientUserId,
        string $recipientName,
        string $definitionKey,
        array $viewData,
        ?string $referenceType,
        ?int $referenceId
    ): ?int {
        try {
            $recipient =
                $this->recipientService
                ->verifiedPrimaryEmail(
                    $recipientUserId,
                    $recipientName
                );

            if ($recipient === null) {
                return null;
            }

            $definition =
                $this->registry->get(
                    $definitionKey
                );

            $viewData['userName'] =
                $recipient['name'] !== ''
                ? $recipient['name']
                : 'Member';

            return $this->queueService
                ->enqueue(
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
                'Member email could not be queued. '
                    . 'Definition: {definition}; '
                    . 'Recipient user ID: {userId}; '
                    . 'Error: {error}',
                [
                    'definition' =>
                    $definitionKey,

                    'userId' =>
                    $recipientUserId,

                    'error' =>
                    $exception->getMessage(),
                ]
            );

            return null;
        }
    }
}
