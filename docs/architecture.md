# Architecture

## Core request flow

```text
Browser
  → Named route and filter
  → Thin controller
  → Dedicated validation rules
  → Application service
  → CI4 model / provider adapter
  → PostgreSQL, S3, SMS or email provider
  → Result DTO
  → Redirect, JSON response or escaped view
```

## Responsibilities

- **Routes** define URL, HTTP method, name and access filter.
- **Filters** protect member, administrator and super-administrator contexts.
- **Controllers** read expected input, validate, call services and return responses.
- **Validation classes** define reusable authoritative server rules.
- **Services** own business rules, transaction boundaries, concurrency decisions and external-work orchestration.
- **Models** own table configuration and table-specific queries.
- **Views** render escaped data and shared components; they do not query databases.
- **Result DTOs** represent expected success and business failure without HTTP concerns.

## Authentication architecture

Member authentication supports password and mobile-OTP entry points. Both establish the same member session through shared BaseController logic, including session-ID regeneration. Sensitive authentication, OTP and password-reset pages disable browser/intermediate caching.

Administrator authentication is isolated by separate routes, sessions and filters. `SUPER_ADMIN` is required for administrator and field-officer management.

## Transaction and external-call rule

A service may use a database transaction for one atomic database decision. SMS, email and AWS calls must occur after commit or through a retryable queue/outbox. A failed delivery must be recorded using a non-usable status such as `DELIVERY_FAILED`; it must not leave a usable OTP whose delivery is unknown.

## Media architecture

```text
Authorized controller
  → AwsMediaService
  → MediaPathService
  → S3Service / CloudFrontService
  → private S3 object + signed CloudFront URL
```

The database stores media object keys and metadata. It never stores signed URLs. Controllers do not call the AWS SDK directly.

## Profile architecture

Profile sections are separate controller/service/model operations but share master-data lookup, completion calculation and profile-preview aggregation. Sikh and Religious details are not part of the displayed journey or completion calculation. Community/sub-community are owned by Family Details.

## Prelaunch architecture

Production can route public member entry points to a separate prelaunch collection workflow. Prelaunch data remains operationally separate until an explicit migration imports approved records and uploads approved media into the live private-media structure.
