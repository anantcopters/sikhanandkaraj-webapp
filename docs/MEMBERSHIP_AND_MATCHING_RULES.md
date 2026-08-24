# Membership and Matching Rules

## 1. Purpose

This document is the product and architecture source of truth for Free and Paid membership, profile access, membership usage, matching, ranking, and search in Sikhanandkaraj.

The goals are to:

- provide useful discovery to Free members without exposing paid functionality;
- provide clear value to Go, Plus, and Pro members;
- protect member privacy, especially female member contact/profile access;
- keep safety features available regardless of plan;
- keep interests unrestricted;
- make profile and Live Introduction usage fair and auditable;
- keep matching relevant rather than allowing payment alone to dominate results;
- provide a fast, scalable, maintainable search and ranking architecture;
- centralize entitlement rules so UI and controllers do not independently invent membership logic;
- allow Superadmin to configure Match Score weightage without code changes.

Payment gateway, payment reconciliation, refunds, and chargebacks are outside the scope of this document. For membership activation rules below, a Paid membership is assumed to start only after payment has been successfully completed.

---

## 2. Membership Plans

The current paid plans are defined by the pricing page and must ultimately use the same plan configuration as the entitlement system.

| Feature / Limit | Free | Go | Plus | Pro |
|---|---:|---:|---:|---:|
| Duration | Unlimited | 3 months | 6 months | 12 months |
| Total unique profile views | 0 | 50 | 100 | 300 |
| Daily new unique profile views | 0 | 5 | 10 | 20 |
| Unique Live Introduction views | 0 | 10 | 30 | 80 |
| Interests sent | Unlimited | Unlimited | Unlimited | Unlimited |
| Interests received | Unlimited | Unlimited | Unlimited | Unlimited |
| Dedicated Match Manager | No | No | No | Yes |

The values displayed on the pricing page and the values enforced by the application must come from one authoritative plan definition. Plan limits must not be independently hard-coded in multiple views/services.

---

## 3. Product Principle

Membership is divided into two broad capabilities:

- **Free:** Discovery + Intent + Safety
- **Paid:** Discovery + Intent + Safety + Access + Trust Features

Free membership must remain useful enough for a member to discover relevant candidates, send/receive interests, and decide whether the platform has value.

Paid membership unlocks deeper access, advanced discovery, trust features, and controlled consumption of full profiles and Live Introductions.

Safety functionality must not require payment.

---

## 4. Feature Entitlement Matrix

| Feature | Free | Go | Plus | Pro |
|---|---|---|---|---|
| Create profile | Yes | Yes | Yes | Yes |
| Edit profile | Yes | Yes | Yes | Yes |
| Set partner preferences | Yes | Yes | Yes | Yes |
| Upload/manage photos | Yes | Yes | Yes | Yes |
| Verify mobile | Yes | Yes | Yes | Yes |
| Verify email | Yes | Yes | Yes | Yes |
| Basic Search | Yes | Yes | Yes | Yes |
| Advanced Search | Locked | Yes | Yes | Yes |
| Header/member navigation | Yes | Yes | Yes | Yes |
| Dashboard match sections | Yes | Yes | Yes | Yes |
| ProfileThumbnail | Yes | Yes | Yes | Yes |
| ProfileCard | Yes | Yes | Yes | Yes |
| ProfileInterestCard | Yes | Yes | Yes | Yes |
| Search by Profile ID | ProfileCard | Subject to full-profile policy | Subject to full-profile policy | Subject to full-profile policy |
| Send Interest | Unlimited | Unlimited | Unlimited | Unlimited |
| Receive Interest | Unlimited | Unlimited | Unlimited | Unlimited |
| Accept/Decline Interest | Yes | Yes | Yes | Yes |
| Full Profile View | Locked | Yes* | Yes* | Yes* |
| Aadhaar | Locked | Yes | Yes | Yes |
| Create Live Introduction | Locked | Yes | Yes | Yes |
| Watch Live Introduction | Locked | Limited | Limited | Limited |
| Report Profile | Yes | Yes | Yes | Yes |
| Block Profile | Yes | Yes | Yes | Yes |
| Shortlist Profile | Locked | Yes | Yes | Yes |
| Membership Usage | Not applicable | Yes | Yes | Yes |
| Dedicated Match Manager | No | No | No | Yes |

