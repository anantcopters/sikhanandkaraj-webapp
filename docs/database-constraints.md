# Database Constraints Catalogue

This document records table-level integrity rules for the current `development` branch. It must be updated whenever a schema/update SQL file, model contract, validation rule, or service-level state rule changes.

## How to read this document

- **Database-enforced** means PostgreSQL currently protects the rule using a primary key, foreign key, `UNIQUE`, partial unique index, `CHECK`, `NOT NULL`, or another database object.
- **Application-enforced** means the current PHP validation/service/model flow restricts the value, but the reviewed SQL does not provide equivalent final protection.
- **Master-data constrained** means the column references a master table; the permitted values are the active rows in that table rather than a hard-coded text list.
- Indexes used only for query performance are not treated as constraints unless they are unique or partial-unique.

The primary reviewed SQL source is `app/Database/db_sikhanandkaraj.sql`, together with incremental files under `database/` and the current application models, validation classes, and services.

## Required maintenance rule

For every database change:

1. add or update the applicable table section below;
2. record the exact allowed values for every `CHECK` or application enum;
3. record nullability, uniqueness, foreign-key action, and partial-index predicate;
4. identify any business rule that remains application-only;
5. never edit an already-deployed SQL update merely to make this document match it.

---

## `ci_sessions`

| Column(s) | Constraint | Enforcement | Allowed values / rule |
|---|---|---|---|
| `id` | `NOT NULL` | Database | Non-null session identifier; no primary/unique constraint is declared in the reviewed baseline SQL. |
| `ip_address` | `NOT NULL` | Database | Valid PostgreSQL `inet` value. |
| `timestamp` | `NOT NULL`, default | Database | Defaults to `CURRENT_TIMESTAMP`. |
| `data` | `NOT NULL`, default | Database | Defaults to empty `bytea`. |

**Review note:** CI4 database-session deployments normally require a unique or primary key on `id`. The reviewed baseline defines only a timestamp index, so the deployed schema should be checked before relying on session-row uniqueness.

## `users`

| Column(s) | Constraint | Enforcement | Allowed values / rule |
|---|---|---|---|
| `id` | Primary key | Database | Generated `BIGSERIAL`. |
| `profile_ref_number` | `NOT NULL`, unique, format check | Database | Exactly `SAK` followed by seven digits: `^SAK[0-9]{7}$`. |
| `profile_created_for` | `NOT NULL`, check | Database | `self`, `son`, `daughter`, `brother`, `sister`. |
| `gender` | `NOT NULL`, check | Database | `M`, `F`. |
| `full_name` | `NOT NULL` | Database | Maximum 100 characters. Additional formatting is application validation. |
| `password_hash` | `NOT NULL` | Database | Password hash; plain passwords are prohibited by application rules. |
| `account_status` | `NOT NULL`, default, check | Database | `PENDING`, `ACTIVE`, `SUSPENDED`, `DELETED`; default `PENDING`. |
| `created_at`, `updated_at` | `NOT NULL`, default | Database | Default `CURRENT_TIMESTAMP`. |
| `deleted_at` | Nullable | Database | Null until soft deletion. |

**Application state rule:** registration creates/resumes only `PENDING`; successful mobile verification changes the account to `ACTIVE`; login and password reset require `ACTIVE`.

## `user_contacts`

| Column(s) | Constraint | Enforcement | Allowed values / rule |
|---|---|---|---|
| `id` | Primary key | Database | Generated `BIGSERIAL`. |
| `user_id` | Foreign key, `NOT NULL` | Database | References `users(id)`; `ON DELETE CASCADE`. |
| `contact_type` | `NOT NULL`, check | Database | `EMAIL`, `MOBILE`. |
| `contact_value` | `NOT NULL` | Database | Original/display contact value, maximum 254 characters. |
| `normalized_value` | `NOT NULL` | Database | Canonical comparison value, maximum 254 characters. |
| `normalized_value` where `contact_type = 'EMAIL'` | Partial unique index | Database | One normalized email across all contact rows. |
| `normalized_value` where `contact_type = 'MOBILE'` | Partial unique index | Database | One normalized mobile across all contact rows. The baseline contains two equivalent mobile unique indexes; this duplication should be cleaned by a new corrective SQL file if both exist in deployed databases. |
| `(user_id, contact_type)` where `is_primary = TRUE` | Partial unique index | Database | At most one primary contact of each type per user. |
| `is_primary` | `NOT NULL`, default | Database | Boolean; default `TRUE`. |
| `is_verified`, `verified_at` | Consistency check | Database | Either `FALSE` with `verified_at IS NULL`, or `TRUE` with `verified_at IS NOT NULL`. |

