BEGIN;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS is_aadhaar_verified
        BOOLEAN NOT NULL DEFAULT FALSE;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS aadhaar_verified_at
        TIMESTAMP WITH TIME ZONE NULL;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS is_selfie_verified
        BOOLEAN NOT NULL DEFAULT FALSE;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS selfie_verified_at
        TIMESTAMP WITH TIME ZONE NULL;

COMMENT ON COLUMN users.is_aadhaar_verified IS
    'Whether the member Aadhaar identity has been verified.';

COMMENT ON COLUMN users.aadhaar_verified_at IS
    'UTC timestamp when Aadhaar verification was completed.';

COMMENT ON COLUMN users.is_selfie_verified IS
    'Whether the member selfie identity check has been verified.';

COMMENT ON COLUMN users.selfie_verified_at IS
    'UTC timestamp when selfie verification was completed.';

COMMIT;