# Deployment

_Last reconciled with `development` HEAD `f2b16aa1a3ce7c53278b3b68d20524d3970fca05` on 2026-08-12._

## Environments

- Local: Windows/XAMPP.
- QA and production: Ubuntu EC2, Apache, PHP 8.3 and PostgreSQL 16.
- Apache document root points only to `<project>/public`.
- Each environment has separate DB, base URL, session cookie, provider credentials, AWS resources and secrets.

## Deployment mode versus CI environment

`CI_ENVIRONMENT` controls CodeIgniter runtime behavior/diagnostics. `APP_DEPLOYMENT` controls product deployment behavior such as production prelaunch routing and explicit non-production tooling gates.

QA may intentionally run with `CI_ENVIRONMENT=production` for production-like behavior while `APP_DEPLOYMENT=qa`. Do not infer product deployment mode from `CI_ENVIRONMENT` alone.

## HTTPS and cookies

CI4 `ForceHTTPS` remains the application safeguard; Apache should perform the primary HTTP→HTTPS redirect in QA/production.

Repository-safe local defaults keep forced HTTPS disabled. Environment `.env` overrides own the deployed values.

| Environment | Typical root | HTTPS | Secure cookies | Session cookie |
|---|---|---:|---:|---|
| Local | local checkout | no | no | `sak_session` (local) |
| QA | `/var/www/sikhanandkaraj-qa` | yes | yes | `sak_qa_session` |
| Production | `/var/www/sikhanandkaraj-webapp` | yes | yes | `sak_session` |

Never trust `0.0.0.0/0` as a proxy source. When TLS terminates at a trusted proxy/load balancer, configure only the real trusted proxy subnet and preserve `X-Forwarded-Proto: https`.

## Product route mode

When `APP_DEPLOYMENT=production`, current public home/login/register entry points redirect to the prelaunch profile flow. Launching the normal member entry flow therefore requires an explicit deployment-mode/routing decision; changing only `CI_ENVIRONMENT` is not sufficient.

Public legal/information and SAK Volunteer routes remain intentionally independent of this homepage redirect logic where configured in `Routes.php`.

## Database deployment contract

The immutable baseline is:

```text
app/Database/sikhanandkaraj_db.sql   # version 000
```

Incremental deployed changes are:

```text
database/001_*.sql
database/002_*.sql
...
database/009_*.sql   # current latest as of this reconciliation
```

CI4 `app/Database/Migrations` is not the deployment mechanism.

### Target classification

```text
FRESH
  → run baseline 000
  → verify baseline
  → run 001+ in numeric order
  → record successful increments

EXISTING
  → never run baseline 000
  → read deployment_sql_history
  → run only missing 001+ in numeric order

UNKNOWN / PARTIAL
  → STOP
  → do not guess, rerun 000, or mark scripts manually
  → reconcile actual schema with deployment_sql_history first
```

A numbered script is recorded only after it succeeds. Any SQL failure stops deployment.

## Secrets and private signing material

Never deploy CloudFront private keys, AWS secret keys, SMS/email credentials or encryption keys from source control.

CloudFront signing keys may live outside the application checkout. Preserve least-privilege permissions (for example root-owned, service-group readable, mode `0640`). Verify both the Apache/PHP user and every authorized CLI deployment/maintenance user that instantiates media services can read the key.

A web upload/display succeeding does not prove a CLI process can read the signer key; Linux permissions are evaluated for the actual process user.

## QA environment essentials

Example intent (values remain environment-specific):

```ini
CI_ENVIRONMENT = production
APP_DEPLOYMENT = qa
app.baseURL = 'https://qa.sikhanandkaraj.com/'
app.forceGlobalSecureRequests = true
cookie.secure = true
cookie.httponly = true
cookie.samesite = 'Lax'
session.cookieName = sak_qa_session
```

QA-specific development-profile loading must additionally be explicitly enabled; production must remain prohibited.

## Production essentials

```ini
CI_ENVIRONMENT = production
APP_DEPLOYMENT = production
app.baseURL = 'https://www.sikhanandkaraj.com/'
app.forceGlobalSecureRequests = true
cookie.secure = true
cookie.httponly = true
cookie.samesite = 'Lax'
session.cookieName = sak_session
```

Use one canonical production hostname consistently across DNS, Apache redirects, certificates and `app.baseURL`.

## Apache checks

Before reload:

```bash
sudo apachectl configtest
```

Reload only after `Syntax OK`:

```bash
sudo systemctl reload apache2
```

Validate HTTP→HTTPS and redirect chains with `curl -I`/`curl -IL --max-redirs 5`. QA/production session and CSRF cookies must be `Secure` and `HttpOnly`.

## Release order

```text
Reviewed commit
  → classify database target (FRESH / EXISTING / STOP)
  → backup + prerequisite checks
  → composer install --no-dev --optimize-autoloader
  → run baseline only when FRESH
  → apply pending numbered SQL in order
  → deploy files/configuration
  → verify writable + lock-directory permissions
  → verify signer/provider secret permissions
  → verify cron/CLI commands as their real OS users
  → verify HTTPS/cookies/route mode
  → run smoke tests
```

## Mandatory smoke tests

- HTTP redirects once to canonical HTTPS; no loop.
- Local HTTP remains usable where local TLS is not configured.
- QA and production cookies are Secure/HttpOnly.
- Mobile-only registration and OTP activation.
- Password + OTP member login.
- Forgot/reset password through verified-mobile OTP.
- Member dashboard/profile sections/completion.
- Search and Matches member cards/results.
- Interest Received/Sent counts, filters and pending Accept/Decline.
- Notifications.
- Private media upload and authorized signed delivery.
- Admin role restrictions, member/photo/prelaunch review.
- SAK Volunteer self-registration/review/login/profile visibility.
- PostgreSQL, sessions, SMS/email providers, S3 and CloudFront.
- Authorized CLI profile loader on development/QA only, when intentionally enabled.

Never continue a release with unknown/partial DB state or unreadable required secrets.