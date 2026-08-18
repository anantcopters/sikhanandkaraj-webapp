BEGIN;

ALTER TABLE users
    ADD COLUMN is_paid BOOLEAN NOT NULL DEFAULT FALSE,
    ADD COLUMN profile_visibility VARCHAR(30)
        NOT NULL DEFAULT 'ALL_MEMBERS';

ALTER TABLE users
    ADD CONSTRAINT chk_users_profile_visibility
    CHECK (
        profile_visibility IN (
            'ALL_MEMBERS',
            'PAID_MEMBERS_ONLY'
        )
    );

COMMENT ON COLUMN users.is_paid IS
    'Temporary QA membership flag until the subscription module is implemented.';

COMMENT ON COLUMN users.profile_visibility IS
    'Controls whether all authenticated members or only paid members can view the full profile.';


/*
 * Pending replacement-email support.
 *
 * A verified primary email remains active while the replacement email
 * is awaiting verification.
 */
ALTER TABLE user_contacts
    ADD COLUMN replaces_contact_id BIGINT NULL,
    ADD COLUMN change_available_at TIMESTAMPTZ NULL;

ALTER TABLE user_contacts
    ADD CONSTRAINT fk_user_contacts_replaced_contact
    FOREIGN KEY (replaces_contact_id)
    REFERENCES user_contacts(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE;

ALTER TABLE user_contacts
    ADD CONSTRAINT chk_user_contacts_email_replacement
    CHECK (
        (
            replaces_contact_id IS NULL
        )
        OR
        (
            contact_type = 'EMAIL'
            AND is_primary = FALSE
            AND is_verified = FALSE
            AND verified_at IS NULL
        )
    );

CREATE UNIQUE INDEX uq_user_pending_email_replacement
    ON user_contacts(user_id)
    WHERE
        contact_type = 'EMAIL'
        AND is_primary = FALSE
        AND replaces_contact_id IS NOT NULL;


/*
 * Member profile reports.
 */
CREATE TABLE member_profile_reports (
    id BIGSERIAL PRIMARY KEY,

    reporter_user_id BIGINT NOT NULL,
    reported_user_id BIGINT NOT NULL,

    description VARCHAR(1000) NOT NULL,

    status VARCHAR(20) NOT NULL DEFAULT 'OPEN',

    reviewed_by_admin_id BIGINT NULL,
    reviewed_at TIMESTAMPTZ NULL,
    resolution_note VARCHAR(1000) NULL,

    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_member_report_reporter
        FOREIGN KEY (reporter_user_id)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_member_report_reported
        FOREIGN KEY (reported_user_id)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_member_report_reviewer
        FOREIGN KEY (reviewed_by_admin_id)
        REFERENCES admin_users(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT chk_member_report_not_self
        CHECK (
            reporter_user_id <> reported_user_id
        ),

    CONSTRAINT chk_member_report_description
        CHECK (
            CHAR_LENGTH(
                BTRIM(description)
            ) BETWEEN 10 AND 1000
        ),

    CONSTRAINT chk_member_report_status
        CHECK (
            status IN (
                'OPEN',
                'REVIEWED',
                'DISMISSED',
                'ACTION_TAKEN'
            )
        ),

    CONSTRAINT chk_member_report_review_state
        CHECK (
            (
                status = 'OPEN'
                AND reviewed_by_admin_id IS NULL
                AND reviewed_at IS NULL
            )
            OR
            (
                status <> 'OPEN'
                AND reviewed_by_admin_id IS NOT NULL
                AND reviewed_at IS NOT NULL
            )
        )
);

CREATE UNIQUE INDEX uq_member_profile_report_open
    ON member_profile_reports(
        reporter_user_id,
        reported_user_id
    )
    WHERE status = 'OPEN';

CREATE INDEX idx_member_profile_report_admin_queue
    ON member_profile_reports(
        status,
        created_at DESC,
        id DESC
    );

CREATE INDEX idx_member_profile_report_reported
    ON member_profile_reports(
        reported_user_id,
        created_at DESC
    );


/*
 * Authenticated member Contact Us submissions.
 */
CREATE TABLE member_contact_requests (
    id BIGSERIAL PRIMARY KEY,

    member_user_id BIGINT NOT NULL,

    message VARCHAR(2000) NOT NULL,

    status VARCHAR(20) NOT NULL DEFAULT 'OPEN',

    reviewed_by_admin_id BIGINT NULL,
    reviewed_at TIMESTAMPTZ NULL,
    response_note VARCHAR(2000) NULL,

    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_member_contact_request_member
        FOREIGN KEY (member_user_id)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_member_contact_request_reviewer
        FOREIGN KEY (reviewed_by_admin_id)
        REFERENCES admin_users(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT chk_member_contact_request_message
        CHECK (
            CHAR_LENGTH(
                BTRIM(message)
            ) BETWEEN 10 AND 2000
        ),

    CONSTRAINT chk_member_contact_request_status
        CHECK (
            status IN (
                'OPEN',
                'IN_PROGRESS',
                'RESOLVED',
                'CLOSED'
            )
        ),

    CONSTRAINT chk_member_contact_request_review_state
        CHECK (
            (
                status = 'OPEN'
                AND reviewed_by_admin_id IS NULL
                AND reviewed_at IS NULL
            )
            OR
            (
                status <> 'OPEN'
                AND reviewed_by_admin_id IS NOT NULL
                AND reviewed_at IS NOT NULL
            )
        )
);

CREATE INDEX idx_member_contact_request_admin_queue
    ON member_contact_requests(
        status,
        created_at DESC,
        id DESC
    );

CREATE INDEX idx_member_contact_request_member
    ON member_contact_requests(
        member_user_id,
        created_at DESC
    );

COMMIT;