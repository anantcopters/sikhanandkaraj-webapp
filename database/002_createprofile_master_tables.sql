BEGIN;

CREATE TABLE IF NOT EXISTS master_marital_statuses (
    id SMALLSERIAL PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    display_order SMALLINT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_master_marital_statuses_active_order
    ON master_marital_statuses (is_active, display_order);

CREATE TABLE IF NOT EXISTS master_heights (
    id SMALLSERIAL PRIMARY KEY,
    height_cm SMALLINT NOT NULL UNIQUE,
    display_name VARCHAR(50) NOT NULL,
    display_order SMALLINT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT chk_master_heights_height_cm
        CHECK (height_cm BETWEEN 120 AND 220)
);

CREATE INDEX IF NOT EXISTS idx_master_heights_active_order
    ON master_heights (is_active, display_order);

CREATE TABLE IF NOT EXISTS master_mother_tongues (
    id SMALLSERIAL PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    display_order SMALLINT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_master_mother_tongues_active_order
    ON master_mother_tongues (is_active, display_order);

CREATE TABLE IF NOT EXISTS master_countries (
    id SMALLSERIAL PRIMARY KEY,
    iso_code CHAR(2) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    phone_code VARCHAR(10) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS master_states (
    id SERIAL PRIMARY KEY,
    country_id SMALLINT NOT NULL,
    code VARCHAR(10) NOT NULL,
    name VARCHAR(100) NOT NULL,
    display_order SMALLINT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_master_states_country
        FOREIGN KEY (country_id)
        REFERENCES master_countries(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT uq_master_states_country_code
        UNIQUE (country_id, code),

    CONSTRAINT uq_master_states_country_name
        UNIQUE (country_id, name)
);

CREATE INDEX IF NOT EXISTS idx_master_states_country_active_order
    ON master_states (
        country_id,
        is_active,
        display_order
    );

CREATE TABLE IF NOT EXISTS master_cities (
    id SERIAL PRIMARY KEY,
    state_id INTEGER NOT NULL,
    name VARCHAR(120) NOT NULL,
    display_order INTEGER NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_master_cities_state
        FOREIGN KEY (state_id)
        REFERENCES master_states(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT uq_master_cities_state_name
        UNIQUE (state_id, name)
);

CREATE INDEX IF NOT EXISTS idx_master_cities_state_active_order
    ON master_cities (
        state_id,
        is_active,
        display_order
    );

COMMIT;

INSERT INTO master_countries (
    iso_code,
    name,
    phone_code,
    is_active,
    created_at,
    updated_at
)
VALUES (
    'IN',
    'India',
    '+91',
    TRUE,
    CURRENT_TIMESTAMP,
    CURRENT_TIMESTAMP
)
ON CONFLICT (iso_code)
DO UPDATE SET
    name = EXCLUDED.name,
    phone_code = EXCLUDED.phone_code,
    is_active = TRUE,
    updated_at = CURRENT_TIMESTAMP;

INSERT INTO master_marital_statuses (
    code,
    name,
    display_order,
    is_active,
    created_at,
    updated_at
)
VALUES
    (
        'NEVER_MARRIED',
        'Never Married',
        1,
        TRUE,
        CURRENT_TIMESTAMP,
        CURRENT_TIMESTAMP
    ),
    (
        'DIVORCED',
        'Divorced',
        2,
        TRUE,
        CURRENT_TIMESTAMP,
        CURRENT_TIMESTAMP
    ),
    (
        'WIDOWED',
        'Widowed',
        3,
        TRUE,
        CURRENT_TIMESTAMP,
        CURRENT_TIMESTAMP
    ),
    (
        'ANNULLED',
        'Marriage Annulled',
        4,
        TRUE,
        CURRENT_TIMESTAMP,
        CURRENT_TIMESTAMP
    ),
    (
        'AWAITING_DIVORCE',
        'Awaiting Divorce',
        5,
        TRUE,
        CURRENT_TIMESTAMP,
        CURRENT_TIMESTAMP
    )
ON CONFLICT (code)
DO UPDATE SET
    name = EXCLUDED.name,
    display_order = EXCLUDED.display_order,
    is_active = TRUE,
    updated_at = CURRENT_TIMESTAMP;

INSERT INTO master_mother_tongues (
    code,
    name,
    display_order,
    is_active,
    created_at,
    updated_at
)
VALUES
    (
        'PUNJABI',
        'Punjabi',
        1,
        TRUE,
        CURRENT_TIMESTAMP,
        CURRENT_TIMESTAMP
    ),
    (
        'HINDI',
        'Hindi',
        2,
        TRUE,
        CURRENT_TIMESTAMP,
        CURRENT_TIMESTAMP
    ),
    (
        'ENGLISH',
        'English',
        3,
        TRUE,
        CURRENT_TIMESTAMP,
        CURRENT_TIMESTAMP
    ),
    (
        'URDU',
        'Urdu',
        4,
        TRUE,
        CURRENT_TIMESTAMP,
        CURRENT_TIMESTAMP
    ),
    (
        'OTHER',
        'Other',
        99,
        TRUE,
        CURRENT_TIMESTAMP,
        CURRENT_TIMESTAMP
    )
ON CONFLICT (code)
DO UPDATE SET
    name = EXCLUDED.name,
    display_order = EXCLUDED.display_order,
    is_active = TRUE,
    updated_at = CURRENT_TIMESTAMP;

INSERT INTO master_heights (
    height_cm,
    display_name,
    display_order,
    is_active,
    created_at,
    updated_at
)
SELECT
    height_cm,
    FLOOR((height_cm / 2.54) / 12)::INTEGER
        || ''' '
        || MOD(
            ROUND(height_cm / 2.54)::INTEGER,
            12
        )
        || '" ('
        || height_cm
        || ' cm)',
    height_cm,
    TRUE,
    CURRENT_TIMESTAMP,
    CURRENT_TIMESTAMP
FROM generate_series(120, 220) AS height_cm
ON CONFLICT (height_cm)
DO UPDATE SET
    display_name = EXCLUDED.display_name,
    display_order = EXCLUDED.display_order,
    is_active = TRUE,
    updated_at = CURRENT_TIMESTAMP;

WITH india AS (
    SELECT id
    FROM master_countries
    WHERE iso_code = 'IN'
)
INSERT INTO master_states (
    country_id,
    code,
    name,
    display_order,
    is_active,
    created_at,
    updated_at
)
SELECT
    india.id,
    state_data.code,
    state_data.name,
    state_data.display_order,
    TRUE,
    CURRENT_TIMESTAMP,
    CURRENT_TIMESTAMP
FROM india
CROSS JOIN (
    VALUES
        ('AN', 'Andaman and Nicobar Islands', 1),
        ('AP', 'Andhra Pradesh', 2),
        ('AR', 'Arunachal Pradesh', 3),
        ('AS', 'Assam', 4),
        ('BR', 'Bihar', 5),
        ('CH', 'Chandigarh', 6),
        ('CG', 'Chhattisgarh', 7),
        (
            'DN',
            'Dadra and Nagar Haveli and Daman and Diu',
            8
        ),
        ('DL', 'Delhi', 9),
        ('GA', 'Goa', 10),
        ('GJ', 'Gujarat', 11),
        ('HR', 'Haryana', 12),
        ('HP', 'Himachal Pradesh', 13),
        ('JK', 'Jammu and Kashmir', 14),
        ('JH', 'Jharkhand', 15),
        ('KA', 'Karnataka', 16),
        ('KL', 'Kerala', 17),
        ('LA', 'Ladakh', 18),
        ('LD', 'Lakshadweep', 19),
        ('MP', 'Madhya Pradesh', 20),
        ('MH', 'Maharashtra', 21),
        ('MN', 'Manipur', 22),
        ('ML', 'Meghalaya', 23),
        ('MZ', 'Mizoram', 24),
        ('NL', 'Nagaland', 25),
        ('OD', 'Odisha', 26),
        ('PY', 'Puducherry', 27),
        ('PB', 'Punjab', 28),
        ('RJ', 'Rajasthan', 29),
        ('SK', 'Sikkim', 30),
        ('TN', 'Tamil Nadu', 31),
        ('TS', 'Telangana', 32),
        ('TR', 'Tripura', 33),
        ('UP', 'Uttar Pradesh', 34),
        ('UK', 'Uttarakhand', 35),
        ('WB', 'West Bengal', 36)
) AS state_data (
    code,
    name,
    display_order
)
ON CONFLICT (country_id, code)
DO UPDATE SET
    name = EXCLUDED.name,
    display_order = EXCLUDED.display_order,
    is_active = TRUE,
    updated_at = CURRENT_TIMESTAMP;

INSERT INTO master_cities (
    state_id,
    name,
    display_order,
    is_active,
    created_at,
    updated_at
)
SELECT
    master_states.id,
    city_data.name,
    city_data.display_order,
    TRUE,
    CURRENT_TIMESTAMP,
    CURRENT_TIMESTAMP
FROM (
    VALUES
        ('RJ', 'Ajmer', 1),
        ('RJ', 'Alwar', 2),
        ('RJ', 'Bharatpur', 3),
        ('RJ', 'Bhilwara', 4),
        ('RJ', 'Bikaner', 5),
        ('RJ', 'Jaipur', 6),
        ('RJ', 'Jodhpur', 7),
        ('RJ', 'Kota', 8),
        ('RJ', 'Sikar', 9),
        ('RJ', 'Udaipur', 10),

        ('PB', 'Amritsar', 1),
        ('PB', 'Bathinda', 2),
        ('PB', 'Jalandhar', 3),
        ('PB', 'Ludhiana', 4),
        ('PB', 'Mohali', 5),
        ('PB', 'Patiala', 6),

        ('DL', 'New Delhi', 1),

        ('HR', 'Ambala', 1),
        ('HR', 'Faridabad', 2),
        ('HR', 'Gurugram', 3),
        ('HR', 'Karnal', 4),
        ('HR', 'Panipat', 5),

        ('UP', 'Agra', 1),
        ('UP', 'Ghaziabad', 2),
        ('UP', 'Gorakhpur', 3),
        ('UP', 'Kanpur', 4),
        ('UP', 'Lucknow', 5),
        ('UP', 'Noida', 6),
        ('UP', 'Varanasi', 7),

        ('CH', 'Chandigarh', 1)
) AS city_data (
    state_code,
    name,
    display_order
)
INNER JOIN master_states
    ON master_states.code = city_data.state_code
INNER JOIN master_countries
    ON master_countries.id = master_states.country_id
   AND master_countries.iso_code = 'IN'
ON CONFLICT (state_id, name)
DO UPDATE SET
    display_order = EXCLUDED.display_order,
    is_active = TRUE,
    updated_at = CURRENT_TIMESTAMP;

BEGIN;

ALTER TABLE member_basic_details
    ADD COLUMN IF NOT EXISTS marital_status_id SMALLINT NULL,
    ADD COLUMN IF NOT EXISTS height_id SMALLINT NULL,
    ADD COLUMN IF NOT EXISTS mother_tongue_id SMALLINT NULL,
    ADD COLUMN IF NOT EXISTS country_id SMALLINT NULL,
    ADD COLUMN IF NOT EXISTS state_id INTEGER NULL,
    ADD COLUMN IF NOT EXISTS city_id INTEGER NULL;

ALTER TABLE member_basic_details
    DROP CONSTRAINT IF EXISTS fk_member_basic_marital_status;

ALTER TABLE member_basic_details
    ADD CONSTRAINT fk_member_basic_marital_status
    FOREIGN KEY (marital_status_id)
    REFERENCES master_marital_statuses(id)
    ON UPDATE RESTRICT
    ON DELETE RESTRICT;

ALTER TABLE member_basic_details
    DROP CONSTRAINT IF EXISTS fk_member_basic_height;

ALTER TABLE member_basic_details
    ADD CONSTRAINT fk_member_basic_height
    FOREIGN KEY (height_id)
    REFERENCES master_heights(id)
    ON UPDATE RESTRICT
    ON DELETE RESTRICT;

ALTER TABLE member_basic_details
    DROP CONSTRAINT IF EXISTS fk_member_basic_mother_tongue;

ALTER TABLE member_basic_details
    ADD CONSTRAINT fk_member_basic_mother_tongue
    FOREIGN KEY (mother_tongue_id)
    REFERENCES master_mother_tongues(id)
    ON UPDATE RESTRICT
    ON DELETE RESTRICT;

ALTER TABLE member_basic_details
    DROP CONSTRAINT IF EXISTS fk_member_basic_country;

ALTER TABLE member_basic_details
    ADD CONSTRAINT fk_member_basic_country
    FOREIGN KEY (country_id)
    REFERENCES master_countries(id)
    ON UPDATE RESTRICT
    ON DELETE RESTRICT;

ALTER TABLE member_basic_details
    DROP CONSTRAINT IF EXISTS fk_member_basic_state;

ALTER TABLE member_basic_details
    ADD CONSTRAINT fk_member_basic_state
    FOREIGN KEY (state_id)
    REFERENCES master_states(id)
    ON UPDATE RESTRICT
    ON DELETE RESTRICT;

ALTER TABLE member_basic_details
    DROP CONSTRAINT IF EXISTS fk_member_basic_city;

ALTER TABLE member_basic_details
    ADD CONSTRAINT fk_member_basic_city
    FOREIGN KEY (city_id)
    REFERENCES master_cities(id)
    ON UPDATE RESTRICT
    ON DELETE RESTRICT;

CREATE INDEX IF NOT EXISTS idx_member_basic_state_city
    ON member_basic_details (
        state_id,
        city_id
    );

COMMIT;