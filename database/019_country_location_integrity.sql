BEGIN;

/*
 * Persist country-level partner preferences.
 * No rows means Any country, preserving all existing preferences.
 */
CREATE TABLE IF NOT EXISTS member_partner_preference_countries (
    id BIGSERIAL PRIMARY KEY,
    partner_location_preference_id BIGINT NOT NULL,
    country_id SMALLINT NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_partner_preference_country UNIQUE (
        partner_location_preference_id,
        country_id
    ),
    CONSTRAINT fk_partner_preference_country_parent FOREIGN KEY (
        partner_location_preference_id
    ) REFERENCES member_partner_location_preferences (id)
      ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT fk_partner_preference_country_master FOREIGN KEY (country_id)
      REFERENCES master_countries (id)
      ON UPDATE RESTRICT ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS idx_partner_preference_country_parent
ON member_partner_preference_countries (partner_location_preference_id);

CREATE INDEX IF NOT EXISTS idx_partner_preference_country_master
ON member_partner_preference_countries (country_id);

/* Required parent keys for composite foreign keys. */
ALTER TABLE master_states
    ADD CONSTRAINT uq_master_states_id_country UNIQUE (id, country_id);

ALTER TABLE master_cities
    ADD CONSTRAINT uq_master_cities_id_state UNIQUE (id, state_id);

/* Fail before adding constraints if historical data contains mixed hierarchies. */
DO $$
DECLARE
    invalid_count BIGINT;
BEGIN
    SELECT COUNT(*) INTO invalid_count
    FROM member_basic_details d
    JOIN master_states s ON s.id = d.state_id
    JOIN master_cities c ON c.id = d.city_id
    WHERE (d.country_id IS NOT NULL AND s.country_id <> d.country_id)
       OR (d.state_id IS NOT NULL AND c.state_id <> d.state_id);
    IF invalid_count > 0 THEN
        RAISE EXCEPTION 'member_basic_details has % invalid location hierarchies', invalid_count;
    END IF;

    SELECT COUNT(*) INTO invalid_count
    FROM member_family_details d
    JOIN master_states s ON s.id = d.state_id
    JOIN master_cities c ON c.id = d.city_id
    WHERE s.country_id <> d.country_id OR c.state_id <> d.state_id;
    IF invalid_count > 0 THEN
        RAISE EXCEPTION 'member_family_details has % invalid location hierarchies', invalid_count;
    END IF;

    SELECT COUNT(*) INTO invalid_count
    FROM member_sikh_religious_details d
    JOIN master_states s ON s.id = d.birth_state_id
    JOIN master_cities c ON c.id = d.birth_city_id
    WHERE s.country_id <> d.birth_country_id OR c.state_id <> d.birth_state_id;
    IF invalid_count > 0 THEN
        RAISE EXCEPTION 'member_sikh_religious_details has % invalid birth-location hierarchies', invalid_count;
    END IF;

    SELECT COUNT(*) INTO invalid_count
    FROM field_officers d
    JOIN master_states s ON s.id = d.state_id
    JOIN master_cities c ON c.id = d.city_id
    WHERE s.country_id <> d.country_id OR c.state_id <> d.state_id;
    IF invalid_count > 0 THEN
        RAISE EXCEPTION 'field_officers has % invalid location hierarchies', invalid_count;
    END IF;

    SELECT COUNT(*) INTO invalid_count
    FROM prelaunch_profiles d
    JOIN master_states s ON s.id = d.state_id
    JOIN master_cities c ON c.id = d.city_id
    WHERE s.country_id <> d.country_id OR c.state_id <> d.state_id;
    IF invalid_count > 0 THEN
        RAISE EXCEPTION 'prelaunch_profiles has % invalid location hierarchies', invalid_count;
    END IF;
END $$;

ALTER TABLE member_basic_details
    ADD CONSTRAINT fk_member_basic_state_country
    FOREIGN KEY (state_id, country_id)
    REFERENCES master_states (id, country_id)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
    ADD CONSTRAINT fk_member_basic_city_state
    FOREIGN KEY (city_id, state_id)
    REFERENCES master_cities (id, state_id)
    ON UPDATE RESTRICT ON DELETE RESTRICT;

ALTER TABLE member_family_details
    ADD CONSTRAINT fk_member_family_state_country
    FOREIGN KEY (state_id, country_id)
    REFERENCES master_states (id, country_id)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
    ADD CONSTRAINT fk_member_family_city_state
    FOREIGN KEY (city_id, state_id)
    REFERENCES master_cities (id, state_id)
    ON UPDATE RESTRICT ON DELETE RESTRICT;

ALTER TABLE member_sikh_religious_details
    ADD CONSTRAINT fk_member_sikh_birth_state_country
    FOREIGN KEY (birth_state_id, birth_country_id)
    REFERENCES master_states (id, country_id)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
    ADD CONSTRAINT fk_member_sikh_birth_city_state
    FOREIGN KEY (birth_city_id, birth_state_id)
    REFERENCES master_cities (id, state_id)
    ON UPDATE RESTRICT ON DELETE RESTRICT;

ALTER TABLE field_officers
    ADD CONSTRAINT fk_field_officers_state_country
    FOREIGN KEY (state_id, country_id)
    REFERENCES master_states (id, country_id)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
    ADD CONSTRAINT fk_field_officers_city_state
    FOREIGN KEY (city_id, state_id)
    REFERENCES master_cities (id, state_id)
    ON UPDATE RESTRICT ON DELETE RESTRICT;

ALTER TABLE prelaunch_profiles
    ADD CONSTRAINT fk_prelaunch_state_country
    FOREIGN KEY (state_id, country_id)
    REFERENCES master_states (id, country_id)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
    ADD CONSTRAINT fk_prelaunch_city_state
    FOREIGN KEY (city_id, state_id)
    REFERENCES master_cities (id, state_id)
    ON UPDATE RESTRICT ON DELETE RESTRICT;

COMMIT;