**Application normalization:** mobile is stored in the canonical project format; email is trimmed and lower-cased before comparison.

## `contact_verifications`

| Column(s) | Constraint | Enforcement | Allowed values / rule |
|---|---|---|---|
| `id` | Primary key | Database | Generated `BIGSERIAL`. |
| `user_contact_id` | Foreign key, `NOT NULL` | Database | References `user_contacts(id)`; `ON DELETE CASCADE`. |
| `purpose` | `NOT NULL`, default, check in baseline | Database | Baseline values: `REGISTER`, `LOGIN`, `CHANGE_MOBILE`, `CHANGE_EMAIL`; default `REGISTER`. |
| `status` | `NOT NULL`, default, check in baseline | Database | Baseline values: `PENDING`, `VERIFIED`, `EXPIRED`, `CANCELLED`; default `PENDING`. |
| `(user_contact_id, purpose)` where `status = 'PENDING'` | Partial unique index | Database | At most one pending verification per contact and purpose. |
| `otp_hash`, `expires_at` | `NOT NULL` | Database | Hash and expiry are mandatory. |
| `attempt_count`, `resend_count` | `NOT NULL`, default | Database | Default `0`; baseline SQL does not check that values remain non-negative. |

**Current application values requiring schema reconciliation:** application documentation/services also use `PASSWORD_RESET` and a non-usable delivery-failure state such as `DELIVERY_FAILED`. If these values are used in the running application, a deployed SQL update must extend the corresponding checks. Until confirmed, they are application-enforced and may conflict with an unchanged baseline constraint.

## `http_request_logs`

| Column(s) | Constraint | Enforcement | Allowed values / rule |
|---|---|---|---|
| `id` | Primary key | Database | Generated `BIGSERIAL`. |
| `request_id` | `NOT NULL`, unique | Database | PostgreSQL UUID. |
| `occurred_at`, `request_method`, `request_uri`, `response_status` | `NOT NULL` | Database | Required request/response metadata. |
| `duration_ms` | `NOT NULL`, default | Database | Default `0`; no non-negative check in baseline. |
| `environment` | `NOT NULL` | Database | Application-provided environment string; no database value check. |
| `severity` | `NOT NULL`, default | Database | Default `INFO`; no database value check. |
| `is_authenticated`, `is_successful` | `NOT NULL`, default | Database | Boolean; defaults `FALSE` and `TRUE` respectively. |
| `user_id` | Nullable, no foreign key in baseline | Database | May contain a user identifier, but referential integrity is not enforced. |

## `email_verification_tokens`

| Column(s) | Constraint | Enforcement | Allowed values / rule |
|---|---|---|---|
| `id` | Primary key | Database | Generated `BIGSERIAL`. |
| `user_id` | Foreign key, `NOT NULL` | Database | References `users(id)`; `ON DELETE CASCADE`. |
| `user_contact_id` | Foreign key, `NOT NULL` | Database | References `user_contacts(id)`; `ON DELETE CASCADE`. |
| `token_hash` | `NOT NULL`, unique | Database | Exactly 64 characters in the baseline schema. |
| `expires_at` | `NOT NULL` | Database | Expiry timestamp required. |
| `used_at` | Nullable | Database | Null until consumed. |

**Application rule:** only an unused, unexpired token for the expected user/contact may be consumed. The baseline SQL does not enforce one active token per contact or consistency between `user_id` and the contact's owner.

## `email_queue`

| Column(s) | Constraint | Enforcement | Allowed values / rule |
|---|---|---|---|
| `id` | Primary key | Database | Generated `BIGSERIAL`. |
| `queue_name` | `NOT NULL`, default | Database | Default `default`. |
| `recipient_email`, `subject`, `view_name` | `NOT NULL` | Database | Required delivery fields. |
| `view_data` | `NOT NULL`, default | Database | JSON object default `{}`. |
| `status` | `NOT NULL`, default, check | Database | `PENDING`, `PROCESSING`, `SENT`, `FAILED`; default `PENDING`. |
| `attempts`, `max_attempts` | Check | Database | `attempts >= 0` and `max_attempts > 0`; defaults `0` and `3`. |
| pending-ready rows | Partial index | Database performance | Indexed where `status = 'PENDING'`; not a uniqueness rule. |