`*` Full-profile access remains subject to gender/privacy/interest rules and membership usage limits.

---

## 5. Locked Feature UX

Paid-only features should generally remain discoverable to Free members instead of disappearing from navigation.

Examples include:

- Advanced Search
- Aadhaar
- Live Introduction
- Shortlist
- Full Profile View

When a Free member reaches a non-accessible screen or action, display a consistent locked-feature message and an action to view/purchase membership plans.

Example concept:

> This feature is available with a paid membership. Upgrade your plan to continue.

The UI lock is only presentation. Server-side entitlement checks are mandatory for every protected endpoint/action.

Report and Block are safety features and must never be presented as paid-only functionality.

---

## 6. Interests

Interest sending and receiving are unlimited for Free, Go, Plus, and Pro.

A member may:

- send interests;
- receive interests;
- view interest cards;
- accept interests;
- decline interests.

A Free member must never be required to purchase a plan merely to accept or decline an interest.

Interest state and membership state are independent. An accepted interest remains accepted if either member's paid membership expires.

Membership entitlement is re-evaluated whenever a protected action is attempted.

---

## 7. Report, Block, and Shortlist

### Report

Available to Free and Paid members.

Reporting is a platform safety function, not a premium feature.

Report Profile should be accessible from relevant profile interaction surfaces, including ProfileCard and ProfileInterestCard, using the existing report workflow and validation.

### Block

Available to Free and Paid members.

Block Profile should also be accessible from ProfileCard and ProfileInterestCard using the existing blocking workflow.

Blocking takes effect immediately. A blocked relationship must override membership entitlement and remove access/search visibility according to existing block rules.

### Shortlist

Paid only.

Free members may see the action as locked where appropriate, but cannot create a shortlist entry.

---

## 8. Full Profile Access Policy

Full-profile access requires an active Paid membership plus all applicable privacy/access rules.

### 8.1 Female viewer viewing a male profile

A female member with an active Paid membership may view a male member's full profile, subject to normal profile visibility, block, moderation, and usage rules.

No accepted interest is required.

### 8.2 Male viewer viewing a female profile

A male member may view a female member's full profile only when:

1. the male has an active Paid membership;
2. the female has accepted the male member's interest;
3. neither member blocks the other;
4. the target profile remains eligible/visible;
5. applicable usage limits permit access, unless the profile was already consumed during the same membership.

### 8.3 Membership expiry after accepted interest

If the female has accepted the male's interest but the male's membership expires:

- the interest remains accepted;
- the male immediately loses paid full-profile access;
- purchasing/activating another eligible Paid membership restores access, subject to current rules and the new membership's usage accounting.

### 8.4 Accepted interest while male is Free

If a female accepts an interest from a Free male member, the interest becomes accepted but the male's full-profile access remains locked.

If the male later activates a Paid membership, access becomes available automatically, subject to current eligibility/privacy/usage rules.

---

## 9. Female Contact Privacy

When a viewer is entitled to see contact information on a female profile:

1. display the parents' mobile number when available;
2. if the parents' mobile number is unavailable, display the female member's mobile number;
3. do not display both numbers merely because both exist.

Contact information must only be returned after server-side profile-access policy succeeds.

Membership alone must never bypass the female profile-access rule.

---

## 10. Photo Visibility

Photo access is determined by the photo/profile visibility policy, not merely by whether the viewer is Paid.

For Free viewers, photos shown in ProfileThumbnail, ProfileCard, and ProfileInterestCard are visible only when the candidate's applicable photo visibility is `PUBLIC`.

The application should evaluate photo access through a centralized policy such as the conceptual responsibility:

```text
canViewerSeePhoto(viewer, candidate, relationship)
```

Do not duplicate visibility conditions separately in every card/view.

Existing supported visibility states and relationship rules must remain authoritative.

---

## 11. Profile View Consumption

Profile limits represent unique candidates accessed, not page loads.

