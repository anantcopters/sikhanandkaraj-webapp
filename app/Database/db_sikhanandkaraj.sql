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

CREATE TABLE email_verification_tokens
(
    id                  BIGSERIAL PRIMARY KEY,

    user_id             BIGINT NOT NULL,
    user_contact_id     BIGINT NOT NULL,

    token_hash          CHAR(64) NOT NULL,

    expires_at          TIMESTAMP NOT NULL,
    used_at             TIMESTAMP NULL,

    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_email_verification_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_email_verification_contact
        FOREIGN KEY (user_contact_id)
        REFERENCES user_contacts(id)
        ON DELETE CASCADE
);

CREATE UNIQUE INDEX uq_email_verification_token_hash
ON email_verification_tokens(token_hash);

CREATE INDEX idx_email_verification_user
ON email_verification_tokens(user_id);

CREATE INDEX idx_email_verification_contact
ON email_verification_tokens(user_contact_id);

CREATE INDEX idx_email_verification_expiry
ON email_verification_tokens(expires_at);

CREATE TABLE email_queue
(
    id                  BIGSERIAL PRIMARY KEY,

    queue_name          VARCHAR(50) NOT NULL DEFAULT 'default',

    recipient_email     VARCHAR(254) NOT NULL,
    recipient_name      VARCHAR(150) NULL,

    subject             VARCHAR(255) NOT NULL,
    view_name           VARCHAR(255) NOT NULL,
    view_data           JSONB NOT NULL DEFAULT '{}'::jsonb,

    status              VARCHAR(20) NOT NULL DEFAULT 'PENDING',
    priority            SMALLINT NOT NULL DEFAULT 100,

    attempts            SMALLINT NOT NULL DEFAULT 0,
    max_attempts        SMALLINT NOT NULL DEFAULT 3,

    available_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    locked_at           TIMESTAMP NULL,
    locked_by           VARCHAR(100) NULL,

    sent_at             TIMESTAMP NULL,
    failed_at           TIMESTAMP NULL,

    last_error          TEXT NULL,

    reference_type      VARCHAR(100) NULL,
    reference_id        BIGINT NULL,

    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT chk_email_queue_status
        CHECK (
            status IN (
                'PENDING',
                'PROCESSING',
                'SENT',
                'FAILED'
            )
        ),

    CONSTRAINT chk_email_queue_attempts
        CHECK (
            attempts >= 0
            AND max_attempts > 0
        )
);

CREATE INDEX idx_email_queue_ready
ON email_queue (
    priority ASC,
    available_at ASC,
    id ASC
)
WHERE status = 'PENDING';

CREATE INDEX idx_email_queue_stale
ON email_queue (locked_at)
WHERE status = 'PROCESSING';

CREATE INDEX idx_email_queue_reference
ON email_queue (reference_type, reference_id);


CREATE TABLE email_queue_attempts
(
    id                  BIGSERIAL PRIMARY KEY,

    email_queue_id      BIGINT NOT NULL,
    attempt_number      SMALLINT NOT NULL,

    status              VARCHAR(20) NOT NULL,
    started_at          TIMESTAMP NOT NULL,
    completed_at        TIMESTAMP NULL,

    duration_ms         INTEGER NULL,
    error_message       TEXT NULL,
    smtp_debug          TEXT NULL,

    worker_name         VARCHAR(100) NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_email_queue_attempt
        FOREIGN KEY (email_queue_id)
        REFERENCES email_queue(id)
        ON DELETE CASCADE,

    CONSTRAINT chk_email_attempt_status
        CHECK (
            status IN (
                'STARTED',
                'SENT',
                'RETRY',
                'FAILED'
            )
        )
);

CREATE INDEX idx_email_attempt_queue
ON email_queue_attempts (
    email_queue_id,
    attempt_number
);

CREATE TABLE admin_users
(
    id                  BIGSERIAL PRIMARY KEY,

    full_name           VARCHAR(150) NOT NULL,
    mobile_number       VARCHAR(15) NOT NULL,
    email_address       VARCHAR(254) NOT NULL,

    password_hash       VARCHAR(255) NULL,

    role                VARCHAR(20) NOT NULL DEFAULT 'ADMIN',
    account_status      VARCHAR(20) NOT NULL DEFAULT 'PENDING',

    is_mobile_verified  BOOLEAN NOT NULL DEFAULT TRUE,
    mobile_verified_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    is_email_verified   BOOLEAN NOT NULL DEFAULT FALSE,
    email_verified_at   TIMESTAMP NULL,

    password_set_at     TIMESTAMP NULL,
    last_login_at       TIMESTAMP NULL,

    created_by          BIGINT NULL,

    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at          TIMESTAMP NULL,

    CONSTRAINT uq_admin_users_mobile
        UNIQUE (mobile_number),

    CONSTRAINT uq_admin_users_email
        UNIQUE (email_address),

    CONSTRAINT chk_admin_role
        CHECK (
            role IN ('SUPER_ADMIN', 'ADMIN')
        ),

    CONSTRAINT chk_admin_status
        CHECK (
            account_status IN (
                'PENDING',
                'VERIFIED',
                'SUSPENDED'
            )
        ),

    CONSTRAINT fk_admin_created_by
        FOREIGN KEY (created_by)
        REFERENCES admin_users(id)
        ON DELETE SET NULL
);

