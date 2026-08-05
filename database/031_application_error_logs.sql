BEGIN;

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
    request_id          VARCHAR(64) NULL,

    severity            VARCHAR(20) NOT NULL,
    message             TEXT NOT NULL,

    /*
     * Sanitized structured context. Do not store passwords, OTP values,
     * access tokens, cookies, authorization headers or signed URLs.
     */
    context             JSONB NOT NULL
                            DEFAULT '{}'::jsonb,

    environment         VARCHAR(20) NOT NULL,
    source              VARCHAR(20) NOT NULL
                            DEFAULT 'WEB',

    request_method      VARCHAR(10) NULL,
    request_uri         TEXT NULL,

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

CREATE INDEX idx_application_error_logs_created
    ON application_error_logs (
        created_at DESC,
        id DESC
    );

CREATE INDEX idx_application_error_logs_severity_created
    ON application_error_logs (
        severity,
        created_at DESC,
        id DESC
    );

CREATE INDEX idx_application_error_logs_request_id
    ON application_error_logs (
        request_id
    )
    WHERE request_id IS NOT NULL;

CREATE INDEX idx_application_error_logs_request_uri
    ON application_error_logs (
        request_method,
        created_at DESC
    )
    WHERE request_method IS NOT NULL;

CREATE INDEX idx_application_error_logs_member
    ON application_error_logs (
        member_user_id,
        created_at DESC
    )
    WHERE member_user_id IS NOT NULL;

CREATE INDEX idx_application_error_logs_admin
    ON application_error_logs (
        admin_user_id,
        created_at DESC
    )
    WHERE admin_user_id IS NOT NULL;
COMMIT;