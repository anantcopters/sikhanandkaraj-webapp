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
| Prelaunch Profile | NOT BASELINED | - | - |

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

## Baseline evolution

A future feature does not replace the baseline history. After a feature passes QA, update the affected module's stable current behavior here only when the feature materially changes that baseline. Feature-specific evidence remains in its feature QA record.
