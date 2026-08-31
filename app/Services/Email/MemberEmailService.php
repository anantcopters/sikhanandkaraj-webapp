<?php

declare(strict_types=1);

namespace App\Services\Email;

use App\Support\CurrencyDisplay;
use App\Support\DateDisplay;

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
     * Queue acknowledgement after a member successfully creates
     * a Contact Us request.
     *
     * The member's original message is intentionally not copied into
     * external email. The authenticated application remains the canonical
     * location for support history.
     */
    public function queueSupportRequestReceived(
        int $recipientUserId,
        string $requestReference,
        int $requestId
    ): ?int {
        $requestReference =
            trim(
                $requestReference
            );

        return $this->queueMemberCommunication(
            recipientUserId: $recipientUserId,

            recipientName: '',

            definitionKey: EmailRegistry
            ::MEMBER_SUPPORT_REQUEST_RECEIVED,

            viewData: [
                'heading' =>
                'We received your support request',

                'message' =>
                'Your request has been received by the '
                    . 'Sikhanandkaraj support team.',

                'requestReference' =>
                $requestReference,

                'responseNote' =>
                '',

                'actionUrl' =>
                base_url(
                    'account-settings/contact'
                ),

                'actionLabel' =>
                'View Support Request',
            ],

            referenceType: 'MEMBER_CONTACT_REQUEST',

            referenceId: $requestId
        );
    }

    /**
     * Queue the member-facing support resolution.
     *
     * responseNote is safe to expose because MemberSupportService receives it
     * specifically as "the message to the member". Internal report/moderation
     * notes must never call this method.
     */
    public function queueSupportRequestResolved(
        int $recipientUserId,
        string $requestReference,
        int $requestId,
        string $responseNote
    ): ?int {
        $requestReference =
            trim(
                $requestReference
            );

        $responseNote =
            trim(
                $responseNote
            );

        return $this->queueMemberCommunication(
            recipientUserId: $recipientUserId,

            recipientName: '',

            definitionKey: EmailRegistry
            ::MEMBER_SUPPORT_REQUEST_RESOLVED,

            viewData: [
                'heading' =>
                'Your support request has been resolved',

                'message' =>
                'The Sikhanandkaraj support team has '
                    . 'reviewed and resolved your request.',

                'requestReference' =>
                $requestReference,

                'responseNote' =>
                $responseNote,

                'actionUrl' =>
                base_url(
                    'account-settings/contact'
                ),

                'actionLabel' =>
                'View Support History',
            ],

            referenceType: 'MEMBER_CONTACT_REQUEST',

            referenceId: $requestId
        );
    }

    /**
     * Queue the successful-payment / membership-activation email.
     *
     * Commercial values are supplied from immutable payment/membership
     * snapshots. Provider IDs and raw provider responses must never be passed
     * into the email view.
     */
    public function queueMembershipActivated(
        int $recipientUserId,
        int $membershipId,
        string $planName,
        int $amountPaise,
        string $transactionReference,
        string $expiresAt
    ): ?int {
        $planName =
            trim(
                $planName
            );

        $transactionReference =
            trim(
                $transactionReference
            );

        return $this->queueMemberCommunication(
            recipientUserId: $recipientUserId,

            recipientName: '',

            definitionKey: EmailRegistry
            ::MEMBER_MEMBERSHIP_ACTIVATED,

            viewData: [
                'heading' =>
                'Your membership is active',

                'message' =>
                'Your payment was successful and your '
                    . 'Sikhanandkaraj membership is now active.',

                'planName' =>
                $planName,

                /*
                * Currency formatting belongs to the common
                * presentation Support layer.
                */
                'amount' =>
                CurrencyDisplay::formatIndianRupees(
                    $amountPaise
                ),

                'transactionReference' =>
                $transactionReference,

                /*
                * Membership timestamps are stored in UTC.
                *
                * Reuse the project-wide date presentation
                * boundary rather than defining email-specific
                * timezone/date rules.
                */
                'expiresAt' =>
                DateDisplay::formatUtcDate(
                    $expiresAt,
                    ''
                ),

                'isExpired' =>
                false,

                'actionUrl' =>
                base_url(
                    'account-settings/membership'
                ),

                'actionLabel' =>
                'View Membership',
            ],

            referenceType: 'MEMBER_MEMBERSHIP',

            referenceId: $membershipId
        );
    }

    /**
     * Queue the one-time membership expiry reminder.
     *
     * The reminder is intentionally tied to the purchased membership ID.
     *
     * Idempotency:
     *
     * The morning lifecycle job may be executed more than once. Once this
     * reminder has entered email_queue, another reminder for the same purchased
     * membership must not be created.
     *
     * Delivery retries remain owned by EmailQueueService/email_worker.php.
     */
    public function queueMembershipExpiringSoon(
        int $recipientUserId,
        int $membershipId,
        string $planName,
        string $expiresAt
    ): ?int {
        if (
            $recipientUserId <= 0
            || $membershipId <= 0
        ) {
            return null;
        }

        /*
     * Use a reminder-specific reference type.
     *
     * MEMBER_MEMBERSHIP is already used by activation/expiry communication,
     * therefore using the same reference type here would make those distinct
     * business communications indistinguishable.
     */
        $referenceType =
            'MEMBER_MEMBERSHIP_EXPIRY_REMINDER';

        /*
     * Scheduled jobs must be safe to rerun.
     *
     * A reminder already in PENDING, PROCESSING, SENT or FAILED state counts
     * as having been generated.
     */
        if (
            $this
            ->queueService
            ->hasReference(
                $referenceType,
                $membershipId
            )
        ) {
            return null;
        }

        return $this->queueMemberCommunication(
            recipientUserId: $recipientUserId,

            recipientName: '',

            definitionKey: EmailRegistry
            ::MEMBER_MEMBERSHIP_EXPIRING_SOON,

            viewData: [
                'heading' =>
                'Your membership expires in 3 days',

                'message' =>
                'Your Sikhanandkaraj membership is approaching '
                    . 'its expiry date. Renew your membership to '
                    . 'continue using paid membership features.',

                /*
             * Use the immutable purchased plan snapshot rather
             * than the current membership-plan master.
             */
                'planName' =>
                trim(
                    $planName
                ),

                /*
             * This is not a payment receipt.
             */
                'amount' =>
                '',

                'transactionReference' =>
                '',

                /*
             * Reuse the existing project-wide UTC -> member-facing
             * date presentation boundary.
             */
                'expiresAt' =>
                DateDisplay::formatUtcDate(
                    $expiresAt,
                    ''
                ),

                /*
             * MembershipActivity.php already uses this flag to
             * display "Valid Until" instead of "Expired On".
             */
                'isExpired' =>
                false,

                'actionUrl' =>
                base_url(
                    'account-settings/plans'
                ),

                'actionLabel' =>
                'View Membership Plans',
            ],

            referenceType: $referenceType,

            referenceId: $membershipId
        );
    }

    /**
     * Queue a membership-expired lifecycle notification.
     *
     * This is transactional lifecycle communication. It is not the future
     * renewal-reminder/engagement flow.
     */
    public function queueMembershipExpired(
        int $recipientUserId,
        int $membershipId,
        string $planName,
        string $expiresAt
    ): ?int {
        return $this->queueMemberCommunication(
            recipientUserId: $recipientUserId,

            recipientName: '',

            definitionKey: EmailRegistry
            ::MEMBER_MEMBERSHIP_EXPIRED,

            viewData: [
                'heading' =>
                'Your membership has expired',

                'message' =>
                'Your Sikhanandkaraj membership period has ended. '
                    . 'Your profile remains available, but paid membership '
                    . 'features now follow the current account entitlement.',

                'planName' =>
                trim(
                    $planName
                ),

                'amount' =>
                '',

                'transactionReference' =>
                '',

                'expiresAt' =>
                DateDisplay::formatUtcDate(
                    $expiresAt,
                    ''
                ),

                'isExpired' =>
                true,

                'actionUrl' =>
                base_url(
                    'account-settings/plans'
                ),

                'actionLabel' =>
                'View Membership Plans',
            ],

            referenceType: 'MEMBER_MEMBERSHIP',

            referenceId: $membershipId
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
