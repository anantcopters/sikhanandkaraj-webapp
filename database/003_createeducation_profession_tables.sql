BEGIN;

-- ============================================================
-- Highest Education master
-- ============================================================

CREATE TABLE IF NOT EXISTS master_educations (
    id SMALLSERIAL PRIMARY KEY,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(120) NOT NULL,
    display_order SMALLINT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_master_educations_code
        UNIQUE (code),

    CONSTRAINT uq_master_educations_name
        UNIQUE (name),

    CONSTRAINT chk_master_educations_code_not_blank
        CHECK (BTRIM(code) <> ''),

    CONSTRAINT chk_master_educations_name_not_blank
        CHECK (BTRIM(name) <> '')
);

CREATE INDEX IF NOT EXISTS idx_master_educations_active_order
    ON master_educations (
        is_active,
        display_order,
        name
    );


-- ============================================================
-- Occupation master
-- ============================================================

CREATE TABLE IF NOT EXISTS master_occupations (
    id SMALLSERIAL PRIMARY KEY,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(120) NOT NULL,
    display_order SMALLINT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_master_occupations_code
        UNIQUE (code),

    CONSTRAINT uq_master_occupations_name
        UNIQUE (name),

    CONSTRAINT chk_master_occupations_code_not_blank
        CHECK (BTRIM(code) <> ''),

    CONSTRAINT chk_master_occupations_name_not_blank
        CHECK (BTRIM(name) <> '')
);

CREATE INDEX IF NOT EXISTS idx_master_occupations_active_order
    ON master_occupations (
        is_active,
        display_order,
        name
    );


-- ============================================================
-- Annual Income master
--
-- min_amount and max_amount are stored in INR.
-- max_amount NULL means no upper limit.
-- ============================================================

CREATE TABLE IF NOT EXISTS master_annual_incomes (
    id SMALLSERIAL PRIMARY KEY,
    code VARCHAR(50) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    min_amount BIGINT NOT NULL DEFAULT 0,
    max_amount BIGINT NULL,
    display_order SMALLINT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_master_annual_incomes_code
        UNIQUE (code),

    CONSTRAINT chk_master_annual_incomes_code_not_blank
        CHECK (BTRIM(code) <> ''),

    CONSTRAINT chk_master_annual_incomes_display_name_not_blank
        CHECK (BTRIM(display_name) <> ''),

    CONSTRAINT chk_master_annual_incomes_min_amount
        CHECK (min_amount >= 0),

    CONSTRAINT chk_master_annual_incomes_range
        CHECK (
            max_amount IS NULL
            OR max_amount > min_amount
        )
);

CREATE INDEX IF NOT EXISTS idx_master_annual_incomes_active_order
    ON master_annual_incomes (
        is_active,
        display_order
    );


-- ============================================================
-- Member Education & Profession details
-- ============================================================

