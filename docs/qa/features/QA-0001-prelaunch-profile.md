# QA-0001 Prelaunch Profile

## Review scope

**Branch:** `development`  
**Re-QA date:** 2026-08-11  
**QA mode:** Static code/configuration/database-script review. Browser execution, live database inspection, upload execution, S3 execution and end-to-end migration were not available in this review.

**Rules used:**
- `docs/project_rules.md`
- `docs/project-rules-coding.md`
- `docs/qa/QA_RULES.md`
- `docs/qa/QA_GATE.md`
- `docs/qa/REGRESSION_SUITE.md`

## Requirement summary

Prelaunch Profile is a public standalone profile-entry flow that remains separate from live member data until administrator approval/migration. It collects required profile/master data, optional email, two photographs, and Field Officer attribution. The current production-style flow requires Field Officer code verification and revalidates the code/hidden ID server-side before save. Administrator review supports photo moderation, contact correction, profile approval/rejection and migration into member/profile/media storage.

## Re-QA findings

### QA-PRE-001 — Shared default password on immediately ACTIVE migrated accounts

**QA area:** Security QA / Requirement QA  
**Severity:** HIGH  
**Status:** OPEN / BLOCKING  
**Retest:** FAIL

**Location:** `app/Services/Prelaunch/PrelaunchMemberMigrationService.php`, `app/Config/Prelaunch.php`

**Evidence:** Migration still creates the member as `ACTIVE` and stores a hash derived from the single environment value `PRELAUNCH_MEMBER_DEFAULT_PASSWORD`. The migrated mobile and optional email are treated as verified.

**Project-rule conflict:** `docs/project-rules-coding.md` requires migration to define a safe initial account/password state and requires secure password/token behavior. A single reusable bootstrap password across all migrated members is not member-specific authentication.

**Risk:** Disclosure of the shared password can expose multiple migrated accounts when their login identifier is known or discoverable.

**Expected:** Initial access must be member-specific. Preferred design is no reusable shared password: require verified-mobile OTP/password creation/reset, or use a cryptographically random per-member bootstrap secret with forced replacement and secure delivery.

---

### QA-PRE-002 — Server validation accepts profile relationships the service rejects

**QA area:** Validation QA / Code QA  
**Severity:** MEDIUM  
**Status:** RESOLVED  
**Retest:** PASS (static)

**Location:** `app/Validation/Prelaunch/PrelaunchProfileValidation.php`; `app/Services/Prelaunch/PrelaunchProfileService.php`

**Evidence:** Validation now accepts exactly `SELF,SON,DAUGHTER,BROTHER,SISTER`. The service resolves SON/BROTHER to MALE, DAUGHTER/SISTER to FEMALE and accepts submitted gender only for SELF. `RELATIVE` and `FRIEND` are no longer accepted by authoritative validation.

---

### QA-PRE-003 — Duplicate `PageNotFoundException` catch block

**QA area:** Code QA  
**Severity:** LOW  
**Status:** RESOLVED  
**Retest:** PASS (static)

**Location:** `app/Controllers/Admin/PrelaunchProfileController.php`, `review()`

**Evidence:** Only one `PageNotFoundException` catch remains before the general `Throwable` catch.

---

### QA-PRE-004 — Photo moderation service exposes unused rejection-reason contract

**QA area:** Code QA  
**Severity:** LOW  
**Status:** OPEN / NON-BLOCKING

**Location:** `app/Services/Prelaunch/PrelaunchAdminReviewService.php::updatePhotoStatus()`

**Evidence:** The method still accepts `?string $reason`, but the implementation always writes `rejection_reason => null`; the argument is unused.

**Expected:** Remove the unused argument if photo rejection reasons are intentionally unsupported, or validate/persist it if required.

---

### QA-PRE-005 — Prelaunch migration performs external S3 work inside an open DB transaction

**QA area:** Code QA / Database QA  
**Severity:** HIGH  
**Status:** OPEN / BLOCKING

**Location:** `app/Services/Prelaunch/PrelaunchMemberMigrationService.php`

**Evidence:** `migrate()` begins a DB transaction, creates member/contact/profile records, calls `migrateApprovedPhotos()` through the AWS media workflow, and commits only afterwards. The service includes compensating S3 deletion if a later failure occurs.

**Project-rule conflict:** `docs/project-rules-coding.md` explicitly states that external SMS, email, S3 or CloudFront calls must not keep a database transaction open; commit the database decision first or use a queue/outbox.

**Risk:** Network/provider latency or failure can hold PostgreSQL locks/transactions open, increase contention, and make operational recovery dependent on cross-system compensation.

**Expected:** Restructure migration so external media/provider operations do not execute while a DB transaction is held, while preserving idempotency and failure-state tracking.

---

### QA-PRE-006 — Immutable database baseline was modified after baseline creation

**QA area:** Database QA / Code QA  
**Severity:** HIGH  
**Status:** OPEN / BLOCKING

**Location:** `app/Database/sikhanandkaraj_db.sql`

**Evidence:** The baseline declares `BASELINE_VERSION: 000` and says it is immutable and that all subsequent DB changes must be numbered incremental scripts. Comparison from the original QA commit to current `development` shows this baseline has since been modified (+15 lines).

**Project-rule conflict:** Both `docs/project_rules.md` and `docs/project-rules-coding.md` require the baseline to remain immutable and later DB changes to use numbered incremental SQL files.

**Risk:** Fresh and existing deployments can diverge because existing environments never replay baseline 000 while fresh environments receive schema changes that are absent from the incremental ledger.

**Expected:** Restore baseline 000 to its frozen baseline content and place every post-baseline schema change in the appropriate new numbered incremental SQL script. Do not amend already executed incremental scripts.

---

