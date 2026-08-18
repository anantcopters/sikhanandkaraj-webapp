BEGIN;


CREATE TABLE member_aadhaar_submissions (
    id BIGSERIAL PRIMARY KEY,

    upload_reference CHAR(32) NOT NULL,

    member_id BIGINT NOT NULL,

    object_key VARCHAR(1024) NOT NULL,

    mime_type VARCHAR(50) NOT NULL,

    file_extension VARCHAR(5) NOT NULL,

    file_size_bytes INTEGER NOT NULL,

    checksum_sha256 CHAR(64) NOT NULL,

    status VARCHAR(20) NOT NULL DEFAULT 'UNDER_REVIEW',

    /*
    * These are identity-verification values.
    *
    * They are completely separate from:
    *
    * users.full_name
    * member_basic_details.date_of_birth
    */
    aadhaar_name VARCHAR(100) NULL,
    aadhaar_date_of_birth DATE NULL,
    rejection_reason VARCHAR(500) NULL,
    reviewed_by_admin_id BIGINT NULL,
    reviewed_at TIMESTAMPTZ NULL,
    uploaded_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_member_aadhaar_submission_member FOREIGN KEY (member_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_member_aadhaar_submission_reviewer FOREIGN KEY (reviewed_by_admin_id) REFERENCES admin_users (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT uq_member_aadhaar_upload_reference UNIQUE (upload_reference),
    CONSTRAINT uq_member_aadhaar_object_key UNIQUE (object_key),
    CONSTRAINT chk_member_aadhaar_upload_reference CHECK (
        upload_reference ~ '^[0-9a-f]{32}$'
),
CONSTRAINT chk_member_aadhaar_mime CHECK (
    mime_type IN (
        'image/jpeg',
        'image/png',
        'application/pdf'
    )
),
CONSTRAINT chk_member_aadhaar_extension CHECK (
    file_extension IN ('jpg', 'png', 'pdf')
),

/*
 * File must be strictly smaller than 1 MB.
 */
CONSTRAINT chk_member_aadhaar_size CHECK (
    file_size_bytes BETWEEN 1 AND 1048575
),
CONSTRAINT chk_member_aadhaar_checksum CHECK (
    checksum_sha256 ~ '^[0-9a-f]{64}$'
),
CONSTRAINT chk_member_aadhaar_status CHECK (
    status IN (
        'UNDER_REVIEW',
        'APPROVED',
        'REJECTED'
    )
),

/*
 * UNDER_REVIEW:
 *     No review or approved identity data.
 *
 * APPROVED:
 *     Reviewer, review time, Aadhaar name and Aadhaar DOB required.
 *
 * REJECTED:
 *     Reviewer, review time and rejection reason required.
 *     No approved Aadhaar name/DOB stored.
 */
CONSTRAINT chk_member_aadhaar_review_state
        CHECK (
            (
                status = 'UNDER_REVIEW'
                AND reviewed_by_admin_id IS NULL
                AND reviewed_at IS NULL
                AND aadhaar_name IS NULL
                AND aadhaar_date_of_birth IS NULL
                AND rejection_reason IS NULL
            )
            OR
            (
                status = 'APPROVED'
                AND reviewed_by_admin_id IS NOT NULL
                AND reviewed_at IS NOT NULL
                AND BTRIM(
                    COALESCE(
                        aadhaar_name,
                        ''
                    )
                ) <> ''
                AND aadhaar_date_of_birth IS NOT NULL
                AND rejection_reason IS NULL
            )
            OR
            (
                status = 'REJECTED'
                AND reviewed_by_admin_id IS NOT NULL
                AND reviewed_at IS NOT NULL
                AND aadhaar_name IS NULL
                AND aadhaar_date_of_birth IS NULL
                AND BTRIM(
                    COALESCE(
                        rejection_reason,
                        ''
                    )
                ) <> ''
            )
        )
);

CREATE UNIQUE INDEX uq_member_aadhaar_one_pending ON member_aadhaar_submissions (member_id)
WHERE
    status = 'UNDER_REVIEW';

CREATE UNIQUE INDEX uq_member_aadhaar_one_approved ON member_aadhaar_submissions (member_id)
WHERE
    status = 'APPROVED';

CREATE INDEX idx_member_aadhaar_pending_queue ON member_aadhaar_submissions (uploaded_at, id)
WHERE
    status = 'UNDER_REVIEW';

CREATE INDEX idx_member_aadhaar_history ON member_aadhaar_submissions (
    member_id,
    uploaded_at DESC,
    id DESC
);

COMMENT ON TABLE member_aadhaar_submissions IS 'Immutable member Aadhaar upload and administrator-review history.';

COMMENT ON COLUMN member_aadhaar_submissions.aadhaar_name IS 'Approved Aadhaar name, separate from the matrimonial profile name.';

COMMENT ON COLUMN member_aadhaar_submissions.aadhaar_date_of_birth IS 'Approved Aadhaar DOB, separate from Basic Details and matchmaking.';

COMMIT;