**Application rule:** a row's timestamps should match its state (`sent_at` for `SENT`, `failed_at` for `FAILED`, lock values for `PROCESSING`). The baseline has no cross-column state check.

## `email_queue_attempts`

| Column(s) | Constraint | Enforcement | Allowed values / rule |
|---|---|---|---|
| `id` | Primary key | Database | Generated `BIGSERIAL`. |
| `email_queue_id` | Foreign key, `NOT NULL` | Database | References `email_queue(id)`; `ON DELETE CASCADE`. |
| `status` | `NOT NULL`, check | Database | `STARTED`, `SENT`, `RETRY`, `FAILED`. |
| `attempt_number`, `started_at` | `NOT NULL` | Database | Required attempt sequence and start timestamp. |

**Gap:** `(email_queue_id, attempt_number)` is indexed but not unique, so duplicate attempt numbers are not prevented by PostgreSQL.

## `admin_users`

| Column(s) | Constraint | Enforcement | Allowed values / rule |
|---|---|---|---|
| `id` | Primary key | Database | Generated `BIGSERIAL`. |
| `mobile_number` | `NOT NULL`, unique | Database | One administrator row per stored mobile number. |
| `email_address` | `NOT NULL`, unique | Database | One administrator row per stored email address. Application must normalize before storage. |
| `role` | `NOT NULL`, default, check | Database | `SUPER_ADMIN`, `ADMIN`; default `ADMIN`. |
| `account_status` | `NOT NULL`, default, check | Database | `PENDING`, `VERIFIED`, `SUSPENDED`; default `PENDING`. |
| `created_by` | Foreign key, nullable | Database | References `admin_users(id)`; `ON DELETE SET NULL`. |
| `is_mobile_verified` | `NOT NULL`, default | Database | Default `TRUE`. |
| `mobile_verified_at` | `NOT NULL`, default | Database | Default `CURRENT_TIMESTAMP`. |
| `is_email_verified` | `NOT NULL`, default | Database | Default `FALSE`. |
| `email_verified_at`, `password_set_at` | Nullable | Database | Application state controls when populated. |

**Application authorization:** only `SUPER_ADMIN` may manage administrators/field officers and privileged KPIs. Database constraints do not enforce route/action authorization.

**Consistency gap:** the baseline does not check that verification booleans agree with verification timestamps or that `VERIFIED` accounts have a password and verified email.

## `admin_invitations`

| Column(s) | Constraint | Enforcement | Allowed values / rule |
|---|---|---|---|
| `id` | Primary key | Database | Generated `BIGSERIAL`. |
| `admin_user_id` | Foreign key, `NOT NULL` | Database | References `admin_users(id)`; `ON DELETE CASCADE`. |
| `created_by` | Foreign key, `NOT NULL` | Database | References `admin_users(id)`; `ON DELETE RESTRICT`. |
| `token_hash` | `NOT NULL`, unique | Database | Exactly 64 characters. |
| `expires_at` | `NOT NULL` | Database | Expiry required. |
| `used_at`, `revoked_at` | Nullable | Database | Application determines usability. |

**Application rule:** usable means not expired, not used, and not revoked. Consumption is protected with row locking. The baseline has no check preventing both `used_at` and `revoked_at` from being populated and no partial unique rule limiting active invitations per administrator.

## `admin_audit_logs`

| Column(s) | Constraint | Enforcement | Allowed values / rule |
|---|---|---|---|
| `id` | Primary key | Database | Generated `BIGSERIAL`. |
| `actor_admin_id` | Foreign key, nullable | Database | References `admin_users(id)`; `ON DELETE SET NULL`. |
| `action` | `NOT NULL` | Database | Application-defined audit action name. |
| `outcome` | `NOT NULL`, default, check | Database | `SUCCESS`, `FAILURE`, `DENIED`; default `SUCCESS`. |
| `occurred_at` | `NOT NULL`, default | Database | Default `CURRENT_TIMESTAMP`. |

