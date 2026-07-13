# Architecture Decision Log

This file records important technical decisions so future developers understand why the project is structured this way.

## ADR-001: CodeIgniter 4 layered architecture

**Decision:** Use Route → Controller → Validation → Service → Model → Database.

**Reason:** It keeps HTTP handling, business logic and database access separate and makes the code easier for junior developers to follow.

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

**Decision:** Use `sql/schema`, `sql/updates` and `sql/seeds`.

**Reason:** The project owner prefers simple, reviewable SQL that can later be executed by a CI/CD pipeline.

## ADR-006: PostgreSQL

**Decision:** Use PostgreSQL as the application database.

**Reason:** It provides strong constraints, partial indexes, transactions and reliable relational data handling suitable for profile, contact and matching data.

## ADR-007: Server validation is authoritative

**Decision:** Client validation improves UX, but every form must also have server validation.

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

**Decision:** Reusable component styles belong in `app.css`. `custom.css` contains only missing project/page-specific styles.

**Reason:** It reduces redundant CSS and keeps visual behaviour consistent.

## ADR-013: Public profile reference numbers

**Decision:** Public profiles use a unique value in the form `SAK` plus seven digits.

**Reason:** Internal numeric database IDs should not be exposed as public profile identifiers.

## ADR-014: Contacts are stored separately

**Decision:** Email and mobile contacts live in `user_contacts` with independent verification status.

**Reason:** It supports future contact changes, multiple contact types and verification history without widening the user table.

## ADR-015: OTP values are hashed

**Decision:** Store only a hash of the OTP.

**Reason:** A database leak should not expose usable OTP values.

## Adding a decision

Add a new numbered section when a decision affects several future features. Include:

- decision;
- reason;
- date when useful;
- consequences or migration notes when relevant.