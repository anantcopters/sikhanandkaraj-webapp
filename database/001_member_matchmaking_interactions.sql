BEGIN;

-- ============================================================
-- MEMBER -> MEMBER BLOCKS
-- ============================================================

CREATE TABLE member_blocks (
    id BIGSERIAL PRIMARY KEY,
    blocker_user_id INTEGER NOT NULL,
    blocked_user_id INTEGER NOT NULL,
    comment VARCHAR(250) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_member_blocks_blocker FOREIGN KEY (blocker_user_id) REFERENCES users (id) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT fk_member_blocks_blocked FOREIGN KEY (blocked_user_id) REFERENCES users (id) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT chk_member_blocks_different_users CHECK (
        blocker_user_id <> blocked_user_id
    ),
    CONSTRAINT uq_member_blocks_pair UNIQUE (
        blocker_user_id,
        blocked_user_id
    ),
    CONSTRAINT chk_member_blocks_comment CHECK (
        char_length(btrim(comment)) BETWEEN 1 AND 250
    )
);

CREATE INDEX idx_member_blocks_blocked_user ON member_blocks (blocked_user_id);

CREATE INDEX idx_member_blocks_blocker_user ON member_blocks (blocker_user_id);

-- ============================================================
-- MEMBER INTERESTS
-- ============================================================

CREATE TABLE member_interests (
    id BIGSERIAL PRIMARY KEY,
    from_user_id INTEGER NOT NULL,
    to_user_id INTEGER NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_member_interests_from_user FOREIGN KEY (from_user_id) REFERENCES users (id) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT fk_member_interests_to_user FOREIGN KEY (to_user_id) REFERENCES users (id) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT chk_member_interests_different_users CHECK (from_user_id <> to_user_id),
    CONSTRAINT uq_member_interests_pair UNIQUE (from_user_id, to_user_id)
);

CREATE INDEX idx_member_interests_to_user_created ON member_interests (to_user_id, created_at DESC);

CREATE INDEX idx_member_interests_from_user_created ON member_interests (from_user_id, created_at DESC);

-- ============================================================
-- MEMBER PROFILE VIEWS
-- ============================================================

CREATE TABLE member_profile_views (
    id BIGSERIAL PRIMARY KEY,
    viewer_user_id INTEGER NOT NULL,
    viewed_user_id INTEGER NOT NULL,
    view_count INTEGER NOT NULL DEFAULT 1,
    first_viewed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_viewed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_member_profile_views_viewer FOREIGN KEY (viewer_user_id) REFERENCES users (id) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT fk_member_profile_views_viewed FOREIGN KEY (viewed_user_id) REFERENCES users (id) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT chk_member_profile_views_different_users CHECK (
        viewer_user_id <> viewed_user_id
    ),
    CONSTRAINT chk_member_profile_views_count CHECK (view_count > 0),
    CONSTRAINT uq_member_profile_views_pair UNIQUE (
        viewer_user_id,
        viewed_user_id
    )
);

CREATE INDEX idx_member_profile_views_viewed_last ON member_profile_views (
    viewed_user_id,
    last_viewed_at DESC
);

CREATE INDEX idx_member_profile_views_viewer_last ON member_profile_views (
    viewer_user_id,
    last_viewed_at DESC
);

COMMIT;