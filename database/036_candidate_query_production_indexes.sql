BEGIN;

-- ============================================================================
-- Membership-32 / Production Candidate Query Indexes
-- ============================================================================
--
-- Purpose:
--
-- Add only indexes justified by the authoritative candidate SQL and its
-- PostgreSQL EXPLAIN (ANALYZE, BUFFERS) profile.
--
-- IMPORTANT:
--
-- Do not duplicate the broad Search indexes from:
--
--     004_member_search.sql
--
-- or the card/presentation indexes from:
--
--     035_search_optimization.sql
--
-- Development databases are intentionally small and PostgreSQL may continue
-- choosing sequential scans there. That is expected and is not evidence that
-- these indexes are unused at production scale.
-- ============================================================================


-- ----------------------------------------------------------------------------
-- 1. Approved-photo count used by candidate discovery
-- ----------------------------------------------------------------------------
--
-- MemberMatchCandidateModel::baseCandidateBuilder() calculates:
--
--     COUNT(*) GROUP BY member_id
--
-- for photos satisfying:
--
--     status = 'APPROVED'
--     deleted_at IS NULL
--
-- Candidate ranking then consumes approved_photo_count as a scoring signal.
--
-- The existing photo indexes serve different purposes:
--
--     idx_member_photo_search_visibility
--         -> approved PRIMARY-photo visibility Search
--
--     idx_member_photos_primary_card_lookup
--         -> approved PRIMARY-photo card presentation
--
-- Neither index can efficiently represent the approved-photo-count aggregate
-- because that aggregate intentionally includes all approved gallery photos,
-- not only the primary photo.
--
-- Keep the index narrow. member_id is the only key needed by GROUP BY and the
-- filtering predicates are represented by the partial-index condition.
--
CREATE INDEX IF NOT EXISTS
    idx_member_photos_approved_member
ON member_photos (
    member_id
)
WHERE
    status = 'APPROVED'
    AND deleted_at IS NULL;


COMMIT;