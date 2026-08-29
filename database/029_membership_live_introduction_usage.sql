BEGIN;

/*
 * Membership-scoped Live Introduction usage.
 *
 * PRODUCT RULE:
 *
 * Each paid membership has a purchased allowance for the number of distinct
 * approved Live Introductions that may be watched during that membership.
 *
 * The commercial unit is the approved Video Introduction VERSION, not merely
 * the member who owns it.
 *
 * Therefore:
 *
 * - replaying the same approved video version during the same membership
 *   does NOT consume another allowance;
 *
 * - if the owner later replaces that video with a newly approved version,
 *   watching that new version is a new Live Introduction consumption;
 *
 * - a future membership receives a fresh allowance because consumption is
 *   scoped by membership_id.
 *
 * This table is deliberately separate from the video lifecycle tables.
 * Moderation state and commercial consumption are different concerns.
 */

CREATE TABLE member_membership_live_introduction_views (
    id BIGSERIAL PRIMARY KEY,

    membership_id BIGINT NOT NULL,

    viewer_user_id BIGINT NOT NULL,

    owner_user_id BIGINT NOT NULL,

    video_introduction_id BIGINT NOT NULL,

    first_viewed_at TIMESTAMP WITHOUT TIME ZONE NOT NULL,
    last_viewed_at TIMESTAMP WITHOUT TIME ZONE NOT NULL,

    /*
     * Number of successful signed-playback URL requests for this already
     * consumed Live Introduction.
     *
     * This is activity information only. It does not affect quota because
     * quota consumption is represented by the unique row itself.
     */
    view_count INTEGER NOT NULL DEFAULT 1,

    created_at TIMESTAMP WITHOUT TIME ZONE
        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP WITHOUT TIME ZONE
        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_membership_live_intro_membership
        FOREIGN KEY (membership_id)
        REFERENCES member_memberships(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_membership_live_intro_viewer
        FOREIGN KEY (viewer_user_id)
        REFERENCES users(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_membership_live_intro_owner
        FOREIGN KEY (owner_user_id)
        REFERENCES users(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_membership_live_intro_video
        FOREIGN KEY (video_introduction_id)
        REFERENCES member_video_introductions(id)
        ON DELETE RESTRICT,

    CONSTRAINT chk_membership_live_intro_distinct_members
        CHECK (viewer_user_id <> owner_user_id),

    CONSTRAINT chk_membership_live_intro_view_count
        CHECK (view_count > 0),

    /*
     * One approved video version consumes at most one allowance during one
     * membership.
     *
     * This database constraint is also the final concurrency protection
     * against duplicate consumption.
     */
    CONSTRAINT uq_membership_live_intro_video
        UNIQUE (
            membership_id,
            video_introduction_id
        )
);

/*
 * Member transaction/history lookup.
 */
CREATE INDEX idx_membership_live_intro_history
    ON member_membership_live_introduction_views (
        viewer_user_id,
        last_viewed_at DESC
    );

/*
 * Owner-oriented lookup can later support statistics such as how many paid
 * members watched a member's approved Live Introduction.
 */
CREATE INDEX idx_membership_live_intro_owner
    ON member_membership_live_introduction_views (
        owner_user_id,
        last_viewed_at DESC
    );

COMMIT;