CREATE TABLE IF NOT EXISTS member_education_profession_details (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,

    highest_education_id SMALLINT NOT NULL,
    education_detail VARCHAR(500) NULL,
    college_institution VARCHAR(200) NULL,

    employed_in VARCHAR(30) NOT NULL,
    occupation_id SMALLINT NOT NULL,
    occupation_detail VARCHAR(500) NULL,
    organization VARCHAR(200) NULL,

    annual_income_id SMALLINT NULL,

    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_member_education_profession_user
        UNIQUE (user_id),

    CONSTRAINT fk_member_education_profession_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON UPDATE RESTRICT
        ON DELETE CASCADE,

    CONSTRAINT fk_member_education_profession_education
        FOREIGN KEY (highest_education_id)
        REFERENCES master_educations(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_member_education_profession_occupation
        FOREIGN KEY (occupation_id)
        REFERENCES master_occupations(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_member_education_profession_income
        FOREIGN KEY (annual_income_id)
        REFERENCES master_annual_incomes(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT chk_member_education_detail_not_blank
        CHECK (
            education_detail IS NULL
            OR BTRIM(education_detail) <> ''
        ),

    CONSTRAINT chk_member_college_not_blank
        CHECK (
            college_institution IS NULL
            OR BTRIM(college_institution) <> ''
        ),

    CONSTRAINT chk_member_occupation_detail_not_blank
        CHECK (
            occupation_detail IS NULL
            OR BTRIM(occupation_detail) <> ''
        ),

    CONSTRAINT chk_member_organization_not_blank
        CHECK (
            organization IS NULL
            OR BTRIM(organization) <> ''
        ),

    CONSTRAINT chk_member_employed_in
        CHECK (
            employed_in IN (
                'GOVERNMENT_PSU',
                'PRIVATE',
                'BUSINESS',
                'DEFENSE',
                'SELF_EMPLOYED',
                'NOT_WORKING'
            )
        )
);

CREATE INDEX IF NOT EXISTS idx_member_education_profession_education
    ON member_education_profession_details (
        highest_education_id
    );

CREATE INDEX IF NOT EXISTS idx_member_education_profession_occupation
    ON member_education_profession_details (
        occupation_id
    );

CREATE INDEX IF NOT EXISTS idx_member_education_profession_income
    ON member_education_profession_details (
        annual_income_id
    );

CREATE INDEX IF NOT EXISTS idx_member_education_profession_employed
    ON member_education_profession_details (
        employed_in
    );


-- ============================================================
-- Seed: Education
-- ============================================================

INSERT INTO master_educations (
    code,
    name,
    display_order
)
VALUES
    ('HIGH_SCHOOL', 'High School', 10),
    ('DIPLOMA', 'Diploma', 20),
    ('BACHELORS', 'Bachelor''s Degree', 30),
    ('MASTERS', 'Master''s Degree', 40),
    ('DOCTORATE', 'Doctorate / PhD', 50)
ON CONFLICT (code) DO UPDATE
SET
    name = EXCLUDED.name,
    display_order = EXCLUDED.display_order,
    is_active = TRUE,
    updated_at = CURRENT_TIMESTAMP;


-- ============================================================
-- Seed: Occupation
-- ============================================================

INSERT INTO master_occupations (
    code,
    name,
    display_order
)
VALUES
    ('SOFTWARE_PROFESSIONAL', 'Software Professional', 10),
    ('DOCTOR', 'Doctor', 20),
    ('ENGINEER', 'Engineer', 30),
    ('TEACHER', 'Teacher / Professor', 40),
    ('BUSINESS_OWNER', 'Business Owner', 50)
ON CONFLICT (code) DO UPDATE
SET
    name = EXCLUDED.name,
    display_order = EXCLUDED.display_order,
    is_active = TRUE,
    updated_at = CURRENT_TIMESTAMP;


-- ============================================================
-- Seed: Annual Income
-- 1 lakh = 100000 INR
-- ============================================================

INSERT INTO master_annual_incomes (
    code,
    display_name,
    min_amount,
    max_amount,
    display_order
)
VALUES
    ('INR_0_1_LAKH', '₹0 – ₹1 Lakh', 0, 100000, 10),
    ('INR_1_2_LAKH', '₹1 – ₹2 Lakh', 100000, 200000, 20),
    ('INR_2_3_LAKH', '₹2 – ₹3 Lakh', 200000, 300000, 30),
    ('INR_3_4_LAKH', '₹3 – ₹4 Lakh', 300000, 400000, 40),
    ('INR_4_5_LAKH', '₹4 – ₹5 Lakh', 400000, 500000, 50),
    ('INR_5_6_LAKH', '₹5 – ₹6 Lakh', 500000, 600000, 60),
    ('INR_6_7_LAKH', '₹6 – ₹7 Lakh', 600000, 700000, 70),
    ('INR_7_8_LAKH', '₹7 – ₹8 Lakh', 700000, 800000, 80),
    ('INR_8_9_LAKH', '₹8 – ₹9 Lakh', 800000, 900000, 90),
    ('INR_9_10_LAKH', '₹9 – ₹10 Lakh', 900000, 1000000, 100),

    ('INR_10_15_LAKH', '₹10 – ₹15 Lakh', 1000000, 1500000, 110),
    ('INR_15_20_LAKH', '₹15 – ₹20 Lakh', 1500000, 2000000, 120),
    ('INR_20_25_LAKH', '₹20 – ₹25 Lakh', 2000000, 2500000, 130),
    ('INR_25_30_LAKH', '₹25 – ₹30 Lakh', 2500000, 3000000, 140),
    ('INR_30_35_LAKH', '₹30 – ₹35 Lakh', 3000000, 3500000, 150),
    ('INR_35_40_LAKH', '₹35 – ₹40 Lakh', 3500000, 4000000, 160),
    ('INR_40_45_LAKH', '₹40 – ₹45 Lakh', 4000000, 4500000, 170),
    ('INR_45_50_LAKH', '₹45 – ₹50 Lakh', 4500000, 5000000, 180),

    ('INR_50_60_LAKH', '₹50 – ₹60 Lakh', 5000000, 6000000, 190),
    ('INR_60_70_LAKH', '₹60 – ₹70 Lakh', 6000000, 7000000, 200),
    ('INR_70_80_LAKH', '₹70 – ₹80 Lakh', 7000000, 8000000, 210),
    ('INR_80_90_LAKH', '₹80 – ₹90 Lakh', 8000000, 9000000, 220),
    ('INR_90_100_LAKH', '₹90 Lakh – ₹1 Crore', 9000000, 10000000, 230),

    ('INR_ABOVE_1_CRORE', 'More than ₹1 Crore', 10000000, NULL, 240)
ON CONFLICT (code) DO UPDATE
SET
    display_name = EXCLUDED.display_name,
    min_amount = EXCLUDED.min_amount,
    max_amount = EXCLUDED.max_amount,
    display_order = EXCLUDED.display_order,
    is_active = TRUE,
    updated_at = CURRENT_TIMESTAMP;

COMMIT;