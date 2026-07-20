# Email Verification

## Purpose

Email verification confirms ownership of a member or administrator email address before the address is trusted for authentication or account recovery.

## Member email verification

The member flow should:

1. normalize the email address;
2. create a cryptographically random verification token;
3. store only a secure hash of the token;
4. set an expiry time and unused status;
5. send the raw token only inside the verification link;
6. validate the token, expiry and account state when the link is opened;
7. mark the email contact verified and record `verified_at`;
8. consume or revoke the verification record so it cannot be reused.

Email login must be rejected until the selected email contact is verified. A member may still log in with a separately verified mobile number.

## Administrator email verification

Administrator email verification is completed as part of invitation acceptance.

When a valid invitation is accepted, the service must perform the activation as one business operation:

- validate and lock the invitation;
- lock the administrator account;
- set the password hash;
- mark the email verified;
- record the email-verification timestamp;
- change the account status to `VERIFIED`;
- mark the invitation used;
- revoke other active invitations for that administrator;
- commit the transaction;
- write the corresponding audit event.

## Token security

- Use cryptographically secure random tokens.
- Store only token hashes.
- Use constant-time comparison where manual comparison is required.
- Apply an expiry time.
- Reject used, revoked and expired tokens.
- Never log raw verification tokens.
- Do not expose internal database identifiers in verification URLs.

## Resend behaviour

A resend operation should revoke or supersede older active verification records before creating a new one. Apply rate limits and safe user-facing responses so the endpoint does not disclose whether an email address belongs to an account.

## Failure handling

Business-state changes belong inside a transaction. Email delivery or audit-writing failures that occur after commit must be logged separately and must not falsely report that committed account changes were rolled back.

## Testing checklist

- valid token;
- invalid token;
- expired token;
- already-used token;
- revoked token;
- successful member verification;
- administrator invitation acceptance;
- duplicate concurrent requests;
- resend revokes older links;
- verified email login succeeds;
- unverified email login shows the correct message;
- raw tokens do not appear in logs.