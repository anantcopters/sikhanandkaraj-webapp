# QA-0001 Prelaunch Profile

## Review scope

**Branch:** `development`  
**Reviewed commit:** `7193e791dadddc2293e9becd0b8e92936b4038a4`  
**Reviewed date:** 2026-08-10  
**QA mode:** Static code/configuration review. Browser execution, live database inspection, upload execution, S3 execution and end-to-end migration were not available in this review.

## Requirement summary observed from current implementation

The current Prelaunch Profile module provides a public standalone profile-entry form, configured Field Officer assignment, mandatory profile/master-data validation, exactly two required photographs, local optimized WebP staging, administrator review/moderation, contact correction, profile approval/rejection, and migration of approved profiles/photos into the normal member/S3 workflow.

No current non-empty repository project-rules document exists. `docs/PROJECT_RULES.md` was empty before being removed from the latest branch. Architecture checks therefore use the current codebase patterns and `docs/qa/QA_RULES.md`.

## Affected components reviewed

- `app/Config/Prelaunch.php`
- `app/Config/Routes.php`
- `app/Controllers/Prelaunch/PrelaunchProfileController.php`
- `app/Controllers/Admin/PrelaunchProfileController.php`
- `app/Services/Prelaunch/PrelaunchProfileService.php`
- `app/Services/Prelaunch/PrelaunchPhotoService.php`
- `app/Services/Prelaunch/PrelaunchAdminReviewService.php`
- `app/Services/Prelaunch/PrelaunchMemberMigrationService.php`
- `app/Models/Prelaunch/PrelaunchProfileModel.php`
- `app/Validation/Prelaunch/PrelaunchProfileValidation.php`
- `app/Validation/Prelaunch/PrelaunchPhotoValidation.php`
- `app/Views/Prelaunch/Profile/Index.php`
- `app/Views/Prelaunch/Profile/Partials/MemberDetails.php`
- `public/assets/js/pages/prelaunch-profile-form.js`
- QA knowledge base under `docs/qa/`

## Findings

### QA-PRE-001 — Shared default password on immediately ACTIVE migrated accounts

**QA area:** Security QA  
**Severity:** HIGH  
**Status:** OPEN / BLOCKING

**Location:** `app/Services/Prelaunch/PrelaunchMemberMigrationService.php`, `app/Config/Prelaunch.php`

**Evidence:** Migration creates an `ACTIVE` account, hashes one environment-configured `PRELAUNCH_MEMBER_DEFAULT_PASSWORD`, and marks the migrated mobile and optional email contacts verified.

**Risk:** Every migrated prelaunch member receives the same known credential. If that credential becomes known and a member identifier is known/guessed, the account can potentially be accessed without proving control of that specific member's contact. This is particularly risky because the account is immediately ACTIVE and contacts are already treated as verified.

**Expected:** Initial access should require a per-user secret or a verified one-time password/password-creation flow. A shared reusable password must not independently grant access to all migrated accounts.

**Recommended correction:** Prefer `password_hash = NULL` plus the existing verified-mobile OTP/password-creation/reset flow, or generate a cryptographically random per-member bootstrap secret with mandatory first-login replacement and secure delivery. Do not use one shared default password across migrated members.

**Retest:** NOT RUN.

---

### QA-PRE-002 — Server validation accepts profile relationships the service rejects

**QA area:** Validation QA / Code QA  
**Severity:** MEDIUM  
**Status:** OPEN

**Location:** `app/Validation/Prelaunch/PrelaunchProfileValidation.php`; `app/Services/Prelaunch/PrelaunchProfileService.php`

**Evidence:** `profile_created_for` validation accepts `RELATIVE` and `FRIEND`, but `resolveGender()` supports only `SELF`, `SON`, `DAUGHTER`, `BROTHER`, and `SISTER`. The public UI currently exposes only those five supported values, so this is primarily a tampered-request/server-contract inconsistency.

**Risk:** A request can pass authoritative validation and then fail later as an internal/service exception rather than a field validation error. The validation contract and business contract disagree.

**Expected:** Authoritative validation and service-accepted values must be identical.

**Recommended correction:** Remove `RELATIVE` and `FRIEND` from validation unless they are intended to be supported; otherwise implement their gender handling and expose them consistently.

**Retest:** NOT RUN.

---

### QA-PRE-003 — Duplicate `PageNotFoundException` catch block

**QA area:** Code QA  
**Severity:** LOW  
**Status:** OPEN

**Location:** `app/Controllers/Admin/PrelaunchProfileController.php`, `review()`

**Evidence:** Two consecutive identical `catch (PageNotFoundException $exception)` blocks are present.

**Risk:** No expected runtime behavior difference, but it is redundant/dead code and weakens maintainability.

**Expected:** One catch block.

**Recommended correction:** Remove the duplicate catch.

**Retest:** NOT RUN.

---

### QA-PRE-004 — Photo moderation service exposes unused rejection-reason contract

**QA area:** Code QA  
**Severity:** LOW  
**Status:** OPEN

**Location:** `app/Controllers/Admin/PrelaunchProfileController.php`; `app/Services/Prelaunch/PrelaunchAdminReviewService.php`

**Evidence:** `updatePhotoStatus()` accepts `?string $reason`, but the controller always sends `null` and the service always stores `rejection_reason => null`.

**Risk:** Misleading/dead API contract. If photo-rejection reasons are expected later, they are currently discarded.

**Expected:** Either remove the unused parameter/field behavior or validate and persist an actual reason.

