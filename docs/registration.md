# Registration

## Purpose

The Register Free flow creates or resumes a pending matrimonial profile while protecting verified mobile numbers from duplicate registration.

## Main flow

```text
Registration form
  ↓
RegistrationController
  ↓
RegisterFreeValidation
  ↓
RegisterFreeService
  ↓
UserModel
UserContactModel
ContactVerificationModel
  ↓
PostgreSQL transaction
  ↓
Store pending identifiers in session
  ↓
Redirect to OTP verification
```

## Business rules

- Normalize the mobile number before lookup or storage.
- A verified mobile number cannot start another registration.
- An existing unverified pending mobile number may resume or update its pending registration.
- A new mobile number creates the user and contact records.
- Email and mobile contacts are stored independently.
- The profile relationship is stored and may determine gender when the business rule is unambiguous.
- Public profile references use the `SAK` plus seven-digit format.

## Transaction boundary

The following writes form one registration operation and must succeed or fail together:

1. create or update the user;
2. create or update the mobile contact;
3. create or update the email contact where supplied;
4. create the OTP verification record;
5. commit the transaction.

Rollback on every exception. Controllers must not own the transaction.

## Validation

Every field requires matching client and server validation. Server validation is authoritative. Restore submitted values after redirects and show field-specific messages below the relevant field.

## OTP preparation

- Store only the OTP hash.
- Record purpose, expiry, attempts, resend count and status.
- Never expose an OTP in logs or database records.
- Session identifiers used between registration and verification must be validated before use.

## Concurrency

Friendly duplicate checks belong in the service, but PostgreSQL unique constraints are the final protection against simultaneous submissions using the same normalized mobile or public profile reference.

## Testing checklist

- empty and invalid form;
- new registration;
- verified mobile duplicate;
- pending unverified mobile resume;
- duplicate email handling;
- transaction rollback;
- OTP record creation;
- old-value restoration;
- mobile and desktop layout;
- keyboard and accessibility behaviour.