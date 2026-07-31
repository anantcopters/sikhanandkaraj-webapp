CREATE INDEX IF NOT EXISTS
    idx_contact_verifications_contact_purpose_status
ON contact_verifications (
    user_contact_id,
    purpose,
    status,
    id DESC
);

ALTER TABLE contact_verifications
DROP CONSTRAINT IF EXISTS contact_verifications_status_check;

ALTER TABLE contact_verifications
ADD CONSTRAINT contact_verifications_status_check
CHECK (
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
ADD CONSTRAINT chk_contact_verification_purpose
CHECK (
    purpose IN (
        'REGISTER',
        'PASSWORD_RESET',
        'PENDING',
        'VERIFIED',
        'EXPIRED',
        'CANCELLED'
    )
);