BEGIN;
/*
|--------------------------------------------------------------------------
| 031 - Member Communication Preferences
|--------------------------------------------------------------------------
|
| Purpose:
|
| Store member-controlled preferences for OPTIONAL communication.
|
| IMPORTANT:
|
| 1. This table does NOT control essential SECURITY, VERIFICATION,
|    TRANSACTIONAL, MODERATION or active SUPPORT communication.
|
| 2. A member cannot disable an essential communication merely by
|    changing a row in this table.
|
| 3. The CommunicationPolicyService remains the server-side authority
|    deciding whether an event is configurable.
|
| 4. One row represents one member/category/channel combination.
|
| 5. Future SMS and WhatsApp preferences can use the same table without
|    introducing another preference model.
|
*/

CREATE TABLE IF NOT EXISTS member_communication_preferences
(
    id BIGSERIAL PRIMARY KEY,

    user_id BIGINT NOT NULL,

    category VARCHAR(50) NOT NULL,

    channel VARCHAR(20) NOT NULL,

    is_enabled BOOLEAN NOT NULL DEFAULT TRUE,

    frequency VARCHAR(20) NOT NULL DEFAULT 'IMMEDIATE',

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_member_communication_preference
        UNIQUE
        (
            user_id,
            category,
            channel
        ),

    CONSTRAINT chk_member_communication_channel
        CHECK
        (
            channel IN
            (
                'EMAIL',
                'SMS',
                'WHATSAPP'
            )
        ),

    CONSTRAINT chk_member_communication_frequency
        CHECK
        (
            frequency IN
            (
                'IMMEDIATE',
                'DAILY',
                'WEEKLY',
                'OFF'
            )
        )
);

CREATE INDEX IF NOT EXISTS
    idx_member_communication_preferences_user
ON
    member_communication_preferences
    (
        user_id
    );


COMMIT;