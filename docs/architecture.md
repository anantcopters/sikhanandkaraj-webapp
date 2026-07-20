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

Routes live in `app/Config/Routes.php`. Use named routes so views and controllers do not hard-code URLs.

### Controller

Controllers live in `app/Controllers`. A controller may read request data, normalize simple values, run validation, call services and choose a redirect, view or response. It must not contain SQL, transactions or multi-step business rules.

### Validation

Validation classes live in `app/Validation`. They contain reusable field rules, labels and messages. Server validation is authoritative.

### Service

Services live in `app/Services`. A service owns business rules and transaction boundaries. It may coordinate several models and return a small result object.

### Model

Models live in `app/Models`.