### 11.1 First view

The first successful full-profile access of Candidate B by Viewer A during a particular membership consumes one profile view.

Conceptually the consumption identity is:

```text
membership_id + viewer_member_id + viewed_member_id
```

### 11.2 Repeat view

Reopening the same candidate during the same membership does not consume another profile view.

Repeat access remains allowed even when the viewer has subsequently reached the daily or total limit, provided all other current access rules still succeed.

### 11.3 Daily limit

Daily limits count new unique candidate profiles first consumed on that day.

Example for Go:

```text
A, B, C, D, E opened for the first time today
Daily usage: 5 / 5
Membership usage: 5 / 50
```

Opening A again is allowed.

Opening a new Candidate F is blocked until the next daily period.

### 11.4 Total membership limit

Once total unique-profile allowance is exhausted:

- previously consumed profiles remain accessible while the membership remains active and access policy still succeeds;
- new profiles cannot be consumed;
- the member may upgrade when an eligible upgrade exists.

### 11.5 New membership

Profile consumption belongs to a specific membership period.

When a new membership becomes active, previously viewed candidates may be consumed again against the new membership's allowance when first opened under that membership.

---

## 12. Live Introduction Consumption

Live Introduction consumption follows the same fairness principle as Profile Views.

The allowance represents unique members whose Live Introduction has been accessed, not video play count.

Conceptually:

```text
membership_id + viewer_member_id + video_owner_member_id
```

Rules:

- first successful access consumes one allowance;
- replaying the same member's Live Introduction does not consume another allowance;
- replacing/re-uploading the candidate's Live Introduction does not consume another allowance for the same viewer within the same membership;
- all existing Live Introduction visibility/privacy rules still apply;
- membership expiry immediately removes Paid access;
- a new membership starts a new usage period.

Profile View and Live Introduction View are separate usage counters.

---

## 13. Membership Usage Screen

Paid members should receive an Account Settings menu item named **Membership Usage** rather than an internal term such as Txn.

The page should display at minimum:

### Profile Views

- plan allowance;
- total used;
- total remaining;
- today's new-profile usage;
- today's daily limit;
- date-wise usage history;
- viewed member name using existing masking rules;
- viewed Member ID.

### Live Introductions

- plan allowance;
- used;
- remaining;
- date-wise usage history;
- viewed member name using existing masking rules;
- viewed Member ID.

Usage records must be auditable and must not be inferred only from page analytics/log files.

---

## 14. Membership Lifecycle

A member may have many historical memberships but only one active membership at a time.

Membership history must never be overwritten when a plan expires or is replaced.

Recommended lifecycle statuses:

```text
ACTIVE
EXPIRED
REPLACED
CANCELLED
```

Payment/refund-specific statuses may be introduced later with the payment implementation.

### 14.1 Activation

For the current scope, activation occurs after successful payment is confirmed by the future payment flow.

Until payment integration exists, membership activation should be treated as an administrative/system capability and must not pretend that payment processing exists.

### 14.2 Upgrade

While a plan is active, only upgrades are allowed.

Plan commercial hierarchy:

```text
FREE < GO < PLUS < PRO
```

Allowed while active:

```text
GO -> PLUS
GO -> PRO
PLUS -> PRO
```

Not allowed while the higher plan remains active:

```text
PRO -> PLUS
PRO -> GO
PLUS -> GO
```

The new upgraded plan starts from its activation/purchase date.

The previous active membership becomes `REPLACED`.

Unused duration and usage are not silently carried into the new plan unless a future commercial policy explicitly introduces such behaviour.

Before an upgrade, the member should be clearly informed that the current membership will end and the new membership will begin immediately.

### 14.3 Expiry

An entitlement is active only when the membership is logically active at the current time.

Conceptually:

```text
status = ACTIVE
AND starts_at <= current_time
AND expires_at > current_time
```

Authorization must use this rule directly.

The application must not depend on a cron having already changed `ACTIVE` to `EXPIRED`.

### 14.4 Expiry cron

A daily housekeeping job should mark elapsed active memberships as `EXPIRED` and ensure any denormalized/current membership state is synchronized.

