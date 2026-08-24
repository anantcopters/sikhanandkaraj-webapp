BEGIN;

ALTER TABLE member_basic_details
    ADD COLUMN IF NOT EXISTS is_amritdhari BOOLEAN NOT NULL DEFAULT FALSE;

ALTER TABLE member_partner_basic_preferences
    ADD COLUMN IF NOT EXISTS amritdhari BOOLEAN NULL;

ALTER TABLE member_partner_basic_preferences
    ADD COLUMN IF NOT EXISTS amritdhari_match_mode BOOLEAN NOT NULL DEFAULT FALSE;

COMMIT;