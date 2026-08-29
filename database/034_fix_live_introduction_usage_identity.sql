BEGIN;

/*
 * Live Introduction commercial usage is candidate-scoped.
 *
 * Previous implementation used:
 *
 *     membership_id + video_introduction_id
 *
 * That incorrectly allows a replacement/re-uploaded video belonging to the
 * same member to consume another allowance.
 *
 * The authoritative commercial identity is:
 *
 *     membership_id + owner_user_id
 *
 * video_introduction_id remains stored for audit/history purposes.
 */

/*
 * Defensive cleanup before applying the new unique constraint.
 *
 * In case development/QA data already contains multiple video-version rows
 * for one owner in the same membership, preserve the earliest commercial
 * consumption and remove later duplicate commercial rows.
 *
 * Production deployment should still review affected-row counts before this
 * migration is promoted.
 */
DELETE FROM member_membership_live_introduction_views duplicate_usage
USING member_membership_live_introduction_views retained_usage
WHERE duplicate_usage.membership_id =
      retained_usage.membership_id
AND duplicate_usage.owner_user_id =
    retained_usage.owner_user_id
AND duplicate_usage.id >
    retained_usage.id;

/*
 * Remove the previous video-version uniqueness constraint/index.
 *
 * PostgreSQL DROP CONSTRAINT and DROP INDEX are both included defensively
 * because the original deployment may have represented uniqueness using
 * either mechanism.
 */
ALTER TABLE member_membership_live_introduction_views
    DROP CONSTRAINT IF EXISTS
    uq_member_membership_live_intro_video;

DROP INDEX IF EXISTS
    uq_member_membership_live_intro_video;

/*
 * One candidate may consume Live Introduction allowance only once during one
 * membership, regardless of how many approved replacement videos they upload.
 */
CREATE UNIQUE INDEX IF NOT EXISTS
    uq_member_membership_live_intro_owner
ON member_membership_live_introduction_views (
    membership_id,
    owner_user_id
);

COMMIT;