**Application rule:** audit JSON and text must not contain passwords, OTPs, raw tokens, complete signed URLs, or session IDs.

## `deployment_sql_history`

| Column(s) | Constraint | Enforcement | Allowed values / rule |
|---|---|---|---|
| `id` | Primary key | Database | Generated `BIGSERIAL`. |
| `filename` | `NOT NULL`, unique | Database | One recorded execution per SQL filename. |
| `checksum_sha256` | `NOT NULL` | Database | Maximum 64 characters; baseline does not validate hexadecimal format/length exactly. |
| `git_commit` | `NOT NULL` | Database | Maximum 40 characters; baseline does not validate hexadecimal SHA format. |
| execution timestamps/time | `NOT NULL` | Database | Start, end, and elapsed milliseconds required. |
| `deployed_by` | `NOT NULL`, default | Database | Default `github-actions`. |

---

# Profile and master-data tables

The application contains profile-section tables and master tables introduced through incremental files under `database/`. Their exact deployed constraints must be taken from those immutable SQL files. The following rules are confirmed from current model/service usage; where the physical constraint name was not present in the reviewed baseline file, enforcement is marked accordingly.

## Common master-table contract

Applies to master tables such as countries, states, cities, profile-created-for relationships, education, occupation, employment type, income range, family value/type/status, family occupations, Sikh communities, moon signs, birth stars, lifestyle categories/options, and other profile lookup tables.

| Column(s) | Constraint | Enforcement | Allowed values / rule |
|---|---|---|---|
| `id` | Primary key | Database/update SQL | Positive generated identifier. |
| parent ID, where applicable | Foreign key | Database/update SQL | Examples: state → country, city → state, dependent option → parent master. |
| `code`, where present | Unique within business scope | Database/update SQL or required design | Stable code; exact uniqueness scope may be global or parent-scoped. |
| `name` | Required business value | Database/update SQL | Human-readable label. |
| `is_active` | Boolean default | Database/update SQL | Application selects only active values. |
| `display_order` | Numeric default | Database/update SQL | Controls UI ordering; no allowed enum. |

**Allowed column values:** foreign-key profile fields may contain only IDs present in the referenced master table. UI/service rules normally further restrict selection to rows where `is_active = TRUE`.

## `master_states`

| Column(s) | Constraint | Enforcement | Allowed values / rule |
|---|---|---|---|
| `country_id` | Foreign key, `NOT NULL` | Database/update SQL | Must reference `master_countries(id)`. |
| `(country_id, code)` | Unique | Database/update SQL | State code unique within a country. |
| `(country_id, name)` | Unique | Database/update SQL | State name unique within a country. |

## `master_cities`

| Column(s) | Constraint | Enforcement | Allowed values / rule |
|---|---|---|---|
| `state_id` | Foreign key, `NOT NULL` | Database/update SQL | Must reference `master_states(id)`. |
| city name within state | Expected unique business rule | Verify deployed SQL | A duplicate city under the same state should be prevented; confirm the current update file contains the composite unique constraint. |

## `member_basic_details`

| Column(s) | Constraint | Enforcement | Allowed values / rule |
|---|---|---|---|
| `user_id` | One row per user, foreign key | Database/update SQL expected | Must reference `users(id)`; service performs upsert-style ownership checks. |
| country/state/city and other master IDs | Foreign keys | Master-data constrained | Only referenced master IDs; city must belong to selected state and state to selected country. |
| `about_me` | Nullable text | Database | Added by `database/025_aboutme_missing.sql`. |

**Application-only examples:** date/age limits, height range, marital-status selection, text lengths, and cross-field country/state/city ownership are validated in PHP unless an incremental SQL check explicitly covers them.

## `member_education_profession_details`

| Column(s) | Constraint | Enforcement | Allowed values / rule |
|---|---|---|---|
| `user_id` | One row per user, foreign key | Database/update SQL expected | Must reference `users(id)`. |
| education, employment, occupation, income IDs | Foreign keys | Master-data constrained | Only active IDs from their respective master tables. |
| detail/organization fields | Nullable text | Database/application | Length and formatting primarily enforced by validation. |

## `member_family_details`

