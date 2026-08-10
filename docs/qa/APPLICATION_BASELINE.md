# Application QA Baseline

## Purpose

This file is the QA baseline for the application as it exists when systematic QA is introduced. It is not intended to recreate historical feature tickets.

The baseline is built incrementally. When QA reviews a module, update its section with observed behavior and dependencies from the current code. Do not assume undocumented behavior.

## Baseline rules

- The `development` branch is the normal baseline source unless another branch is explicitly requested.
- Record behavior observed from current code and available test evidence.
- Separate pre-existing defects from defects introduced by a newly reviewed feature.
- Do not silently redefine legacy behavior while documenting it.
- If expected behavior is unclear, mark it `NEEDS CONFIRMATION`.
- Add important stable behavior to the permanent regression suite.

## Module inventory

Status values: `NOT BASELINED`, `IN REVIEW`, `BASELINED`, `NEEDS RE-BASELINE`.

| Module | Baseline Status | Last QA Review | Notes |
| --- | --- | --- | --- |
| Registration | NOT BASELINED | - | - |
| Mobile/OTP Verification | NOT BASELINED | - | - |
| Login/Authentication | NOT BASELINED | - | - |
| Forgot/Reset Password | NOT BASELINED | - | - |
| Member Dashboard | NOT BASELINED | - | - |
| Member Profile - Basic Details | NOT BASELINED | - | - |
| Member Profile - Family Details | NOT BASELINED | - | - |
| Member Profile - Education/Profession | NOT BASELINED | - | - |
| Member Profile - Photos/Media | NOT BASELINED | - | - |
| Profile Completion | NOT BASELINED | - | - |
| Matches/Search | NOT BASELINED | - | - |
| Interests | NOT BASELINED | - | - |
| Messages/Notifications | NOT BASELINED | - | - |
| Admin | NOT BASELINED | - | - |
| Master Data | NOT BASELINED | - | - |
| Prelaunch Profile | IN REVIEW | 2026-08-10 / QA-0001 | Static baseline recorded; QA Gate FAILED pending fixes and runtime verification. |

Add/remove modules when code inspection shows that the application boundaries differ.

## Baseline module template

Use the following structure when a module is baselined.

### Module: <name>

**Baseline status:** BASELINED  
**Reviewed branch/commit:** <branch and commit>  
**Reviewed date:** <date>

#### Current behavior
- <observed behavior>

#### Entry points
- Routes: <paths>
- Controllers: <paths>
- Services/support: <paths>
- Models: <paths>
- Views/JS: <paths>

#### Database dependencies
- Tables: <tables>
- Important constraints/relationships: <details>

#### Validation rules
- <stable validation behavior>

#### Security expectations
- <authentication/authorization/ownership expectations>

#### Known pre-existing findings
- <finding or None>

#### Permanent regression coverage
- <REG-ID references>

#### Items needing confirmation/manual verification
- <items or None>

## Module: Prelaunch Profile

**Baseline status:** IN REVIEW  
**Reviewed branch/commit:** `development` / `7193e791dadddc2293e9becd0b8e92936b4038a4`  
**Reviewed date:** 2026-08-10  
**Feature QA record:** `docs/qa/features/QA-0001-prelaunch-profile.md`

### Current behavior
- Public standalone prelaunch profile form is available while `Prelaunch::profileEntryEnabled` is true.
- Field Officer assignment comes from server configuration (`profileFieldOfficerId`); the former public Field Officer verification flow is commented out.
- The form currently offers profile-created-for values Self, Son, Daughter, Brother and Sister.
- Gender is inferred server-side for Son/Brother/Daughter/Sister and selected for Self.
- Email is optional; mobile is required.
- Basic details, education/profession, family details, consent and exactly two photographs are collected.
- Photographs accept JPG/PNG/WebP, are validated for size/type/dimensions, re-encoded to optimized WebP and staged under CI4 writable storage.
- Current local photo storage generates the optimized original only; medium/thumbnail paths may be null and admin photo delivery falls back to original.
- Admin prelaunch review routes are protected by `adminAuth`.
- Admin can list/search profiles, view staged photographs, moderate photographs, correct contacts, reject profiles, or approve and migrate them.
- At least one approved photograph is required before profile approval/migration.
- Approval migrates the profile into normal member/profile/contact tables and approved photographs into the existing S3 media pipeline.
- Migration performs a row lock/idempotency checks and attempts compensating S3 cleanup when the DB transaction fails.
- Current migration creates an ACTIVE member, treats migrated contacts as verified, and uses one environment-configured default password for migrated accounts; this is an open HIGH security finding.

