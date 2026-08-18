BEGIN;

/*
 * Remove permanent reporter/profile uniqueness.
 *
 * A dismissed report remains in history but must not prevent the
 * member from reporting the same profile again.
 */
DROP INDEX IF EXISTS
    uq_member_profile_report_once;

DROP INDEX IF EXISTS
    uq_member_profile_report_open;

/*
 * Only one active/actionable report may exist for the same
 * reporter and reported profile.
 *
 * DISMISSED reports are intentionally excluded.
 */
CREATE UNIQUE INDEX
    uq_member_profile_report_active
ON member_profile_reports(
    reporter_user_id,
    reported_user_id
)
WHERE status IN (
    'OPEN',
    'REVIEWED',
    'ACTION_TAKEN'
);

COMMIT;