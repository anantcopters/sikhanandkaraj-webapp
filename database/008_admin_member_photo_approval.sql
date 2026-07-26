BEGIN;

-- Speeds up retrieval of pending, non-deleted member photos.
CREATE INDEX IF NOT EXISTS idx_member_photos_pending_member
    ON member_photos (
        member_id,
        created_at DESC
    )
    WHERE status = 'PENDING'
      AND deleted_at IS NULL;

-- Speeds up individual approval and rejection operations.
CREATE INDEX IF NOT EXISTS idx_member_photos_pending_id
    ON member_photos (id)
    WHERE status = 'PENDING'
      AND deleted_at IS NULL;

-- Supports lookup of active member photos inside the carousel.
CREATE INDEX IF NOT EXISTS idx_member_photos_member_active
    ON member_photos (
        member_id,
        is_primary DESC,
        created_at DESC
    )
    WHERE deleted_at IS NULL
      AND status <> 'DELETED';

-- Search by profile reference number.
CREATE INDEX IF NOT EXISTS idx_users_profile_ref_number_search
    ON users (profile_ref_number)
    WHERE deleted_at IS NULL;

-- Case-insensitive member-name search.
CREATE INDEX IF NOT EXISTS idx_users_full_name_lower_search
    ON users (LOWER(full_name))
    WHERE deleted_at IS NULL;

COMMIT;