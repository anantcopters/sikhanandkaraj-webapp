# Security

## Authentication

- Regenerate the session ID after successful password or OTP authentication.
- Keep member and administrator session keys separate.
- Disable caching on login, OTP, verification and password-reset pages.
- Require `ACTIVE` member status for login and password recovery.
- Require verified contact ownership before using a contact for authentication.

## Enumeration resistance

Public OTP-login initiation returns the same safe failure for unknown, unverified, pending, suspended or deleted accounts. Do not expose eligibility distinctions through messages, timing, JSON fields or HTTP behavior.

## OTPs and tokens

Generate cryptographically secure values and store only hashes. Enforce purpose, expiry, attempts, resend cooldowns, issue quotas and one-time consumption. Mark delivery failures unusable. Raw OTPs/tokens must never appear in logs, audit records or sessions.

## Media

S3 buckets remain private. Direct S3 access is denied. CloudFront signed URLs are created only after authorization and expire quickly. Validate file size, MIME and decoded image content; generate safe object keys; strip unnecessary metadata; never trust original filenames.

## Request/data protection

Use CSRF for mutations, expected-field input maps, dedicated validation, escaped view output, parameterized model queries and database constraints. Secrets belong in environment/role configuration, never source control.
