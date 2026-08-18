# Database

_Last reconciled with `development` HEAD `f2b16aa1a3ce7c53278b3b68d20524d3970fca05` on 2026-08-12._

The application uses PostgreSQL 16. Runtime table access belongs in CI4 models; multi-table business transactions and locking decisions belong in services.

## Schema evolution convention

The project does **not** currently use CI4 migrations for deployed schema evolution (`app/Database/Migrations` contains only `.gitkeep`).

The authoritative deployment convention is:

```text
Fresh environment
  → app/Database/sikhanandkaraj_db.sql (immutable baseline / version 000)
  → verify baseline
  → database/001_*.sql ... database/NNN_*.sql in numeric order

Existing environment
  → never rerun baseline 000
  → read deployment_sql_history
  → apply only missing numbered increments in order

Unknown / partial environment
  → STOP
  → reconcile database state and deployment ledger before continuing
```

`app/Database/sikhanandkaraj_db.sql` remains the immutable baseline. Later feature changes belong only in numbered files under `database/`. A successfully applied increment is recorded in `deployment_sql_history` only after the script completes successfully.

Current numbered increments:

| Version | File | Purpose |
|---|---|---|
| 001 | `001_member_matchmaking_interactions.sql` | member blocks, interests and profile-view tracking |
| 002 | `002_member_shortlists.sql` | member shortlist persistence |
| 003 | `003_member_interests.sql` | interest status/responded-at and notification types |
| 004 | `004_member_search.sql` | `last_login_at` and search-supporting indexes |
| 005 | `005_field_officer_identity.sql` | Aadhaar/PAN identity constraints and UPI uniqueness |
| 006 | `006_member_family_field_officer.sql` | optional immutable member→SAK Volunteer assignment |
| 007 | `007_field_officer_portal.sql` | SAK Volunteer login OTPs and submitted-profile read view |
| 008 | `008_sak_volunteer_self_registration.sql` | volunteer registration source/review workflow |
| 009 | `009_sak_volunteer_documents.sql` | protected volunteer document filename references |
| 010 | `010_admin_password_reset.sql` | administrator password-reset workflow persistence |
| 011 | `011_member_identity_verification.sql` | member identity-verification persistence |
| 012 | `012_prelaunch_optional_sak_volunteer.sql` | optional prelaunch SAK Volunteer association |
| 013 | `013_member_aadhaar_verification.sql` | member Aadhaar verification persistence |
| 014 | `014_member_account_settings.sql` | member account and privacy settings |
| 015 | `015_contact_support_refinement.sql` | contact-support workflow refinement |
| 016 | `016_profile_report_single_submission.sql` | profile-report uniqueness refinement |
| 017 | `017_restore_reporting_after_dismissal.sql` | active report uniqueness after dismissal |
| 018 | `018_canada_matrimonial_locations.sql` | Canada, 13 provinces/territories and curated matrimonial-ready locations |

Do not edit an already-deployed numbered SQL file to represent a new change. Add the next increment.

## Core account/contact model

- `users.account_status`: `PENDING`, `ACTIVE`, `SUSPENDED`, `DELETED`.
- Registration creates/maintains a `PENDING` user until verified mobile activation.
- `user_contacts` stores `MOBILE` and optional `EMAIL` independently.
- Public registration creates one primary mobile contact and does not create email.
- Optional-email columns/data must remain nullable where the product contract says email is optional.
- `users.last_login_at` supports recent-login/search ordering/indexes.

Contact uniqueness is protected at database level; application pre-checks are not sufficient concurrency protection.

## Verification/OTP data

`contact_verifications` supports purposes including `REGISTER`, `LOGIN` and `PASSWORD_RESET`. Verification persistence stores hashes, expiry, attempt/resend state and status—not raw OTPs.

SAK Volunteer login uses the separate `field_officer_login_otps` table with:

- FK to `field_officers` using `RESTRICT` delete behavior;
- mobile snapshot;
- OTP hash and expiry;
- attempt count;
- `PENDING`, `VERIFIED`, `EXPIRED`, `CANCELLED`, `DELIVERY_FAILED` status;
- one partial unique pending OTP per volunteer.

Delivery-failed OTP rows are unusable and must not count as successfully delivered/pending credentials.

## Member profile domain

Member profile data is normalized into business-section tables and master references. Important areas include:

