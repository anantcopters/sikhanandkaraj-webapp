<?php

declare(strict_types=1);

namespace App\Services\Messaging;

use App\Models\MemberBlockModel;
use App\Models\MemberConversationModel;
use App\Models\MemberInterestModel;
use App\Models\MemberMessageModel;
use App\Models\MemberMessageReportModel;
use App\Models\UserModel;
use App\Services\Membership\MembershipEntitlementService;
use App\Services\Membership\MembershipService;
use App\Services\Notification\MemberNotificationService;
use App\Models\MemberMatchCandidateModel;
use App\Services\Matchmaking\MemberProfilePresentationService;
use CodeIgniter\Database\BaseConnection;
use Config\MemberMessaging;
use DomainException;
use RuntimeException;
use Throwable;

final class MemberMessagingService
{
    public function __construct(
        private readonly UserModel
        $userModel,

        private readonly MemberConversationModel
        $conversationModel,

        private readonly MemberMessageModel
        $messageModel,

        private readonly MemberMessageReportModel
        $reportModel,

        private readonly MemberInterestModel
        $interestModel,

        private readonly MemberBlockModel
        $blockModel,

        private readonly MemberMatchCandidateModel
        $candidateModel,

        private readonly MemberProfilePresentationService
        $profilePresentationService,

        private readonly MembershipService
        $membershipService,

        private readonly MembershipEntitlementService
        $entitlementService,

        private readonly MemberNotificationService
        $notificationService,

        private readonly BaseConnection
        $database,

        private readonly MemberMessaging
        $configuration
    ) {}

    public function unreadCount(
        int $userId
    ): int {
        return $this
            ->messageModel
            ->unreadCount(
                $userId
            );
    }

    public function unreadConversationCount(
        int $userId
    ): int {
        return $this
            ->messageModel
            ->unreadConversationCount(
                $userId
            );
    }

    /**
     * Called from the existing Interest transaction.
     */
    public function interestSent(
        int $interestId,
        int $fromUserId,
        int $toUserId,
        string $profileReference
    ): void {
        $conversation = $this
            ->findOrCreateConversation(
                firstUserId: $fromUserId,
                secondUserId: $toUserId,
                createdFrom: MemberConversationModel
                ::CREATED_FROM_INTEREST,
                manualInitiatorUserId: null
            );

        $message = str_replace(
            '{PROFILE_ID}',
            $profileReference,
            $this->configuration
                ->interestMessage
        );

        $this->insertSystemEvent(
            conversationId: (int) $conversation['id'],

            interestId: $interestId,

            eventType: MemberMessageModel
            ::EVENT_INTEREST_SENT,

            message: $message
        );
    }

    public function interestAccepted(
        int $interestId,
        int $fromUserId,
        int $toUserId
    ): void {
        $conversation = $this
            ->findOrCreateConversation(
                $fromUserId,
                $toUserId,
                MemberConversationModel
                ::CREATED_FROM_INTEREST,
                null
            );

        $now =
            date('Y-m-d H:i:s');

        $this->conversationModel
            ->update(
                (int) $conversation['id'],
                [
                    'status' =>
                    MemberConversationModel
                    ::STATUS_ACTIVE,

                    'closed_at' =>
                    null,

                    'updated_at' =>
                    $now,
                ]
            );

        $this->insertSystemEvent(
            (int) $conversation['id'],
            $interestId,
            MemberMessageModel
            ::EVENT_INTEREST_ACCEPTED,
            'Interest Accepted'
        );
    }

    public function interestDeclined(
        int $interestId,
        int $fromUserId,
        int $toUserId
    ): void {
        $conversation = $this
            ->findOrCreateConversation(
                $fromUserId,
                $toUserId,
                MemberConversationModel
                ::CREATED_FROM_INTEREST,
                null
            );

        $now =
            date('Y-m-d H:i:s');

        $this->conversationModel
            ->update(
                (int) $conversation['id'],
                [
                    'status' =>
                    MemberConversationModel
                    ::STATUS_CLOSED_DECLINED,

                    'closed_at' =>
                    $now,

                    'updated_at' =>
                    $now,
                ]
            );

        $this->insertSystemEvent(
            (int) $conversation['id'],
            $interestId,
            MemberMessageModel
            ::EVENT_INTEREST_DECLINED,
            'Interest Declined'
        );
    }

