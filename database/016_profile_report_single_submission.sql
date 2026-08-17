BEGIN;

/*
 * Stop the migration when historical duplicate reporter/target pairs
 * already exist. Review those records before enforcing uniqueness.
 */
DO $$
BEGIN
    IF EXISTS (
        SELECT 1
        FROM member_profile_reports
        GROUP BY
            reporter_user_id,
            reported_user_id
        HAVING COUNT(*) > 1
    ) THEN
        RAISE EXCEPTION
            'Duplicate member profile report pairs exist. '
            'Review them before running migration 016.';
    END IF;
END
$$;

/*
 * The old partial index prevents only duplicate OPEN reports.
 */
DROP INDEX IF EXISTS
    uq_member_profile_report_open;

/*
 * A member may report another member only once, regardless
 * of the report's eventual administrator status.
 */
CREATE UNIQUE INDEX
    uq_member_profile_report_once
ON member_profile_reports(
    reporter_user_id,
    reported_user_id
);

COMMIT;