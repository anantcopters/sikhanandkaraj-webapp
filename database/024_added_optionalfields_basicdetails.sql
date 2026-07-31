BEGIN;

CREATE TABLE IF NOT EXISTS master_drinking_habits (
    id SERIAL PRIMARY KEY,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    display_order SMALLINT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_master_drinking_habits_code
        UNIQUE (code),

    CONSTRAINT uq_master_drinking_habits_name
        UNIQUE (name)
);

CREATE INDEX IF NOT EXISTS idx_master_drinking_habits_active_order
    ON master_drinking_habits (
        is_active,
        display_order
    );

CREATE TABLE IF NOT EXISTS master_eating_habits (
    id SERIAL PRIMARY KEY,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    display_order SMALLINT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_master_eating_habits_code
        UNIQUE (code),

    CONSTRAINT uq_master_eating_habits_name
        UNIQUE (name)
);

CREATE INDEX IF NOT EXISTS idx_master_eating_habits_active_order
    ON master_eating_habits (
        is_active,
        display_order
    );

CREATE TABLE IF NOT EXISTS master_physical_statuses (
    id SERIAL PRIMARY KEY,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    display_order SMALLINT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_master_physical_statuses_code
        UNIQUE (code),

    CONSTRAINT uq_master_physical_statuses_name
        UNIQUE (name)
);

CREATE INDEX IF NOT EXISTS idx_master_physical_statuses_active_order
    ON master_physical_statuses (
        is_active,
        display_order
    );

INSERT INTO master_drinking_habits (
    code,
    name,
    display_order,
    is_active
)
VALUES
    ('NEVER_DRINKS', 'Never Drinks', 10, TRUE),
    ('DRINKS_SOCIALLY', 'Drinks Socially', 20, TRUE),
    ('DRINKS_REGULARLY', 'Drinks Regularly', 30, TRUE)
ON CONFLICT (code)
DO UPDATE SET
    name = EXCLUDED.name,
    display_order = EXCLUDED.display_order,
    is_active = EXCLUDED.is_active,
    updated_at = CURRENT_TIMESTAMP;

INSERT INTO master_eating_habits (
    code,
    name,
    display_order,
    is_active
)
VALUES
    ('VEGETARIAN', 'Vegetarian', 10, TRUE),
    ('NON_VEGETARIAN', 'Non Vegetarian', 20, TRUE),
    ('EGGETARIAN', 'Eggetarian', 30, TRUE)
ON CONFLICT (code)
DO UPDATE SET
    name = EXCLUDED.name,
    display_order = EXCLUDED.display_order,
    is_active = EXCLUDED.is_active,
    updated_at = CURRENT_TIMESTAMP;

INSERT INTO master_physical_statuses (
    code,
    name,
    display_order,
    is_active
)
VALUES
    ('NORMAL', 'Normal', 10, TRUE),
    ('PHYSICALLY_CHALLENGED', 'Physically Challenged', 20, TRUE)
ON CONFLICT (code)
DO UPDATE SET
    name = EXCLUDED.name,
    display_order = EXCLUDED.display_order,
    is_active = EXCLUDED.is_active,
    updated_at = CURRENT_TIMESTAMP;

ALTER TABLE member_basic_details
    ADD COLUMN IF NOT EXISTS drinking_habit_id INTEGER NULL,
    ADD COLUMN IF NOT EXISTS eating_habit_id INTEGER NULL,
    ADD COLUMN IF NOT EXISTS physical_status_id INTEGER NULL,
    ADD COLUMN IF NOT EXISTS number_of_children SMALLINT NULL,
    ADD COLUMN IF NOT EXISTS children_living_together BOOLEAN NULL;

ALTER TABLE member_basic_details
    ADD CONSTRAINT fk_member_basic_details_drinking_habit
        FOREIGN KEY (drinking_habit_id)
        REFERENCES master_drinking_habits(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    ADD CONSTRAINT fk_member_basic_details_eating_habit
        FOREIGN KEY (eating_habit_id)
        REFERENCES master_eating_habits(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    ADD CONSTRAINT fk_member_basic_details_physical_status
        FOREIGN KEY (physical_status_id)
        REFERENCES master_physical_statuses(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    ADD CONSTRAINT chk_member_basic_details_children_count
        CHECK (
            number_of_children IS NULL
            OR number_of_children BETWEEN 1 AND 99
        ),

    ADD CONSTRAINT chk_member_basic_details_children_living
        CHECK (
            children_living_together IS NULL
            OR number_of_children IS NOT NULL
        );

CREATE INDEX IF NOT EXISTS idx_member_basic_details_drinking_habit
    ON member_basic_details (drinking_habit_id);

CREATE INDEX IF NOT EXISTS idx_member_basic_details_eating_habit
    ON member_basic_details (eating_habit_id);

CREATE INDEX IF NOT EXISTS idx_member_basic_details_physical_status
    ON member_basic_details (physical_status_id);

COMMIT;