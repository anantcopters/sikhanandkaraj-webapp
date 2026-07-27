CREATE INDEX IF NOT EXISTS
    idx_contact_verifications_contact_purpose_status
ON contact_verifications (
    user_contact_id,
    purpose,
    status,
    id DESC
);

ALTER TABLE contact_verifications
DROP CONSTRAINT IF EXISTS chk_contact_verifications_purpose;

ALTER TABLE contact_verifications
ADD CONSTRAINT chk_contact_verifications_purpose
CHECK (
    purpose IN (
        'REGISTER',
        'PASSWORD_RESET'
    )
);