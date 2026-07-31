CREATE TABLE IF NOT EXISTS member_photos (
    id BIGSERIAL PRIMARY KEY,

    uuid UUID NOT NULL,

    member_id BIGINT NOT NULL,

    media_type VARCHAR(20) NOT NULL DEFAULT 'PROFILE_PHOTO',

    original_object_key VARCHAR(500) NOT NULL,
    medium_object_key VARCHAR(500) NOT NULL,
    thumbnail_object_key VARCHAR(500) NOT NULL,

    original_filename VARCHAR(255) NOT NULL,
    original_mime_type VARCHAR(100) NOT NULL,
    original_extension VARCHAR(10) NOT NULL,

    original_file_size BIGINT NOT NULL,
    original_width INTEGER NOT NULL,
    original_height INTEGER NOT NULL,

    status VARCHAR(20) NOT NULL DEFAULT 'PENDING',

    visibility VARCHAR(30) NOT NULL DEFAULT 'PUBLIC',

    is_primary BOOLEAN NOT NULL DEFAULT FALSE,

    uploaded_by_type VARCHAR(20) NOT NULL DEFAULT 'MEMBER',
    uploaded_by_id BIGINT NOT NULL,

    approved_by BIGINT NULL,
    approved_at TIMESTAMP NULL,

    rejected_by BIGINT NULL,
    rejected_at TIMESTAMP NULL,
    rejection_reason VARCHAR(500) NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    CONSTRAINT uq_member_photos_uuid
        UNIQUE (uuid),

    CONSTRAINT fk_member_photos_member
        FOREIGN KEY (member_id)
        REFERENCES users(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT chk_member_photos_media_type
        CHECK (
            media_type IN ('PROFILE_PHOTO')
        ),

    CONSTRAINT chk_member_photos_status
        CHECK (
            status IN (
                'PENDING',
                'APPROVED',
                'REJECTED',
                'DELETED'
            )
        ),

    CONSTRAINT chk_member_photos_visibility
        CHECK (
            visibility IN (
                'PUBLIC',
                'INTERESTED_MEMBERS'
            )
        ),

    CONSTRAINT chk_member_photos_uploaded_by_type
        CHECK (
            uploaded_by_type IN ('MEMBER', 'ADMIN')
        ),

    CONSTRAINT chk_member_photos_original_mime
        CHECK (
            original_mime_type IN (
                'image/jpeg',
                'image/png',
                'image/webp'
            )
        ),

    CONSTRAINT chk_member_photos_size
        CHECK (
            original_file_size > 0
            AND original_width > 0
            AND original_height > 0
        )
);

CREATE INDEX IF NOT EXISTS idx_member_photos_member_status
    ON member_photos (
        member_id,
        status,
        created_at DESC
    )
    WHERE deleted_at IS NULL;

CREATE INDEX IF NOT EXISTS idx_member_photos_member_visibility
    ON member_photos (
        member_id,
        visibility
    )
    WHERE deleted_at IS NULL;

CREATE UNIQUE INDEX IF NOT EXISTS uq_member_photos_one_primary
    ON member_photos (member_id)
    WHERE is_primary = TRUE
      AND deleted_at IS NULL;