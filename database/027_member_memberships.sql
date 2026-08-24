BEGIN;

/*
 * Membership foundation.
 *
 * This increment introduces:
 *
 * 1. the authoritative membership plan master;
 * 2. historical member membership instances;
 * 3. one-active-membership database protection;
 * 4. purchased-plan snapshots;
 * 5. removal of the temporary QA-only users.is_paid flag.
 *
 * IMPORTANT:
 * - membership_plans contains the current commercial definition;
 * - member_memberships contains what the member actually purchased.
 *
 * A later change to a plan must therefore NOT silently change an already
 * purchased membership. Limits and commercial values are copied into the
 * membership row when the membership is created.
 */

CREATE TABLE membership_plans (
    id BIGSERIAL PRIMARY KEY,

    code VARCHAR(20) NOT NULL,
    name VARCHAR(100) NOT NULL,
    positioning VARCHAR(100) NOT NULL,

    /*
     * Amount is stored in paise so commercial calculations never depend on
     * floating-point currency arithmetic.
     *
     * Example:
     * ₹1,499.00 = 149900 paise.
     */
    price_paise INTEGER NOT NULL,

    duration_months SMALLINT NOT NULL,

    /*
     * Number of distinct Verified Profiles that may be opened during one
     * membership period.
     */
    profile_view_limit INTEGER NOT NULL,

    /*
     * Number of previously unconsumed Verified Profiles that may consume
     * allowance on one IST calendar day.
     */
    daily_profile_view_limit INTEGER NOT NULL,

    /*
     * Number of distinct approved Live Introductions that may be watched
     * during one membership period.
     */
    live_introduction_view_limit INTEGER NOT NULL,

    has_match_manager BOOLEAN NOT NULL DEFAULT FALSE,

    /*
     * Higher value means higher commercial ranking contribution.
     *
     * This is intentionally independent from the future Superadmin Match
     * Score weight. The plan supplies the commercial signal; Match Score
     * configuration decides how much that signal contributes.
     */
    commercial_priority SMALLINT NOT NULL,

    display_order SMALLINT NOT NULL,

    is_active BOOLEAN NOT NULL DEFAULT TRUE,

    created_at TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_membership_plans_code
        UNIQUE (code),

    CONSTRAINT chk_membership_plans_code
        CHECK (code IN ('GO', 'PLUS', 'PRO')),

    CONSTRAINT chk_membership_plans_price
        CHECK (price_paise >= 0),

    CONSTRAINT chk_membership_plans_duration
        CHECK (duration_months > 0),

    CONSTRAINT chk_membership_plans_profile_limit
        CHECK (profile_view_limit > 0),

    CONSTRAINT chk_membership_plans_daily_profile_limit
        CHECK (
            daily_profile_view_limit > 0
            AND daily_profile_view_limit <= profile_view_limit
        ),

    CONSTRAINT chk_membership_plans_video_limit
        CHECK (live_introduction_view_limit > 0),

    CONSTRAINT chk_membership_plans_priority
        CHECK (commercial_priority > 0),

    CONSTRAINT chk_membership_plans_display_order
        CHECK (display_order > 0)
);

/*
 * Seed the currently approved commercial plans.
 *
 * These rows are the authoritative plan master. Pricing UI and future
 * purchase/activation flows must read these values rather than maintaining
 * another hard-coded copy.
 */
INSERT INTO membership_plans (
    code,
    name,
    positioning,
    price_paise,
    duration_months,
    profile_view_limit,
    daily_profile_view_limit,
    live_introduction_view_limit,
    has_match_manager,
    commercial_priority,
    display_order,
    is_active
) VALUES
(
    'GO',
    'Sikhanandkaraj Go',
    'Start Connecting',
    149900,
    3,
    50,
    5,
    10,
    FALSE,
    1,
    1,
    TRUE
),
(
    'PLUS',
    'Sikhanandkaraj Plus',
    'Best Value',
    249900,
    6,
    100,
    10,
    30,
    FALSE,
    2,
    2,
    TRUE
),
(
    'PRO',
    'Sikhanandkaraj Pro',
    'Personalised Assistance',
    999900,
    12,
    300,
    20,
    80,
    TRUE,
    3,
    3,
    TRUE
);

CREATE INDEX idx_membership_plans_active_display
    ON membership_plans (
        is_active,
        display_order
    );

