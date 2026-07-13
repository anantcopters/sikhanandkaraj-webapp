# Database

The application uses PostgreSQL.

## SQL folder

```text
sql/
├── schema/
├── updates/
└── seeds/
```

### `sql/schema`

Contains the complete structure required for a fresh database.

Examples:

- tables;
- indexes;
- constraints;
- session table.

### `sql/updates`

Contains incremental changes for an existing database.

Use sortable names:

```text
YYYYMMDD_NNN_description.sql
```

Example:

```text
20260713_001_add_profile_photo_status.sql
```

Never edit an update file after it has been deployed. Add a new update file.

### `sql/seeds`

Contains master or reference data.

Examples:

- countries;
- states;
- profile relationships;
- religions;
- education master data.

Use idempotent statements where possible, such as `ON CONFLICT DO NOTHING`.

## SQL file format

Wrap related changes in a transaction:

```sql
BEGIN;

ALTER TABLE profiles
ADD COLUMN headline VARCHAR(150);

CREATE INDEX idx_profiles_headline
ON profiles (headline);

COMMIT;
```

## Runtime queries

Runtime queries do not belong in SQL update files.

Use CI4 models for:

- `SELECT`;
- `INSERT`;
- `UPDATE`;
- `DELETE`;
- table-specific query methods.

Use services for business decisions and transactions.

## Current registration tables

### `users`

Stores account-level identity such as:

- internal ID;
- public profile reference number;
- profile-created-for relationship;
- gender;
- full name;
- account status.

The public reference follows:

```text
SAK1234567
```

The database must enforce uniqueness and format.

### `user_contacts`

Stores email and mobile contacts separately.

Important fields:

- `contact_type`;
- `contact_value`;
- `normalized_value`;
- `is_primary`;
- `is_verified`;
- `verified_at`.

Normalized mobile values should use one standard format such as `+919876543210`.

Emails should be trimmed and lower-cased for comparison.

### `contact_verifications`

Stores OTP verification history.

Store only the OTP hash, never the plain OTP.

Important fields:

- contact ID;
- purpose;
- OTP hash;
- expiry;
- attempt count;
- resend count;
- status;
- verification time.

## Constraints

Use database constraints for final data integrity.

Examples:

- unique profile reference;
- unique normalized mobile;
- valid status values;
- valid contact types;
- foreign keys;
- verified timestamp consistency.

## Transactions

A service starts a transaction when several writes represent one operation.

Example registration transaction:

```text
Create user
Create mobile contact
Create email contact
Create OTP verification
Commit
```

If any write fails, roll back all writes.

## Model rules

- One model normally represents one table.
- Define `$allowedFields`.
- Use timestamps consistently.
- Add small, named query methods for repeated lookups.
- Do not place form redirects or HTML in models.
- Do not start a business transaction inside a model.

## Concurrency

A service lookup is useful for friendly business handling, but the database unique constraint is the final guarantee.

For example, two users may submit the same mobile at nearly the same time. Only a unique database index can prevent duplicate storage.

## Data deletion

Use soft deletion where the record must remain for audit or recovery. Use hard deletion only when the feature explicitly requires it and related foreign-key behaviour is understood.

## Future CI/CD execution

A future deployment step can:

1. list `sql/updates` files in order;
2. compare them with an executed-script tracking table;
3. run missing scripts;
4. record successful execution;
5. stop deployment if a script fails.