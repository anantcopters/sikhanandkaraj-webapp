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

Suggested area codes:
- `AUTH` - registration/login/OTP/password/account state
- `PROF` - member profile/profile completion
- `MEDIA` - photos/media
- `MATCH` - matching/search
- `INT` - interests
- `MSG` - messages/notifications
- `ADMIN` - administration
- `MASTER` - master/reference data
- `PRE` - prelaunch profile
- `DB` - cross-module database integrity
- `SEC` - cross-module security regression

## Execution status

- `PASS`
- `FAIL`
- `NOT RUN`
- `BLOCKED`
- `RETIRED`

## Regression case format

### REG-<AREA>-NNN - <short title>

**Purpose:** <what previous/stable behavior this protects>  
**Origin:** Baseline / Feature `<QA-ID>` / Defect `<finding-id>`  
**Preconditions:** <required state>  
**Steps:**
1. <step>
2. <step>

**Expected result:** <observable result>  
**Automation:** Manual / Automated `<test path if available>`  
**Last result:** NOT RUN  
**Last reviewed:** <date/commit when known>

---

## Authentication

No permanent cases recorded yet. Populate during authentication baseline/feature QA.

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

No permanent cases recorded yet.

## Cross-module Database Integrity

No permanent cases recorded yet.

## Cross-module Security

No permanent cases recorded yet.

## Retired cases

Keep retired cases here or retain their original section with status `RETIRED` and the reason. Do not erase regression history without explanation.