    public function interestWithdrawn(
        int $interestId,
        int $fromUserId,
        int $toUserId
    ): void {
        $conversation = $this
            ->conversationModel
            ->between(
                $fromUserId,
                $toUserId
            );

        if (!is_array($conversation)) {
            return;
        }

        $conversationId =
            (int) $conversation['id'];

        /*
         * Product rule:
         *
         * withdrawal before recipient manual reply closes the conversation;
         * withdrawal after recipient reply leaves the established conversation.
         */
        $recipientHasReplied = $this
            ->messageModel
            ->recipientHasReplied(
                $conversationId,
                $toUserId
            );

        if (!$recipientHasReplied) {
            $now =
                date('Y-m-d H:i:s');

            $this->conversationModel
                ->update(
                    $conversationId,
                    [
                        'status' =>
                        MemberConversationModel
                        ::STATUS_CLOSED_WITHDRAWN,

                        'closed_at' =>
                        $now,

                        'updated_at' =>
                        $now,
                    ]
                );
        }

        $this->insertSystemEvent(
            $conversationId,
            $interestId,
            MemberMessageModel
            ::EVENT_INTEREST_WITHDRAWN,
            'Interest Withdrawn'
        );
    }

    public function send(
        int $senderUserId,
        int $recipientUserId,
        string $message,
        string $clientRequestId
    ): int {
        $message =
            trim($message);

        $clientRequestId =
            trim($clientRequestId);

        if (!$this->configuration->enabled) {
            throw new DomainException(
                'Messaging is temporarily unavailable.'
            );
        }

        if (
            $message === ''
            || mb_strlen($message)
            > $this->configuration
            ->maximumMessageLength
        ) {
            throw new DomainException(
                'Please enter a message of no more than '
                    . $this->configuration
                    ->maximumMessageLength
                    . ' characters.'
            );
        }

        if (
            $clientRequestId === ''
            || mb_strlen(
                $clientRequestId
            ) > 64
        ) {
            throw new DomainException(
                'The message request is invalid.'
            );
        }

        if (
            !$this->entitlementService
                ->canSendMessage(
                    $senderUserId
                )
        ) {
            throw new DomainException(
                'Messaging is available with membership. '
                    . 'You can receive and read messages from members. '
                    . 'Upgrade to start conversations and reply.'
            );
        }

        $this->database
            ->transBegin();

        try {
            /*
             * Same deterministic pair-locking pattern already used by
             * MemberInteractionService.
             */
            $this->database->query(
                'SELECT id '
                    . 'FROM users '
                    . 'WHERE id IN (?, ?) '
                    . 'ORDER BY id '
                    . 'FOR UPDATE',
                [
                    $senderUserId,
                    $recipientUserId,
                ]
            );

            $this->assertActivePair(
                $senderUserId,
                $recipientUserId
            );

            if (
                $this->blockModel
                ->existsBetween(
                    $senderUserId,
                    $recipientUserId
                )
            ) {
                throw new DomainException(
                    'Messaging is unavailable for this member.'
                );
            }

            /*
             * Retry/idempotency check after pair lock.
             */
            $existing = $this
                ->messageModel
                ->where(
                    'sender_user_id',
                    $senderUserId
                )
                ->where(
                    'client_request_id',
                    $clientRequestId
                )
                ->first();

            if (is_array($existing)) {
                $this->database
                    ->transCommit();

                return (int) $existing['id'];
            }

            $conversation = $this
                ->conversationModel
                ->between(
                    $senderUserId,
                    $recipientUserId
                );

            $newManualConversation =
                !is_array($conversation);

            if (!is_array($conversation)) {
                $conversation = $this
                    ->findOrCreateConversation(
                        $senderUserId,
                        $recipientUserId,
                        MemberConversationModel
                        ::CREATED_FROM_MANUAL,
                        $senderUserId
                    );
            }

            $this->assertConversationOpen(
                $conversation
            );

            $this->assertInterestState(
                $senderUserId,
                $recipientUserId
            );

            $this->assertLimits(
                senderUserId: $senderUserId,

                recipientUserId: $recipientUserId,

                conversationId: (int) $conversation['id'],

                newManualConversation: $newManualConversation
            );

            $messageId = $this
                ->messageModel
                ->insert(
                    [
                        'conversation_id' =>
                        (int) $conversation['id'],

                        'sender_user_id' =>
                        $senderUserId,

                        'recipient_user_id' =>
                        $recipientUserId,

                        'message_type' =>
                        MemberMessageModel
                        ::TYPE_MEMBER,

                        'event_type' =>
                        null,

                        'interest_id' =>
                        null,

                        'message_text' =>
                        $message,

                        'client_request_id' =>
                        $clientRequestId,

                        'created_at' =>
                        date(
                            'Y-m-d H:i:s'
                        ),
                    ],
                    true
                );

            if (
                !is_numeric($messageId)
                || (int) $messageId <= 0
            ) {
                throw new RuntimeException(
                    'The message could not be saved.'
                );
            }

            $now =
                date('Y-m-d H:i:s');

            $this->conversationModel
                ->update(
                    (int) $conversation['id'],
                    [
                        'last_message_at' =>
                        $now,

                        'updated_at' =>
                        $now,
                    ]
                );

            /*
             * In-app notification is authoritative application state and can
             * participate in the same transaction, matching current Interest
             * behaviour.
             *
             * Do NOT copy the private message body into the notification.
             */
            $sender = $this
                ->userModel
                ->find(
                    $senderUserId
                );

            $profileReference =
                is_array($sender)
                ? trim(
                    (string) (
                        $sender['profile_ref_number']
                        ?? ''
                    )
                )
                : '';

            $this->notificationService
                ->create(
                    [
                        'recipientUserId' =>
                        $recipientUserId,

                        'actorUserId' =>
                        $senderUserId,

                        'type' =>
                        'MESSAGE',

                        'title' =>
                        'New Message',

                        'message' =>
                        $profileReference !== ''
                            ? 'You have a new message from '
                            . $profileReference
                            . '.'
                            : 'You have a new message.',

                        'entityType' =>
                        'MEMBER_MESSAGE',

                        'entityId' =>
                        (int) $messageId,

                        'targetUrl' =>
                        '/messages/'
                            . (int) $conversation['id'],
                    ]
                );

            if (
                $this->database
                ->transStatus()
                === false
            ) {
                throw new RuntimeException(
                    'The message transaction failed.'
                );
            }

            $this->database
                ->transCommit();

            return (int) $messageId;
        } catch (Throwable $exception) {
            $this->database
                ->transRollback();

            throw $exception;
        }
    }

