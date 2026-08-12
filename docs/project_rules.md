# Project Rules

## Purpose

This document is the mandatory engineering rule set for SikhanAndKaraj. All implementation and QA work must read this file together with the current application architecture and `docs/qa/QA_RULES.md`.

## Responsibility boundary

- The developer implements application features and fixes.
- QA reviews the implemented code and does not modify application code unless explicitly requested.
- QA may create and update QA documentation under `docs/qa/` as part of the QA process.
- A feature is not production-ready until its QA Gate is PASSED or an explicitly documented risk acceptance permits otherwise.

## Architecture and reuse

- Follow the existing CodeIgniter 4 project structure and established application patterns.
- Keep controllers thin. Controllers coordinate request/response flow and delegate business logic to services.
- Reuse existing Services, Support classes, Models, Validation classes, Helpers and shared components before creating new abstractions.
- Do not duplicate business logic already available in the application.
- Keep business logic and SQL out of views.
- Keep environment-specific behavior in configuration/environment values rather than hardcoding it in controllers, services or views.
- Use dependency/service wiring consistent with the existing `Config\Services` pattern.
- Do not introduce new third-party dependencies unless they are necessary and explicitly justified.

### Member profile views

The member-facing application uses exactly four profile presentation contexts. New member-listing/profile UI must reuse the appropriate existing presentation context rather than creating another independent member-card implementation.

1. **Dashboard profile thumbnail** — compact member presentation used by Dashboard profile collections. The shared component is `app/Views/Components/Member/ProfileThumbnail.php`.
2. **Search / Matches profile card** — standard member card used by Search Results and Matches. Search and Matches deliberately share the same presentation component and result pipeline. The shared component is `app/Views/Components/Member/ProfileCard.php`.
3. **Interest profile card** — member presentation used by Interest Received and Interest Sent, including Interest-specific status/actions. The shared component is `app/Views/Components/Member/ProfileInterestCard.php`.
4. **Full member profile view** — detailed single-member profile opened from member cards/thumbnails. This remains the authoritative detailed profile presentation and must not be replaced by listing-card logic.

Common member-summary data used by the first three multi-profile contexts must be produced through `App\Services\Matchmaking\MemberProfilePresentationService`. Context-specific state such as match percentage, Interest status/actions, Search state and pagination remains with the owning domain/service and must not be moved into the common presentation service.

All four contexts must preserve the application's member visibility, authorization, photo privacy and public profile-reference rules. Multi-profile views must use the authorized thumbnail media path and the standard gender-based placeholder when no authorized thumbnail is available. A new fifth member profile/card presentation must not be introduced unless an explicit requirement demonstrates that none of these four contexts can safely satisfy it.

## Validation

- Server-side validation is authoritative and mandatory for all submitted data.
- Client-side validation is for user experience only and must remain consistent with server rules.
- UI, server validation, service/business rules and database constraints must not contradict each other.
- Reuse existing validation classes/rules where the same business rule already exists.
- Normalize user input consistently before business checks and persistence.
- Never trust hidden fields, IDs, status values or browser-controlled business decisions without server-side verification.

## Database

- PostgreSQL is the application database; SQL must be PostgreSQL-compatible.
- Use existing models/services for database access where available.
- Multi-step writes that must succeed or fail together must use a database transaction.
- Protect data integrity using appropriate PK, FK, UNIQUE, CHECK, NOT NULL and index constraints where applicable.
- Prevent duplicate and orphan records.
- Database deployment follows the project deployment convention: `app/Database/sikhanandkaraj_db.sql` remains the immutable baseline; subsequent schema/data changes are placed in numbered incremental SQL files and tracked through `deployment_sql_history`.
- New deployments start from the baseline and apply numbered scripts newer than the baseline version in order. Existing deployments execute only scripts not already recorded in the deployment ledger. Stop deployment on SQL failure and record a script only after successful execution.
- Do not edit the baseline SQL to represent later incremental changes.

