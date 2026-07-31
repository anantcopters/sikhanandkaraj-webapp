# Create a Feature from UI to Database

Use this order for every feature:

1. Confirm the current business flow and affected account/profile states.
2. Add named GET/POST routes with the correct filter.
3. Add immutable SQL updates and constraints when data changes.
4. Add or extend a table-specific model.
5. Add dedicated server validation.
6. Add a service for business rules, transactions and provider orchestration.
7. Keep the controller limited to expected input, validation and response selection.
8. Build an escaped, accessible view using existing components and `app.css`.
9. Add page JavaScript only for page-specific behavior.
10. Test success, validation, authorization, concurrency, provider failure and mobile layout.
11. Update the relevant documentation and decision log.

## Required design questions

Before coding, document:

- who may perform the action;
- valid source and destination states;
- tables and master data involved;
- transaction boundary;
- whether an external call occurs after commit;
- safe public error behavior;
- audit requirements;
- rollback and duplicate-submission behavior.

## Completion rule

A feature is incomplete when routes, validation, service logic, constraints, responsive UI, security handling or documentation are missing—even when the happy path works.
