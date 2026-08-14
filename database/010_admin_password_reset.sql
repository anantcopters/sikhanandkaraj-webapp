BEGIN;

CREATE TABLE IF NOT EXISTS admin_password_reset_verifications (
    id BIGSERIAL PRIMARY KEY,

    admin_user_id BIGINT NOT NULL,

    otp_hash VARCHAR(255) NOT NULL,

    expires_at TIMESTAMP WITH TIME ZONE NOT NULL,

    attempt_count SMALLINT NOT NULL DEFAULT 0,

    resend_count SMALLINT NOT NULL DEFAULT 0,

    status VARCHAR(30) NOT NULL DEFAULT 'PENDING',

    verified_at TIMESTAMP WITH TIME ZONE NULL,

    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_admin_password_reset_admin
        FOREIGN KEY (admin_user_id)
        REFERENCES admin_users(id)
        ON DELETE CASCADE,

    CONSTRAINT chk_admin_password_reset_status
        CHECK (
            status IN (
                'PENDING',
                'VERIFIED',
                'EXPIRED',
                'CANCELLED',
                'DELIVERY_FAILED'
            )
        )
);

CREATE INDEX IF NOT EXISTS idx_admin_password_reset_admin_status
    ON admin_password_reset_verifications (
        admin_user_id,
        status
    );

CREATE INDEX IF NOT EXISTS idx_admin_password_reset_admin_created
    ON admin_password_reset_verifications (
        admin_user_id,
        created_at
    );

CREATE INDEX IF NOT EXISTS idx_admin_password_reset_expires
    ON admin_password_reset_verifications (
        expires_at
    );

COMMIT;