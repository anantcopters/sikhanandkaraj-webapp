# Operations

_Last reconciled with `development` HEAD `f2b16aa1a3ce7c53278b3b68d20524d3970fca05` on 2026-08-12._

## CLI operational rule

Maintenance/development scripts live under `scripts/`, reject browser execution and must run from the project root under an explicitly authorized OS user.

The OS user is part of the runtime contract: it needs only the filesystem/database/provider permissions required by that command. Do not fix permission problems with `chmod 777` or world-readable secrets.

## Email queue worker

QA email worker example:

```cron
* * * * * cd /var/www/sikhanandkaraj-qa && /usr/bin/flock -n /var/lock/sikhanandkaraj/qa-email-worker.lock /usr/bin/php scripts/email_worker.php 10 >> writable/logs/email-worker.log 2>&1
```

The worker processes a bounded batch and `flock` prevents overlap. Provider failures/retries must remain observable without logging message secrets/tokens.

## Table cleanup

The generic table cleanup runner executes only jobs registered in `app/Config/TableCleanup.php`.

```bash
php scripts/cleanup_tables.php list
php scripts/cleanup_tables.php all
```

The configured read-notification cleanup removes read notifications older than the retention window in bounded batches.

Recommended QA cron:

```cron
15 2 * * * cd /var/www/sikhanandkaraj-qa && /usr/bin/flock -n /var/lock/sikhanandkaraj/qa-table-cleanup.lock /usr/bin/php scripts/cleanup_tables.php all >> writable/logs/table-cleanup.log 2>&1
```

Use separate lock files for independent jobs.

## Other cleanup jobs

```bash
php scripts/log_retention_cleanup.php
php scripts/email_queue_cleanup.php
```

Database cleanup does not rotate filesystem logs. Dedicated cron output files require OS-level log rotation.

Example QA logrotate scope:

```text
/var/www/sikhanandkaraj-qa/writable/logs/email-worker.log
/var/www/sikhanandkaraj-qa/writable/logs/table-cleanup.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
    copytruncate
}
```

Validate changes with `logrotate --debug` before enabling.

## Development/QA profile loader

Run only in an explicitly allowed development/QA deployment:

```bash
cd /var/www/sikhanandkaraj-qa
php scripts/load_development_profiles.php
```

The command reads numeric source folders under `public/assets/images/male/` and `female/`, creates deterministic QA member profiles and uploads their images through the normal processing/S3 pipeline. Reruns skip already-imported source folders using `development_profile_imports`.

Operational prerequisites:

- `APP_DEPLOYMENT` must explicitly allow development/QA use;
- the loader enable flag must be true;
- PostgreSQL must be reachable;
- AWS/S3 configuration must be valid;
- the CloudFront signing configuration must be valid because `AwsMediaService` currently instantiates `CloudFrontService` even for upload-only workflows;
- the CLI OS user must be able to read the configured CloudFront private key;
- `writable/` and temp paths must be writable.

### CloudFront private key permissions

On QA the signing key may intentionally live outside the Git checkout, e.g. under a protected `/var/www/.../cloudfront/` directory. A secure pattern is:

```text
owner: root
service group: www-data (or dedicated media group)
mode: 0640
```

If Apache/PHP can display signed photos but a CLI command reports `CloudFront private signing key is unavailable`, verify the CLI user separately:

```bash
whoami
namei -l /absolute/path/to/member-media-private.pem
php -r '$p="/absolute/path/to/member-media-private.pem"; var_dump(is_file($p), is_readable($p));'
```

Grant the authorized CLI user group membership or execute the command as the intended service user. Do not make the private key world-readable.

## Provider monitoring

Monitor SMS/email delivery failures, retry counts, queue age and provider rejections. OTP rows marked `DELIVERY_FAILED` are unusable and excluded from usable pending/delivered credential counts.

## AWS media operations

Monitor S3 upload/delete failures, CloudFront signing failures, orphaned variants and storage growth. S3 remains private and direct object access stays blocked. Signing-key rotation requires a documented deployment plan and permission verification for both web and CLI process users.

## Database operations

Database rollout is baseline 000 + immutable numbered increments under `database/`. Before any deployment, classify the target:

```text
FRESH      → run baseline 000, verify, apply 001+
EXISTING   → never run baseline 000, apply only missing increments
UNKNOWN    → STOP and reconcile
```

Record a numbered script in `deployment_sql_history` only after successful execution. Stop immediately on SQL failure.

Backups are useful only when restore is tested. Monitor connections, slow queries, failed transactions, verification-table growth, audit/notification retention and deployment-ledger consistency.

## Health/smoke checks

After infrastructure or application deployment verify at minimum:

- public/prelaunch route mode;
- member registration + mobile OTP activation;
- password and OTP login;
- password recovery;
- member dashboard/profile sections/completion;
- Search and Matches;
- Interest status/actions;
- notifications;
- private media upload + authorized signed delivery;
- admin login/role restrictions/reviews;
- SAK Volunteer registration/review/login/dashboard/submitted-profile visibility;
- PostgreSQL, session, SMS/email provider, S3 and CloudFront connectivity.

Health responses/logs must not expose internal secrets.

## Video Introduction workers

Install FFmpeg and verify both binaries are available to the CLI worker:

```bash
sudo apt-get update
sudo apt-get install -y ffmpeg
/usr/bin/ffmpeg -version
/usr/bin/ffprobe -version