**Recommended correction:** Simplify the method contract if no reason is required, or persist a validated reason when rejecting.

**Retest:** NOT RUN.

## 1. Requirement QA

**Result: NOT VERIFIED**

The current implementation is internally coherent around the configured Field Officer workflow and two-photo requirement, but there is no current feature specification in the QA knowledge base and the repository project-rules document is absent. Requirement-to-code traceability therefore cannot be marked PASS solely from static implementation inspection.

Manual/owner confirmation needed for:
- configured single Field Officer assignment versus user-entered Field Officer selection;
- exact two-photo requirement;
- whether administrator approval is intended to migrate immediately into the live member tables/S3;
- whether migrated contacts are intentionally considered verified;
- intended first-login/password behavior for migrated members.

## 2. Code QA

**Result: FAIL**

Positive observations:
- controllers generally delegate business logic to services;
- master-data logic is reused instead of duplicated;
- profile creation uses a transaction;
- filesystem cleanup is attempted on photo persistence failure;
- migration includes transaction handling and compensating S3 deletion;
- public input is allow-listed in the controller;
- centralized error logging is used without intentionally logging submitted PII.

Blocking/non-blocking findings: `QA-PRE-002`, `QA-PRE-003`, `QA-PRE-004`.

## 3. UI QA

**Result: NOT VERIFIED**

Static review confirms server-rendered validation errors, CSRF field, responsive Bootstrap layout classes, a save-progress modal, dependent state/city behavior, DOB age guidance, and explicit consent UI. However desktop/tablet/mobile rendering, Choices.js behavior, loading modal behavior, duplicate-submit protection, browser back/refresh behavior, large-photo upload UX, error focus/accessibility and actual responsive layout were not executed.

Required manual/browser checks are listed under regression coverage.

## 4. Validation QA

**Result: FAIL**

Positive observations:
- server validation is authoritative;
- email is optional and validated when present;
- DOB enforces 18+;
- names/gotra/contact/master IDs/photo type/size/dimensions are validated;
- photo validation is configuration-driven;
- live member mobile duplication is checked during prelaunch creation;
- active education/occupation selection is revalidated through the shared master-data service.

Finding `QA-PRE-002` causes FAIL because accepted server values and service-supported values disagree.

## 5. Database QA

**Result: NOT VERIFIED**

Static service/model review confirms transactional writes, soft-delete-aware duplicate lookups, migration idempotency checks, row locking during migration, contact uniqueness checks and migration status tracking. The actual current PostgreSQL DDL/migration file for the evolved prelaunch schema was not located on the latest branch during this review, so FK/UNIQUE/CHECK/index/nullability/rollback deployment guarantees cannot be fully verified.

Required DB verification:
- unique `profile_reference`;
- uniqueness strategy for prelaunch mobile and optional email under concurrency;
- FK coverage for all master IDs, field officer, reviewer and migrated user;
- CHECK constraints for status/profile-created-for/gender/created-source/photo approval status;
- unique `(prelaunch_profile_id, sequence_no)` photo constraint;
- unique `users.prelaunch_profile_id` constraint referenced by migration code;
- indexes for admin listing/search and cleanup fields;
- nullable `medium_path`/`thumbnail_path` compatibility with current one-original-only photo storage.

## 6. Security QA

**Result: FAIL**

Positive observations:
- admin prelaunch routes are inside `adminAuth`;
- staged photo delivery is admin-authenticated, private/no-store and uses server-resolved local paths;
- uploads validate image MIME/extension/decodability/dimensions and are re-encoded to WebP with random stored names;
- raw source uploads are not permanently retained;
- service/controller input allow-listing limits mass assignment;
- normal query-builder parameterization/escaping is used;
- public form includes CSRF markup.

Blocking finding: `QA-PRE-001`.

Additional runtime verification still required for the configured global CSRF filter, session/auth behavior, rate/abuse control on the public form, and direct-endpoint testing.

## 7. Regression QA

**Result: NOT VERIFIED**

No automated CI/status checks are attached to the reviewed commit and no executed prelaunch regression suite existed before this review. Permanent cases have been added to `docs/qa/REGRESSION_SUITE.md`, but they remain `NOT RUN` until browser/database/integration execution is performed.

## QA Gate

| QA Area | Result |
| --- | --- |
| Requirement QA | NOT VERIFIED |
| Code QA | FAIL |
| UI QA | NOT VERIFIED |
| Validation QA | FAIL |
| Database QA | NOT VERIFIED |
| Security QA | FAIL |
| Regression QA | NOT VERIFIED |
| **FINAL QA GATE** | **FAILED** |

### Gate reason

The release is blocked by one unresolved HIGH security finding (`QA-PRE-001`). Validation contract inconsistency (`QA-PRE-002`) must also be resolved or explicitly narrowed to the supported relationship set. UI/database/regression execution remains outstanding and must not be silently treated as passed.

## Re-QA scope

After fixes, re-QA must at minimum cover:
1. migrated-member first-login/password flow;
2. migrated contact verification/account state;
3. supported `profile_created_for` values including tampered POST requests;
4. public form CSRF and duplicate submission;
5. exactly-two-photo upload including invalid/duplicate/oversized images;
6. admin photo approval/rejection and profile approval/rejection;
7. contact collision against prelaunch and live member contacts;
8. successful migration into member profile tables and S3;
9. rollback when DB or S3 migration fails;
10. desktop/tablet/mobile UI execution.
