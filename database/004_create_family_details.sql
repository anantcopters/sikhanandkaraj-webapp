BEGIN;

-- ---------------------------------------------------------------------------
-- Master values used for both father's and mother's occupation/status.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS master_family_occupations (
    id SMALLSERIAL PRIMARY KEY,
    code VARCHAR(40) NOT NULL,
    name VARCHAR(100) NOT NULL,
    display_order SMALLINT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_master_family_occupations_code
        UNIQUE (code),

    CONSTRAINT uq_master_family_occupations_name
        UNIQUE (name)
);

CREATE INDEX IF NOT EXISTS
    idx_master_family_occupations_active_order
ON master_family_occupations (
    is_active,
    display_order,
    name
);

INSERT INTO master_family_occupations (
    code,
    name,
    display_order,
    is_active
)
VALUES
    ('EMPLOYED', 'Employed', 10, TRUE),
    ('BUSINESS_PERSON', 'Business Person', 20, TRUE),
    ('PROFESSIONAL', 'Professional', 30, TRUE),
    ('RETIRED', 'Retired', 40, TRUE),
    ('NOT_EMPLOYED', 'Not Employed', 50, TRUE),
    ('PASSED_AWAY', 'Passed Away', 60, TRUE)
ON CONFLICT (code)
DO UPDATE SET
    name = EXCLUDED.name,
    display_order = EXCLUDED.display_order,
    is_active = EXCLUDED.is_active,
    updated_at = CURRENT_TIMESTAMP;

-- ---------------------------------------------------------------------------
-- One family-detail row per member.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS member_family_details (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,

    family_value VARCHAR(20) NOT NULL,
    family_type VARCHAR(20) NOT NULL,
    family_status VARCHAR(30) NOT NULL,

    father_occupation_id SMALLINT NULL,
    mother_occupation_id SMALLINT NULL,

    brothers_count SMALLINT NOT NULL DEFAULT 0,
    married_brothers_count SMALLINT NOT NULL DEFAULT 0,
    sisters_count SMALLINT NOT NULL DEFAULT 0,
    married_sisters_count SMALLINT NOT NULL DEFAULT 0,

    country_id SMALLINT NOT NULL,
    state_id INTEGER NOT NULL,
    city_id INTEGER NOT NULL,

    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_member_family_details_user
        UNIQUE (user_id),

    CONSTRAINT fk_member_family_details_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON UPDATE RESTRICT
        ON DELETE CASCADE,

    CONSTRAINT fk_member_family_details_father_occupation
        FOREIGN KEY (father_occupation_id)
        REFERENCES master_family_occupations(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_member_family_details_mother_occupation
        FOREIGN KEY (mother_occupation_id)
        REFERENCES master_family_occupations(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_member_family_details_country
        FOREIGN KEY (country_id)
        REFERENCES master_countries(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_member_family_details_state
        FOREIGN KEY (state_id)
        REFERENCES master_states(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_member_family_details_city
        FOREIGN KEY (city_id)
        REFERENCES master_cities(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT chk_member_family_value
        CHECK (
            family_value IN (
                'ORTHODOX',
                'TRADITIONAL',
                'MODERATE',
                'LIBERAL'
            )
        ),

    CONSTRAINT chk_member_family_type
        CHECK (
            family_type IN (
                'JOINT_FAMILY',
                'NUCLEAR_FAMILY',
                'OTHERS'
            )
        ),

    CONSTRAINT chk_member_family_status
        CHECK (
            family_status IN (
                'MIDDLE_CLASS',
                'UPPER_MIDDLE_CLASS',
                'HIGH_CLASS',
                'RICH_AFFLUENT'
            )
        ),

    CONSTRAINT chk_member_family_brothers_count
        CHECK (
            brothers_count BETWEEN 0 AND 10
        ),

    CONSTRAINT chk_member_family_married_brothers_count
        CHECK (
            married_brothers_count BETWEEN 0 AND brothers_count
        ),

    CONSTRAINT chk_member_family_sisters_count
        CHECK (
            sisters_count BETWEEN 0 AND 10
        ),

    CONSTRAINT chk_member_family_married_sisters_count
        CHECK (
            married_sisters_count BETWEEN 0 AND sisters_count
        )
);

CREATE INDEX IF NOT EXISTS
    idx_member_family_details_location
ON member_family_details (
    country_id,
    state_id,
    city_id
);

COMMIT;