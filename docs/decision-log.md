# Architecture Decision Log

## Existing foundations

- Layered CI4 flow: route → controller → validation → service → model/provider.
- Thin controllers and service-owned transactions.
- PostgreSQL constraints as final integrity protection.
- Separate member and administrator authentication contexts.
- Hashed OTPs/tokens, named routes, escaped views and shared UI assets.
- Private S3 media delivered only through signed CloudFront URLs.

## ADR-023: Registration is mobile-only

**Decision:** Public registration does not accept or persist email and creates one primary mobile contact.

**Reason:** Mobile OTP is the required activation mechanism and optional email should not block onboarding.

## ADR-024: Mobile OTP activates the member account

**Decision:** A user remains `PENDING` until mobile verification succeeds, then becomes `ACTIVE`.

**Reason:** Account state must represent the actual login eligibility rule.

## ADR-025: Offer password and OTP login

**Decision:** Members may authenticate with a verified contact plus password or by passwordless OTP to a verified mobile.

**Reason:** It provides recovery-friendly access while preserving one shared authenticated session contract.

## ADR-026: OTP-login initiation resists enumeration

**Decision:** Public OTP-login initiation does not reveal whether the number exists, is verified or belongs to an inactive account.

**Reason:** Eligibility-specific responses disclose account membership and status.

## ADR-027: Shared sensitive-page and session helpers

**Decision:** BaseController owns member-session establishment, authentication-state checks and no-cache headers used by authentication workflows.

**Reason:** Password and OTP paths must apply identical session-fixation and caching protections.

## ADR-028: Provider calls occur outside transactions

**Decision:** Commit OTP/database decisions before SMS/email/storage calls and record delivery failure explicitly.

**Reason:** Network calls must not hold database locks or leave ambiguous usable credentials.

## ADR-029: Project rules are versioned documentation

**Decision:** `docs/project-rules.md` is the mandatory coding and architecture checklist for generated and human-written changes.

**Reason:** Consistent implementation requires one reviewable source of non-negotiable rules.