    /**
     * @return array<string,mixed>
     */
    public function conversation(
        int $conversationId,
        int $userId,
        bool $markRead = true
    ): array {
        $conversation = $this
            ->conversationModel
            ->find(
                $conversationId
            );

        if (
            !is_array($conversation)
            || !$this->isParticipant(
                $conversation,
                $userId
            )
        ) {
            throw new DomainException(
                'The conversation could not be found.'
            );
        }

        if ($markRead) {
            $this->messageModel
                ->markConversationRead(
                    $conversationId,
                    $userId
                );
        }

        $otherUserId =
            (int) $conversation['first_user_id'] === $userId
            ? (int) $conversation['second_user_id']
            : (int) $conversation['first_user_id'];

        $member = $this
            ->visibleCandidate(
                $userId,
                $otherUserId
            );

        if (!is_array($member)) {
            throw new DomainException(
                'The member is no longer available.'
            );
        }

        $interest = $this
            ->interestModel
            ->relationshipBetween(
                $userId,
                $otherUserId
            );

        $profile = $this
            ->profilePresentationService
            ->summary(
                viewerUserId: $userId,

                member: $member,

                hasInterestRelationship: is_array($interest)
            );

        if (!is_array($profile)) {
            throw new DomainException(
                'The member is no longer available.'
            );
        }

        return [
            'conversation' =>
            $conversation,

            'otherUserId' =>
            $otherUserId,

            'member' =>
            $profile,

            'messages' =>
            $this->memberVisibleMessages(
                $conversationId,
                $userId
            ),

            'composer' =>
            $this->composerState(
                $conversation,
                $userId,
                $otherUserId
            ),

            'safetyWarning' =>
            $this->configuration
                ->safetyWarning,
        ];
    }

