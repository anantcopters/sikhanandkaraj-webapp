# Project Rules for Coding

These rules are mandatory for all human-written and AI-generated changes in `sikhanandkaraj-webapp`. Review every proposed change against this document before committing or merging.

## 1. Source of truth

- Read the latest `development` branch before suggesting or writing code.
- Reuse the existing architecture, services, validation, components, CSS and JavaScript.
- Do not invent files, methods, routes, columns or master data without checking the repository.
- State migrations, backward-compatibility impact and redundant code when business logic changes.

## 2. Technology and environment

- CodeIgniter 4, PHP 8.3 and PostgreSQL 16.
- Local development runs on Windows/XAMPP.
- QA and production run on Ubuntu EC2 with Apache.
- Code must be environment-agnostic and respect Linux case sensitivity.
- Follow exact PSR-4 namespace, filename and class casing.

## 3. PHP standard

- Start PHP classes/files with `declare(strict_types=1);`.
- Add parameter and return types wherever supported.
- Prefer `final` for concrete classes not intended for inheritance.
- Keep methods small and focused.
- Add DocBlocks for non-obvious contracts and comments for business reasons, not obvious syntax.
- Never suppress errors to hide incorrect behavior.

## 4. Required architecture

```text
Route
  → Filter
  → Controller
  → Validation
  → Service
  → Model / Provider adapter
  → Database / external provider
  → Result DTO
  → Controller response
```

### Controllers

Controllers may:

- read explicitly expected request fields;
- perform simple normalization;
- run validation;
- call services;
- manage redirect/view/JSON responses and safe flash/session workflow identifiers.

Controllers must not:

- contain SQL;
- own multi-table transactions;
- implement large business rules;
- call AWS SDK/provider clients directly;
- pass the complete request payload to a model or service.

### Services

Services own:

- business decisions and state transitions;
- transaction boundaries;
- coordination of multiple models;
- row-lock/concurrency behavior;
- provider orchestration;
- small result DTOs for expected outcomes.

External SMS, email, S3 or CloudFront calls must not keep a database transaction open. Commit the database decision first or use a queue/outbox. Record delivery failure explicitly and safely.

### Models

- One model normally represents one table.
- Define table, primary key, return type, allowed fields and timestamps explicitly.
- Keep table-specific queries and reusable lookup methods in models.
- Do not put redirects, HTML, session decisions or complete business flows in models.

### Views

- Escape dynamic output with `esc()`.
- Use named routes through `route_to()`/`url_to()` as appropriate.
- Use layouts and reusable components.
- Do not query the database or decide account/business state in views.
- Never expose internal IDs when a public reference exists.

## 5. Validation and input handling

- Client validation is for UX; server validation is authoritative.
- Build an allowlisted input array from expected fields.
- Pass only validated values (`getValidated()`) to services.
- Ignore/reject unexpected fields; never mass-assign raw POST data.
- Keep client and server conditional rules aligned.
- Restore old values safely, never passwords, OTPs or tokens.
- Use database constraints as final protection against concurrent requests.

## 6. Authentication and account rules

- Keep member and administrator authentication contexts separate.
- Regenerate the session ID after successful authentication.
- Use shared member-session establishment so password and OTP login create the same contract.
- Disable caching on login, OTP, verification and password-reset pages.
- Member registration is mobile-only; email is optional and added later.
- A member remains `PENDING` until mobile OTP verification, then becomes `ACTIVE`.
- Login and password reset require `ACTIVE` status.
- OTP login is available only through a verified mobile, but public initiation must not reveal account existence or state.
- Password login through email is allowed only when that email contact is verified.

## 7. OTP, password and token security

- Hash passwords with `password_hash()` and verify with `password_verify()`.
- Generate OTPs/tokens with cryptographically secure randomness.
- Store only hashes of OTPs and one-time tokens.
- Enforce purpose, expiry, attempt limits, resend cooldown, issue quota and one-time consumption.
- Use row locking for sensitive token consumption when concurrent requests are possible.
- Never store plain OTPs/tokens in sessions.
- Never log passwords, OTPs, raw tokens, full signed URLs or session IDs.
- Delivery-failed OTP records must be unusable.

## 8. Database rules

- PostgreSQL constraints are the final integrity layer.
- Use foreign keys, unique/partial indexes and checks where business rules require them.
- Normalize mobile/email before comparison.
- Multi-table writes representing one operation belong in a service transaction.
- SQL deployment files are immutable after execution; add a new corrective script.
- Database columns must reflect optionality. Do not make email required when the product says it is optional.
- Use public profile references such as `SAKxxxxxxx`; do not expose numeric IDs.

## 9. AWS media rules

- S3 bucket remains private.
- Direct S3 object access is denied.
- CloudFront is the only media delivery path.
- Generate signed URLs only after authorization and with short expiry.
- Store object keys, never permanent/signed URLs.
- Controllers must use the AWS media service layer, not the AWS SDK directly.
- Validate size, MIME type and decoded image content server-side.
- Generate safe object keys; do not trust uploaded filenames.
- Maintain required original/medium/thumbnail variants consistently.
- Enforce maximum photo count, approval, primary-photo and visibility rules in services and constraints where practical.

## 10. Frontend and CSS rules

- UI must be mobile-first and fully responsive.
- Use `public/assets/css/bootstrap.css`, `icons.css`, `app.css`, and `custom.css` only when required.
- Search Bootstrap utilities and `app.css` before adding CSS.
- Do not duplicate components or build a parallel admin design system.
- Use existing reusable form alerts, field errors, loaders, buttons, menus and cards.
- Use shared JavaScript components before creating page-specific logic.
- Load each page script once and make initialization safe against duplicates.
- Use accessible labels, error associations, focus behavior, modal names and touch targets.

## 11. Administrator rules

- `ADMIN` must not see/create administrators or super-admin-only KPIs.
- `SUPER_ADMIN` controls administrator and field-officer management.
- Role restrictions must be enforced in routes/filters/services, not only hidden in UI.
- Audit security-sensitive administrator actions.
- Audit payloads must exclude secrets and unnecessary personal data.

## 12. Prelaunch rules

- Prelaunch collection remains separate from live member records until explicit migration.
- Email is optional in prelaunch profiles.
- Field-officer verification and profile review rules must be enforced server-side.
- Production prelaunch route behavior must be an explicit release decision.
- Migration must preserve public references, normalize contacts, define initial account/password state and move approved media into the private AWS structure.

## 13. Error handling and logging

- Use result DTOs for expected business failures and exceptions for unexpected technical failures.
- Log technical context safely; show simple messages to users.
- Do not expose stack traces, SQL, filesystem paths or provider secrets in production.
- Avoid misleading success/failure when the database committed but a later provider/audit action failed.

## 14. Required testing

For every feature test:

- authorization and direct URL access;
- valid submission;
- empty and invalid input;
- duplicate and concurrent submission;
- transaction rollback;
- provider failure and retry behavior;
- account/status transitions;
- mobile and desktop UI;
- keyboard/accessibility behavior;
- no secret leakage in logs/responses;
- database constraints and SQL deployment impact.

## 15. Documentation and merge checklist

Before merge confirm:

- latest `development` was used;
- route names and filters are correct;
- controller is thin;
- validation is present and aligned with UI;
- service owns business logic/transactions;
- models own queries;
- provider calls occur outside transactions;
- SQL update/constraint is included when required;
- responsive UI uses existing assets;
- security and enumeration risks are addressed;
- relevant docs and decision log are updated;
- no unrelated files, secrets, placeholders or dead code remain.
