BEGIN;

ALTER TABLE users
ADD COLUMN IF NOT EXISTS last_login_at TIMESTAMP NULL;

CREATE INDEX IF NOT EXISTS idx_users_search_active_created ON users (
    account_status,
    created_at DESC,
    id DESC
)
WHERE
    deleted_at IS NULL;

CREATE INDEX IF NOT EXISTS idx_users_last_login ON users (last_login_at DESC, id DESC)
WHERE
    account_status = 'ACTIVE'
    AND deleted_at IS NULL;

CREATE INDEX IF NOT EXISTS idx_member_basic_search ON member_basic_details (
    state_id,
    city_id,
    marital_status_id,
    height_id,
    date_of_birth,
    user_id
);

CREATE INDEX IF NOT EXISTS idx_member_education_search ON member_education_profession_details (
    highest_education_id,
    occupation_id,
    employed_in,
    annual_income_id,
    user_id
);

CREATE INDEX IF NOT EXISTS idx_member_family_community_search ON member_family_details (community_id, user_id);

CREATE INDEX IF NOT EXISTS idx_member_lifestyle_search ON member_lifestyle_options (lifestyle_option_id, user_id);

CREATE INDEX IF NOT EXISTS idx_member_photo_search_visibility ON member_photos (member_id, visibility)
WHERE
    status = 'APPROVED'
    AND is_primary = TRUE
    AND deleted_at IS NULL;

COMMIT;