/*
 * Historical membership instances.
 *
 * A member may have many historical memberships but may have at most one
 * ACTIVE membership at a time.
 *
 * FREE is intentionally NOT persisted as a membership plan. A member for
 * whom MembershipService cannot resolve an active paid membership is a Free
 * member.
 */
CREATE TABLE member_memberships (
    id BIGSERIAL PRIMARY KEY,

    user_id BIGINT NOT NULL,
    membership_plan_id BIGINT NOT NULL,

    status VARCHAR(20) NOT NULL,

    starts_at TIMESTAMP WITHOUT TIME ZONE NOT NULL,
    expires_at TIMESTAMP WITHOUT TIME ZONE NOT NULL,

    /*
     * Commercial snapshot.
     *
     * These values represent the purchased contract and must not change when
     * membership_plans is edited in the future.
     */
    plan_code_snapshot VARCHAR(20) NOT NULL,
    plan_name_snapshot VARCHAR(100) NOT NULL,
    price_paise_snapshot INTEGER NOT NULL,
    duration_months_snapshot SMALLINT NOT NULL,
    profile_view_limit_snapshot INTEGER NOT NULL,
    daily_profile_view_limit_snapshot INTEGER NOT NULL,
    live_introduction_view_limit_snapshot INTEGER NOT NULL,
    has_match_manager_snapshot BOOLEAN NOT NULL,
    commercial_priority_snapshot SMALLINT NOT NULL,

    /*
     * When an active membership is replaced by an upgrade, the old row is
     * retained and linked to the replacement. This preserves membership
     * history instead of overwriting commercial state.
     */
    replaced_by_membership_id BIGINT NULL,

    created_at TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_member_memberships_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_member_memberships_plan
        FOREIGN KEY (membership_plan_id)
        REFERENCES membership_plans(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_member_memberships_replacement
        FOREIGN KEY (replaced_by_membership_id)
        REFERENCES member_memberships(id)
        ON DELETE RESTRICT,

    CONSTRAINT chk_member_memberships_status
        CHECK (
            status IN (
                'ACTIVE',
                'EXPIRED',
                'REPLACED',
                'CANCELLED'
            )
        ),

    CONSTRAINT chk_member_memberships_period
        CHECK (expires_at > starts_at),

    CONSTRAINT chk_member_memberships_plan_code
        CHECK (
            plan_code_snapshot IN (
                'GO',
                'PLUS',
                'PRO'
            )
        ),

    CONSTRAINT chk_member_memberships_price
        CHECK (price_paise_snapshot >= 0),

    CONSTRAINT chk_member_memberships_duration
        CHECK (duration_months_snapshot > 0),

    CONSTRAINT chk_member_memberships_profile_limit
        CHECK (profile_view_limit_snapshot > 0),

    CONSTRAINT chk_member_memberships_daily_profile_limit
        CHECK (
            daily_profile_view_limit_snapshot > 0
            AND daily_profile_view_limit_snapshot
                <= profile_view_limit_snapshot
        ),

    CONSTRAINT chk_member_memberships_video_limit
        CHECK (
            live_introduction_view_limit_snapshot > 0
        ),

    CONSTRAINT chk_member_memberships_priority
        CHECK (commercial_priority_snapshot > 0),

    CONSTRAINT chk_member_memberships_replacement
        CHECK (
            replaced_by_membership_id IS NULL
            OR status = 'REPLACED'
        )
);

/*
 * Database-level protection against two ACTIVE memberships for one member.
 *
 * Application pre-checks alone are insufficient because two concurrent
 * requests could otherwise both observe "no active membership" and insert.
 */
CREATE UNIQUE INDEX uq_member_memberships_one_active
    ON member_memberships (user_id)
    WHERE status = 'ACTIVE';

CREATE INDEX idx_member_memberships_user_history
    ON member_memberships (
        user_id,
        created_at DESC
    );

CREATE INDEX idx_member_memberships_active_expiry
    ON member_memberships (
        expires_at
    )
    WHERE status = 'ACTIVE';

/*
 * is_paid was introduced only as a temporary QA switch.
 *
 * Production membership authority now belongs exclusively to
 * member_memberships + MembershipService. Keeping the flag would create two
 * competing sources of truth, so it is deliberately removed here.
 */
ALTER TABLE users
    DROP COLUMN IF EXISTS is_paid;

COMMIT;