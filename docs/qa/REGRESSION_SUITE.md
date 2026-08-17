# Permanent Regression Suite

## Purpose

This file contains regression cases that must survive individual feature work. It grows as the application baseline is established, features are QA-reviewed, and defects are fixed.

Do not populate cases from assumptions. Add a case after current behavior/requirement is confirmed by code review, accepted requirement, or resolved defect.

## Rules

- Every case has a stable unique ID.
- Do not reuse deleted IDs.
- A defect that could recur should result in a regression case.
- Feature QA must identify which existing regression cases are affected.
- Re-QA must execute/review the regression cases affected by the fix.
- Automated and manual cases may coexist.
- A case that was not actually executed is `NOT RUN`, not PASS.

## ID convention

Use `REG-<AREA>-NNN`.

Suggested area codes: AUTH, PROF, MEDIA, MATCH, INT, MSG, ADMIN, MASTER, PRE, DB, SEC.

## Execution status

- `PASS`
- `FAIL`
- `NOT RUN`
- `BLOCKED`
- `RETIRED`

## Authentication

No permanent cases recorded yet.

## Member Profile

No permanent cases recorded yet.

## Photos / Media

No permanent cases recorded yet.

## Matches / Search

No permanent cases recorded yet.

## Interests

No permanent cases recorded yet.

## Messages / Notifications

No permanent cases recorded yet.

## Admin

No permanent cases recorded yet.

## Master Data

No permanent cases recorded yet.

## Prelaunch Profile

### REG-PRE-001 - Valid public prelaunch profile save
**Origin:** Feature `QA-0001`  
**Expected:** Valid required data, consent and exactly two valid photos create one DRAFT profile plus two photo records atomically and redirect to success.  
**Automation:** Manual pending automation  
**Last result:** NOT RUN

### REG-PRE-002 - Duplicate member mobile rejected
**Origin:** Feature `QA-0001`  
**Expected:** A mobile already used by a prelaunch profile or live member contact is rejected and no partial profile/photo state is committed.  
**Automation:** Manual pending automation  
**Last result:** NOT RUN

### REG-PRE-003 - Optional email behavior
**Origin:** Feature `QA-0001`  
**Expected:** Missing email is accepted as NULL; supplied email must be valid and unique according to the prelaunch/member rules.  
**Automation:** Manual pending automation  
**Last result:** NOT RUN

### REG-PRE-004 - Photo upload safety
**Origin:** Feature `QA-0001`  
**Expected:** Invalid type, undecodable content, undersized/oversized images and duplicate raw photos are rejected without partial persistent state.  
**Automation:** Manual pending automation  
**Last result:** NOT RUN

### REG-PRE-005 - Admin prelaunch authorization
**Origin:** Feature `QA-0001`  
**Expected:** Admin list/review/photo/moderation/migration endpoints are inaccessible without a valid authenticated administrator session.  
**Automation:** Manual pending automation  
**Last result:** NOT RUN

### REG-PRE-006 - Approval requires approved photo
**Origin:** Feature `QA-0001`  
**Expected:** A DRAFT profile with zero approved photos cannot be approved/migrated; one or more approved photos satisfies the media prerequisite.  
**Automation:** Manual pending automation  
**Last result:** NOT RUN

### REG-PRE-007 - Migration is one-time
**Origin:** Feature `QA-0001`  
**Expected:** Concurrent/repeated approval attempts create at most one member for a prelaunch profile.  
**Automation:** Integration/manual pending automation  
**Last result:** NOT RUN

### REG-PRE-008 - Migration rollback consistency
**Origin:** Feature `QA-0001`  
**Expected:** A migration failure rolls back DB changes and removes S3 objects created by that failed attempt where cleanup succeeds.  
**Automation:** Integration/manual fault injection  
**Last result:** NOT RUN

### REG-PRE-009 - Relationship and gender contract
**Origin:** Feature `QA-0001`, finding `QA-PRE-002`  
**Expected:** SELF uses a valid submitted gender; SON/BROTHER resolve to MALE; DAUGHTER/SISTER resolve to FEMALE; unsupported relationship values fail validation cleanly.  
**Automation:** Manual pending automation  
**Last result:** NOT RUN

### REG-PRE-010 - Responsive form states
**Origin:** Feature `QA-0001`  
**Expected:** Desktop/tablet/mobile layouts, dependent dropdowns, validation, loading, duplicate-submit prevention, back/refresh and large-photo save states remain usable and consistent.  
**Automation:** Manual / Playwright candidate  
**Last result:** NOT RUN

## International Location Hierarchy

### REG-MASTER-001 - Country dependent location options
**Origin:** Canada international-location rollout

**Expected:** India remains the default. Changing country clears stale state/city values and loads only active states for the newest country request; changing state loads only active cities for the newest state request.

**Automation:** Integration/manual pending automation

**Last result:** NOT RUN

### REG-MASTER-002 - Location hierarchy tampering
**Origin:** Canada international-location rollout

**Expected:** Prelaunch and live member saves reject a country, state and city combination when the state does not belong to the country or the city does not belong to the state.

**Automation:** Integration test pending

**Last result:** NOT RUN

### REG-MASTER-003 - Migrated Canadian profile editing
**Origin:** Canada international-location rollout

**Expected:** A Canadian prelaunch profile can migrate and then reopen/save Basic Details, Family Details and Sikh/Religious birth location without being forced back to India.

**Automation:** End-to-end/manual pending automation

**Last result:** NOT RUN

### REG-MASTER-004 - International Search and Partner Preference
**Origin:** Canada international-location rollout

**Expected:** Search and Partner Preference show active Canadian provinces/territories with country-qualified labels and load cities for selected Canadian states.

**Automation:** Integration/manual pending automation

**Last result:** NOT RUN

## Cross-module Database Integrity

No dedicated permanent DB cases recorded yet. Prelaunch migration integrity is covered by `REG-PRE-007` and `REG-PRE-008` pending DDL baseline verification.

## Cross-module Security

### REG-SEC-001 - Migrated accounts do not share one reusable login secret
**Origin:** Feature `QA-0001`, finding `QA-PRE-001`  
**Expected:** Initial access for one migrated member cannot be reused to authenticate another migrated member; bootstrap access must be member-specific or prove control of that member's verified contact.  
**Automation:** Authentication integration/manual pending automation  
**Last result:** NOT RUN

## Retired cases

Keep retired cases here or retain their original section with status `RETIRED` and the reason. Do not erase regression history without explanation.
