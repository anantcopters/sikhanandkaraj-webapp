# Architecture Decision Log

This file records important technical decisions so future developers understand why the project is structured this way.

## ADR-001: CodeIgniter 4 layered architecture

**Decision:** Use Route → Controller → Validation → Service → Model → Database.

**Reason:** It keeps HTTP handling, business logic and database access separate and makes the code easier to test and maintain.

## ADR-002: Thin controllers

**Decision:** Controllers read requests, validate, call services and return responses.

**Reason:** Controllers become difficult to test and reuse when they contain business rules or SQL.

## ADR-003: Services own business logic and transactions

**Decision:** Multi-step operations and transactions belong in services.

**Reason:** A service knows the full business operation and can coordinate several models safely.

## ADR-004: Models own runtime database queries

**Decision:** Runtime table queries live in CI4 models.

**Reason:** This keeps query code close to table configuration and avoids SQL inside controllers and views.

## ADR-005: Plain SQL files for schema and deployment changes

**Decision:** Use `sql/schema`, `sql/updates` and `sql/seeds` as the database source of truth.

**Reason:** Plain SQL is reviewable, PostgreSQL-specific constraints remain explicit and deployment can execute updates deterministically.

## ADR-006: PostgreSQL

**Decision:** Use PostgreSQL as the application database.

**Reason:** It provides strong constraints, partial indexes, row locking and reliable transactions suitable for profile, contact and authentication data.

## ADR-007: Server validation is authoritative

**Decision:** Client validation improves UX, but every form also has server validation.

**Reason:** Browser validation can be bypassed.

## ADR-008: Database constraints are final protection

**Decision:** Use unique indexes, checks, foreign keys and `NOT NULL` constraints.

**Reason:** Application pre-checks cannot fully protect against concurrent requests.

## ADR-009: Choices.js is opt-in

**Decision:** Enhance only selects containing `data-choice`.

**Reason:** Small native selects such as country codes should not be transformed automatically, and opt-in behaviour is predictable.

## ADR-010: Shared JavaScript components plus page scripts

**Decision:** Reusable logic lives in `assets/js/components`; page behaviour lives in `assets/js/pages`.

**Reason:** It prevents a large global file and avoids duplicating common behaviour.

## ADR-011: Page scripts are passed by controllers

**Decision:** Controllers pass a `pageScripts` array to the view layout.

**Reason:** The layout remains the single loading point while each page downloads only the scripts it needs.

## ADR-012: `app.css` is universal

**Decision:** Reusable public, member and administrator component styles belong in `app.css`. `custom.css` contains only missing public/page-specific styles.

**Reason:** It reduces redundant CSS and keeps visual behaviour consistent. Admin pages must not create a parallel `admin-*` design system.

## ADR-013: Public profile reference numbers

**Decision:** Public profiles use a unique value in the form `SAK` plus seven digits.

**Reason:** Internal numeric database IDs should not be exposed as public profile identifiers.

## ADR-014: Contacts are stored separately

**Decision:** Email and mobile contacts live in `user_contacts` with independent verification status.

**Reason:** It supports contact changes, multiple contact types and verification history without widening the user table.

## ADR-015: OTP values are hashed

**Decision:** Store only a hash of each OTP.

**Reason:** A database leak should not expose usable OTP values.

## ADR-016: Separate member and administrator authentication contexts

**Decision:** Member and administrator authentication use separate controllers, routes, filters, views and session keys.

**Reason:** Administrator privileges have different account states and security requirements. Separation prevents member-session assumptions from granting Admin access and allows each context to evolve independently.

**Consequence:** Logging out of one context should clear only that context unless a deliberate global logout is implemented.

## ADR-017: Role and status authorization for administrators

**Decision:** Administrator access is based on both role (`SUPER_ADMIN`, `ADMIN`) and status (`PENDING`, `VERIFIED`, `SUSPENDED`).

**Reason:** Authentication alone is insufficient. Pending accounts must finish invitation acceptance, suspended accounts must remain blocked and privileged management actions require a super administrator.

## ADR-018: Administrator invitations use hashed one-time tokens

**Decision:** Invitation URLs contain a cryptographically random token, while the database stores only its hash with expiry and consumption state.

**Reason:** Plain invitation tokens in the database would remain usable after a database disclosure.

**Consequence:** The raw token is available only when the invitation is generated and must not be logged.

## ADR-019: Invitation acceptance uses row locking

**Decision:** Lock invitation and affected administrator rows during acceptance before validating and consuming the token.

**Reason:** Two concurrent requests must not activate an account or consume the same invitation twice.

## ADR-020: External messages occur after the database decision

**Decision:** Commit essential account/invitation data before sending or queueing external email, using a retryable queue/outbox approach where available.

**Reason:** A mail-provider failure must not leave an uncertain or partially committed account transaction.

## ADR-021: Administrator actions are audited

**Decision:** Record meaningful authentication, invitation and account-management events in `admin_audit_logs`.

**Reason:** Privileged actions require traceability for support, security review and incident response.

**Consequence:** Audit payloads must omit passwords, OTPs, tokens, full session identifiers and unnecessary personal data.

## ADR-022: Operational scripts are CLI-only

**Decision:** Maintenance scripts live under `scripts/`, reject web execution and run through controlled cron/deployment jobs.

**Reason:** Cleanup and maintenance operations need filesystem/database privileges that must not be exposed through HTTP.

## Adding a decision

Add a new numbered section when a decision affects several future features. Include the decision, reason, date when useful, and consequences or migration notes when relevant.