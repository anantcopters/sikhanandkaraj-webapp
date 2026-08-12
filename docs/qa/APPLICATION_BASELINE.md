# Application QA Baseline

_Last structural reconciliation: `development` HEAD `f2b16aa1a3ce7c53278b3b68d20524d3970fca05`, 2026-08-12._

## Purpose

This file is the persistent QA baseline for the application as it exists when reviewed. It is not a reconstruction of historical tickets.

Stable current behavior belongs here after code/runtime verification. Feature-specific findings/evidence remain under `docs/qa/features/` and permanent repeatable checks belong in `REGRESSION_SUITE.md`.

## Baseline rules

- `development` is the normal source unless another branch is explicitly requested.
- Record behavior observed from current code and available test evidence.
- Separate static code review from runtime/browser/database verification.
- A check not executed is `NOT VERIFIED`/`NOT RUN`, never PASS by assumption.
- Separate pre-existing defects from regressions introduced by the current feature.
- If behavior/requirement conflicts, record `NEEDS CONFIRMATION` rather than silently redefining it.
- Update this inventory when new real modules appear in code.

## Current module inventory

Status values: `NOT BASELINED`, `IN REVIEW`, `BASELINED`, `NEEDS RE-BASELINE`.

| Module | Baseline Status | Last structural review | Notes |
|---|---|---|---|
| Registration | NEEDS RE-BASELINE | 2026-08-12 | Mobile-only + OTP activation contract documented; runtime QA still required. |
| Member Password/OTP Login | NEEDS RE-BASELINE | 2026-08-12 | Shared member session, enumeration-resistant OTP flow. |
| Forgot/Reset Password | NEEDS RE-BASELINE | 2026-08-12 | Verified-mobile delivery contract. |
| Member Dashboard | NEEDS RE-BASELINE | 2026-08-12 | Uses standardized profile thumbnail presentation. |
| Member Profile - Basic Details | NEEDS RE-BASELINE | 2026-08-12 | Current service/model flow present. |
| Member Profile - Family Details | NEEDS RE-BASELINE | 2026-08-12 | Community + optional immutable SAK Volunteer assignment. |
| Member Profile - Education/Profession | NEEDS RE-BASELINE | 2026-08-12 | Search/master dependencies expanded. |
| Member Profile - Lifestyle/About Me | NEEDS RE-BASELINE | 2026-08-12 | Current displayed journey. |
| Member Profile - Photos/Media | NEEDS RE-BASELINE | 2026-08-12 | Private S3 + signed viewer-aware CloudFront delivery. |
| Profile Completion | NEEDS RE-BASELINE | 2026-08-12 | Sikh/Religious section excluded from displayed completion journey. |
| Partner Preferences | NEEDS RE-BASELINE | 2026-08-12 | Structured/basic/additional preferences drive matchmaking. |
| Matches | NEEDS RE-BASELINE | 2026-08-12 | Standard ProfileCard + preference scoring. |
| Search | NEEDS RE-BASELINE | 2026-08-12 | Dedicated filters/pagination; DB search indexes in 004. |
| Interests | NEEDS RE-BASELINE | 2026-08-12 | Received/Sent All/Pending/Accepted/Declined + response actions. |
| Shortlists | NEEDS RE-BASELINE | 2026-08-12 | Dedicated relationship table/domain. |
| Member Blocking | NEEDS RE-BASELINE | 2026-08-12 | Relationship privacy, distinct from admin suspension. |
| Full Other-Member Profile | NEEDS RE-BASELINE | 2026-08-12 | Viewer-aware media authorization + profile-view tracking. |
| Notifications | NEEDS RE-BASELINE | 2026-08-12 | Interaction notification types + read cleanup. |
| Messages | NOT BASELINED | - | Navigation exists; end-to-end messaging contract requires separate review. |
| Admin Authentication/Authorization | NEEDS RE-BASELINE | 2026-08-12 | SUPER_ADMIN/ADMIN boundaries remain. |
| Admin Member/Photo Review | NEEDS RE-BASELINE | 2026-08-12 | Private media/role rules apply. |
| SAK Volunteer Admin Management | NEEDS RE-BASELINE | 2026-08-12 | Identity/review/document schema expanded through SQL 009. |
| SAK Volunteer Self-Registration | NEEDS RE-BASELINE | 2026-08-12 | Review-controlled SELF registrations. |
| SAK Volunteer OTP Login/Portal | NEEDS RE-BASELINE | 2026-08-12 | Separate OTP/session/filter + submitted-profile read view. |
| Master Data | NOT BASELINED | - | Requires explicit current-data review. |
| Prelaunch Profile | IN REVIEW | 2026-08-10 / QA-0001 | Existing QA record remains authoritative for open findings/runtime verification. |
| Development/QA Profile Loader | NEEDS RE-BASELINE | 2026-08-12 | CLI-only deterministic QA profiles via real media pipeline. |
| Email Queue/Maintenance Jobs | NEEDS RE-BASELINE | 2026-08-12 | Worker + cleanup scripts and locking. |
| Database Deployment | NEEDS RE-BASELINE | 2026-08-12 | Baseline 000 + numbered 001–009; unknown/partial state must stop. |

`NEEDS RE-BASELINE` above means the module boundary/current static design has been reconciled, but the complete mandatory QA evidence set has not been rerun and therefore is not claimed PASS.

## Stable cross-module architecture observations

### Presentation

The member UI has exactly four supported member presentation contexts:

1. Dashboard thumbnail;
2. Search/Matches ProfileCard;
3. Interest ProfileInterestCard;
4. Full other-member profile.

Shared summary data for list contexts is produced through `MemberProfilePresentationService`. Runtime visual/responsive verification remains required before marking affected UI baselined.

### Database deployment

- baseline: `app/Database/sikhanandkaraj_db.sql` (000, immutable);
- increments: `database/001_*.sql` through current `009_*.sql`;
- CI4 migrations are not in use for deployed evolution;
- deployment ledger: `deployment_sql_history`;
- FRESH → 000 then increments;
- EXISTING → only missing increments, never 000;
- UNKNOWN/PARTIAL → STOP.

### Current interaction schema

Incremental SQL now includes member blocks, interests/statuses, profile views, shortlists, Search indexes, SAK Volunteer identity, immutable Family Details volunteer assignment, volunteer login OTP/portal view, self-registration review state and private document filename references.

### Media/runtime permission observation

`AwsMediaService` currently depends on `CloudFrontService` at construction. Therefore a CLI workflow using upload-only media operations can still fail during service construction if the CloudFront private key is unreadable by the CLI OS user. Web success does not prove CLI readability when Apache and shell commands run under different users.

QA/security expectation: preserve least-privilege signer-key permissions; authorize the intended CLI process user/group rather than making the private key world-readable.

## Existing Prelaunch Profile baseline

The detailed feature review remains in `docs/qa/features/QA-0001-prelaunch-profile.md`. Its recorded open findings/runtime-verification items are not silently closed by this structural reconciliation.

Stable observed design includes:

- public standalone prelaunch collection while enabled;
- optional email, required mobile;
- required profile/family/education details and configured photo count;
- staged private local photo processing;
- authenticated admin review/moderation;
- approved migration into normal member/contact/profile/media tables;
- migration idempotency/locking and compensating S3 cleanup where possible.

Refer to QA-0001 for exact findings, evidence status and regression IDs.

## Baseline module template

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

## Baseline evolution

A future feature does not replace baseline history. After a feature passes QA, update affected stable current behavior here and permanent regression coverage where appropriate. Feature-specific evidence remains in its feature QA record.