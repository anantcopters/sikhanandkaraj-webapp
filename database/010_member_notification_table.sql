-- ============================================================================
-- MEMBER NOTIFICATIONS
-- ============================================================================
-- Stores both system notifications and message-related notifications.
--
-- Important:
-- 1. recipient_user_id identifies the member receiving the notification.
-- 2. actor_user_id identifies the member who caused it, where applicable.
-- 3. entity_type/entity_id let the notification point to a message,
--    interest, profile, photo or any future domain entity.
-- 4. target_url is an internal application path, not an external URL.
-- 5. read_at is NULL until the member reads the notification.
-- ============================================================================

CREATE TABLE IF NOT EXISTS member_notifications
(
    id                  BIGSERIAL PRIMARY KEY,

    recipient_user_id   BIGINT NOT NULL,
    actor_user_id       BIGINT NULL,

    notification_type   VARCHAR(40) NOT NULL,
    title               VARCHAR(150) NOT NULL,
    message             VARCHAR(500) NOT NULL,

    entity_type         VARCHAR(40) NULL,
    entity_id           BIGINT NULL,

    target_url          VARCHAR(255) NULL,

    read_at             TIMESTAMP NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NULL,

    CONSTRAINT fk_member_notifications_recipient
        FOREIGN KEY (recipient_user_id)
        REFERENCES users(id)
        ON UPDATE RESTRICT
        ON DELETE CASCADE,

    CONSTRAINT fk_member_notifications_actor
        FOREIGN KEY (actor_user_id)
        REFERENCES users(id)
        ON UPDATE RESTRICT
        ON DELETE SET NULL,

    CONSTRAINT chk_member_notifications_type
        CHECK (
            notification_type IN
            (
                'MESSAGE',
                'INTEREST_RECEIVED',
                'INTEREST_ACCEPTED',
                'INTEREST_REJECTED',
                'PROFILE_VIEW',
                'SHORTLISTED',
                'SYSTEM'
            )
        ),

    CONSTRAINT chk_member_notifications_title
        CHECK (BTRIM(title) <> ''),

    CONSTRAINT chk_member_notifications_message
        CHECK (BTRIM(message) <> '')
);

CREATE INDEX IF NOT EXISTS
idx_member_notifications_recipient_unread
ON member_notifications
(
    recipient_user_id,
    created_at DESC
)
WHERE read_at IS NULL;

CREATE INDEX IF NOT EXISTS
idx_member_notifications_recipient_type_unread
ON member_notifications
(
    recipient_user_id,
    notification_type,
    created_at DESC
)
WHERE read_at IS NULL;

CREATE INDEX IF NOT EXISTS
idx_member_notifications_recipient_created
ON member_notifications
(
    recipient_user_id,
    created_at DESC
);

CREATE INDEX IF NOT EXISTS
idx_member_notifications_entity
ON member_notifications
(
    entity_type,
    entity_id
)
WHERE entity_type IS NOT NULL
  AND entity_id IS NOT NULL;