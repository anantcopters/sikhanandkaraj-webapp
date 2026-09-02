<?php

declare(strict_types=1);

namespace App\Services\Communication;

use App\Models\CommunicationEventModel;
use App\Services\Email\EmailQueueService;
use App\Services\Email\EmailRegistry;
use App\Services\Email\MemberEmailRecipientService;
use CodeIgniter\Database\BaseConnection;
use JsonException;
use Throwable;

/**
 * Builds member Engagement digest emails from durable communication events.
 *
 * Engagement currently includes:
 *
 * - PROFILE_VIEWED;
 * - PROFILE_SHORTLISTED.
 *
 * Important privacy rule:
 *
 * The email contains aggregate activity only.
 *
 * It does not contain:
 *
 * - another member's name;
 * - profile reference;
 * - profile photo;
 * - contact information;
 * - direct Full Profile URL.
 *
 * The member signs in to Sikhanandkaraj where the existing authorization,
 * block, moderation and membership rules are evaluated again.
 */
final class EngagementDigestService
{
    private const DEFAULT_BATCH_SIZE =
    100;

    private const MAXIMUM_BATCH_SIZE =
    500;

    /**
     * Keep Engagement reservation recovery aligned with the current
     * communication/email operational stale-work threshold.
     */
    private const STALE_PROCESSING_MINUTES =
    10;

    public function __construct(
        private readonly CommunicationEventModel
        $eventModel,

        private readonly CommunicationPolicyService
        $communicationPolicyService,

        private readonly MemberEmailRecipientService
        $recipientService,

        private readonly EmailRegistry
        $emailRegistry,

        private readonly EmailQueueService
        $emailQueueService,

        private readonly BaseConnection
        $database
    ) {}

    /**
     * Queue due Engagement digest emails.
     *
     * @return array{
     *     recipients:int,
     *     queued:int,
     *     skipped:int,
     *     events:int,
     *     failed:int
     * }
     */
    public function processDue(
        string $frequency,
        int $limit = self::DEFAULT_BATCH_SIZE
    ): array {
        $frequency =
            $this->normaliseFrequency(
                $frequency
            );

        $limit =
            max(
                1,
                min(
                    self::MAXIMUM_BATCH_SIZE,
                    $limit
                )
            );

        /*
        * Recover Engagement events left in PROCESSING by an interrupted previous
        * digest run.
        */
        $this
            ->eventModel
            ->releaseStaleEngagementProcessing(
                self::STALE_PROCESSING_MINUTES
            );

        /*
        * Explicitly opted-out members must not accumulate an Engagement backlog.
        */
        $this
            ->eventModel
            ->consumeOptedOutEngagementEvents();

        /*
        * Apply frequency before LIMIT so another frequency cannot consume this
        * worker's batch capacity.
        */
        $recipientUserIds =
            $this
            ->eventModel
            ->pendingEngagementRecipientIds(
                $frequency,
                $limit
            );

        $result = [
            'recipients' =>
            count(
                $recipientUserIds
            ),

            'queued' =>
            0,

            'skipped' =>
            0,

            'events' =>
            0,

            'failed' =>
            0,
        ];

        foreach ($recipientUserIds as $recipientUserId) {
            try {
                $processed =
                    $this
                    ->processRecipient(
                        $recipientUserId,
                        $frequency
                    );

                if ($processed['queued']) {
                    $result['queued']++;

                    $result['events'] +=
                        $processed['events'];

                    continue;
                }

                $result['skipped']++;
            } catch (Throwable $exception) {
                $result['failed']++;

                log_message(
                    'error',
                    'Engagement digest could not be processed. '
                        . 'Recipient user ID: {userId}; '
                        . 'Frequency: {frequency}; '
                        . 'Error: {error}',
                    [
                        'userId' =>
                        $recipientUserId,

                        'frequency' =>
                        $frequency,

                        'error' =>
                        $exception
                            ->getMessage(),
                    ]
                );
            }
        }

        return $result;
    }

