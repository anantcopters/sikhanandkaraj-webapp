BEGIN;
-- SikhAnandKaraj
-- 044_coupon_management.sql
--
-- Coupon Management V1.
-- Coupon configuration is payment-channel independent.
-- Offline payment is the first redemption channel.

CREATE TABLE IF NOT EXISTS coupons (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(40) NOT NULL,
    description VARCHAR(255) NULL,

    discount_type ENUM(
        'PERCENTAGE',
        'FLAT'
    ) NOT NULL,

    discount_value INT UNSIGNED NOT NULL
        COMMENT 'PERCENTAGE: whole percentage; FLAT: amount in paise',

    eligibility_type ENUM(
        'ALL',
        'SELECTED',
        'GENDER'
    ) NOT NULL,

    eligible_gender ENUM(
        'MALE',
        'FEMALE'
    ) NULL,

    usage_limit INT UNSIGNED NOT NULL,

    starts_at DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,

    country_id BIGINT UNSIGNED NULL,
    state_id BIGINT UNSIGNED NULL,
    city_id BIGINT UNSIGNED NULL,

    is_active TINYINT(1) NOT NULL DEFAULT 1,

    created_by_admin_user_id BIGINT UNSIGNED NOT NULL,
    updated_by_admin_user_id BIGINT UNSIGNED NOT NULL,

    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_coupons_code (code),

    KEY idx_coupons_status_dates (
        is_active,
        starts_at,
        expires_at
    ),

    KEY idx_coupons_location (
        country_id,
        state_id,
        city_id
    ),

    CONSTRAINT chk_coupons_usage_limit
        CHECK (usage_limit > 0),

    CONSTRAINT chk_coupons_discount_value
        CHECK (discount_value > 0),

    CONSTRAINT chk_coupons_dates
        CHECK (expires_at >= starts_at),

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
        )
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS coupon_plans (
    coupon_id BIGINT UNSIGNED NOT NULL,
    membership_plan_id BIGINT UNSIGNED NOT NULL,

    PRIMARY KEY (
        coupon_id,
        membership_plan_id
    ),

    CONSTRAINT fk_coupon_plans_coupon
        FOREIGN KEY (coupon_id)
        REFERENCES coupons(id),

    CONSTRAINT fk_coupon_plans_plan
        FOREIGN KEY (membership_plan_id)
        REFERENCES membership_plans(id)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS coupon_members (
    coupon_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,

    PRIMARY KEY (
        coupon_id,
        user_id
    ),

    KEY idx_coupon_members_user (
        user_id
    ),

    CONSTRAINT fk_coupon_members_coupon
        FOREIGN KEY (coupon_id)
        REFERENCES coupons(id),

    CONSTRAINT fk_coupon_members_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS coupon_redemptions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    coupon_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    member_payment_id BIGINT UNSIGNED NOT NULL,
    membership_plan_id BIGINT UNSIGNED NOT NULL,

    coupon_code_snapshot VARCHAR(40) NOT NULL,

    discount_type_snapshot ENUM(
        'PERCENTAGE',
        'FLAT'
    ) NOT NULL,

    discount_value_snapshot INT UNSIGNED NOT NULL,

    plan_price_paise INT UNSIGNED NOT NULL,
    discount_amount_paise INT UNSIGNED NOT NULL,
    final_payable_paise INT UNSIGNED NOT NULL,

    status ENUM(
        'COMPLETED',
        'VOIDED'
    ) NOT NULL DEFAULT 'COMPLETED',

    redeemed_by_admin_user_id BIGINT UNSIGNED NOT NULL,

    redeemed_at DATETIME NOT NULL,
    voided_at DATETIME NULL,
    voided_by_admin_user_id BIGINT UNSIGNED NULL,

    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,

    PRIMARY KEY (id),

    UNIQUE KEY uq_coupon_redemption_payment (
        member_payment_id
    ),

    KEY idx_coupon_redemptions_coupon_status (
        coupon_id,
        status
    ),

    KEY idx_coupon_redemptions_member (
        user_id
    ),

    /*
     * Historical VOIDED rows must remain.
     * Therefore member/coupon uniqueness cannot be represented by a simple
     * unique key if a voided redemption should permit later reuse.
     * The service performs this check transactionally against COMPLETED rows.
     */

    CONSTRAINT fk_coupon_redemptions_coupon
        FOREIGN KEY (coupon_id)
        REFERENCES coupons(id),

    CONSTRAINT fk_coupon_redemptions_user
        FOREIGN KEY (user_id)
        REFERENCES users(id),

    CONSTRAINT fk_coupon_redemptions_payment
        FOREIGN KEY (member_payment_id)
        REFERENCES member_payments(id),

    CONSTRAINT fk_coupon_redemptions_plan
        FOREIGN KEY (membership_plan_id)
        REFERENCES membership_plans(id)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS coupon_audit_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    coupon_id BIGINT UNSIGNED NOT NULL,
    admin_user_id BIGINT UNSIGNED NOT NULL,

    action VARCHAR(40) NOT NULL,

    previous_values JSON NULL,
    new_values JSON NULL,

    created_at DATETIME NOT NULL,

    PRIMARY KEY (id),

    KEY idx_coupon_audit_coupon (
        coupon_id,
        created_at
    ),

    CONSTRAINT fk_coupon_audit_coupon
        FOREIGN KEY (coupon_id)
        REFERENCES coupons(id)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


ALTER TABLE member_payments
    ADD COLUMN coupon_id BIGINT UNSIGNED NULL
        AFTER membership_plan_id,

    ADD COLUMN plan_price_paise INT UNSIGNED NULL
        AFTER amount_paise,

    ADD COLUMN coupon_discount_paise INT UNSIGNED NULL
        AFTER plan_price_paise,

    ADD COLUMN final_payable_paise INT UNSIGNED NULL
        AFTER coupon_discount_paise,

    ADD KEY idx_member_payments_coupon (
        coupon_id
    ),

    ADD CONSTRAINT fk_member_payments_coupon
        FOREIGN KEY (coupon_id)
        REFERENCES coupons(id);

COMMIT;