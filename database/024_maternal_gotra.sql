BEGIN;

ALTER TABLE prelaunch_profiles
    ADD COLUMN IF NOT EXISTS gotra_maternal VARCHAR(100) NULL;

ALTER TABLE member_family_details
    ADD COLUMN IF NOT EXISTS gotra_maternal VARCHAR(100) NULL;

COMMIT;