# Operations

## Scheduled work

CLI maintenance scripts live under `scripts/`, reject web execution and run under controlled cron/deployment users.

### Email queue worker

The QA email worker runs every minute, processes up to 10 queued emails and uses `flock` to prevent overlapping executions:

```cron
* * * * * cd /var/www/sikhanandkaraj-qa && /usr/bin/flock -n /var/lock/sikhanandkaraj/qa-email-worker.lock /usr/bin/php scripts/email_worker.php 10 >> writable/logs/email-worker.log 2>&1
```

The dedicated `email-worker.log` receives console output and errors. Calls made through CodeIgniter's `log_message()` are also written to the normal dated application logs under `writable/logs/`, subject to the configured environment logging threshold.

### Table cleanup

The table-cleanup runner executes only jobs registered in `app/Config/TableCleanup.php`. The current `read-notifications` job deletes read notifications whose `read_at` value is older than 30 days, in bounded batches.

List registered cleanup jobs:

```bash
php scripts/cleanup_tables.php list
```

Run all registered jobs manually:

```bash
php scripts/cleanup_tables.php all
```

Recommended QA cron entry, running daily at 02:15 server time:

```cron
15 2 * * * cd /var/www/sikhanandkaraj-qa && /usr/bin/flock -n /var/lock/sikhanandkaraj/qa-table-cleanup.lock /usr/bin/php scripts/cleanup_tables.php all >> writable/logs/table-cleanup.log 2>&1
```

Use a separate lock file for each independent job. The cron user must have write permission for `/var/lock/sikhanandkaraj` and the project's `writable/logs` directory. Do not use `chmod 777`.

Before adding the cron entry, test the exact command manually:

```bash
cd /var/www/sikhanandkaraj-qa \
&& /usr/bin/flock -n \
/var/lock/sikhanandkaraj/qa-table-cleanup.lock \
/usr/bin/php scripts/cleanup_tables.php all \
>> writable/logs/table-cleanup.log 2>&1

echo $?
tail -50 writable/logs/table-cleanup.log
```

A successful run returns exit code `0`. Any failed cleanup job or critical bootstrap failure returns exit code `1`.

### Log-retention cleanup

The database log-retention cleanup remains:

```bash
php scripts/log_retention_cleanup.php
```

It removes old records from the configured database log tables. It does not rotate filesystem logs such as `email-worker.log` or `table-cleanup.log`.

### Filesystem log rotation

Dedicated cron output files must be rotated separately. Example QA configuration for `/etc/logrotate.d/sikhanandkaraj-qa`:

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

Validate the configuration before enabling it:

```bash
sudo logrotate --debug /etc/logrotate.d/sikhanandkaraj-qa
```

## Provider monitoring

Monitor SMS/email delivery failures, retry counts, queue age and provider rejections. OTP rows marked `DELIVERY_FAILED` must be unusable and excluded from delivered/pending quotas. Never log OTPs, tokens or complete secret-bearing URLs.

## AWS media operations

Monitor S3 upload/delete failures, CloudFront signing failures, orphaned variants and storage growth. S3 remains private and direct object access must stay blocked. Key rotation and CloudFront signer configuration require a documented rollout.

## Database operations

Apply immutable SQL updates in order and track execution. Backups are useful only when restore is tested. Monitor connections, slow queries, failed transactions, verification-table growth and audit retention.

## Health checks

Verify public/prelaunch routing, password login, OTP login, registration OTP, password recovery, admin login, PostgreSQL, sessions, SMS/email providers, S3 and CloudFront. Health responses must not expose internal details.
