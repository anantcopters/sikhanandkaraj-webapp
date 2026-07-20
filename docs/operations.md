# Operations

## Purpose

This guide covers recurring maintenance tasks that must run outside normal web requests.

## CLI-only scripts

Operational scripts live under `scripts/` and must reject web execution. They should bootstrap the application safely, return a non-zero exit code on failure and write concise logs without exposing secrets.

## Log retention cleanup

Current cleanup entry point:

```bash
php scripts/log_retention_cleanup.php
```

Run it from the project root using the same PHP version and environment configuration as the deployed application.

The script should:

- remove only files older than the configured retention period;
- restrict deletion to approved writable/log directories;
- never follow arbitrary user-supplied paths;
- record the number of files examined/deleted;
- return failure when cleanup cannot complete safely.

Example cron entry:

```cron
15 2 * * * cd /var/www/sikhanandkaraj-webapp && /usr/bin/php scripts/log_retention_cleanup.php >> writable/logs/maintenance.log 2>&1
```

Adjust the deployment path and PHP binary for the server. Avoid running multiple overlapping instances; use a lock when script duration could exceed the schedule interval.

## Email queue

Invitation, verification and password-related email should use the configured queue/outbox mechanism where available. Operations must monitor:

- pending and failed jobs;
- retry count and last error;
- provider rejection/bounce responses;
- queue age;
- duplicate delivery safeguards.

Do not log full invitation or verification URLs because they contain secrets.

## Database maintenance

- Back up the PostgreSQL database before destructive updates.
- Test restore procedures, not only backup creation.
- Monitor database size, connection usage, slow queries and failed transactions.
- Apply SQL updates in filename order and track successful execution.
- Stop deployment when an update fails.

## Audit and security review

Periodically review administrator audit events for repeated login failures, unusual invitation activity, suspension changes and privileged actions. Access to audit data must itself be restricted and reviewed.

## Health checks

At minimum verify:

- application homepage;
- member login/registration endpoints;
- Admin login endpoint;
- PostgreSQL connectivity;
- session storage;
- mail queue/provider connectivity;
- writable directory permissions;
- recent application error rate.

A health endpoint must avoid exposing secrets, table names or internal exception details.

## Incident handling

For an authentication or token incident:

1. preserve relevant logs and audit records;
2. revoke active invitations/reset tokens where applicable;
3. suspend affected accounts if required;
4. rotate compromised credentials or keys;
5. deploy the fix;
6. document scope, timeline and corrective action.

## Operational checklist

- cron jobs execute under the intended user;
- scripts cannot be reached through Apache;
- logs rotate and retention cleanup runs;
- backups complete and restores are tested;
- queue failures are visible;
- disk space and database capacity are monitored;
- production secrets are not present in repository files;
- security and Admin audit reviews occur regularly.