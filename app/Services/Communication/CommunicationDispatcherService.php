<?php

declare(strict_types=1);

namespace App\Services\Communication;

use App\Models\CommunicationEventModel;
use JsonException;
use Throwable;

/**
 * Processes durable communication events.
 *
 * Phase 3B establishes the durable orchestration boundary.
 *
 * Channel-specific dispatchers will be added incrementally instead of
 * moving all existing production communication in one risky change.
 */
final class CommunicationDispatcherService
{
    private const DEFAULT_BATCH_SIZE =
    100;

    private const MAX_ATTEMPTS =
    3;

    public function __construct(
        private readonly CommunicationEventModel
        $eventModel
    ) {}

    /**
     * @return array{
     *     reserved:int,
     *     processed:int,
     *     failed:int
     * }
     */
    public function processPending(
        int $batchSize = self::DEFAULT_BATCH_SIZE
    ): array {
        $batchSize =
            max(
                1,
                min(
                    500,
                    $batchSize
                )
            );

        $events =
            $this
            ->eventModel
            ->reserveBatch(
                $batchSize
            );

        $processed =
            0;

        $failed =
            0;

        foreach ($events as $event) {
            try {
                $this
                    ->processEvent(
                        $event
                    );

                $this
                    ->markProcessed(
                        (int) $event['id']
                    );

                $processed++;
            } catch (Throwable $exception) {
                $this
                    ->markFailed(
                        $event,
                        $exception
                    );

                $failed++;
            }
        }

        return [
            'reserved' =>
            count(
                $events
            ),

            'processed' =>
            $processed,

            'failed' =>
            $failed,
        ];
    }

    /**
     * Phase 3B validates and acknowledges the durable event.
     *
     * Phase 3C will attach immediate/digest channel dispatchers here.
     *
     * @param array<string, mixed> $event
     */
    private function processEvent(
        array $event
    ): void {
        $eventKey =
            trim(
                (string) (
                    $event['event_key']
                    ?? ''
                )
            );

        if ($eventKey === '') {
            throw new \RuntimeException(
                'Communication event key is missing.'
            );
        }

        $payload =
            trim(
                (string) (
                    $event['payload_json']
                    ?? ''
                )
            );

        if ($payload === '') {
            return;
        }

        try {
            json_decode(
                $payload,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $exception) {
            throw new \RuntimeException(
                'Communication event payload is invalid.',
                0,
                $exception
            );
        }
    }

    private function markProcessed(
        int $eventId
    ): void {
        $now =
            gmdate(
                'Y-m-d H:i:s'
            );

        $this
            ->eventModel
            ->update(
                $eventId,
                [
                    'status' =>
                    CommunicationEventModel
                    ::STATUS_PROCESSED,

                    'processed_at' =>
                    $now,

                    'processing_started_at' =>
                    null,

                    'last_error' =>
                    null,
                ]
            );
    }

    /**
     * @param array<string, mixed> $event
     */
    private function markFailed(
        array $event,
        Throwable $exception
    ): void {
        $eventId =
            (int) (
                $event['id']
                ?? 0
            );

        $attemptCount =
            (int) (
                $event['attempt_count']
                ?? 0
            ) + 1;

        $terminal =
            $attemptCount >=
            self::MAX_ATTEMPTS;

        $this
            ->eventModel
            ->update(
                $eventId,
                [
                    'status' =>
                    $terminal
                        ? CommunicationEventModel
                        ::STATUS_FAILED
                        : CommunicationEventModel
                        ::STATUS_PENDING,

                    'attempt_count' =>
                    $attemptCount,

                    'available_at' =>
                    $terminal
                        ? gmdate(
                            'Y-m-d H:i:s'
                        )
                        : gmdate(
                            'Y-m-d H:i:s',
                            time() + 300
                        ),

                    'processing_started_at' =>
                    null,

                    'last_error' =>
                    mb_substr(
                        $exception
                            ->getMessage(),
                        0,
                        2000
                    ),
                ]
            );

        log_message(
            'error',
            'Communication event processing failed. '
                . 'Event ID: {eventId}; '
                . 'Event: {event}; '
                . 'Attempt: {attempt}; '
                . 'Error: {error}',
            [
                'eventId' =>
                $eventId,

                'event' =>
                $event['event_key']
                    ?? '',

                'attempt' =>
                $attemptCount,

                'error' =>
                $exception
                    ->getMessage(),
            ]
        );
    }
}
