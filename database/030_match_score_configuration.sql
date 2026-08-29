/*
 * SikhAnandKaraj
 * Match Score configuration
 *
 * Purpose
 * -------
 * Stores the currently effective Match Score component weights.
 *
 * Match Score is intentionally configuration-driven so Superadmin can tune
 * relevance without requiring a code deployment.
 *
 * IMPORTANT
 * ---------
 * This table stores global scoring configuration only.
 *
 * It does NOT store viewer/candidate Match Scores because Partner Preference
 * scoring is viewer-specific and persisting every viewer/candidate pair would
 * not scale.
 */

CREATE TABLE IF NOT EXISTS match_score_configurations (
    id BIGSERIAL PRIMARY KEY,

    /*
     * Partner Preference / relevance remains the dominant component.
     */
    preference_weight SMALLINT NOT NULL DEFAULT 55,

    /*
     * Candidate profile completeness.
     */
    profile_completion_weight SMALLINT NOT NULL DEFAULT 10,

    /*
     * Number of approved profile photos, normalized by the scoring service.
     */
    approved_photo_weight SMALLINT NOT NULL DEFAULT 10,

    /*
     * Mobile / Email / Aadhaar / Live Introduction trust score.
     */
    trust_weight SMALLINT NOT NULL DEFAULT 15,

    /*
     * Membership commercial priority.
     *
     * This must remain a minority component. Paid membership must improve
     * ranking but must never overpower relevance.
     */
    commercial_weight SMALLINT NOT NULL DEFAULT 10,

    /*
     * Only one configuration should be effective at a time.
     *
     * Historical rows are retained for auditability.
     */
    is_active BOOLEAN NOT NULL DEFAULT FALSE,

    created_by_admin_id BIGINT NULL,

    created_at TIMESTAMP WITHOUT TIME ZONE
        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    /*
     * Defensive database constraints.
     *
     * Application validation also enforces these rules, but the database is
     * the final protection against invalid scoring configuration.
     */
    CONSTRAINT chk_match_score_preference_weight
        CHECK (
            preference_weight >= 0
            AND preference_weight <= 100
        ),

    CONSTRAINT chk_match_score_completion_weight
        CHECK (
            profile_completion_weight >= 0
            AND profile_completion_weight <= 100
        ),

    CONSTRAINT chk_match_score_photo_weight
        CHECK (
            approved_photo_weight >= 0
            AND approved_photo_weight <= 100
        ),

    CONSTRAINT chk_match_score_trust_weight
        CHECK (
            trust_weight >= 0
            AND trust_weight <= 100
        ),

    /*
     * Commercial influence is intentionally capped at 20%.
     *
     * This protects matchmaking relevance even if an invalid administrative
     * value somehow bypasses application validation.
     */
    CONSTRAINT chk_match_score_commercial_weight
        CHECK (
            commercial_weight >= 0
            AND commercial_weight <= 20
        ),

    CONSTRAINT chk_match_score_total_weight
        CHECK (
            preference_weight
            + profile_completion_weight
            + approved_photo_weight
            + trust_weight
            + commercial_weight
            = 100
        )
);

/*
 * PostgreSQL partial unique index guarantees that there can be at most one
 * currently active scoring configuration.
 */
CREATE UNIQUE INDEX IF NOT EXISTS
    uq_match_score_configurations_active
ON match_score_configurations (is_active)
WHERE is_active = TRUE;

/*
 * History is naturally retained because configuration changes create a new
 * row rather than overwriting the previous configuration.
 */
CREATE INDEX IF NOT EXISTS
    idx_match_score_configurations_created_at
ON match_score_configurations (created_at DESC);


/*
 * Seed the documented default only when no configuration exists.
 */
INSERT INTO match_score_configurations (
    preference_weight,
    profile_completion_weight,
    approved_photo_weight,
    trust_weight,
    commercial_weight,
    is_active,
    created_by_admin_id
)
SELECT
    55,
    10,
    10,
    15,
    10,
    TRUE,
    NULL
WHERE NOT EXISTS (
    SELECT 1
    FROM match_score_configurations
);