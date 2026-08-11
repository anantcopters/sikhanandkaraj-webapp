BEGIN;

ALTER TABLE member_interests
ADD COLUMN IF NOT EXISTS status VARCHAR(16);

UPDATE member_interests
SET
    status = 'PENDING'
WHERE
    status IS NULL
    OR BTRIM(status) = '';

ALTER TABLE member_interests
ALTER COLUMN status
SET DEFAULT 'PENDING',
ALTER COLUMN status
SET NOT NULL;

ALTER TABLE member_interests
ADD COLUMN IF NOT EXISTS responded_at TIMESTAMP NULL;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conname = 'chk_member_interests_status'
          AND conrelid = 'member_interests'::regclass
    ) THEN
        ALTER TABLE member_interests
            ADD CONSTRAINT chk_member_interests_status
            CHECK (
                status IN (
                    'PENDING',
                    'ACCEPTED',
                    'DECLINED'
                )
            );
    END IF;
END
$$;

CREATE INDEX IF NOT EXISTS idx_member_interests_received_status ON member_interests (
    to_user_id,
    status,
    created_at DESC
);

CREATE INDEX IF NOT EXISTS idx_member_interests_sent_status ON member_interests (
    from_user_id,
    status,
    created_at DESC
);

ALTER TABLE member_notifications
    DROP CONSTRAINT IF EXISTS chk_member_notifications_type;

ALTER TABLE member_notifications
    ADD CONSTRAINT chk_member_notifications_type
    CHECK (
        notification_type IN (
            'MESSAGE',
            'INTEREST_RECEIVED',
            'INTEREST_ACCEPTED',
            'INTEREST_REJECTED',
            'MUTUAL_INTEREST',
            'PROFILE_VIEW',
            'SHORTLISTED',
            'PHOTO_REJECTED',
            'SYSTEM'
        )
    );

COMMIT;