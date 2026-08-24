BEGIN;

ALTER TABLE prelaunch_profiles
    ADD COLUMN IF NOT EXISTS gotra_maternal VARCHAR(100) NULL;

ALTER TABLE member_family_details
    ADD COLUMN IF NOT EXISTS gotra_maternal VARCHAR(100) NULL;

UPDATE master_sikh_communities set is_active=FALSE
WHERE code='PREFER_NOT_TO_SAY';

COMMIT;