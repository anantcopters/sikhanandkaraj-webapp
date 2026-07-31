-- ============================================================================
-- MEMBER PHOTO REJECTED NOTIFICATION TYPE
-- ============================================================================
-- Adds PHOTO_REJECTED as a supported member notification type.
--
-- This script is safe to execute after:
-- database/010_member_notification_table.sql
-- ============================================================================

BEGIN;

ALTER TABLE member_notifications
    DROP CONSTRAINT IF EXISTS chk_member_notifications_type;

ALTER TABLE member_notifications
    ADD CONSTRAINT chk_member_notifications_type
    CHECK (
        notification_type IN
        (
            'MESSAGE',
            'INTEREST_RECEIVED',
            'INTEREST_ACCEPTED',
            'INTEREST_REJECTED',
            'PROFILE_VIEW',
            'SHORTLISTED',
            'PHOTO_REJECTED',
            'SYSTEM'
        )
    );

CREATE INDEX IF NOT EXISTS
idx_member_notifications_read_cleanup
ON member_notifications (read_at)
WHERE read_at IS NOT NULL;

COMMIT;