The cron is housekeeping, not the authorization source of truth.

If the cron fails, an expired membership must still fail entitlement checks because `expires_at` has passed.

Use a precise `expires_at` value so membership behaviour does not depend on ambiguous calendar-day boundaries.

---

## 15. Membership History and Auditability

Maintain immutable historical membership records containing, as applicable:

- member;
- plan;
- activation/start time;
- expiry time;
- status;
- amount at purchase time;
- plan limits at purchase time or an immutable plan-version reference;
- payment reference when payment integration is introduced;
- replacement/cancellation metadata;
- created/updated timestamps;
- administrative action audit information when manually changed.

A member record may contain a denormalized current membership reference for performance, but historical membership remains authoritative for usage/audit purposes.

---

## 16. Match Eligibility: Mandatory First Stage

A candidate must first pass hard eligibility rules before Match Score is considered.

An ineligible candidate must not become visible merely because the candidate is Paid, highly verified, or has a high profile-completion score.

Eligibility uses the existing matching/search business rules and applicable filters, including relevant concepts such as:

- opposite/allowed gender according to current application rules;
- age and mandatory matching constraints;
- requested search filters;
- member/profile active status;
- profile visibility;
- block state;
- moderation/report exclusion rules already enforced by the application;
- location/community filters for the relevant dashboard/search section;
- any other existing mandatory match conditions.

The rule is:

```text
Eligibility first -> Match Score second -> Sorting third
```

Hard eligibility must never be converted into a commercial Match Score weight.

---

## 17. Match Score

Every eligible candidate receives a Match Score used for ordering results.

The score combines these agreed dimensions:

1. Partner Preference / Relevance
2. Profile Completion
3. Approved Photos
4. Trust / Verification
5. Commercial / Membership Priority

The score should be normalized to a predictable range, preferably 0-100.

Conceptually:

```text
match_score =
    preference_component
  + profile_completion_component
  + approved_photo_component
  + trust_component
  + commercial_component
```

The score must not be hard-coded in controllers or SQL fragments scattered across the application.

---

## 18. Superadmin Match Score Configuration

Match Score weightage must be configurable by Superadmin.

Superadmin should be able to configure the relative weight of:

- Partner Preference / Relevance
- Profile Completion
- Approved Photos
- Trust / Verification
- Commercial / Membership

Recommended initial default configuration:

| Component | Initial Weight |
|---|---:|
| Partner Preference / Relevance | 55% |
| Profile Completion | 10% |
| Approved Photos | 10% |
| Trust / Verification | 15% |
| Commercial / Membership | 10% |
| **Total** | **100%** |

These are initial product defaults, not permanently hard-coded business rules.

### Configuration rules

- total active weight must equal 100%;
- negative values are invalid;
- configuration changes require server-side validation;
- Superadmin changes must be audited;
- previous configurations should remain historically traceable;
- a malformed/missing configuration must fall back to a safe application default;
- changing weights should not require deployment/code modification.

The commercial component should improve exposure without overwhelming candidate relevance.

---

## 19. Partner Preference / Relevance Component

Partner preference is viewer-specific and should remain the strongest Match Score component.

The existing preference matching logic should remain the source for what constitutes a matched preference point.

The normalized preference score can conceptually be derived from:

```text
matched_preference_points / applicable_preference_points
```

and normalized before applying the configured weight.

Missing/not-applicable preference fields must not incorrectly punish a candidate. The denominator should represent applicable comparable preference points according to the existing matching rules.

This component is viewer-specific and should normally be calculated during the candidate query/ranking pipeline rather than persisted for every possible member pair.

---

## 20. Profile Completion Component

Profile completion should reward serious, complete profiles.

Use the application's authoritative profile-completion calculation rather than inventing a second completion formula specifically for ranking.

Normalize the existing completion percentage into the configured Match Score weight.

A Paid membership must not compensate completely for a substantially incomplete profile.

---

## 21. Approved Photos Component

Approved-photo count contributes to Match Score.

Only approved photos count.

Do not let an unlimited number of photos create an unlimited ranking advantage. Normalize/cap the photo contribution at a sensible product threshold.

