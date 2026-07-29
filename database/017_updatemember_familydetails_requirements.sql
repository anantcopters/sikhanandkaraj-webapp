BEGIN;

ALTER TABLE member_family_details
ADD COLUMN IF NOT EXISTS gotra VARCHAR(100) NULL;

ALTER TABLE member_family_details
    ALTER COLUMN family_value_id DROP NOT NULL,
    ALTER COLUMN family_type_id DROP NOT NULL,
    ALTER COLUMN family_status_id DROP NOT NULL;

COMMIT;