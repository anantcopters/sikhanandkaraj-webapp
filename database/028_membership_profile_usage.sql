BEGIN;

/*
 * Membership-scoped Full Profile usage.
 *
 * IMPORTANT PRODUCT RULE:
 *
 * A paid member consumes one Full Profile allowance only when:
 *
 * 1. the target qualifies as a Verified Profile;
 * 2. all profile-access/privacy rules allow access;
 * 3. that target has not already been consumed during this membership.
 *
 * Reopening the same target during the SAME membership therefore does not
 * consume another allowance.
 *
 * A future membership is a new commercial contract and receives its own
 * usage scope.
 */

CREATE TABLE member_membership_profile_views (
    id BIGSERIAL PRIMARY KEY,

    membership_id BIGINT NOT NULL,

    viewer_user_id BIGINT NOT NULL,

    viewed_user_id BIGINT NOT NULL,

    /*
     * usage_date_ist is stored explicitly because the commercial daily
     * allowance is based on the India calendar day, not UTC.
     *
     * Keeping this value on the consumed row makes daily counting fast and
     * avoids timezone conversion in every quota query.
     */
    usage_date_ist DATE NOT NULL,

    first_viewed_at TIMESTAMP WITHOUT TIME ZONE NOT NULL,
    last_viewed_at TIMESTAMP WITHOUT TIME ZONE NOT NULL,

    /*
     * Actual page openings are useful for the future member transaction
     * history while quota consumption remains one unique target per
     * membership.
     */
    view_count INTEGER NOT NULL DEFAULT 1,

    created_at TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_membership_profile_views_membership
        FOREIGN KEY (membership_id)
        REFERENCES member_memberships(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_membership_profile_views_viewer
        FOREIGN KEY (viewer_user_id)
        REFERENCES users(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_membership_profile_views_viewed
        FOREIGN KEY (viewed_user_id)
        REFERENCES users(id)
        ON DELETE RESTRICT,

    CONSTRAINT chk_membership_profile_views_distinct
        CHECK (viewer_user_id <> viewed_user_id),

    CONSTRAINT chk_membership_profile_views_count
        CHECK (view_count > 0),

    /*
     * This is the core commercial uniqueness rule.
     *
     * Candidate X can consume at most one Full Profile allowance during
     * membership Y.
     */
    CONSTRAINT uq_membership_profile_views_unique_target
        UNIQUE (
            membership_id,
            viewed_user_id
        )
);

/*
 * Fast total membership-consumption lookup.
 *
 * The unique constraint already provides an index beginning with
 * membership_id, so no duplicate index is needed for total counting.
 */

/*
 * Fast daily allowance count:
 *
 * WHERE membership_id = ?
 *   AND usage_date_ist = ?
 */
CREATE INDEX idx_membership_profile_views_daily
    ON member_membership_profile_views (
        membership_id,
        usage_date_ist
    );

/*
 * Transaction-history lookup for the authenticated member.
 */
CREATE INDEX idx_membership_profile_views_history
    ON member_membership_profile_views (
        viewer_user_id,
        last_viewed_at DESC
    );

/*
 * profile_visibility is no longer a valid product concept.
 *
 * Profile availability is now determined centrally by:
 *
 * - active account;
 * - blocked/report safety rules;
 * - paid membership;
 * - Verified Profile requirement;
 * - gender / accepted-interest privacy rule;
 * - membership usage quota.
 *
 * Leaving this column in place would preserve a second, contradictory
 * authorization system.
 */
ALTER TABLE users
    DROP COLUMN IF EXISTS profile_visibility;

COMMIT;