The threshold should be configurable or centrally defined rather than duplicated in search queries.

The member-level approved-photo count should be cheap to retrieve during search.

---

## 22. Trust / Verification Component

Trust is based on the existing four verification dimensions:

- Mobile
- Email
- Aadhaar
- Live Introduction

These credentials do not necessarily have equal trust value.

Recommended internal trust weighting:

| Verification | Trust Points |
|---|---:|
| Mobile | 1 |
| Email | 1 |
| Aadhaar | 3 |
| Live Introduction | 3 |
| **Maximum** | **8** |

The UI may continue to display the four existing verification badges independently.

For ranking, normalize the trust points into the configured Trust component weight.

Trust weighting should be centrally defined/configurable so it is not duplicated across queries.

---

## 23. Commercial / Membership Component

Commercial priority is one Match Score input, not an absolute sorting override.

Recommended plan priority:

```text
FREE = 0
GO   = 1
PLUS = 2
PRO  = 3
```

Normalize this priority into the configured Commercial component weight.

This means Paid members receive increased visibility, and higher plans receive greater commercial benefit, but a poorly matched Pro profile should not automatically outrank a highly relevant and trustworthy Free profile.

This protects matchmaking quality and long-term member engagement.

---

## 24. Match Score Tie-Breaking

Primary result ordering:

```text
match_score DESC
```

For equal/near-equal scores, use deterministic secondary ordering.

Recommended tie-breaking:

1. higher preference/relevance score;
2. higher trust score;
3. higher profile completion;
4. higher approved-photo count;
5. newer member/profile timestamp available in the current schema;
6. stable member identifier as the final deterministic key.

Do not depend on undefined database row order.

---

## 25. New Profiles

The product definition of **New Profile** is a profile introduced within the last 30 days.

The application currently does not have a dedicated profile publish date.

Therefore, initial implementation must use the most appropriate existing member/profile creation/activation timestamp already present in the current schema and architecture.

Do not introduce a new publish-date field merely for this rule unless implementation analysis demonstrates that the existing timestamps cannot correctly represent the product requirement.

The chosen timestamp must be documented in implementation code/comments and used consistently.

---

## 26. Dashboard Match Sections

Existing dashboard/match sections remain eligibility/filter contexts. Match Score determines ordering after candidates have passed the section's criteria.

### All Matches

Use the normal match eligibility rules, then order by Match Score.

### Living in Same State

First restrict to eligible candidates satisfying the same-state condition, then order by Match Score.

### Living in Same City

First restrict to eligible candidates satisfying the same-city condition, then order by Match Score.

### Same Community

First restrict to eligible candidates satisfying the existing community condition, then order by Match Score.

### Profiles With Photos

First restrict to eligible candidates with the required approved-photo condition, then order by Match Score.

### New Profiles

First restrict to eligible candidates falling within the agreed 30-day New Profile window, then order by Match Score.

The same ranking philosophy should be reused rather than maintaining unrelated sorting logic per dashboard section.

---

## 27. Search Sorting

Search results should follow this pipeline:

```text
Request Filters
      ↓
Hard Eligibility
      ↓
Candidate Set
      ↓
Viewer-specific Preference/Relevance
      +
Precomputed Member-level Signals
      ↓
Match Score
      ↓
ORDER BY Match Score DESC
      ↓
Deterministic Tie-breakers
      ↓
Pagination
```

Basic and Advanced Search should share the same candidate/ranking engine. Advanced Search adds additional filters/criteria; it should not create a completely separate ranking implementation.

---

## 28. Fast and Scalable Search Architecture

Search must be optimized around reducing the candidate set before expensive viewer-specific calculations.

### 28.1 Do not persist every member pair

Do not precompute/store a Match Score row for every possible viewer/candidate pair.

At 100,000 members, possible pair combinations become extremely large and create expensive recalculation when:

- a member edits preferences;
- a candidate edits profile details;
- Superadmin changes Match Score weights;
- verification changes;
- membership changes;
- photos are approved/rejected.

### 28.2 Precompute member-level signals

Maintain or efficiently expose cheap member-level ranking/search signals such as:

