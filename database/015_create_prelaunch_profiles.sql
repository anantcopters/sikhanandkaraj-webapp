BEGIN;

-- ============================================================
-- 1. PRE-LAUNCH PROFILE MASTER TABLE
-- ============================================================

CREATE TABLE IF NOT EXISTS prelaunch_profiles (
    id BIGSERIAL PRIMARY KEY,

    profile_reference VARCHAR(30) NOT NULL,

    -- Registration / identity
    profile_created_for VARCHAR(30) NOT NULL,
    gender VARCHAR(20) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    date_of_birth DATE NOT NULL,

    -- Contact details
    email VARCHAR(190) NOT NULL,
    country_code VARCHAR(8) NOT NULL DEFAULT '+91',
    mobile_number VARCHAR(20) NOT NULL,

    -- Basic details
    marital_status_id INTEGER NOT NULL,
    height_id INTEGER NOT NULL,
    mother_tongue_id INTEGER NOT NULL,
    country_id INTEGER NOT NULL,
    state_id INTEGER NOT NULL,
    city_id INTEGER NOT NULL,

    -- Education and profession
    highest_education_id INTEGER NOT NULL,
    employed_in VARCHAR(40) NOT NULL,
    occupation_id INTEGER NOT NULL,

    -- Family details
    father_name VARCHAR(100) NOT NULL,
    mother_name VARCHAR(100) NOT NULL,
    family_value_id INTEGER NOT NULL,
    family_type_id INTEGER NOT NULL,
    family_status_id INTEGER NOT NULL,
    sikh_community_id INTEGER NOT NULL,
    sikh_subcommunity_id INTEGER NOT NULL,

    -- Pre-launch operational details
    field_officer_id BIGINT NOT NULL,
    created_by BIGINT NOT NULL,
    created_source VARCHAR(30) NOT NULL DEFAULT 'FIELD_OFFICER',
    is_prelaunch_profile BOOLEAN NOT NULL DEFAULT TRUE,

    status VARCHAR(20) NOT NULL DEFAULT 'DRAFT',

    -- Admin review details
    reviewed_by BIGINT NULL,
    reviewed_at TIMESTAMP NULL,
    rejection_reason TEXT NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    CONSTRAINT uq_prelaunch_profiles_reference
        UNIQUE (profile_reference),

    CONSTRAINT chk_prelaunch_profile_created_for
        CHECK (
            profile_created_for IN (
                'SELF',
                'SON',
                'DAUGHTER',
                'BROTHER',
                'SISTER',
                'RELATIVE',
                'FRIEND'
            )
        ),

    CONSTRAINT chk_prelaunch_profile_gender
        CHECK (
            gender IN (
                'MALE',
                'FEMALE'
            )
        ),

    CONSTRAINT chk_prelaunch_employed_in
        CHECK (
            employed_in IN (
                'GOVERNMENT_PSU',
                'PRIVATE',
                'BUSINESS',
                'DEFENCE',
                'SELF_EMPLOYED',
                'NOT_WORKING'
            )
        ),

    CONSTRAINT chk_prelaunch_profile_status
        CHECK (
            status IN (
                'DRAFT',
                'APPROVED',
                'REJECTED'
            )
        ),

    CONSTRAINT chk_prelaunch_created_source
        CHECK (
            created_source = 'FIELD_OFFICER'
        ),

    CONSTRAINT chk_prelaunch_profile_flag
        CHECK (
            is_prelaunch_profile = TRUE
        ),

    CONSTRAINT chk_prelaunch_country_code
        CHECK (
            country_code ~ '^\+[1-9][0-9]{0,3}$'
        ),

    CONSTRAINT chk_prelaunch_mobile_number
        CHECK (
            mobile_number ~ '^[0-9]{10,15}$'
        ),

    CONSTRAINT chk_prelaunch_email_format
        CHECK (
            email ~* '^[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}$'
        ),

    CONSTRAINT fk_prelaunch_marital_status
        FOREIGN KEY (marital_status_id)
        REFERENCES master_marital_statuses(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_prelaunch_height
        FOREIGN KEY (height_id)
        REFERENCES master_heights(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_prelaunch_mother_tongue
        FOREIGN KEY (mother_tongue_id)
        REFERENCES master_mother_tongues(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_prelaunch_country
        FOREIGN KEY (country_id)
        REFERENCES master_countries(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_prelaunch_state
        FOREIGN KEY (state_id)
        REFERENCES master_states(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_prelaunch_city
        FOREIGN KEY (city_id)
        REFERENCES master_cities(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_prelaunch_education
        FOREIGN KEY (highest_education_id)
        REFERENCES master_educations(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_prelaunch_occupation
        FOREIGN KEY (occupation_id)
        REFERENCES master_occupations(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_prelaunch_family_value
        FOREIGN KEY (family_value_id)
        REFERENCES master_family_values(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_prelaunch_family_type
        FOREIGN KEY (family_type_id)
        REFERENCES master_family_types(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_prelaunch_family_status
        FOREIGN KEY (family_status_id)
        REFERENCES master_family_statuses(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_prelaunch_sikh_community
        FOREIGN KEY (sikh_community_id)
        REFERENCES master_sikh_communities(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_prelaunch_sikh_subcommunity
        FOREIGN KEY (sikh_subcommunity_id)
        REFERENCES master_sikh_subcommunities(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_prelaunch_field_officer
        FOREIGN KEY (field_officer_id)
        REFERENCES field_officers(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT
);


-- ============================================================
-- 2. UNIQUE EMAIL AND MOBILE
-- Soft-deleted records do not block reuse.
-- Email uniqueness is case-insensitive.
-- ============================================================

CREATE UNIQUE INDEX IF NOT EXISTS
    uq_prelaunch_profiles_active_email
ON prelaunch_profiles (
    LOWER(email)
)
WHERE deleted_at IS NULL;


CREATE UNIQUE INDEX IF NOT EXISTS
    uq_prelaunch_profiles_active_mobile
ON prelaunch_profiles (
    country_code,
    mobile_number
)
WHERE deleted_at IS NULL;


-- ============================================================
-- 3. PERFORMANCE INDEXES
-- ============================================================

CREATE INDEX IF NOT EXISTS
    idx_prelaunch_profiles_status_created
ON prelaunch_profiles (
    status,
    created_at DESC
)
WHERE deleted_at IS NULL;


CREATE INDEX IF NOT EXISTS
    idx_prelaunch_profiles_field_officer
ON prelaunch_profiles (
    field_officer_id,
    created_at DESC
)
WHERE deleted_at IS NULL;


CREATE INDEX IF NOT EXISTS
    idx_prelaunch_profiles_reviewed_by
ON prelaunch_profiles (
    reviewed_by
)
WHERE reviewed_by IS NOT NULL
  AND deleted_at IS NULL;


CREATE INDEX IF NOT EXISTS
    idx_prelaunch_profiles_location
ON prelaunch_profiles (
    country_id,
    state_id,
    city_id
)
WHERE deleted_at IS NULL;


-- ============================================================
-- 4. PRE-LAUNCH PHOTOS
-- Stores only secure writable-folder relative paths.
-- It does not store publicly accessible URLs.
-- ============================================================

CREATE TABLE IF NOT EXISTS prelaunch_photos (
    id BIGSERIAL PRIMARY KEY,

    prelaunch_profile_id BIGINT NOT NULL,

    -- Exactly three sequence positions are allowed.
    sequence_no SMALLINT NOT NULL,

    -- Paths relative to the CI4 writable directory.
    original_path VARCHAR(500) NOT NULL,
    medium_path VARCHAR(500) NOT NULL,
    thumbnail_path VARCHAR(500) NOT NULL,

    original_filename VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_extension VARCHAR(10) NOT NULL,
    file_size_bytes BIGINT NOT NULL,

    width_px INTEGER NOT NULL,
    height_px INTEGER NOT NULL,

    checksum_sha256 CHAR(64) NOT NULL,

    approval_status VARCHAR(20) NOT NULL DEFAULT 'PENDING',

    reviewed_by BIGINT NULL,
    reviewed_at TIMESTAMP NULL,
    rejection_reason TEXT NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    CONSTRAINT uq_prelaunch_photo_sequence
        UNIQUE (
            prelaunch_profile_id,
            sequence_no
        ),

    CONSTRAINT chk_prelaunch_photo_sequence
        CHECK (
            sequence_no BETWEEN 1 AND 3
        ),

    CONSTRAINT chk_prelaunch_photo_status
        CHECK (
            approval_status IN (
                'PENDING',
                'APPROVED',
                'REJECTED'
            )
        ),

    CONSTRAINT chk_prelaunch_photo_file_size
        CHECK (
            file_size_bytes > 0
            AND file_size_bytes <= 5242880
        ),

    CONSTRAINT chk_prelaunch_photo_dimensions
        CHECK (
            width_px >= 400
            AND height_px >= 400
        ),

    CONSTRAINT chk_prelaunch_photo_extension
        CHECK (
            LOWER(file_extension) IN (
                'jpg',
                'jpeg',
                'png',
                'webp'
            )
        ),

    CONSTRAINT chk_prelaunch_photo_mime
        CHECK (
            mime_type IN (
                'image/jpeg',
                'image/png',
                'image/webp'
            )
        ),

    CONSTRAINT chk_prelaunch_photo_checksum
        CHECK (
            checksum_sha256 ~ '^[a-fA-F0-9]{64}$'
        ),

    CONSTRAINT fk_prelaunch_photo_profile
        FOREIGN KEY (prelaunch_profile_id)
        REFERENCES prelaunch_profiles(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);


-- ============================================================
-- 5. PHOTO PERFORMANCE INDEXES
-- ============================================================

CREATE INDEX IF NOT EXISTS
    idx_prelaunch_photos_profile
ON prelaunch_photos (
    prelaunch_profile_id,
    sequence_no
)
WHERE deleted_at IS NULL;


CREATE INDEX IF NOT EXISTS
    idx_prelaunch_photos_approval_status
ON prelaunch_photos (
    approval_status,
    created_at DESC
)
WHERE deleted_at IS NULL;


CREATE INDEX IF NOT EXISTS
    idx_prelaunch_photos_reviewed_by
ON prelaunch_photos (
    reviewed_by
)
WHERE reviewed_by IS NOT NULL
  AND deleted_at IS NULL;


-- Prevent the same physical image from being uploaded more than once
-- for the same pre-launch profile.
CREATE UNIQUE INDEX IF NOT EXISTS
    uq_prelaunch_photo_profile_checksum
ON prelaunch_photos (
    prelaunch_profile_id,
    checksum_sha256
)
WHERE deleted_at IS NULL;


-- ============================================================
-- 6. UPDATED_AT TRIGGER FUNCTION
-- Reuse an existing project trigger function if already available.
-- ============================================================

CREATE OR REPLACE FUNCTION set_current_updated_at()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$;


DROP TRIGGER IF EXISTS
    trg_prelaunch_profiles_updated_at
ON prelaunch_profiles;


CREATE TRIGGER trg_prelaunch_profiles_updated_at
BEFORE UPDATE
ON prelaunch_profiles
FOR EACH ROW
EXECUTE FUNCTION set_current_updated_at();


DROP TRIGGER IF EXISTS
    trg_prelaunch_photos_updated_at
ON prelaunch_photos;


CREATE TRIGGER trg_prelaunch_photos_updated_at
BEFORE UPDATE
ON prelaunch_photos
FOR EACH ROW
EXECUTE FUNCTION set_current_updated_at();


COMMIT;