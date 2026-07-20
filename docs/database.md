# Database

The application uses PostgreSQL 16. Database constraints are the final protection for data integrity; services coordinate business transactions and CI4 models own runtime table queries.

## SQL source of truth

All schema, update and seed SQL must be maintained under:

```text
sql/
├── schema/
├── updates/
└── seeds/
```

`app/Database/db_sikhanandkaraj.sql` is not the long-term deployment source of truth. When legacy statements are still required, move or reconcile them into the appropriate `sql` folder before deployment.

### Schema

`sql/schema` contains the complete structure required for a fresh database: tables, constraints, indexes, session storage and required database objects.

### Updates

`sql/updates` contains immutable incremental changes for an existing database. Use sortable names:

```text
YYYYMMDD_NNN_description.sql
```

Never edit a deployed update file. Add a new corrective update.

### Seeds

`sql/seeds` contains reference data such as countries, states, profile relationships, religions and education masters. Prefer idempotent statements such as `ON CONFLICT DO NOTHING`.

## SQL file format

Wrap related changes in a transaction where PostgreSQL permits it:

```sql
BEGIN;

ALTER TABLE profiles
ADD COLUMN headline VARCHAR(150);

CREATE INDEX idx_profiles_headline
ON profiles (headline);

COMMIT;
```

## Runtime data access

Use CI4 models for table queries and named lookup methods. Use services for business decisions, transactions and coordination across models. Do not place SQL in controllers or views.

## Member and registration tables

### `users`

Stores account identity, public profile reference, profile-created-for relationship, gender, name and account status. Public references use `SAK` plus seven digits and must be unique.

### `user_contacts`

Stores email and mobile contacts independently. Important values include contact type, original and normalized values, primary status, verification status and verification time.

Normalize mobile numbers to one canonical format and compare email addresses after trimming and lower-casing.

### `contact_verifications`

Stores OTP or contact-verification history. Store only token/OTP hashes together with purpose, expiry, attempts, resend count, status and completion time.

## Administrator tables

### `admin_users`

Stores administrator identity and authentication state. Expected concepts include:

- name and normalized email;
- password hash;
- role such as `SUPER_ADMIN` or `ADMIN`;
- status such as `PENDING`, `VERIFIED` or `SUSPENDED`;
- email verification time;
- last-login and suspension metadata;
- created/updated timestamps.

The database must enforce unique normalized administrator email addresses and valid role/status values.

### `admin_invitations`

Stores one-time administrator invitations. Expected concepts include:

- administrator ID;
- invitation token hash, never the plain token;
- expiry time;
- accepted/revoked timestamps;
- inviter ID;
- resend/replacement metadata;
- created timestamp.

Invitation acceptance must lock the applicable row inside a transaction before checking and consuming it. Only one valid invitation should be usable for a given acceptance flow.

### `admin_audit_logs`

Stores security and operational events such as login success/failure, logout, invitation creation/resend/acceptance, role or status changes and suspension.

Audit records should include actor, target where applicable, event name, outcome, request/IP context where appropriate and creation time. Do not store passwords, plain tokens or OTPs.

## Constraints and indexes

Use database constraints for:

- unique public profile references;
- unique normalized contacts where required;
- unique normalized administrator email;
- valid role, account and invitation status values;
- foreign-key integrity;
- verification timestamp consistency;
- one-time token consumption rules where practical.

Create indexes for common authentication, expiry, status and audit-history lookups.

## Transaction boundaries

A service starts a transaction when several writes represent one operation.

Registration may include user, contacts and verification records. Administrator invitation acceptance may include row locking, password creation, email verification, account activation, invitation consumption and audit logging.

Rollback every database write when the operation fails. External side effects such as email delivery should occur after commit or through a retryable queue/outbox design.

## Model rules

- One model normally represents one table.
- Define `$allowedFields` explicitly.
- Use timestamps consistently.
- Add small named methods for repeated lookups.
- Do not place redirects, HTML or session decisions in models.
- Do not start a full business transaction inside a model.

## Data retention and deletion

Use soft deletion or status changes when records must remain for audit, security or recovery. Hard deletion requires an explicit feature decision and an understood foreign-key/retention impact.

## Deployment execution

A deployment runner should list `sql/updates` in order, compare them with an executed-script tracking table, execute missing scripts, record success and stop immediately on failure.