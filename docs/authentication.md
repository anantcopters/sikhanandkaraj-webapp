# Authentication

## Member login choices

The login screen offers two separate paths:

1. **Password login** using a verified mobile number or verified email address.
2. **OTP login** using an active account's verified mobile number.

Both paths establish the same member session and redirect to the dashboard.

## Password login rules

- The identifier must resolve to a stored contact.
- The selected contact must be verified.
- The account must be `ACTIVE`.
- The password hash must verify.
- Successful authentication regenerates the session ID.
- Expected failures return safe user messages without exposing internal state.

## OTP login flow

```text
Login options
  → enter mobile
  → generic eligibility response
  → create hashed LOGIN OTP
  → commit database work
  → deliver SMS
  → verify OTP
  → consume OTP
  → establish member session
```

Public OTP initiation must not disclose whether a number exists, is unverified, belongs to a pending/suspended/deleted account, or is unknown. Resend cooldowns, issue quotas, expiry and attempt limits apply. Delivery-failed records are unusable.

## Password recovery

Password recovery accepts a registered mobile or verified email, but sends the OTP only to the verified mobile associated with an `ACTIVE` account. After OTP verification, the member sets a new password and returns to login.

## Shared sensitive-page behavior

Authentication, OTP verification and password-reset pages must set no-store/no-cache response headers. Shared BaseController helpers own member-session establishment, authentication-state checks and sensitive-page cache prevention.

## Administrator authentication

Administrator authentication remains separate. Only `VERIFIED` administrators may log in. `PENDING` invitation accounts and `SUSPENDED` accounts are blocked. Administrator and member session keys must not overlap.