- `member_basic_details`;
- `member_education_profession_details`;
- `member_family_details`;
- `member_lifestyle_options`;
- partner-preference tables;
- `member_photos`.

Search-specific indexes introduced in 004 cover active users, last login, basic/location/demographic fields, education/profession, community, lifestyle and approved primary-photo visibility.

## Matchmaking/interactions

### `member_blocks`

- blocker and blocked users are FK-protected with cascade on user deletion;
- self-block is prohibited;
- `(blocker_user_id, blocked_user_id)` is unique;
- comment is required and trimmed length is constrained to 1–250 characters.

### `member_interests`

- sender and recipient must differ;
- sender→recipient pair is unique;
- status is `PENDING`, `ACCEPTED` or `DECLINED`;
- `responded_at` records response timing;
- received/sent status indexes support Interest screens.

### `member_profile_views`

- viewer and viewed users must differ;
- pair is unique and aggregates `view_count`, first and last viewed timestamps;
- `view_count > 0` is DB-enforced.

### Shortlists

Shortlisting is persisted independently of Interests and member blocking. Each relationship domain keeps its own business meaning instead of overloading another table/status.

## Notifications

`member_notifications.notification_type` supports the current interaction events including `MESSAGE`, interest received/accepted/rejected, mutual interest, profile view, shortlist, photo rejection and `SYSTEM`. Read notifications are eligible for configured retention cleanup after their retention window.

## SAK Volunteer / Field Officer domain

`field_officers` includes identity, account, review and optional document-reference data.

Identity constraints include:

- Aadhaar: nullable, exactly 12 digits when present, unique when present;
- PAN: nullable, required format when present, unique when present;
- UPI: case-insensitive unique partial index when non-empty.

Self-registration adds:

- `registration_source`: `ADMIN` or `SELF`;
- `review_status`: `PENDING`, `APPROVED`, `REJECTED`;
- reviewer metadata and rejection reason;
- DB rule preventing a self-registered volunteer from being active while review is pending/rejected.

Protected document columns store randomized filenames only; current files live under private writable storage, not under public web assets.

## Immutable member→SAK Volunteer assignment

`member_family_details.field_officer_id` and `field_officer_code` are optional but must be both null or both present.

Database protections in increment 006 enforce:

1. FK to `field_officers` with `ON DELETE RESTRICT` to preserve history;
2. ID/code consistency against the same volunteer;
3. immutability once a non-null assignment has been saved.

Application/UI validation must never offer an update path that contradicts these DB rules.

## SAK Volunteer submitted-profile read model

`vw_field_officer_submitted_profiles` combines:

- assigned prelaunch profiles; and
- normal registered members assigned through Family Details.

Migrated prelaunch members are excluded from the normal-member branch to prevent duplicates. The view resolves live migrated users through `users.prelaunch_profile_id` rather than depending on a redundant migrated-user column.

## Media persistence

`member_photos` stores object keys, source/variant metadata, approval state, primary flag and visibility. Signed CloudFront URLs are never persisted.

Approved primary photo indexes support search/listing retrieval. Viewer authorization remains an application/service concern before signed delivery.

## Development profile import ledger

`development_profile_imports` records the source folder→generated user relationship so the development/QA loader can safely skip already-imported numeric source folders. It is development/QA support data, not a production business domain.

## Integrity rules

- Use PK/FK/UNIQUE/CHECK/NOT NULL/partial indexes when they represent true invariants.
- Normalize contacts/identifiers before uniqueness checks.
- Use row locks for one-time token consumption and conflicting state transitions.
- Use transactions for multi-step database decisions that must succeed/fail together.
- Do not keep a DB transaction open during SMS/email/AWS/network work.
- Never rely only on application pre-checks for uniqueness or immutable relationships.
- Prefer `RESTRICT` when deleting a referenced master/business actor would destroy historical meaning; use cascade only for genuinely owned child rows.

For the detailed constraint inventory, see `docs/database-constraints.md`.

## Increment 019 — Country-aware search/preferences and hierarchy integrity

`database/019_country_location_integrity.sql` adds persisted country-level
partner preferences. An empty country selection intentionally means Any country,
so existing preference rows retain their behaviour.

The increment also adds composite foreign keys for every persisted
country/state/city tuple used by live members, family details, Sikh birth
locations, SAK Volunteers and prelaunch profiles. A precondition block aborts
the transaction with a table-specific count if historical rows contain a state
from another country or a city from another state.