CREATE INDEX idx_admin_users_status
ON admin_users(account_status)
WHERE deleted_at IS NULL;

CREATE INDEX idx_admin_users_role
ON admin_users(role)
WHERE deleted_at IS NULL;


CREATE TABLE admin_invitations
(
    id                  BIGSERIAL PRIMARY KEY,

    admin_user_id       BIGINT NOT NULL,
    token_hash          CHAR(64) NOT NULL,

    expires_at          TIMESTAMP NOT NULL,
    used_at             TIMESTAMP NULL,
    revoked_at          TIMESTAMP NULL,

    created_by          BIGINT NOT NULL,

    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_admin_invitation_token
        UNIQUE (token_hash),

    CONSTRAINT fk_admin_invitation_user
        FOREIGN KEY (admin_user_id)
        REFERENCES admin_users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_admin_invitation_creator
        FOREIGN KEY (created_by)
        REFERENCES admin_users(id)
        ON DELETE RESTRICT
);

CREATE INDEX idx_admin_invitation_user
ON admin_invitations(admin_user_id);

CREATE INDEX idx_admin_invitation_usable
ON admin_invitations(token_hash, expires_at)
WHERE used_at IS NULL
  AND revoked_at IS NULL;

INSERT INTO admin_users
(
    full_name,
    mobile_number,
    email_address,
    password_hash,
    role,
    account_status,
    is_mobile_verified,
    mobile_verified_at,
    is_email_verified,
    email_verified_at,
    password_set_at
)
VALUES
(
    'Anant Prakash Singh',
    '+918550915559',
    'anantsinghkota@gmail.com',
    '$2y$10$j4NbDmW.KD8/PLEj7VTfi.97R./.zfv1KAFqKopBxobPBEpnph1f6',
    'SUPER_ADMIN',
    'VERIFIED',
    TRUE,
    CURRENT_TIMESTAMP,
    TRUE,
    CURRENT_TIMESTAMP,
    CURRENT_TIMESTAMP
);

CREATE TABLE admin_audit_logs
(
    id                  BIGSERIAL PRIMARY KEY,

    occurred_at         TIMESTAMPTZ NOT NULL
                            DEFAULT CURRENT_TIMESTAMP,

    actor_admin_id      BIGINT NULL,
    actor_name          VARCHAR(150) NULL,
    actor_role          VARCHAR(30) NULL,

    action              VARCHAR(100) NOT NULL,

    target_type         VARCHAR(100) NULL,
    target_id           BIGINT NULL,
    target_label        VARCHAR(254) NULL,

    outcome             VARCHAR(20) NOT NULL DEFAULT 'SUCCESS',

    description         TEXT NULL,

    before_data         JSONB NULL,
    after_data          JSONB NULL,
    metadata            JSONB NULL,

    request_id          VARCHAR(100) NULL,
    route_name          VARCHAR(150) NULL,

    ip_address          VARCHAR(45) NULL,
    user_agent          VARCHAR(1000) NULL,

    CONSTRAINT fk_admin_audit_actor
        FOREIGN KEY (actor_admin_id)
        REFERENCES admin_users(id)
        ON DELETE SET NULL,

    CONSTRAINT chk_admin_audit_outcome
        CHECK (
            outcome IN (
                'SUCCESS',
                'FAILURE',
                'DENIED'
            )
        )
);

CREATE INDEX idx_admin_audit_occurred
ON admin_audit_logs(occurred_at DESC);

CREATE INDEX idx_admin_audit_actor
ON admin_audit_logs(
    actor_admin_id,
    occurred_at DESC
);

CREATE INDEX idx_admin_audit_action
ON admin_audit_logs(
    action,
    occurred_at DESC
);

CREATE INDEX idx_admin_audit_target
ON admin_audit_logs(
    target_type,
    target_id,
    occurred_at DESC
);

CREATE INDEX idx_admin_audit_outcome
ON admin_audit_logs(
    outcome,
    occurred_at DESC
);

COMMIT;
