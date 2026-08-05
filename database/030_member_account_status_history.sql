BEGIN;

CREATE TABLE member_account_status_history
(
    id                  BIGSERIAL PRIMARY KEY,

    user_id             BIGINT NOT NULL,
    action              VARCHAR(10) NOT NULL,

    previous_status     VARCHAR(20) NOT NULL,
    new_status          VARCHAR(20) NOT NULL,

    reason              VARCHAR(64) NOT NULL,

    changed_by_admin_id BIGINT NOT NULL,
    changed_at          TIMESTAMPTZ NOT NULL
                            DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_member_status_history_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT fk_member_status_history_admin
        FOREIGN KEY (changed_by_admin_id)
        REFERENCES admin_users(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT chk_member_status_history_action
        CHECK (
            action IN (
                'BLOCK',
                'UNBLOCK'
            )
        ),

    CONSTRAINT chk_member_status_history_previous
        CHECK (
            previous_status IN (
                'ACTIVE',
                'SUSPENDED'
            )
        ),

    CONSTRAINT chk_member_status_history_new
        CHECK (
            new_status IN (
                'ACTIVE',
                'SUSPENDED'
            )
        ),

    CONSTRAINT chk_member_status_history_transition
        CHECK (
            (
                action = 'BLOCK'
                AND previous_status = 'ACTIVE'
                AND new_status = 'SUSPENDED'
            )
            OR
            (
                action = 'UNBLOCK'
                AND previous_status = 'SUSPENDED'
                AND new_status = 'ACTIVE'
            )
        ),

    CONSTRAINT chk_member_status_history_reason
        CHECK (
            LENGTH(BTRIM(reason)) BETWEEN 1 AND 64
        )
);

CREATE INDEX idx_member_status_history_user
    ON member_account_status_history (
        user_id,
        changed_at DESC,
        id DESC
    );

CREATE INDEX idx_member_status_history_admin
    ON member_account_status_history (
        changed_by_admin_id,
        changed_at DESC
    );

CREATE INDEX idx_users_admin_member_listing
    ON users (
        account_status,
        created_at DESC,
        id DESC
    )
    WHERE deleted_at IS NULL;

COMMIT;