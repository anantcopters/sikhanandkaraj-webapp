# Database

The application uses PostgreSQL 16. Runtime queries belong in CI4 models; business transactions belong in services.

## Core account model

- `users.account_status`: `PENDING`, `ACTIVE`, `SUSPENDED`, `DELETED`.
- Registration creates a `PENDING` user.
- Successful mobile OTP verification moves the user to `ACTIVE`.
- `user_contacts` stores `MOBILE` and optional `EMAIL` contacts independently.
- Registration creates only one primary mobile contact.
- Email columns and related profile/prelaunch data must remain nullable where email is optional.

## Verification records

`contact_verifications` supports purposes including `REGISTER`, `LOGIN` and `PASSWORD_RESET`. Store only hashes, expiry, attempt/resend counters and status. Delivery failures must be marked unusable and excluded from successful-delivery quotas.

## Profile and media data

Profile details are split by business section and reference master tables. Media rows store S3 object keys, variant metadata, approval state, primary flag and visibility—not signed URLs.

## SQL rules

- Use immutable numbered SQL updates for deployed environments.
- Use foreign keys, checks, unique/partial indexes and `NOT NULL` only when business rules require them.
- Normalize contacts before uniqueness checks.
- Use row locking for one-time token consumption and conflicting state transitions.
- Never rely only on application pre-checks for uniqueness.
