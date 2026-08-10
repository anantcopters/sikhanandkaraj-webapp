BEGIN;

ALTER TABLE member_family_details
ADD COLUMN IF NOT EXISTS field_officer_id BIGINT NULL,
ADD COLUMN IF NOT EXISTS field_officer_code VARCHAR(11) NULL;

/*
 * Both values must either exist together or both remain NULL.
 */
ALTER TABLE member_family_details
DROP CONSTRAINT IF EXISTS chk_member_family_details_field_officer_pair;

ALTER TABLE member_family_details
ADD CONSTRAINT chk_member_family_details_field_officer_pair CHECK (
    (
        field_officer_id IS NULL
        AND field_officer_code IS NULL
    )
    OR (
        field_officer_id IS NOT NULL
        AND field_officer_code IS NOT NULL
    )
);

/*
 * Keep the actual Field Officer relationship protected.
 *
 * RESTRICT is intentional. Field Officers already use soft delete;
 * physically removing an officer must not silently destroy the
 * historical member -> Field Officer association.
 */
ALTER TABLE member_family_details
DROP CONSTRAINT IF EXISTS fk_member_family_details_field_officer;

ALTER TABLE member_family_details
ADD CONSTRAINT fk_member_family_details_field_officer FOREIGN KEY (field_officer_id) REFERENCES field_officers (id) ON UPDATE RESTRICT ON DELETE RESTRICT;

CREATE INDEX IF NOT EXISTS idx_member_family_details_field_officer_id ON member_family_details (field_officer_id)
WHERE
    field_officer_id IS NOT NULL;

/*
 * Protect two rules at database level:
 *
 * 1. field_officer_id and field_officer_code must refer to the
 *    same Field Officer.
 *
 * 2. Once a Field Officer has been assigned to a member,
 *    that assignment cannot be changed or removed.
 */
CREATE OR REPLACE FUNCTION
protect_member_family_field_officer()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
BEGIN
    IF
        TG_OP = 'UPDATE'
        AND OLD.field_officer_id IS NOT NULL
        AND (
            NEW.field_officer_id
                IS DISTINCT FROM OLD.field_officer_id
            OR
            NEW.field_officer_code
                IS DISTINCT FROM OLD.field_officer_code
        )
    THEN
        RAISE EXCEPTION
            'Field Officer assignment cannot be changed once saved.';
    END IF;

    IF NEW.field_officer_id IS NOT NULL THEN
        IF NOT EXISTS (
            SELECT 1
            FROM field_officers fo
            WHERE fo.id = NEW.field_officer_id
              AND UPPER(fo.officer_code)
                    = UPPER(NEW.field_officer_code)
        ) THEN
            RAISE EXCEPTION
                'Field Officer ID and code do not match.';
        END IF;
    END IF;

    RETURN NEW;
END;
$$;

DROP TRIGGER IF EXISTS trg_protect_member_family_field_officer ON member_family_details;

CREATE TRIGGER trg_protect_member_family_field_officer
BEFORE INSERT OR UPDATE
ON member_family_details
FOR EACH ROW
EXECUTE FUNCTION protect_member_family_field_officer();

COMMIT;