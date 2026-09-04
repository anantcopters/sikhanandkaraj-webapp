BEGIN;

-- ============================================================================
-- SikhAnandKaraj
-- 044_coupon_management.sql
--
-- Coupon Management V1.
--
-- Coupon configuration is payment-channel independent.
-- Offline payment is the first redemption channel.
-- ============================================================================


-- ============================================================================
-- Coupons
-- ============================================================================

CREATE TABLE IF NOT EXISTS coupons (
    id BIGSERIAL PRIMARY KEY,

    code VARCHAR(40) NOT NULL,
    description VARCHAR(255) NULL,

    discount_type VARCHAR(20) NOT NULL,

    /*
     * PERCENTAGE:
     *     Whole percentage value, e.g. 10 = 10%.
     *
     * FLAT:
     *     Discount amount in paise, e.g. 25050 = Rs. 250.50.
     */
    discount_value INTEGER NOT NULL,

    eligibility_type VARCHAR(20) NOT NULL,

    eligible_gender VARCHAR(10) NULL,

    usage_limit INTEGER NOT NULL,

    starts_at TIMESTAMP NOT NULL,
    expires_at TIMESTAMP NOT NULL,

    country_id BIGINT NULL,
    state_id BIGINT NULL,
    city_id BIGINT NULL,

    is_active BOOLEAN NOT NULL DEFAULT TRUE,

    created_by_admin_user_id BIGINT NOT NULL,
    updated_by_admin_user_id BIGINT NOT NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_coupons_code
        UNIQUE (code),

    CONSTRAINT chk_coupons_discount_type
        CHECK (
            discount_type IN (
                'PERCENTAGE',
                'FLAT'
            )
        ),

    CONSTRAINT chk_coupons_eligibility_type
        CHECK (
            eligibility_type IN (
                'ALL',
                'SELECTED',
                'GENDER'
            )
        ),

    CONSTRAINT chk_coupons_eligible_gender
        CHECK (
            eligible_gender IS NULL
            OR eligible_gender IN (
                'MALE',
                'FEMALE'
            )
        ),

    CONSTRAINT chk_coupons_usage_limit
        CHECK (
            usage_limit > 0
        ),

    CONSTRAINT chk_coupons_discount_value
        CHECK (
            discount_value > 0
        ),

    CONSTRAINT chk_coupons_dates
        CHECK (
            expires_at >= starts_at
        ),

    /*
     * Gender must be populated only for GENDER eligibility.
     *
     * ALL and SELECTED coupons must not retain a stale gender.
     */
    CONSTRAINT chk_coupons_gender
        CHECK (
            (
                eligibility_type = 'GENDER'
                AND eligible_gender IS NOT NULL
            )
            OR
            (
                eligibility_type <> 'GENDER'
                AND eligible_gender IS NULL
            )
        ),

    CONSTRAINT fk_coupons_country
        FOREIGN KEY (country_id)
        REFERENCES master_countries(id),

    CONSTRAINT fk_coupons_state
        FOREIGN KEY (state_id)
        REFERENCES master_states(id),

    CONSTRAINT fk_coupons_city
        FOREIGN KEY (city_id)
        REFERENCES master_cities(id),

    CONSTRAINT fk_coupons_created_by_admin
        FOREIGN KEY (created_by_admin_user_id)
        REFERENCES admin_users(id),

    CONSTRAINT fk_coupons_updated_by_admin
        FOREIGN KEY (updated_by_admin_user_id)
        REFERENCES admin_users(id)
);


CREATE INDEX IF NOT EXISTS
    idx_coupons_status_dates
ON coupons (
    is_active,
    starts_at,
    expires_at
);


CREATE INDEX IF NOT EXISTS
    idx_coupons_location
ON coupons (
    country_id,
    state_id,
    city_id
);


COMMENT ON TABLE coupons IS
    'Coupon definitions for SikhAnandKaraj membership purchases.';


COMMENT ON COLUMN coupons.discount_value IS
    'PERCENTAGE stores a whole percentage; FLAT stores the amount in paise.';


COMMENT ON COLUMN coupons.starts_at IS
    'Coupon validity start time. Application interprets business time using Asia/Kolkata.';


COMMENT ON COLUMN coupons.expires_at IS
    'Coupon validity end time. V1 expiry dates resolve to 23:59:59 Asia/Kolkata.';


-- ============================================================================
-- Coupon -> Membership Plan mapping
-- ============================================================================

