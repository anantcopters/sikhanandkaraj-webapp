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

## ADR-030: Matchmaking uses configurable structured-preference scoring

**Decision:** Member matchmaking calculates a percentage using only structured Partner Preferences actually configured by the member. The minimum accepted percentage is environment-configurable. A preference marked compulsory is a hard eligibility condition rather than only a weighted score. Free-text Special Request content is excluded from numerical scoring.

**Reason:** Matching logic is product intellectual property and must remain isolated, configurable, deterministic and independently evolvable without coupling controllers, views or database structure to one scoring formula.

## ADR-031: Member-to-member blocking is separate from administrator suspension

**Decision:** Member-to-member blocking uses `member_blocks` and affects relationship visibility in both directions. Administrator Block Member continues to control the member account status and is not reused for personal member blocks.

**Reason:** One member hiding another is a relationship/privacy decision and must not suspend or otherwise change the other member's account.

## ADR-032: Other-member media requires viewer-aware authorization

**Decision:** Another-member match listings use authorized thumbnail variants and profile-detail pages use authorized medium variants. `INTERESTED_MEMBERS` media requires an interest relationship in either direction. Another-member profile assembly suppresses owner-context signed URL generation until viewer authorization has completed.

**Reason:** Approval, ownership and viewer visibility are separate authorization concerns. Signed CloudFront URLs must be generated only after the applicable viewer context is known.