### QA-PRE-007 — Field Officer environment/configuration contract is contradictory

**QA area:** Requirement QA / Validation QA / Code QA  
**Severity:** MEDIUM  
**Status:** OPEN / BLOCKING

**Location:** `app/Config/Prelaunch.php`; `app/Controllers/Prelaunch/PrelaunchProfileController.php`; `app/Services/Prelaunch/PrelaunchProfileService.php`

**Evidence:** Comments state that production requires explicit Field Officer verification while QA/development should continue automatic configured-officer behavior. However `requiresFieldOfficerVerification` is set TRUE when `APP_DEPLOYMENT` is either `development` or `production`. The automatic configured-officer path is therefore not used for `development` as documented. Depending on the actual QA deployment value, QA behavior may also differ from the stated contract.

**Risk:** Environment-specific behavior can be the opposite of the documented release intent, causing QA to test a different Field Officer workflow than expected.

**Expected:** Define the intended values explicitly (for example production=true, QA/development=false if that is the requirement) and make config, comments, UI and service behavior agree.

---

## 1. Requirement QA

**Result: FAIL**

Positive re-QA evidence:
- Field Officer verification is now implemented as an explicit public POST endpoint.
- The hidden verified officer ID is not trusted alone; save revalidates active officer code and ID server-side.
- Relationship/gender values are aligned.
- Email remains optional.

Failure reasons:
- `QA-PRE-001`: initial migrated-member credential model remains unsafe/unresolved.
- `QA-PRE-007`: environment behavior contradicts the documented production versus QA/development Field Officer requirement.

## 2. Code QA

**Result: FAIL**

Positive re-QA evidence:
- `QA-PRE-003` is fixed.
- `QA-PRE-002` is fixed.
- Public controller continues to build allowlisted input, runs authoritative validation and delegates creation to services.
- Field Officer browser-controlled ID is revalidated in the service.

Failure reasons:
- `QA-PRE-005`: S3/provider work is performed while a DB transaction is open, directly violating coding rules.
- `QA-PRE-006`: baseline DB change violates change/deployment discipline.
- `QA-PRE-007`: configuration implementation disagrees with its documented behavior.

Non-blocking cleanup: `QA-PRE-004`.

## 3. UI QA

**Result: NOT VERIFIED**

Static review confirms the public form receives `requiresFieldOfficerVerification`, uses the Field Officer verification workflow, and has page JavaScript for dependent/verification behavior. Actual desktop/tablet/mobile rendering, keyboard/focus behavior, verification reset after code edits, loading/error states, two-photo UX and duplicate-submit behavior were not executed in a browser.

## 4. Validation QA

**Result: FAIL**

Positive re-QA evidence:
- `profile_created_for` validation now matches service-supported values.
- Production-style Field Officer validation requires `FOSAK` plus six digits and a verified positive officer ID.
- Save revalidates officer code/ID against an ACTIVE officer.

Failure reason:
- `QA-PRE-007`: whether Field Officer verification is required is controlled inconsistently with the documented environment contract.

## 5. Database QA

**Result: FAIL**

Positive static evidence:
- Migration uses row locking/idempotency checks and DB transaction handling.
- New numbered database scripts exist for later features.

Failure reasons:
- `QA-PRE-005`: external S3 work occurs inside the DB transaction.
- `QA-PRE-006`: baseline 000 has been changed despite being explicitly immutable.

Live PostgreSQL verification of current constraints/indexes and migration rollback remains NOT RUN.

## 6. Security QA

**Result: FAIL**

Positive re-QA evidence:
- Field Officer code has strict server validation.
- Verification resolves only an ACTIVE/non-deleted officer.
- Save revalidates the officer ID/code pair, preventing simple hidden-ID tampering.
- Admin routes remain protected by admin authentication.

Blocking finding:
- `QA-PRE-001`: all migrated ACTIVE members still receive a password derived from one shared environment secret.

Runtime CSRF, abuse/rate-limit, direct endpoint, upload and session checks remain NOT RUN.

## 7. Regression QA

**Result: NOT VERIFIED**

Static regression review confirms `REG-PRE-009`'s relationship contract is corrected in code. No browser/integration/live-DB suite was executed, so permanent cases remain `NOT RUN` rather than PASS. Re-QA additionally requires Field Officer verification/environment behavior, migration transaction/provider behavior and deployment-baseline integrity to be covered permanently.

## QA Gate

| QA Area | Result |
| --- | --- |
| Requirement QA | FAIL |
| Code QA | FAIL |
| UI QA | NOT VERIFIED |
| Validation QA | FAIL |
| Database QA | FAIL |
| Security QA | FAIL |
| Regression QA | NOT VERIFIED |
| **FINAL QA GATE** | **FAILED** |

### Gate reason

Re-QA closes `QA-PRE-002` and `QA-PRE-003`, but the feature remains release-blocked by HIGH findings `QA-PRE-001`, `QA-PRE-005` and `QA-PRE-006`, plus blocking MEDIUM `QA-PRE-007`. UI and executable regression evidence are still outstanding.

## Required next re-QA scope

1. Replace or explicitly redesign the shared migrated-member password/bootstrap flow.
2. Move S3/provider activity outside open DB transactions while preserving safe migration recovery/idempotency.
3. Restore immutable baseline 000 discipline and put post-baseline changes in numbered incremental SQL.
4. Align Field Officer verification configuration with the intended production/QA/development behavior.
5. Decide/remove or implement the unused photo rejection-reason argument.
6. Execute browser checks for public form, Field Officer verify/edit/reverify, two-photo validation, responsive behavior and duplicate-submit prevention.
7. Execute integration/live-DB checks for duplicate contacts, one-time migration, DB/provider failure recovery and schema constraints.
