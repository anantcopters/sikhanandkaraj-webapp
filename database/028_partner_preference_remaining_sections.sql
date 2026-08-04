BEGIN;

-- ============================================================
-- Religious preference
-- ============================================================

CREATE TABLE IF NOT EXISTS member_partner_religious_preferences (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    community_match_mode BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_partner_religious_preference_user
        UNIQUE (user_id),

    CONSTRAINT fk_partner_religious_preference_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON UPDATE RESTRICT
        ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS
    idx_partner_religious_preference_user
ON member_partner_religious_preferences(user_id);


CREATE TABLE IF NOT EXISTS
    member_partner_preference_communities (
        id BIGSERIAL PRIMARY KEY,
        partner_religious_preference_id BIGINT NOT NULL,
        community_id INTEGER NOT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

        CONSTRAINT uq_partner_preference_community
            UNIQUE (
                partner_religious_preference_id,
                community_id
            ),

        CONSTRAINT fk_partner_preference_community_parent
            FOREIGN KEY (partner_religious_preference_id)
            REFERENCES member_partner_religious_preferences(id)
            ON UPDATE RESTRICT
            ON DELETE CASCADE,

        CONSTRAINT fk_partner_preference_community_master
            FOREIGN KEY (community_id)
            REFERENCES master_sikh_communities(id)
            ON UPDATE RESTRICT
            ON DELETE RESTRICT
    );

CREATE INDEX IF NOT EXISTS
    idx_partner_preference_community_parent
ON member_partner_preference_communities(
    partner_religious_preference_id
);

CREATE INDEX IF NOT EXISTS
    idx_partner_preference_community_master
ON member_partner_preference_communities(
    community_id
);


-- ============================================================
-- Professional preference
-- ============================================================

CREATE TABLE IF NOT EXISTS member_partner_professional_preferences (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,

    education_match_mode BOOLEAN NOT NULL DEFAULT FALSE,
    employed_in_match_mode BOOLEAN NOT NULL DEFAULT FALSE,
    occupation_match_mode BOOLEAN NOT NULL DEFAULT FALSE,

    annual_income_from_id INTEGER NULL,
    annual_income_to_id INTEGER NULL,
    annual_income_match_mode BOOLEAN NOT NULL DEFAULT FALSE,

    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_partner_professional_preference_user
        UNIQUE (user_id),

    CONSTRAINT fk_partner_professional_preference_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON UPDATE RESTRICT
        ON DELETE CASCADE,

    CONSTRAINT fk_partner_professional_income_from
        FOREIGN KEY (annual_income_from_id)
        REFERENCES master_annual_incomes(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_partner_professional_income_to
        FOREIGN KEY (annual_income_to_id)
        REFERENCES master_annual_incomes(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS
    idx_partner_professional_preference_user
ON member_partner_professional_preferences(user_id);


CREATE TABLE IF NOT EXISTS
    member_partner_preference_educations (
        id BIGSERIAL PRIMARY KEY,
        partner_professional_preference_id BIGINT NOT NULL,
        education_id INTEGER NOT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

        CONSTRAINT uq_partner_preference_education
            UNIQUE (
                partner_professional_preference_id,
                education_id
            ),

        CONSTRAINT fk_partner_preference_education_parent
            FOREIGN KEY (partner_professional_preference_id)
            REFERENCES member_partner_professional_preferences(id)
            ON UPDATE RESTRICT
            ON DELETE CASCADE,

        CONSTRAINT fk_partner_preference_education_master
            FOREIGN KEY (education_id)
            REFERENCES master_educations(id)
            ON UPDATE RESTRICT
            ON DELETE RESTRICT
    );

CREATE INDEX IF NOT EXISTS
    idx_partner_preference_education_parent
ON member_partner_preference_educations(
    partner_professional_preference_id
);


CREATE TABLE IF NOT EXISTS
    member_partner_preference_employment_types (
        id BIGSERIAL PRIMARY KEY,
        partner_professional_preference_id BIGINT NOT NULL,
        employed_in VARCHAR(30) NOT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

        CONSTRAINT uq_partner_preference_employment
            UNIQUE (
                partner_professional_preference_id,
                employed_in
            ),

        CONSTRAINT fk_partner_preference_employment_parent
            FOREIGN KEY (partner_professional_preference_id)
            REFERENCES member_partner_professional_preferences(id)
            ON UPDATE RESTRICT
            ON DELETE CASCADE,

        CONSTRAINT chk_partner_preference_employment_type
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

CREATE INDEX IF NOT EXISTS
    idx_partner_preference_employment_parent
ON member_partner_preference_employment_types(
    partner_professional_preference_id
);


CREATE TABLE IF NOT EXISTS
    member_partner_preference_occupations (
        id BIGSERIAL PRIMARY KEY,
        partner_professional_preference_id BIGINT NOT NULL,
        occupation_id INTEGER NOT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

        CONSTRAINT uq_partner_preference_occupation
            UNIQUE (
                partner_professional_preference_id,
                occupation_id
            ),

        CONSTRAINT fk_partner_preference_occupation_parent
            FOREIGN KEY (partner_professional_preference_id)
            REFERENCES member_partner_professional_preferences(id)
            ON UPDATE RESTRICT
            ON DELETE CASCADE,

        CONSTRAINT fk_partner_preference_occupation_master
            FOREIGN KEY (occupation_id)
            REFERENCES master_occupations(id)
            ON UPDATE RESTRICT
            ON DELETE RESTRICT
    );

CREATE INDEX IF NOT EXISTS
    idx_partner_preference_occupation_parent
ON member_partner_preference_occupations(
    partner_professional_preference_id
);


-- ============================================================
-- Location preference
-- ============================================================

CREATE TABLE IF NOT EXISTS member_partner_location_preferences (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    state_id INTEGER NOT NULL,
    city_id INTEGER NOT NULL,
    location_match_mode BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_partner_location_preference_user
        UNIQUE (user_id),

    CONSTRAINT fk_partner_location_preference_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON UPDATE RESTRICT
        ON DELETE CASCADE,

    CONSTRAINT fk_partner_location_preference_state
        FOREIGN KEY (state_id)
        REFERENCES master_states(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_partner_location_preference_city
        FOREIGN KEY (city_id)
        REFERENCES master_cities(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS
    idx_partner_location_preference_user
ON member_partner_location_preferences(user_id);

CREATE INDEX IF NOT EXISTS
    idx_partner_location_preference_state_city
ON member_partner_location_preferences(
    state_id,
    city_id
);


-- ============================================================
-- Special request
-- ============================================================

CREATE TABLE IF NOT EXISTS member_partner_special_requests (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    request_text VARCHAR(1000) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_partner_special_request_user
        UNIQUE (user_id),

    CONSTRAINT fk_partner_special_request_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON UPDATE RESTRICT
        ON DELETE CASCADE,

    CONSTRAINT chk_partner_special_request_not_blank
        CHECK (btrim(request_text) <> '')
);

CREATE INDEX IF NOT EXISTS
    idx_partner_special_request_user
ON member_partner_special_requests(user_id);

COMMIT;