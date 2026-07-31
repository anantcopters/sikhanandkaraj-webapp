# Sikh Anand Karaj

Sikh Anand Karaj is a Sikh matrimonial web application built with CodeIgniter 4, PHP 8.3, PostgreSQL 16, Bootstrap and vanilla JavaScript.

## Current product flows

### Member registration

- Public registration collects profile-created-for, name, gender where applicable, mobile number and password.
- Email is not collected during registration.
- Registration creates one primary `MOBILE` contact row.
- New accounts remain `PENDING` until the mobile OTP is verified.
- Successful mobile verification activates the account as `ACTIVE`.
- A verified mobile cannot be reused for another registration.
- An unverified mobile may resume only its existing `PENDING` registration.

### Member login

Members may choose:

1. password login using a verified mobile number or a verified email added later; or
2. passwordless OTP login using an active account's verified mobile number.

OTP-login initiation uses generic public responses so callers cannot determine whether a mobile exists, is verified, or belongs to an inactive account.

### Password recovery

Password reset accepts a registered mobile number or verified email, resolves the account, and sends the OTP only to its verified mobile contact. Password reset is available only to `ACTIVE` accounts.

### Member profile

Authenticated members can maintain basic details, education and profession, family details, lifestyle, about-me information and up to five photos. Profile completion uses only sections currently displayed in the member journey.

### Media

- S3 objects remain private.
- CloudFront is the only delivery path.
- The database stores object keys, not permanent URLs.
- Authorized requests receive short-lived signed URLs.
- Uploads create original, medium and thumbnail variants.
- Photos support approval status, primary-photo selection and visibility rules.

### Administration and prelaunch

The separate administrator application supports role/status authorization, administrator invitations, audit logging, field officers, prelaunch profile review and member photo review. In production prelaunch mode, public home, login and registration routes redirect to the prelaunch profile form.

## Architecture

```text
Route
  → Controller
  → Validation
  → Service
  → Model
  → PostgreSQL / AWS / provider adapter
  → Result DTO
  → Controller response
```

Controllers remain thin. Services own business decisions and transactions. Models own table queries. External SMS, email and storage calls must not keep database transactions open.

## Technology

| Area | Technology |
|---|---|
| Framework | CodeIgniter 4 |
| Runtime | PHP 8.3 with strict types |
| Database | PostgreSQL 16 |
| Frontend | Bootstrap, `app.css`, HTML5 |
| JavaScript | Vanilla JavaScript and existing approved libraries |
| Media | Private AWS S3 plus CloudFront signed URLs |
| Local | Windows/XAMPP |
| QA/Production | Ubuntu EC2 and Apache |

## Documentation

- [Project coding rules](docs/project-rules.md)
- [Architecture](docs/architecture.md)
- [Authentication](docs/authentication.md)
- [Registration](docs/registration.md)
- [Administrator module](docs/admin-module.md)
- [Database](docs/database.md)
- [Security](docs/security.md)
- [Frontend](docs/frontend.md)
- [Validation](docs/validation.md)
- [Deployment](docs/deployment.md)
- [Operations](docs/operations.md)
- [Decision log](docs/decision-log.md)

## Non-negotiable engineering rules

- Use `declare(strict_types=1);` and exact PSR-4 casing.
- Read only expected request fields and pass only validated data to services.
- Keep SQL, transactions and multi-table rules out of controllers.
- Use named routes and escape dynamic view output.
- Use existing Bootstrap, `app.css` and shared JavaScript before adding new code.
- Keep member, administrator and prelaunch authorization contexts explicit.
- Hash passwords, OTPs and one-time tokens; never log secrets.
- Use database constraints as final protection against concurrency.
- Update documentation whenever business logic or architecture changes.
