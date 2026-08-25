BEGIN;

/*
 * Membership architecture legacy cleanup.
 *
 * Profile Visibility was introduced before the centralized membership
 * authorization architecture existed.
 *
 * Full Profile access is now controlled authoritatively by:
 *
 *     ProfileAccessPolicy
 *         -> Verified Profile policy
 *         -> Membership entitlement
 *         -> relationship / gender rules
 *         -> block / moderation rules
 *         -> membership-scoped usage limits
 *
 * Member-configurable profile_visibility would therefore create a second,
 * conflicting authorization source and must be removed.
 *
 * IMPORTANT:
 *
 * This removes PROFILE visibility only.
 *
 * Photo visibility remains a separate supported feature and must not be
 * changed by this deployment.
 */

ALTER TABLE users
    DROP CONSTRAINT IF EXISTS chk_users_profile_visibility;

ALTER TABLE users
    DROP COLUMN IF EXISTS profile_visibility;

COMMIT;