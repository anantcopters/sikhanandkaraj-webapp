BEGIN;

/*
 * Add member-visible support reference.
 *
 * Existing rows are assigned a deterministic reference during migration.
 * All new references are generated randomly by the application.
 */
ALTER TABLE member_contact_requests
ADD COLUMN request_reference VARCHAR(14);

UPDATE member_contact_requests
SET
    request_reference = 'SAKSUPP-' || LPAD(id::TEXT, 6, '0')
WHERE
    request_reference IS NULL;

ALTER TABLE member_contact_requests
ALTER COLUMN request_reference
SET NOT NULL;

ALTER TABLE member_contact_requests
ADD CONSTRAINT uq_member_contact_request_reference UNIQUE (request_reference);

ALTER TABLE member_contact_requests
ADD CONSTRAINT chk_member_contact_request_reference CHECK (
    request_reference ~ '^SAKSUPP-[0-9]{6}$'
);

/*
 * Preserve existing data before reducing field lengths.
 */
UPDATE member_contact_requests
SET
    message = LEFT(BTRIM(message), 255);

UPDATE member_contact_requests
SET
    response_note = LEFT(BTRIM(response_note), 255)
WHERE
    response_note IS NOT NULL;

/*
 * Convert old statuses to the new two-status workflow.
 */
UPDATE member_contact_requests
SET
    status = 'OPEN'
WHERE
    status = 'IN_PROGRESS';

UPDATE member_contact_requests
SET
    status = 'RESOLVED'
WHERE
    status = 'CLOSED';

ALTER TABLE member_contact_requests
DROP CONSTRAINT chk_member_contact_request_message;

ALTER TABLE member_contact_requests
DROP CONSTRAINT chk_member_contact_request_status;

ALTER TABLE member_contact_requests
DROP CONSTRAINT chk_member_contact_request_review_state;

ALTER TABLE member_contact_requests
ALTER COLUMN message TYPE VARCHAR(255),
ALTER COLUMN response_note TYPE VARCHAR(255);

ALTER TABLE member_contact_requests
ADD CONSTRAINT chk_member_contact_request_message CHECK (
    CHAR_LENGTH(BTRIM(message)) BETWEEN 10 AND 255
);

ALTER TABLE member_contact_requests
ADD CONSTRAINT chk_member_contact_request_response CHECK (
    response_note IS NULL
    OR CHAR_LENGTH(BTRIM(response_note)) BETWEEN 5 AND 255
);

ALTER TABLE member_contact_requests
ADD CONSTRAINT chk_member_contact_request_status CHECK (
    status IN ('OPEN', 'RESOLVED')
);

ALTER TABLE member_contact_requests
ADD CONSTRAINT chk_member_contact_request_review_state CHECK (
    (
        status = 'OPEN'
        AND reviewed_by_admin_id IS NULL
        AND reviewed_at IS NULL
        AND response_note IS NULL
    )
    OR (
        status = 'RESOLVED'
        AND reviewed_by_admin_id IS NOT NULL
        AND reviewed_at IS NOT NULL
        AND response_note IS NOT NULL
    )
);

CREATE INDEX idx_member_contact_request_member_history ON member_contact_requests (
    member_user_id,
    created_at DESC,
    id DESC
);

COMMIT;