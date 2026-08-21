BEGIN;

CREATE TABLE member_video_introductions (
    id BIGSERIAL PRIMARY KEY,
    public_id UUID NOT NULL UNIQUE,
    member_user_id BIGINT NOT NULL,
    version_number INTEGER NOT NULL,
    moderation_status VARCHAR(30) NOT NULL DEFAULT 'PROCESSING',
    visibility VARCHAR(40) NOT NULL,
    consent_version VARCHAR(20) NOT NULL,
    consented_at TIMESTAMPTZ NOT NULL,
    original_object_key VARCHAR(500) NOT NULL,
    playback_object_key VARCHAR(500) NULL,
    poster_object_key VARCHAR(500) NULL,
    source_mime_type VARCHAR(100) NOT NULL,
    source_size_bytes BIGINT NOT NULL,
    duration_seconds NUMERIC(6,3) NULL,
    video_codec VARCHAR(50) NULL,
    audio_codec VARCHAR(50) NULL,
    width INTEGER NULL,
    height INTEGER NULL,
    processing_attempts INTEGER NOT NULL DEFAULT 0,
    processing_error VARCHAR(500) NULL,
    processing_started_at TIMESTAMPTZ NULL,
    processed_at TIMESTAMPTZ NULL,
    submitted_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    locked_until TIMESTAMPTZ NOT NULL,
    approved_at TIMESTAMPTZ NULL,
    approved_by_admin_id BIGINT NULL,
    rejection_reason VARCHAR(500) NULL,
    moderated_at TIMESTAMPTZ NULL,
    moderated_by_admin_id BIGINT NULL,
    is_active BOOLEAN NOT NULL DEFAULT FALSE,
    hidden_at TIMESTAMPTZ NULL,
    deleted_at TIMESTAMPTZ NULL,
    assets_purged_at TIMESTAMPTZ NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_member_video_member
        FOREIGN KEY (member_user_id)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_member_video_approver
        FOREIGN KEY (approved_by_admin_id)
        REFERENCES admin_users(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_member_video_moderator
        FOREIGN KEY (moderated_by_admin_id)
        REFERENCES admin_users(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT uq_member_video_version
        UNIQUE (member_user_id, version_number),

    CONSTRAINT chk_member_video_status
        CHECK (
            moderation_status IN (
                'PROCESSING',
                'PROCESSING_FAILED',
                'PENDING_REVIEW',
                'APPROVED',
                'REJECTED',
                'RESUBMISSION_REQUESTED',
                'REPLACED',
                'DELETED'
            )
        ),

    CONSTRAINT chk_member_video_visibility
        CHECK (
            visibility IN (
                'VISIBLE_PRO',
                'VISIBLE_AFTER_ACCEPTED_INTEREST',
                'HIDDEN'
            )
        ),

    CONSTRAINT chk_member_video_source_size
        CHECK (source_size_bytes > 0),

    CONSTRAINT chk_member_video_version
        CHECK (version_number > 0),

    CONSTRAINT chk_member_video_processing_attempts
        CHECK (processing_attempts >= 0),

    CONSTRAINT chk_member_video_duration
        CHECK (
            duration_seconds IS NULL
            OR duration_seconds BETWEEN 15 AND 30.5
        ),

    CONSTRAINT chk_member_video_dimensions
        CHECK (
            (width IS NULL AND height IS NULL)
            OR (width > 0 AND height > 0)
        ),

    CONSTRAINT chk_member_video_active
        CHECK (
            is_active = FALSE
            OR (
                moderation_status = 'APPROVED'
                AND playback_object_key IS NOT NULL
                AND poster_object_key IS NOT NULL
                AND deleted_at IS NULL
            )
        )
);

CREATE UNIQUE INDEX uq_member_video_active
    ON member_video_introductions(member_user_id)
    WHERE is_active = TRUE
      AND deleted_at IS NULL;

CREATE UNIQUE INDEX uq_member_video_open_submission
    ON member_video_introductions(member_user_id)
    WHERE moderation_status IN (
        'PROCESSING',
        'PENDING_REVIEW'
    );

CREATE INDEX idx_member_video_moderation_queue
    ON member_video_introductions(
        moderation_status,
        submitted_at,
        id
    )
    WHERE moderation_status = 'PENDING_REVIEW';

CREATE INDEX idx_member_video_member_history
    ON member_video_introductions(
        member_user_id,
        version_number DESC
    );

CREATE INDEX idx_member_video_asset_retention
    ON member_video_introductions(
        moderation_status,
        deleted_at,
        moderated_at
    )
    WHERE assets_purged_at IS NULL;

CREATE TABLE member_video_processing_jobs (
    id BIGSERIAL PRIMARY KEY,
    video_introduction_id BIGINT NOT NULL UNIQUE,
    status VARCHAR(20) NOT NULL DEFAULT 'PENDING',
    attempt_count INTEGER NOT NULL DEFAULT 0,
    available_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    locked_at TIMESTAMPTZ NULL,
    locked_by VARCHAR(100) NULL,
    last_error VARCHAR(500) NULL,
    completed_at TIMESTAMPTZ NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_member_video_job_video
        FOREIGN KEY (video_introduction_id)
        REFERENCES member_video_introductions(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT chk_member_video_job_status
        CHECK (
            status IN (
                'PENDING',
                'PROCESSING',
                'COMPLETED',
                'FAILED'
            )
        ),

    CONSTRAINT chk_member_video_job_attempt
        CHECK (attempt_count >= 0)
);

CREATE INDEX idx_member_video_jobs_ready
    ON member_video_processing_jobs(
        status,
        available_at,
        id
    )
    WHERE status IN (
        'PENDING',
        'FAILED'
    );

CREATE INDEX idx_member_video_jobs_stale
    ON member_video_processing_jobs(
        locked_at,
        id
    )
    WHERE status = 'PROCESSING';

CREATE TABLE member_video_moderation_history (
    id BIGSERIAL PRIMARY KEY,
    video_introduction_id BIGINT NOT NULL,
    admin_user_id BIGINT NOT NULL,
    from_status VARCHAR(30) NOT NULL,
    to_status VARCHAR(30) NOT NULL,
    reason VARCHAR(500) NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_member_video_history_video
        FOREIGN KEY (video_introduction_id)
        REFERENCES member_video_introductions(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_member_video_history_admin
        FOREIGN KEY (admin_user_id)
        REFERENCES admin_users(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT
);

CREATE INDEX idx_member_video_history_video
    ON member_video_moderation_history(
        video_introduction_id,
        created_at DESC,
        id DESC
    );

COMMENT ON TABLE member_video_introductions IS
    'Versioned, privately stored and moderated member Video Introductions.';

COMMENT ON COLUMN member_video_introductions.public_id IS
    'Non-sequential identifier safe for browser routes.';

COMMENT ON COLUMN member_video_introductions.visibility IS
    'VISIBLE_PRO currently maps to users.is_paid = TRUE until plan entitlements exist.';

COMMIT;