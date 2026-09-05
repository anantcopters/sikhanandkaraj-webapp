BEGIN;

-------------------------------------------------------------------------------
-- Interest withdrawal
-------------------------------------------------------------------------------

ALTER TABLE member_interests
    ADD COLUMN IF NOT EXISTS withdrawn_at TIMESTAMP NULL;

ALTER TABLE member_interests
    DROP CONSTRAINT IF EXISTS chk_member_interests_status;

ALTER TABLE member_interests
    ADD CONSTRAINT chk_member_interests_status
    CHECK (
        status IN (
            'PENDING',
            'ACCEPTED',
            'DECLINED',
            'WITHDRAWN'
        )
    );

-------------------------------------------------------------------------------
-- Member conversations
-------------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS member_conversations (
    id BIGSERIAL PRIMARY KEY,

    first_user_id BIGINT NOT NULL
        REFERENCES users(id),

    second_user_id BIGINT NOT NULL
        REFERENCES users(id),

    status VARCHAR(30) NOT NULL
        DEFAULT 'ACTIVE',

    created_from VARCHAR(20) NOT NULL
        DEFAULT 'MANUAL',

    /*
     * Only populated when a member manually starts the relationship.
     *
     * Interest-created conversations remain NULL so Interest never consumes
     * the manual-new-conversation allowance.
     */
    manual_initiated_by_user_id BIGINT NULL
        REFERENCES users(id),

    last_message_at TIMESTAMP NULL,

    closed_at TIMESTAMP NULL,

    created_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT chk_member_conversations_pair
        CHECK (
            first_user_id < second_user_id
        ),

    CONSTRAINT uq_member_conversations_pair
        UNIQUE (
            first_user_id,
            second_user_id
        ),

    CONSTRAINT chk_member_conversations_status
        CHECK (
            status IN (
                'ACTIVE',
                'CLOSED_DECLINED',
                'CLOSED_WITHDRAWN'
            )
        ),

    CONSTRAINT chk_member_conversations_created_from
        CHECK (
            created_from IN (
                'MANUAL',
                'INTEREST'
            )
        )
);

CREATE INDEX IF NOT EXISTS
    idx_member_conversations_first_last
ON member_conversations (
    first_user_id,
    last_message_at DESC,
    id DESC
);

CREATE INDEX IF NOT EXISTS
    idx_member_conversations_second_last
ON member_conversations (
    second_user_id,
    last_message_at DESC,
    id DESC
);

CREATE INDEX IF NOT EXISTS
    idx_member_conversations_manual_daily
ON member_conversations (
    manual_initiated_by_user_id,
    created_at DESC
)
WHERE manual_initiated_by_user_id IS NOT NULL;

-------------------------------------------------------------------------------
-- Messages and Interest/system events
-------------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS member_messages (
    id BIGSERIAL PRIMARY KEY,

    conversation_id BIGINT NOT NULL
        REFERENCES member_conversations(id),

    sender_user_id BIGINT NULL
        REFERENCES users(id),

    recipient_user_id BIGINT NULL
        REFERENCES users(id),

    message_type VARCHAR(20) NOT NULL,

    event_type VARCHAR(40) NULL,

    interest_id BIGINT NULL
        REFERENCES member_interests(id),

    message_text VARCHAR(500) NOT NULL,

    /*
     * Browser-generated idempotency key.
     *
     * Prevents double-click/retry/replay from creating the same manual
     * message twice.
     */
    client_request_id VARCHAR(64) NULL,

    read_at TIMESTAMP NULL,

    removed_at TIMESTAMP NULL,

    removed_by_admin_id BIGINT NULL
        REFERENCES admin_users(id),

    removal_reason VARCHAR(500) NULL,

    created_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT chk_member_messages_type
        CHECK (
            message_type IN (
                'MEMBER',
                'SYSTEM'
            )
        ),

    CONSTRAINT chk_member_messages_direction
        CHECK (
            (
                message_type = 'MEMBER'
                AND sender_user_id IS NOT NULL
                AND recipient_user_id IS NOT NULL
                AND sender_user_id <> recipient_user_id
            )
            OR
            (
                message_type = 'SYSTEM'
                AND sender_user_id IS NULL
                AND recipient_user_id IS NULL
            )
        )
);

CREATE UNIQUE INDEX IF NOT EXISTS
    uq_member_messages_client_request
ON member_messages (
    sender_user_id,
    client_request_id
)
WHERE
    message_type = 'MEMBER'
    AND client_request_id IS NOT NULL;

CREATE UNIQUE INDEX IF NOT EXISTS
    uq_member_messages_interest_event
ON member_messages (
    interest_id,
    event_type
)
WHERE
    interest_id IS NOT NULL
    AND event_type IS NOT NULL;

CREATE INDEX IF NOT EXISTS
    idx_member_messages_conversation
ON member_messages (
    conversation_id,
    id DESC
);

CREATE INDEX IF NOT EXISTS
    idx_member_messages_recipient_unread
ON member_messages (
    recipient_user_id,
    conversation_id,
    id DESC
)
WHERE
    message_type = 'MEMBER'
    AND read_at IS NULL;

CREATE INDEX IF NOT EXISTS
    idx_member_messages_sender_daily
ON member_messages (
    sender_user_id,
    created_at DESC
)
WHERE message_type = 'MEMBER';

CREATE INDEX IF NOT EXISTS
    idx_member_messages_sender_recipient_daily
ON member_messages (
    sender_user_id,
    recipient_user_id,
    created_at DESC
)
WHERE message_type = 'MEMBER';

-------------------------------------------------------------------------------
-- Message reports
-------------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS member_message_reports (
    id BIGSERIAL PRIMARY KEY,

    message_id BIGINT NOT NULL
        REFERENCES member_messages(id),

    conversation_id BIGINT NOT NULL
        REFERENCES member_conversations(id),

    reporter_user_id BIGINT NOT NULL
        REFERENCES users(id),

    reported_user_id BIGINT NOT NULL
        REFERENCES users(id),

    reason VARCHAR(40) NOT NULL,

    comment VARCHAR(500) NULL,

    created_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_member_message_reports_member
        UNIQUE (
            message_id,
            reporter_user_id
        ),

    CONSTRAINT chk_member_message_reports_reason
        CHECK (
            reason IN (
                'HARASSMENT',
                'ASKING_FOR_MONEY',
                'FAKE_IDENTITY',
                'INAPPROPRIATE',
                'UNWANTED_CONTACT',
                'SPAM',
                'OTHER'
            )
        )
);

CREATE INDEX IF NOT EXISTS
    idx_member_message_reports_reported
ON member_message_reports (
    reported_user_id,
    created_at DESC
);

CREATE INDEX IF NOT EXISTS
    idx_member_message_reports_conversation
ON member_message_reports (
    conversation_id,
    created_at DESC
);

COMMIT;