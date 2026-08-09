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

-- -------------------------------------------------------------------------
-- 1. Normalise historical data.
-- -------------------------------------------------------------------------

UPDATE member_interests
SET
    status = 'PENDING'
WHERE
    status IS NULL
    OR BTRIM(status) = '';

-- -------------------------------------------------------------------------
-- 2. Temporarily remove the existing status constraint.
-- -------------------------------------------------------------------------

ALTER TABLE member_interests
DROP CONSTRAINT IF EXISTS chk_member_interests_status;

-- -------------------------------------------------------------------------
-- 3. Add MUTUAL as a valid state.
-- -------------------------------------------------------------------------

ALTER TABLE member_interests
ADD CONSTRAINT chk_member_interests_status CHECK (
    status IN (
        'PENDING',
        'ACCEPTED',
        'DECLINED',
        'MUTUAL'
    )
);

ALTER TABLE member_interests
ALTER COLUMN status
SET DEFAULT 'PENDING',
ALTER COLUMN status
SET NOT NULL;

-- -------------------------------------------------------------------------
-- 4. Convert historical reciprocal positive interests into MUTUAL.
--
-- Only PENDING / ACCEPTED interests participate.
-- A DECLINED relationship must never be promoted automatically.
--
-- responded_at represents the point at which the pair became mutual,
-- i.e. the later of the two interest creation timestamps.
-- -------------------------------------------------------------------------

UPDATE member_interests AS interest
SET
    status = 'MUTUAL',
    responded_at = COALESCE(
        interest.responded_at,
        (
            SELECT GREATEST(
                    interest.created_at, reverse_interest.created_at
                )
            FROM
                member_interests AS reverse_interest
            WHERE
                reverse_interest.from_user_id = interest.to_user_id
                AND reverse_interest.to_user_id = interest.from_user_id
                AND reverse_interest.status IN ('PENDING', 'ACCEPTED')
            LIMIT 1
        )
    )
WHERE
    interest.status IN ('PENDING', 'ACCEPTED')
    AND EXISTS (
        SELECT 1
        FROM
            member_interests AS reverse_interest
        WHERE
            reverse_interest.from_user_id = interest.to_user_id
            AND reverse_interest.to_user_id = interest.from_user_id
            AND reverse_interest.status IN ('PENDING', 'ACCEPTED')
    );

-- -------------------------------------------------------------------------
-- 5. Supporting indexes.
-- -------------------------------------------------------------------------

CREATE INDEX IF NOT EXISTS idx_member_interests_mutual_from ON member_interests (
    from_user_id,
    status,
    responded_at DESC
)
WHERE
    status = 'MUTUAL';

CREATE INDEX IF NOT EXISTS idx_member_interests_mutual_to ON member_interests (
    to_user_id,
    status,
    responded_at DESC
)
WHERE
    status = 'MUTUAL';

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