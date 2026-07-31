# Email Verification

## Current role of email

Email is optional and is not collected during public registration. A member may add email later through an authenticated account-management flow.

## Member email rules

- Store email as a separate `EMAIL` row in `user_contacts`.
- Normalize by trimming and lower-casing before comparison.
- Email is not trusted until verification succeeds.
- An unverified email cannot be used for password login or password recovery.
- The dashboard must not show the retired registration-era email-activation banner.
- Mobile verification remains the account-activation requirement.

## Verification flow

```text
Authenticated member adds email
  → create random token
  → store token hash and expiry
  → queue verification email
  → member opens link
  → validate unused, unexpired token
  → mark contact verified
  → consume/revoke verification record
```

Raw tokens must appear only in the outgoing link. Never store or log them. Resend replaces or revokes older usable tokens and must be rate limited.

## Administrator email

Administrator email verification remains part of one-time invitation acceptance. Account activation, password creation, invitation consumption and email verification form one transactional business operation.