CREATE TABLE IF NOT EXISTS coupon_plans (
    coupon_id BIGINT NOT NULL,
    membership_plan_id BIGINT NOT NULL,

    PRIMARY KEY (
        coupon_id,
        membership_plan_id
    ),

    CONSTRAINT fk_coupon_plans_coupon
        FOREIGN KEY (coupon_id)
        REFERENCES coupons(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_coupon_plans_plan
        FOREIGN KEY (membership_plan_id)
        REFERENCES membership_plans(id)
        ON DELETE RESTRICT
);


CREATE INDEX IF NOT EXISTS
    idx_coupon_plans_membership_plan
ON coupon_plans (
    membership_plan_id
);


COMMENT ON TABLE coupon_plans IS
    'Membership plans to which a coupon is applicable.';


-- ============================================================================
-- Coupon -> Selected Member mapping
--
-- Rows exist only for SELECTED eligibility coupons.
-- Eligibility rules remain authoritatively enforced by the application service.
-- ============================================================================

CREATE TABLE IF NOT EXISTS coupon_members (
    coupon_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,

    PRIMARY KEY (
        coupon_id,
        user_id
    ),

    CONSTRAINT fk_coupon_members_coupon
        FOREIGN KEY (coupon_id)
        REFERENCES coupons(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_coupon_members_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE RESTRICT
);


CREATE INDEX IF NOT EXISTS
    idx_coupon_members_user
ON coupon_members (
    user_id
);


COMMENT ON TABLE coupon_members IS
    'Members explicitly eligible for coupons whose eligibility type is SELECTED.';


-- ============================================================================
-- Coupon redemptions
--
-- Financial values are snapshots.
--
-- Historical reporting must therefore remain correct even if:
--
--     * membership-plan prices later change;
--     * coupon configuration later changes;
--     * the coupon is deactivated or expires.
-- ============================================================================

CREATE TABLE IF NOT EXISTS coupon_redemptions (
    id BIGSERIAL PRIMARY KEY,

    coupon_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,

    member_payment_id BIGINT NOT NULL,
    membership_plan_id BIGINT NOT NULL,

    coupon_code_snapshot VARCHAR(40) NOT NULL,

    discount_type_snapshot VARCHAR(20) NOT NULL,

    /*
     * PERCENTAGE:
     *     Whole percentage.
     *
     * FLAT:
     *     Amount in paise.
     */
    discount_value_snapshot INTEGER NOT NULL,

    plan_price_paise INTEGER NOT NULL,
    discount_amount_paise INTEGER NOT NULL,
    final_payable_paise INTEGER NOT NULL,

    status VARCHAR(20) NOT NULL DEFAULT 'COMPLETED',

    redeemed_by_admin_user_id BIGINT NOT NULL,

    redeemed_at TIMESTAMP NOT NULL,

    /*
     * VOIDED is retained in the schema for historical compatibility/future
     * reversal support. Coupon reversal/voiding is outside V1 application scope.
     */
    voided_at TIMESTAMP NULL,
    voided_by_admin_user_id BIGINT NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_coupon_redemption_payment
        UNIQUE (member_payment_id),

    CONSTRAINT chk_coupon_redemption_discount_type
        CHECK (
            discount_type_snapshot IN (
                'PERCENTAGE',
                'FLAT'
            )
        ),

    CONSTRAINT chk_coupon_redemption_status
        CHECK (
            status IN (
                'COMPLETED',
                'VOIDED'
            )
        ),

    CONSTRAINT chk_coupon_redemption_discount_value
        CHECK (
            discount_value_snapshot > 0
        ),

    CONSTRAINT chk_coupon_redemption_plan_price
        CHECK (
            plan_price_paise > 0
        ),

    CONSTRAINT chk_coupon_redemption_discount_amount
        CHECK (
            discount_amount_paise > 0
        ),

    /*
     * V1 does not allow complimentary/zero-payable memberships.
     */
    CONSTRAINT chk_coupon_redemption_final_payable
        CHECK (
            final_payable_paise > 0
        ),

    CONSTRAINT chk_coupon_redemption_financials
        CHECK (
            discount_amount_paise < plan_price_paise
            AND final_payable_paise =
                plan_price_paise - discount_amount_paise
        ),

    CONSTRAINT chk_coupon_redemption_void_state
        CHECK (
            (
                status = 'COMPLETED'
                AND voided_at IS NULL
                AND voided_by_admin_user_id IS NULL
            )
            OR
            (
                status = 'VOIDED'
                AND voided_at IS NOT NULL
                AND voided_by_admin_user_id IS NOT NULL
            )
        ),

    CONSTRAINT fk_coupon_redemptions_coupon
        FOREIGN KEY (coupon_id)
        REFERENCES coupons(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_coupon_redemptions_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_coupon_redemptions_payment
        FOREIGN KEY (member_payment_id)
        REFERENCES member_payments(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_coupon_redemptions_plan
        FOREIGN KEY (membership_plan_id)
        REFERENCES membership_plans(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_coupon_redemptions_admin
        FOREIGN KEY (redeemed_by_admin_user_id)
        REFERENCES admin_users(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_coupon_redemptions_voided_admin
        FOREIGN KEY (voided_by_admin_user_id)
        REFERENCES admin_users(id)
        ON DELETE RESTRICT
);


CREATE INDEX IF NOT EXISTS
    idx_coupon_redemptions_coupon_status
ON coupon_redemptions (
    coupon_id,
    status
);


CREATE INDEX IF NOT EXISTS
    idx_coupon_redemptions_member
ON coupon_redemptions (
    user_id
);


/*
 * V1 rule:
 *
 * One successful redemption per member per coupon.
 *
 * PostgreSQL partial UNIQUE index gives us database-level protection while
 * still allowing a historical VOIDED row to coexist with a later successful
 * redemption if reversal functionality is introduced in the future.
 */
CREATE UNIQUE INDEX IF NOT EXISTS
    uq_coupon_redemptions_completed_member
ON coupon_redemptions (
    coupon_id,
    user_id
)
WHERE status = 'COMPLETED';


COMMENT ON TABLE coupon_redemptions IS
    'Immutable financial snapshots for successful coupon redemptions.';


COMMENT ON COLUMN coupon_redemptions.plan_price_paise IS
    'Membership plan price at the time of successful redemption.';


COMMENT ON COLUMN coupon_redemptions.discount_amount_paise IS
    'Authoritative discount amount applied at successful redemption.';


COMMENT ON COLUMN coupon_redemptions.final_payable_paise IS
    'Authoritative final payable amount after coupon discount.';


-- ============================================================================
-- Coupon audit log
-- ============================================================================

CREATE TABLE IF NOT EXISTS coupon_audit_logs (
    id BIGSERIAL PRIMARY KEY,

    coupon_id BIGINT NOT NULL,
    admin_user_id BIGINT NOT NULL,

    action VARCHAR(40) NOT NULL,

    previous_values JSONB NULL,
    new_values JSONB NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_coupon_audit_coupon
        FOREIGN KEY (coupon_id)
        REFERENCES coupons(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_coupon_audit_admin
        FOREIGN KEY (admin_user_id)
        REFERENCES admin_users(id)
        ON DELETE RESTRICT
);


CREATE INDEX IF NOT EXISTS
    idx_coupon_audit_coupon
ON coupon_audit_logs (
    coupon_id,
    created_at DESC
);


CREATE INDEX IF NOT EXISTS
    idx_coupon_audit_admin
ON coupon_audit_logs (
    admin_user_id,
    created_at DESC
);


COMMENT ON TABLE coupon_audit_logs IS
    'Administrative audit history for coupon creation, editing, status changes and redemption.';


-- ============================================================================
-- Extend membership payment ledger with coupon/pricing snapshots
-- ============================================================================

ALTER TABLE member_payments
    ADD COLUMN IF NOT EXISTS coupon_id BIGINT NULL;


ALTER TABLE member_payments
    ADD COLUMN IF NOT EXISTS plan_price_paise INTEGER NULL;


ALTER TABLE member_payments
    ADD COLUMN IF NOT EXISTS coupon_discount_paise INTEGER NULL;


ALTER TABLE member_payments
    ADD COLUMN IF NOT EXISTS final_payable_paise INTEGER NULL;


/*
 * Existing non-coupon payment rows may legitimately contain NULL in these
 * snapshot columns, so the constraints allow NULL.
 */
ALTER TABLE member_payments
    ADD CONSTRAINT chk_member_payments_plan_price
        CHECK (
            plan_price_paise IS NULL
            OR plan_price_paise >= 0
        );


ALTER TABLE member_payments
    ADD CONSTRAINT chk_member_payments_coupon_discount
        CHECK (
            coupon_discount_paise IS NULL
            OR coupon_discount_paise >= 0
        );


ALTER TABLE member_payments
    ADD CONSTRAINT chk_member_payments_final_payable
        CHECK (
            final_payable_paise IS NULL
            OR final_payable_paise >= 0
        );


ALTER TABLE member_payments
    ADD CONSTRAINT fk_member_payments_coupon
        FOREIGN KEY (coupon_id)
        REFERENCES coupons(id)
        ON DELETE RESTRICT;


CREATE INDEX IF NOT EXISTS
    idx_member_payments_coupon
ON member_payments (
    coupon_id
)
WHERE coupon_id IS NOT NULL;


COMMIT;