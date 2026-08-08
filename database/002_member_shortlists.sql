BEGIN;

-- ============================================================
-- MEMBER SHORTLISTS
-- ============================================================
--
-- A shortlist is directional:
--
-- user_id -> shortlisted_user_id
--
-- Example:
-- Member A may shortlist Member B without Member B
-- automatically shortlisting Member A.
-- ============================================================

CREATE TABLE member_shortlists (
    id BIGSERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    shortlisted_user_id INTEGER NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_member_shortlists_user FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT fk_member_shortlists_shortlisted_user FOREIGN KEY (shortlisted_user_id) REFERENCES users (id) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT chk_member_shortlists_different_users CHECK (
        user_id <> shortlisted_user_id
    ),
    CONSTRAINT uq_member_shortlists_pair UNIQUE (user_id, shortlisted_user_id)
);

CREATE INDEX idx_member_shortlists_user_created ON member_shortlists (user_id, created_at DESC);

CREATE INDEX idx_member_shortlists_shortlisted_user ON member_shortlists (shortlisted_user_id);

COMMIT;