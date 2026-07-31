# Registration

## Current registration contract

Public registration collects only:

- profile-created-for relationship;
- full name;
- gender when required by the selected relationship;
- Indian mobile country code/number;
- password and confirmation.

Email is intentionally excluded from the controller input, validation payload, service and registration database writes. Posting an unexpected email field must have no effect.

## Account lifecycle

```text
Submit registration
  → validate expected fields
  → normalize mobile
  → create/update PENDING user
  → maintain one primary MOBILE contact
  → create hashed REGISTER OTP
  → deliver OTP
  → verify OTP
  → mark mobile verified
  → set user ACTIVE
  → redirect to member application
```

## Duplicate/resume rules

- A verified mobile is already owned and registration is rejected.
- An unverified mobile may resume only when its user is still `PENDING`.
- An unverified contact attached to `ACTIVE`, `SUSPENDED`, `DELETED` or unknown state cannot overwrite that account.
- A resumed pending registration may update the latest name, relationship, gender and password hash.
- PostgreSQL uniqueness remains the final concurrency protection.

## Database writes

Registration creates or updates the user and mobile contact in a service transaction. OTP issuance uses the shared OTP rules after the member/contact decision is committed, preventing external SMS delivery from holding the transaction open.

## Email after registration

Email may be added later through an authenticated account-management flow. Any added email remains optional and cannot be used for login or recovery until verified.
