# QA Rules

## Purpose

This is the mandatory QA process for Sikhanandkaraj. The developer implements features. QA reviews the implementation, reports defects, maintains QA knowledge, and decides the QA Gate. QA does not modify application code unless explicitly requested.

## Source of truth

QA reviews the latest requested branch, normally `development`, together with current project rules, architecture, existing services/support/validation patterns, database design, and the QA knowledge base.

A feature is never marked PASS only because code appears correct. PASS requires evidence from code inspection, executable tests where available, or explicit manual verification. Unexecuted checks are `NOT VERIFIED`, not PASS.

## Mandatory QA areas

### 1. Requirement QA
Verify implementation against the stated requirement and acceptance criteria. Check requested behavior, omissions, unintended scope, business rules, status transitions, happy paths, alternate paths, error paths, and corner cases. Record ambiguities rather than guessing.

### 2. Code QA
Check compliance with project architecture and rules: thin controllers; reuse existing services, support classes, helpers, models and validation patterns; avoid duplicate business logic; keep SQL/business logic out of views; use transactions for atomic multi-step writes; appropriate error handling and logging; no hardcoded environment behavior; consistent naming/structure; no unnecessary classes, CSS, JavaScript, dependencies or abstractions; identify dead/redundant code introduced by the change.

### 3. UI QA
Where applicable check desktop/tablet/mobile behavior; reuse existing styling/classes; normal, empty, loading, disabled, success and error states; duplicate-click protection; confirmations/errors; long or missing content; overflow/layout issues; refresh/back behavior; labels, focus, keyboard usability and meaningful feedback. Browser/device checks that cannot be executed are `NOT VERIFIED` and listed for manual QA.

### 4. Validation QA
Check required/optional/nullability rules, type/format, length/range, allowed values, normalization, cross-field rules, client validation, authoritative server validation, appropriate database constraints, useful error messages, and tampered-request handling. Report disagreement between UI, server and database rules.

### 5. Database QA
Where applicable check primary/foreign keys, uniqueness, nullability, check/status constraints, indexes, defaults/timestamps, duplicate/orphan prevention, update/delete behavior, transaction safety, existing-data compatibility, deployment/migration safety, rollback implications and query scope. For stateful features document allowed and forbidden transitions.

### 6. Security QA
Security review is mandatory. Where applicable check authentication, authorization/roles, resource ownership, IDOR/object authorization, CSRF, XSS/output escaping, query parameterization, mass assignment, request tampering, privilege escalation, direct endpoint access, file-upload/media authorization, sensitive-data exposure, error/log leakage, abuse controls, replay/double-submit risks, and session/security-state handling.

### 7. Regression QA
Identify and verify existing behavior that can be affected: directly affected modules, shared services/helpers/models/validation, authentication/account-state dependencies, related UI flows, database side effects, existing automated tests, recorded regression cases, and previously fixed defects. A confirmed defect fix should create or update a permanent regression case.

## Result values

Each QA area uses one of: `PASS`, `FAIL`, `NOT VERIFIED`, `NOT APPLICABLE`.

## Finding severity

- `CRITICAL` — severe security, data-loss, or system-wide failure; release blocked.
- `HIGH` — major requirement, authorization, data-integrity, or core-flow failure; release blocked.
- `MEDIUM` — meaningful functional/validation/UI problem; normally blocks until resolved unless risk is explicitly accepted.
- `LOW` — minor defect with low user/business impact.
- `IMPROVEMENT` — maintainability/usability recommendation, not a defect.

Each finding records: unique ID, QA area, severity, exact file/path and code location where possible, expected behavior, actual behavior/evidence, risk/impact, recommended correction, and retest status.

## QA lifecycle

1. Developer implements and updates the target branch.
2. QA reads the latest branch and relevant QA knowledge.
3. QA performs all seven QA reviews.
4. QA records findings and QA Gate status in the feature QA record.
5. Developer fixes findings.
6. QA re-reviews fixes and affected regression scope.
7. QA updates the same feature record.
8. When blocking conditions are resolved, QA Gate becomes PASS.
9. Permanent regression knowledge is added to `REGRESSION_SUITE.md`.

## Existing application

Do not reconstruct every historical feature ticket. Document the current application incrementally in `APPLICATION_BASELINE.md`. When a module is first reviewed, record observed behavior, architecture, data dependencies, validation/security expectations, and known gaps. Mark baseline defects as pre-existing unless a new feature caused them.

## Future features

Each reviewed feature gets one persistent record under `docs/qa/features/`. Re-QA updates the same record. It contains requirement coverage, affected components, findings, all QA-area results, regression additions, outstanding manual verification, and final QA Gate status.
