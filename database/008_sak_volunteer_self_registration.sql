BEGIN;

ALTER TABLE field_officers
ADD COLUMN IF NOT EXISTS registration_source VARCHAR(20);

ALTER TABLE field_officers
ADD COLUMN IF NOT EXISTS review_status VARCHAR(20);

ALTER TABLE field_officers
ADD COLUMN IF NOT EXISTS reviewed_by BIGINT NULL;

ALTER TABLE field_officers
ADD COLUMN IF NOT EXISTS reviewed_at TIMESTAMPTZ NULL;

ALTER TABLE field_officers
ADD COLUMN IF NOT EXISTS rejection_reason VARCHAR(500) NULL;


/*
 * Existing records were created by Admin/Super Admin
 * and are treated as already reviewed.
 */
UPDATE field_officers
SET
    registration_source = 'ADMIN',
    review_status = 'APPROVED'
WHERE registration_source IS NULL
   OR review_status IS NULL;


ALTER TABLE field_officers
ALTER COLUMN registration_source
SET DEFAULT 'ADMIN';

ALTER TABLE field_officers
ALTER COLUMN registration_source
SET NOT NULL;

ALTER TABLE field_officers
ALTER COLUMN review_status
SET DEFAULT 'APPROVED';

ALTER TABLE field_officers
ALTER COLUMN review_status
SET NOT NULL;


ALTER TABLE field_officers
DROP CONSTRAINT IF EXISTS
    chk_field_officers_registration_source;

ALTER TABLE field_officers
ADD CONSTRAINT
    chk_field_officers_registration_source
CHECK (
    registration_source IN (
        'ADMIN',
        'SELF'
    )
);


ALTER TABLE field_officers
DROP CONSTRAINT IF EXISTS
    chk_field_officers_review_status;

ALTER TABLE field_officers
ADD CONSTRAINT
    chk_field_officers_review_status
CHECK (
    review_status IN (
        'PENDING',
        'APPROVED',
        'REJECTED'
    )
);


/*
 * A self-registration may not become ACTIVE while it
 * is still awaiting review or has been rejected.
 */
ALTER TABLE field_officers
DROP CONSTRAINT IF EXISTS
    chk_field_officers_review_account_status;

ALTER TABLE field_officers
ADD CONSTRAINT
    chk_field_officers_review_account_status
CHECK (
    registration_source <> 'SELF'
    OR review_status = 'APPROVED'
    OR account_status = 'INACTIVE'
);


/*
 * Only reviewed records may carry reviewer metadata.
 */
ALTER TABLE field_officers
DROP CONSTRAINT IF EXISTS
    chk_field_officers_review_metadata;

ALTER TABLE field_officers
ADD CONSTRAINT
    chk_field_officers_review_metadata
CHECK (
    (
        review_status = 'PENDING'
        AND reviewed_at IS NULL
        AND reviewed_by IS NULL
    )
    OR
    (
        review_status IN (
            'APPROVED',
            'REJECTED'
        )
    )
);

COMMIT;