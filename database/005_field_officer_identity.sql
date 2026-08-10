BEGIN;

ALTER TABLE field_officers
ADD COLUMN IF NOT EXISTS aadhaar_number VARCHAR(12),
ADD COLUMN IF NOT EXISTS pan_number VARCHAR(10);

ALTER TABLE field_officers
ADD CONSTRAINT chk_field_officers_aadhaar_number CHECK (
    aadhaar_number IS NULL
    OR aadhaar_number ~ '^[0-9]{12}$'
);

ALTER TABLE field_officers
ADD CONSTRAINT chk_field_officers_pan_number CHECK (
    pan_number IS NULL
    OR pan_number ~ '^[A-Z]{5}[0-9]{4}[A-Z]$'
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_field_officers_aadhaar_number ON field_officers (aadhaar_number)
WHERE
    aadhaar_number IS NOT NULL;

CREATE UNIQUE INDEX IF NOT EXISTS uq_field_officers_pan_number ON field_officers (pan_number)
WHERE
    pan_number IS NOT NULL;

COMMIT;