## Security

- Authentication and authorization must be enforced server-side.
- Every resource-specific action must verify ownership or role authorization; never rely on an ID supplied by the browser alone.
- Protect state-changing browser requests with the application's CSRF mechanism unless an explicitly reviewed exception exists.
- Prevent IDOR, privilege escalation, mass assignment, SQL injection, XSS and unsafe direct endpoint access.
- Use parameterized/query-builder database access.
- Never expose secrets, credentials, signing keys, passwords or sensitive tokens in source control, responses or logs.
- Do not log unnecessary personal or sensitive information. Mask sensitive audit values where practical.
- File uploads must validate size, MIME/type, extension and actual decodability/content where applicable.
- Media/private files must only be delivered through authorized application/media paths.
- Duplicate/replayed submissions must not create duplicate business operations.

## UI and CSS

- Follow the existing application visual language and responsive layout patterns.
- Reuse existing classes from `app.css`, `custom.css`, Bootstrap and existing shared components before adding new CSS.
- Do not create a new CSS class when an existing class/pattern can achieve the requirement.
- New UI must handle normal, loading, success, error, empty and disabled states where applicable.
- Saving/action buttons must prevent accidental duplicate submission where the operation is not safely repeatable.
- Preserve responsive behavior across desktop, tablet and mobile.
- Escape user-controlled output in views using the project's existing escaping conventions.
- Every PHP view must declare all controller/service supplied UI variables   in the opening PHPDoc using appropriate types.
- Every PHP view must normalize supplied values into local view variables   before rendering HTML. Views must not repeatedly access undefined or unvalidated external variables directly throughout the markup.
- PHP views must contain meaningful comments for major UI sections and for non-obvious conditional rendering logic.
- View-local normalization is permitted, but business rules, database access, authorization decisions and reusable business transformations must remain outside views.

## JavaScript

- Reuse existing JavaScript utilities/components and validation patterns.
- Keep JavaScript page-specific when behavior belongs to one page and shared when genuinely reusable.
- Client-side logic must not be the only enforcement point for business/security rules.
- Avoid duplicate requests and race conditions on save/action operations.

## Error handling and logging

- Use the existing centralized error logging/context patterns where available.
- User-facing errors must be safe and useful; internal exception details must not leak in production.
- Development may expose normal framework diagnostics where the project already permits it.
- Operational logs should include enough non-sensitive context for diagnosis.

## Change discipline

- Make the minimum maintainable change required by the feature.
- Do not refactor unrelated code as part of a feature unless required to make the feature safe/correct.
- Identify and remove code made genuinely redundant by the implemented change, but do not delete unrelated legacy behavior without requirement justification.
- Preserve backward compatibility unless the requirement explicitly changes existing behavior.
- Before adding a new class, service, helper, validation rule, CSS rule, JS utility or database structure, check whether an existing implementation should be reused or extended.

## QA requirements

Every implemented feature must be reviewed against the QA knowledge base under `docs/qa/`.

Mandatory QA areas are:

1. Requirement QA
2. Code QA
3. UI QA
4. Validation QA
5. Database QA
6. Security QA
7. Regression QA
8. Final QA Gate

QA findings and evidence are maintained in the relevant feature record under `docs/qa/features/`. Confirmed stable behavior and defects that can recur must update the application baseline and/or permanent regression suite.

A check that was not actually executed must be recorded as `NOT VERIFIED`/`NOT RUN`; it must not be reported as PASS solely from assumption.

## Source-of-truth order

When reviewing or implementing a change, use the following order:

1. Explicit current feature requirement.
2. This `docs/project_rules.md` document.
3. `docs/qa/QA_RULES.md` and applicable QA knowledge.
4. Existing application architecture and reusable implementation patterns.
5. Existing documented module behavior/regression knowledge.

If these sources conflict, do not silently choose one. Record the conflict and resolve it against the explicit current requirement and data/security integrity before proceeding.
