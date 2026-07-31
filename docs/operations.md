# Operations

## Scheduled work

CLI maintenance scripts live under `scripts/`, reject web execution and run under controlled cron/deployment users. Current log-retention cleanup remains:

```bash
php scripts/log_retention_cleanup.php
```

## Provider monitoring

Monitor SMS/email delivery failures, retry counts, queue age and provider rejections. OTP rows marked `DELIVERY_FAILED` must be unusable and excluded from delivered/pending quotas. Never log OTPs, tokens or complete secret-bearing URLs.

## AWS media operations

Monitor S3 upload/delete failures, CloudFront signing failures, orphaned variants and storage growth. S3 remains private and direct object access must stay blocked. Key rotation and CloudFront signer configuration require a documented rollout.

## Database operations

Apply immutable SQL updates in order and track execution. Backups are useful only when restore is tested. Monitor connections, slow queries, failed transactions, verification-table growth and audit retention.

## Health checks

Verify public/prelaunch routing, password login, OTP login, registration OTP, password recovery, admin login, PostgreSQL, sessions, SMS/email providers, S3 and CloudFront. Health responses must not expose internal details.