    /**
     * Process one member's pending Engagement events.
     *
     * @return array{
     *     queued:bool,
     *     events:int
     * }
     */
    private function processRecipient(
        int $recipientUserId,
        string $frequency
    ): array {
        /*
        * CommunicationPolicyService remains the single authority for the
        * member's configured Engagement frequency.
        *
        * The database query already filters recipients by frequency, but the
        * policy is deliberately checked again immediately before processing.
        * This protects against a preference change between batch discovery and
        * recipient processing.
        */
        $decision =
            $this
            ->communicationPolicyService
            ->emailDeliveryDecision(
                $recipientUserId,
                CommunicationCategory
                ::ENGAGEMENT
            );

        if ($decision !== $frequency) {
            return [
                'queued' =>
                false,

                'events' =>
                0,
            ];
        }

        /*
        * Reserve this member's currently pending Engagement events.
        *
        * PostgreSQL SKIP LOCKED in CommunicationEventModel prevents two digest
        * workers from consuming the same member activity concurrently.
        */
        $events =
            $this
            ->eventModel
            ->reserveEngagementForRecipient(
                $recipientUserId
            );

        if ($events === []) {
            return [
                'queued' =>
                false,

                'events' =>
                0,
            ];
        }

        /*
        * Keep only valid persisted event IDs.
        *
        * These exact IDs are acknowledged only after the digest has entered the
        * durable email queue.
        */
        $eventIds =
            array_values(
                array_filter(
                    array_map(
                        static fn(array $event): int =>
                        (int) (
                            $event['id']
                            ?? 0
                        ),
                        $events
                    ),
                    static fn(int $eventId): bool =>
                    $eventId > 0
                )
            );

        if ($eventIds === []) {
            /*
            * This should not normally occur because reserved communication
            * events are persisted rows. Return them to PENDING rather than
            * leaving an invalid reservation in PROCESSING.
            */
            throw new \RuntimeException(
                'Reserved Engagement events do not contain valid event IDs.'
            );
        }

        /*
        * Normal member communication requires a verified primary email.
        *
        * Reuse MemberEmailRecipientService so Engagement digest delivery follows
        * exactly the same recipient authority as the existing member email
        * workflows.
        */
        $recipient =
            $this
            ->recipientService
            ->verifiedPrimaryEmail(
                $recipientUserId
            );

        if ($recipient === null) {
            /*
            * No verified primary email means this digest is intentionally
            * skipped.
            *
            * Consume the reserved events so activity which occurred while the
            * member was not eligible for email is not delivered later as a stale
            * digest after an email address is verified.
            */
            $this
                ->eventModel
                ->markSkippedEngagementIds(
                    $eventIds,
                    'Engagement email skipped because no verified primary email was available.'
                );

            return [
                'queued' =>
                false,

                'events' =>
                count(
                    $eventIds
                ),
            ];
        }

        try {
            /*
            * The digest contains aggregate activity only.
            *
            * No actor name, profile URL, photo, contact information or other
            * member PII is copied into the external communication.
            */
            $summary =
                $this
                ->summarise(
                    $events
                );

            $definition =
                $this
                ->emailRegistry
                ->get(
                    EmailRegistry
                    ::MEMBER_ENGAGEMENT_DIGEST
                );

            /*
            * The existing email queue remains the durable email-channel
            * boundary.
            *
            * This service does not make an SMTP/provider connection directly.
            */
            $this
                ->emailQueueService
                ->enqueue(
                    recipientEmail: $recipient['email'],

                    recipientName: $recipient['name'],

                    subject: $definition->subject,

                    viewName: $definition->viewName,

                    viewData: [
                        'userName' =>
                        $recipient['name'] !== ''
                            ? $recipient['name']
                            : 'Member',

                        'profileViewCount' =>
                        $summary['profileViewCount'],

                        'uniqueViewerCount' =>
                        $summary['uniqueViewerCount'],

                        'shortlistCount' =>
                        $summary['shortlistCount'],

                        'actionUrl' =>
                        base_url(
                            'dashboard'
                        ),

                        'actionLabel' =>
                        'View Your Dashboard',
                    ],

                    priority: $definition->priority,

                    maxAttempts: $definition->maxAttempts
                );

            /*
            * Acknowledge the communication events only after the digest has
            * successfully entered the durable email queue.
            */
            $this
                ->eventModel
                ->markProcessedIds(
                    $eventIds
                );

            /*
            * IMPORTANT:
            *
            * The current file is missing this successful return. Without it PHP
            * reaches the end of a method declared ": array" and raises a
            * TypeError after successfully queueing the digest.
            */
            return [
                'queued' =>
                true,

                'events' =>
                count(
                    $eventIds
                ),
            ];
        } catch (Throwable $exception) {
            /*
            * Queue/render/definition failure must not lose Engagement activity.
            *
            * Return the reserved events to PENDING so a later digest run can
            * retry them.
            */
            $this
                ->eventModel
                ->releaseIds(
                    $eventIds,
                    $exception
                        ->getMessage()
                );

            throw $exception;
        }
    }

    /**
     * @param array<int, array<string, mixed>> $events
     *
     * @return array{
     *     profileViewCount:int,
     *     uniqueViewerCount:int,
     *     shortlistCount:int
     * }
     */
    private function summarise(
        array $events
    ): array {
        $profileViewCount =
            0;

        $shortlistCount =
            0;

        $uniqueViewerIds =
            [];

        foreach ($events as $event) {
            $eventKey =
                trim(
                    (string) (
                        $event['event_key']
                        ?? ''
                    )
                );

            $payload =
                $this
                ->decodePayload(
                    (string) (
                        $event['payload_json']
                        ?? ''
                    )
                );

            if (
                $eventKey ===
                CommunicationEventRegistry
                ::PROFILE_VIEWED
            ) {
                $profileViewCount++;

                $actorUserId =
                    (int) (
                        $payload['actor_user_id']
                        ?? 0
                    );

                if ($actorUserId > 0) {
                    $uniqueViewerIds[$actorUserId] = true;
                }

                continue;
            }

            if (
                $eventKey ===
                CommunicationEventRegistry
                ::PROFILE_SHORTLISTED
            ) {
                $shortlistCount++;
            }
        }

        return [
            'profileViewCount' =>
            $profileViewCount,

            'uniqueViewerCount' =>
            count(
                $uniqueViewerIds
            ),

            'shortlistCount' =>
            $shortlistCount,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodePayload(
        string $payload
    ): array {
        $payload =
            trim(
                $payload
            );

        if ($payload === '') {
            return [];
        }

        try {
            $decoded =
                json_decode(
                    $payload,
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );
        } catch (JsonException $exception) {
            throw new \RuntimeException(
                'Engagement event payload is invalid.',
                0,
                $exception
            );
        }

        return is_array(
            $decoded
        )
            ? $decoded
            : [];
    }

    private function normaliseFrequency(
        string $frequency
    ): string {
        $frequency =
            mb_strtoupper(
                trim(
                    $frequency
                )
            );

        if (
            !in_array(
                $frequency,
                [
                    CommunicationDeliveryDecision
                    ::DAILY,

                    CommunicationDeliveryDecision
                    ::WEEKLY,
                ],
                true
            )
        ) {
            throw new \InvalidArgumentException(
                'Engagement digest frequency must be DAILY or WEEKLY.'
            );
        }

        return $frequency;
    }
}
