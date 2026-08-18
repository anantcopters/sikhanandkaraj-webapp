BEGIN;

ALTER TABLE contact_verifications
    DROP CONSTRAINT IF EXISTS
        chk_contact_verification_purpose;

ALTER TABLE contact_verifications
    ADD CONSTRAINT chk_contact_verification_purpose
    CHECK (
        purpose IN (
            'REGISTER',
            'LOGIN',
            'CHANGE_MOBILE',
            'CHANGE_EMAIL',
            'PASSWORD_RESET',
            'PASSWORD_SETUP'
        )
    );

COMMIT;