- searchable/active flag;
- gender and core filter fields;
- current country/state/city identifiers;
- community identifiers used by matching;
- profile completion percentage;
- approved-photo count / has-approved-photo;
- verification/trust score or its source flags;
- active membership plan priority;
- applicable creation/activation timestamp for freshness.

These values do not depend on the current viewer and can be indexed/cached/denormalized where justified.

### 28.3 Calculate viewer-specific preference relevance only for candidates

Apply indexed hard filters first.

Only after reducing the candidate population should the query calculate viewer-specific preference matching and Match Score.

### 28.4 Database indexes

PostgreSQL indexes should support actual high-frequency filters rather than indexing every column independently.

Implementation should review query plans and add indexes around real access patterns such as:

- searchable/active + gender;
- country/state/city filters;
- community filters;
- relevant age/date-of-birth filtering;
- profile creation/activation timestamp used for New Profiles;
- active membership lookup;
- unique usage identities;
- block/report exclusion lookups;
- interest relationship lookups required for access policy.

Use partial indexes where PostgreSQL can benefit from conditions such as active/searchable records.

### 28.5 Avoid N+1 queries

Search result construction must not execute separate queries per candidate for:

- membership;
- verification;
- approved photos;
- interest state;
- block state;
- preference count.

Retrieve/batch/join the required data as part of the candidate query or through bounded bulk loading.

### 28.6 Pagination

Do not load the full matching population into PHP and sort it in application memory.

Filtering, scoring, ordering, and pagination should happen as close to PostgreSQL as practical.

For early/moderate scale, standard pagination may remain acceptable. At larger result depths, prefer deterministic keyset/cursor pagination based on Match Score plus tie-breaker keys rather than increasingly expensive large offsets.

### 28.7 Cache configuration, not personalized result sets by default

Superadmin scoring configuration and static plan definitions are excellent cache candidates.

Personalized search-result caching should only be introduced when measurements justify it because preferences, blocks, interests, membership, verification, and profile changes make invalidation complex.

### 28.8 Measure before introducing an external search engine

PostgreSQL should remain the primary search/ranking engine while it meets performance requirements.

Do not introduce Elasticsearch/OpenSearch or another search platform solely because ranking exists.

Move to a dedicated search engine only when measured database/search scale demonstrates a real need.

---

## 29. Match Score Recalculation Strategy

Superadmin weight changes must not require recalculating and persisting every viewer-candidate pair.

Recommended design:

- persist/derive member-level signals independently;
- cache the active scoring configuration;
- calculate viewer-specific preference relevance for the filtered candidate set;
- combine normalized components into Match Score at query time;
- optionally materialize/denormalize only expensive member-level aggregates that measurements prove useful.

When a member changes profile data, update only affected member-level signals.

When preferences change, no global candidate recalculation should be required.

When Superadmin changes weights, the new configuration should affect subsequent searches without a full member-pair rebuild.

---

## 30. Centralized Entitlement Architecture

Membership logic must be centralized.

Views/controllers must not independently decide `if paid` and invent plan rules.

Conceptual responsibilities:

```text
MembershipService
    getActiveMembership()
    getPlan()
    canUpgradeTo()
    expireMemberships()

MembershipEntitlementService
    canUseAdvancedSearch()
    canViewProfile()
    canViewPhoto()
    canAddAadhaar()
    canCreateLiveIntroduction()
    canWatchLiveIntroduction()
    canShortlist()
    canReport()
    canBlock()
    canSendInterest()

MembershipUsageService
    consumeProfileView()
    consumeLiveIntroductionView()
    hasConsumedProfile()
    hasConsumedLiveIntroduction()
    getDailyProfileUsage()
    getMembershipProfileUsage()
    getLiveIntroductionUsage()

ProfileAccessPolicy
    canViewerAccessCandidate()
    canViewerSeeContact()
    canViewerSeePhoto()
```

Exact class names should follow the existing CodeIgniter/project architecture when implemented; these names describe responsibilities, not a requirement to invent an incompatible structure.

Controllers enforce authorization.

Views receive entitlement state and render available/locked actions.

