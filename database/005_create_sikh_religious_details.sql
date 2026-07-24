BEGIN;

-- ============================================================================
-- Sikh community/caste master
-- ============================================================================

CREATE TABLE IF NOT EXISTS master_sikh_communities (
    id SMALLSERIAL PRIMARY KEY,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    display_order SMALLINT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_master_sikh_communities_code
        UNIQUE (code),

    CONSTRAINT uq_master_sikh_communities_name
        UNIQUE (name)
);

CREATE INDEX IF NOT EXISTS idx_master_sikh_communities_active_order
ON master_sikh_communities (
    is_active,
    display_order,
    name
);

INSERT INTO master_sikh_communities (
    code,
    name,
    display_order,
    is_active
)
VALUES
    ('JAT_SIKH', 'Jat Sikh', 10, TRUE),
    ('KHATRI_SIKH', 'Khatri Sikh', 20, TRUE),
    ('ARORA_SIKH', 'Arora Sikh', 30, TRUE),
    ('RAMGARHIA', 'Ramgarhia', 40, TRUE),
    ('SAINI_SIKH', 'Saini Sikh', 50, TRUE),
    ('KAMBOJ_SIKH', 'Kamboj Sikh', 60, TRUE),
    ('AHluwalia', 'Ahluwalia', 70, TRUE),
    ('LUBANA_SIKH', 'Lubana Sikh', 80, TRUE),
    ('RAI_SIKH', 'Rai Sikh', 90, TRUE),
    ('MAZHABI_SIKH', 'Mazhabi Sikh', 100, TRUE),
    ('RAMDASIA_SIKH', 'Ramdasia Sikh', 110, TRUE),
    ('RAVIDASIA_SIKH', 'Ravidassia Sikh', 120, TRUE),
    ('OTHER_SIKH', 'Other Sikh Community', 900, TRUE),
    ('PREFER_NOT_TO_SAY', 'Prefer not to say', 999, TRUE)
ON CONFLICT (code)
DO UPDATE SET
    name = EXCLUDED.name,
    display_order = EXCLUDED.display_order,
    is_active = EXCLUDED.is_active,
    updated_at = CURRENT_TIMESTAMP;


-- ============================================================================
-- Sikh sub-community/sub-caste master
-- ============================================================================

