BEGIN;

CREATE TABLE IF NOT EXISTS member_partner_basic_preferences (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,

    age_from SMALLINT NULL,
    age_to SMALLINT NULL,
    is_age_compulsory BOOLEAN NOT NULL DEFAULT FALSE,

    height_from_id INTEGER NULL,
    height_to_id INTEGER NULL,
    is_height_compulsory BOOLEAN NOT NULL DEFAULT FALSE,

    marital_status_id INTEGER NULL,
    is_marital_status_compulsory BOOLEAN NOT NULL DEFAULT FALSE,

    have_children BOOLEAN NULL,
    is_have_children_compulsory BOOLEAN NOT NULL DEFAULT FALSE,

    physical_status_id INTEGER NULL,
    is_physical_status_compulsory BOOLEAN NOT NULL DEFAULT FALSE,

    is_mother_tongue_compulsory BOOLEAN NOT NULL DEFAULT FALSE,
    is_eating_habit_compulsory BOOLEAN NOT NULL DEFAULT FALSE,
    is_drinking_habit_compulsory BOOLEAN NOT NULL DEFAULT FALSE,

    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_member_partner_basic_preferences_user
        UNIQUE (user_id),

    CONSTRAINT fk_partner_basic_preferences_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON UPDATE RESTRICT
        ON DELETE CASCADE,

    CONSTRAINT fk_partner_basic_preferences_height_from
        FOREIGN KEY (height_from_id)
        REFERENCES master_heights(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_partner_basic_preferences_height_to
        FOREIGN KEY (height_to_id)
        REFERENCES master_heights(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_partner_basic_preferences_marital_status
        FOREIGN KEY (marital_status_id)
        REFERENCES master_marital_statuses(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_partner_basic_preferences_physical_status
        FOREIGN KEY (physical_status_id)
        REFERENCES master_physical_statuses(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT chk_partner_preference_age_from
        CHECK (
            age_from IS NULL
            OR age_from BETWEEN 18 AND 80
        ),

    CONSTRAINT chk_partner_preference_age_to
        CHECK (
            age_to IS NULL
            OR age_to BETWEEN 18 AND 80
        ),

    CONSTRAINT chk_partner_preference_age_range
        CHECK (
            age_from IS NULL
            OR age_to IS NULL
            OR age_from <= age_to
        ),

    CONSTRAINT chk_partner_preference_height_range
        CHECK (
            height_from_id IS NULL
            OR height_to_id IS NULL
            OR height_from_id <= height_to_id
        )
);

CREATE INDEX IF NOT EXISTS
    idx_partner_basic_preferences_user
ON member_partner_basic_preferences(user_id);


CREATE TABLE IF NOT EXISTS
    member_partner_preference_mother_tongues (
        id BIGSERIAL PRIMARY KEY,
        partner_basic_preference_id BIGINT NOT NULL,
        mother_tongue_id INTEGER NOT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

        CONSTRAINT uq_partner_preference_mother_tongue
            UNIQUE (
                partner_basic_preference_id,
                mother_tongue_id
            ),

        CONSTRAINT fk_partner_preference_mother_tongue_parent
            FOREIGN KEY (partner_basic_preference_id)
            REFERENCES member_partner_basic_preferences(id)
            ON UPDATE RESTRICT
            ON DELETE CASCADE,

        CONSTRAINT fk_partner_preference_mother_tongue_master
            FOREIGN KEY (mother_tongue_id)
            REFERENCES master_mother_tongues(id)
            ON UPDATE RESTRICT
            ON DELETE RESTRICT
    );

CREATE INDEX IF NOT EXISTS
    idx_partner_preference_mother_tongue_parent
ON member_partner_preference_mother_tongues(
    partner_basic_preference_id
);

CREATE INDEX IF NOT EXISTS
    idx_partner_preference_mother_tongue_master
ON member_partner_preference_mother_tongues(
    mother_tongue_id
);


CREATE TABLE IF NOT EXISTS
    member_partner_preference_eating_habits (
        id BIGSERIAL PRIMARY KEY,
        partner_basic_preference_id BIGINT NOT NULL,
        eating_habit_id INTEGER NOT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

        CONSTRAINT uq_partner_preference_eating_habit
            UNIQUE (
                partner_basic_preference_id,
                eating_habit_id
            ),

        CONSTRAINT fk_partner_preference_eating_habit_parent
            FOREIGN KEY (partner_basic_preference_id)
            REFERENCES member_partner_basic_preferences(id)
            ON UPDATE RESTRICT
            ON DELETE CASCADE,

        CONSTRAINT fk_partner_preference_eating_habit_master
            FOREIGN KEY (eating_habit_id)
            REFERENCES master_eating_habits(id)
            ON UPDATE RESTRICT
            ON DELETE RESTRICT
    );

CREATE INDEX IF NOT EXISTS
    idx_partner_preference_eating_habit_parent
ON member_partner_preference_eating_habits(
    partner_basic_preference_id
);

CREATE INDEX IF NOT EXISTS
    idx_partner_preference_eating_habit_master
ON member_partner_preference_eating_habits(
    eating_habit_id
);


CREATE TABLE IF NOT EXISTS
    member_partner_preference_drinking_habits (
        id BIGSERIAL PRIMARY KEY,
        partner_basic_preference_id BIGINT NOT NULL,
        drinking_habit_id INTEGER NOT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

        CONSTRAINT uq_partner_preference_drinking_habit
            UNIQUE (
                partner_basic_preference_id,
                drinking_habit_id
            ),

        CONSTRAINT fk_partner_preference_drinking_habit_parent
            FOREIGN KEY (partner_basic_preference_id)
            REFERENCES member_partner_basic_preferences(id)
            ON UPDATE RESTRICT
            ON DELETE CASCADE,

        CONSTRAINT fk_partner_preference_drinking_habit_master
            FOREIGN KEY (drinking_habit_id)
            REFERENCES master_drinking_habits(id)
            ON UPDATE RESTRICT
            ON DELETE RESTRICT
    );

CREATE INDEX IF NOT EXISTS
    idx_partner_preference_drinking_habit_parent
ON member_partner_preference_drinking_habits(
    partner_basic_preference_id
);

CREATE INDEX IF NOT EXISTS
    idx_partner_preference_drinking_habit_master
ON member_partner_preference_drinking_habits(
    drinking_habit_id
);

COMMIT;