| Column(s) | Constraint | Enforcement | Allowed values / rule |
|---|---|---|---|
| `user_id` | One row per user, foreign key | Database/update SQL expected | Must reference `users(id)`. |
| family value/type/status, community, occupation and location IDs | Foreign keys | Master-data constrained | Values are IDs from the applicable master tables. |
| `father_name`, `mother_name` | Required in current UI/service | Application-enforced; DB may remain nullable | Current business flow requires both, while the database was intentionally allowed to remain nullable for backward compatibility. |
| `community_id` | Required current flow | Application/master constrained | Must be an active Sikh-community master row. |
| `subcommunity_id` | Removed | Application/schema evolution | Removed from the current family/prelaunch flow; new code must not reintroduce it. |

## `member_lifestyle_details`

| Column(s) | Constraint | Enforcement | Allowed values / rule |
|---|---|---|---|
| `user_id` | One row per user, foreign key | Database/update SQL expected | Must reference `users(id)`. |
| lifestyle selections | Foreign keys | Master-data constrained | Only configured active lifestyle option IDs. |

## `member_sikh_religious_details`

This table/model may remain for compatibility, but Sikh & Religious Details are not currently displayed and are excluded from profile completion. Values should not be newly required by current UI flows. Any retained foreign keys remain master-data constrained.

## Member photo tables

The current media flow stores original, medium, and thumbnail object keys/metadata and applies approval, primary-photo, and visibility rules.

| Column(s) | Constraint | Enforcement | Allowed values / rule |
|---|---|---|---|
| owner/user ID | Foreign key | Database/update SQL | Must reference the owning member. |
| variant/type | Check or application enum | Verify deployed SQL | Expected variants: `ORIGINAL`, `MEDIUM`, `THUMBNAIL` or the equivalent values used by the current schema. |
| approval status | Check or application enum | Verify deployed SQL | Current flow distinguishes pending, approved, and rejected states. Exact stored strings must match the SQL/model constants. |
| visibility | Check or application enum | Verify deployed SQL | Current product choices include public and interested-members-only visibility. Exact stored strings must match the SQL/model constants. |
| primary flag per member | Partial unique rule required | Database where implemented | At most one primary photo per member. |
| maximum photo count | Service rule | Application-enforced | Maximum five member photos in the live member flow. |
| object key | Required | Database/application | Stores S3 object keys, never signed/permanent URLs. |

**Important:** photo count, one-primary-photo, approval transitions, and complete variant sets should be protected with database constraints where practical; otherwise concurrent requests can bypass application pre-checks.

## `field_officers`

| Column(s) | Constraint | Enforcement | Allowed values / rule |
|---|---|---|---|
| identity/contact fields | Required/unique as defined in update SQL | Database/update SQL | Current prelaunch verification resolves an active field officer. |
| status/active flag | Application/master constrained | Database/update SQL | Only active field officers may be used by the prelaunch flow. |
| role access | Service/filter rule | Application-enforced | Management is restricted to `SUPER_ADMIN`. |

## `prelaunch_profiles`

| Column(s) | Constraint | Enforcement | Allowed values / rule |
|---|---|---|---|
| profile reference/contact values | Required/unique according to update SQL | Database/update SQL | Contact values must be normalized before duplicate checks. |
| email | Nullable | Database/business rule | Email is optional. No new `NOT NULL` or required validation may be introduced. |
| gender/profile-created-for | Application enum and/or SQL check | Verify deployed SQL | Must match the current prelaunch validation values. |
| master-data IDs | Foreign keys | Master-data constrained | Country/state/city, education/profession, family, community, and related selections must reference valid master rows. |
| field officer | Foreign key/business rule | Database plus service | Must resolve to an active verified field officer. |
| review/approval status | Application enum and/or SQL check | Verify deployed SQL | Exact values must match the current admin review service/model. |
| `sikh_subcommunity_id` | Removed | Schema/application evolution | No longer part of the current form/model. |

## Prelaunch photo tables

| Column(s) | Constraint | Enforcement | Allowed values / rule |
|---|---|---|---|
| `prelaunch_profile_id` | Foreign key | Database/update SQL | References the owning prelaunch profile; deletion behavior must match cleanup requirements. |
| stored path/object key | Required | Database/application | Current prelaunch storage contract; must not duplicate the same physical image unnecessarily. |
| photo count | Service rule | Application-enforced | Current prelaunch limit must match the configured upload flow. |
| review state | Application enum and/or SQL check | Verify deployed SQL | Exact admin-review values must be documented from the defining update file. |

