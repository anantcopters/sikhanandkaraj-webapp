# Deployment

This document describes the intended deployment flow. Environment-specific commands may change as infrastructure is finalized.

## Environments

Recommended environments:

```text
Local
Development/Staging
Production
```

Each environment should have its own:

- `.env` values;
- database;
- base URL;
- session configuration;
- logging level;
- credentials and secrets.

Never commit production secrets.

## Web server

The web server document root must point to:

```text
<project>/public
```

Do not expose the project root.

## Basic deployment flow

```text
Push code
  ↓
CI checks
  ↓
Deploy application files
  ↓
Install production dependencies
  ↓
Execute pending SQL updates
  ↓
Clear/warm required caches
  ↓
Restart/reload services when needed
  ↓
Health check
```

## Application preparation

Typical production steps:

```bash
composer install --no-dev --optimize-autoloader
```

Ensure writable permissions for CI4 writable directories without granting unnecessary permissions to the full repository.

## Environment settings

Production should use:

```text
CI_ENVIRONMENT = production
```

Configure:

- application URL;
- PostgreSQL connection;
- session driver;
- encryption keys;
- email/SMS provider settings;
- logging;
- secure cookies;
- HTTPS proxy settings if applicable.

## Database updates

SQL updates live under:

```text
sql/updates
```

A future deployment script should execute only files not previously recorded as executed.

Recommended tracking table:

```sql
CREATE TABLE schema_updates (
    id BIGSERIAL PRIMARY KEY,
    script_name VARCHAR(255) NOT NULL UNIQUE,
    executed_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

Deployment must stop when a database update fails.

## Zero or low downtime principles

- Add nullable columns before making them required when old rows exist.
- Backfill data before adding strict constraints.
- Avoid destructive schema changes in the same release as code that still depends on old columns.
- Deploy compatible database changes before code when necessary.
- Create indexes carefully on large production tables.

## Session table

When database sessions are enabled, ensure the `ci_sessions` table exists before application traffic reaches the new release.

## Logging

Production logs should contain enough context to investigate errors but must not include:

- plain OTPs;
- passwords;
- full authentication tokens;
- sensitive personal data unless strictly required.

## Post-deployment checks

Test:

- homepage loads;
- static assets load;
- registration form validation works;
- database connection works;
- session persists;
- CSRF submission works;
- registration redirects to OTP page;
- error logs have no new fatal errors.

## Rollback

A rollback plan should include:

- previous application commit;
- database compatibility assessment;
- backup or reversal SQL for destructive changes;
- clear decision on whether a failed database update can be safely reversed.

Never assume application rollback automatically reverses database changes.