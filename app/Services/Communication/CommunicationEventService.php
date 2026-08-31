<?php

declare(strict_types=1);

namespace App\Services\Communication;

use App\Models\CommunicationEventModel;
use JsonException;
use Throwable;

/**
 * Durable communication-event boundary.
 *
 * Business/domain services publish events here rather than calling
 * individual external channels directly.
 */
final class CommunicationEventService
{
    public function __construct(
        private readonly CommunicationEventModel
        $eventModel
    ) {}

    /**
     * Persist one communication event.
     *
     * Duplicate logical events are treated as an idempotent success.
     */
    public function publish(
        CommunicationEvent $event
    ): ?int {
        if (
            trim(
                $event->eventKey
            ) === ''
            || $event->recipientUserId <= 0
        ) {
            return null;
        }

        try {
            $payloadJson =
                json_encode(
                    $event->payload,
                    JSON_THROW_ON_ERROR
                        | JSON_UNESCAPED_UNICODE
                        | JSON_UNESCAPED_SLASHES
                );

            $data = [
                'event_key' =>
                trim(
                    $event->eventKey
                ),

                'recipient_user_id' =>
                $event->recipientUserId,

                'reference_type' =>
                $event->referenceType !== null
                    ? trim(
                        $event->referenceType
                    )
                    : null,

                'reference_id' =>
                $event->referenceId,

                'payload_json' =>
                $payloadJson,

                'status' =>
                CommunicationEventModel
                ::STATUS_PENDING,

                'attempt_count' =>
                0,

                'available_at' =>
                gmdate(
                    'Y-m-d H:i:s'
                ),
            ];

            $id =
                $this
                ->eventModel
                ->insert(
                    $data,
                    true
                );

            return is_numeric($id)
                ? (int) $id
                : null;
        } catch (JsonException $exception) {
            log_message(
                'error',
                'Communication event payload could not be encoded. '
                    . 'Event: {event}; '
                    . 'Recipient: {recipient}; '
                    . 'Error: {error}',
                [
                    'event' =>
                    $event->eventKey,

                    'recipient' =>
                    $event->recipientUserId,

                    'error' =>
                    $exception
                        ->getMessage(),
                ]
            );

            return null;
        } catch (Throwable $exception) {
            /*
             * A UNIQUE violation means another request/process already
             * published the same logical event.
             *
             * The business action must not fail because communication
             * already exists.
             */
            if (
                $this
                ->isDuplicateReference(
                    $event
                )
            ) {
                return null;
            }

            log_message(
                'error',
                'Communication event could not be published. '
                    . 'Event: {event}; '
                    . 'Recipient: {recipient}; '
                    . 'Error: {error}',
                [
                    'event' =>
                    $event->eventKey,

                    'recipient' =>
                    $event->recipientUserId,

                    'error' =>
                    $exception
                        ->getMessage(),
                ]
            );

            return null;
        }
    }

    private function isDuplicateReference(
        CommunicationEvent $event
    ): bool {
        if (
            $event->referenceType === null
            || $event->referenceId === null
            || $event->referenceId <= 0
        ) {
            return false;
        }

        return $this
            ->eventModel
            ->hasReference(
                $event->eventKey,
                $event->recipientUserId,
                $event->referenceType,
                $event->referenceId
            );
    }
}