---

# Application value constraints that require database review

These values are used by current business logic and must either have matching PostgreSQL checks or be explicitly documented as application-only:

| Area | Column/concept | Current allowed values or rule |
|---|---|---|
| Member account | `users.account_status` | `PENDING`, `ACTIVE`, `SUSPENDED`, `DELETED`. |
| Profile owner relation | `users.profile_created_for` | `self`, `son`, `daughter`, `brother`, `sister`. |
| Gender | `users.gender` | `M`, `F`. |
| Contact type | `user_contacts.contact_type` | `EMAIL`, `MOBILE`. |
| OTP purpose | `contact_verifications.purpose` | Baseline: `REGISTER`, `LOGIN`, `CHANGE_MOBILE`, `CHANGE_EMAIL`; current application also requires `PASSWORD_RESET`. |
| OTP status | `contact_verifications.status` | Baseline: `PENDING`, `VERIFIED`, `EXPIRED`, `CANCELLED`; current delivery flow may require `DELIVERY_FAILED`. |
| Admin role | `admin_users.role` | `SUPER_ADMIN`, `ADMIN`. |
| Admin account | `admin_users.account_status` | `PENDING`, `VERIFIED`, `SUSPENDED`. |
| Admin audit | `admin_audit_logs.outcome` | `SUCCESS`, `FAILURE`, `DENIED`. |
| Email queue | `email_queue.status` | `PENDING`, `PROCESSING`, `SENT`, `FAILED`. |
| Email attempt | `email_queue_attempts.status` | `STARTED`, `SENT`, `RETRY`, `FAILED`. |
| Media variants | photo variant column | Original, medium, thumbnail equivalents used by the schema/model. |
| Media approval | photo approval column | Pending, approved, rejected equivalents used by the schema/model. |
| Media visibility | photo visibility column | Public and interested-members-only equivalents used by the schema/model. |
| Prelaunch review | profile/photo review status | Exact values defined in current review service/model and SQL update. |

---

# Known integrity gaps / follow-up checks

1. Confirm the deployed `ci_sessions.id` primary/unique key.
2. Remove duplicate equivalent unique indexes for normalized mobile contacts through a new SQL update, not by rewriting deployed files.
3. Reconcile `contact_verifications` checks with `PASSWORD_RESET` and delivery-failure states used by the application.
4. Consider non-negative checks for verification counters and request durations.
5. Add cross-column state checks for email queue state/timestamps where operationally safe.
6. Consider uniqueness for `(email_queue_id, attempt_number)`.
7. Consider administrator verification/password/status consistency checks.
8. Consider an active-invitation partial unique rule if only one usable invitation per administrator is intended.
9. Confirm every profile table has a unique `user_id` when the design is one row per member per section.
10. Confirm all country/state/city relationships are protected against mismatched parent-child selections, either with service validation or stronger composite database design.
11. Confirm member/prelaunch photo tables enforce one primary photo and valid enum values; photo-count limits remain service-level unless implemented with a concurrency-safe database strategy.
12. Confirm removed sub-community columns/foreign keys are handled by immutable corrective SQL and are no longer written by the application.

# Review checklist for new constraints

- Is the column nullable only when the product permits it?
- Does every foreign key declare intentional `ON DELETE` and `ON UPDATE` behavior?
- Are normalized values protected by the correct unique or partial unique index?
- Are all status/type strings listed explicitly in this document?
- Does application validation use exactly the same allowed values as PostgreSQL?
- Could two concurrent requests bypass an application pre-check?
- Does a new strict constraint require data cleanup/backfill before deployment?
- Is the SQL update immutable, ordered, and recorded in `deployment_sql_history`?

## `member_account_status_history`

