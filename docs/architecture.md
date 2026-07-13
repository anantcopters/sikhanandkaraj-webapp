# Architecture

## Purpose

The project uses a layered CodeIgniter 4 architecture. Each layer has one job, which makes code easier to test, review and extend.

## Request flow

```text
Browser
  ↓
Route
  ↓
Controller
  ↓
Validation
  ↓
Service
  ↓
Model
  ↓
PostgreSQL
  ↓
Service result
  ↓
Controller
  ↓
Redirect or View
```

## Layers

### Route

Routes live in `app/Config/Routes.php`.

A route maps a URL and HTTP method to a controller method. Use named routes so views and controllers do not hard-code URLs.

### Controller

Controllers live in `app/Controllers`.

A controller should:

- read request data;
- perform simple normalization;
- run validation;
- call one or more services;
- choose a redirect, view or response.

A controller should not contain SQL, transaction logic or multi-step business rules.

### Validation

Validation classes live in `app/Validation`.

They contain field labels, rules, messages and conditional rules. Server validation is the source of truth.

### Service

Services live in `app/Services`.

A service owns business decisions and transactions. It can call several models and return a result object to the controller.

### Model

Models live in `app/Models`.

A model represents one table and provides table-specific reads and writes. A model does not decide the overall feature flow.

### View

Views live in `app/Views`.

Views render escaped HTML. Shared visual parts belong in `Views/Components`; page files belong in `Views/Pages`.

### JavaScript

Reusable JavaScript belongs in `public/assets/js/components`. Page-specific behaviour belongs in `public/assets/js/pages`.

### CSS

Application-wide styles belong in `app.css`. Project or page-specific additions belong in `custom.css` only when an equivalent rule does not already exist.

## Dependency direction

```text
Controller → Service → Model → Database
```

A model must not call a controller. A view must not call a model. A service must not return HTML or redirects.

## Service results

Services should return small immutable result objects containing information such as:

- success or failure;
- action performed;
- affected identifiers;
- field name for a field error;
- safe user-facing message.

This keeps HTTP concerns in the controller and business concerns in the service.

## Transactions

Transactions belong in services because a service knows when a complete business operation starts and ends.

Use a transaction when several writes must succeed together. Roll back on every exception.

## Reference feature

The Register Free flow is the current reference implementation:

```text
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
RegisterFreeResult
  ↓
Redirect to OTP page
```

New features should follow this structure unless a different decision is recorded in `decision-log.md`.