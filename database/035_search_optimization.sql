BEGIN;

-- ============================================================================
-- Membership-23 / Phase 6 Search Optimization
-- ============================================================================
--
-- These indexes support the actual collection queries introduced by
-- Membership-23 and the existing candidate projection.
--
-- Do not duplicate the general Search indexes already created by:
--
--     004_member_search.sql
--
-- ============================================================================


-- ----------------------------------------------------------------------------
-- 1. Batch Interest relationship lookup
-- ----------------------------------------------------------------------------
--
-- Supports:
--
--     viewer -> candidate collection
--
-- used by:
--
--     MemberInterestModel::findRelationshipsForViewer()
--
CREATE INDEX IF NOT EXISTS
    idx_member_interests_from_to
ON member_interests (
    from_user_id,
    to_user_id
);


-- ----------------------------------------------------------------------------
-- 2. Reverse-direction batch Interest lookup
-- ----------------------------------------------------------------------------
--
-- Supports:
--
--     candidate collection -> viewer
--
CREATE INDEX IF NOT EXISTS
    idx_member_interests_to_from
ON member_interests (
    to_user_id,
    from_user_id
);


-- ----------------------------------------------------------------------------
-- 3. Batch Shortlist state
-- ----------------------------------------------------------------------------
--
-- The table normally already has uniqueness around this relationship.
-- This index is intentionally created only when an equivalent index does not
-- already exist in the target environment.
--
-- PostgreSQL IF NOT EXISTS prevents deployment failure if the project already
-- created this exact named index.
--
CREATE INDEX IF NOT EXISTS
    idx_member_shortlists_user_target
ON member_shortlists (
    user_id,
    shortlisted_user_id
);


-- ----------------------------------------------------------------------------
-- 4. Candidate active membership projection
-- ----------------------------------------------------------------------------
--
-- MemberMatchCandidateModel resolves the latest currently usable membership
-- using:
--
--     user_id
--     status = ACTIVE
--     starts_at <= CURRENT_TIMESTAMP
--     expires_at > CURRENT_TIMESTAMP
--     ORDER BY starts_at DESC, id DESC
--
-- Do not place CURRENT_TIMESTAMP in a partial-index predicate because that is
-- not an immutable PostgreSQL expression.
--
CREATE INDEX IF NOT EXISTS
    idx_member_memberships_candidate_active_lookup
ON member_memberships (
    user_id,
    status,
    starts_at DESC,
    expires_at,
    id DESC
);


-- ----------------------------------------------------------------------------
-- 5. Approved primary photo collection lookup
-- ----------------------------------------------------------------------------
--
-- Existing 004_member_search.sql has:
--
--     member_id, visibility
--
-- for approved primary photos.
--
-- This additional covering index supports the Membership-23 card-photo batch
-- projection without indexing private original media keys.
--
CREATE INDEX IF NOT EXISTS
    idx_member_photos_primary_card_lookup
ON member_photos (
    member_id,
    created_at DESC,
    id DESC
)
INCLUDE (
    visibility,
    thumbnail_object_key
)
WHERE
    status = 'APPROVED'
    AND is_primary = TRUE
    AND deleted_at IS NULL;


-- ----------------------------------------------------------------------------
-- 6. Administrator-confirmed report exclusion
-- ----------------------------------------------------------------------------
--
-- Existing candidate discovery executes:
--
--     NOT EXISTS (
--         SELECT 1
--         FROM member_profile_reports
--         WHERE reported_user_id = candidate
--           AND status = ACTION_TAKEN
--     )
--
-- Keep status in the key rather than hard-coding the application constant in
-- a partial-index predicate.
--
CREATE INDEX IF NOT EXISTS
    idx_member_profile_reports_candidate_status
ON member_profile_reports (
    reported_user_id,
    status
);


COMMIT;