| Column(s) | Constraint | Enforcement | Allowed values / rule |
|---|---|---|---|
| `id` | Primary key | Database | Unique generated identifier. |
| `user_id` | Foreign key, `NOT NULL` | Database | Must reference `users.id`; deletion restricted. |
| `action` | `CHECK`, `NOT NULL` | Database | `BLOCK`, `UNBLOCK`. |
| `previous_status` | `CHECK`, `NOT NULL` | Database | `ACTIVE`, `SUSPENDED`. |
| `new_status` | `CHECK`, `NOT NULL` | Database | `ACTIVE`, `SUSPENDED`. |
| action/status combination | `CHECK` | Database | `BLOCK`: `ACTIVE → SUSPENDED`; `UNBLOCK`: `SUSPENDED → ACTIVE`. |
| `reason` | `VARCHAR(64)`, `CHECK`, `NOT NULL` | Database | Trimmed length must be between 1 and 64. |
| `changed_by_admin_id` | Foreign key, `NOT NULL` | Database | Must reference `admin_users.id`; deletion restricted. |
| `changed_at` | `NOT NULL`, default | Database | Defaults to `CURRENT_TIMESTAMP`. |

## `member_blocks`

| Column(s) | Constraint | Enforcement | Allowed values / rule |
|---|---|---|---|
| `id` | Primary key | Database | Generated `BIGSERIAL`. |
| `blocker_user_id` | Foreign key, `NOT NULL` | Database | References `users(id)`; `ON DELETE CASCADE`, `ON UPDATE RESTRICT`. |
| `blocked_user_id` | Foreign key, `NOT NULL` | Database | References `users(id)`; `ON DELETE CASCADE`, `ON UPDATE RESTRICT`. |
| `(blocker_user_id, blocked_user_id)` | Unique | Database | One directional block record per member pair. |
| blocker/blocked identity | Check | Database | A member cannot block themself. |
| `comment` | `NOT NULL`, check | Database | Trimmed content must contain between 1 and 250 characters. |
| `created_at`, `updated_at` | `NOT NULL`, default | Database | Default `CURRENT_TIMESTAMP`. |

**Application rule:** a block in either direction makes the member pair invisible to one another in member-facing discovery, match, interest, view and direct-profile workflows. This is separate from administrator account blocking/suspension.

## `member_interests`

| Column(s) | Constraint | Enforcement | Allowed values / rule |
|---|---|---|---|
| `id` | Primary key | Database | Generated `BIGSERIAL`. |
| `from_user_id` | Foreign key, `NOT NULL` | Database | References `users(id)`; `ON DELETE CASCADE`, `ON UPDATE RESTRICT`. |
| `to_user_id` | Foreign key, `NOT NULL` | Database | References `users(id)`; `ON DELETE CASCADE`, `ON UPDATE RESTRICT`. |
| `(from_user_id, to_user_id)` | Unique | Database | One directional interest per member pair. |
| from/to identity | Check | Database | A member cannot show interest in themself. |
| `created_at` | `NOT NULL`, default | Database | Default `CURRENT_TIMESTAMP`. |

**Application rule:** showing interest is idempotent. Existing interest history is retained if a member later blocks the other, but becomes unavailable in member-facing screens while the block exists.

## `member_profile_views`

| Column(s) | Constraint | Enforcement | Allowed values / rule |
|---|---|---|---|
| `id` | Primary key | Database | Generated `BIGSERIAL`. |
| `viewer_user_id` | Foreign key, `NOT NULL` | Database | References `users(id)`; `ON DELETE CASCADE`, `ON UPDATE RESTRICT`. |
| `viewed_user_id` | Foreign key, `NOT NULL` | Database | References `users(id)`; `ON DELETE CASCADE`, `ON UPDATE RESTRICT`. |
| `(viewer_user_id, viewed_user_id)` | Unique | Database | One aggregate row per member pair. |
| viewer/viewed identity | Check | Database | A member cannot create a self-view record. |
| `view_count` | `NOT NULL`, check | Database | Positive integer; default `1`. |
| `first_viewed_at` | `NOT NULL`, default | Database | Timestamp of first successful authorized profile view. |
| `last_viewed_at` | `NOT NULL`, default | Database | Timestamp of most recent successful authorized profile view. |
| `created_at`, `updated_at` | `NOT NULL`, default | Database | Default `CURRENT_TIMESTAMP`. |

**Application rule:** repeated authorized profile views atomically increment `view_count`. The aggregate supports both unique-viewer statistics and total-view counts for future free/paid entitlement rules.