BEGIN;

-- -------------------------------------------------------------------------
-- FIELD OFFICER LOGIN METADATA
-- -------------------------------------------------------------------------

ALTER TABLE field_officers
ADD COLUMN IF NOT EXISTS last_login_at TIMESTAMPTZ NULL;


-- -------------------------------------------------------------------------
-- FIELD OFFICER LOGIN OTP
-- -------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS field_officer_login_otps
(
    id BIGSERIAL PRIMARY KEY,

    field_officer_id BIGINT NOT NULL,

    /*
     * Snapshot of the number to which this OTP was delivered.
     *
     * The authoritative current mobile remains field_officers.mobile_number.
     */
    mobile_number VARCHAR(16) NOT NULL,

    otp_hash VARCHAR(255) NOT NULL,

    expires_at TIMESTAMPTZ NOT NULL,

    attempt_count SMALLINT NOT NULL DEFAULT 0,

    status VARCHAR(20) NOT NULL DEFAULT 'PENDING',

    verified_at TIMESTAMPTZ NULL,

    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_field_officer_login_otps_field_officer
        FOREIGN KEY (field_officer_id)
        REFERENCES field_officers(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT chk_field_officer_login_otps_attempt_count
        CHECK (attempt_count >= 0),

    CONSTRAINT chk_field_officer_login_otps_status
        CHECK (
            status IN (
                'PENDING',
                'VERIFIED',
                'EXPIRED',
                'CANCELLED',
                'DELIVERY_FAILED'
            )
        )
);

CREATE INDEX IF NOT EXISTS
    idx_field_officer_login_otps_officer
ON field_officer_login_otps (
    field_officer_id,
    created_at DESC
);

CREATE INDEX IF NOT EXISTS
    idx_field_officer_login_otps_pending
ON field_officer_login_otps (
    field_officer_id,
    expires_at
)
WHERE status = 'PENDING';


-- -------------------------------------------------------------------------
-- FIELD OFFICER SUBMITTED PROFILE READ VIEW
-- -------------------------------------------------------------------------
--
-- PRELAUNCH:
--      migrated_user_id IS NULL  -> DRAFT
--      migrated_user_id IS SET   -> APPROVED
--
-- MEMBER:
--      explicit FO assignment in member_family_details -> APPROVED
--
-- Migrated prelaunch members are deliberately excluded from the MEMBER
-- branch to prevent duplicate rows.
-- -------------------------------------------------------------------------

CREATE OR REPLACE VIEW vw_field_officer_submitted_profiles
AS

SELECT
    'PRELAUNCH:' || p.id::TEXT AS row_key,

    'PRELAUNCH'::VARCHAR AS source_type,

    p.id AS source_id,

    p.migrated_user_id AS member_user_id,

    p.field_officer_id,

    COALESCE(
        migrated_user.profile_ref_number,
        p.profile_reference
    ) AS profile_reference,

    COALESCE(
        migrated_user.full_name,
        p.full_name
    ) AS full_name,

    p.mobile_number,

    city.name AS city_name,

    state.name AS state_name,

    CASE
        WHEN p.migrated_user_id IS NULL
            THEN 'DRAFT'
        ELSE 'APPROVED'
    END::VARCHAR AS display_status,

    p.created_at AS submitted_at

FROM prelaunch_profiles p

LEFT JOIN users migrated_user
    ON migrated_user.id = p.migrated_user_id
   AND migrated_user.deleted_at IS NULL

LEFT JOIN master_states state
    ON state.id = p.state_id

LEFT JOIN master_cities city
    ON city.id = p.city_id

WHERE p.deleted_at IS NULL
  AND p.field_officer_id IS NOT NULL


UNION ALL


SELECT
    'MEMBER:' || u.id::TEXT AS row_key,

    'MEMBER'::VARCHAR AS source_type,

    u.id AS source_id,

    u.id AS member_user_id,

    family.field_officer_id,

    u.profile_ref_number AS profile_reference,

    u.full_name,

    mobile.mobile_number,

    city.name AS city_name,

    state.name AS state_name,

    'APPROVED'::VARCHAR AS display_status,

    family.created_at AS submitted_at

FROM users u

INNER JOIN member_family_details family
    ON family.user_id = u.id
   AND family.field_officer_id IS NOT NULL

LEFT JOIN member_basic_details basic
    ON basic.user_id = u.id

LEFT JOIN master_states state
    ON state.id = basic.state_id

LEFT JOIN master_cities city
    ON city.id = basic.city_id

LEFT JOIN LATERAL
(
    SELECT
        REGEXP_REPLACE(
            contact.normalized_value,
            '^\+91',
            ''
        ) AS mobile_number
    FROM user_contacts contact
    WHERE contact.user_id = u.id
      AND contact.contact_type = 'MOBILE'
      AND contact.is_primary = TRUE
    ORDER BY contact.id ASC
    LIMIT 1
) mobile ON TRUE

WHERE u.deleted_at IS NULL

/*
 * A migrated prelaunch profile is already represented in
 * the PRELAUNCH branch above.
 */
AND u.prelaunch_profile_id IS NULL;

COMMIT;