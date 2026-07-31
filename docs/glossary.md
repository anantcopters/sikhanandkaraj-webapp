# Glossary

## Account statuses

- `PENDING`: registration exists but required mobile verification is incomplete.
- `ACTIVE`: mobile is verified and member authentication is allowed.
- `SUSPENDED`: temporarily restricted.
- `DELETED`: logically disabled/deleted.

## Contact

A mobile or optional email row stored separately from the user. Each contact has type, normalized value, primary flag and independent verification state.

## OTP purposes

- `REGISTER`: verifies mobile and activates a pending account.
- `LOGIN`: passwordless authentication for an active account's verified mobile.
- `PASSWORD_RESET`: authorizes password replacement through a verified mobile.

## Delivery failed

A verification record whose external delivery failed. It is not usable and should not consume successful-delivery quota.

## Profile reference

A public identifier such as `SAK1234567`; internal database IDs must not be exposed as profile identifiers.

## Primary photo

The member photo selected as the main profile image. It remains subject to approval and visibility authorization.

## Signed URL

A short-lived CloudFront URL generated after authorization. It is not stored as the permanent media reference.

## Prelaunch profile

A temporary launch-preparation record collected separately from live member accounts and migrated only through an explicit approved process.

## Field officer

An operational person associated with prelaunch profile collection and verified through the prelaunch workflow.

## Result DTO

A small service return object representing expected success/failure without redirects, views or session behavior.

## Project rules

The mandatory coding, architecture, security, UI and review rules stored in `docs/project-rules.md`.
