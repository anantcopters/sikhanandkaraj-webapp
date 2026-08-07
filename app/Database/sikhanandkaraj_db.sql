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
    CONSTRAINT chk_users_profile_created_for CHECK (
        profile_created_for IN (
            'self',
            'son',
            'daughter',
            'brother',
            'sister'
        )
    ),
    CONSTRAINT chk_users_gender CHECK (gender IN ('M', 'F')),
    CONSTRAINT chk_users_account_status CHECK (
        account_status IN (
            'PENDING',
            'ACTIVE',
            'SUSPENDED',
            'DELETED'
        )
    ),
    CONSTRAINT uq_users_profile_ref_number UNIQUE (profile_ref_number),
    CONSTRAINT chk_users_profile_ref_number CHECK (
        profile_ref_number ~ '^SAK[0-9]{7}$'
    )
);

CREATE TABLE user_contacts (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    contact_type VARCHAR(10) NOT NULL,
    contact_value VARCHAR(254) NOT NULL,
    normalized_value VARCHAR(254) NOT NULL,
    is_primary BOOLEAN NOT NULL DEFAULT TRUE,
    is_verified BOOLEAN NOT NULL DEFAULT FALSE,
    verified_at TIMESTAMPTZ NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_user_contacts_type CHECK (
        contact_type IN ('EMAIL', 'MOBILE')
    ),
    CONSTRAINT chk_user_contacts_verification CHECK (
        (
            is_verified = FALSE
            AND verified_at IS NULL
        )
        OR (
            is_verified = TRUE
            AND verified_at IS NOT NULL
        )
    )
);

CREATE UNIQUE INDEX uq_user_contacts_mobile ON user_contacts (normalized_value)
WHERE
    contact_type = 'MOBILE';

CREATE UNIQUE INDEX uq_user_primary_contact_type ON user_contacts (user_id, contact_type)
WHERE
    is_primary = TRUE;

CREATE TABLE contact_verifications (
    id BIGSERIAL PRIMARY KEY,
    user_contact_id BIGINT NOT NULL REFERENCES user_contacts (id) ON DELETE CASCADE,
    purpose VARCHAR(30) NOT NULL DEFAULT 'REGISTER',
    otp_hash VARCHAR(255) NOT NULL,
    expires_at TIMESTAMPTZ NOT NULL,
    attempt_count SMALLINT NOT NULL DEFAULT 0,
    resend_count SMALLINT NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'PENDING',
    verified_at TIMESTAMPTZ NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_contact_verification_purpose CHECK (
        purpose IN (
            'REGISTER',
            'LOGIN',
            'CHANGE_MOBILE',
            'CHANGE_EMAIL'
        )
    ),
    CONSTRAINT chk_contact_verification_status CHECK (
        status IN (
            'PENDING',
            'VERIFIED',
            'EXPIRED',
            'CANCELLED'
        )
    )
);

CREATE INDEX idx_contact_verifications_pending ON contact_verifications (
    user_contact_id,
    status,
    expires_at
);

CREATE INDEX IF NOT EXISTS idx_contact_verifications_contact_purpose_created ON contact_verifications (
    user_contact_id,
    purpose,
    created_at DESC
);

