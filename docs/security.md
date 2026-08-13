# Security

_Last reconciled with `development` HEAD `f2b16aa1a3ce7c53278b3b68d20524d3970fca05` on 2026-08-12._

## Trust boundaries

The application has separate member, administrator and SAK Volunteer authentication/authorization contexts. A valid session in one context never authorizes another.

Every resource-specific operation must verify ownership/role/viewer authorization server-side. Browser-supplied IDs, status values, hidden fields and route parameters are untrusted identifiers, not authorization decisions.

## Member authentication

- Regenerate the session ID after successful password or OTP authentication.
- Require an `ACTIVE` member for login/password recovery.
- Password login requires a verified stored contact and a valid password hash.
- OTP login delivers only to the verified mobile.
- Sensitive auth/OTP/password-reset responses use no-store/no-cache behavior.
- Member and administrator/volunteer session keys remain separate.

## Enumeration resistance

Public OTP-login initiation must not disclose whether a mobile is unknown, unverified, pending, suspended or deleted. Responses and workflow behavior must remain intentionally generic where account-state disclosure is not required.

## OTPs and one-time tokens

- Generate cryptographically secure values.
- Persist only hashes, never raw OTP/token values.
- Enforce purpose, expiry, attempts, resend cooldowns, quotas and one-time consumption.
- Use locking/atomic state transitions where concurrent verification is possible.
- Provider delivery failure must mark the record unusable (`DELIVERY_FAILED`).
- Raw OTPs/tokens must not appear in application logs, request logs, audit rows or sessions.

SAK Volunteer login OTPs are isolated in `field_officer_login_otps`; a partial unique index permits only one pending OTP per volunteer.

## CSRF and request handling

- Browser mutations use the configured CI4 session CSRF protection unless an explicitly reviewed exception exists.
- Controllers read expected fields only and pass validated data to services.
- Server validation is authoritative; client validation is never a security boundary.
- Query-builder/parameterized SQL is mandatory.
- Views escape user-controlled output using existing project conventions.

## Private member media

- S3 buckets remain private.
- Direct S3 object access is denied.
- CloudFront is the delivery path for live private member media.
- Signed URLs are created only after authorization and expire quickly.
- Database rows store object keys, never signed URLs.
- Validate configured size limits, MIME/type, actual decoded image content and dimensions.
- Generate server-controlled object keys/filenames and do not trust client filenames.
- Other-member thumbnails/full-profile media must pass viewer-aware visibility rules before signing.
- `INTERESTED_MEMBERS` media requires an interest relationship in either direction.

## Signing-key and secret handling

CloudFront private keys, AWS credentials, provider keys, encryption keys and passwords never belong in Git/source control.

The CloudFront private key may intentionally live outside the deployed repository. Its file permissions should remain least-privilege (for example `0640` with a dedicated service group). Every process that instantiates `CloudFrontService` must be able to read it, including authorized CLI jobs. Do **not** solve CLI access problems by making the private key world-readable.

## SAK Volunteer identity/documents

- Aadhaar/PAN/UPI uniqueness and format rules have database protection.
- Self-registered volunteers remain inactive until approved.
- Current Aadhaar/PAN/cancelled-cheque document references point to randomized filenames in private writable storage.
- Volunteer document download/view endpoints must verify administrator authorization and must never expose writable storage as a public directory.
- Avoid logging full Aadhaar/PAN/bank/UPI values where not operationally necessary; mask sensitive audit values when practical.

## Member-to-member interaction security

- Blocking another member is a relationship/privacy action, not account suspension.
- Self-block/self-interest/self-profile-view records are prohibited by DB constraints where applicable.
- Other-member profile assembly must authorize the viewer before signed media is generated.
- Duplicate/replayed Interest/shortlist/block requests must not create duplicate business rows.

## Administrator security

Role checks are enforced in routes/services, not by hiding UI controls. `SUPER_ADMIN`-only administrator/SAK Volunteer management must not become reachable to operational `ADMIN` accounts through direct URLs.

Administrative review displays public/business profile references rather than treating internal numeric IDs as user-facing identity.

## Logging and errors

- Use centralized application/error logging patterns.
- Do not expose internal exception details in production responses.
- Do not log secrets, raw OTPs, tokens, signing material, full sensitive provider URLs or unnecessary PII.
- Request/audit logging must sanitize sensitive fields.
- Operational logs should still retain safe correlation/context sufficient to diagnose provider, DB and media failures.

## Deployment security

- Apache document root points only to `<project>/public`.
- QA/production use HTTPS and secure/HttpOnly cookies.
- Secrets and signer keys are provisioned outside source control.
- Do not use `chmod 777` for `writable`, lock directories or secret files.
- Unknown/partially applied database state is a deployment stop condition, not a reason to guess and continue.