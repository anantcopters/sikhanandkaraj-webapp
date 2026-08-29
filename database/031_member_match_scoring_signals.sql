/*
 * SikhAnandKaraj
 * Cached intrinsic member scoring signals
 *
 * IMPORTANT
 * ---------
 * We intentionally cache only candidate-intrinsic values which are expensive
 * to reconstruct during every Search/Dashboard request.
 *
 * We DO NOT cache:
 *
 * - Partner Preference score: viewer-specific.
 * - Trust score: verification state is already projected efficiently.
 * - Approved photo count: already projected by the candidate query.
 * - Commercial priority: already projected from active membership.
 *
 * Profile completion is the only cached signal introduced here.
 */

CREATE TABLE member_match_scoring_signals (
    user_id BIGINT PRIMARY KEY,

    /*
     * Authoritative overall profile completion percentage generated through
     * ProfileCompletionService.
     */
    profile_completion SMALLINT NOT NULL DEFAULT 0,

    /*
     * Useful for diagnostics and future repair/backfill jobs.
     */
    updated_at TIMESTAMP WITHOUT TIME ZONE
        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_member_match_scoring_signals_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT chk_member_match_profile_completion
        CHECK (
            profile_completion >= 0
            AND profile_completion <= 100
        )
);


/*
 * No additional user_id index is required because PRIMARY KEY already creates
 * an efficient unique B-tree index.
 */