    /**
     * Build presentation state for a new conversation before
     * its first message has been persisted.
     *
     * @return array<string,mixed>
     */
    public function draftConversation(
        int $userId,
        int $otherUserId
    ): array {
        $this->assertActivePair(
            $userId,
            $otherUserId
        );

        if (
            $this->blockModel
            ->existsBetween(
                $userId,
                $otherUserId
            )
        ) {
            throw new DomainException(
                'Messaging is unavailable for this member.'
            );
        }

        $member = $this
            ->visibleCandidate(
                $userId,
                $otherUserId
            );

        if (!is_array($member)) {
            throw new DomainException(
                'The member is no longer available.'
            );
        }

        $interest = $this
            ->interestModel
            ->relationshipBetween(
                $userId,
                $otherUserId
            );

        $profile = $this
            ->profilePresentationService
            ->summary(
                viewerUserId: $userId,

                member: $member,

                hasInterestRelationship: is_array($interest)
            );

        if (!is_array($profile)) {
            throw new DomainException(
                'The member is no longer available.'
            );
        }

        /*
     * A declined Interest cannot be bypassed by opening
     * the direct Message entry point.
     */
        $this->assertInterestState(
            $userId,
            $otherUserId
        );

        return [
            'conversation' =>
            null,

            'otherUserId' =>
            $otherUserId,

            'member' =>
            $profile,

            'messages' =>
            [],

            'composer' => [
                'enabled' =>
                $this->entitlementService
                    ->canSendMessage(
                        $userId
                    ),

                'reason' =>
                '',

                'showUpgrade' =>
                false,
            ],

            'safetyWarning' =>
            $this->configuration
                ->safetyWarning,

            'isDraft' =>
            true,
        ];
    }

    /**
     * Build the member-facing conversation-list contract.
     *
     * Current profile/privacy state is resolved on every request. Messaging
     * therefore never becomes a cache/bypass for old private profile data.
     *
     * @return list<array<string,mixed>>
     */
    public function conversations(
        int $userId
    ): array {
        $rows = $this
            ->conversationModel
            ->listingForMember(
                $userId
            );

        if ($rows === []) {
            return [];
        }

        $result = [];

        foreach ($rows as $row) {
            $otherUserId = max(
                0,
                (int) (
                    $row['other_user_id']
                    ?? 0
                )
            );

            if ($otherUserId <= 0) {
                continue;
            }

            /*
         * Use the existing member candidate projection. Do not query raw
         * full_name/photo/contact fields from the messaging view.
         */
            $member = $this
                ->candidateModel
                ->findCandidateForViewer(
                    $userId,
                    $otherUserId
                );

            if (!is_array($member)) {
                continue;
            }

            $interest = $this
                ->interestModel
                ->relationshipBetween(
                    $userId,
                    $otherUserId
                );

            $profile = $this
                ->profilePresentationService
                ->summary(
                    viewerUserId: $userId,

                    member: $member,

                    hasInterestRelationship: is_array($interest)
                );

            if (!is_array($profile)) {
                continue;
            }

            $preview = trim(
                (string) (
                    $row['latest_message_text']
                    ?? ''
                )
            );

            if (
                !empty($row['latest_removed_at'])
            ) {
                $preview =
                    'This message was removed by SikhanandKaraj moderation.';
            }

            $result[] = [
                'id' =>
                (int) $row['id'],

                'member' =>
                $profile,

                'preview' =>
                $preview,

                'lastMessageAt' =>
                trim(
                    (string) (
                        $row['last_message_at']
                        ?? ''
                    )
                ),

                'unreadCount' =>
                max(
                    0,
                    (int) (
                        $row['unread_count']
                        ?? 0
                    )
                ),

                'status' =>
                trim(
                    (string) (
                        $row['status']
                        ?? ''
                    )
                ),
            ];
        }

        return $result;
    }

