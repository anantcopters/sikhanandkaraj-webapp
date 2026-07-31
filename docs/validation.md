# Validation

Validation has three layers:

```text
Client constraints → server validation → database constraints
```

Server validation is authoritative. Controllers must read only expected fields, run dedicated rules and pass only `getValidated()` output to services.

## Current examples

- Registration validation excludes email completely.
- Gender requirements depend on profile-created-for rules and must match client behavior.
- OTP forms normalize only the approved digit fields.
- Mobile values are normalized before service and uniqueness decisions.
- Prelaunch email is optional; required rules must not be reintroduced in UI, validation or database constraints.

## Error ownership

Use a field error when the user can fix one field. Use a form alert for provider failure, invalid workflow state or unexpected technical failure. Restore old values only where doing so is safe; never restore passwords or OTPs.

## Security rules

Validation must not reveal account existence through public OTP-login initiation. Unexpected POST keys must never reach services through mass assignment.
