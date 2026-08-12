# Architecture Decision Log

_Last reconciled with `development` HEAD `f2b16aa1a3ce7c53278b3b68d20524d3970fca05` on 2026-08-12._

## Existing foundations

- Layered CI4 flow: route → controller → validation → service → model/provider.
- Thin controllers and service-owned transactions.
- PostgreSQL constraints as final integrity protection.
- Separate member, administrator and SAK Volunteer authentication contexts.
- Hashed OTPs/tokens, named routes, escaped views and shared UI assets.
- Private S3 media delivered only through authorized signed CloudFront URLs.
- Immutable baseline database plus numbered incremental SQL deployment.

## ADR-023: Registration is mobile-only

**Decision:** Public registration does not accept/persist email and creates one primary mobile contact.

**Reason:** Mobile OTP is the required activation mechanism and optional email should not block onboarding.

## ADR-024: Mobile OTP activates the member account

**Decision:** A user remains `PENDING` until mobile verification succeeds, then becomes `ACTIVE`.

**Reason:** Account state represents actual login eligibility.

## ADR-025: Offer password and OTP login

**Decision:** Members may authenticate with a verified contact + password or by passwordless OTP to a verified mobile.

**Reason:** Both paths converge on one authenticated-member session contract.

## ADR-026: OTP-login initiation resists enumeration

**Decision:** Public OTP initiation does not reveal existence/verification/inactive-account distinctions.

**Reason:** Eligibility-specific responses disclose account membership/status.

## ADR-027: Shared sensitive-page and session helpers

**Decision:** Shared controller helpers own member-session establishment, auth-state checks and no-cache behavior.

**Reason:** Password and OTP paths must apply identical session-fixation/cache protections.

## ADR-028: Provider calls occur outside transactions

**Decision:** Commit DB decisions before SMS/email/storage calls and record delivery failure explicitly.

**Reason:** Network calls must not hold DB locks or leave ambiguous usable credentials.

## ADR-029: Project rules are versioned documentation

**Decision:** `docs/project_rules.md`, together with QA rules, is the mandatory implementation/review contract.

**Reason:** Engineering and QA need one versioned source of non-negotiable rules.

## ADR-030: Matchmaking uses configurable structured-preference scoring

**Decision:** Match percentage uses structured Partner Preferences actually configured by the member. Compulsory preferences are hard eligibility conditions. Free-text Special Request is excluded from numeric scoring.

**Reason:** Matchmaking logic must remain isolated, configurable and deterministic.

## ADR-031: Member blocking is separate from administrator suspension

**Decision:** `member_blocks` controls member-to-member visibility/relationship behavior; administrator Block/Suspend controls account state.

**Reason:** Personal privacy decisions must not alter another member's account status.

## ADR-032: Other-member media requires viewer-aware authorization

**Decision:** Listings use authorized thumbnail variants; full profile uses authorized medium variants. `INTERESTED_MEMBERS` visibility requires an Interest relationship in either direction. Sign URLs only after viewer context is authorized.

**Reason:** Approval, ownership and viewer visibility are distinct authorization concerns.

## ADR-033: Standardize member profile presentation into four contexts

**Decision:** Dashboard thumbnail, Search/Matches card, Interest card and Full Profile are the only supported member-presentation contexts. Shared list-summary shaping lives in `MemberProfilePresentationService`.

**Reason:** Search/Matches/Interest/Dashboard must not drift into independent duplicated member-card implementations.

## ADR-034: Member→SAK Volunteer assignment is optional but immutable

**Decision:** Family Details may store a verified `field_officer_id` + code pair. The pair must be internally consistent and cannot be changed/removed once saved. PostgreSQL trigger/FK/check constraints enforce this in addition to service validation.

**Reason:** The relationship is historical verification/provenance data and must not be silently reassigned.

## ADR-035: SAK Volunteer is an independent portal/authentication context

**Decision:** Volunteer login uses its own OTP table/session/filter. Self-registration is review-controlled; pending/rejected self-registrations cannot become active.

**Reason:** Volunteer operational access has different identity, authorization and lifecycle rules from members/admins.

## ADR-036: Database deployment is baseline 000 plus immutable numbered SQL

**Decision:** `app/Database/sikhanandkaraj_db.sql` is immutable baseline 000. Deployed changes are numbered files under `database/` tracked in `deployment_sql_history`; CI4 migrations are not the deployment mechanism.

**Reason:** Fresh/existing deployments need deterministic, auditable SQL ordering without rewriting history.

## ADR-037: Unknown/partial database state is a deployment stop condition

**Decision:** Fresh runs 000 then increments; existing never reruns 000 and runs only missing increments; unknown/partial state stops deployment for reconciliation.

**Reason:** Guessing schema state can duplicate/destructively conflict with deployed constraints/data.

## ADR-038: Development/QA profile generation uses the real media/profile pipeline

**Decision:** The CLI-only development profile loader creates deterministic QA identities/profile sections/preferences and sends source photos through the same image-processing/S3 path as live member media. Import source is ledgered for rerun safety; failures perform compensating cleanup.

**Reason:** QA data should exercise real application behavior rather than create a parallel fake storage/model path.

## ADR-039: Signing keys remain outside source control and obey process-user permissions

**Decision:** CloudFront private signing material is provisioned outside the repository with least-privilege filesystem access. Every web/CLI process that instantiates the signer must be explicitly authorized to read it; do not make keys world-readable to fix CLI failures.

**Reason:** Web and CLI PHP may run as different Linux users even in the same environment.