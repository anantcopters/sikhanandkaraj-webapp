BEGIN;

/*
 * Registration no longer creates an EMAIL contact.
 *
 * In the current normalized schema, optional email is represented by the
 * absence of an EMAIL row in user_contacts. We deliberately retain NOT NULL
 * constraints on contact_value and normalized_value because every contact
 * row that does exist must contain a valid contact.
 *
 * Remove invalid blank EMAIL rows that may have been created by older or
 * partially deployed registration code.
 */
DELETE FROM user_contacts
WHERE contact_type = 'EMAIL'
  AND (
      BTRIM(contact_value) = ''
      OR BTRIM(normalized_value) = ''
  );

/*
 * Keep email uniqueness for users who add an email later through account
 * settings or another controlled workflow.
 */
CREATE UNIQUE INDEX IF NOT EXISTS
    uq_user_contacts_email_normalized
ON user_contacts (normalized_value)
WHERE contact_type = 'EMAIL';

/*
 * Ensure that each user can have only one primary contact for each contact
 * type. A user may have no EMAIL row at all.
 */
CREATE UNIQUE INDEX IF NOT EXISTS
    uq_user_primary_contact_type
ON user_contacts (user_id, contact_type)
WHERE is_primary = TRUE;

UPDATE prelaunch_profiles
SET email = NULL
WHERE TRIM(COALESCE(email, '')) = '';

ALTER TABLE prelaunch_profiles
ALTER COLUMN email DROP NOT NULL;

ALTER TABLE contact_verifications
DROP CONSTRAINT IF EXISTS
chk_contact_verification_purpose;

ALTER TABLE contact_verifications
ADD CONSTRAINT
chk_contact_verification_purpose

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
COMMIT;