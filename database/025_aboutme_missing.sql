BEGIN;

ALTER TABLE member_basic_details
    ADD COLUMN IF NOT EXISTS about_me TEXT NULL;

COMMIT;