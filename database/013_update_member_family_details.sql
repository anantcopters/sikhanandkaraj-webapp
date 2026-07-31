/*
|--------------------------------------------------------------------------
| Family Details update
|--------------------------------------------------------------------------
|
| 1. Add father_name and mother_name.
| 2. Keep both columns nullable so existing rows remain deployable.
| 3. Remove married sibling columns and their irrelevant historical data.
|
| PostgreSQL 16 compatible.
|
*/

BEGIN;

ALTER TABLE member_family_details
    ADD COLUMN IF NOT EXISTS father_name VARCHAR(150) NULL;

ALTER TABLE member_family_details
    ADD COLUMN IF NOT EXISTS mother_name VARCHAR(150) NULL;

/*
 * Existing married-sibling information is no longer part of the
 * application and is deliberately discarded.
 */
ALTER TABLE member_family_details
    DROP COLUMN IF EXISTS married_brothers_count;

ALTER TABLE member_family_details
    DROP COLUMN IF EXISTS married_sisters_count;

COMMIT;