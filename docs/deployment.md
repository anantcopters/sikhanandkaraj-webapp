# Deployment

## Environments

- Local: Windows/XAMPP.
- QA and production: Ubuntu EC2, Apache, PHP 8.3 and PostgreSQL 16.
- Apache document root must point only to `<project>/public`.
- Each environment has separate database, base URL, sessions, provider credentials, AWS resources and secrets.

## Production route mode

Production currently supports prelaunch routing: public home, login and registration entry points redirect to the prelaunch profile flow. Confirm this mode before every release; launch requires an explicit route/configuration decision rather than accidental environment behavior.

## Release order

```text
Reviewed commit
  → backup and prerequisites
  → composer install --no-dev --optimize-autoloader
  → apply pending SQL in order
  → deploy files
  → verify writable permissions and cron
  → smoke-test public, member, admin, prelaunch, SMS and media paths
```

## Mandatory smoke tests

- mobile-only registration and OTP activation;
- password and OTP login choices;
- password recovery through verified mobile;
- profile sections and completion calculation;
- private-media upload and signed delivery;
- admin role restrictions and photo/profile review;
- prelaunch form, field-officer verification and optional email;
- no-cache headers on sensitive pages;
- PostgreSQL/session/provider connectivity.

Never deploy secrets, CloudFront private keys or AWS access keys from source control. Prefer instance roles and protected environment configuration.