### Entry points
- Routes: `app/Config/Routes.php` (`/prelaunch/profile`, public city master endpoint, success route; admin prelaunch routes under authenticated admin group)
- Controllers: `app/Controllers/Prelaunch/PrelaunchProfileController.php`, `app/Controllers/Admin/PrelaunchProfileController.php`
- Services/support: `app/Services/Prelaunch/PrelaunchProfileService.php`, `PrelaunchPhotoService.php`, `PrelaunchAdminReviewService.php`, `PrelaunchMemberMigrationService.php` and related prelaunch services
- Models: `app/Models/Prelaunch/PrelaunchProfileModel.php`, `PrelaunchPhotoModel.php` plus normal user/contact/member models during migration
- Validation: `app/Validation/Prelaunch/PrelaunchProfileValidation.php`, `PrelaunchPhotoValidation.php`
- Views/JS: `app/Views/Prelaunch/Profile/`, `app/Views/Admin/Prelaunch/`, `public/assets/js/pages/prelaunch-profile-form.js` and admin prelaunch scripts

### Database dependencies
- `prelaunch_profiles`
- prelaunch photo table used by `PrelaunchPhotoModel`
- `users`
- `user_contacts`
- normal member profile detail tables
- normal member photo/media tables
- field officer and master reference tables

Current DDL constraints were not fully verified during QA-0001; Database QA remains NOT VERIFIED.

### Validation rules
- DOB required, valid date, minimum age 18.
- Email optional, valid when present, maximum 190 characters.
- Mobile required and normalized; live-member contact duplication is checked.
- Master IDs must be positive and education/occupation are revalidated against shared active master data.
- Father/mother names, parent/guardian contact, Sikh community, gotra and nearest Gurudwara are required in current server validation.
- Consent must equal `1`.
- Exactly two photos are required by current config, with upload size limit 18 MB each and 300×300 minimum dimensions.
- Known mismatch: server validation also accepts `RELATIVE` and `FRIEND`, while the service does not support those relationship values (`QA-PRE-002`).

### Security expectations
- Public creation must enforce CSRF and server-side validation.
- Admin list/review/photo/moderation/migration must require authenticated admin access.
- Staged local photos must never be directly public.
- Uploaded files must be decoded/re-encoded and stored using non-user-controlled filenames.
- Member/contact collisions must prevent migration.
- Migration must be one-time and atomic with compensating S3 cleanup where possible.
- Migrated account bootstrap credentials must not use one reusable shared password capable of authenticating multiple ACTIVE accounts (`QA-PRE-001`).

### Known pre-existing findings
- `QA-PRE-001` HIGH — shared default password on ACTIVE migrated accounts.
- `QA-PRE-002` MEDIUM — relationship validation/service contract mismatch.
- `QA-PRE-003` LOW — duplicate exception catch in admin controller.
- `QA-PRE-004` LOW — unused photo rejection-reason contract.

### Permanent regression coverage
- `REG-PRE-001` through `REG-PRE-010`.
- `REG-SEC-001`.

### Items needing confirmation/manual verification
- Current feature requirements around configured versus entered Field Officer.
- Browser/responsive behavior and duplicate-submit handling.
- CSRF runtime enforcement.
- Current PostgreSQL DDL constraints and indexes.
- End-to-end local photo processing, admin review, S3 migration and rollback.
- First-login/bootstrap behavior after migrated-account security changes.

## Baseline evolution

A future feature does not replace the baseline history. After a feature passes QA, update the affected module's stable current behavior here only when the feature materially changes that baseline. Feature-specific evidence remains in its feature QA record.
