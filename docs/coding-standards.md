# Coding Standards

## PHP

- Use `declare(strict_types=1);`.
- Use clear namespaces matching folders.
- Add return types and parameter types.
- Prefer small methods with one responsibility.
- Use descriptive names instead of abbreviations.
- Comment business reasons, not obvious syntax.
- Escape all view output with `esc()`.

## Controllers

Controllers should be thin.

Allowed:

- request reading;
- simple normalization;
- validation;
- service calls;
- redirects and views;
- flashdata and session identifiers.

Not allowed:

- SQL;
- multi-table transactions;
- large business rules;
- HTML generation.

## Services

Services own:

- business decisions;
- transaction boundaries;
- multiple model calls;
- result DTO creation;
- safe business error messages.

A service should not return a redirect or render a view.

## Models

Models own table-specific queries.

- Define `$table`, `$primaryKey`, `$returnType` and `$allowedFields`.
- Use timestamp fields consistently.
- Add named methods for repeated lookups.
- Do not place page flow or UI messages in models.

## Validation

- Keep complex form rules in `app/Validation`.
- Use custom messages for user-facing fields.
- Keep client and server rules aligned.
- Treat server validation as authoritative.

## Views

- Use layouts and reusable components.
- Keep business logic out of views.
- Resolve flashdata once near the top.
- Restore old values after validation redirects.
- Use named routes.
- Use semantic HTML and accessible labels.

## JavaScript

- Use vanilla JavaScript unless a library is already part of the project.
- Use IIFEs or modules to avoid leaking variables.
- Put reusable logic in `components`.
- Put page-only logic in `pages`.
- Prevent double initialization with a marker where needed.
- Do not load a page script twice.

## CSS

- Check Bootstrap utilities and `app.css` before adding CSS.
- Keep reusable styles in `app.css`.
- Keep only specific additions in `custom.css`.
- Avoid `!important` unless required to override a vendor rule.
- Use meaningful component class names.
- Do not duplicate selectors with the same purpose.

## Database

- Use parameterized queries or CI4 query builders.
- Use constraints for final data integrity.
- Use services for transactions.
- Keep runtime queries in models.
- Keep schema and deployment changes in `sql`.

## Error handling

- Throw exceptions for unexpected technical failures.
- Return result DTOs for expected business outcomes.
- Log technical details.
- Show safe, simple messages to users.
- Never display stack traces or raw database errors in production.

## Security

- Keep CSRF protection enabled.
- Read only expected request fields.
- Do not mass-assign the whole request.
- Never store plain OTPs or passwords.
- Never commit secrets.
- Use public reference numbers instead of exposing internal IDs.

## Naming

Examples:

```text
Controller: RegistrationController
Validation: RegisterFreeValidation
Service: RegisterFreeService
Result DTO: RegisterFreeResult
Model: UserContactModel
View: Pages/Registration/VerifyOtp.php
Page JS: registration-verify.js
SQL update: 20260713_001_add_field.sql
```

## Method order

A class should generally contain:

1. constants;
2. properties;
3. constructor;
4. public methods;
5. protected methods;
6. private methods.

## Review checklist

- Does every class have one clear purpose?
- Is the controller thin?
- Is business logic in a service?
- Are database queries in models?
- Are expected failures represented clearly?
- Is output escaped?
- Are transactions rolled back on exceptions?
- Are client, server and database rules aligned?