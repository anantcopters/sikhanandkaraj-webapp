# Security

## Scope

Security applies to both the member application and the separate administrator application. Browser checks improve usability; server validation, authorization and database constraints remain authoritative.

## Authentication and authorization

- Keep member and administrator routes, filters, sessions and views separate.
- Regenerate the session ID after successful authentication.
- Allow access only after account status and contact-verification checks pass.
- Apply `WebAuthFilter`, `AdminAuthFilter` and `SuperAdminFilter` to the appropriate route groups.
- Re-check authorization inside sensitive services where business actions require stronger guarantees.
- Logout must clear the intended authentication context and rotate/destroy the relevant session state.

## Passwords

- Hash with `password_hash()` using `PASSWORD_DEFAULT`.
- Verify with `password_verify()`.
- Rehash with `password_needs_rehash()` when required.
- Never store, email or log a plain password.
- Apply server-side password length and quality rules to registration, invitation acceptance and password reset.

## OTPs and tokens

- Generate tokens using cryptographically secure randomness.
- Store only hashes of OTPs, invitation tokens and password-reset tokens.
- Set explicit expiry and one-time consumption rules.
- Limit verification attempts and resends.
- Lock the applicable database row during one-time token consumption.
- Revoke or supersede older invitations when a replacement is issued.
- Never include tokens in logs or audit payloads.

## Request protection

- Keep CI4 CSRF protection enabled for state-changing browser requests.
- Use POST/PUT/PATCH/DELETE semantics for mutations; do not mutate state through GET routes.
- Validate all request input with dedicated validation classes/rules.
- Escape output in views unless rendering explicitly trusted/sanitized HTML.
- Use CI4 models/query builder or parameterized SQL; never concatenate untrusted values into SQL.

## Session and cookie settings

Production must use secure session configuration:

- HTTPS only;
- secure cookies;
- HTTP-only cookies;
- an appropriate SameSite policy;
- environment-specific session names where needed;
- reasonable inactivity and absolute lifetimes;
- database or another production-suitable session driver.

## Personal data

Matrimonial profiles contain sensitive personal information. Collect only required fields, restrict Admin visibility, avoid placing personal data in logs, and define retention/deletion rules before adding document, image or identity-verification features.

## Audit logging

Audit meaningful administrator and authentication events, including login success/failure, logout, invitation actions, account status changes and privileged updates.

Audit records must not contain passwords, plain OTPs, tokens, full session IDs or unnecessary personal data. Restrict audit-log access to authorized administrators.

## Secrets

Keep database passwords, encryption keys, mail credentials, SMS credentials and cloud keys in environment-specific secret storage. Never commit production secrets, `.env` files or private keys.

## Files and media

When profile images or videos are added:

- validate MIME type and size server-side;
- generate safe object names rather than trusting uploaded filenames;
- store media in AWS S3, not under the public application directory;
- use private objects and time-limited access where content is not public;
- scan or quarantine uploads when appropriate;
- strip unnecessary metadata where privacy requires it.

## Error handling

Production responses must not expose stack traces, SQL, secrets or filesystem paths. Log a request/correlation identifier and return a safe message to the user.

## Review checklist

- routes have the correct filter;
- services enforce status and role rules;
- CSRF is active;
- output is escaped;
- tokens are hashed and expiring;
- attempts/resends are limited;
- sessions rotate on login;
- secrets are outside Git;
- audit events omit sensitive values;
- database constraints protect concurrent requests.