CREATE TABLE IF NOT EXISTS master_sikh_subcommunities (
    id SERIAL PRIMARY KEY,
    community_id SMALLINT NOT NULL,
    code VARCHAR(70) NOT NULL,
    name VARCHAR(120) NOT NULL,
    display_order SMALLINT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_master_sikh_subcommunities_community
        FOREIGN KEY (community_id)
        REFERENCES master_sikh_communities(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT uq_master_sikh_subcommunities_community_code
        UNIQUE (community_id, code),

    CONSTRAINT uq_master_sikh_subcommunities_community_name
        UNIQUE (community_id, name)
);

CREATE INDEX IF NOT EXISTS
    idx_master_sikh_subcommunities_community_active
ON master_sikh_subcommunities (
    community_id,
    is_active,
    display_order,
    name
);

-- Seed only commonly used matrimonial community groupings.
-- Add or deactivate rows later without changing application code.

INSERT INTO master_sikh_subcommunities (
    community_id,
    code,
    name,
    display_order,
    is_active
)
SELECT
    community.id,
    seed.code,
    seed.name,
    seed.display_order,
    TRUE
FROM master_sikh_communities community
JOIN (
    VALUES
        ('JAT_SIKH', 'SANDHU', 'Sandhu', 10),
        ('JAT_SIKH', 'SIDHU', 'Sidhu', 20),
        ('JAT_SIKH', 'GILL', 'Gill', 30),
        ('JAT_SIKH', 'BRAR', 'Brar', 40),
        ('JAT_SIKH', 'DHILLON', 'Dhillon', 50),
        ('JAT_SIKH', 'MANN', 'Mann', 60),
        ('JAT_SIKH', 'GREWAL', 'Grewal', 70),
        ('JAT_SIKH', 'BAJWA', 'Bajwa', 80),
        ('JAT_SIKH', 'RANDHAWA', 'Randhawa', 90),
        ('JAT_SIKH', 'OTHER', 'Other', 999),

        ('KHATRI_SIKH', 'BEDI', 'Bedi', 10),
        ('KHATRI_SIKH', 'KAPOOR', 'Kapoor', 20),
        ('KHATRI_SIKH', 'KHANNA', 'Khanna', 30),
        ('KHATRI_SIKH', 'MALHOTRA', 'Malhotra', 40),
        ('KHATRI_SIKH', 'MEHRA', 'Mehra', 50),
        ('KHATRI_SIKH', 'SURI', 'Suri', 60),
        ('KHATRI_SIKH', 'OTHER', 'Other', 999),

        ('ARORA_SIKH', 'AHUJA', 'Ahuja', 10),
        ('ARORA_SIKH', 'ARORA', 'Arora', 20),
        ('ARORA_SIKH', 'CHAWLA', 'Chawla', 30),
        ('ARORA_SIKH', 'GROVER', 'Grover', 40),
        ('ARORA_SIKH', 'NARANG', 'Narang', 50),
        ('ARORA_SIKH', 'OTHER', 'Other', 999),

        ('RAMGARHIA', 'BHOGAL', 'Bhogal', 10),
        ('RAMGARHIA', 'BIRDI', 'Birdi', 20),
        ('RAMGARHIA', 'MATHARU', 'Matharu', 30),
        ('RAMGARHIA', 'REKHY', 'Rekhy', 40),
        ('RAMGARHIA', 'SAGOO', 'Sagoo', 50),
        ('RAMGARHIA', 'OTHER', 'Other', 999),

        ('SAINI_SIKH', 'BHOLA', 'Bhola', 10),
        ('SAINI_SIKH', 'CHANDI', 'Chandi', 20),
        ('SAINI_SIKH', 'DULL', 'Dull', 30),
        ('SAINI_SIKH', 'OTHER', 'Other', 999),

        ('KAMBOJ_SIKH', 'THIND', 'Thind', 10),
        ('KAMBOJ_SIKH', 'SANDHA', 'Sandha', 20),
        ('KAMBOJ_SIKH', 'OTHER', 'Other', 999)
) AS seed (
    community_code,
    code,
    name,
    display_order
)
    ON seed.community_code = community.code
ON CONFLICT (community_id, code)
DO UPDATE SET
    name = EXCLUDED.name,
    display_order = EXCLUDED.display_order,
    is_active = EXCLUDED.is_active,
    updated_at = CURRENT_TIMESTAMP;


-- ============================================================================
-- Moon sign/Raashi master
-- ============================================================================

CREATE TABLE IF NOT EXISTS master_moon_signs (
    id SMALLSERIAL PRIMARY KEY,
    code VARCHAR(30) NOT NULL,
    name VARCHAR(80) NOT NULL,
    english_name VARCHAR(50) NOT NULL,
    display_order SMALLINT NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_master_moon_signs_code UNIQUE (code),
    CONSTRAINT uq_master_moon_signs_name UNIQUE (name)
);

INSERT INTO master_moon_signs (
    code,
    name,
    english_name,
    display_order,
    is_active
)
VALUES
    ('MESH', 'Mesh', 'Aries', 10, TRUE),
    ('VRISHABH', 'Vrishabh', 'Taurus', 20, TRUE),
    ('MITHUN', 'Mithun', 'Gemini', 30, TRUE),
    ('KARK', 'Kark', 'Cancer', 40, TRUE),
    ('SINGH', 'Singh', 'Leo', 50, TRUE),
    ('KANYA', 'Kanya', 'Virgo', 60, TRUE),
    ('TULA', 'Tula', 'Libra', 70, TRUE),
    ('VRISHCHIK', 'Vrishchik', 'Scorpio', 80, TRUE),
    ('DHANU', 'Dhanu', 'Sagittarius', 90, TRUE),
    ('MAKAR', 'Makar', 'Capricorn', 100, TRUE),
    ('KUMBH', 'Kumbh', 'Aquarius', 110, TRUE),
    ('MEEN', 'Meen', 'Pisces', 120, TRUE)
ON CONFLICT (code)
DO UPDATE SET
    name = EXCLUDED.name,
    english_name = EXCLUDED.english_name,
    display_order = EXCLUDED.display_order,
    is_active = EXCLUDED.is_active,
    updated_at = CURRENT_TIMESTAMP;


-- ============================================================================
-- Birth star/Nakshatra master
-- ============================================================================

CREATE TABLE IF NOT EXISTS master_birth_stars (
    id SMALLSERIAL PRIMARY KEY,
    code VARCHAR(40) NOT NULL,
    name VARCHAR(80) NOT NULL,
    display_order SMALLINT NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_master_birth_stars_code UNIQUE (code),
    CONSTRAINT uq_master_birth_stars_name UNIQUE (name)
);

INSERT INTO master_birth_stars (
    code,
    name,
    display_order,
    is_active
)
VALUES
    ('ASHWINI', 'Ashwini', 10, TRUE),
    ('BHARANI', 'Bharani', 20, TRUE),
    ('KRITTIKA', 'Krittika', 30, TRUE),
    ('ROHINI', 'Rohini', 40, TRUE),
    ('MRIGASHIRA', 'Mrigashira', 50, TRUE),
    ('ARDRA', 'Ardra', 60, TRUE),
    ('PUNARVASU', 'Punarvasu', 70, TRUE),
    ('PUSHYA', 'Pushya', 80, TRUE),
    ('ASHLESHA', 'Ashlesha', 90, TRUE),
    ('MAGHA', 'Magha', 100, TRUE),
    ('PURVA_PHALGUNI', 'Purva Phalguni', 110, TRUE),
    ('UTTARA_PHALGUNI', 'Uttara Phalguni', 120, TRUE),
    ('HASTA', 'Hasta', 130, TRUE),
    ('CHITRA', 'Chitra', 140, TRUE),
    ('SWATI', 'Swati', 150, TRUE),
    ('VISHAKHA', 'Vishakha', 160, TRUE),
    ('ANURADHA', 'Anuradha', 170, TRUE),
    ('JYESHTHA', 'Jyeshtha', 180, TRUE),
    ('MULA', 'Mula', 190, TRUE),
    ('PURVA_ASHADHA', 'Purva Ashadha', 200, TRUE),
    ('UTTARA_ASHADHA', 'Uttara Ashadha', 210, TRUE),
    ('SHRAVANA', 'Shravana', 220, TRUE),
    ('DHANISHTHA', 'Dhanishtha', 230, TRUE),
    ('SHATABHISHA', 'Shatabhisha', 240, TRUE),
    ('PURVA_BHADRAPADA', 'Purva Bhadrapada', 250, TRUE),
    ('UTTARA_BHADRAPADA', 'Uttara Bhadrapada', 260, TRUE),
    ('REVATI', 'Revati', 270, TRUE)
ON CONFLICT (code)
DO UPDATE SET
    name = EXCLUDED.name,
    display_order = EXCLUDED.display_order,
    is_active = EXCLUDED.is_active,
    updated_at = CURRENT_TIMESTAMP;


-- ============================================================================
-- Member Sikh and religious details
-- ============================================================================

CREATE TABLE IF NOT EXISTS member_sikh_religious_details (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,

    community_id SMALLINT NOT NULL,
    subcommunity_id INTEGER NOT NULL,

    birth_hour SMALLINT NOT NULL,
    birth_minute SMALLINT NOT NULL,
    birth_meridiem VARCHAR(2) NOT NULL,

    birth_country_id SMALLINT NOT NULL,
    birth_state_id INTEGER NOT NULL,
    birth_city_id INTEGER NOT NULL,

    gotra VARCHAR(100) NULL,
    moon_sign_id SMALLINT NULL,
    birth_star_id SMALLINT NULL,
    has_dosh VARCHAR(20) NULL,

    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_member_sikh_religious_details_user
        UNIQUE (user_id),

    CONSTRAINT fk_member_sikh_religious_details_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON UPDATE RESTRICT
        ON DELETE CASCADE,

    CONSTRAINT fk_member_sikh_religious_community
        FOREIGN KEY (community_id)
        REFERENCES master_sikh_communities(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_member_sikh_religious_subcommunity
        FOREIGN KEY (subcommunity_id)
        REFERENCES master_sikh_subcommunities(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_member_sikh_religious_birth_country
        FOREIGN KEY (birth_country_id)
        REFERENCES master_countries(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_member_sikh_religious_birth_state
        FOREIGN KEY (birth_state_id)
        REFERENCES master_states(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_member_sikh_religious_birth_city
        FOREIGN KEY (birth_city_id)
        REFERENCES master_cities(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_member_sikh_religious_moon_sign
        FOREIGN KEY (moon_sign_id)
        REFERENCES master_moon_signs(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_member_sikh_religious_birth_star
        FOREIGN KEY (birth_star_id)
        REFERENCES master_birth_stars(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT chk_sikh_religious_birth_hour
        CHECK (
            birth_hour IS NULL
            OR birth_hour BETWEEN 1 AND 12
        ),

    CONSTRAINT chk_sikh_religious_birth_minute
        CHECK (
            birth_minute IS NULL
            OR birth_minute BETWEEN 0 AND 59
        ),

    CONSTRAINT chk_sikh_religious_birth_meridiem
        CHECK (
            birth_meridiem IS NULL
            OR birth_meridiem IN ('AM', 'PM')
        ),    

    CONSTRAINT chk_sikh_religious_dosh
        CHECK (
            has_dosh IS NULL
            OR has_dosh IN (
                'NO',
                'YES',
                'DONT_KNOW',
                'NOT_APPLICABLE'
            )
        )
);

CREATE INDEX IF NOT EXISTS
    idx_member_sikh_religious_birth_location
ON member_sikh_religious_details (
    birth_country_id,
    birth_state_id,
    birth_city_id
);

COMMIT;