CREATE INDEX IF NOT EXISTS idx_contact_verifications_pending_lookup ON contact_verifications (
    user_contact_id,
    purpose,
    status,
    id DESC
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_one_pending_registration_otp ON contact_verifications (user_contact_id, purpose)
WHERE
    status = 'PENDING';

CREATE UNIQUE INDEX IF NOT EXISTS uq_user_contacts_email_normalized ON user_contacts (normalized_value)
WHERE
    contact_type = 'EMAIL';

CREATE UNIQUE INDEX IF NOT EXISTS uq_user_contacts_mobile_normalized ON user_contacts (normalized_value)
WHERE
    contact_type = 'MOBILE';

CREATE TABLE IF NOT EXISTS http_request_logs (
    id BIGSERIAL PRIMARY KEY,
    request_id UUID NOT NULL UNIQUE,
    occurred_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
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
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_http_request_logs_occurred_at ON http_request_logs (occurred_at DESC);

CREATE INDEX IF NOT EXISTS idx_http_request_logs_user ON http_request_logs (user_id, occurred_at DESC);

CREATE INDEX IF NOT EXISTS idx_http_request_logs_status ON http_request_logs (
    response_status,
    occurred_at DESC
);

CREATE INDEX IF NOT EXISTS idx_http_request_logs_failed ON http_request_logs (occurred_at DESC)
WHERE
    response_status >= 400;

CREATE TABLE email_verification_tokens (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    user_contact_id BIGINT NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_email_verification_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_email_verification_contact FOREIGN KEY (user_contact_id) REFERENCES user_contacts (id) ON DELETE CASCADE
);

CREATE UNIQUE INDEX uq_email_verification_token_hash ON email_verification_tokens (token_hash);

CREATE INDEX idx_email_verification_user ON email_verification_tokens (user_id);

CREATE INDEX idx_email_verification_contact ON email_verification_tokens (user_contact_id);

CREATE INDEX idx_email_verification_expiry ON email_verification_tokens (expires_at);

CREATE TABLE email_queue (
    id BIGSERIAL PRIMARY KEY,
    queue_name VARCHAR(50) NOT NULL DEFAULT 'default',
    recipient_email VARCHAR(254) NOT NULL,
    recipient_name VARCHAR(150) NULL,
    subject VARCHAR(255) NOT NULL,
    view_name VARCHAR(255) NOT NULL,
    view_data JSONB NOT NULL DEFAULT '{}'::jsonb,
    status VARCHAR(20) NOT NULL DEFAULT 'PENDING',
    priority SMALLINT NOT NULL DEFAULT 100,
    attempts SMALLINT NOT NULL DEFAULT 0,
    max_attempts SMALLINT NOT NULL DEFAULT 3,
    available_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    locked_at TIMESTAMP NULL,
    locked_by VARCHAR(100) NULL,
    sent_at TIMESTAMP NULL,
    failed_at TIMESTAMP NULL,
    last_error TEXT NULL,
    reference_type VARCHAR(100) NULL,
    reference_id BIGINT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_email_queue_status CHECK (
        status IN (
            'PENDING',
            'PROCESSING',
            'SENT',
            'FAILED'
        )
    ),
    CONSTRAINT chk_email_queue_attempts CHECK (
        attempts >= 0
        AND max_attempts > 0
    )
);

CREATE INDEX idx_email_queue_ready ON email_queue (
    priority ASC,
    available_at ASC,
    id ASC
)
WHERE
    status = 'PENDING';

CREATE INDEX idx_email_queue_stale ON email_queue (locked_at)
WHERE
    status = 'PROCESSING';

CREATE INDEX idx_email_queue_reference ON email_queue (reference_type, reference_id);

CREATE TABLE email_queue_attempts (
    id BIGSERIAL PRIMARY KEY,
    email_queue_id BIGINT NOT NULL,
    attempt_number SMALLINT NOT NULL,
    status VARCHAR(20) NOT NULL,
    started_at TIMESTAMP NOT NULL,
    completed_at TIMESTAMP NULL,
    duration_ms INTEGER NULL,
    error_message TEXT NULL,
    smtp_debug TEXT NULL,
    worker_name VARCHAR(100) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_email_queue_attempt FOREIGN KEY (email_queue_id) REFERENCES email_queue (id) ON DELETE CASCADE,
    CONSTRAINT chk_email_attempt_status CHECK (
        status IN (
            'STARTED',
            'SENT',
            'RETRY',
            'FAILED'
        )
    )
);

CREATE INDEX idx_email_attempt_queue ON email_queue_attempts (
    email_queue_id,
    attempt_number
);

CREATE TABLE admin_users (
    id BIGSERIAL PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    mobile_number VARCHAR(15) NOT NULL,
    email_address VARCHAR(254) NOT NULL,
    password_hash VARCHAR(255) NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'ADMIN',
    account_status VARCHAR(20) NOT NULL DEFAULT 'PENDING',
    is_mobile_verified BOOLEAN NOT NULL DEFAULT TRUE,
    mobile_verified_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_email_verified BOOLEAN NOT NULL DEFAULT FALSE,
    email_verified_at TIMESTAMP NULL,
    password_set_at TIMESTAMP NULL,
    last_login_at TIMESTAMP NULL,
    created_by BIGINT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT uq_admin_users_mobile UNIQUE (mobile_number),
    CONSTRAINT uq_admin_users_email UNIQUE (email_address),
    CONSTRAINT chk_admin_role CHECK (
        role IN ('SUPER_ADMIN', 'ADMIN')
    ),
    CONSTRAINT chk_admin_status CHECK (
        account_status IN (
            'PENDING',
            'VERIFIED',
            'SUSPENDED'
        )
    ),
    CONSTRAINT fk_admin_created_by FOREIGN KEY (created_by) REFERENCES admin_users (id) ON DELETE SET NULL
);

CREATE INDEX idx_admin_users_status ON admin_users (account_status)
WHERE
    deleted_at IS NULL;

CREATE INDEX idx_admin_users_role ON admin_users (role)
WHERE
    deleted_at IS NULL;

CREATE TABLE admin_invitations (
    id BIGSERIAL PRIMARY KEY,
    admin_user_id BIGINT NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    revoked_at TIMESTAMP NULL,
    created_by BIGINT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_admin_invitation_token UNIQUE (token_hash),
    CONSTRAINT fk_admin_invitation_user FOREIGN KEY (admin_user_id) REFERENCES admin_users (id) ON DELETE CASCADE,
    CONSTRAINT fk_admin_invitation_creator FOREIGN KEY (created_by) REFERENCES admin_users (id) ON DELETE RESTRICT
);

CREATE INDEX idx_admin_invitation_user ON admin_invitations (admin_user_id);

CREATE INDEX idx_admin_invitation_usable ON admin_invitations (token_hash, expires_at)
WHERE
    used_at IS NULL
    AND revoked_at IS NULL;

CREATE TABLE admin_audit_logs (
    id BIGSERIAL PRIMARY KEY,
    occurred_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actor_admin_id BIGINT NULL,
    actor_name VARCHAR(150) NULL,
    actor_role VARCHAR(30) NULL,
    action VARCHAR(100) NOT NULL,
    target_type VARCHAR(100) NULL,
    target_id BIGINT NULL,
    target_label VARCHAR(254) NULL,
    outcome VARCHAR(20) NOT NULL DEFAULT 'SUCCESS',
    description TEXT NULL,
    before_data JSONB NULL,
    after_data JSONB NULL,
    metadata JSONB NULL,
    request_id VARCHAR(100) NULL,
    route_name VARCHAR(150) NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(1000) NULL,
    CONSTRAINT fk_admin_audit_actor FOREIGN KEY (actor_admin_id) REFERENCES admin_users (id) ON DELETE SET NULL,
    CONSTRAINT chk_admin_audit_outcome CHECK (
        outcome IN (
            'SUCCESS',
            'FAILURE',
            'DENIED'
        )
    )
);

CREATE INDEX idx_admin_audit_occurred ON admin_audit_logs (occurred_at DESC);

CREATE INDEX idx_admin_audit_actor ON admin_audit_logs (
    actor_admin_id,
    occurred_at DESC
);

CREATE INDEX idx_admin_audit_action ON admin_audit_logs (action, occurred_at DESC);

CREATE INDEX idx_admin_audit_target ON admin_audit_logs (
    target_type,
    target_id,
    occurred_at DESC
);

CREATE INDEX idx_admin_audit_outcome ON admin_audit_logs (outcome, occurred_at DESC);

CREATE TABLE IF NOT EXISTS deployment_sql_history (
    id BIGSERIAL PRIMARY KEY,
    filename VARCHAR(255) NOT NULL UNIQUE,
    checksum_sha256 VARCHAR(64) NOT NULL,
    git_commit VARCHAR(40) NOT NULL,
    execution_started_at TIMESTAMPTZ NOT NULL,
    execution_ended_at TIMESTAMPTZ NOT NULL,
    execution_time_ms BIGINT NOT NULL,
    deployed_by VARCHAR(100) NOT NULL DEFAULT 'github-actions',
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE member_basic_details (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    date_of_birth DATE NULL,
    marital_status VARCHAR(30) NULL,
    height_cm SMALLINT NULL,
    mother_tongue VARCHAR(50) NULL,
    current_city VARCHAR(100) NULL,
    current_state VARCHAR(100) NULL,
    country_code CHAR(2) NOT NULL DEFAULT 'IN',
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    CONSTRAINT uq_member_basic_details_user_id UNIQUE (user_id),
    CONSTRAINT fk_member_basic_details_user FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS master_marital_statuses (
    id SMALLSERIAL PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    display_order SMALLINT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_master_marital_statuses_active_order ON master_marital_statuses (is_active, display_order);

CREATE TABLE IF NOT EXISTS master_heights (
    id SMALLSERIAL PRIMARY KEY,
    height_cm SMALLINT NOT NULL UNIQUE,
    display_name VARCHAR(50) NOT NULL,
    display_order SMALLINT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_master_heights_height_cm CHECK (height_cm BETWEEN 120 AND 220)
);

CREATE INDEX IF NOT EXISTS idx_master_heights_active_order ON master_heights (is_active, display_order);

CREATE TABLE IF NOT EXISTS master_mother_tongues (
    id SMALLSERIAL PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    display_order SMALLINT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_master_mother_tongues_active_order ON master_mother_tongues (is_active, display_order);

CREATE TABLE IF NOT EXISTS master_countries (
    id SMALLSERIAL PRIMARY KEY,
    iso_code CHAR(2) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    phone_code VARCHAR(10) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS master_states (
    id SERIAL PRIMARY KEY,
    country_id SMALLINT NOT NULL,
    code VARCHAR(10) NOT NULL,
    name VARCHAR(100) NOT NULL,
    display_order SMALLINT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_master_states_country FOREIGN KEY (country_id) REFERENCES master_countries (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT uq_master_states_country_code UNIQUE (country_id, code),
    CONSTRAINT uq_master_states_country_name UNIQUE (country_id, name)
);

CREATE INDEX IF NOT EXISTS idx_master_states_country_active_order ON master_states (
    country_id,
    is_active,
    display_order
);

CREATE TABLE IF NOT EXISTS master_cities (
    id SERIAL PRIMARY KEY,
    state_id INTEGER NOT NULL,
    name VARCHAR(120) NOT NULL,
    display_order INTEGER NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_master_cities_state FOREIGN KEY (state_id) REFERENCES master_states (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT uq_master_cities_state_name UNIQUE (state_id, name)
);

CREATE INDEX IF NOT EXISTS idx_master_cities_state_active_order ON master_cities (
    state_id,
    is_active,
    display_order
);

COMMIT;

INSERT INTO
    master_countries (
        iso_code,
        name,
        phone_code,
        is_active,
        created_at,
        updated_at
    )
VALUES (
        'IN',
        'India',
        '+91',
        TRUE,
        CURRENT_TIMESTAMP,
        CURRENT_TIMESTAMP
    )
ON CONFLICT (iso_code) DO
UPDATE
SET
    name = EXCLUDED.name,
    phone_code = EXCLUDED.phone_code,
    is_active = TRUE,
    updated_at = CURRENT_TIMESTAMP;

INSERT INTO
    master_marital_statuses (
        code,
        name,
        display_order,
        is_active,
        created_at,
        updated_at
    )
VALUES (
        'NEVER_MARRIED',
        'Never Married',
        1,
        TRUE,
        CURRENT_TIMESTAMP,
        CURRENT_TIMESTAMP
    ),
    (
        'DIVORCED',
        'Divorced',
        2,
        TRUE,
        CURRENT_TIMESTAMP,
        CURRENT_TIMESTAMP
    ),
    (
        'WIDOWED',
        'Widowed',
        3,
        TRUE,
        CURRENT_TIMESTAMP,
        CURRENT_TIMESTAMP
    ),
    (
        'ANNULLED',
        'Marriage Annulled',
        4,
        TRUE,
        CURRENT_TIMESTAMP,
        CURRENT_TIMESTAMP
    ),
    (
        'AWAITING_DIVORCE',
        'Awaiting Divorce',
        5,
        TRUE,
        CURRENT_TIMESTAMP,
        CURRENT_TIMESTAMP
    )
ON CONFLICT (code) DO
UPDATE
SET
    name = EXCLUDED.name,
    display_order = EXCLUDED.display_order,
    is_active = TRUE,
    updated_at = CURRENT_TIMESTAMP;

INSERT INTO
    master_mother_tongues (
        code,
        name,
        display_order,
        is_active,
        created_at,
        updated_at
    )
VALUES (
        'PUNJABI',
        'Punjabi',
        1,
        TRUE,
        CURRENT_TIMESTAMP,
        CURRENT_TIMESTAMP
    ),
    (
        'HINDI',
        'Hindi',
        2,
        TRUE,
        CURRENT_TIMESTAMP,
        CURRENT_TIMESTAMP
    ),
    (
        'ENGLISH',
        'English',
        3,
        TRUE,
        CURRENT_TIMESTAMP,
        CURRENT_TIMESTAMP
    ),
    (
        'URDU',
        'Urdu',
        4,
        TRUE,
        CURRENT_TIMESTAMP,
        CURRENT_TIMESTAMP
    ),
    (
        'OTHER',
        'Other',
        99,
        TRUE,
        CURRENT_TIMESTAMP,
        CURRENT_TIMESTAMP
    )
ON CONFLICT (code) DO
UPDATE
SET
    name = EXCLUDED.name,
    display_order = EXCLUDED.display_order,
    is_active = TRUE,
    updated_at = CURRENT_TIMESTAMP;

INSERT INTO
    master_heights (
        height_cm,
        display_name,
        display_order,
        is_active,
        created_at,
        updated_at
    )
SELECT
    height_cm,
    FLOOR((height_cm / 2.54) / 12)::INTEGER || ''' ' || MOD(
        ROUND(height_cm / 2.54)::INTEGER,
        12
    ) || '" (' || height_cm || ' cm)',
    height_cm,
    TRUE,
    CURRENT_TIMESTAMP,
    CURRENT_TIMESTAMP
FROM generate_series(120, 220) AS height_cm
ON CONFLICT (height_cm) DO
UPDATE
SET
    display_name = EXCLUDED.display_name,
    display_order = EXCLUDED.display_order,
    is_active = TRUE,
    updated_at = CURRENT_TIMESTAMP;

WITH
    india AS (
        SELECT id
        FROM master_countries
        WHERE
            iso_code = 'IN'
    )
INSERT INTO
    master_states (
        country_id,
        code,
        name,
        display_order,
        is_active,
        created_at,
        updated_at
    )
SELECT india.id, state_data.code, state_data.name, state_data.display_order, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
FROM india
    CROSS JOIN (
        VALUES (
                'AN', 'Andaman and Nicobar Islands', 1
            ), ('AP', 'Andhra Pradesh', 2), ('AR', 'Arunachal Pradesh', 3), ('AS', 'Assam', 4), ('BR', 'Bihar', 5), ('CH', 'Chandigarh', 6), ('CG', 'Chhattisgarh', 7), (
                'DN', 'Dadra and Nagar Haveli and Daman and Diu', 8
            ), ('DL', 'Delhi', 9), ('GA', 'Goa', 10), ('GJ', 'Gujarat', 11), ('HR', 'Haryana', 12), ('HP', 'Himachal Pradesh', 13), ('JK', 'Jammu and Kashmir', 14), ('JH', 'Jharkhand', 15), ('KA', 'Karnataka', 16), ('KL', 'Kerala', 17), ('LA', 'Ladakh', 18), ('LD', 'Lakshadweep', 19), ('MP', 'Madhya Pradesh', 20), ('MH', 'Maharashtra', 21), ('MN', 'Manipur', 22), ('ML', 'Meghalaya', 23), ('MZ', 'Mizoram', 24), ('NL', 'Nagaland', 25), ('OD', 'Odisha', 26), ('PY', 'Puducherry', 27), ('PB', 'Punjab', 28), ('RJ', 'Rajasthan', 29), ('SK', 'Sikkim', 30), ('TN', 'Tamil Nadu', 31), ('TS', 'Telangana', 32), ('TR', 'Tripura', 33), ('UP', 'Uttar Pradesh', 34), ('UK', 'Uttarakhand', 35), ('WB', 'West Bengal', 36)
    ) AS state_data (code, name, display_order)
ON CONFLICT (country_id, code) DO
UPDATE
SET
    name = EXCLUDED.name,
    display_order = EXCLUDED.display_order,
    is_active = TRUE,
    updated_at = CURRENT_TIMESTAMP;

INSERT INTO
    master_cities (
        state_id,
        name,
        display_order,
        is_active,
        created_at,
        updated_at
    )
SELECT master_states.id, city_data.name, city_data.display_order, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
FROM (
        VALUES ('RJ', 'Ajmer', 1),
            ('RJ', 'Alwar', 2),
            ('RJ', 'Bharatpur', 3),
            ('RJ', 'Bhilwara', 4),
            ('RJ', 'Bikaner', 5),
            ('RJ', 'Jaipur', 6),
            ('RJ', 'Jodhpur', 7),
            ('RJ', 'Kota', 8),
            ('RJ', 'Sikar', 9),
            ('RJ', 'Udaipur', 10),
            ('PB', 'Amritsar', 1),
            ('PB', 'Bathinda', 2),
            ('PB', 'Jalandhar', 3),
            ('PB', 'Ludhiana', 4),
            ('PB', 'Mohali', 5),
            ('PB', 'Patiala', 6),
            ('DL', 'New Delhi', 1),
            ('HR', 'Ambala', 1),
            ('HR', 'Faridabad', 2),
            ('HR', 'Gurugram', 3),
            ('HR', 'Karnal', 4),
            ('HR', 'Panipat', 5),
            ('UP', 'Agra', 1),
            ('UP', 'Ghaziabad', 2),
            ('UP', 'Gorakhpur', 3),
            ('UP', 'Kanpur', 4),
            ('UP', 'Lucknow', 5),
            ('UP', 'Noida', 6),
            ('UP', 'Varanasi', 7),
            ('CH', 'Chandigarh', 1)
    ) AS city_data (
        state_code,
        name,
        display_order
    )
    INNER JOIN master_states ON master_states.code = city_data.state_code
    INNER JOIN master_countries ON master_countries.id = master_states.country_id
    AND master_countries.iso_code = 'IN'
ON CONFLICT (state_id, name) DO
UPDATE
SET
    display_order = EXCLUDED.display_order,
    is_active = TRUE,
    updated_at = CURRENT_TIMESTAMP;

BEGIN;

ALTER TABLE member_basic_details
ADD COLUMN IF NOT EXISTS marital_status_id SMALLINT NULL,
ADD COLUMN IF NOT EXISTS height_id SMALLINT NULL,
ADD COLUMN IF NOT EXISTS mother_tongue_id SMALLINT NULL,
ADD COLUMN IF NOT EXISTS country_id SMALLINT NULL,
ADD COLUMN IF NOT EXISTS state_id INTEGER NULL,
ADD COLUMN IF NOT EXISTS city_id INTEGER NULL;

ALTER TABLE member_basic_details
DROP CONSTRAINT IF EXISTS fk_member_basic_marital_status;

ALTER TABLE member_basic_details
ADD CONSTRAINT fk_member_basic_marital_status FOREIGN KEY (marital_status_id) REFERENCES master_marital_statuses (id) ON UPDATE RESTRICT ON DELETE RESTRICT;

ALTER TABLE member_basic_details
DROP CONSTRAINT IF EXISTS fk_member_basic_height;

ALTER TABLE member_basic_details
ADD CONSTRAINT fk_member_basic_height FOREIGN KEY (height_id) REFERENCES master_heights (id) ON UPDATE RESTRICT ON DELETE RESTRICT;

ALTER TABLE member_basic_details
DROP CONSTRAINT IF EXISTS fk_member_basic_mother_tongue;

ALTER TABLE member_basic_details
ADD CONSTRAINT fk_member_basic_mother_tongue FOREIGN KEY (mother_tongue_id) REFERENCES master_mother_tongues (id) ON UPDATE RESTRICT ON DELETE RESTRICT;

ALTER TABLE member_basic_details
DROP CONSTRAINT IF EXISTS fk_member_basic_country;

ALTER TABLE member_basic_details
ADD CONSTRAINT fk_member_basic_country FOREIGN KEY (country_id) REFERENCES master_countries (id) ON UPDATE RESTRICT ON DELETE RESTRICT;

ALTER TABLE member_basic_details
DROP CONSTRAINT IF EXISTS fk_member_basic_state;

ALTER TABLE member_basic_details
ADD CONSTRAINT fk_member_basic_state FOREIGN KEY (state_id) REFERENCES master_states (id) ON UPDATE RESTRICT ON DELETE RESTRICT;

ALTER TABLE member_basic_details
DROP CONSTRAINT IF EXISTS fk_member_basic_city;

ALTER TABLE member_basic_details
ADD CONSTRAINT fk_member_basic_city FOREIGN KEY (city_id) REFERENCES master_cities (id) ON UPDATE RESTRICT ON DELETE RESTRICT;

CREATE INDEX IF NOT EXISTS idx_member_basic_state_city ON member_basic_details (state_id, city_id);

ALTER TABLE member_basic_details
DROP COLUMN marital_status,
DROP column height_cm,
DROP column mother_tongue,
DROP column current_city,
DROP column current_state,
DROP column country_code;

-- ============================================================
-- Highest Education master
-- ============================================================

CREATE TABLE IF NOT EXISTS master_educations (
    id SMALLSERIAL PRIMARY KEY,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(120) NOT NULL,
    display_order SMALLINT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_master_educations_code UNIQUE (code),
    CONSTRAINT uq_master_educations_name UNIQUE (name),
    CONSTRAINT chk_master_educations_code_not_blank CHECK (BTRIM(code) <> ''),
    CONSTRAINT chk_master_educations_name_not_blank CHECK (BTRIM(name) <> '')
);

CREATE INDEX IF NOT EXISTS idx_master_educations_active_order ON master_educations (
    is_active,
    display_order,
    name
);

-- ============================================================
-- Occupation master
-- ============================================================

CREATE TABLE master_occupation_categories (
    id SMALLSERIAL PRIMARY KEY,
    code VARCHAR(60) NOT NULL,
    name VARCHAR(100) NOT NULL,
    display_order SMALLINT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_master_occupation_categories_code UNIQUE (code),
    CONSTRAINT uq_master_occupation_categories_name UNIQUE (name),
    CONSTRAINT chk_master_occupation_categories_code_not_blank CHECK (BTRIM(code) <> ''),
    CONSTRAINT chk_master_occupation_categories_name_not_blank CHECK (BTRIM(name) <> ''),
    CONSTRAINT chk_master_occupation_categories_display_order CHECK (display_order >= 0)
);

CREATE INDEX idx_master_occupation_categories_active_order ON master_occupation_categories (
    is_active,
    display_order,
    name
);


CREATE TABLE master_occupations (
    id SMALLSERIAL PRIMARY KEY,
    category_id SMALLINT NOT NULL,
    code VARCHAR(80) NOT NULL,
    name VARCHAR(120) NOT NULL,
    display_order SMALLINT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_master_occupations_category
        FOREIGN KEY (category_id)
        REFERENCES master_occupation_categories(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT uq_master_occupations_code
        UNIQUE (code),

/*
 * The same visible name may exist in different categories.
 * Example:
 *   Engineering -> Designer
 *   Media & Entertainment -> Designer
 */

CONSTRAINT uq_master_occupations_category_name
        UNIQUE (category_id, name),

    CONSTRAINT chk_master_occupations_code_not_blank
        CHECK (BTRIM(code) <> ''),

    CONSTRAINT chk_master_occupations_name_not_blank
        CHECK (BTRIM(name) <> ''),

    CONSTRAINT chk_master_occupations_display_order
        CHECK (display_order >= 0)
);

CREATE INDEX idx_master_occupations_category_active_order ON master_occupations (
    category_id,
    is_active,
    display_order,
    name
);

CREATE INDEX idx_master_occupations_active_order ON master_occupations (
    is_active,
    display_order,
    name
);

-- ============================================================
-- Annual Income master
--
-- min_amount and max_amount are stored in INR.
-- max_amount NULL means no upper limit.
-- ============================================================

CREATE TABLE IF NOT EXISTS master_annual_incomes (
    id SMALLSERIAL PRIMARY KEY,
    code VARCHAR(50) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    min_amount BIGINT NOT NULL DEFAULT 0,
    max_amount BIGINT NULL,
    display_order SMALLINT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_master_annual_incomes_code UNIQUE (code),
    CONSTRAINT chk_master_annual_incomes_code_not_blank CHECK (BTRIM(code) <> ''),
    CONSTRAINT chk_master_annual_incomes_display_name_not_blank CHECK (BTRIM(display_name) <> ''),
    CONSTRAINT chk_master_annual_incomes_min_amount CHECK (min_amount >= 0),
    CONSTRAINT chk_master_annual_incomes_range CHECK (
        max_amount IS NULL
        OR max_amount > min_amount
    )
);

CREATE INDEX IF NOT EXISTS idx_master_annual_incomes_active_order ON master_annual_incomes (is_active, display_order);

-- ============================================================
-- Member Education & Profession details
-- ============================================================

CREATE TABLE IF NOT EXISTS member_education_profession_details (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    highest_education_id SMALLINT NOT NULL,
    education_detail VARCHAR(500) NULL,
    college_institution VARCHAR(200) NULL,
    employed_in VARCHAR(30) NOT NULL,
    occupation_id SMALLINT NOT NULL,
    occupation_detail VARCHAR(500) NULL,
    organization VARCHAR(200) NULL,
    annual_income_id SMALLINT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_member_education_profession_user UNIQUE (user_id),
    CONSTRAINT fk_member_education_profession_user FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT fk_member_education_profession_education FOREIGN KEY (highest_education_id) REFERENCES master_educations (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_member_education_profession_occupation FOREIGN KEY (occupation_id) REFERENCES master_occupations (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_member_education_profession_income FOREIGN KEY (annual_income_id) REFERENCES master_annual_incomes (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT chk_member_education_detail_not_blank CHECK (
        education_detail IS NULL
        OR BTRIM(education_detail) <> ''
    ),
    CONSTRAINT chk_member_college_not_blank CHECK (
        college_institution IS NULL
        OR BTRIM(college_institution) <> ''
    ),
    CONSTRAINT chk_member_occupation_detail_not_blank CHECK (
        occupation_detail IS NULL
        OR BTRIM(occupation_detail) <> ''
    ),
    CONSTRAINT chk_member_organization_not_blank CHECK (
        organization IS NULL
        OR BTRIM(organization) <> ''
    ),
    CONSTRAINT chk_member_employed_in CHECK (
        employed_in IN (
            'GOVERNMENT_PSU',
            'PRIVATE',
            'BUSINESS',
            'DEFENSE',
            'SELF_EMPLOYED',
            'NOT_WORKING'
        )
    )
);

CREATE INDEX IF NOT EXISTS idx_member_education_profession_education ON member_education_profession_details (highest_education_id);

CREATE INDEX IF NOT EXISTS idx_member_education_profession_occupation ON member_education_profession_details (occupation_id);

CREATE INDEX IF NOT EXISTS idx_member_education_profession_income ON member_education_profession_details (annual_income_id);

CREATE INDEX IF NOT EXISTS idx_member_education_profession_employed ON member_education_profession_details (employed_in);

-- ============================================================
-- Seed: Education
-- ============================================================

INSERT INTO
    master_educations (code, name, display_order)
VALUES (
        'HIGH_SCHOOL',
        'High School',
        10
    ),
    ('DIPLOMA', 'Diploma', 20),
    (
        'BACHELORS',
        'Bachelor''s Degree',
        30
    ),
    (
        'MASTERS',
        'Master''s Degree',
        40
    ),
    (
        'DOCTORATE',
        'Doctorate / PhD',
        50
    )
ON CONFLICT (code) DO
UPDATE
SET
    name = EXCLUDED.name,
    display_order = EXCLUDED.display_order,
    is_active = TRUE,
    updated_at = CURRENT_TIMESTAMP;

-- ============================================================
-- Seed occupation categories
-- ============================================================

INSERT INTO
    master_occupation_categories (id, code, name, display_order)
VALUES (
        1,
        'ADMINISTRATION',
        'Administration',
        10
    ),
    (
        2,
        'AGRICULTURE',
        'Agriculture',
        20
    ),
    (3, 'AIRLINE', 'Airline', 30),
    (
        4,
        'ARCHITECTURE_DESIGN',
        'Architecture & Design',
        40
    ),
    (
        5,
        'BANKING_FINANCE',
        'Banking & Finance',
        50
    ),
    (
        6,
        'BEAUTY_FASHION',
        'Beauty & Fashion',
        60
    ),
    (
        7,
        'BPO_CUSTOMER_SERVICE',
        'BPO & Customer Service',
        70
    ),
    (
        8,
        'CIVIL_SERVICES',
        'Civil Services',
        80
    ),
    (
        9,
        'CORPORATE_PROFESSIONALS',
        'Corporate Professionals',
        90
    ),
    (10, 'DEFENCE', 'Defence', 100),
    (
        11,
        'EDUCATION_TRAINING',
        'Education & Training',
        110
    ),
    (
        12,
        'ENGINEERING',
        'Engineering',
        120
    ),
    (
        13,
        'HOSPITALITY',
        'Hospitality',
        130
    ),
    (
        14,
        'IT_SOFTWARE',
        'IT & Software',
        140
    ),
    (15, 'LEGAL', 'Legal', 150),
    (
        16,
        'POLICE_LAW_ENFORCEMENT',
        'Police / Law Enforcement',
        160
    ),
    (
        17,
        'MEDICAL_HEALTHCARE',
        'Medical & Healthcare',
        170
    ),
    (
        18,
        'MEDIA_ENTERTAINMENT',
        'Media & Entertainment',
        180
    ),
    (
        19,
        'MERCHANT_NAVY',
        'Merchant Navy',
        190
    ),
    (
        20,
        'SCIENTIST',
        'Scientist',
        200
    ),
    (
        21,
        'SENIOR_MANAGEMENT',
        'Senior Management',
        210
    ),
    (22, 'OTHERS', 'Others', 220),
    (23, 'DOCTOR', 'Doctor', 230);

SELECT setval(
        pg_get_serial_sequence(
            'master_occupation_categories', 'id'
        ), (
            SELECT MAX(id)
            FROM master_occupation_categories
        ), TRUE
    );

-- ============================================================
-- Seed occupations
-- ============================================================

INSERT INTO
    master_occupations (
        id,
        category_id,
        code,
        name,
        display_order
    )
VALUES

-- Administration
(
    49,
    1,
    'MANAGER',
    'Manager',
    10
),
(
    48,
    1,
    'SUPERVISOR',
    'Supervisor',
    20
),
(
    47,
    1,
    'OFFICER',
    'Officer',
    30
),
(
    39,
    1,
    'ADMINISTRATIVE_PROFESSIONAL',
    'Administrative Professional',
    40
),
(
    50,
    1,
    'EXECUTIVE',
    'Executive',
    50
),
(46, 1, 'CLERK', 'Clerk', 60),
(
    63,
    1,
    'HUMAN_RESOURCES_PROFESSIONAL',
    'Human Resources Professional',
    70
),
(
    78,
    1,
    'SECRETARY_FRONT_OFFICE',
    'Secretary / Front Office',
    80
),

-- Agriculture
(
    37,
    2,
    'AGRICULTURE_FARMING_PROFESSIONAL',
    'Agriculture & Farming Professional',
    10
),
(
    81,
    2,
    'HORTICULTURIST',
    'Horticulturist',
    20
),

-- Airline
(30, 3, 'PILOT', 'Pilot', 10),
(
    28,
    3,
    'AIR_HOSTESS_FLIGHT_ATTENDANT',
    'Air Hostess / Flight Attendant',
    20
),
(
    29,
    3,
    'AIRLINE_PROFESSIONAL',
    'Airline Professional',
    30
),

-- Architecture & Design
(
    19,
    4,
    'ARCHITECT',
    'Architect',
    10
),
(
    20,
    4,
    'INTERIOR_DESIGNER',
    'Interior Designer',
    20
),

-- Banking & Finance
(
    7,
    5,
    'CHARTERED_ACCOUNTANT',
    'Chartered Accountant',
    10
),
(
    10,
    5,
    'COMPANY_SECRETARY',
    'Company Secretary',
    20
),
(
    8,
    5,
    'ACCOUNTS_FINANCE_PROFESSIONAL',
    'Accounts / Finance Professional',
    30
),
(
    16,
    5,
    'BANKING_PROFESSIONAL',
    'Banking Professional',
    40
),
(
    9,
    5,
    'AUDITOR',
    'Auditor',
    50
),
(
    69,
    5,
    'FINANCIAL_ACCOUNTANT',
    'Financial Accountant',
    60
),
(
    64,
    5,
    'FINANCIAL_ANALYST_PLANNING',
    'Financial Analyst / Planning',
    70
),
(
    87,
    5,
    'INVESTMENT_PROFESSIONAL',
    'Investment Professional',
    80
),

-- Beauty & Fashion
(
    25,
    6,
    'FASHION_DESIGNER',
    'Fashion Designer',
    10
),
(
    33,
    6,
    'BEAUTICIAN',
    'Beautician',
    20
),
(
    82,
    6,
    'HAIR_STYLIST',
    'Hair Stylist',
    30
),
(
    83,
    6,
    'JEWELLERY_DESIGNER',
    'Jewellery Designer',
    40
),
(
    84,
    6,
    'DESIGNER_OTHERS',
    'Designer (Others)',
    50
),
(
    85,
    6,
    'MAKEUP_ARTIST',
    'Makeup Artist',
    60
),

-- BPO & Customer Service
(
    86,
    7,
    'BPO_KPO_ITES_PROFESSIONAL',
    'BPO / KPO / ITES Professional',
    10
),
(
    40,
    7,
    'CUSTOMER_SERVICE_PROFESSIONAL',
    'Customer Service Professional',
    20
),

-- Civil Services
(
    52,
    8,
    'CIVIL_SERVICES',
    'Civil Services (IAS / IPS / IRS / IES / IFS)',
    10
),

-- Corporate Professionals
(
    70,
    9,
    'ANALYST',
    'Analyst',
    10
),
(
    45,
    9,
    'CONSULTANT',
    'Consultant',
    20
),
(
    88,
    9,
    'CORPORATE_COMMUNICATION',
    'Corporate Communication',
    30
),
(
    89,
    9,
    'CORPORATE_PLANNING',
    'Corporate Planning',
    40
),
(
    42,
    9,
    'MARKETING_PROFESSIONAL',
    'Marketing Professional',
    50
),
(
    90,
    9,
    'OPERATIONS_MANAGEMENT',
    'Operations Management',
    60
),
(
    43,
    9,
    'SALES_PROFESSIONAL',
    'Sales Professional',
    70
),
(
    91,
    9,
    'SENIOR_MANAGER_MANAGER',
    'Senior Manager / Manager',
    80
),
(
    92,
    9,
    'SUBJECT_MATTER_EXPERT',
    'Subject Matter Expert',
    90
),
(
    93,
    9,
    'BUSINESS_DEVELOPMENT_PROFESSIONAL',
    'Business Development Professional',
    100
),
(
    94,
    9,
    'CONTENT_WRITER',
    'Content Writer',
    110
),

-- Defence
(53, 10, 'ARMY', 'Army', 10),
(54, 10, 'NAVY', 'Navy', 20),
(
    96,
    10,
    'DEFENCE_SERVICES_OTHERS',
    'Defence Services (Others)',
    30
),
(
    55,
    10,
    'AIR_FORCE',
    'Air Force',
    40
),
(
    97,
    10,
    'PARAMILITARY',
    'Paramilitary',
    50
),

-- Education & Training
(
    5,
    11,
    'PROFESSOR_LECTURER',
    'Professor / Lecturer',
    10
),
(
    4,
    11,
    'TEACHING_ACADEMICIAN',
    'Teaching / Academician',
    20
),
(
    6,
    11,
    'EDUCATION_PROFESSIONAL',
    'Education Professional',
    30
),
(
    111,
    11,
    'TRAINING_PROFESSIONAL',
    'Training Professional',
    40
),
(
    112,
    11,
    'RESEARCH_ASSISTANT',
    'Research Assistant',
    50
),
(
    113,
    11,
    'RESEARCH_SCHOLAR',
    'Research Scholar',
    60
),

-- Engineering
(
    114,
    12,
    'CIVIL_ENGINEER',
    'Civil Engineer',
    10
),
(
    115,
    12,
    'ELECTRONICS_TELECOM_ENGINEER',
    'Electronics / Telecom Engineer',
    20
),
(
    116,
    12,
    'MECHANICAL_PRODUCTION_ENGINEER',
    'Mechanical / Production Engineer',
    30
),
(
    117,
    12,
    'QA_ENGINEER_NON_IT',
    'Quality Assurance Engineer - Non IT',
    40
),
(
    3,
    12,
    'ENGINEER_NON_IT',
    'Engineer - Non IT',
    50
),
(
    65,
    12,
    'ENGINEERING_DESIGNER',
    'Designer',
    60
),
(
    118,
    12,
    'PRODUCT_MANAGER_NON_IT',
    'Product Manager - Non IT',
    70
),
(
    77,
    12,
    'PROJECT_MANAGER_NON_IT',
    'Project Manager - Non IT',
    80
),

-- Hospitality
(
    34,
    13,
    'HOTEL_HOSPITALITY_PROFESSIONAL',
    'Hotel / Hospitality Professional',
    10
),
(
    129,
    13,
    'RESTAURANT_CATERING_PROFESSIONAL',
    'Restaurant / Catering Professional',
    20
),
(
    130,
    13,
    'CHEF_COOK',
    'Chef / Cook',
    30
),

-- IT & Software
(
    1,
    14,
    'SOFTWARE_PROFESSIONAL',
    'Software Professional',
    10
),
(
    2,
    14,
    'HARDWARE_PROFESSIONAL',
    'Hardware Professional',
    20
),
(
    74,
    14,
    'PRODUCT_MANAGER',
    'Product Manager',
    30
),
(
    76,
    14,
    'PROJECT_MANAGER',
    'Project Manager',
    40
),
(
    75,
    14,
    'PROGRAM_MANAGER',
    'Program Manager',
    50
),
(
    119,
    14,
    'ANIMATOR',
    'Animator',
    60
),
(
    120,
    14,
    'CYBER_NETWORK_SECURITY',
    'Cyber / Network Security',
    70
),
(
    121,
    14,
    'UI_UX_DESIGNER',
    'UI / UX Designer',
    80
),
(
    122,
    14,
    'WEB_GRAPHIC_DESIGNER',
    'Web / Graphic Designer',
    90
),
(
    123,
    14,
    'SOFTWARE_CONSULTANT',
    'Software Consultant',
    100
),
(
    124,
    14,
    'DATA_ANALYST',
    'Data Analyst',
    110
),
(
    125,
    14,
    'DATA_SCIENTIST',
    'Data Scientist',
    120
),
(
    126,
    14,
    'NETWORK_ENGINEER',
    'Network Engineer',
    130
),
(
    128,
    14,
    'QUALITY_ASSURANCE_ENGINEER',
    'Quality Assurance Engineer',
    140
),

-- Legal
(
    17,
    15,
    'LAWYER_LEGAL_PROFESSIONAL',
    'Lawyer & Legal Professional',
    10
),
(
    131,
    15,
    'LEGAL_ASSISTANT',
    'Legal Assistant',
    20
),

-- Police / Law Enforcement
(
    18,
    16,
    'LAW_ENFORCEMENT_OFFICER',
    'Law Enforcement Officer',
    10
),
(
    95,
    16,
    'POLICE',
    'Police',
    20
),

-- Medical & Healthcare
(
    14,
    17,
    'HEALTHCARE_PROFESSIONAL',
    'Healthcare Professional',
    10
),
(
    15,
    17,
    'PARAMEDICAL_PROFESSIONAL',
    'Paramedical Professional',
    20
),
(13, 17, 'NURSE', 'Nurse', 30),
(
    98,
    17,
    'PHARMACIST',
    'Pharmacist',
    40
),
(
    100,
    17,
    'PHYSIOTHERAPIST',
    'Physiotherapist',
    50
),
(
    103,
    17,
    'PSYCHOLOGIST',
    'Psychologist',
    60
),
(
    107,
    17,
    'THERAPIST',
    'Therapist',
    70
),
(
    108,
    17,
    'MEDICAL_TRANSCRIPTIONIST',
    'Medical Transcriptionist',
    80
),
(
    109,
    17,
    'DIETICIAN_NUTRITIONIST',
    'Dietician / Nutritionist',
    90
),
(
    110,
    17,
    'LAB_TECHNICIAN',
    'Lab Technician',
    100
),
(
    150,
    17,
    'MEDICAL_REPRESENTATIVE',
    'Medical Representative',
    110
),

-- Media & Entertainment
(
    27,
    18,
    'JOURNALIST',
    'Journalist',
    10
),
(
    22,
    18,
    'MEDIA_PROFESSIONAL',
    'Media Professional',
    20
),
(
    24,
    18,
    'ENTERTAINMENT_PROFESSIONAL',
    'Entertainment Professional',
    30
),
(
    26,
    18,
    'EVENT_MANAGEMENT_PROFESSIONAL',
    'Event Management Professional',
    40
),
(
    21,
    18,
    'ADVERTISING_PR_PROFESSIONAL',
    'Advertising / PR Professional',
    50
),
(
    66,
    18,
    'MEDIA_DESIGNER',
    'Designer',
    60
),
(
    79,
    18,
    'ACTOR_MODEL',
    'Actor / Model',
    70
),
(
    80,
    18,
    'ARTIST',
    'Artist',
    80
),

-- Merchant Navy
(
    32,
    19,
    'MARINER_MERCHANT_NAVY',
    'Mariner / Merchant Navy',
    10
),
(
    133,
    19,
    'SAILOR',
    'Sailor',
    20
),

-- Scientist
(
    35,
    20,
    'SCIENTIST_RESEARCHER',
    'Scientist / Researcher',
    10
),

-- Senior Management
(
    41,
    21,
    'CXO_PRESIDENT_DIRECTOR_CHAIRMAN',
    'CXO / President, Director, Chairman',
    10
),
(
    134,
    21,
    'VP_AVP_GM_DGM_AGM',
    'VP / AVP / GM / DGM / AGM',
    20
),

-- Others
(
    44,
    22,
    'TECHNICIAN',
    'Technician',
    10
),
(
    38,
    22,
    'ARTS_CRAFTSMAN',
    'Arts & Craftsman',
    20
),
(
    68,
    22,
    'LIBRARIAN',
    'Librarian',
    30
),
(
    71,
    22,
    'BUSINESS_OWNER_ENTREPRENEUR',
    'Business Owner / Entrepreneur',
    40
),
(
    72,
    22,
    'RETIRED',
    'Retired',
    50
),
(
    73,
    22,
    'TRANSPORT_LOGISTICS_PROFESSIONAL',
    'Transportation / Logistics Professional',
    60
),
(
    135,
    22,
    'AGENT_BROKER_TRADER',
    'Agent / Broker / Trader',
    70
),
(
    136,
    22,
    'CONTRACTOR',
    'Contractor',
    80
),
(
    137,
    22,
    'FITNESS_PROFESSIONAL',
    'Fitness Professional',
    90
),
(
    138,
    22,
    'SECURITY_PROFESSIONAL',
    'Security Professional',
    100
),
(
    36,
    22,
    'SOCIAL_WORKER_VOLUNTEER_NGO',
    'Social Worker / Volunteer / NGO',
    110
),
(
    51,
    22,
    'SPORTSPERSON',
    'Sportsperson',
    120
),
(
    139,
    22,
    'TRAVEL_PROFESSIONAL',
    'Travel Professional',
    130
),
(
    140,
    22,
    'SINGER',
    'Singer',
    140
),
(
    141,
    22,
    'WRITER',
    'Writer',
    150
),
(
    158,
    22,
    'POLITICIAN',
    'Politician',
    160
),
(
    142,
    22,
    'ASSOCIATE',
    'Associate',
    170
),
(
    143,
    22,
    'BUILDER',
    'Builder',
    180
),
(
    144,
    22,
    'CHEMIST',
    'Chemist',
    190
),
(
    145,
    22,
    'CNC_OPERATOR',
    'CNC Operator',
    200
),
(
    146,
    22,
    'DISTRIBUTOR',
    'Distributor',
    210
),
(
    147,
    22,
    'DRIVER',
    'Driver',
    220
),
(
    148,
    22,
    'FREELANCER',
    'Freelancer',
    230
),
(
    149,
    22,
    'MECHANIC',
    'Mechanic',
    240
),
(
    151,
    22,
    'MUSICIAN',
    'Musician',
    250
),
(
    152,
    22,
    'PHOTO_VIDEOGRAPHER',
    'Photo / Videographer',
    260
),
(
    153,
    22,
    'SURVEYOR',
    'Surveyor',
    270
),
(
    154,
    22,
    'TAILOR',
    'Tailor',
    280
),
(
    99,
    22,
    'OTHERS',
    'Others',
    290
),

/*
 * Required by the current Not Working synchronization.
 * Keep this code unchanged.
 */
(
    159,
    22,
    'NOT_APPLICABLE',
    'Not Applicable',
    300
),

-- Doctor
(
    12,
    23,
    'DOCTOR',
    'Doctor',
    10
),
(
    105,
    23,
    'DENTIST',
    'Dentist',
    20
),
(
    106,
    23,
    'SURGEON',
    'Surgeon',
    30
),
(
    104,
    23,
    'VETERINARY_DOCTOR',
    'Veterinary Doctor',
    40
);

SELECT setval(
        pg_get_serial_sequence('master_occupations', 'id'), (
            SELECT MAX(id)
            FROM master_occupations
        ), TRUE
    );

-- ============================================================
-- Seed: Annual Income
-- 1 lakh = 100000 INR
-- ============================================================

INSERT INTO
    master_annual_incomes (
        code,
        display_name,
        min_amount,
        max_amount,
        display_order
    )
VALUES (
        'INR_0_1_LAKH',
        '₹0 – ₹1 Lakh',
        0,
        100000,
        10
    ),
    (
        'INR_1_2_LAKH',
        '₹1 – ₹2 Lakh',
        100000,
        200000,
        20
    ),
    (
        'INR_2_3_LAKH',
        '₹2 – ₹3 Lakh',
        200000,
        300000,
        30
    ),
    (
        'INR_3_4_LAKH',
        '₹3 – ₹4 Lakh',
        300000,
        400000,
        40
    ),
    (
        'INR_4_5_LAKH',
        '₹4 – ₹5 Lakh',
        400000,
        500000,
        50
    ),
    (
        'INR_5_6_LAKH',
        '₹5 – ₹6 Lakh',
        500000,
        600000,
        60
    ),
    (
        'INR_6_7_LAKH',
        '₹6 – ₹7 Lakh',
        600000,
        700000,
        70
    ),
    (
        'INR_7_8_LAKH',
        '₹7 – ₹8 Lakh',
        700000,
        800000,
        80
    ),
    (
        'INR_8_9_LAKH',
        '₹8 – ₹9 Lakh',
        800000,
        900000,
        90
    ),
    (
        'INR_9_10_LAKH',
        '₹9 – ₹10 Lakh',
        900000,
        1000000,
        100
    ),
    (
        'INR_10_15_LAKH',
        '₹10 – ₹15 Lakh',
        1000000,
        1500000,
        110
    ),
    (
        'INR_15_20_LAKH',
        '₹15 – ₹20 Lakh',
        1500000,
        2000000,
        120
    ),
    (
        'INR_20_25_LAKH',
        '₹20 – ₹25 Lakh',
        2000000,
        2500000,
        130
    ),
    (
        'INR_25_30_LAKH',
        '₹25 – ₹30 Lakh',
        2500000,
        3000000,
        140
    ),
    (
        'INR_30_35_LAKH',
        '₹30 – ₹35 Lakh',
        3000000,
        3500000,
        150
    ),
    (
        'INR_35_40_LAKH',
        '₹35 – ₹40 Lakh',
        3500000,
        4000000,
        160
    ),
    (
        'INR_40_45_LAKH',
        '₹40 – ₹45 Lakh',
        4000000,
        4500000,
        170
    ),
    (
        'INR_45_50_LAKH',
        '₹45 – ₹50 Lakh',
        4500000,
        5000000,
        180
    ),
    (
        'INR_50_60_LAKH',
        '₹50 – ₹60 Lakh',
        5000000,
        6000000,
        190
    ),
    (
        'INR_60_70_LAKH',
        '₹60 – ₹70 Lakh',
        6000000,
        7000000,
        200
    ),
    (
        'INR_70_80_LAKH',
        '₹70 – ₹80 Lakh',
        7000000,
        8000000,
        210
    ),
    (
        'INR_80_90_LAKH',
        '₹80 – ₹90 Lakh',
        8000000,
        9000000,
        220
    ),
    (
        'INR_90_100_LAKH',
        '₹90 Lakh – ₹1 Crore',
        9000000,
        10000000,
        230
    ),
    (
        'INR_ABOVE_1_CRORE',
        'More than ₹1 Crore',
        10000000,
        NULL,
        240
    )
ON CONFLICT (code) DO
UPDATE
SET
    display_name = EXCLUDED.display_name,
    min_amount = EXCLUDED.min_amount,
    max_amount = EXCLUDED.max_amount,
    display_order = EXCLUDED.display_order,
    is_active = TRUE,
    updated_at = CURRENT_TIMESTAMP;

CREATE TABLE IF NOT EXISTS master_family_occupations (
    id SMALLSERIAL PRIMARY KEY,
    code VARCHAR(40) NOT NULL,
    name VARCHAR(100) NOT NULL,
    display_order SMALLINT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_master_family_occupations_code UNIQUE (code),
    CONSTRAINT uq_master_family_occupations_name UNIQUE (name)
);

CREATE INDEX IF NOT EXISTS idx_master_family_occupations_active_order ON master_family_occupations (
    is_active,
    display_order,
    name
);

INSERT INTO
    master_family_occupations (
        code,
        name,
        display_order,
        is_active
    )
VALUES (
        'EMPLOYED',
        'Employed',
        10,
        TRUE
    ),
    (
        'BUSINESS_PERSON',
        'Business Person',
        20,
        TRUE
    ),
    (
        'PROFESSIONAL',
        'Professional',
        30,
        TRUE
    ),
    (
        'RETIRED',
        'Retired',
        40,
        TRUE
    ),
    (
        'NOT_EMPLOYED',
        'Not Employed',
        50,
        TRUE
    ),
    (
        'PASSED_AWAY',
        'Passed Away',
        60,
        TRUE
    )
ON CONFLICT (code) DO
UPDATE
SET
    name = EXCLUDED.name,
    display_order = EXCLUDED.display_order,
    is_active = EXCLUDED.is_active,
    updated_at = CURRENT_TIMESTAMP;

-- ---------------------------------------------------------------------------
-- One family-detail row per member.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS member_family_details (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    family_value VARCHAR(20) NOT NULL,
    family_type VARCHAR(20) NOT NULL,
    family_status VARCHAR(30) NOT NULL,
    father_occupation_id SMALLINT NULL,
    mother_occupation_id SMALLINT NULL,
    brothers_count SMALLINT NOT NULL DEFAULT 0,
    married_brothers_count SMALLINT NOT NULL DEFAULT 0,
    sisters_count SMALLINT NOT NULL DEFAULT 0,
    married_sisters_count SMALLINT NOT NULL DEFAULT 0,
    country_id SMALLINT NOT NULL,
    state_id INTEGER NOT NULL,
    city_id INTEGER NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_member_family_details_user UNIQUE (user_id),
    CONSTRAINT fk_member_family_details_user FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT fk_member_family_details_father_occupation FOREIGN KEY (father_occupation_id) REFERENCES master_family_occupations (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_member_family_details_mother_occupation FOREIGN KEY (mother_occupation_id) REFERENCES master_family_occupations (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_member_family_details_country FOREIGN KEY (country_id) REFERENCES master_countries (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_member_family_details_state FOREIGN KEY (state_id) REFERENCES master_states (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_member_family_details_city FOREIGN KEY (city_id) REFERENCES master_cities (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT chk_member_family_value CHECK (
        family_value IN (
            'ORTHODOX',
            'TRADITIONAL',
            'MODERATE',
            'LIBERAL'
        )
    ),
    CONSTRAINT chk_member_family_type CHECK (
        family_type IN (
            'JOINT_FAMILY',
            'NUCLEAR_FAMILY',
            'OTHERS'
        )
    ),
    CONSTRAINT chk_member_family_status CHECK (
        family_status IN (
            'MIDDLE_CLASS',
            'UPPER_MIDDLE_CLASS',
            'HIGH_CLASS',
            'RICH_AFFLUENT'
        )
    ),
    CONSTRAINT chk_member_family_brothers_count CHECK (
        brothers_count BETWEEN 0 AND 10
    ),
    CONSTRAINT chk_member_family_married_brothers_count CHECK (
        married_brothers_count BETWEEN 0 AND brothers_count
    ),
    CONSTRAINT chk_member_family_sisters_count CHECK (
        sisters_count BETWEEN 0 AND 10
    ),
    CONSTRAINT chk_member_family_married_sisters_count CHECK (
        married_sisters_count BETWEEN 0 AND sisters_count
    )
);

CREATE INDEX IF NOT EXISTS idx_member_family_details_location ON member_family_details (country_id, state_id, city_id);

CREATE TABLE IF NOT EXISTS master_sikh_communities (
    id SMALLSERIAL PRIMARY KEY,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    display_order SMALLINT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_master_sikh_communities_code UNIQUE (code),
    CONSTRAINT uq_master_sikh_communities_name UNIQUE (name)
);

CREATE INDEX IF NOT EXISTS idx_master_sikh_communities_active_order ON master_sikh_communities (
    is_active,
    display_order,
    name
);

INSERT INTO
    master_sikh_communities (
        code,
        name,
        display_order,
        is_active
    )
VALUES (
        'JAT_SIKH',
        'Jat Sikh',
        10,
        TRUE
    ),
    (
        'KHATRI_SIKH',
        'Khatri Sikh',
        20,
        TRUE
    ),
    (
        'ARORA_SIKH',
        'Arora Sikh',
        30,
        TRUE
    ),
    (
        'RAMGARHIA_SIKH',
        'Ramgarhia Sikh',
        40,
        TRUE
    ),
    (
        'SAINI_SIKH',
        'Saini Sikh',
        50,
        TRUE
    ),
    (
        'KAMBOH_SIKH',
        'Kamboh Sikh',
        60,
        TRUE
    ),
    (
        'AHLUWALIA_SIKH',
        'Ahluwalia Sikh',
        70,
        TRUE
    ),
    (
        'LUBANA_SIKH',
        'Lubana Sikh',
        80,
        TRUE
    ),
    (
        'RAI_SIKH',
        'Rai Sikh',
        90,
        TRUE
    ),
    (
        'MAZHABI_SIKH',
        'Mazhabi Sikh',
        100,
        TRUE
    ),
    (
        'RAMDASIA_SIKH',
        'Ramdasia Sikh',
        110,
        TRUE
    ),
    (
        'RAVIDASIA_SIKH',
        'Ravidassia Sikh',
        120,
        TRUE
    ),
    (
        'OTHER_SIKH',
        'Other Sikh Community',
        900,
        TRUE
    ),
    (
        'PREFER_NOT_TO_SAY',
        'Prefer not to say',
        999,
        TRUE
    )
ON CONFLICT (code) DO
UPDATE
SET
    name = EXCLUDED.name,
    display_order = EXCLUDED.display_order,
    is_active = EXCLUDED.is_active,
    updated_at = CURRENT_TIMESTAMP;

-- ============================================================================
-- Sikh sub-community/sub-caste master
-- ============================================================================

CREATE TABLE IF NOT EXISTS master_sikh_subcommunities (
    id SERIAL PRIMARY KEY,
    community_id SMALLINT NOT NULL,
    code VARCHAR(70) NOT NULL,
    name VARCHAR(120) NOT NULL,
    display_order SMALLINT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_master_sikh_subcommunities_community FOREIGN KEY (community_id) REFERENCES master_sikh_communities (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT uq_master_sikh_subcommunities_community_code UNIQUE (community_id, code),
    CONSTRAINT uq_master_sikh_subcommunities_community_name UNIQUE (community_id, name)
);

CREATE INDEX IF NOT EXISTS idx_master_sikh_subcommunities_community_active ON master_sikh_subcommunities (
    community_id,
    is_active,
    display_order,
    name
);

-- Seed only commonly used matrimonial community groupings.
-- Add or deactivate rows later without changing application code.

INSERT INTO
    master_sikh_subcommunities (
        community_id,
        code,
        name,
        display_order,
        is_active
    )
SELECT community.id, seed.code, seed.name, seed.display_order, TRUE
FROM
    master_sikh_communities community
    JOIN (
        VALUES (
                'JAT_SIKH',
                'SANDHU',
                'Sandhu',
                10
            ),
            (
                'JAT_SIKH',
                'SIDHU',
                'Sidhu',
                20
            ),
            (
                'JAT_SIKH',
                'GILL',
                'Gill',
                30
            ),
            (
                'JAT_SIKH',
                'BRAR',
                'Brar',
                40
            ),
            (
                'JAT_SIKH',
                'DHILLON',
                'Dhillon',
                50
            ),
            (
                'JAT_SIKH',
                'MANN',
                'Mann',
                60
            ),
            (
                'JAT_SIKH',
                'GREWAL',
                'Grewal',
                70
            ),
            (
                'JAT_SIKH',
                'BAJWA',
                'Bajwa',
                80
            ),
            (
                'JAT_SIKH',
                'RANDHAWA',
                'Randhawa',
                90
            ),
            (
                'JAT_SIKH',
                'OTHER',
                'Other',
                999
            ),
            (
                'KHATRI_SIKH',
                'BEDI',
                'Bedi',
                10
            ),
            (
                'KHATRI_SIKH',
                'KAPOOR',
                'Kapoor',
                20
            ),
            (
                'KHATRI_SIKH',
                'KHANNA',
                'Khanna',
                30
            ),
            (
                'KHATRI_SIKH',
                'MALHOTRA',
                'Malhotra',
                40
            ),
            (
                'KHATRI_SIKH',
                'MEHRA',
                'Mehra',
                50
            ),
            (
                'KHATRI_SIKH',
                'SURI',
                'Suri',
                60
            ),
            (
                'KHATRI_SIKH',
                'OTHER',
                'Other',
                999
            ),
            (
                'ARORA_SIKH',
                'AHUJA',
                'Ahuja',
                10
            ),
            (
                'ARORA_SIKH',
                'ARORA',
                'Arora',
                20
            ),
            (
                'ARORA_SIKH',
                'CHAWLA',
                'Chawla',
                30
            ),
            (
                'ARORA_SIKH',
                'GROVER',
                'Grover',
                40
            ),
            (
                'ARORA_SIKH',
                'NARANG',
                'Narang',
                50
            ),
            (
                'ARORA_SIKH',
                'OTHER',
                'Other',
                999
            ),
            (
                'RAMGARHIA',
                'BHOGAL',
                'Bhogal',
                10
            ),
            (
                'RAMGARHIA',
                'BIRDI',
                'Birdi',
                20
            ),
            (
                'RAMGARHIA',
                'MATHARU',
                'Matharu',
                30
            ),
            (
                'RAMGARHIA',
                'REKHY',
                'Rekhy',
                40
            ),
            (
                'RAMGARHIA',
                'SAGOO',
                'Sagoo',
                50
            ),
            (
                'RAMGARHIA',
                'OTHER',
                'Other',
                999
            ),
            (
                'SAINI_SIKH',
                'BHOLA',
                'Bhola',
                10
            ),
            (
                'SAINI_SIKH',
                'CHANDI',
                'Chandi',
                20
            ),
            (
                'SAINI_SIKH',
                'DULL',
                'Dull',
                30
            ),
            (
                'SAINI_SIKH',
                'OTHER',
                'Other',
                999
            ),
            (
                'KAMBOJ_SIKH',
                'THIND',
                'Thind',
                10
            ),
            (
                'KAMBOJ_SIKH',
                'SANDHA',
                'Sandha',
                20
            ),
            (
                'KAMBOJ_SIKH',
                'OTHER',
                'Other',
                999
            )
    ) AS seed (
        community_code,
        code,
        name,
        display_order
    ) ON seed.community_code = community.code
ON CONFLICT (community_id, code) DO
UPDATE
SET
    name = EXCLUDED.name,
    display_order = EXCLUDED.display_order,
    is_active = EXCLUDED.is_active,
    updated_at = CURRENT_TIMESTAMP;

-- ============================================================================
-- Moon sign/Raashi master
-- ============================================================================

CREATE TABLE IF NOT EXISTS master_moon_signs (
    id SMALLSERIAL PRIMARY KEY,
    code VARCHAR(30) NOT NULL,
    name VARCHAR(80) NOT NULL,
    english_name VARCHAR(50) NOT NULL,
    display_order SMALLINT NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_master_moon_signs_code UNIQUE (code),
    CONSTRAINT uq_master_moon_signs_name UNIQUE (name)
);

INSERT INTO
    master_moon_signs (
        code,
        name,
        english_name,
        display_order,
        is_active
    )
VALUES (
        'MESH',
        'Mesh',
        'Aries',
        10,
        TRUE
    ),
    (
        'VRISHABH',
        'Vrishabh',
        'Taurus',
        20,
        TRUE
    ),
    (
        'MITHUN',
        'Mithun',
        'Gemini',
        30,
        TRUE
    ),
    (
        'KARK',
        'Kark',
        'Cancer',
        40,
        TRUE
    ),
    (
        'SINGH',
        'Singh',
        'Leo',
        50,
        TRUE
    ),
    (
        'KANYA',
        'Kanya',
        'Virgo',
        60,
        TRUE
    ),
    (
        'TULA',
        'Tula',
        'Libra',
        70,
        TRUE
    ),
    (
        'VRISHCHIK',
        'Vrishchik',
        'Scorpio',
        80,
        TRUE
    ),
    (
        'DHANU',
        'Dhanu',
        'Sagittarius',
        90,
        TRUE
    ),
    (
        'MAKAR',
        'Makar',
        'Capricorn',
        100,
        TRUE
    ),
    (
        'KUMBH',
        'Kumbh',
        'Aquarius',
        110,
        TRUE
    ),
    (
        'MEEN',
        'Meen',
        'Pisces',
        120,
        TRUE
    )
ON CONFLICT (code) DO
UPDATE
SET
    name = EXCLUDED.name,
    english_name = EXCLUDED.english_name,
    display_order = EXCLUDED.display_order,
    is_active = EXCLUDED.is_active,
    updated_at = CURRENT_TIMESTAMP;

-- ============================================================================
-- Birth star/Nakshatra master
-- ============================================================================

CREATE TABLE IF NOT EXISTS master_birth_stars (
    id SMALLSERIAL PRIMARY KEY,
    code VARCHAR(40) NOT NULL,
    name VARCHAR(80) NOT NULL,
    display_order SMALLINT NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_master_birth_stars_code UNIQUE (code),
    CONSTRAINT uq_master_birth_stars_name UNIQUE (name)
);

INSERT INTO
    master_birth_stars (
        code,
        name,
        display_order,
        is_active
    )
VALUES (
        'ASHWINI',
        'Ashwini',
        10,
        TRUE
    ),
    (
        'BHARANI',
        'Bharani',
        20,
        TRUE
    ),
    (
        'KRITTIKA',
        'Krittika',
        30,
        TRUE
    ),
    ('ROHINI', 'Rohini', 40, TRUE),
    (
        'MRIGASHIRA',
        'Mrigashira',
        50,
        TRUE
    ),
    ('ARDRA', 'Ardra', 60, TRUE),
    (
        'PUNARVASU',
        'Punarvasu',
        70,
        TRUE
    ),
    ('PUSHYA', 'Pushya', 80, TRUE),
    (
        'ASHLESHA',
        'Ashlesha',
        90,
        TRUE
    ),
    ('MAGHA', 'Magha', 100, TRUE),
    (
        'PURVA_PHALGUNI',
        'Purva Phalguni',
        110,
        TRUE
    ),
    (
        'UTTARA_PHALGUNI',
        'Uttara Phalguni',
        120,
        TRUE
    ),
    ('HASTA', 'Hasta', 130, TRUE),
    ('CHITRA', 'Chitra', 140, TRUE),
    ('SWATI', 'Swati', 150, TRUE),
    (
        'VISHAKHA',
        'Vishakha',
        160,
        TRUE
    ),
    (
        'ANURADHA',
        'Anuradha',
        170,
        TRUE
    ),
    (
        'JYESHTHA',
        'Jyeshtha',
        180,
        TRUE
    ),
    ('MULA', 'Mula', 190, TRUE),
    (
        'PURVA_ASHADHA',
        'Purva Ashadha',
        200,
        TRUE
    ),
    (
        'UTTARA_ASHADHA',
        'Uttara Ashadha',
        210,
        TRUE
    ),
    (
        'SHRAVANA',
        'Shravana',
        220,
        TRUE
    ),
    (
        'DHANISHTHA',
        'Dhanishtha',
        230,
        TRUE
    ),
    (
        'SHATABHISHA',
        'Shatabhisha',
        240,
        TRUE
    ),
    (
        'PURVA_BHADRAPADA',
        'Purva Bhadrapada',
        250,
        TRUE
    ),
    (
        'UTTARA_BHADRAPADA',
        'Uttara Bhadrapada',
        260,
        TRUE
    ),
    ('REVATI', 'Revati', 270, TRUE)
ON CONFLICT (code) DO
UPDATE
SET
    name = EXCLUDED.name,
    display_order = EXCLUDED.display_order,
    is_active = EXCLUDED.is_active,
    updated_at = CURRENT_TIMESTAMP;

-- ============================================================================
-- Member Sikh and religious details
-- ============================================================================

CREATE TABLE IF NOT EXISTS member_sikh_religious_details (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    community_id SMALLINT NOT NULL,
    subcommunity_id INTEGER NOT NULL,
    birth_hour SMALLINT NOT NULL,
    birth_minute SMALLINT NOT NULL,
    birth_meridiem VARCHAR(2) NOT NULL,
    birth_country_id SMALLINT NOT NULL,
    birth_state_id INTEGER NOT NULL,
    birth_city_id INTEGER NOT NULL,
    gotra VARCHAR(100) NULL,
    moon_sign_id SMALLINT NULL,
    birth_star_id SMALLINT NULL,
    has_dosh VARCHAR(20) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_member_sikh_religious_details_user UNIQUE (user_id),
    CONSTRAINT fk_member_sikh_religious_details_user FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT fk_member_sikh_religious_community FOREIGN KEY (community_id) REFERENCES master_sikh_communities (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_member_sikh_religious_subcommunity FOREIGN KEY (subcommunity_id) REFERENCES master_sikh_subcommunities (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_member_sikh_religious_birth_country FOREIGN KEY (birth_country_id) REFERENCES master_countries (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_member_sikh_religious_birth_state FOREIGN KEY (birth_state_id) REFERENCES master_states (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_member_sikh_religious_birth_city FOREIGN KEY (birth_city_id) REFERENCES master_cities (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_member_sikh_religious_moon_sign FOREIGN KEY (moon_sign_id) REFERENCES master_moon_signs (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_member_sikh_religious_birth_star FOREIGN KEY (birth_star_id) REFERENCES master_birth_stars (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT chk_sikh_religious_birth_hour CHECK (
        birth_hour IS NULL
        OR birth_hour BETWEEN 1 AND 12
    ),
    CONSTRAINT chk_sikh_religious_birth_minute CHECK (
        birth_minute IS NULL
        OR birth_minute BETWEEN 0 AND 59
    ),
    CONSTRAINT chk_sikh_religious_birth_meridiem CHECK (
        birth_meridiem IS NULL
        OR birth_meridiem IN ('AM', 'PM')
    ),
    CONSTRAINT chk_sikh_religious_dosh CHECK (
        has_dosh IS NULL
        OR has_dosh IN (
            'NO',
            'YES',
            'DONT_KNOW',
            'NOT_APPLICABLE'
        )
    )
);

CREATE INDEX IF NOT EXISTS idx_member_sikh_religious_birth_location ON member_sikh_religious_details (
    birth_country_id,
    birth_state_id,
    birth_city_id
);

CREATE TABLE IF NOT EXISTS master_lifestyle_categories (
    id SMALLSERIAL PRIMARY KEY,
    code VARCHAR(40) NOT NULL,
    name VARCHAR(100) NOT NULL,
    icon_class VARCHAR(60) NOT NULL,
    display_order SMALLINT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_master_lifestyle_categories_code UNIQUE (code),
    CONSTRAINT uq_master_lifestyle_categories_name UNIQUE (name)
);

CREATE INDEX IF NOT EXISTS idx_master_lifestyle_categories_active_order ON master_lifestyle_categories (is_active, display_order, id);

CREATE TABLE IF NOT EXISTS master_lifestyle_options (
    id SERIAL PRIMARY KEY,
    lifestyle_category_id SMALLINT NOT NULL,
    code VARCHAR(60) NOT NULL,
    name VARCHAR(100) NOT NULL,
    display_order SMALLINT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_master_lifestyle_options_category FOREIGN KEY (lifestyle_category_id) REFERENCES master_lifestyle_categories (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT uq_master_lifestyle_options_category_code UNIQUE (lifestyle_category_id, code),
    CONSTRAINT uq_master_lifestyle_options_category_name UNIQUE (lifestyle_category_id, name)
);

CREATE INDEX IF NOT EXISTS idx_master_lifestyle_options_category_active_order ON master_lifestyle_options (
    lifestyle_category_id,
    is_active,
    display_order,
    id
);

CREATE TABLE IF NOT EXISTS member_lifestyle_options (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    lifestyle_option_id INTEGER NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_member_lifestyle_options_user FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT fk_member_lifestyle_options_option FOREIGN KEY (lifestyle_option_id) REFERENCES master_lifestyle_options (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT uq_member_lifestyle_user_option UNIQUE (user_id, lifestyle_option_id)
);

CREATE INDEX IF NOT EXISTS idx_member_lifestyle_options_user ON member_lifestyle_options (user_id);

CREATE INDEX IF NOT EXISTS idx_member_lifestyle_options_option ON member_lifestyle_options (lifestyle_option_id);

COMMIT;

BEGIN;

INSERT INTO
    master_lifestyle_categories (
        code,
        name,
        icon_class,
        display_order
    )
VALUES (
        'HOBBIES_INTERESTS',
        'Hobbies & Interests',
        'ri-palette-line',
        10
    ),
    (
        'MUSIC',
        'Music',
        'ri-music-2-line',
        20
    ),
    (
        'READING',
        'Reading',
        'ri-book-open-line',
        30
    ),
    (
        'MOVIES_TV',
        'Movies & TV Shows',
        'ri-movie-2-line',
        40
    ),
    (
        'SPORTS_FITNESS',
        'Sports & Fitness',
        'ri-run-line',
        50
    ),
    (
        'FOOD',
        'Food',
        'ri-restaurant-line',
        60
    )
ON CONFLICT (code) DO
UPDATE
SET
    name = EXCLUDED.name,
    icon_class = EXCLUDED.icon_class,
    display_order = EXCLUDED.display_order,
    is_active = TRUE,
    updated_at = CURRENT_TIMESTAMP;

INSERT INTO
    master_lifestyle_options (
        lifestyle_category_id,
        code,
        name,
        display_order
    )
SELECT category.id, seed.code, seed.name, seed.display_order
FROM
    master_lifestyle_categories AS category
    JOIN (
        VALUES (
                'HOBBIES_INTERESTS',
                'TRAVELLING',
                'Travelling',
                10
            ),
            (
                'HOBBIES_INTERESTS',
                'PHOTOGRAPHY',
                'Photography',
                20
            ),
            (
                'HOBBIES_INTERESTS',
                'COOKING',
                'Cooking',
                30
            ),
            (
                'HOBBIES_INTERESTS',
                'GARDENING',
                'Gardening',
                40
            ),
            (
                'HOBBIES_INTERESTS',
                'PAINTING',
                'Painting',
                50
            ),
            (
                'HOBBIES_INTERESTS',
                'DANCING',
                'Dancing',
                60
            ),
            (
                'HOBBIES_INTERESTS',
                'VOLUNTEERING',
                'Volunteering',
                70
            ),
            (
                'HOBBIES_INTERESTS',
                'PET_LOVER',
                'Pet Lover',
                80
            ),
            (
                'HOBBIES_INTERESTS',
                'TECHNOLOGY',
                'Technology',
                90
            ),
            (
                'HOBBIES_INTERESTS',
                'NATURE',
                'Nature',
                100
            ),
            (
                'MUSIC',
                'PUNJABI',
                'Punjabi',
                10
            ),
            (
                'MUSIC',
                'GURBANI_KIRTAN',
                'Gurbani & Kirtan',
                20
            ),
            (
                'MUSIC',
                'BOLLYWOOD',
                'Bollywood',
                30
            ),
            (
                'MUSIC',
                'CLASSICAL',
                'Classical',
                40
            ),
            ('MUSIC', 'SUFI', 'Sufi', 50),
            ('MUSIC', 'POP', 'Pop', 60),
            ('MUSIC', 'ROCK', 'Rock', 70),
            (
                'MUSIC',
                'INSTRUMENTAL',
                'Instrumental',
                80
            ),
            ('MUSIC', 'FOLK', 'Folk', 90),
            ('MUSIC', 'JAZZ', 'Jazz', 100),
            (
                'READING',
                'FICTION',
                'Fiction',
                10
            ),
            (
                'READING',
                'NON_FICTION',
                'Non-fiction',
                20
            ),
            (
                'READING',
                'BIOGRAPHIES',
                'Biographies',
                30
            ),
            (
                'READING',
                'HISTORY',
                'History',
                40
            ),
            (
                'READING',
                'SPIRITUAL',
                'Spiritual',
                50
            ),
            (
                'READING',
                'POETRY',
                'Poetry',
                60
            ),
            (
                'READING',
                'BUSINESS',
                'Business',
                70
            ),
            (
                'READING',
                'SCIENCE_TECHNOLOGY',
                'Science & Technology',
                80
            ),
            (
                'READING',
                'NEWSPAPERS',
                'Newspapers',
                90
            ),
            (
                'READING',
                'COMICS',
                'Comics',
                100
            ),
            (
                'MOVIES_TV',
                'BOLLYWOOD',
                'Bollywood',
                10
            ),
            (
                'MOVIES_TV',
                'HOLLYWOOD',
                'Hollywood',
                20
            ),
            (
                'MOVIES_TV',
                'PUNJABI_CINEMA',
                'Punjabi Cinema',
                30
            ),
            (
                'MOVIES_TV',
                'COMEDY',
                'Comedy',
                40
            ),
            (
                'MOVIES_TV',
                'ACTION',
                'Action',
                50
            ),
            (
                'MOVIES_TV',
                'ROMANCE',
                'Romance',
                60
            ),
            (
                'MOVIES_TV',
                'THRILLER',
                'Thriller',
                70
            ),
            (
                'MOVIES_TV',
                'DOCUMENTARIES',
                'Documentaries',
                80
            ),
            (
                'MOVIES_TV',
                'WEB_SERIES',
                'Web Series',
                90
            ),
            (
                'MOVIES_TV',
                'SPORTS_SHOWS',
                'Sports Shows',
                100
            ),
            (
                'SPORTS_FITNESS',
                'GYM',
                'Gym',
                10
            ),
            (
                'SPORTS_FITNESS',
                'YOGA',
                'Yoga',
                20
            ),
            (
                'SPORTS_FITNESS',
                'WALKING',
                'Walking',
                30
            ),
            (
                'SPORTS_FITNESS',
                'RUNNING',
                'Running',
                40
            ),
            (
                'SPORTS_FITNESS',
                'CRICKET',
                'Cricket',
                50
            ),
            (
                'SPORTS_FITNESS',
                'BADMINTON',
                'Badminton',
                60
            ),
            (
                'SPORTS_FITNESS',
                'FOOTBALL',
                'Football',
                70
            ),
            (
                'SPORTS_FITNESS',
                'CYCLING',
                'Cycling',
                80
            ),
            (
                'SPORTS_FITNESS',
                'SWIMMING',
                'Swimming',
                90
            ),
            (
                'SPORTS_FITNESS',
                'MEDITATION',
                'Meditation',
                100
            ),
            (
                'FOOD',
                'PUNJABI',
                'Punjabi',
                10
            ),
            (
                'FOOD',
                'NORTH_INDIAN',
                'North Indian',
                20
            ),
            (
                'FOOD',
                'SOUTH_INDIAN',
                'South Indian',
                30
            ),
            (
                'FOOD',
                'CHINESE',
                'Chinese',
                40
            ),
            (
                'FOOD',
                'ITALIAN',
                'Italian',
                50
            ),
            (
                'FOOD',
                'CONTINENTAL',
                'Continental',
                60
            ),
            (
                'FOOD',
                'STREET_FOOD',
                'Street Food',
                70
            ),
            (
                'FOOD',
                'HOME_COOKED',
                'Home-cooked Food',
                80
            ),
            (
                'FOOD',
                'VEGETARIAN',
                'Vegetarian',
                90
            ),
            (
                'FOOD',
                'NON_VEGETARIAN',
                'Non-vegetarian',
                100
            )
    ) AS seed (
        category_code,
        code,
        name,
        display_order
    ) ON category.code = seed.category_code
ON CONFLICT (lifestyle_category_id, code) DO
UPDATE
SET
    name = EXCLUDED.name,
    display_order = EXCLUDED.display_order,
    is_active = TRUE,
    updated_at = CURRENT_TIMESTAMP;

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
    CONSTRAINT uq_member_photos_uuid UNIQUE (uuid),
    CONSTRAINT fk_member_photos_member FOREIGN KEY (member_id) REFERENCES users (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT chk_member_photos_media_type CHECK (
        media_type IN ('PROFILE_PHOTO')
    ),
    CONSTRAINT chk_member_photos_status CHECK (
        status IN (
            'PENDING',
            'APPROVED',
            'REJECTED',
            'DELETED'
        )
    ),
    CONSTRAINT chk_member_photos_visibility CHECK (
        visibility IN (
            'PUBLIC',
            'INTERESTED_MEMBERS'
        )
    ),
    CONSTRAINT chk_member_photos_uploaded_by_type CHECK (
        uploaded_by_type IN ('MEMBER', 'ADMIN')
    ),
    CONSTRAINT chk_member_photos_original_mime CHECK (
        original_mime_type IN (
            'image/jpeg',
            'image/png',
            'image/webp'
        )
    ),
    CONSTRAINT chk_member_photos_size CHECK (
        original_file_size > 0
        AND original_width > 0
        AND original_height > 0
    )
);

CREATE INDEX IF NOT EXISTS idx_member_photos_member_status ON member_photos (
    member_id,
    status,
    created_at DESC
)
WHERE
    deleted_at IS NULL;

CREATE INDEX IF NOT EXISTS idx_member_photos_member_visibility ON member_photos (member_id, visibility)
WHERE
    deleted_at IS NULL;

CREATE UNIQUE INDEX IF NOT EXISTS uq_member_photos_one_primary ON member_photos (member_id)
WHERE
    is_primary = TRUE
    AND deleted_at IS NULL;

CREATE INDEX IF NOT EXISTS idx_member_photos_pending_member ON member_photos (member_id, created_at DESC)
WHERE
    status = 'PENDING'
    AND deleted_at IS NULL;

-- Speeds up individual approval and rejection operations.
CREATE INDEX IF NOT EXISTS idx_member_photos_pending_id ON member_photos (id)
WHERE
    status = 'PENDING'
    AND deleted_at IS NULL;

-- Supports lookup of active member photos inside the carousel.
CREATE INDEX IF NOT EXISTS idx_member_photos_member_active ON member_photos (
    member_id,
    is_primary DESC,
    created_at DESC
)
WHERE
    deleted_at IS NULL
    AND status <> 'DELETED';

-- Search by profile reference number.
CREATE INDEX IF NOT EXISTS idx_users_profile_ref_number_search ON users (profile_ref_number)
WHERE
    deleted_at IS NULL;

-- Case-insensitive member-name search.
CREATE INDEX IF NOT EXISTS idx_users_full_name_lower_search ON users (LOWER(full_name))
WHERE
    deleted_at IS NULL;

COMMIT;

BEGIN;

CREATE INDEX IF NOT EXISTS idx_member_photos_pending_member_created ON member_photos (member_id, created_at ASC)
WHERE
    status = 'PENDING'
    AND deleted_at IS NULL;

CREATE INDEX IF NOT EXISTS idx_users_profile_ref_active ON users (profile_ref_number)
WHERE
    deleted_at IS NULL;

CREATE INDEX IF NOT EXISTS idx_users_full_name_lower_active ON users (LOWER(full_name))
WHERE
    deleted_at IS NULL;

CREATE INDEX IF NOT EXISTS idx_contact_verifications_contact_purpose_status ON contact_verifications (
    user_contact_id,
    purpose,
    status,
    id DESC
);

ALTER TABLE contact_verifications
DROP CONSTRAINT IF EXISTS contact_verifications_status_check;

ALTER TABLE contact_verifications
ADD CONSTRAINT contact_verifications_status_check CHECK (
    status IN (
        'PENDING',
        'VERIFIED',
        'EXPIRED',
        'CANCELLED',
        'DELIVERY_FAILED'
    )
);

ALTER TABLE contact_verifications
DROP CONSTRAINT IF EXISTS chk_contact_verification_purpose;

ALTER TABLE contact_verifications
ADD CONSTRAINT chk_contact_verification_purpose CHECK (
    purpose IN (
        'REGISTER',
        'PASSWORD_RESET',
        'PENDING',
        'VERIFIED',
        'EXPIRED',
        'CANCELLED'
    )
);

CREATE TABLE IF NOT EXISTS member_notifications (
    id BIGSERIAL PRIMARY KEY,
    recipient_user_id BIGINT NOT NULL,
    actor_user_id BIGINT NULL,
    notification_type VARCHAR(40) NOT NULL,
    title VARCHAR(150) NOT NULL,
    message VARCHAR(500) NOT NULL,
    entity_type VARCHAR(40) NULL,
    entity_id BIGINT NULL,
    target_url VARCHAR(255) NULL,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_member_notifications_recipient FOREIGN KEY (recipient_user_id) REFERENCES users (id) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT fk_member_notifications_actor FOREIGN KEY (actor_user_id) REFERENCES users (id) ON UPDATE RESTRICT ON DELETE SET NULL,
    CONSTRAINT chk_member_notifications_type CHECK (
        notification_type IN (
            'MESSAGE',
            'INTEREST_RECEIVED',
            'INTEREST_ACCEPTED',
            'INTEREST_REJECTED',
            'PROFILE_VIEW',
            'SHORTLISTED',
            'SYSTEM'
        )
    ),
    CONSTRAINT chk_member_notifications_title CHECK (BTRIM(title) <> ''),
    CONSTRAINT chk_member_notifications_message CHECK (BTRIM(message) <> '')
);

CREATE INDEX IF NOT EXISTS idx_member_notifications_recipient_unread ON member_notifications (
    recipient_user_id,
    created_at DESC
)
WHERE
    read_at IS NULL;

CREATE INDEX IF NOT EXISTS idx_member_notifications_recipient_type_unread ON member_notifications (
    recipient_user_id,
    notification_type,
    created_at DESC
)
WHERE
    read_at IS NULL;

CREATE INDEX IF NOT EXISTS idx_member_notifications_recipient_created ON member_notifications (
    recipient_user_id,
    created_at DESC
);

CREATE INDEX IF NOT EXISTS idx_member_notifications_entity ON member_notifications (entity_type, entity_id)
WHERE
    entity_type IS NOT NULL
    AND entity_id IS NOT NULL;

ALTER TABLE member_notifications
DROP CONSTRAINT IF EXISTS chk_member_notifications_type;

ALTER TABLE member_notifications
ADD CONSTRAINT chk_member_notifications_type CHECK (
    notification_type IN (
        'MESSAGE',
        'INTEREST_RECEIVED',
        'INTEREST_ACCEPTED',
        'INTEREST_REJECTED',
        'PROFILE_VIEW',
        'SHORTLISTED',
        'PHOTO_REJECTED',
        'SYSTEM'
    )
);

CREATE INDEX IF NOT EXISTS idx_member_notifications_read_cleanup ON member_notifications (read_at)
WHERE
    read_at IS NOT NULL;

CREATE TABLE IF NOT EXISTS master_family_values (
    id SMALLSERIAL PRIMARY KEY,
    code VARCHAR(40) NOT NULL,
    name VARCHAR(80) NOT NULL,
    display_order SMALLINT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_master_family_values_code UNIQUE (code),
    CONSTRAINT uq_master_family_values_name UNIQUE (name)
);

CREATE INDEX IF NOT EXISTS idx_master_family_values_active_order ON master_family_values (is_active, display_order);

INSERT INTO
    master_family_values (code, name, display_order)
VALUES ('ORTHODOX', 'Orthodox', 10),
    (
        'TRADITIONAL',
        'Traditional',
        20
    ),
    ('MODERATE', 'Moderate', 30),
    ('LIBERAL', 'Liberal', 40)
ON CONFLICT (code) DO NOTHING;

CREATE TABLE IF NOT EXISTS master_family_types (
    id SMALLSERIAL PRIMARY KEY,
    code VARCHAR(40) NOT NULL,
    name VARCHAR(80) NOT NULL,
    display_order SMALLINT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_master_family_types_code UNIQUE (code),
    CONSTRAINT uq_master_family_types_name UNIQUE (name)
);

CREATE INDEX IF NOT EXISTS idx_master_family_types_active_order ON master_family_types (is_active, display_order);

INSERT INTO
    master_family_types (code, name, display_order)
VALUES (
        'JOINT_FAMILY',
        'Joint Family',
        10
    ),
    (
        'NUCLEAR_FAMILY',
        'Nuclear Family',
        20
    ),
    ('OTHERS', 'Others', 30)
ON CONFLICT (code) DO NOTHING;

CREATE TABLE IF NOT EXISTS master_family_statuses (
    id SMALLSERIAL PRIMARY KEY,
    code VARCHAR(40) NOT NULL,
    name VARCHAR(80) NOT NULL,
    display_order SMALLINT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_master_family_statuses_code UNIQUE (code),
    CONSTRAINT uq_master_family_statuses_name UNIQUE (name)
);

CREATE INDEX IF NOT EXISTS idx_master_family_statuses_active_order ON master_family_statuses (is_active, display_order);

INSERT INTO
    master_family_statuses (code, name, display_order)
VALUES (
        'MIDDLE_CLASS',
        'Middle Class',
        10
    ),
    (
        'UPPER_MIDDLE_CLASS',
        'Upper Middle Class',
        20
    ),
    (
        'HIGH_CLASS',
        'High Class',
        30
    ),
    (
        'RICH_AFFLUENT',
        'Rich / Affluent',
        40
    )
ON CONFLICT (code) DO NOTHING;

ALTER TABLE member_family_details
ADD COLUMN IF NOT EXISTS family_value_id SMALLINT,
ADD COLUMN IF NOT EXISTS family_type_id SMALLINT,
ADD COLUMN IF NOT EXISTS family_status_id SMALLINT,
ADD COLUMN IF NOT EXISTS community_id INTEGER,
ADD COLUMN IF NOT EXISTS subcommunity_id INTEGER;

UPDATE member_family_details mf
SET
    family_value_id = mv.id
FROM master_family_values mv
WHERE
    mv.code = mf.family_value;

UPDATE member_family_details mf
SET
    family_type_id = mt.id
FROM master_family_types mt
WHERE
    mt.code = mf.family_type;

UPDATE member_family_details mf
SET
    family_status_id = ms.id
FROM master_family_statuses ms
WHERE
    ms.code = mf.family_status;

UPDATE member_family_details mf
SET
    community_id = sr.community_id,
    subcommunity_id = sr.subcommunity_id
FROM
    member_sikh_religious_details sr
WHERE
    sr.user_id = mf.user_id;

ALTER TABLE member_family_details
ADD CONSTRAINT fk_member_family_value FOREIGN KEY (family_value_id) REFERENCES master_family_values (id);

ALTER TABLE member_family_details
ADD CONSTRAINT fk_member_family_type FOREIGN KEY (family_type_id) REFERENCES master_family_types (id);

ALTER TABLE member_family_details
ADD CONSTRAINT fk_member_family_status FOREIGN KEY (family_status_id) REFERENCES master_family_statuses (id);

ALTER TABLE member_family_details
ADD CONSTRAINT fk_member_family_community FOREIGN KEY (community_id) REFERENCES master_sikh_communities (id);

ALTER TABLE member_family_details
ADD CONSTRAINT fk_member_family_subcommunity FOREIGN KEY (subcommunity_id) REFERENCES master_sikh_subcommunities (id);

CREATE INDEX IF NOT EXISTS idx_member_family_value ON member_family_details (family_value_id);

CREATE INDEX IF NOT EXISTS idx_member_family_type ON member_family_details (family_type_id);

CREATE INDEX IF NOT EXISTS idx_member_family_status ON member_family_details (family_status_id);

CREATE INDEX IF NOT EXISTS idx_member_family_community ON member_family_details (community_id);

CREATE INDEX IF NOT EXISTS idx_member_family_subcommunity ON member_family_details (subcommunity_id);

ALTER TABLE member_family_details
DROP COLUMN family_value,
DROP COLUMN family_type,
DROP COLUMN family_status;

ALTER TABLE member_family_details
ADD COLUMN IF NOT EXISTS father_name VARCHAR(150) NULL;

ALTER TABLE member_family_details
ADD COLUMN IF NOT EXISTS mother_name VARCHAR(150) NULL;

/*
 * Existing married-sibling information is no longer part of the
 * application and is deliberately discarded.
 */
ALTER TABLE member_family_details
DROP COLUMN IF EXISTS married_brothers_count;

ALTER TABLE member_family_details
DROP COLUMN IF EXISTS married_sisters_count;

CREATE SEQUENCE IF NOT EXISTS field_officer_code_seq START
WITH
    1 INCREMENT BY 1 MINVALUE 1 MAXVALUE 999999 NO CYCLE;

CREATE TABLE IF NOT EXISTS field_officers (
    id BIGSERIAL PRIMARY KEY,
    officer_code VARCHAR(11) NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    mobile_number VARCHAR(15) NOT NULL,
    country_id SMALLINT NOT NULL,
    state_id INTEGER NOT NULL,
    city_id INTEGER NOT NULL,
    address VARCHAR(500) NULL,
    upi_id VARCHAR(150) NULL,
    account_status VARCHAR(20),
    created_by BIGINT NOT NULL,
    activated_at TIMESTAMP NULL,
    deactivated_at TIMESTAMP NULL,
    updated_by BIGINT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT uq_field_officers_code UNIQUE (officer_code),
    CONSTRAINT uq_field_officers_mobile UNIQUE (mobile_number),
    CONSTRAINT fk_field_officers_country FOREIGN KEY (country_id) REFERENCES master_countries (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_field_officers_state FOREIGN KEY (state_id) REFERENCES master_states (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_field_officers_city FOREIGN KEY (city_id) REFERENCES master_cities (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_field_officers_created_by FOREIGN KEY (created_by) REFERENCES admin_users (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_field_officers_updated_by FOREIGN KEY (updated_by) REFERENCES admin_users (id) ON UPDATE RESTRICT ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS idx_field_officers_location ON field_officers (country_id, state_id, city_id)
WHERE
    deleted_at IS NULL;

CREATE INDEX IF NOT EXISTS idx_field_officers_name ON field_officers (full_name)
WHERE
    deleted_at IS NULL;

ALTER TABLE field_officers
ADD CONSTRAINT chk_field_officers_status CHECK (
    account_status IN ('ACTIVE', 'INACTIVE')
);

CREATE INDEX IF NOT EXISTS idx_field_officers_status ON field_officers (
    account_status,
    created_at DESC
)
WHERE
    deleted_at IS NULL;

ALTER TABLE field_officers
ADD CONSTRAINT uq_field_officers_officer_code UNIQUE (officer_code);


CREATE TABLE IF NOT EXISTS prelaunch_profiles (
    id BIGSERIAL PRIMARY KEY,

    profile_reference VARCHAR(30) NOT NULL,

-- Registration / identity
profile_created_for VARCHAR(30) NOT NULL,
gender VARCHAR(20) NOT NULL,
full_name VARCHAR(100) NOT NULL,
date_of_birth DATE NOT NULL,

-- Contact details
email VARCHAR(190) NOT NULL,
country_code VARCHAR(8) NOT NULL DEFAULT '+91',
mobile_number VARCHAR(20) NOT NULL,

-- Basic details
marital_status_id INTEGER NOT NULL,
height_id INTEGER NOT NULL,
mother_tongue_id INTEGER NOT NULL,
country_id INTEGER NOT NULL,
state_id INTEGER NOT NULL,
city_id INTEGER NOT NULL,

-- Education and profession
highest_education_id INTEGER NOT NULL,
employed_in VARCHAR(40) NOT NULL,
occupation_id INTEGER NOT NULL,

-- Family details
father_name VARCHAR(100) NOT NULL,
mother_name VARCHAR(100) NOT NULL,
family_value_id INTEGER NOT NULL,
family_type_id INTEGER NOT NULL,
family_status_id INTEGER NOT NULL,
sikh_community_id INTEGER NOT NULL,
sikh_subcommunity_id INTEGER NOT NULL,

-- Pre-launch operational details
field_officer_id BIGINT NOT NULL,
created_by BIGINT NOT NULL,
created_source VARCHAR(30) NOT NULL DEFAULT 'FIELD_OFFICER',
is_prelaunch_profile BOOLEAN NOT NULL DEFAULT TRUE,
status VARCHAR(20) NOT NULL DEFAULT 'DRAFT',

-- Admin review details
reviewed_by BIGINT NULL,
    reviewed_at TIMESTAMP NULL,
    rejection_reason TEXT NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    CONSTRAINT uq_prelaunch_profiles_reference
        UNIQUE (profile_reference),

    CONSTRAINT chk_prelaunch_profile_created_for
        CHECK (
            profile_created_for IN (
                'SELF',
                'SON',
                'DAUGHTER',
                'BROTHER',
                'SISTER',
                'RELATIVE',
                'FRIEND'
            )
        ),

    CONSTRAINT chk_prelaunch_profile_gender
        CHECK (
            gender IN (
                'MALE',
                'FEMALE'
            )
        ),

    CONSTRAINT chk_prelaunch_employed_in
        CHECK (
            employed_in IN (
                'GOVERNMENT_PSU',
                'PRIVATE',
                'BUSINESS',
                'DEFENSE',
                'SELF_EMPLOYED',
                'NOT_WORKING'
            )
        ),

    CONSTRAINT chk_prelaunch_profile_status
        CHECK (
            status IN (
                'DRAFT',
                'APPROVED',
                'REJECTED'
            )
        ),

    CONSTRAINT chk_prelaunch_created_source
        CHECK (
            created_source = 'FIELD_OFFICER'
        ),

    CONSTRAINT chk_prelaunch_profile_flag
        CHECK (
            is_prelaunch_profile = TRUE
        ),

    CONSTRAINT chk_prelaunch_country_code
        CHECK (
            country_code ~ '^\+[1-9][0-9]{0,3}$'
        ),

    CONSTRAINT chk_prelaunch_mobile_number
        CHECK (
            mobile_number ~ '^[0-9]{10,15}$'
        ),

    CONSTRAINT chk_prelaunch_email_format
        CHECK (
            email ~* '^[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}$'
        ),

    CONSTRAINT fk_prelaunch_marital_status
        FOREIGN KEY (marital_status_id)
        REFERENCES master_marital_statuses(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_prelaunch_height
        FOREIGN KEY (height_id)
        REFERENCES master_heights(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_prelaunch_mother_tongue
        FOREIGN KEY (mother_tongue_id)
        REFERENCES master_mother_tongues(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_prelaunch_country
        FOREIGN KEY (country_id)
        REFERENCES master_countries(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_prelaunch_state
        FOREIGN KEY (state_id)
        REFERENCES master_states(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_prelaunch_city
        FOREIGN KEY (city_id)
        REFERENCES master_cities(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_prelaunch_education
        FOREIGN KEY (highest_education_id)
        REFERENCES master_educations(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_prelaunch_occupation
        FOREIGN KEY (occupation_id)
        REFERENCES master_occupations(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_prelaunch_family_value
        FOREIGN KEY (family_value_id)
        REFERENCES master_family_values(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_prelaunch_family_type
        FOREIGN KEY (family_type_id)
        REFERENCES master_family_types(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_prelaunch_family_status
        FOREIGN KEY (family_status_id)
        REFERENCES master_family_statuses(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_prelaunch_sikh_community
        FOREIGN KEY (sikh_community_id)
        REFERENCES master_sikh_communities(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_prelaunch_sikh_subcommunity
        FOREIGN KEY (sikh_subcommunity_id)
        REFERENCES master_sikh_subcommunities(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_prelaunch_field_officer
        FOREIGN KEY (field_officer_id)
        REFERENCES field_officers(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT
);

-- ============================================================
-- 2. UNIQUE EMAIL AND MOBILE
-- Soft-deleted records do not block reuse.
-- Email uniqueness is case-insensitive.
-- ============================================================

CREATE UNIQUE INDEX IF NOT EXISTS uq_prelaunch_profiles_active_email ON prelaunch_profiles (LOWER(email))
WHERE
    deleted_at IS NULL;

CREATE UNIQUE INDEX IF NOT EXISTS uq_prelaunch_profiles_active_mobile ON prelaunch_profiles (country_code, mobile_number)
WHERE
    deleted_at IS NULL;

-- ============================================================
-- 3. PERFORMANCE INDEXES
-- ============================================================

CREATE INDEX IF NOT EXISTS idx_prelaunch_profiles_status_created ON prelaunch_profiles (status, created_at DESC)
WHERE
    deleted_at IS NULL;

CREATE INDEX IF NOT EXISTS idx_prelaunch_profiles_field_officer ON prelaunch_profiles (
    field_officer_id,
    created_at DESC
)
WHERE
    deleted_at IS NULL;

CREATE INDEX IF NOT EXISTS idx_prelaunch_profiles_reviewed_by ON prelaunch_profiles (reviewed_by)
WHERE
    reviewed_by IS NOT NULL
    AND deleted_at IS NULL;

CREATE INDEX IF NOT EXISTS idx_prelaunch_profiles_location ON prelaunch_profiles (country_id, state_id, city_id)
WHERE
    deleted_at IS NULL;

-- ============================================================
-- 4. PRE-LAUNCH PHOTOS
-- Stores only secure writable-folder relative paths.
-- It does not store publicly accessible URLs.
-- ============================================================


CREATE TABLE IF NOT EXISTS prelaunch_photos (
    id BIGSERIAL PRIMARY KEY,

    prelaunch_profile_id BIGINT NOT NULL,

-- Exactly three sequence positions are allowed.
sequence_no SMALLINT NOT NULL,

-- Paths relative to the CI4 writable directory.
original_path VARCHAR(500) NOT NULL,
    medium_path VARCHAR(500) NOT NULL,
    thumbnail_path VARCHAR(500) NOT NULL,

    original_filename VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_extension VARCHAR(10) NOT NULL,
    file_size_bytes BIGINT NOT NULL,

    width_px INTEGER NOT NULL,
    height_px INTEGER NOT NULL,

    checksum_sha256 CHAR(64) NOT NULL,

    approval_status VARCHAR(20) NOT NULL DEFAULT 'PENDING',

    reviewed_by BIGINT NULL,
    reviewed_at TIMESTAMP NULL,
    rejection_reason TEXT NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    CONSTRAINT uq_prelaunch_photo_sequence
        UNIQUE (
            prelaunch_profile_id,
            sequence_no
        ),

    CONSTRAINT chk_prelaunch_photo_sequence
        CHECK (
            sequence_no BETWEEN 1 AND 3
        ),

    CONSTRAINT chk_prelaunch_photo_status
        CHECK (
            approval_status IN (
                'PENDING',
                'APPROVED',
                'REJECTED'
            )
        ),

    CONSTRAINT chk_prelaunch_photo_file_size
        CHECK (
            file_size_bytes > 0
            AND file_size_bytes <= 5242880
        ),

    CONSTRAINT chk_prelaunch_photo_dimensions
        CHECK (
            width_px >= 400
            AND height_px >= 400
        ),

    CONSTRAINT chk_prelaunch_photo_extension
        CHECK (
            LOWER(file_extension) IN (
                'jpg',
                'jpeg',
                'png',
                'webp'
            )
        ),

    CONSTRAINT chk_prelaunch_photo_mime
        CHECK (
            mime_type IN (
                'image/jpeg',
                'image/png',
                'image/webp'
            )
        ),

    CONSTRAINT chk_prelaunch_photo_checksum
        CHECK (
            checksum_sha256 ~ '^[a-fA-F0-9]{64}$'
        ),

    CONSTRAINT fk_prelaunch_photo_profile
        FOREIGN KEY (prelaunch_profile_id)
        REFERENCES prelaunch_profiles(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

-- ============================================================
-- 5. PHOTO PERFORMANCE INDEXES
-- ============================================================

CREATE INDEX IF NOT EXISTS idx_prelaunch_photos_profile ON prelaunch_photos (
    prelaunch_profile_id,
    sequence_no
)
WHERE
    deleted_at IS NULL;

CREATE INDEX IF NOT EXISTS idx_prelaunch_photos_approval_status ON prelaunch_photos (
    approval_status,
    created_at DESC
)
WHERE
    deleted_at IS NULL;

CREATE INDEX IF NOT EXISTS idx_prelaunch_photos_reviewed_by ON prelaunch_photos (reviewed_by)
WHERE
    reviewed_by IS NOT NULL
    AND deleted_at IS NULL;

-- Prevent the same physical image from being uploaded more than once
-- for the same pre-launch profile.
CREATE UNIQUE INDEX IF NOT EXISTS uq_prelaunch_photo_profile_checksum ON prelaunch_photos (
    prelaunch_profile_id,
    checksum_sha256
)
WHERE
    deleted_at IS NULL;

-- ============================================================
-- 6. UPDATED_AT TRIGGER FUNCTION
-- Reuse an existing project trigger function if already available.
-- ============================================================

CREATE OR REPLACE FUNCTION set_current_updated_at()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$;

DROP TRIGGER IF EXISTS trg_prelaunch_profiles_updated_at ON prelaunch_profiles;

CREATE TRIGGER trg_prelaunch_profiles_updated_at
BEFORE UPDATE
ON prelaunch_profiles
FOR EACH ROW
EXECUTE FUNCTION set_current_updated_at();

DROP TRIGGER IF EXISTS trg_prelaunch_photos_updated_at ON prelaunch_photos;

CREATE TRIGGER trg_prelaunch_photos_updated_at
BEFORE UPDATE
ON prelaunch_photos
FOR EACH ROW
EXECUTE FUNCTION set_current_updated_at();

ALTER TABLE prelaunch_profiles
ADD COLUMN IF NOT EXISTS gotra VARCHAR(100) NULL;

-- Gotra is mandatory for every prelaunch profile.
ALTER TABLE prelaunch_profiles ALTER COLUMN gotra SET NOT NULL;

-- Optional DB-level protection against blank strings.
ALTER TABLE prelaunch_profiles
DROP CONSTRAINT IF EXISTS chk_prelaunch_profiles_gotra_not_blank;

-- Remove indexes whose only purpose is one of the removed columns.
-- Replace/add names here if your original migration used different names.
DROP INDEX IF EXISTS idx_prelaunch_profiles_mother_tongue_id;

DROP INDEX IF EXISTS idx_prelaunch_profiles_family_value_id;

DROP INDEX IF EXISTS idx_prelaunch_profiles_family_type_id;

DROP INDEX IF EXISTS idx_prelaunch_profiles_family_status_id;

-- Remove the obsolete data columns from only the prelaunch table.
ALTER TABLE prelaunch_profiles
DROP COLUMN IF EXISTS mother_tongue_id,
DROP COLUMN IF EXISTS family_value_id,
DROP COLUMN IF EXISTS family_type_id,
DROP COLUMN IF EXISTS family_status_id;

ALTER TABLE member_family_details
ADD COLUMN IF NOT EXISTS gotra VARCHAR(100) NULL;

ALTER TABLE member_family_details
ALTER COLUMN family_value_id
DROP NOT NULL,
ALTER COLUMN family_type_id
DROP NOT NULL,
ALTER COLUMN family_status_id
DROP NOT NULL;

INSERT INTO
    master_sikh_communities (
        code,
        name,
        display_order,
        is_active
    )
VALUES (
        'BRAHMIN_SIKH',
        'Brahmin Sikh',
        130,
        TRUE
    ),
    (
        'RAJPUT_SIKH',
        'Rajput Sikh',
        140,
        TRUE
    ),
    (
        'BANIA_SIKH',
        'Bania Sikh',
        150,
        TRUE
    ),
    (
        'BHATRA_SINGH',
        'Bhatra Sikh',
        160,
        TRUE
    ),
    (
        'TARKHAN_SIKH',
        'Tarkhan Sikh',
        170,
        TRUE
    ),
    (
        'LOHAR_SIKH',
        'Lohar Sikh',
        180,
        TRUE
    ),
    (
        'SUNIAR_SIKH',
        'Suniar Sikh',
        190,
        TRUE
    ),
    (
        'CHHIMBA_SIKH',
        'Chhimba Sikh',
        200,
        TRUE
    ),
    (
        'KASHYAP_SIKH',
        'Kashyap Sikh',
        210,
        TRUE
    ),
    (
        'KUMHAR_SIKH',
        'Kumhar Sikh',
        220,
        TRUE
    ),
    (
        'NAI_SIKH',
        'Nai Sikh',
        230,
        TRUE
    ),
    (
        'DHOBI_SIKH',
        'Dhobi Sikh',
        240,
        TRUE
    ),
    (
        'TELI_SIKH',
        'Teli Sikh',
        250,
        TRUE
    ),
    (
        'KALAL_SIKH',
        'Kalal Sikh',
        260,
        TRUE
    ),
    (
        'JULAHA_SIKH',
        'Julaha Sikh',
        270,
        TRUE
    ),
    (
        'BAZIGAR_SIKH',
        'Bazigar Sikh',
        280,
        TRUE
    ),
    (
        'SIKLIGAR_SIKH',
        'Sikligar Sikh',
        290,
        TRUE
    ),
    (
        'SANSI_SIKH',
        'Sansi Sikh',
        300,
        TRUE
    ),
    (
        'BAWARIA_SIKH',
        'Bawaria Sikh',
        310,
        TRUE
    ),
    (
        'MAHTAM_SIKH',
        'Mahtam Sikh',
        320,
        TRUE
    ),
    (
        'MIRASI_SIKH',
        'Mirasi Sikh',
        330,
        TRUE
    ),
    (
        'MOCHI_SIKH',
        'Mochi Sikh',
        340,
        TRUE
    ),
    (
        'MEGH_SIKH',
        'Megh Sikh',
        350,
        TRUE
    ),
    (
        'AD_DHARMI_SIKH',
        'Ad-Dharmi Sikh',
        360,
        TRUE
    ),
    (
        'BALMIKI_SIKH',
        'Balmiki Sikh',
        370,
        TRUE
    )
ON CONFLICT (code) DO
UPDATE
SET
    name = EXCLUDED.name,
    display_order = EXCLUDED.display_order,
    is_active = EXCLUDED.is_active,
    updated_at = CURRENT_TIMESTAMP;

DO $$
DECLARE
    constraint_record RECORD;
BEGIN
    FOR constraint_record IN
        SELECT
            ns.nspname AS schema_name,
            tbl.relname AS table_name,
            con.conname AS constraint_name
        FROM pg_constraint con
        JOIN pg_class tbl
            ON tbl.oid = con.conrelid
        JOIN pg_namespace ns
            ON ns.oid = tbl.relnamespace
        WHERE con.contype = 'f'
          AND con.confrelid =
              to_regclass('public.master_sikh_subcommunities')
    LOOP
        EXECUTE format(
            'ALTER TABLE %I.%I DROP CONSTRAINT IF EXISTS %I',
            constraint_record.schema_name,
            constraint_record.table_name,
            constraint_record.constraint_name
        );
    END LOOP;
END
$$;

-- -------------------------------------------------------------------------
-- 2. Remove any separately created indexes involving Sub-community columns.
--    PostgreSQL already drops indexes owned by a dropped constraint/column,
--    but these statements handle known standalone index names if present.
-- -------------------------------------------------------------------------
DROP INDEX IF EXISTS public.idx_member_family_details_subcommunity_id;

DROP INDEX IF EXISTS public.idx_member_sikh_religious_details_subcommunity_id;

DROP INDEX IF EXISTS public.idx_prelaunch_profiles_sikh_subcommunity_id;

DROP INDEX IF EXISTS public.idx_master_sikh_subcommunities_community_id;

DROP INDEX IF EXISTS public.idx_master_sikh_subcommunities_community_active_order;

-- -------------------------------------------------------------------------
-- 3. Remove Sub-community columns from every application table.
-- -------------------------------------------------------------------------
ALTER TABLE public.member_family_details
DROP COLUMN IF EXISTS subcommunity_id;

ALTER TABLE public.member_sikh_religious_details
DROP COLUMN IF EXISTS subcommunity_id;

ALTER TABLE public.prelaunch_profiles
DROP COLUMN IF EXISTS sikh_subcommunity_id;

-- -------------------------------------------------------------------------
-- 4. Remove the obsolete master table.
--    CASCADE acts as a final safeguard for any unknown remaining dependency.
-- -------------------------------------------------------------------------
DROP TABLE IF EXISTS public.master_sikh_subcommunities CASCADE;

TRUNCATE TABLE master_states RESTART IDENTITY CASCADE;

TRUNCATE TABLE master_cities RESTART IDENTITY CASCADE;

DO $$
DECLARE
    v_country_id SMALLINT := 1;
BEGIN
    INSERT INTO master_states (
        country_id, code, name, display_order, is_active, created_at, updated_at
    )
    VALUES
        (v_country_id, 'AN', 'Andaman and Nicobar Islands', 1, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
        (v_country_id, 'AP', 'Andhra Pradesh', 2, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
        (v_country_id, 'AR', 'Arunachal Pradesh', 3, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
        (v_country_id, 'AS', 'Assam', 4, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
        (v_country_id, 'BR', 'Bihar', 5, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
        (v_country_id, 'CH', 'Chandigarh', 6, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
        (v_country_id, 'CG', 'Chhattisgarh', 7, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
        (v_country_id, 'DN', 'Dadra and Nagar Haveli and Daman and Diu', 8, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
        (v_country_id, 'DL', 'Delhi', 9, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
        (v_country_id, 'GA', 'Goa', 10, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
        (v_country_id, 'GJ', 'Gujarat', 11, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
        (v_country_id, 'HR', 'Haryana', 12, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
        (v_country_id, 'HP', 'Himachal Pradesh', 13, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
        (v_country_id, 'JK', 'Jammu and Kashmir', 14, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
        (v_country_id, 'JH', 'Jharkhand', 15, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
        (v_country_id, 'KA', 'Karnataka', 16, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
        (v_country_id, 'KL', 'Kerala', 17, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
        (v_country_id, 'LA', 'Ladakh', 18, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
        (v_country_id, 'LD', 'Lakshadweep', 19, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
        (v_country_id, 'MP', 'Madhya Pradesh', 20, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
        (v_country_id, 'MH', 'Maharashtra', 21, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
        (v_country_id, 'MN', 'Manipur', 22, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
        (v_country_id, 'ML', 'Meghalaya', 23, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
        (v_country_id, 'MZ', 'Mizoram', 24, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
        (v_country_id, 'NL', 'Nagaland', 25, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
        (v_country_id, 'OD', 'Odisha', 26, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
        (v_country_id, 'PY', 'Puducherry', 27, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
        (v_country_id, 'PB', 'Punjab', 28, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
        (v_country_id, 'RJ', 'Rajasthan', 29, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
        (v_country_id, 'SK', 'Sikkim', 30, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
        (v_country_id, 'TN', 'Tamil Nadu', 31, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
        (v_country_id, 'TS', 'Telangana', 32, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
        (v_country_id, 'TR', 'Tripura', 33, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
        (v_country_id, 'UP', 'Uttar Pradesh', 34, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
        (v_country_id, 'UK', 'Uttarakhand', 35, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
        (v_country_id, 'WB', 'West Bengal', 36, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
    ON CONFLICT (country_id, code) DO UPDATE
    SET name = EXCLUDED.name,
        display_order = EXCLUDED.display_order,
        is_active = EXCLUDED.is_active,
        updated_at = CURRENT_TIMESTAMP;

    INSERT INTO master_cities (
        state_id, name, display_order, is_active, created_at, updated_at
    )
    SELECT
        ms.id,
        d.district_name,
        d.display_order,
        TRUE,
        CURRENT_TIMESTAMP,
        CURRENT_TIMESTAMP
    FROM (VALUES
        ('AN', 'Nicobars', 1),
        ('AN', 'North And Middle Andaman', 2),
        ('AN', 'South Andamans', 3),
        ('AP', 'Alluri Sitharama Raju', 1),
        ('AP', 'Anakapalli', 2),
        ('AP', 'Anantapur', 3),
        ('AP', 'Annamayya', 4),
        ('AP', 'Bapatla', 5),
        ('AP', 'Chittoor', 6),
        ('AP', 'East Godavari', 7),
        ('AP', 'Eluru', 8),
        ('AP', 'Guntur', 9),
        ('AP', 'Kakinada', 10),
        ('AP', 'Konaseema', 11),
        ('AP', 'Krishna', 12),
        ('AP', 'Kurnool', 13),
        ('AP', 'Nandyal', 14),
        ('AP', 'NTR', 15),
        ('AP', 'Palnadu', 16),
        ('AP', 'Parvathipuram Manyam', 17),
        ('AP', 'Prakasam', 18),
        ('AP', 'SPSR Nellore', 19),
        ('AP', 'Sri Sathya Sai', 20),
        ('AP', 'Srikakulam', 21),
        ('AP', 'Tirupati', 22),
        ('AP', 'Visakhapatanam', 23),
        ('AP', 'Vizianagaram', 24),
        ('AP', 'West Godavari', 25),
        ('AP', 'Y.S.R.', 26),
        ('AR', 'Anjaw', 1),
        ('AR', 'Changlang', 2),
        ('AR', 'Dibang Valley', 3),
        ('AR', 'East Kameng', 4),
        ('AR', 'East Siang', 5),
        ('AR', 'Kamle', 6),
        ('AR', 'Kra Daadi', 7),
        ('AR', 'Kurung Kumey', 8),
        ('AR', 'Leparada', 9),
        ('AR', 'Lohit', 10),
        ('AR', 'Longding', 11),
        ('AR', 'Lower Dibang Valley', 12),
        ('AR', 'Lower Siang', 13),
        ('AR', 'Lower Subansiri', 14),
        ('AR', 'Namsai', 15),
        ('AR', 'Pakke Kessang', 16),
        ('AR', 'Papum Pare', 17),
        ('AR', 'Shi Yomi', 18),
        ('AR', 'Siang', 19),
        ('AR', 'Tawang', 20),
        ('AR', 'Tirap', 21),
        ('AR', 'Upper Siang', 22),
        ('AR', 'Upper Subansiri', 23),
        ('AR', 'West Kameng', 24),
        ('AR', 'West Siang', 25),
        ('AS', 'Bajali', 1),
        ('AS', 'Baksa', 2),
        ('AS', 'Barpeta', 3),
        ('AS', 'Biswanath', 4),
        ('AS', 'Bongaigaon', 5),
        ('AS', 'Cachar', 6),
        ('AS', 'Charaideo', 7),
        ('AS', 'Chirang', 8),
        ('AS', 'Darrang', 9),
        ('AS', 'Dhemaji', 10),
        ('AS', 'Dhubri', 11),
        ('AS', 'Dibrugarh', 12),
        ('AS', 'Dima Hasao', 13),
        ('AS', 'Goalpara', 14),
        ('AS', 'Golaghat', 15),
        ('AS', 'Hailakandi', 16),
        ('AS', 'Hojai', 17),
        ('AS', 'Jorhat', 18),
        ('AS', 'Kamrup', 19),
        ('AS', 'Kamrup Metro', 20),
        ('AS', 'Karbi Anglong', 21),
        ('AS', 'Karimganj', 22),
        ('AS', 'Kokrajhar', 23),
        ('AS', 'Lakhimpur', 24),
        ('AS', 'Majuli', 25),
        ('AS', 'Marigaon', 26),
        ('AS', 'Nagaon', 27),
        ('AS', 'Nalbari', 28),
        ('AS', 'Sivasagar', 29),
        ('AS', 'Sonitpur', 30),
        ('AS', 'South Salmara Mancachar', 31),
        ('AS', 'Tamulpur', 32),
        ('AS', 'Tinsukia', 33),
        ('AS', 'Udalguri', 34),
        ('AS', 'West Karbi Anglong', 35),
        ('BR', 'Araria', 1),
        ('BR', 'Arwal', 2),
        ('BR', 'Aurangabad', 3),
        ('BR', 'Banka', 4),
        ('BR', 'Begusarai', 5),
        ('BR', 'Bhagalpur', 6),
        ('BR', 'Bhojpur', 7),
        ('BR', 'Buxar', 8),
        ('BR', 'Darbhanga', 9),
        ('BR', 'Gaya', 10),
        ('BR', 'Gopalganj', 11),
        ('BR', 'Jamui', 12),
        ('BR', 'Jehanabad', 13),
        ('BR', 'Kaimur (Bhabua)', 14),
        ('BR', 'Katihar', 15),
        ('BR', 'Khagaria', 16),
        ('BR', 'Kishanganj', 17),
        ('BR', 'Lakhisarai', 18),
        ('BR', 'Madhepura', 19),
        ('BR', 'Madhubani', 20),
        ('BR', 'Munger', 21),
        ('BR', 'Muzaffarpur', 22),
        ('BR', 'Nalanda', 23),
        ('BR', 'Nawada', 24),
        ('BR', 'Pashchim Champaran', 25),
        ('BR', 'Patna', 26),
        ('BR', 'Purbi Champaran', 27),
        ('BR', 'Purnia', 28),
        ('BR', 'Rohtas', 29),
        ('BR', 'Saharsa', 30),
        ('BR', 'Samastipur', 31),
        ('BR', 'Saran', 32),
        ('BR', 'Sheikhpura', 33),
        ('BR', 'Sheohar', 34),
        ('BR', 'Sitamarhi', 35),
        ('BR', 'Siwan', 36),
        ('BR', 'Supaul', 37),
        ('BR', 'Vaishali', 38),
        ('CH', 'Chandigarh', 1),
        ('CG', 'Balod', 1),
        ('CG', 'Baloda Bazar', 2),
        ('CG', 'Balrampur', 3),
        ('CG', 'Bastar', 4),
        ('CG', 'Bemetara', 5),
        ('CG', 'Bijapur', 6),
        ('CG', 'Bilaspur', 7),
        ('CG', 'Dantewada', 8),
        ('CG', 'Dhamtari', 9),
        ('CG', 'Durg', 10),
        ('CG', 'Gariyaband', 11),
        ('CG', 'Gaurella Pendra Marwahi', 12),
        ('CG', 'Janjgir-Champa', 13),
        ('CG', 'Jashpur', 14),
        ('CG', 'Kabirdham', 15),
        ('CG', 'Kanker', 16),
        ('CG', 'Khairgarh Chhuikhadan Gandai', 17),
        ('CG', 'Kondagaon', 18),
        ('CG', 'Korba', 19),
        ('CG', 'Korea', 20),
        ('CG', 'Mahasamund', 21),
        ('CG', 'Manendragarh Chirimiri Bharatpur', 22),
        ('CG', 'Mohla Manpur Ambagarh Chouki', 23),
        ('CG', 'Mungeli', 24),
        ('CG', 'Narayanpur', 25),
        ('CG', 'Raigarh', 26),
        ('CG', 'Raipur', 27),
        ('CG', 'Rajnandgaon', 28),
        ('CG', 'Sakti', 29),
        ('CG', 'Sarangarh Bilaigarh', 30),
        ('CG', 'Sukma', 31),
        ('CG', 'Surajpur', 32),
        ('CG', 'Surguja', 33),
        ('DN', 'Dadra And Nagar Haveli', 1),
        ('DN', 'Daman', 2),
        ('DN', 'Diu', 3),
        ('DL', 'Central', 1),
        ('DL', 'East', 2),
        ('DL', 'New Delhi', 3),
        ('DL', 'North', 4),
        ('DL', 'North East', 5),
        ('DL', 'North West', 6),
        ('DL', 'Shahdara', 7),
        ('DL', 'South', 8),
        ('DL', 'South East', 9),
        ('DL', 'South West', 10),
        ('DL', 'West', 11),
        ('GA', 'North Goa', 1),
        ('GA', 'South Goa', 2),
        ('GJ', 'Ahmadabad', 1),
        ('GJ', 'Amreli', 2),
        ('GJ', 'Anand', 3),
        ('GJ', 'Arvalli', 4),
        ('GJ', 'Banas Kantha', 5),
        ('GJ', 'Bharuch', 6),
        ('GJ', 'Bhavnagar', 7),
        ('GJ', 'Botad', 8),
        ('GJ', 'Chhotaudepur', 9),
        ('GJ', 'Dang', 10),
        ('GJ', 'Devbhumi Dwarka', 11),
        ('GJ', 'Dohad', 12),
        ('GJ', 'Gandhinagar', 13),
        ('GJ', 'Gir Somnath', 14),
        ('GJ', 'Jamnagar', 15),
        ('GJ', 'Junagadh', 16),
        ('GJ', 'Kachchh', 17),
        ('GJ', 'Kheda', 18),
        ('GJ', 'Mahesana', 19),
        ('GJ', 'Mahisagar', 20),
        ('GJ', 'Morbi', 21),
        ('GJ', 'Narmada', 22),
        ('GJ', 'Navsari', 23),
        ('GJ', 'Panch Mahals', 24),
        ('GJ', 'Patan', 25),
        ('GJ', 'Porbandar', 26),
        ('GJ', 'Rajkot', 27),
        ('GJ', 'Sabar Kantha', 28),
        ('GJ', 'Surat', 29),
        ('GJ', 'Surendranagar', 30),
        ('GJ', 'Tapi', 31),
        ('GJ', 'Vadodara', 32),
        ('GJ', 'Valsad', 33),
        ('HR', 'Ambala', 1),
        ('HR', 'Bhiwani', 2),
        ('HR', 'Charki Dadri', 3),
        ('HR', 'Faridabad', 4),
        ('HR', 'Fatehabad', 5),
        ('HR', 'Gurugram', 6),
        ('HR', 'Hisar', 7),
        ('HR', 'Jhajjar', 8),
        ('HR', 'Jind', 9),
        ('HR', 'Kaithal', 10),
        ('HR', 'Karnal', 11),
        ('HR', 'Kurukshetra', 12),
        ('HR', 'Mahendragarh', 13),
        ('HR', 'Nuh', 14),
        ('HR', 'Palwal', 15),
        ('HR', 'Panchkula', 16),
        ('HR', 'Panipat', 17),
        ('HR', 'Rewari', 18),
        ('HR', 'Rohtak', 19),
        ('HR', 'Sirsa', 20),
        ('HR', 'Sonipat', 21),
        ('HR', 'Yamunanagar', 22),
        ('HP', 'Bilaspur', 1),
        ('HP', 'Chamba', 2),
        ('HP', 'Hamirpur', 3),
        ('HP', 'Kangra', 4),
        ('HP', 'Kinnaur', 5),
        ('HP', 'Kullu', 6),
        ('HP', 'Lahul And Spiti', 7),
        ('HP', 'Mandi', 8),
        ('HP', 'Shimla', 9),
        ('HP', 'Sirmaur', 10),
        ('HP', 'Solan', 11),
        ('HP', 'Una', 12),
        ('JK', 'Anantnag', 1),
        ('JK', 'Bandipora', 2),
        ('JK', 'Baramulla', 3),
        ('JK', 'Budgam', 4),
        ('JK', 'Doda', 5),
        ('JK', 'Ganderbal', 6),
        ('JK', 'Jammu', 7),
        ('JK', 'Kathua', 8),
        ('JK', 'Kishtwar', 9),
        ('JK', 'Kulgam', 10),
        ('JK', 'Kupwara', 11),
        ('JK', 'Poonch', 12),
        ('JK', 'Pulwama', 13),
        ('JK', 'Rajouri', 14),
        ('JK', 'Ramban', 15),
        ('JK', 'Reasi', 16),
        ('JK', 'Samba', 17),
        ('JK', 'Shopian', 18),
        ('JK', 'Srinagar', 19),
        ('JK', 'Udhampur', 20),
        ('JH', 'Bokaro', 1),
        ('JH', 'Chatra', 2),
        ('JH', 'Deoghar', 3),
        ('JH', 'Dhanbad', 4),
        ('JH', 'Dumka', 5),
        ('JH', 'East Singhbum', 6),
        ('JH', 'Garhwa', 7),
        ('JH', 'Giridih', 8),
        ('JH', 'Godda', 9),
        ('JH', 'Gumla', 10),
        ('JH', 'Hazaribagh', 11),
        ('JH', 'Jamtara', 12),
        ('JH', 'Khunti', 13),
        ('JH', 'Koderma', 14),
        ('JH', 'Latehar', 15),
        ('JH', 'Lohardaga', 16),
        ('JH', 'Pakur', 17),
        ('JH', 'Palamu', 18),
        ('JH', 'Ramgarh', 19),
        ('JH', 'Ranchi', 20),
        ('JH', 'Sahebganj', 21),
        ('JH', 'Saraikela Kharsawan', 22),
        ('JH', 'Simdega', 23),
        ('JH', 'West Singhbhum', 24),
        ('KA', 'Bagalkote', 1),
        ('KA', 'Ballari', 2),
        ('KA', 'Belagavi', 3),
        ('KA', 'Bengaluru Rural', 4),
        ('KA', 'Bengaluru Urban', 5),
        ('KA', 'Bidar', 6),
        ('KA', 'Chamarajanagara', 7),
        ('KA', 'Chikkaballapura', 8),
        ('KA', 'Chikkamagaluru', 9),
        ('KA', 'Chitradurga', 10),
        ('KA', 'Dakshina Kannada', 11),
        ('KA', 'Davangere', 12),
        ('KA', 'Dharwad', 13),
        ('KA', 'Gadag', 14),
        ('KA', 'Hassan', 15),
        ('KA', 'Haveri', 16),
        ('KA', 'Kalaburagi', 17),
        ('KA', 'Kodagu', 18),
        ('KA', 'Kolar', 19),
        ('KA', 'Koppal', 20),
        ('KA', 'Mandya', 21),
        ('KA', 'Mysuru', 22),
        ('KA', 'Raichur', 23),
        ('KA', 'Ramanagara', 24),
        ('KA', 'Shivamogga', 25),
        ('KA', 'Tumakuru', 26),
        ('KA', 'Udupi', 27),
        ('KA', 'Uttara Kannada', 28),
        ('KA', 'Vijayanagar', 29),
        ('KA', 'Vijayapura', 30),
        ('KA', 'Yadgir', 31),
        ('KL', 'Alappuzha', 1),
        ('KL', 'Ernakulam', 2),
        ('KL', 'Idukki', 3),
        ('KL', 'Kannur', 4),
        ('KL', 'Kasaragod', 5),
        ('KL', 'Kollam', 6),
        ('KL', 'Kottayam', 7),
        ('KL', 'Kozhikode', 8),
        ('KL', 'Malappuram', 9),
        ('KL', 'Palakkad', 10),
        ('KL', 'Pathanamthitta', 11),
        ('KL', 'Thiruvananthapuram', 12),
        ('KL', 'Thrissur', 13),
        ('KL', 'Wayanad', 14),
        ('LA', 'Kargil', 1),
        ('LA', 'Leh Ladakh', 2),
        ('LD', 'Lakshadweep District', 1),
        ('MP', 'Agar Malwa', 1),
        ('MP', 'Alirajpur', 2),
        ('MP', 'Anuppur', 3),
        ('MP', 'Ashoknagar', 4),
        ('MP', 'Balaghat', 5),
        ('MP', 'Barwani', 6),
        ('MP', 'Betul', 7),
        ('MP', 'Bhind', 8),
        ('MP', 'Bhopal', 9),
        ('MP', 'Burhanpur', 10),
        ('MP', 'Chhatarpur', 11),
        ('MP', 'Chhindwara', 12),
        ('MP', 'Damoh', 13),
        ('MP', 'Datia', 14),
        ('MP', 'Dewas', 15),
        ('MP', 'Dhar', 16),
        ('MP', 'Dindori', 17),
        ('MP', 'East Nimar', 18),
        ('MP', 'Guna', 19),
        ('MP', 'Gwalior', 20),
        ('MP', 'Harda', 21),
        ('MP', 'Indore', 22),
        ('MP', 'Jabalpur', 23),
        ('MP', 'Jhabua', 24),
        ('MP', 'Katni', 25),
        ('MP', 'Khargone', 26),
        ('MP', 'Mandla', 27),
        ('MP', 'Mandsaur', 28),
        ('MP', 'Morena', 29),
        ('MP', 'Narmadapuram', 30),
        ('MP', 'Narsinghpur', 31),
        ('MP', 'Neemuch', 32),
        ('MP', 'Niwari', 33),
        ('MP', 'Panna', 34),
        ('MP', 'Raisen', 35),
        ('MP', 'Rajgarh', 36),
        ('MP', 'Ratlam', 37),
        ('MP', 'Rewa', 38),
        ('MP', 'Sagar', 39),
        ('MP', 'Satna', 40),
        ('MP', 'Sehore', 41),
        ('MP', 'Seoni', 42),
        ('MP', 'Shahdol', 43),
        ('MP', 'Shajapur', 44),
        ('MP', 'Sheopur', 45),
        ('MP', 'Shivpuri', 46),
        ('MP', 'Sidhi', 47),
        ('MP', 'Singrauli', 48),
        ('MP', 'Tikamgarh', 49),
        ('MP', 'Ujjain', 50),
        ('MP', 'Umaria', 51),
        ('MP', 'Vidisha', 52),
        ('MH', 'Ahmednagar', 1),
        ('MH', 'Akola', 2),
        ('MH', 'Amravati', 3),
        ('MH', 'Aurangabad', 4),
        ('MH', 'Beed', 5),
        ('MH', 'Bhandara', 6),
        ('MH', 'Buldhana', 7),
        ('MH', 'Chandrapur', 8),
        ('MH', 'Dhule', 9),
        ('MH', 'Gadchiroli', 10),
        ('MH', 'Gondia', 11),
        ('MH', 'Hingoli', 12),
        ('MH', 'Jalgaon', 13),
        ('MH', 'Jalna', 14),
        ('MH', 'Kolhapur', 15),
        ('MH', 'Latur', 16),
        ('MH', 'Mumbai', 17),
        ('MH', 'Mumbai Suburban', 18),
        ('MH', 'Nagpur', 19),
        ('MH', 'Nanded', 20),
        ('MH', 'Nandurbar', 21),
        ('MH', 'Nashik', 22),
        ('MH', 'Osmanabad', 23),
        ('MH', 'Palghar', 24),
        ('MH', 'Parbhani', 25),
        ('MH', 'Pune', 26),
        ('MH', 'Raigad', 27),
        ('MH', 'Ratnagiri', 28),
        ('MH', 'Sangli', 29),
        ('MH', 'Satara', 30),
        ('MH', 'Sindhudurg', 31),
        ('MH', 'Solapur', 32),
        ('MH', 'Thane', 33),
        ('MH', 'Wardha', 34),
        ('MH', 'Washim', 35),
        ('MH', 'Yavatmal', 36),
        ('MN', 'Bishnupur', 1),
        ('MN', 'Chandel', 2),
        ('MN', 'Churachandpur', 3),
        ('MN', 'Imphal East', 4),
        ('MN', 'Imphal West', 5),
        ('MN', 'Jiribam', 6),
        ('MN', 'Kakching', 7),
        ('MN', 'Kamjong', 8),
        ('MN', 'Kangpokpi', 9),
        ('MN', 'Noney', 10),
        ('MN', 'Pherzawl', 11),
        ('MN', 'Senapati', 12),
        ('MN', 'Tamenglong', 13),
        ('MN', 'Tengnoupal', 14),
        ('MN', 'Thoubal', 15),
        ('MN', 'Ukhrul', 16),
        ('ML', 'East Garo Hills', 1),
        ('ML', 'East Jaintia Hills', 2),
        ('ML', 'East Khasi Hills', 3),
        ('ML', 'Eastern West Khasi Hills', 4),
        ('ML', 'North Garo Hills', 5),
        ('ML', 'Ri Bhoi', 6),
        ('ML', 'South Garo Hills', 7),
        ('ML', 'South West Garo Hills', 8),
        ('ML', 'South West Khasi Hills', 9),
        ('ML', 'West Garo Hills', 10),
        ('ML', 'West Jaintia Hills', 11),
        ('ML', 'West Khasi Hills', 12),
        ('MZ', 'Aizawl', 1),
        ('MZ', 'Champhai', 2),
        ('MZ', 'Hnahthial', 3),
        ('MZ', 'Khawzawl', 4),
        ('MZ', 'Kolasib', 5),
        ('MZ', 'Lawngtlai', 6),
        ('MZ', 'Lunglei', 7),
        ('MZ', 'Mamit', 8),
        ('MZ', 'Saiha', 9),
        ('MZ', 'Saitual', 10),
        ('MZ', 'Serchhip', 11),
        ('NL', 'Chumoukedima', 1),
        ('NL', 'Dimapur', 2),
        ('NL', 'Kiphire', 3),
        ('NL', 'Kohima', 4),
        ('NL', 'Longleng', 5),
        ('NL', 'Mokokchung', 6),
        ('NL', 'Mon', 7),
        ('NL', 'Noklak', 8),
        ('NL', 'Peren', 9),
        ('NL', 'Phek', 10),
        ('NL', 'Tseminyu', 11),
        ('NL', 'Tuensang', 12),
        ('NL', 'Wokha', 13),
        ('NL', 'Zunheboto', 14),
        ('OD', 'Anugul', 1),
        ('OD', 'Balangir', 2),
        ('OD', 'Baleshwar', 3),
        ('OD', 'Bargarh', 4),
        ('OD', 'Bhadrak', 5),
        ('OD', 'Boudh', 6),
        ('OD', 'Cuttack', 7),
        ('OD', 'Deogarh', 8),
        ('OD', 'Dhenkanal', 9),
        ('OD', 'Gajapati', 10),
        ('OD', 'Ganjam', 11),
        ('OD', 'Jagatsinghapur', 12),
        ('OD', 'Jajapur', 13),
        ('OD', 'Jharsuguda', 14),
        ('OD', 'Kalahandi', 15),
        ('OD', 'Kandhamal', 16),
        ('OD', 'Kendrapara', 17),
        ('OD', 'Kendujhar', 18),
        ('OD', 'Khordha', 19),
        ('OD', 'Koraput', 20),
        ('OD', 'Malkangiri', 21),
        ('OD', 'Mayurbhanj', 22),
        ('OD', 'Nabarangpur', 23),
        ('OD', 'Nayagarh', 24),
        ('OD', 'Nuapada', 25),
        ('OD', 'Puri', 26),
        ('OD', 'Rayagada', 27),
        ('OD', 'Sambalpur', 28),
        ('OD', 'Sonepur', 29),
        ('OD', 'Sundargarh', 30),
        ('PY', 'Karaikal', 1),
        ('PY', 'Mahe', 2),
        ('PY', 'Pondicherry', 3),
        ('PY', 'Yanam', 4),
        ('PB', 'Amritsar', 1),
        ('PB', 'Barnala', 2),
        ('PB', 'Bathinda', 3),
        ('PB', 'Faridkot', 4),
        ('PB', 'Fatehgarh Sahib', 5),
        ('PB', 'Fazilka', 6),
        ('PB', 'Ferozepur', 7),
        ('PB', 'Gurdaspur', 8),
        ('PB', 'Hoshiarpur', 9),
        ('PB', 'Jalandhar', 10),
        ('PB', 'Kapurthala', 11),
        ('PB', 'Ludhiana', 12),
        ('PB', 'Malerkotla', 13),
        ('PB', 'Mansa', 14),
        ('PB', 'Moga', 15),
        ('PB', 'Pathankot', 16),
        ('PB', 'Patiala', 17),
        ('PB', 'Rupnagar', 18),
        ('PB', 'S.A.S Nagar', 19),
        ('PB', 'Sangrur', 20),
        ('PB', 'Shahid Bhagat Singh Nagar', 21),
        ('PB', 'Sri Muktsar Sahib', 22),
        ('PB', 'Tarn Taran', 23),
        ('RJ', 'Ajmer', 1),
        ('RJ', 'Alwar', 2),
        ('RJ', 'Banswara', 3),
        ('RJ', 'Baran', 4),
        ('RJ', 'Barmer', 5),
        ('RJ', 'Bharatpur', 6),
        ('RJ', 'Bhilwara', 7),
        ('RJ', 'Bikaner', 8),
        ('RJ', 'Bundi', 9),
        ('RJ', 'Chittorgarh', 10),
        ('RJ', 'Churu', 11),
        ('RJ', 'Dausa', 12),
        ('RJ', 'Dholpur', 13),
        ('RJ', 'Dungarpur', 14),
        ('RJ', 'Ganganagar', 15),
        ('RJ', 'Hanumangarh', 16),
        ('RJ', 'Jaipur', 17),
        ('RJ', 'Jaisalmer', 18),
        ('RJ', 'Jalore', 19),
        ('RJ', 'Jhalawar', 20),
        ('RJ', 'Jhunjhunu', 21),
        ('RJ', 'Jodhpur', 22),
        ('RJ', 'Karauli', 23),
        ('RJ', 'Kota', 24),
        ('RJ', 'Nagaur', 25),
        ('RJ', 'Pali', 26),
        ('RJ', 'Pratapgarh', 27),
        ('RJ', 'Rajsamand', 28),
        ('RJ', 'Sawai Madhopur', 29),
        ('RJ', 'Sikar', 30),
        ('RJ', 'Sirohi', 31),
        ('RJ', 'Tonk', 32),
        ('RJ', 'Udaipur', 33),
        ('SK', 'Gangtok', 1),
        ('SK', 'Gyalshing', 2),
        ('SK', 'Mangan', 3),
        ('SK', 'Namchi', 4),
        ('SK', 'Pakyong', 5),
        ('SK', 'Soreng', 6),
        ('TN', 'Ariyalur', 1),
        ('TN', 'Chengalpattu', 2),
        ('TN', 'Chennai', 3),
        ('TN', 'Coimbatore', 4),
        ('TN', 'Cuddalore', 5),
        ('TN', 'Dharmapuri', 6),
        ('TN', 'Dindigul', 7),
        ('TN', 'Erode', 8),
        ('TN', 'Kallakurichi', 9),
        ('TN', 'Kanchipuram', 10),
        ('TN', 'Kanniyakumari', 11),
        ('TN', 'Karur', 12),
        ('TN', 'Krishnagiri', 13),
        ('TN', 'Madurai', 14),
        ('TN', 'Mayiladuthurai', 15),
        ('TN', 'Nagapattinam', 16),
        ('TN', 'Namakkal', 17),
        ('TN', 'Perambalur', 18),
        ('TN', 'Pudukkottai', 19),
        ('TN', 'Ramanathapuram', 20),
        ('TN', 'Ranipet', 21),
        ('TN', 'Salem', 22),
        ('TN', 'Sivaganga', 23),
        ('TN', 'Tenkasi', 24),
        ('TN', 'Thanjavur', 25),
        ('TN', 'The Nilgiris', 26),
        ('TN', 'Theni', 27),
        ('TN', 'Thiruvallur', 28),
        ('TN', 'Thiruvarur', 29),
        ('TN', 'Tiruchirappalli', 30),
        ('TN', 'Tirunelveli', 31),
        ('TN', 'Tirupathur', 32),
        ('TN', 'Tiruppur', 33),
        ('TN', 'Tiruvannamalai', 34),
        ('TN', 'Tuticorin', 35),
        ('TN', 'Vellore', 36),
        ('TN', 'Villupuram', 37),
        ('TN', 'Virudhunagar', 38),
        ('TS', 'Adilabad', 1),
        ('TS', 'Bhadradri Kothagudem', 2),
        ('TS', 'Hanumakonda', 3),
        ('TS', 'Hyderabad', 4),
        ('TS', 'Jagitial', 5),
        ('TS', 'Jangoan', 6),
        ('TS', 'Jayashankar Bhupalapally', 7),
        ('TS', 'Jogulamba Gadwal', 8),
        ('TS', 'Kamareddy', 9),
        ('TS', 'Karimnagar', 10),
        ('TS', 'Khammam', 11),
        ('TS', 'Kumuram Bheem Asifabad', 12),
        ('TS', 'Mahabubabad', 13),
        ('TS', 'Mahabubnagar', 14),
        ('TS', 'Mancherial', 15),
        ('TS', 'Medak', 16),
        ('TS', 'Medchal Malkajgiri', 17),
        ('TS', 'Mulugu', 18),
        ('TS', 'Nagarkurnool', 19),
        ('TS', 'Nalgonda', 20),
        ('TS', 'Narayanpet', 21),
        ('TS', 'Nirmal', 22),
        ('TS', 'Nizamabad', 23),
        ('TS', 'Peddapalli', 24),
        ('TS', 'Rajanna Sircilla', 25),
        ('TS', 'Ranga Reddy', 26),
        ('TS', 'Sangareddy', 27),
        ('TS', 'Siddipet', 28),
        ('TS', 'Suryapet', 29),
        ('TS', 'Vikarabad', 30),
        ('TS', 'Wanaparthy', 31),
        ('TS', 'Warangal', 32),
        ('TS', 'Yadadri Bhuvanagiri', 33),
        ('TR', 'Dhalai', 1),
        ('TR', 'Gomati', 2),
        ('TR', 'Khowai', 3),
        ('TR', 'North Tripura', 4),
        ('TR', 'Sepahijala', 5),
        ('TR', 'South Tripura', 6),
        ('TR', 'Unakoti', 7),
        ('TR', 'West Tripura', 8),
        ('UP', 'Agra', 1),
        ('UP', 'Aligarh', 2),
        ('UP', 'Ambedkar Nagar', 3),
        ('UP', 'Amethi', 4),
        ('UP', 'Amroha', 5),
        ('UP', 'Auraiya', 6),
        ('UP', 'Ayodhya', 7),
        ('UP', 'Azamgarh', 8),
        ('UP', 'Baghpat', 9),
        ('UP', 'Bahraich', 10),
        ('UP', 'Ballia', 11),
        ('UP', 'Balrampur', 12),
        ('UP', 'Banda', 13),
        ('UP', 'Barabanki', 14),
        ('UP', 'Bareilly', 15),
        ('UP', 'Basti', 16),
        ('UP', 'Bhadohi', 17),
        ('UP', 'Bijnor', 18),
        ('UP', 'Budaun', 19),
        ('UP', 'Bulandshahr', 20),
        ('UP', 'Chandauli', 21),
        ('UP', 'Chitrakoot', 22),
        ('UP', 'Deoria', 23),
        ('UP', 'Etah', 24),
        ('UP', 'Etawah', 25),
        ('UP', 'Farrukhabad', 26),
        ('UP', 'Fatehpur', 27),
        ('UP', 'Firozabad', 28),
        ('UP', 'Gautam Buddha Nagar', 29),
        ('UP', 'Ghaziabad', 30),
        ('UP', 'Ghazipur', 31),
        ('UP', 'Gonda', 32),
        ('UP', 'Gorakhpur', 33),
        ('UP', 'Hamirpur', 34),
        ('UP', 'Hapur', 35),
        ('UP', 'Hardoi', 36),
        ('UP', 'Hathras', 37),
        ('UP', 'Jalaun', 38),
        ('UP', 'Jaunpur', 39),
        ('UP', 'Jhansi', 40),
        ('UP', 'Kannauj', 41),
        ('UP', 'Kanpur Dehat', 42),
        ('UP', 'Kanpur Nagar', 43),
        ('UP', 'Kasganj', 44),
        ('UP', 'Kaushambi', 45),
        ('UP', 'Kheri', 46),
        ('UP', 'Kushi Nagar', 47),
        ('UP', 'Lalitpur', 48),
        ('UP', 'Lucknow', 49),
        ('UP', 'Maharajganj', 50),
        ('UP', 'Mahoba', 51),
        ('UP', 'Mainpuri', 52),
        ('UP', 'Mathura', 53),
        ('UP', 'Mau', 54),
        ('UP', 'Meerut', 55),
        ('UP', 'Mirzapur', 56),
        ('UP', 'Moradabad', 57),
        ('UP', 'Muzaffarnagar', 58),
        ('UP', 'Pilibhit', 59),
        ('UP', 'Pratapgarh', 60),
        ('UP', 'Prayagraj', 61),
        ('UP', 'Rae Bareli', 62),
        ('UP', 'Rampur', 63),
        ('UP', 'Saharanpur', 64),
        ('UP', 'Sambhal', 65),
        ('UP', 'Sant Kabeer Nagar', 66),
        ('UP', 'Shahjahanpur', 67),
        ('UP', 'Shamli', 68),
        ('UP', 'Shravasti', 69),
        ('UP', 'Siddharth Nagar', 70),
        ('UP', 'Sitapur', 71),
        ('UP', 'Sonbhadra', 72),
        ('UP', 'Sultanpur', 73),
        ('UP', 'Unnao', 74),
        ('UP', 'Varanasi', 75),
        ('UK', 'Almora', 1),
        ('UK', 'Bageshwar', 2),
        ('UK', 'Chamoli', 3),
        ('UK', 'Champawat', 4),
        ('UK', 'Dehradun', 5),
        ('UK', 'Haridwar', 6),
        ('UK', 'Nainital', 7),
        ('UK', 'Pauri Garhwal', 8),
        ('UK', 'Pithoragarh', 9),
        ('UK', 'Rudra Prayag', 10),
        ('UK', 'Tehri Garhwal', 11),
        ('UK', 'Udam Singh Nagar', 12),
        ('UK', 'Uttar Kashi', 13),
        ('WB', '24 Paraganas North', 1),
        ('WB', '24 Paraganas South', 2),
        ('WB', 'Alipurduar', 3),
        ('WB', 'Bankura', 4),
        ('WB', 'Birbhum', 5),
        ('WB', 'Coochbehar', 6),
        ('WB', 'Darjeeling', 7),
        ('WB', 'Dinajpur Dakshin', 8),
        ('WB', 'Dinajpur Uttar', 9),
        ('WB', 'Hooghly', 10),
        ('WB', 'Howrah', 11),
        ('WB', 'Jalpaiguri', 12),
        ('WB', 'Jhargram', 13),
        ('WB', 'Kalimpong', 14),
        ('WB', 'Kolkata', 15),
        ('WB', 'Maldah', 16),
        ('WB', 'Medinipur East', 17),
        ('WB', 'Medinipur West', 18),
        ('WB', 'Murshidabad', 19),
        ('WB', 'Nadia', 20),
        ('WB', 'Paschim Bardhaman', 21),
        ('WB', 'Purba Bardhaman', 22),
        ('WB', 'Purulia', 23)
    ) AS d(state_code, district_name, display_order)
    INNER JOIN master_states ms
        ON ms.country_id = v_country_id
       AND ms.code = d.state_code
    ON CONFLICT (state_id, name) DO UPDATE
    SET display_order = EXCLUDED.display_order,
        is_active = EXCLUDED.is_active,
        updated_at = CURRENT_TIMESTAMP;
END $$;

-- Remove the former 400 × 400 minimum-dimension restriction.
ALTER TABLE public.prelaunch_photos
DROP CONSTRAINT IF EXISTS chk_prelaunch_photo_dimensions;

-- Retain basic data-integrity validation without imposing image quality rules.
ALTER TABLE public.prelaunch_photos
    ADD CONSTRAINT chk_prelaunch_photo_dimensions
    CHECK (
        width_px > 0
        AND height_px > 0

ALTER TABLE public.member_family_details
ADD COLUMN IF NOT EXISTS nearest_gurudwara VARCHAR(200) NULL,
ADD COLUMN IF NOT EXISTS reference_person_1 VARCHAR(200) NULL,
ADD COLUMN IF NOT EXISTS reference_person_2 VARCHAR(200) NULL;

COMMENT ON COLUMN public.member_family_details.nearest_gurudwara IS 'Optional name and/or location of the nearest Gurudwara.';

COMMENT ON COLUMN public.member_family_details.reference_person_1 IS 'Optional name and contact details of the first reference person.';

COMMENT ON COLUMN public.member_family_details.reference_person_2 IS 'Optional name and contact details of the second reference person.';

DELETE FROM user_contacts
WHERE
    contact_type = 'EMAIL'
    AND (
        BTRIM(contact_value) = ''
        OR BTRIM(normalized_value) = ''
    );

/*
 * Keep email uniqueness for users who add an email later through account
 * settings or another controlled workflow.
 */
CREATE UNIQUE INDEX IF NOT EXISTS uq_user_contacts_email_normalized ON user_contacts (normalized_value)
WHERE
    contact_type = 'EMAIL';

/*
 * Ensure that each user can have only one primary contact for each contact
 * type. A user may have no EMAIL row at all.
 */
CREATE UNIQUE INDEX IF NOT EXISTS uq_user_primary_contact_type ON user_contacts (user_id, contact_type)
WHERE
    is_primary = TRUE;

UPDATE prelaunch_profiles
SET
    email = NULL
WHERE
    TRIM(COALESCE(email, '')) = '';

ALTER TABLE prelaunch_profiles ALTER COLUMN email DROP NOT NULL;

ALTER TABLE contact_verifications
DROP CONSTRAINT IF EXISTS chk_contact_verification_purpose;

ALTER TABLE contact_verifications
ADD CONSTRAINT chk_contact_verification_purpose

CHECK (
    purpose IN (
        'REGISTER',
        'LOGIN',
        'CHANGE_MOBILE',
        'CHANGE_EMAIL',
        'REGISTER',
        'PASSWORD_RESET',
        'PENDING',
        'VERIFIED',
        'EXPIRED',
        'CANCELLED'
    )
);

ALTER TABLE prelaunch_profiles
ADD COLUMN nearest_gurudwara VARCHAR(300) NULL;

ALTER TABLE prelaunch_photos
ALTER COLUMN medium_path
DROP NOT NULL;

ALTER TABLE prelaunch_photos
ALTER COLUMN thumbnail_path
DROP NOT NULL;

CREATE TABLE IF NOT EXISTS master_drinking_habits (
    id SERIAL PRIMARY KEY,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    display_order SMALLINT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_master_drinking_habits_code UNIQUE (code),
    CONSTRAINT uq_master_drinking_habits_name UNIQUE (name)
);

CREATE INDEX IF NOT EXISTS idx_master_drinking_habits_active_order ON master_drinking_habits (is_active, display_order);

CREATE TABLE IF NOT EXISTS master_eating_habits (
    id SERIAL PRIMARY KEY,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    display_order SMALLINT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_master_eating_habits_code UNIQUE (code),
    CONSTRAINT uq_master_eating_habits_name UNIQUE (name)
);

CREATE INDEX IF NOT EXISTS idx_master_eating_habits_active_order ON master_eating_habits (is_active, display_order);

CREATE TABLE IF NOT EXISTS master_physical_statuses (
    id SERIAL PRIMARY KEY,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    display_order SMALLINT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_master_physical_statuses_code UNIQUE (code),
    CONSTRAINT uq_master_physical_statuses_name UNIQUE (name)
);

CREATE INDEX IF NOT EXISTS idx_master_physical_statuses_active_order ON master_physical_statuses (is_active, display_order);

INSERT INTO
    master_drinking_habits (
        code,
        name,
        display_order,
        is_active
    )
VALUES (
        'NEVER_DRINKS',
        'Never Drinks',
        10,
        TRUE
    ),
    (
        'DRINKS_SOCIALLY',
        'Drinks Socially',
        20,
        TRUE
    ),
    (
        'DRINKS_REGULARLY',
        'Drinks Regularly',
        30,
        TRUE
    )
ON CONFLICT (code) DO
UPDATE
SET
    name = EXCLUDED.name,
    display_order = EXCLUDED.display_order,
    is_active = EXCLUDED.is_active,
    updated_at = CURRENT_TIMESTAMP;

INSERT INTO
    master_eating_habits (
        code,
        name,
        display_order,
        is_active
    )
VALUES (
        'VEGETARIAN',
        'Vegetarian',
        10,
        TRUE
    ),
    (
        'NON_VEGETARIAN',
        'Non Vegetarian',
        20,
        TRUE
    ),
    (
        'EGGETARIAN',
        'Eggetarian',
        30,
        TRUE
    )
ON CONFLICT (code) DO
UPDATE
SET
    name = EXCLUDED.name,
    display_order = EXCLUDED.display_order,
    is_active = EXCLUDED.is_active,
    updated_at = CURRENT_TIMESTAMP;

INSERT INTO
    master_physical_statuses (
        code,
        name,
        display_order,
        is_active
    )
VALUES ('NORMAL', 'Normal', 10, TRUE),
    (
        'PHYSICALLY_CHALLENGED',
        'Physically Challenged',
        20,
        TRUE
    )
ON CONFLICT (code) DO
UPDATE
SET
    name = EXCLUDED.name,
    display_order = EXCLUDED.display_order,
    is_active = EXCLUDED.is_active,
    updated_at = CURRENT_TIMESTAMP;

ALTER TABLE member_basic_details
ADD COLUMN IF NOT EXISTS drinking_habit_id INTEGER NULL,
ADD COLUMN IF NOT EXISTS eating_habit_id INTEGER NULL,
ADD COLUMN IF NOT EXISTS physical_status_id INTEGER NULL,
ADD COLUMN IF NOT EXISTS number_of_children SMALLINT NULL,
ADD COLUMN IF NOT EXISTS children_living_together BOOLEAN NULL;

ALTER TABLE member_basic_details
ADD CONSTRAINT fk_member_basic_details_drinking_habit FOREIGN KEY (drinking_habit_id) REFERENCES master_drinking_habits (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
ADD CONSTRAINT fk_member_basic_details_eating_habit FOREIGN KEY (eating_habit_id) REFERENCES master_eating_habits (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
ADD CONSTRAINT fk_member_basic_details_physical_status FOREIGN KEY (physical_status_id) REFERENCES master_physical_statuses (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
ADD CONSTRAINT chk_member_basic_details_children_count CHECK (
    number_of_children IS NULL
    OR number_of_children BETWEEN 1 AND 99
),
ADD CONSTRAINT chk_member_basic_details_children_living CHECK (
    children_living_together IS NULL
    OR number_of_children IS NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_member_basic_details_drinking_habit ON member_basic_details (drinking_habit_id);

CREATE INDEX IF NOT EXISTS idx_member_basic_details_eating_habit ON member_basic_details (eating_habit_id);

CREATE INDEX IF NOT EXISTS idx_member_basic_details_physical_status ON member_basic_details (physical_status_id);

ALTER TABLE member_basic_details
ADD COLUMN IF NOT EXISTS about_me TEXT NULL;

ALTER TABLE users ADD COLUMN prelaunch_profile_id BIGINT NULL;

ALTER TABLE users
ADD CONSTRAINT fk_users_prelaunch_profile FOREIGN KEY (prelaunch_profile_id) REFERENCES prelaunch_profiles (id) ON UPDATE RESTRICT ON DELETE RESTRICT;

CREATE UNIQUE INDEX uq_users_prelaunch_profile_id ON users (prelaunch_profile_id)
WHERE
    prelaunch_profile_id IS NOT NULL;

ALTER TABLE prelaunch_profiles
ADD COLUMN migrated_user_id BIGINT NULL,
ADD COLUMN migrated_at TIMESTAMP NULL,
ADD COLUMN local_photos_cleanup_after TIMESTAMP NULL,
ADD COLUMN local_photos_cleaned_at TIMESTAMP NULL,
ADD COLUMN migration_error TEXT NULL;

ALTER TABLE prelaunch_profiles
ADD CONSTRAINT fk_prelaunch_profiles_migrated_user FOREIGN KEY (migrated_user_id) REFERENCES users (id) ON UPDATE RESTRICT ON DELETE RESTRICT;

CREATE INDEX idx_prelaunch_profiles_cleanup ON prelaunch_profiles (
    local_photos_cleanup_after,
    local_photos_cleaned_at
)
WHERE
    migrated_user_id IS NOT NULL
    AND local_photos_cleaned_at IS NULL;

ALTER TABLE member_photos ADD COLUMN prelaunch_photo_id BIGINT NULL;

ALTER TABLE member_photos
ADD CONSTRAINT fk_member_photos_prelaunch_photo FOREIGN KEY (prelaunch_photo_id) REFERENCES prelaunch_photos (id) ON UPDATE RESTRICT ON DELETE RESTRICT;

CREATE UNIQUE INDEX uq_member_photos_prelaunch_photo ON member_photos (prelaunch_photo_id)
WHERE
    prelaunch_photo_id IS NOT NULL;

CREATE INDEX idx_user_contacts_mobile ON user_contacts (
    contact_type,
    normalized_value
);

CREATE INDEX idx_user_contacts_email ON user_contacts (
    contact_type,
    normalized_value
);

ALTER TABLE prelaunch_profiles
DROP CONSTRAINT IF EXISTS chk_prelaunch_profile_created_for;

ALTER TABLE prelaunch_profiles
ADD CONSTRAINT chk_prelaunch_profile_created_for CHECK (
    profile_created_for IN (
        'SELF',
        'SON',
        'DAUGHTER',
        'BROTHER',
        'SISTER'
    )
);

ALTER TABLE prelaunch_profiles
ADD COLUMN IF NOT EXISTS parent_contact_number VARCHAR(16) NULL;

ALTER TABLE member_family_details
ADD COLUMN IF NOT EXISTS parent_contact_number VARCHAR(16) NULL;

CREATE TABLE IF NOT EXISTS member_partner_basic_preferences (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    age_from SMALLINT NULL,
    age_to SMALLINT NULL,
    is_age_compulsory BOOLEAN NOT NULL DEFAULT FALSE,
    height_from_id INTEGER NULL,
    height_to_id INTEGER NULL,
    is_height_compulsory BOOLEAN NOT NULL DEFAULT FALSE,
    marital_status_id INTEGER NULL,
    is_marital_status_compulsory BOOLEAN NOT NULL DEFAULT FALSE,
    have_children BOOLEAN NULL,
    is_have_children_compulsory BOOLEAN NOT NULL DEFAULT FALSE,
    physical_status_id INTEGER NULL,
    is_physical_status_compulsory BOOLEAN NOT NULL DEFAULT FALSE,
    is_mother_tongue_compulsory BOOLEAN NOT NULL DEFAULT FALSE,
    is_eating_habit_compulsory BOOLEAN NOT NULL DEFAULT FALSE,
    is_drinking_habit_compulsory BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_member_partner_basic_preferences_user UNIQUE (user_id),
    CONSTRAINT fk_partner_basic_preferences_user FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT fk_partner_basic_preferences_height_from FOREIGN KEY (height_from_id) REFERENCES master_heights (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_partner_basic_preferences_height_to FOREIGN KEY (height_to_id) REFERENCES master_heights (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_partner_basic_preferences_marital_status FOREIGN KEY (marital_status_id) REFERENCES master_marital_statuses (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_partner_basic_preferences_physical_status FOREIGN KEY (physical_status_id) REFERENCES master_physical_statuses (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT chk_partner_preference_age_from CHECK (
        age_from IS NULL
        OR age_from BETWEEN 18 AND 80
    ),
    CONSTRAINT chk_partner_preference_age_to CHECK (
        age_to IS NULL
        OR age_to BETWEEN 18 AND 80
    ),
    CONSTRAINT chk_partner_preference_age_range CHECK (
        age_from IS NULL
        OR age_to IS NULL
        OR age_from <= age_to
    ),
    CONSTRAINT chk_partner_preference_height_range CHECK (
        height_from_id IS NULL
        OR height_to_id IS NULL
        OR height_from_id <= height_to_id
    )
);

CREATE INDEX IF NOT EXISTS idx_partner_basic_preferences_user ON member_partner_basic_preferences (user_id);

CREATE TABLE IF NOT EXISTS member_partner_preference_mother_tongues (
    id BIGSERIAL PRIMARY KEY,
    partner_basic_preference_id BIGINT NOT NULL,
    mother_tongue_id INTEGER NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_partner_preference_mother_tongue UNIQUE (
        partner_basic_preference_id,
        mother_tongue_id
    ),
    CONSTRAINT fk_partner_preference_mother_tongue_parent FOREIGN KEY (partner_basic_preference_id) REFERENCES member_partner_basic_preferences (id) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT fk_partner_preference_mother_tongue_master FOREIGN KEY (mother_tongue_id) REFERENCES master_mother_tongues (id) ON UPDATE RESTRICT ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS idx_partner_preference_mother_tongue_parent ON member_partner_preference_mother_tongues (partner_basic_preference_id);

CREATE INDEX IF NOT EXISTS idx_partner_preference_mother_tongue_master ON member_partner_preference_mother_tongues (mother_tongue_id);

CREATE TABLE IF NOT EXISTS member_partner_preference_eating_habits (
    id BIGSERIAL PRIMARY KEY,
    partner_basic_preference_id BIGINT NOT NULL,
    eating_habit_id INTEGER NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_partner_preference_eating_habit UNIQUE (
        partner_basic_preference_id,
        eating_habit_id
    ),
    CONSTRAINT fk_partner_preference_eating_habit_parent FOREIGN KEY (partner_basic_preference_id) REFERENCES member_partner_basic_preferences (id) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT fk_partner_preference_eating_habit_master FOREIGN KEY (eating_habit_id) REFERENCES master_eating_habits (id) ON UPDATE RESTRICT ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS idx_partner_preference_eating_habit_parent ON member_partner_preference_eating_habits (partner_basic_preference_id);

CREATE INDEX IF NOT EXISTS idx_partner_preference_eating_habit_master ON member_partner_preference_eating_habits (eating_habit_id);

CREATE TABLE IF NOT EXISTS member_partner_preference_drinking_habits (
    id BIGSERIAL PRIMARY KEY,
    partner_basic_preference_id BIGINT NOT NULL,
    drinking_habit_id INTEGER NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_partner_preference_drinking_habit UNIQUE (
        partner_basic_preference_id,
        drinking_habit_id
    ),
    CONSTRAINT fk_partner_preference_drinking_habit_parent FOREIGN KEY (partner_basic_preference_id) REFERENCES member_partner_basic_preferences (id) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT fk_partner_preference_drinking_habit_master FOREIGN KEY (drinking_habit_id) REFERENCES master_drinking_habits (id) ON UPDATE RESTRICT ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS idx_partner_preference_drinking_habit_parent ON member_partner_preference_drinking_habits (partner_basic_preference_id);

CREATE INDEX IF NOT EXISTS idx_partner_preference_drinking_habit_master ON member_partner_preference_drinking_habits (drinking_habit_id);

BEGIN;

-- ============================================================
-- Rename existing boolean columns
-- FALSE = Preferred Match
-- TRUE  = Strict Match
-- ============================================================

ALTER TABLE member_partner_basic_preferences
RENAME COLUMN is_age_compulsory TO age_match_mode;

ALTER TABLE member_partner_basic_preferences
RENAME COLUMN is_height_compulsory TO height_match_mode;

ALTER TABLE member_partner_basic_preferences
RENAME COLUMN is_marital_status_compulsory TO marital_status_match_mode;

ALTER TABLE member_partner_basic_preferences
RENAME COLUMN is_have_children_compulsory TO have_children_match_mode;

ALTER TABLE member_partner_basic_preferences
RENAME COLUMN is_mother_tongue_compulsory TO mother_tongue_match_mode;

ALTER TABLE member_partner_basic_preferences
RENAME COLUMN is_physical_status_compulsory TO physical_status_match_mode;

ALTER TABLE member_partner_basic_preferences
RENAME COLUMN is_eating_habit_compulsory TO eating_habit_match_mode;

ALTER TABLE member_partner_basic_preferences
RENAME COLUMN is_drinking_habit_compulsory TO drinking_habit_match_mode;

CREATE TABLE IF NOT EXISTS member_partner_religious_preferences (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    community_match_mode BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_partner_religious_preference_user UNIQUE (user_id),
    CONSTRAINT fk_partner_religious_preference_user FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE RESTRICT ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_partner_religious_preference_user ON member_partner_religious_preferences (user_id);

CREATE TABLE IF NOT EXISTS member_partner_preference_communities (
    id BIGSERIAL PRIMARY KEY,
    partner_religious_preference_id BIGINT NOT NULL,
    community_id INTEGER NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_partner_preference_community UNIQUE (
        partner_religious_preference_id,
        community_id
    ),
    CONSTRAINT fk_partner_preference_community_parent FOREIGN KEY (
        partner_religious_preference_id
    ) REFERENCES member_partner_religious_preferences (id) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT fk_partner_preference_community_master FOREIGN KEY (community_id) REFERENCES master_sikh_communities (id) ON UPDATE RESTRICT ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS idx_partner_preference_community_parent ON member_partner_preference_communities (
    partner_religious_preference_id
);

CREATE INDEX IF NOT EXISTS idx_partner_preference_community_master ON member_partner_preference_communities (community_id);

-- ============================================================
-- Professional preference
-- ============================================================

CREATE TABLE IF NOT EXISTS member_partner_professional_preferences (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    education_match_mode BOOLEAN NOT NULL DEFAULT FALSE,
    employed_in_match_mode BOOLEAN NOT NULL DEFAULT FALSE,
    occupation_match_mode BOOLEAN NOT NULL DEFAULT FALSE,
    annual_income_from_id INTEGER NULL,
    annual_income_to_id INTEGER NULL,
    annual_income_match_mode BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_partner_professional_preference_user UNIQUE (user_id),
    CONSTRAINT fk_partner_professional_preference_user FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT fk_partner_professional_income_from FOREIGN KEY (annual_income_from_id) REFERENCES master_annual_incomes (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_partner_professional_income_to FOREIGN KEY (annual_income_to_id) REFERENCES master_annual_incomes (id) ON UPDATE RESTRICT ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS idx_partner_professional_preference_user ON member_partner_professional_preferences (user_id);

CREATE TABLE IF NOT EXISTS member_partner_preference_educations (
    id BIGSERIAL PRIMARY KEY,
    partner_professional_preference_id BIGINT NOT NULL,
    education_id INTEGER NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_partner_preference_education UNIQUE (
        partner_professional_preference_id,
        education_id
    ),
    CONSTRAINT fk_partner_preference_education_parent FOREIGN KEY (
        partner_professional_preference_id
    ) REFERENCES member_partner_professional_preferences (id) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT fk_partner_preference_education_master FOREIGN KEY (education_id) REFERENCES master_educations (id) ON UPDATE RESTRICT ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS idx_partner_preference_education_parent ON member_partner_preference_educations (
    partner_professional_preference_id
);

CREATE TABLE IF NOT EXISTS member_partner_preference_employment_types (
    id BIGSERIAL PRIMARY KEY,
    partner_professional_preference_id BIGINT NOT NULL,
    employed_in VARCHAR(30) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_partner_preference_employment UNIQUE (
        partner_professional_preference_id,
        employed_in
    ),
    CONSTRAINT fk_partner_preference_employment_parent FOREIGN KEY (
        partner_professional_preference_id
    ) REFERENCES member_partner_professional_preferences (id) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT chk_partner_preference_employment_type CHECK (
        employed_in IN (
            'GOVERNMENT_PSU',
            'PRIVATE',
            'BUSINESS',
            'DEFENSE',
            'SELF_EMPLOYED',
            'NOT_WORKING'
        )
    )
);

CREATE INDEX IF NOT EXISTS idx_partner_preference_employment_parent ON member_partner_preference_employment_types (
    partner_professional_preference_id
);

CREATE TABLE IF NOT EXISTS member_partner_preference_occupations (
    id BIGSERIAL PRIMARY KEY,
    partner_professional_preference_id BIGINT NOT NULL,
    occupation_id INTEGER NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_partner_preference_occupation UNIQUE (
        partner_professional_preference_id,
        occupation_id
    ),
    CONSTRAINT fk_partner_preference_occupation_parent FOREIGN KEY (
        partner_professional_preference_id
    ) REFERENCES member_partner_professional_preferences (id) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT fk_partner_preference_occupation_master FOREIGN KEY (occupation_id) REFERENCES master_occupations (id) ON UPDATE RESTRICT ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS idx_partner_preference_occupation_parent ON member_partner_preference_occupations (
    partner_professional_preference_id
);

-- ============================================================
-- Location preference
-- ============================================================

CREATE TABLE IF NOT EXISTS member_partner_location_preferences (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    state_id INTEGER NOT NULL,
    city_id INTEGER NOT NULL,
    location_match_mode BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_partner_location_preference_user UNIQUE (user_id),
    CONSTRAINT fk_partner_location_preference_user FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT fk_partner_location_preference_state FOREIGN KEY (state_id) REFERENCES master_states (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_partner_location_preference_city FOREIGN KEY (city_id) REFERENCES master_cities (id) ON UPDATE RESTRICT ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS idx_partner_location_preference_user ON member_partner_location_preferences (user_id);

CREATE INDEX IF NOT EXISTS idx_partner_location_preference_state_city ON member_partner_location_preferences (state_id, city_id);

-- ============================================================
-- Special request
-- ============================================================

CREATE TABLE IF NOT EXISTS member_partner_special_requests (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    request_text VARCHAR(1000) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_partner_special_request_user UNIQUE (user_id),
    CONSTRAINT fk_partner_special_request_user FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT chk_partner_special_request_not_blank CHECK (btrim(request_text) <> '')
);

CREATE INDEX IF NOT EXISTS idx_partner_special_request_user ON member_partner_special_requests (user_id);

CREATE TABLE IF NOT EXISTS member_partner_preference_annual_incomes (
    id BIGSERIAL PRIMARY KEY,
    partner_professional_preference_id BIGINT NOT NULL,
    annual_income_id INTEGER NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_partner_preference_annual_income UNIQUE (
        partner_professional_preference_id,
        annual_income_id
    ),
    CONSTRAINT fk_partner_preference_income_parent FOREIGN KEY (
        partner_professional_preference_id
    ) REFERENCES member_partner_professional_preferences (id) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT fk_partner_preference_income_master FOREIGN KEY (annual_income_id) REFERENCES master_annual_incomes (id) ON UPDATE RESTRICT ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS idx_partner_preference_income_parent ON member_partner_preference_annual_incomes (
    partner_professional_preference_id
);

CREATE INDEX IF NOT EXISTS idx_partner_preference_income_master ON member_partner_preference_annual_incomes (annual_income_id);

ALTER TABLE member_partner_professional_preferences
DROP CONSTRAINT IF EXISTS fk_partner_professional_income_from;

ALTER TABLE member_partner_professional_preferences
DROP CONSTRAINT IF EXISTS fk_partner_professional_income_to;

ALTER TABLE member_partner_professional_preferences
DROP COLUMN IF EXISTS annual_income_from_id;

ALTER TABLE member_partner_professional_preferences
DROP COLUMN IF EXISTS annual_income_to_id;

-- ============================================================
-- Location: migrate single state/city into junction tables
-- ============================================================

CREATE TABLE IF NOT EXISTS member_partner_preference_states (
    id BIGSERIAL PRIMARY KEY,
    partner_location_preference_id BIGINT NOT NULL,
    state_id INTEGER NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_partner_preference_state UNIQUE (
        partner_location_preference_id,
        state_id
    ),
    CONSTRAINT fk_partner_preference_state_parent FOREIGN KEY (
        partner_location_preference_id
    ) REFERENCES member_partner_location_preferences (id) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT fk_partner_preference_state_master FOREIGN KEY (state_id) REFERENCES master_states (id) ON UPDATE RESTRICT ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS idx_partner_preference_state_parent ON member_partner_preference_states (
    partner_location_preference_id
);

CREATE INDEX IF NOT EXISTS idx_partner_preference_state_master ON member_partner_preference_states (state_id);

CREATE TABLE IF NOT EXISTS member_partner_preference_cities (
    id BIGSERIAL PRIMARY KEY,
    partner_location_preference_id BIGINT NOT NULL,
    city_id INTEGER NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_partner_preference_city UNIQUE (
        partner_location_preference_id,
        city_id
    ),
    CONSTRAINT fk_partner_preference_city_parent FOREIGN KEY (
        partner_location_preference_id
    ) REFERENCES member_partner_location_preferences (id) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT fk_partner_preference_city_master FOREIGN KEY (city_id) REFERENCES master_cities (id) ON UPDATE RESTRICT ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS idx_partner_preference_city_parent ON member_partner_preference_cities (
    partner_location_preference_id
);

CREATE INDEX IF NOT EXISTS idx_partner_preference_city_master ON member_partner_preference_cities (city_id);

ALTER TABLE member_partner_location_preferences
DROP CONSTRAINT IF EXISTS fk_partner_location_preference_state;

ALTER TABLE member_partner_location_preferences
DROP CONSTRAINT IF EXISTS fk_partner_location_preference_city;

ALTER TABLE member_partner_location_preferences
DROP COLUMN IF EXISTS state_id;

ALTER TABLE member_partner_location_preferences
DROP COLUMN IF EXISTS city_id;

CREATE TABLE member_account_status_history (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    action VARCHAR(10) NOT NULL,
    previous_status VARCHAR(20) NOT NULL,
    new_status VARCHAR(20) NOT NULL,
    reason VARCHAR(64) NOT NULL,
    changed_by_admin_id BIGINT NOT NULL,
    changed_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_member_status_history_user FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_member_status_history_admin FOREIGN KEY (changed_by_admin_id) REFERENCES admin_users (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT chk_member_status_history_action CHECK (
        action IN ('BLOCK', 'UNBLOCK')
    ),
    CONSTRAINT chk_member_status_history_previous CHECK (
        previous_status IN ('ACTIVE', 'SUSPENDED')
    ),
    CONSTRAINT chk_member_status_history_new CHECK (
        new_status IN ('ACTIVE', 'SUSPENDED')
    ),
    CONSTRAINT chk_member_status_history_transition CHECK (
        (
            action = 'BLOCK'
            AND previous_status = 'ACTIVE'
            AND new_status = 'SUSPENDED'
        )
        OR (
            action = 'UNBLOCK'
            AND previous_status = 'SUSPENDED'
            AND new_status = 'ACTIVE'
        )
    ),
    CONSTRAINT chk_member_status_history_reason CHECK (
        LENGTH(BTRIM(reason)) BETWEEN 1 AND 64
    )
);

CREATE INDEX idx_member_status_history_user ON member_account_status_history (
    user_id,
    changed_at DESC,
    id DESC
);

CREATE INDEX idx_member_status_history_admin ON member_account_status_history (
    changed_by_admin_id,
    changed_at DESC
);

CREATE INDEX idx_users_admin_member_listing ON users (
    account_status,
    created_at DESC,
    id DESC
)
WHERE
    deleted_at IS NULL;

CREATE TABLE application_error_logs
(
    id                  BIGSERIAL PRIMARY KEY,

/*
 * Correlates this error with http_logs and admin_audit_logs.
 *
 * It intentionally has no foreign key because:
 * - the HTTP log may be inserted after the error;
 * - CLI and cron errors may not have an HTTP log;
 * - error logging must never fail because another log row is unavailable.
 */
request_id VARCHAR(64) NULL,
severity VARCHAR(20) NOT NULL,
message TEXT NOT NULL,

/*
 * Sanitized structured context. Do not store passwords, OTP values,
 * access tokens, cookies, authorization headers or signed URLs.
 */
context JSONB NOT NULL DEFAULT '{}'::jsonb,
environment VARCHAR(20) NOT NULL,
source VARCHAR(20) NOT NULL DEFAULT 'WEB',
request_method VARCHAR(10) NULL,
request_uri TEXT NULL,

/*
 * These values are nullable because logging must not initialize or depend
 * on the application session.
 */
member_user_id      BIGINT NULL,
    admin_user_id       BIGINT NULL,

    created_at          TIMESTAMPTZ NOT NULL
                            DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT chk_application_error_logs_severity
        CHECK (
            severity IN (
                'emergency',
                'alert',
                'critical',
                'error',
                'warning',
                'notice',
                'info',
                'debug'
            )
        ),

    CONSTRAINT chk_application_error_logs_environment
        CHECK (
            environment IN (
                'development',
                'testing',
                'qa',
                'production'
            )
        ),

    CONSTRAINT chk_application_error_logs_source
        CHECK (
            source IN (
                'WEB',
                'CLI',
                'CRON',
                'QUEUE',
                'UNKNOWN'
            )
        ),

    CONSTRAINT fk_application_error_logs_member
        FOREIGN KEY (member_user_id)
        REFERENCES users(id)
        ON UPDATE RESTRICT
        ON DELETE SET NULL,

    CONSTRAINT fk_application_error_logs_admin
        FOREIGN KEY (admin_user_id)
        REFERENCES admin_users(id)
        ON UPDATE RESTRICT
        ON DELETE SET NULL
);

CREATE INDEX idx_application_error_logs_created ON application_error_logs (created_at DESC, id DESC);

CREATE INDEX idx_application_error_logs_severity_created ON application_error_logs (
    severity,
    created_at DESC,
    id DESC
);

CREATE INDEX idx_application_error_logs_request_id ON application_error_logs (request_id)
WHERE
    request_id IS NOT NULL;

CREATE INDEX idx_application_error_logs_request_uri ON application_error_logs (
    request_method,
    created_at DESC
)
WHERE
    request_method IS NOT NULL;

CREATE INDEX idx_application_error_logs_member ON application_error_logs (
    member_user_id,
    created_at DESC
)
WHERE
    member_user_id IS NOT NULL;

CREATE INDEX idx_application_error_logs_admin ON application_error_logs (
    admin_user_id,
    created_at DESC
)
WHERE
    admin_user_id IS NOT NULL;

COMMIT;