    public function reportMessage(
        int $reporterUserId,
        int $messageId,
        string $reason,
        string $comment
    ): bool {
        $reason =
            mb_strtoupper(
                trim($reason)
            );

        $comment =
            trim($comment);

        $allowedReasons = [
            'HARASSMENT',
            'ASKING_FOR_MONEY',
            'FAKE_IDENTITY',
            'INAPPROPRIATE',
            'UNWANTED_CONTACT',
            'SPAM',
            'OTHER',
        ];

        if (
            !in_array(
                $reason,
                $allowedReasons,
                true
            )
        ) {
            throw new DomainException(
                'Please select a valid report reason.'
            );
        }

        if (
            mb_strlen($comment)
            > 500
        ) {
            throw new DomainException(
                'Report comment cannot exceed 500 characters.'
            );
        }

        $message = $this
            ->messageModel
            ->find(
                $messageId
            );

        if (
            !is_array($message)
            || (
                $message['message_type']
                ?? ''
            ) !== MemberMessageModel
            ::TYPE_MEMBER
        ) {
            throw new DomainException(
                'The message could not be found.'
            );
        }

        /*
         * Only the recipient may report the received member-authored message.
         */
        if (
            (int) (
                $message['recipient_user_id']
                ?? 0
            ) !== $reporterUserId
        ) {
            throw new DomainException(
                'You cannot report this message.'
            );
        }

        $existing = $this
            ->reportModel
            ->where(
                'message_id',
                $messageId
            )
            ->where(
                'reporter_user_id',
                $reporterUserId
            )
            ->first();

        if (is_array($existing)) {
            return false;
        }

        $reportId = $this
            ->reportModel
            ->insert(
                [
                    'message_id' =>
                    $messageId,

                    'conversation_id' =>
                    (int) $message['conversation_id'],

                    'reporter_user_id' =>
                    $reporterUserId,

                    /*
                     * Never trust this from the browser.
                     */
                    'reported_user_id' =>
                    (int) $message['sender_user_id'],

                    'reason' =>
                    $reason,

                    'comment' =>
                    $comment !== ''
                        ? $comment
                        : null,

                    'created_at' =>
                    date(
                        'Y-m-d H:i:s'
                    ),
                ],
                true
            );

        if (!is_numeric($reportId)) {
            throw new RuntimeException(
                'The message could not be reported.'
            );
        }

        return true;
    }

