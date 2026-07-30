BEGIN;

-- -------------------------------------------------------------------------
-- 1. Remove foreign keys referencing the Sub-community master table.
--    This discovers the actual constraint names instead of assuming them.
-- -------------------------------------------------------------------------
DO $$
DECLARE
    constraint_record RECORD;
BEGIN
    FOR constraint_record IN
        SELECT
            ns.nspname AS schema_name,
            tbl.relname AS table_name,
            con.conname AS constraint_name
        FROM pg_constraint con
        JOIN pg_class tbl
            ON tbl.oid = con.conrelid
        JOIN pg_namespace ns
            ON ns.oid = tbl.relnamespace
        WHERE con.contype = 'f'
          AND con.confrelid =
              to_regclass('public.master_sikh_subcommunities')
    LOOP
        EXECUTE format(
            'ALTER TABLE %I.%I DROP CONSTRAINT IF EXISTS %I',
            constraint_record.schema_name,
            constraint_record.table_name,
            constraint_record.constraint_name
        );
    END LOOP;
END
$$;

-- -------------------------------------------------------------------------
-- 2. Remove any separately created indexes involving Sub-community columns.
--    PostgreSQL already drops indexes owned by a dropped constraint/column,
--    but these statements handle known standalone index names if present.
-- -------------------------------------------------------------------------
DROP INDEX IF EXISTS
    public.idx_member_family_details_subcommunity_id;

DROP INDEX IF EXISTS
    public.idx_member_sikh_religious_details_subcommunity_id;

DROP INDEX IF EXISTS
    public.idx_prelaunch_profiles_sikh_subcommunity_id;

DROP INDEX IF EXISTS
    public.idx_master_sikh_subcommunities_community_id;

DROP INDEX IF EXISTS
    public.idx_master_sikh_subcommunities_community_active_order;

-- -------------------------------------------------------------------------
-- 3. Remove Sub-community columns from every application table.
-- -------------------------------------------------------------------------
ALTER TABLE public.member_family_details
    DROP COLUMN IF EXISTS subcommunity_id;

ALTER TABLE public.member_sikh_religious_details
    DROP COLUMN IF EXISTS subcommunity_id;

ALTER TABLE public.prelaunch_profiles
    DROP COLUMN IF EXISTS sikh_subcommunity_id;

-- -------------------------------------------------------------------------
-- 4. Remove the obsolete master table.
--    CASCADE acts as a final safeguard for any unknown remaining dependency.
-- -------------------------------------------------------------------------
DROP TABLE IF EXISTS
    public.master_sikh_subcommunities
    CASCADE;

COMMIT;