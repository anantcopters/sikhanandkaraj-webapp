# Glossary

## Account

The login-level identity of a user in the system.

## Profile

The matrimonial information shown for matching and discovery.

## Profile reference number

A public identifier such as `SAK1234567`. It is different from the internal database ID.

## Profile created for

The relationship between the person filling the form and the person represented by the profile, for example Self, Son, Daughter, Brother or Sister.

## Pending registration

A registration record created before required contact verification is complete.

## Verified contact

An email address or mobile number whose ownership has been confirmed.

## Primary contact

The main email or mobile contact currently used for the account.

## Normalized contact value

A contact value converted to a consistent comparison format.

Examples:

```text
Mobile: +919876543210
Email: user@example.com
```

## OTP

One-time password used for short-lived verification. The database stores its hash, expiry, attempt count and status.

## Contact verification

A record representing an OTP or verification workflow for one contact and purpose.

## Controller

The HTTP layer that receives a request, validates it, calls a service and returns a response.

## Validation class

A reusable PHP class containing server-side field rules and messages.

## Service

A class containing business rules, transactions and coordination between models.

## Model

A CI4 class that represents a database table and performs table-specific queries.

## Result DTO

A small immutable object returned by a service. DTO means Data Transfer Object.

## View

A PHP template responsible for rendering HTML.

## Component

A reusable UI or JavaScript unit used by more than one page.

Examples:

- FormAlert;
- FieldError;
- Choices initializer;
- form validator.

## Page script

JavaScript loaded only for one page or feature, such as `home.js`.

## Flashdata

Session data intended to survive one redirect and then expire, commonly used for validation errors and alert messages.

## Old input

Previously submitted form data restored after a validation redirect using CI4 `old()`.

## Field error

An error shown directly below the field the user can correct.

## Form-level alert

A general success, warning or error message that is not owned by one field.

## Client validation

Validation performed in the browser for fast feedback.

## Server validation

Authoritative validation performed by CodeIgniter after form submission.

## Database constraint

A database rule such as unique, foreign key, check or not-null that protects data integrity.

## Transaction

A group of database writes that either all succeed or all roll back.

## Schema file

SQL describing the complete database structure for a fresh installation.

## Update file

Incremental SQL applied to an existing environment.

## Seed file

SQL that inserts master or reference data.

## Choices.js

The JavaScript library used to enhance selected `<select>` elements. In this project it is enabled with `data-choice`.

## `app.css`

The universal application stylesheet containing reusable component styles.

## `custom.css`

The stylesheet for missing Sikh Anand Karaj-specific or page-specific rules. It should not duplicate `app.css`.

## Case A registration

The mobile already exists and is verified. Registration is rejected with a field error.

## Case B registration

The mobile exists, is unverified and belongs to a pending account. The pending registration is updated and a new OTP is created.

## Case C registration

The mobile does not exist. A new pending user, contacts and verification record are created.