    public function existingConversationBetween(
        int $firstUserId,
        int $secondUserId
    ): ?array {
        return $this
            ->conversationModel
            ->between(
                $firstUserId,
                $secondUserId
            );
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function memberVisibleMessages(
        int $conversationId,
        int $viewerUserId
    ): array {
        $messages = $this
            ->messageModel
            ->conversationMessages(
                $conversationId
            );

        foreach ($messages as &$message) {
            $message['isMine'] =
                (int) (
                    $message['sender_user_id']
                    ?? 0
                ) === $viewerUserId;

            $message['isSystem'] =
                (
                    $message['message_type']
                    ?? ''
                ) === MemberMessageModel
                ::TYPE_SYSTEM;

            $message['isRemoved'] =
                !empty($message['removed_at']);

            if (
                $message['isRemoved']
                && !$message['isSystem']
            ) {
                $message['message_text'] =
                    'This message was removed by SikhanandKaraj moderation.';
            }

            $message['state'] =
                !empty($message['read_at'])
                ? 'Read'
                : 'Sent';
        }

        unset($message);

        return $messages;
    }

    /**
     * @return array{
     *     enabled:bool,
     *     reason:string,
     *     showUpgrade:bool,
     *     upgradeLabel?:string
     * }
     */
    private function composerState(
        array $conversation,
        int $userId,
        int $otherUserId
    ): array {
        /*
     * Safety restrictions always take precedence over
     * commercial membership state.
     */
        if (
            $this->blockModel
            ->existsBetween(
                $userId,
                $otherUserId
            )
        ) {
            return [
                'enabled' =>
                false,

                'reason' =>
                'Messaging is unavailable for this member.',

                'showUpgrade' =>
                false,
            ];
        }

        $status = trim(
            (string) (
                $conversation['status']
                ?? ''
            )
        );

        if (
            $status ===
            MemberConversationModel
            ::STATUS_CLOSED_DECLINED
        ) {
            return [
                'enabled' =>
                false,

                'reason' =>
                'This conversation is closed because the Interest was declined.',

                'showUpgrade' =>
                false,
            ];
        }

        if (
            $status ===
            MemberConversationModel
            ::STATUS_CLOSED_WITHDRAWN
        ) {
            return [
                'enabled' =>
                false,

                'reason' =>
                'This conversation is closed because the Interest was withdrawn.',

                'showUpgrade' =>
                false,
            ];
        }

        /*
     * Entitlement remains the authoritative decision for
     * whether the member can manually send.
     */
        if (
            $this->entitlementService
            ->canSendMessage(
                $userId
            )
        ) {
            return [
                'enabled' =>
                true,

                'reason' =>
                '',

                'showUpgrade' =>
                false,
            ];
        }

        /*
     * No paid entitlement.
     *
     * Distinguish a member who previously had a paid
     * membership from a member who has always been Free.
     */
        if (
            $this->membershipService
            ->hasExpiredPaidMembership(
                $userId
            )
        ) {
            return [
                'enabled' =>
                false,

                'reason' =>
                'Your membership has expired. '
                    . 'Renew your membership to continue this conversation.',

                'showUpgrade' =>
                true,

                'upgradeLabel' =>
                'Renew Membership',
            ];
        }

        return [
            'enabled' =>
            false,

            'reason' =>
            'Want to continue this conversation? '
                . 'Upgrade your membership to reply.',

            'showUpgrade' =>
            true,

            'upgradeLabel' =>
            'View Plans',
        ];
    }

    private function assertLimits(
        int $senderUserId,
        int $recipientUserId,
        int $conversationId,
        bool $newManualConversation
    ): void {
        $membership = $this
            ->membershipService
            ->resolveForUser(
                $senderUserId
            );

        $planCode =
            mb_strtoupper(
                trim(
                    (string) (
                        $membership['accountType']
                        ?? 'FREE'
                    )
                )
            );

        $limits =
            $this->configuration
                ->limits[$planCode]
            ?? $this->configuration
                ->limits['FREE'];

        /*
         * Existing application stores timestamps without offsets and uses
         * application date/time consistently. Keep the same convention.
         */
        $dayStart =
            date('Y-m-d 00:00:00');

        if ($newManualConversation) {
            $started = $this
                ->conversationModel
                ->countManualStartedToday(
                    $senderUserId,
                    $dayStart
                );

            if (
                $started
                >= $limits['newConversations']
            ) {
                throw new DomainException(
                    'You have reached today\'s new conversation limit.'
                );
            }
        }

        $perMember = $this
            ->messageModel
            ->countOutgoingToMemberSince(
                $senderUserId,
                $recipientUserId,
                $dayStart
            );

        if (
            $perMember
            >= $limits['perMember']
        ) {
            throw new DomainException(
                'You have reached today\'s messaging limit for this member.'
            );
        }

        $total = $this
            ->messageModel
            ->countOutgoingSince(
                $senderUserId,
                $dayStart
            );

        if (
            $total
            >= $limits['totalOutgoing']
        ) {
            throw new DomainException(
                'You have reached your messaging limit for today.'
            );
        }

        $latest = $this
            ->messageModel
            ->latestManualMessages(
                $conversationId,
                $this->configuration
                    ->maximumConsecutiveUnanswered
            );

        $consecutive = 0;

        foreach ($latest as $row) {
            if (
                (int) (
                    $row['sender_user_id']
                    ?? 0
                ) !== $senderUserId
            ) {
                break;
            }

            ++$consecutive;
        }

        if (
            $consecutive
            >= $this->configuration
            ->maximumConsecutiveUnanswered
        ) {
            throw new DomainException(
                'You have already sent messages to this member. '
                    . 'Please wait for them to respond.'
            );
        }
    }

    private function assertActivePair(
        int $senderUserId,
        int $recipientUserId
    ): void {
        if (
            $senderUserId <= 0
            || $recipientUserId <= 0
            || $senderUserId
            === $recipientUserId
        ) {
            throw new DomainException(
                'The member could not be resolved.'
            );
        }

        foreach (
            [
                $senderUserId,
                $recipientUserId,
            ]
            as $userId
        ) {
            $user = $this
                ->userModel
                ->find(
                    $userId
                );

            if (
                !is_array($user)
                || (
                    $user['account_status']
                    ?? ''
                ) !== UserModel::STATUS_ACTIVE
            ) {
                throw new DomainException(
                    'Messaging is unavailable for this member.'
                );
            }
        }
    }

    private function assertInterestState(
        int $firstUserId,
        int $secondUserId
    ): void {
        $interest = $this
            ->interestModel
            ->relationshipBetween(
                $firstUserId,
                $secondUserId
            );

        if (!is_array($interest)) {
            return;
        }

        $status =
            mb_strtoupper(
                trim(
                    (string) (
                        $interest['status']
                        ?? ''
                    )
                )
            );

        if (
            $status ===
            MemberInterestModel
            ::STATUS_DECLINED
        ) {
            throw new DomainException(
                'This conversation is closed because the Interest was declined.'
            );
        }

        /*
         * A withdrawn Interest may still have an established conversation.
         * Conversation status is therefore the authority for whether sending
         * remains possible after withdrawal.
         */
    }

    private function assertConversationOpen(
        array $conversation
    ): void {
        $status =
            (string) (
                $conversation['status']
                ?? ''
            );

        if (
            $status ===
            MemberConversationModel
            ::STATUS_CLOSED_DECLINED
        ) {
            throw new DomainException(
                'This conversation is closed because the Interest was declined.'
            );
        }

        if (
            $status ===
            MemberConversationModel
            ::STATUS_CLOSED_WITHDRAWN
        ) {
            throw new DomainException(
                'This conversation is closed because the Interest was withdrawn.'
            );
        }
    }

    private function findOrCreateConversation(
        int $firstUserId,
        int $secondUserId,
        string $createdFrom,
        ?int $manualInitiatorUserId
    ): array {
        $existing = $this
            ->conversationModel
            ->between(
                $firstUserId,
                $secondUserId
            );

        if (is_array($existing)) {
            return $existing;
        }

        [
            $orderedFirst,
            $orderedSecond,
        ] = $firstUserId < $secondUserId
            ? [
                $firstUserId,
                $secondUserId,
            ]
            : [
                $secondUserId,
                $firstUserId,
            ];

        $now =
            date('Y-m-d H:i:s');

        $conversationId = $this
            ->conversationModel
            ->insert(
                [
                    'first_user_id' =>
                    $orderedFirst,

                    'second_user_id' =>
                    $orderedSecond,

                    'status' =>
                    MemberConversationModel
                    ::STATUS_ACTIVE,

                    'created_from' =>
                    $createdFrom,

                    'manual_initiated_by_user_id' =>
                    $manualInitiatorUserId,

                    'created_at' =>
                    $now,

                    'updated_at' =>
                    $now,
                ],
                true
            );

        if (
            !is_numeric(
                $conversationId
            )
        ) {
            throw new RuntimeException(
                'The conversation could not be created.'
            );
        }

        $conversation = $this
            ->conversationModel
            ->find(
                (int) $conversationId
            );

        if (!is_array($conversation)) {
            throw new RuntimeException(
                'The conversation could not be resolved.'
            );
        }

        return $conversation;
    }

    private function insertSystemEvent(
        int $conversationId,
        int $interestId,
        string $eventType,
        string $message
    ): void {
        $existing = $this
            ->messageModel
            ->where(
                'interest_id',
                $interestId
            )
            ->where(
                'event_type',
                $eventType
            )
            ->first();

        if (is_array($existing)) {
            return;
        }

        $messageId = $this
            ->messageModel
            ->insert(
                [
                    'conversation_id' =>
                    $conversationId,

                    'sender_user_id' =>
                    null,

                    'recipient_user_id' =>
                    null,

                    'message_type' =>
                    MemberMessageModel
                    ::TYPE_SYSTEM,

                    'event_type' =>
                    $eventType,

                    'interest_id' =>
                    $interestId,

                    'message_text' =>
                    $message,

                    'created_at' =>
                    date(
                        'Y-m-d H:i:s'
                    ),
                ],
                true
            );

        if (!is_numeric($messageId)) {
            throw new RuntimeException(
                'The conversation event could not be saved.'
            );
        }

        $now =
            date('Y-m-d H:i:s');

        $this->conversationModel
            ->update(
                $conversationId,
                [
                    'last_message_at' =>
                    $now,

                    'updated_at' =>
                    $now,
                ]
            );
    }

    private function isParticipant(
        array $conversation,
        int $userId
    ): bool {
        return
            (int) (
                $conversation['first_user_id']
                ?? 0
            ) === $userId
            ||
            (int) (
                $conversation['second_user_id']
                ?? 0
            ) === $userId;
    }

    /**
     * Resolve one messaging counterpart through the existing
     * member-facing candidate/privacy pipeline.
     *
     * @return array<string,mixed>|null
     */
    private function visibleCandidate(
        int $viewerUserId,
        int $candidateUserId
    ): ?array {
        if (
            $viewerUserId <= 0
            || $candidateUserId <= 0
        ) {
            return null;
        }

        $viewer = $this
            ->userModel
            ->find(
                $viewerUserId
            );

        if (!is_array($viewer)) {
            return null;
        }

        $viewerGender = mb_strtoupper(
            trim(
                (string) (
                    $viewer['gender']
                    ?? ''
                )
            )
        );

        if ($viewerGender === '') {
            return null;
        }

        $candidates = $this
            ->candidateModel
            ->visibleCandidatesByIds(
                $viewerUserId,
                $viewerGender,
                [
                    $candidateUserId,
                ]
            );

        $candidate =
            $candidates[0]
            ?? null;

        return is_array($candidate)
            ? $candidate
            : null;
    }
}
