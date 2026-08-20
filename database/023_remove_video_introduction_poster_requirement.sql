BEGIN;

ALTER TABLE member_video_introductions
    DROP CONSTRAINT IF EXISTS
        chk_member_video_active;

ALTER TABLE member_video_introductions
    ADD CONSTRAINT chk_member_video_active
        CHECK (
            is_active = FALSE
            OR (
                moderation_status = 'APPROVED'
                AND playback_object_key IS NOT NULL
                AND deleted_at IS NULL
            )
        );

COMMENT ON COLUMN
    member_video_introductions.poster_object_key
IS
    'Legacy poster key. New Video Introduction submissions '
    'do not generate poster images.';

COMMIT;