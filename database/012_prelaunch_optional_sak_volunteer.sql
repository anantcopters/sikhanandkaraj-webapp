BEGIN;

/*
 * SAK Volunteer association is optional for a prelaunch profile.
 *
 * Existing profiles remain unchanged. New profiles may store both
 * field_officer_id and created_by as NULL when no SAK Volunteer
 * was entered.
 */
ALTER TABLE prelaunch_profiles
ALTER COLUMN field_officer_id
DROP NOT NULL;

ALTER TABLE prelaunch_profiles ALTER COLUMN created_by DROP NOT NULL;

/*
 * Preserve the current prelaunch ownership contract:
 *
 * 1. both values are NULL when no SAK Volunteer was supplied; or
 * 2. both values contain the same verified SAK Volunteer ID.
 *
 * This protects direct database writes and concurrent/application
 * paths that bypass normal form validation.
 */
ALTER TABLE prelaunch_profiles
DROP CONSTRAINT IF EXISTS chk_prelaunch_profiles_field_officer_pair;

ALTER TABLE prelaunch_profiles
ADD CONSTRAINT chk_prelaunch_profiles_field_officer_pair CHECK (
    (
        field_officer_id IS NULL
        AND created_by IS NULL
    )
    OR (
        field_officer_id IS NOT NULL
        AND created_by IS NOT NULL
        AND field_officer_id = created_by
    )
);

COMMIT;