Direct URL/API requests must not bypass the same policy.

---

## 31. Usage Concurrency and Atomicity

Profile and Live Introduction consumption must be safe when multiple requests occur at the same time.

Use database-level uniqueness and transactional/atomic operations so double-clicks, multiple browser tabs, retries, or concurrent requests cannot consume the same candidate multiple times.

The usage flow should conceptually:

1. validate active membership and access policy;
2. check whether this viewer/candidate has already been consumed in the current membership;
3. if already consumed, allow access without increment;
4. if new, atomically verify daily/total allowance;
5. insert one unique usage record;
6. return access.

Do not implement this as an unsafe `SELECT count -> PHP check -> INSERT` sequence without concurrency protection.

---

## 32. Membership Expiry and Session Safety

Do not cache a simple `is_paid = true` in the login session and trust it for the lifetime of the session.

Paid entitlement must reflect current membership expiry and replacement state.

A member whose plan expires while logged in must lose protected access on the next protected request without requiring logout/login.

A short-lived request-level/cache optimization may be used if it cannot extend entitlement beyond expiry.

---

## 33. Pricing Page and Plan Source of Truth

The current pricing UI contains plan names, prices, durations, profile limits, daily limits, and Live Introduction limits.

Once membership enforcement is implemented, those values must be supplied from the same authoritative plan configuration used by the membership services.

This prevents situations where the pricing page promises one allowance while the server enforces another.

Plan configuration should support at least:

- code (`GO`, `PLUS`, `PRO`);
- display name;
- commercial priority;
- price;
- duration;
- total profile-view limit;
- daily profile-view limit;
- Live Introduction limit;
- dedicated manager flag;
- active/available flag;
- display ordering.

Historical memberships must retain the purchased entitlement snapshot or immutable plan-version relationship so future plan edits do not retroactively change an already purchased membership.

---

## 34. Important Corner Cases

| Scenario | Required Behaviour |
|---|---|
| Paid plan expires while logged in | Protected access stops on next request |
| Expiry cron has not run | `expires_at` still prevents Paid access |
| Accepted interest exists but male plan expires | Interest remains accepted; female full profile becomes locked for him |
| Female accepts Free male's interest | Accepted state retained; full profile remains locked until Paid |
| Male later becomes Paid | Access becomes available if all current rules pass |
| Candidate blocks viewer after profile was consumed | Access is removed immediately |
| Candidate becomes inactive/non-searchable | Remove from applicable discovery/access according to current policy |
| Viewer reaches daily profile limit | Existing consumed profiles remain accessible; new profiles blocked |
| Viewer reaches total profile limit | Existing consumed profiles remain accessible; new profiles blocked |
| Viewer reopens same profile | No additional usage |
| Viewer replays same Live Introduction | No additional usage |
| Candidate replaces Live Introduction | No additional usage for same owner/viewer/membership |
| New membership starts | Usage starts fresh for that membership |
| Active Go member buys Plus | Go becomes replaced; Plus starts immediately |
| Active Plus member attempts Go | Downgrade blocked |
| Active Pro member attempts Go/Plus | Downgrade blocked |
| Free member receives interest | Can view card and Accept/Decline |
| Free member tries Advanced Search | Show locked/upgrade state; server denies advanced search |
| Free member directly opens full-profile URL | Server denies and renders/redirects to locked upgrade flow |
| Free member tries Aadhaar/Live Intro direct endpoint | Server denies even if UI was bypassed |
| Free member needs to report/block | Allowed |
| Free member tries Shortlist | Locked/denied |
| Two tabs open same new profile simultaneously | One usage consumption only |
| Match Score weights change | Subsequent ranking uses new active configuration; no pairwise rebuild |
| Candidate purchases higher plan | Commercial component reflects active plan without changing eligibility |
| Paid candidate has weak relevance | Commercial boost cannot bypass eligibility or completely dominate relevance |

Payment, refund, chargeback, duplicate gateway callbacks, and payment reconciliation corner cases will be specified separately when payment gateway implementation begins.

---

## 35. Security Rules

