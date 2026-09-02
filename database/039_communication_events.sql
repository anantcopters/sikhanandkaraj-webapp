BEGIN;

/*
|--------------------------------------------------------------------------
| Durable Communication Events
|--------------------------------------------------------------------------
|
| A communication event represents a business event which may result in
| one or more channel deliveries.
|
| It is deliberately independent from:
|
| - email_queue
| - SMS provider state
| - WhatsApp provider state
| - in-app notification rendering
|
| The UNIQUE reference constraint provides concurrency-safe idempotency
| for business events which have a stable domain reference.
|
*/

CREATE TABLE IF NOT EXISTS communication_events
(
    id BIGSERIAL PRIMARY KEY,

    event_key VARCHAR(100) NOT NULL,

    recipient_user_id BIGINT NOT NULL,

    reference_type VARCHAR(100) NULL,

    reference_id BIGINT NULL,

    payload_json TEXT NOT NULL DEFAULT '{}',

    status VARCHAR(20) NOT NULL DEFAULT 'PENDING',

    attempt_count INTEGER NOT NULL DEFAULT 0,

    available_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    processing_started_at TIMESTAMP NULL,

    processed_at TIMESTAMP NULL,

    last_error TEXT NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT chk_communication_event_status
        CHECK
        (
            status IN
            (
                'PENDING',
                'PROCESSING',
                'PROCESSED',
                'FAILED'
            )
        )
);


/*
|--------------------------------------------------------------------------
| Queue lookup
|--------------------------------------------------------------------------
*/

CREATE INDEX IF NOT EXISTS
    idx_communication_events_pending
ON
    communication_events
    (
        status,
        available_at,
        id
    );


/*
|--------------------------------------------------------------------------
| Recipient history
|--------------------------------------------------------------------------
*/

CREATE INDEX IF NOT EXISTS
    idx_communication_events_recipient
ON
    communication_events
    (
        recipient_user_id,
        created_at
    );


/*
|--------------------------------------------------------------------------
| Concurrency-grade event idempotency
|--------------------------------------------------------------------------
|
| Only reference-backed events participate.
|
| This allows future non-reference events while ensuring that the same
| Interest, membership, verification, moderation, etc. event cannot be
| produced twice for the same recipient.
|
*/

CREATE UNIQUE INDEX IF NOT EXISTS
    uq_communication_events_reference
ON
    communication_events
    (
        event_key,
        recipient_user_id,
        reference_type,
        reference_id
    )
WHERE
    reference_type IS NOT NULL
    AND reference_id IS NOT NULL;

COMMIT;