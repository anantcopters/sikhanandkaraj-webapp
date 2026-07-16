BEGIN;


CREATE TABLE "ci_sessions" (
    "id" varchar(128) NOT NULL,
    "ip_address" inet NOT NULL,
    "timestamp" timestamptz DEFAULT CURRENT_TIMESTAMP NOT NULL,
    "data" bytea DEFAULT '' NOT NULL
);

CREATE INDEX "ci_sessions_timestamp" ON "ci_sessions" ("timestamp");

CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,
    profile_ref_number VARCHAR(32) NOT NULL,
    profile_created_for VARCHAR(20) NOT NULL,
    gender CHAR(1) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    account_status VARCHAR(20) NOT NULL DEFAULT 'PENDING',

    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMPTZ NULL,

    CONSTRAINT chk_users_profile_created_for
        CHECK (
            profile_created_for IN (
                'self',
                'son',
                'daughter',
                'brother',
                'sister'
            )
        ),

    CONSTRAINT chk_users_gender
        CHECK (gender IN ('M', 'F')),

    CONSTRAINT chk_users_account_status
        CHECK (
            account_status IN (
                'PENDING',
                'ACTIVE',
                'SUSPENDED',
                'DELETED'
            )
        ),
    CONSTRAINT uq_users_profile_ref_number
        UNIQUE (profile_ref_number),

    CONSTRAINT chk_users_profile_ref_number
        CHECK (
            profile_ref_number ~ '^SAK[0-9]{7}$'
        )
);

CREATE TABLE user_contacts (
    id BIGSERIAL PRIMARY KEY,

    user_id BIGINT NOT NULL
        REFERENCES users(id)
        ON DELETE CASCADE,

    contact_type VARCHAR(10) NOT NULL,

    contact_value VARCHAR(254) NOT NULL,
    normalized_value VARCHAR(254) NOT NULL,

    is_primary BOOLEAN NOT NULL DEFAULT TRUE,
    is_verified BOOLEAN NOT NULL DEFAULT FALSE,
    verified_at TIMESTAMPTZ NULL,

    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT chk_user_contacts_type
        CHECK (contact_type IN ('EMAIL', 'MOBILE')),

    CONSTRAINT chk_user_contacts_verification
        CHECK (
            (
                is_verified = FALSE
                AND verified_at IS NULL
            )
            OR
            (
                is_verified = TRUE
                AND verified_at IS NOT NULL
            )
        )
);

CREATE UNIQUE INDEX uq_user_contacts_mobile
ON user_contacts (normalized_value)
WHERE contact_type = 'MOBILE';

CREATE UNIQUE INDEX uq_user_primary_contact_type
ON user_contacts (user_id, contact_type)
WHERE is_primary = TRUE;

CREATE TABLE contact_verifications (
    id BIGSERIAL PRIMARY KEY,

    user_contact_id BIGINT NOT NULL
        REFERENCES user_contacts(id)
        ON DELETE CASCADE,

    purpose VARCHAR(30) NOT NULL DEFAULT 'REGISTER',

    otp_hash VARCHAR(255) NOT NULL,
    expires_at TIMESTAMPTZ NOT NULL,

    attempt_count SMALLINT NOT NULL DEFAULT 0,
    resend_count SMALLINT NOT NULL DEFAULT 0,

    status VARCHAR(20) NOT NULL DEFAULT 'PENDING',
    verified_at TIMESTAMPTZ NULL,

    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT chk_contact_verification_purpose
        CHECK (
            purpose IN (
                'REGISTER',
                'LOGIN',
                'CHANGE_MOBILE',
                'CHANGE_EMAIL'
            )
        ),

    CONSTRAINT chk_contact_verification_status
        CHECK (
            status IN (
                'PENDING',
                'VERIFIED',
                'EXPIRED',
                'CANCELLED'
            )
        )
);

CREATE INDEX idx_contact_verifications_pending
ON contact_verifications (
    user_contact_id,
    status,
    expires_at
);

CREATE INDEX IF NOT EXISTS idx_contact_verifications_contact_purpose_created
ON contact_verifications (
    user_contact_id,
    purpose,
    created_at DESC
);

CREATE INDEX IF NOT EXISTS idx_contact_verifications_pending_lookup
ON contact_verifications (
    user_contact_id,
    purpose,
    status,
    id DESC
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_one_pending_registration_otp
ON contact_verifications (
    user_contact_id,
    purpose
)
WHERE status = 'PENDING';

CREATE UNIQUE INDEX IF NOT EXISTS
    uq_user_contacts_email_normalized
ON user_contacts (normalized_value)
WHERE contact_type = 'EMAIL';

CREATE UNIQUE INDEX IF NOT EXISTS
    uq_user_contacts_mobile_normalized
ON user_contacts (normalized_value)
WHERE contact_type = 'MOBILE';

CREATE TABLE IF NOT EXISTS http_request_logs (
    id BIGSERIAL PRIMARY KEY,

    request_id UUID NOT NULL UNIQUE,

    occurred_at TIMESTAMPTZ NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    environment VARCHAR(20) NOT NULL,

    request_method VARCHAR(10) NOT NULL,
    request_uri TEXT NOT NULL,

    route_name VARCHAR(150),
    controller_action VARCHAR(255),

    response_status SMALLINT NOT NULL,
    duration_ms INTEGER NOT NULL DEFAULT 0,

    ip_address INET,

    user_id BIGINT,
    profile_reference VARCHAR(50),
    is_authenticated BOOLEAN NOT NULL DEFAULT FALSE,

    user_agent VARCHAR(1000),
    referer VARCHAR(2000),

    request_headers JSONB,
    request_payload JSONB,
    response_payload JSONB,

    request_size_bytes INTEGER,
    response_size_bytes INTEGER,

    severity VARCHAR(20) NOT NULL DEFAULT 'INFO',
    is_successful BOOLEAN NOT NULL DEFAULT TRUE,

    created_at TIMESTAMPTZ NOT NULL
        DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS
    idx_http_request_logs_occurred_at
    ON http_request_logs (occurred_at DESC);

CREATE INDEX IF NOT EXISTS
    idx_http_request_logs_user
    ON http_request_logs (
        user_id,
        occurred_at DESC
    );

CREATE INDEX IF NOT EXISTS
    idx_http_request_logs_status
    ON http_request_logs (
        response_status,
        occurred_at DESC
    );

CREATE INDEX IF NOT EXISTS
    idx_http_request_logs_failed
        ON http_request_logs (occurred_at DESC)
        WHERE response_status >= 400;



COMMIT;