1. Every Paid feature must be protected server-side.
2. Hiding/disabling a button is not authorization.
3. Full-profile contact details must not be fetched/exposed before access policy succeeds.
4. Signed/private media access must enforce current viewer entitlement and visibility rules.
5. Block state overrides Paid membership.
6. Membership expiry is checked using time/status, not only cron-updated account type.
7. Usage limits are enforced transactionally.
8. Superadmin scoring changes require authorization, validation, and audit history.
9. Member-visible usage history must expose only information the member is permitted to see.
10. Plan changes must not retroactively mutate historical purchased entitlements.

---

## 36. Recommended Implementation Sequence

Implement in controlled phases rather than changing every screen simultaneously.

### Phase 1 - Membership foundation

- authoritative plan definition;
- membership/history storage;
- active membership resolution;
- upgrade/downgrade rules;
- expiry logic and housekeeping cron;
- plan snapshot/version strategy.

### Phase 2 - Entitlement layer

- centralized membership entitlement service;
- profile access policy;
- female access/privacy rules;
- locked-feature response pattern;
- server-side guards.

### Phase 3 - Usage accounting

- Profile View usage;
- Live Introduction usage;
- daily and total caps;
- concurrency-safe unique consumption;
- Membership Usage screen/history.

### Phase 4 - UI feature enforcement

- Advanced Search locking;
- Aadhaar locking;
- Live Introduction locking;
- full-profile locking;
- Shortlist locking;
- Report/Block on ProfileCard;
- Report/Block on ProfileInterestCard;
- plan/current membership display and upgrade rules.

### Phase 5 - Match Score foundation

- Superadmin weight configuration;
- normalized component calculations;
- trust score;
- approved-photo signal;
- profile completion signal;
- commercial signal;
- preference relevance integration.

### Phase 6 - Search/ranking optimization

- candidate filtering pipeline;
- Match Score ordering;
- dashboard match-section reuse;
- Basic/Advanced Search reuse;
- database indexes;
- N+1 removal;
- query-plan/performance testing;
- deterministic pagination.

### Phase 7 - Production hardening

- entitlement tests;
- membership expiry tests;
- concurrency/usage tests;
- privacy tests;
- ranking regression tests;
- Superadmin configuration tests;
- performance/load tests;
- audit/log review.

---

## 37. Acceptance Principles

The implementation is correct only when all of the following remain true:

- Free members can meaningfully discover candidates and exchange interests.
- Safety actions Report and Block remain available to Free members.
- Paid functionality cannot be bypassed with direct URLs/requests.
- Female full-profile/contact privacy is preserved.
- Interests remain unlimited across all plans.
- Profile and Live Introduction limits count unique candidates, not repeat page/video plays.
- Daily limits do not prevent reopening previously consumed profiles.
- Only one membership can be active at a time.
- Active-plan downgrades are prevented.
- Expired membership access stops even if the cron fails.
- Membership history remains auditable.
- Hard match eligibility always runs before Match Score.
- Match Score is configurable by Superadmin.
- Partner preference/relevance remains the dominant default ranking signal.
- Commercial priority improves exposure but does not bypass eligibility.
- Search does not depend on storing every viewer/candidate pair.
- Search filtering/scoring/pagination remains database-oriented and avoids N+1 behaviour.
- Pricing and entitlement limits use one source of truth.

---

## 38. Deferred Scope

The following items require separate specifications when implemented:

- payment gateway integration;
- payment success/failure/callback handling;
- payment reconciliation;
- refunds;
- chargebacks;
- invoices/GST/payment records;
- renewal offers/discounts/coupons;
- Match Manager operational workflow for Pro;
- any future automatic renewal policy.

These deferred items must integrate with, not replace, the membership lifecycle and entitlement principles defined here.

---

## 39. Source of Truth

This document is the agreed product/technical baseline for Membership and Matching behaviour.

When implementation begins:

1. read the latest `development` branch;
2. reuse the existing project architecture, models, services, validation, UI classes, modals, routes, and security patterns;
3. do not create parallel implementations where an existing flow can be extended;
4. check every implementation change against this document;
5. update this document whenever an agreed business rule changes.
