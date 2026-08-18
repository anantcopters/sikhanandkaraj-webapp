BEGIN;

/*
 * Migrated prelaunch members create their password through the existing
 * verified-contact Forgot Password flow. A NULL hash is the authoritative
 * "password setup pending" state.
 */
ALTER TABLE users
    ALTER COLUMN password_hash DROP NOT NULL;

COMMIT;