# Deployment

This document describes the deployment baseline for the CodeIgniter 4 application. Environment-specific commands may change, but security and verification requirements remain mandatory.

## Environments

Use separate Local, QA/Staging and Production environments. Each requires independent `.env` values, database, base URL, sessions, logging level, provider credentials and secrets. Never commit production secrets.

Current platform assumptions:

- local development: Windows/XAMPP;
- QA/production: Ubuntu EC2 with Apache;
- PHP 8.3;
- PostgreSQL 16;
- application document root at `<project>/public`;
- AWS S3 for future profile image/video storage.

## Deployment flow

```text
Push reviewed code
  ↓
CI/static checks
  ↓
Back up and verify release prerequisites
  ↓
Deploy application files
  ↓
composer install --no-dev --optimize-autoloader
  ↓
Execute pending SQL updates
  ↓
Verify writable permissions and scheduled tasks
  ↓
Reload services when needed
  ↓
Application and authentication smoke tests
```

Deployment must stop when a dependency install, SQL update or required health check fails.

## Web server

Apache must expose only:

```text
<project>/public
```

Do not expose the repository root, `app`, `writable`, `vendor`, `sql`, `scripts` or `.env`. Ensure rewrite rules and HTTPS redirection work before accepting production traffic.

## Application preparation

```bash
composer install --no-dev --optimize-autoloader
```

Grant the web-server user only the permissions required for CI4 writable directories. Operational scripts should run under a controlled deployment/maintenance user and must not be web-accessible.

## Production environment

Set:

```text
CI_ENVIRONMENT = production
```

Configure:

- application/base URL;
- PostgreSQL connection;
- production session driver;
- encryption keys;
- mail/SMS queue and provider settings;
- secure cookie and HTTPS proxy settings;
- application logging and retention;
- Admin/member session separation;
- AWS/S3 credentials only through environment or role-based credentials.

## Database updates

SQL updates live under `sql/updates` and are immutable after deployment. Track executed scripts in a table such as:

```sql
CREATE TABLE schema_updates (
    id BIGSERIAL PRIMARY KEY,
    script_name VARCHAR(255) NOT NULL UNIQUE,
    executed_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

Execute only missing scripts, in filename order. Stop on failure. Never assume reverting application code reverses schema changes.

## Low-downtime principles

- Add compatible columns before requiring them.
- Backfill old data before strict constraints.
- Separate destructive changes from code still using old structures.
- Deploy compatible database changes before dependent code when required.
- Plan index creation for large production tables.
- Keep both old and new application versions compatible during the release window where possible.

## Scheduled operations

Install and verify required cron jobs, including:

```bash
php scripts/log_retention_cleanup.php
```

Confirm the execution user, working directory, PHP binary, output log and locking behaviour. See `operations.md`.

## Logging and audit

Production logs and administrator audit records must never contain plain OTPs, passwords, invitation/reset tokens, session IDs or unnecessary personal data. Confirm retention and restricted access.

## Post-deployment checks

### Public/member application

- homepage and static assets load;
- registration validation works;
- registration transaction and OTP preparation succeed;
- email verification links behave correctly;
- member login works with a verified email;
- member login works with a verified mobile;
- unverified email receives the intended message;
- protected routes redirect unauthenticated users;
- logout clears member authentication safely.

### Administrator application

- Admin login page and assets load;
- verified Admin login succeeds;
- pending/suspended Admin login is denied;
- Admin dashboard loads through `AdminAuthFilter`;
- invitation creation and email queueing work;
- invitation acceptance creates the password, verifies email and activates the account;
- resend invalidates/replaces the prior invitation as intended;
- suspension blocks subsequent authentication;
- privileged routes enforce `SuperAdminFilter`;
- expected audit events are recorded without secrets.

### Platform and operations

- PostgreSQL connection and applied update list are correct;
- sessions persist and secure cookie flags are present;
- CSRF-protected forms submit correctly;
- writable paths have minimal required permissions;
- email queue/provider connectivity works;
- cleanup cron is installed and executable;
- error logs show no new fatal errors;
- rollback commit and database compatibility are understood.

## Rollback

A rollback plan must identify the previous application commit, schema compatibility, required data backup/reversal steps and the point at which rollback is no longer safe. Restore service only after repeating the relevant member, Admin and platform checks.