-- Gotra is mandatory for every prelaunch profile.
ALTER TABLE prelaunch_profiles
    ALTER COLUMN gotra SET NOT NULL;

-- Optional DB-level protection against blank strings.
ALTER TABLE prelaunch_profiles
    DROP CONSTRAINT IF EXISTS chk_prelaunch_profiles_gotra_not_blank;

-- Remove indexes whose only purpose is one of the removed columns.
-- Replace/add names here if your original migration used different names.
DROP INDEX IF EXISTS idx_prelaunch_profiles_mother_tongue_id;
DROP INDEX IF EXISTS idx_prelaunch_profiles_family_value_id;
DROP INDEX IF EXISTS idx_prelaunch_profiles_family_type_id;
DROP INDEX IF EXISTS idx_prelaunch_profiles_family_status_id;

-- Remove the obsolete data columns from only the prelaunch table.
ALTER TABLE prelaunch_profiles
    DROP COLUMN IF EXISTS mother_tongue_id,
    DROP COLUMN IF EXISTS family_value_id,
    DROP COLUMN IF EXISTS family_type_id,
    DROP COLUMN IF EXISTS family_status_id;