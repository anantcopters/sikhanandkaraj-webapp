# Coding Standards

This summary complements the mandatory [Project Rules](project-rules.md).

## PHP

Use `declare(strict_types=1);`, typed parameters/returns, exact PSR-4 namespace and filename casing, small methods and comments that explain business reasons.

## Layer boundaries

- Controllers: expected input, validation, service call, response.
- Services: business rules, transactions, orchestration and result DTOs.
- Models: one-table configuration and named queries.
- Views: escaped rendering and reusable components.
- Filters: route authentication/authorization gates.

No SQL in controllers/views. No redirects or HTML in services/models. No external provider calls while a database transaction is open.

## Input and output

Never pass the whole POST body to a model/service. Build an allowlisted input map, validate it and pass only validated values. Escape output with `esc()` unless content has an explicit trusted sanitization contract.

## Frontend

Use Bootstrap and `app.css` first. Reuse shared JavaScript components. Keep page scripts single-loaded, scoped and idempotent. Maintain mobile-first responsiveness and accessibility.

## Database and security

Use constraints for final integrity, transactions for atomic multi-table work, hashed passwords/OTPs/tokens, named public references and environment-managed secrets.
