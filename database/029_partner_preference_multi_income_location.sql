BEGIN;

-- ============================================================
-- Annual Income: migrate From/To values into a selection table
-- ============================================================

CREATE TABLE IF NOT EXISTS
    member_partner_preference_annual_incomes (
        id BIGSERIAL PRIMARY KEY,

        partner_professional_preference_id BIGINT NOT NULL,

        annual_income_id INTEGER NOT NULL,

        created_at TIMESTAMP NULL
            DEFAULT CURRENT_TIMESTAMP,

        CONSTRAINT uq_partner_preference_annual_income
            UNIQUE (
                partner_professional_preference_id,
                annual_income_id
            ),

        CONSTRAINT fk_partner_preference_income_parent
            FOREIGN KEY (
                partner_professional_preference_id
            )
            REFERENCES member_partner_professional_preferences(id)
            ON UPDATE RESTRICT
            ON DELETE CASCADE,

        CONSTRAINT fk_partner_preference_income_master
            FOREIGN KEY (annual_income_id)
            REFERENCES master_annual_incomes(id)
            ON UPDATE RESTRICT
            ON DELETE RESTRICT
    );

CREATE INDEX IF NOT EXISTS
    idx_partner_preference_income_parent
ON member_partner_preference_annual_incomes (
    partner_professional_preference_id
);

CREATE INDEX IF NOT EXISTS
    idx_partner_preference_income_master
ON member_partner_preference_annual_incomes (
    annual_income_id
);

ALTER TABLE member_partner_professional_preferences
    DROP CONSTRAINT IF EXISTS
        fk_partner_professional_income_from;

ALTER TABLE member_partner_professional_preferences
    DROP CONSTRAINT IF EXISTS
        fk_partner_professional_income_to;

ALTER TABLE member_partner_professional_preferences
    DROP COLUMN IF EXISTS annual_income_from_id;

ALTER TABLE member_partner_professional_preferences
    DROP COLUMN IF EXISTS annual_income_to_id;


-- ============================================================
-- Location: migrate single state/city into junction tables
-- ============================================================

CREATE TABLE IF NOT EXISTS
    member_partner_preference_states (
        id BIGSERIAL PRIMARY KEY,

        partner_location_preference_id BIGINT NOT NULL,

        state_id INTEGER NOT NULL,

        created_at TIMESTAMP NULL
            DEFAULT CURRENT_TIMESTAMP,

        CONSTRAINT uq_partner_preference_state
            UNIQUE (
                partner_location_preference_id,
                state_id
            ),

        CONSTRAINT fk_partner_preference_state_parent
            FOREIGN KEY (
                partner_location_preference_id
            )
            REFERENCES member_partner_location_preferences(id)
            ON UPDATE RESTRICT
            ON DELETE CASCADE,

        CONSTRAINT fk_partner_preference_state_master
            FOREIGN KEY (state_id)
            REFERENCES master_states(id)
            ON UPDATE RESTRICT
            ON DELETE RESTRICT
    );

CREATE INDEX IF NOT EXISTS
    idx_partner_preference_state_parent
ON member_partner_preference_states (
    partner_location_preference_id
);

CREATE INDEX IF NOT EXISTS
    idx_partner_preference_state_master
ON member_partner_preference_states (
    state_id
);


CREATE TABLE IF NOT EXISTS
    member_partner_preference_cities (
        id BIGSERIAL PRIMARY KEY,

        partner_location_preference_id BIGINT NOT NULL,

        city_id INTEGER NOT NULL,

        created_at TIMESTAMP NULL
            DEFAULT CURRENT_TIMESTAMP,

        CONSTRAINT uq_partner_preference_city
            UNIQUE (
                partner_location_preference_id,
                city_id
            ),

        CONSTRAINT fk_partner_preference_city_parent
            FOREIGN KEY (
                partner_location_preference_id
            )
            REFERENCES member_partner_location_preferences(id)
            ON UPDATE RESTRICT
            ON DELETE CASCADE,

        CONSTRAINT fk_partner_preference_city_master
            FOREIGN KEY (city_id)
            REFERENCES master_cities(id)
            ON UPDATE RESTRICT
            ON DELETE RESTRICT
    );

CREATE INDEX IF NOT EXISTS
    idx_partner_preference_city_parent
ON member_partner_preference_cities (
    partner_location_preference_id
);

CREATE INDEX IF NOT EXISTS
    idx_partner_preference_city_master
ON member_partner_preference_cities (
    city_id
);

ALTER TABLE member_partner_location_preferences
    DROP CONSTRAINT IF EXISTS
        fk_partner_location_preference_state;

ALTER TABLE member_partner_location_preferences
    DROP CONSTRAINT IF EXISTS
        fk_partner_location_preference_city;

ALTER TABLE member_partner_location_preferences
    DROP COLUMN IF EXISTS state_id;

ALTER TABLE member_partner_location_preferences
    DROP COLUMN IF EXISTS city_id;

COMMIT;