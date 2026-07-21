BEGIN;

-- Replace this email with a dedicated QA administrator.
UPDATE admin_users
SET
    account_status = 'PENDING',
    updated_at = CURRENT_TIMESTAMP
WHERE email_address = 'anant.prakash@gmail.com'
  AND role = 'ADMIN'
  AND deleted_at IS NULL;

-- Disable any previous unused invitation for this QA administrator.
UPDATE admin_invitations
SET
    revoked_at = CURRENT_TIMESTAMP,
    updated_at = CURRENT_TIMESTAMP
WHERE admin_user_id = (
    SELECT id
    FROM admin_users
    WHERE email_address = 'anant.prakash@gmail.com'
      AND role = 'ADMIN'
      AND deleted_at IS NULL
)
AND used_at IS NULL
AND revoked_at IS NULL;

-- Create a new usable invitation.
INSERT INTO admin_invitations (
    admin_user_id,
    token_hash,
    expires_at,
    used_at,
    revoked_at,
    created_by,
    created_at,
    updated_at
)
SELECT
    id,
    encode(
        digest(
            'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
            'sha256'
        ),
        'hex'
    ),
    CURRENT_TIMESTAMP + INTERVAL '24 hours',
    NULL,
    NULL,
    id,
    CURRENT_TIMESTAMP,
    CURRENT_TIMESTAMP
FROM admin_users
WHERE email_address = 'anant.prakash@gmail.com'
  AND role = 'ADMIN'
  AND deleted_at IS NULL;

COMMIT;