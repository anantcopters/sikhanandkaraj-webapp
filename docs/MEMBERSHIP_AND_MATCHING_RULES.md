# Membership and Matching Rules

## 1. Purpose

This document is the product and architecture source of truth for Free and Paid membership, profile access, membership usage, matching, ranking, search, privacy, and membership enforcement in Sikhanandkaraj.

The goals are to:

- provide useful discovery to Free members without exposing paid functionality;
- provide clear value to Go, Plus, and Pro members;
- protect member privacy, especially female member contact/profile access;
- keep safety features available regardless of plan;
- keep interests unrestricted;
- make profile and Live Introduction usage fair, concurrency-safe, and auditable;
- keep matching relevant rather than allowing payment alone to dominate results;
- provide a fast, scalable, maintainable search and ranking architecture;
- centralize entitlement rules so UI and controllers do not independently invent membership logic;
- allow Superadmin to configure Match Score weightage without code changes;
- ensure pricing promises and enforced membership limits use one authoritative plan definition.

Payment gateway, payment reconciliation, refunds, and chargebacks are outside the current scope. A Paid membership is assumed to start only after successful payment confirmation or an authorized administrative/system activation during the pre-payment-integration phase.

---

## 2. Membership Plans

The authoritative plans are:

| Feature / Limit | Free | Go | Plus | Pro |
|---|---:|---:|---:|---:|
| Duration | Unlimited | 3 months | 6 months | 12 months |
| Total unique Full Profile views | 0 | 50 | 100 | 300 |
| Daily new unique Full Profile views | 0 | 5 | 10 | 20 |
| Unique Live Introduction views | 0 | 10 | 30 | 80 |
| Interests sent | Unlimited | Unlimited | Unlimited | Unlimited |
| Interests received | Unlimited | Unlimited | Unlimited | Unlimited |
| Dedicated Match Manager | No | No | No | Yes |

### 2.1 Meaning of a Profile View allowance

A profile allowance means a **unique Full Profile View**. It does not mean a verified profile.

A consumed candidate may be:

- Free or Paid;
- verified or unverified;
- partially verified or fully verified.

Verification status and membership status of the candidate do not determine whether the Full Profile View counts. A view is consumed when the viewer successfully receives first-time Full Profile access to that candidate during the current membership, subject to the rules in this document.

Therefore pricing copy must not describe the 50/100/300 allowance as `Verified Profiles`. Pricing must use terminology such as **Full Profile Views**, **View up to 50 profiles**, or equivalent wording that accurately represents the entitlement.

The pricing UI and server enforcement must use the same authoritative plan definition. Plan values must not be independently hard-coded in views and services.

---

## 3. Membership State and Removal of Temporary `is_paid`

The existing `users.is_paid` field was a temporary QA flag and is not part of the production membership architecture.

It must be removed as part of the membership implementation after all existing references are migrated.

Production authorization must never use:

```text
users.is_paid
session.is_paid
if ($isPaid)
```

as the source of truth.

Membership state must be resolved from the authoritative membership records and current time.

The commercial hierarchy is:

```text
FREE < GO < PLUS < PRO
```

`FREE` is the absence of a currently active Paid membership, not a historical membership record that needs to be created for every member.

Application code should ask for a capability/entitlement rather than repeatedly asking whether a member is Paid.

---

## 4. Product Principle

Membership is divided into two broad capabilities:

- **Free:** Discovery + Intent + Safety
- **Paid:** Discovery + Intent + Safety + Access + Trust Features

Free membership must remain useful enough for a member to discover relevant candidates, send/receive interests, and decide whether the platform has value.

Paid membership unlocks deeper access, advanced discovery, trust features, and controlled consumption of Full Profiles and Live Introductions.

Safety functionality must never require payment.

---

## 5. Feature Entitlement Matrix

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
| Search by Profile ID | ProfileCard | Subject to Full Profile policy | Subject to Full Profile policy | Subject to Full Profile policy |
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

`*` Full Profile access remains subject to gender/interest, block, moderation, eligibility, and membership usage rules.

---

## 6. Locked Feature UX

Paid-only features should generally remain discoverable to Free members instead of disappearing from navigation.

Examples include:

- Advanced Search;
- Aadhaar;
- Live Introduction;
- Shortlist;
- Full Profile View.

When a Free member reaches a non-accessible screen or action, display a consistent locked-feature message and an action to view/purchase membership plans.

Example concept:

> This feature is available with a paid membership. Upgrade your plan to continue.

A relationship-specific lock must explain the relationship requirement rather than incorrectly asking an already-Paid member to upgrade. For example, a Paid male attempting to view a female Full Profile before accepted interest should receive the accepted-interest requirement.

The UI lock is presentation only. Server-side entitlement checks are mandatory for every protected endpoint/action.

Report and Block are safety features and must never be presented as paid-only functionality.

---

## 7. Interests

Interest sending and receiving are unlimited for Free, Go, Plus, and Pro.

A member may:

- send interests;
- receive interests;
- view interest cards;
- accept interests;
- decline interests.

A Free member must never be required to purchase a plan merely to accept or decline an interest.

Interest state and membership state are independent. An accepted interest remains accepted if either member's Paid membership expires.

Membership entitlement is re-evaluated whenever a protected action is attempted.

---

## 8. Report, Block, and Shortlist

### 8.1 Report

Report is available to Free and Paid members.

Reporting is a platform safety function, not a premium feature.

Report Profile should be accessible from relevant interaction surfaces, including ProfileCard and ProfileInterestCard, using the existing report workflow and validation.

### 8.2 Block

Block is available to Free and Paid members.

Block Profile should be accessible from ProfileCard and ProfileInterestCard using the existing blocking workflow.

Blocking takes effect immediately. A blocked relationship overrides membership entitlement and removes access/search visibility according to the existing block rules.

### 8.3 Shortlist

Shortlist is Paid only.

Free members may see the action as locked where appropriate, but cannot create a shortlist entry. The server must deny direct Free shortlist requests.

Existing report/block endpoints that currently assume Full Profile as the return destination must support a safe allowlisted origin/return context so Free members can report/block from cards without being redirected into a Full Profile they cannot access.

---

## 9. Full Profile Access Policy

Full Profile access to another member requires an active Paid membership plus all applicable access rules.

There is no member-configurable profile visibility entitlement. Legacy profile-visibility functionality and its `ALL_MEMBERS` / `PAID_MEMBERS_ONLY` semantics are irrelevant under this policy and must be removed from the member product flow and authorization logic.

A candidate's Full Profile accessibility is determined by platform policy, not by a profile-visibility preference selected by the candidate.

### 9.1 Female viewer viewing a male profile

A female member with an active Paid membership may view a male member's Full Profile subject to block, moderation, target eligibility/status, and usage rules.

No accepted interest is required.

### 9.2 Male viewer viewing a female profile

A male member may view a female member's Full Profile only when:

1. the male has an active Paid membership;
2. the female has accepted the male member's interest;
3. neither member blocks the other;
4. the target profile remains eligible/active and is not excluded by moderation rules;
5. applicable usage limits permit access, unless the profile was already consumed during the same membership.

### 9.3 Membership expiry after accepted interest

If the female has accepted the male's interest but the male's membership expires:

- the interest remains accepted;
- the male immediately loses Paid Full Profile access;
- activating another eligible Paid membership restores access subject to current rules and the new membership's usage accounting.

### 9.4 Accepted interest while male is Free

If a female accepts an interest from a Free male member, the interest becomes accepted but the male's Full Profile access remains locked.

If the male later activates a Paid membership, access becomes available automatically if all current rules pass.

### 9.5 Authorization order

Sensitive Full Profile data must not be loaded before access authorization succeeds.

Conceptually:

```text
resolve target
-> block/moderation/eligibility
-> resolve active membership
-> membership entitlement
-> gender/interest access rule
-> existing-consumption check
-> daily/total allowance for a new consumption
-> atomic consumption
-> sensitive profile/contact/media loading
```

---

## 10. Female Contact Privacy

When a viewer is entitled to see contact information on a female profile:

1. display the parents' mobile number when available;
2. if the parents' mobile number is unavailable, display the female member's mobile number;
3. do not display both numbers merely because both exist.

Contact information must only be fetched/returned after server-side Full Profile access policy succeeds.

Membership alone must never bypass the female gender/interest access rule.

---

## 11. Photo Visibility

Photo access is determined by the existing photo visibility policy plus viewer entitlement where applicable. It is not determined by a removed profile-visibility setting.

For Free viewers, photos shown in ProfileThumbnail, ProfileCard, and ProfileInterestCard are visible only when the candidate's applicable photo visibility permits that viewer under the existing photo policy.

The application must evaluate photo access through a centralized policy/service. Do not duplicate photo conditions separately in every card/view.

Existing supported **photo visibility** states and relationship rules remain authoritative unless separately changed. This section does not preserve the removed member **profile visibility** feature.

---

## 12. Profile View Consumption

Profile limits represent unique candidates whose Full Profile was successfully accessed, not page loads and not verified profiles.

### 12.1 First view

The first successful Full Profile access of Candidate B by Viewer A during a particular membership consumes one profile view.

Consumption identity:

```text
membership_id + viewer_member_id + viewed_member_id
```

The database must enforce uniqueness for this identity.

### 12.2 Repeat view

Reopening the same candidate during the same membership does not consume another profile view.

Repeat access remains allowed even after the viewer reaches the daily or total limit, provided all other current access rules still succeed.

### 12.3 Daily limit

Daily limits count new unique candidate profiles first consumed on that day.

Example for Go:

```text
A, B, C, D, E opened for the first time today
Daily usage: 5 / 5
Membership usage: 5 / 50
```

Opening A again is allowed. Opening a new Candidate F is blocked until the next daily period.

### 12.4 Total membership limit

Once the total unique-profile allowance is exhausted:

- previously consumed profiles remain accessible while the membership remains active and access policy still succeeds;
- new profiles cannot be consumed;
- the member may upgrade when an eligible upgrade exists.

### 12.5 New membership

Profile consumption belongs to a specific membership period.

When a new membership becomes active, previously viewed candidates may be consumed again against the new membership's allowance when first opened under that membership.

---

## 13. Live Introduction Consumption

Live Introduction consumption follows the same unique-consumption principle as Full Profile Views.

Consumption identity:

```text
membership_id + viewer_member_id + video_owner_member_id
```

Rules:

- first successful access consumes one allowance;
- replaying the same member's Live Introduction does not consume another allowance;
- replacing/re-uploading the candidate's Live Introduction does not consume another allowance for the same owner/viewer/membership;
- all existing Live Introduction visibility/privacy rules still apply;
- membership expiry immediately removes Paid access;
- a new membership starts a new usage period;
- Full Profile View and Live Introduction View are separate counters.

Database uniqueness and transactional enforcement are mandatory.

---

## 14. Membership Usage Screen

Paid members receive an Account Settings menu item named **Membership Usage**.

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

Usage records must be auditable and must not be inferred only from analytics or application logs.

---

## 15. Membership Lifecycle

A member may have many historical memberships but only one active membership at a time.

Membership history must never be overwritten when a plan expires or is replaced.

Lifecycle statuses:

```text
ACTIVE
EXPIRED
REPLACED
CANCELLED
```

### 15.1 Activation

For the current scope, activation occurs after successful payment is confirmed by the future payment flow. Until payment integration exists, membership activation is an authorized administrative/system capability and must not pretend that payment processing exists.

### 15.2 Upgrade

While a plan is active, only upgrades are allowed.

Allowed:

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

The upgraded plan starts from its activation/purchase time. The previous active membership becomes `REPLACED`.

Unused duration and usage do not carry forward unless a future commercial policy explicitly introduces that behaviour.

Before upgrade, clearly inform the member that the current membership ends and the new membership begins immediately.

### 15.3 Expiry

An entitlement is active only when:

```text
status = ACTIVE
AND starts_at <= current_time
AND expires_at > current_time
```

Authorization must evaluate this directly. It must not depend on a cron or login-session flag.

### 15.4 Expiry cron

A daily housekeeping job should mark elapsed active memberships as `EXPIRED` and synchronize any denormalized current-membership state.

The cron is housekeeping, not authorization. If it fails, `expires_at` still prevents access.

---

## 16. Membership History and Auditability

Maintain historical membership records containing, as applicable:

- member;
- plan;
- activation/start time;
- expiry time;
- status;
- amount at purchase time;
- plan limits at purchase time or immutable plan-version reference;
- payment reference when introduced;
- replacement/cancellation metadata;
- created/updated timestamps;
- administrative action audit information when manually changed.

Historical membership remains authoritative for usage/audit purposes.

---

## 17. Match Eligibility: Mandatory First Stage

A candidate must pass hard eligibility rules before Match Score is considered.

An ineligible candidate must not become visible merely because the candidate is Paid, highly verified, or highly complete.

Eligibility includes applicable existing business rules such as:

- opposite/allowed gender according to current application rules;
- age and mandatory matching constraints;
- requested search filters;
- member/profile active and searchable status;
- block state;
- moderation/report exclusion rules;
- location/community filters for the relevant section;
- other existing mandatory matching conditions.

**Profile visibility is not an eligibility input and must be removed from the matching/search authorization flow.**

The rule is:

```text
Eligibility first -> Match Score second -> Sorting third
```

Hard eligibility must never be converted into a commercial Match Score weight.

---

## 18. Match Score

Every eligible candidate receives a Match Score used for ordering results.

The score combines:

1. Partner Preference / Relevance;
2. Profile Completion;
3. Approved Photos;
4. Trust / Verification;
5. Commercial / Membership Priority.

Normalize the score to a predictable range, preferably 0-100.

The score must be centralized and must not be hard-coded independently in controllers or scattered SQL fragments.

---

## 19. Superadmin Match Score Configuration

Superadmin configures the relative weight of:

- Partner Preference / Relevance;
- Profile Completion;
- Approved Photos;
- Trust / Verification;
- Commercial / Membership.

Initial defaults:

| Component | Initial Weight |
|---|---:|
| Partner Preference / Relevance | 55% |
| Profile Completion | 10% |
| Approved Photos | 10% |
| Trust / Verification | 15% |
| Commercial / Membership | 10% |
| **Total** | **100%** |

Configuration rules:

- total active weight must equal 100%;
- negative values are invalid;
- configuration changes require server-side validation;
- changes must be audited and historically traceable;
- malformed/missing configuration falls back to a safe application default;
- changing weights must not require deployment;
- commercial priority must not bypass eligibility or overwhelm relevance.

Reasonable server-side ranges should be applied so a valid 100% total cannot accidentally turn commercial priority into the dominant matching rule. Exact ranges should be finalized during implementation and documented with the configuration model.

---

## 20. Match Score Components

### 20.1 Partner Preference / Relevance

Use the existing preference matching logic as the source of matched preference points. Normalize applicable matched points into the configured weight. Missing/not-applicable fields must not incorrectly punish a candidate.

This component is viewer-specific and should be calculated in the candidate/ranking pipeline rather than persisted for every possible member pair.

### 20.2 Profile Completion

Use the application's authoritative profile-completion calculation. Do not create a second completion formula for ranking.

### 20.3 Approved Photos

Only approved photos count. Cap/normalize photo contribution at a centrally defined sensible threshold so unlimited photos do not create unlimited ranking advantage.

### 20.4 Trust / Verification

Trust uses the existing four verification dimensions:

| Verification | Trust Points |
|---|---:|
| Mobile | 1 |
| Email | 1 |
| Aadhaar | 3 |
| Live Introduction | 3 |
| **Maximum** | **8** |

Normalize trust points into the configured Trust component weight.

### 20.5 Commercial / Membership

Recommended plan priority:

```text
FREE = 0
GO   = 1
PLUS = 2
PRO  = 3
```

Commercial priority is a score input, not an absolute sorting override. A poorly matched Pro profile must not automatically outrank a highly relevant and trustworthy Free profile.

---

## 21. Match Score Tie-Breaking

Primary ordering:

```text
match_score DESC
```

Recommended deterministic tie-breakers:

1. higher preference/relevance score;
2. higher trust score;
3. higher profile completion;
4. higher approved-photo count;
5. newer applicable member/profile timestamp;
6. stable member identifier.

Do not depend on undefined database row order.

---

## 22. New Profiles

A **New Profile** is a profile introduced within the last 30 days.

The application currently has no dedicated profile publish date. Initial implementation should use the most appropriate existing member/profile creation/activation timestamp already present in the schema and architecture.

Do not introduce a publish-date field unless implementation analysis demonstrates that existing timestamps cannot represent the requirement correctly.

---

## 23. Dashboard Match Sections and Search Sorting

Existing dashboard/match sections remain eligibility/filter contexts. Match Score determines ordering only after section criteria pass.

Examples:

- All Matches -> eligible candidates -> Match Score;
- Same State -> state filter -> Match Score;
- Same City -> city filter -> Match Score;
- Same Community -> community filter -> Match Score;
- Profiles With Photos -> approved-photo condition -> Match Score;
- New Profiles -> 30-day condition -> Match Score.

Basic and Advanced Search share the same candidate/ranking engine. Advanced Search adds filters; it must not create a parallel ranking implementation.

Search pipeline:

```text
Request Filters
-> Hard Eligibility
-> Candidate Set
-> Viewer Preference/Relevance + Member-level Signals
-> Match Score
-> Deterministic Ordering
-> Pagination
```

Free members attempting Advanced Search through a direct request must be denied with the Paid-feature response; the request must not be silently downgraded to Basic Search.

---

## 24. Fast and Scalable Search Architecture

Search must reduce the candidate set before expensive viewer-specific calculations.

### 24.1 Do not persist every member pair

Do not store a Match Score row for every viewer/candidate pair.

### 24.2 Precompute or efficiently expose member-level signals

Examples:

- searchable/active flag;
- gender/core filter fields;
- country/state/city identifiers;
- community identifiers;
- profile completion percentage;
- approved-photo count/flag;
- verification/trust source flags or score;
- active membership plan priority;
- applicable freshness timestamp.

### 24.3 Viewer-specific relevance

Apply indexed hard filters first, then calculate viewer-specific preference matching for the reduced candidate set.

### 24.4 Database indexes

Review PostgreSQL query plans and index actual high-frequency access patterns, including:

- searchable/active + gender;
- country/state/city;
- community;
- age/date-of-birth;
- New Profile timestamp;
- active membership lookup;
- unique usage identities;
- block/report exclusions;
- interest relationships required by Full Profile access.

### 24.5 Avoid N+1

Search result construction must not execute separate queries per candidate for membership, verification, photos, interest state, block state, or preference count.

### 24.6 Pagination

Do not load the full matching population into PHP and sort in memory. Filtering, scoring, ordering, and pagination should happen as close to PostgreSQL as practical.

### 24.7 Caching

Cache static plan/scoring configuration where useful. Do not cache personalized result sets by default without a measured need and clear invalidation strategy.

### 24.8 External search engine

PostgreSQL remains the primary search/ranking engine while performance requirements are met. Introduce a dedicated search engine only when measured scale demonstrates a real need.

---

## 25. Centralized Entitlement Architecture

Membership logic must be centralized.

Views/controllers must not independently implement `if paid` plan rules.

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
```

Photo authorization remains centralized in the existing photo-policy/service architecture rather than being duplicated into membership views.

Exact class names must follow the existing CodeIgniter/project architecture when implemented.

Controllers enforce authorization. Views receive resolved capability state and render available/locked actions. Direct requests must pass the same policies.

---

## 26. Usage Concurrency and Atomicity

All previously identified concurrency risks are mandatory current-feature requirements, not deferred hardening.

Profile and Live Introduction consumption must be safe under double-clicks, multiple tabs, retries, and concurrent requests.

Required flow:

1. validate active membership and access policy;
2. check whether viewer/candidate is already consumed in the current membership;
3. if consumed, allow without increment;
4. if new, atomically verify daily/total allowance;
5. insert exactly one unique usage record;
6. return access.

Do not implement an unsafe `SELECT count -> PHP check -> INSERT` sequence.

Database unique constraints and transactions/appropriate locking must prevent:

- duplicate consumption of the same candidate;
- two simultaneous new candidates both passing the final daily slot;
- two simultaneous requests exceeding the total membership limit.

---

## 27. Membership Expiry and Session Safety

Do not cache Paid state in the login session as authorization.

Paid entitlement must reflect current membership status, expiry, and replacement state on protected requests.

A member whose plan expires while logged in loses protected access on the next protected request without logout/login.

Any cache optimization must never extend entitlement beyond `expires_at`.

---

## 28. Pricing Page and Plan Source of Truth

The current pricing implementation contains plan data in the view. This must be replaced by the same authoritative plan definition used by membership services.

Plan configuration supports at least:

- code (`GO`, `PLUS`, `PRO`);
- display name;
- commercial priority;
- price;
- duration;
- total Full Profile View limit;
- daily new Full Profile View limit;
- Live Introduction limit;
- Dedicated Match Manager flag;
- active/available flag;
- display ordering.

Historical memberships retain a purchased entitlement snapshot or immutable plan-version relationship so future pricing edits do not alter existing memberships.

Pricing wording must accurately describe profile allowances as Full Profile Views and must not imply that viewed candidates must be verified.

---

## 29. Removed Profile Visibility Feature

The existing member Profile Visibility feature is obsolete under the finalized membership/access policy.

Implementation must remove it from all applicable layers, including as found in the latest development branch:

- Account Settings navigation/UI;
- profile visibility forms/views;
- Account Settings controller handling;
- routes dedicated only to member profile visibility;
- service/model logic used only for profile visibility;
- Full Profile authorization checks based on `ALL_MEMBERS` / `PAID_MEMBERS_ONLY`;
- matching/search eligibility conditions based on profile visibility;
- obsolete validation/constants/helpers that have no remaining consumer.

Database cleanup must follow the project's normal incremental SQL deployment rules. Do not drop a column/table until code references have been migrated and the change has been verified against all member/admin flows.

This removal does **not** remove photo visibility. Photo privacy remains a separate feature governed by the existing photo authorization flow.

---

## 30. Mandatory Risk Controls

All risks identified during the development-agent impact analysis are accepted as valid and must be addressed in the current membership/matching feature.

### 30.1 Temporary `is_paid` dependency

Remove `users.is_paid` and migrate all authorization/presentation references to authoritative membership/entitlement resolution.

### 30.2 UI-only locks

Every Paid feature must have server-side enforcement. UI locks are never authorization.

### 30.3 Profile usage race condition

Use database uniqueness and transactional consumption so the same profile cannot be consumed twice by concurrent requests.

### 30.4 Quota race condition

Daily and total limits must be checked/consumed atomically so concurrent requests cannot exceed the final available slot.

### 30.5 Sensitive female data loaded before authorization

Do not fetch or expose female contact/private Full Profile data until Full Profile access policy succeeds.

### 30.6 Report/Block return-flow risk

ProfileCard/ProfileInterestCard Report and Block must use the existing workflows with a safe allowlisted return context. Free members must not be redirected to inaccessible Full Profile pages.

### 30.7 Match Score N+1 risk

Membership, verification, photo, block, interest, preference, and ranking inputs must be joined/batched/precomputed appropriately rather than queried per candidate.

### 30.8 Pairwise score persistence risk

Do not persist every viewer/candidate Match Score pair. Persist/derive member-level signals and calculate viewer-specific relevance for filtered candidates.

### 30.9 Unsafe Superadmin scoring configuration

Require total weight = 100%, non-negative values, audit history, safe fallback configuration, and sensible component ranges so commercial weighting cannot accidentally dominate matchmaking.

### 30.10 Pricing/entitlement mismatch

Pricing and enforcement must use one plan source. Profile allowances must be described as unique Full Profile Views, irrespective of candidate Free/Paid or verification state.

### 30.11 Profile visibility conflict

Remove the obsolete Profile Visibility feature and all authorization/matching dependencies on it. Do not allow legacy `ALL_MEMBERS` / `PAID_MEMBERS_ONLY` state to conflict with the finalized Full Profile policy.

These controls are acceptance requirements for the current feature, not future recommendations.

---

## 31. Important Corner Cases

| Scenario | Required Behaviour |
|---|---|
| Paid plan expires while logged in | Protected access stops on next request |
| Expiry cron has not run | `expires_at` still prevents Paid access |
| Accepted interest exists but male plan expires | Interest remains accepted; female Full Profile becomes locked for him |
| Female accepts Free male's interest | Accepted state retained; Full Profile remains locked until Paid |
| Male later becomes Paid | Access becomes available if all current rules pass |
| Candidate blocks viewer after profile was consumed | Access is removed immediately |
| Candidate becomes inactive/non-searchable | Remove from applicable discovery/access |
| Viewer reaches daily profile limit | Existing consumed profiles remain accessible; new profiles blocked |
| Viewer reaches total profile limit | Existing consumed profiles remain accessible; new profiles blocked |
| Viewer reopens same profile | No additional usage |
| Viewed candidate is Free | Counts normally as a unique Full Profile View |
| Viewed candidate is Paid | Counts normally as a unique Full Profile View |
| Viewed candidate is unverified | Counts normally as a unique Full Profile View |
| Viewed candidate is verified | Counts normally as a unique Full Profile View |
| Viewer replays same Live Introduction | No additional usage |
| Candidate replaces Live Introduction | No additional usage for same owner/viewer/membership |
| New membership starts | Usage starts fresh for that membership |
| Active Go member buys Plus | Go becomes replaced; Plus starts immediately |
| Active Plus member attempts Go | Downgrade blocked |
| Active Pro member attempts Go/Plus | Downgrade blocked |
| Free member receives interest | Can view card and Accept/Decline |
| Free member tries Advanced Search | Locked; server denies advanced search |
| Free member directly opens Full Profile URL | Server denies and shows locked upgrade flow |
| Free member tries Aadhaar/Live Intro direct endpoint | Server denies |
| Free member needs to report/block | Allowed |
| Free member tries Shortlist | Locked/denied |
| Two tabs open same new profile simultaneously | One usage consumption only |
| Two different new profiles race for final daily slot | Only allowed quota is consumed |
| Match Score weights change | Subsequent ranking uses new active configuration; no pairwise rebuild |
| Candidate purchases higher plan | Commercial component reflects active plan without changing eligibility |
| Paid candidate has weak relevance | Commercial boost cannot bypass eligibility or dominate relevance |
| Legacy profile visibility value exists in DB | Ignored by new authorization; removed through controlled cleanup |

---

## 32. Security Rules

1. Every Paid feature must be protected server-side.
2. Hiding/disabling a button is not authorization.
3. Full Profile contact/private details must not be fetched/exposed before access policy succeeds.
4. Signed/private media access must enforce current viewer entitlement and applicable media privacy rules.
5. Block state overrides Paid membership.
6. Membership expiry is checked using membership status/time, never `users.is_paid` or a stale session flag.
7. Usage limits are enforced transactionally and with database uniqueness.
8. Superadmin scoring changes require authorization, validation, safe ranges, and audit history.
9. Member-visible usage history exposes only permitted information.
10. Plan changes must not retroactively mutate historical purchased entitlements.
11. Direct URL/action requests must pass the same entitlement policy as UI-triggered requests.
12. Legacy Profile Visibility state must not authorize or deny Full Profile access.

---

## 33. Recommended Implementation Sequence

### Phase 1 - Membership foundation

- authoritative plan definition;
- membership/history storage;
- active membership resolution;
- remove/migrate `users.is_paid` references;
- upgrade/downgrade rules;
- expiry logic and housekeeping cron;
- plan snapshot/version strategy;
- pricing copy/source-of-truth correction.

### Phase 2 - Entitlement and obsolete visibility removal

- centralized membership entitlement service;
- remove Profile Visibility UI/routes/controller/service dependencies;
- remove profile-visibility authorization/matching conditions;
- profile access policy;
- female access/privacy rules;
- locked-feature response pattern;
- server-side guards.

### Phase 3 - Usage accounting

- Full Profile View usage;
- Live Introduction usage;
- daily and total caps;
- concurrency-safe unique consumption;
- quota-race protection;
- Membership Usage screen/history.

### Phase 4 - UI feature enforcement

- Advanced Search locking;
- Aadhaar locking;
- Live Introduction locking;
- Full Profile locking;
- Shortlist locking;
- Report/Block on ProfileCard;
- Report/Block on ProfileInterestCard;
- safe return contexts;
- plan/current membership display and upgrade rules.

### Phase 5 - Match Score foundation

- Superadmin weight configuration and ranges;
- audit history;
- normalized component calculations;
- trust score;
- approved-photo signal;
- profile completion signal;
- commercial signal;
- preference relevance integration.

### Phase 6 - Search/ranking optimization

- candidate filtering pipeline;
- Match Score ordering;
- dashboard section reuse;
- Basic/Advanced Search reuse;
- database indexes;
- N+1 prevention;
- query-plan/performance testing;
- deterministic pagination.

### Phase 7 - Production hardening

- entitlement tests;
- `is_paid` removal regression tests;
- profile-visibility removal regression tests;
- membership expiry tests;
- concurrency/quota tests;
- privacy tests;
- pricing/plan consistency tests;
- ranking regression tests;
- Superadmin configuration tests;
- performance/load tests;
- audit/log review.

---

## 34. Acceptance Principles

Implementation is correct only when all of the following remain true:

- `users.is_paid` is no longer an authorization or presentation dependency and is removed through the project deployment process.
- Free members can meaningfully discover candidates and exchange interests.
- Report and Block remain available to Free members.
- Paid functionality cannot be bypassed with direct URLs/requests.
- Profile Visibility no longer affects discovery or Full Profile authorization and its obsolete member flow is removed.
- Photo visibility remains separately enforced.
- Female Full Profile/contact privacy is preserved.
- Interests remain unlimited across all plans.
- Full Profile limits count unique successfully accessed candidates, regardless of candidate plan or verification status.
- Live Introduction limits count unique owners, not repeat plays.
- Daily limits do not prevent reopening previously consumed profiles.
- Concurrency cannot double-consume usage or exceed quota.
- Only one membership can be active at a time.
- Active-plan downgrades are prevented.
- Expired membership access stops even if the cron fails.
- Membership history remains auditable.
- Hard match eligibility always runs before Match Score.
- Match Score is configurable by Superadmin within validated safe rules.
- Partner preference/relevance remains the dominant default ranking signal.
- Commercial priority improves exposure but does not bypass eligibility.
- Search does not depend on storing every viewer/candidate pair.
- Search filtering/scoring/pagination remains database-oriented and avoids N+1 behaviour.
- Pricing and entitlement limits use one source of truth.
- Pricing does not market Full Profile allowances as verified-profile allowances.
- Sensitive data is not loaded before access authorization.
- Card safety actions return members to an accessible context.

---

## 35. Deferred Scope

The following require separate specifications when implemented:

- payment gateway integration;
- payment success/failure/callback handling;
- payment reconciliation;
- refunds;
- chargebacks;
- invoices/GST/payment records;
- renewal offers/discounts/coupons;
- Match Manager operational workflow for Pro;
- any future automatic renewal policy.

Deferred items must integrate with, not replace, the membership lifecycle and entitlement principles defined here.

---

## 36. Source of Truth

This document is the agreed product/technical baseline for Membership and Matching behaviour.

When implementation begins:

1. read the latest `development` branch;
2. reuse the existing project architecture, models, services, validation, UI classes, modals, routes, and security patterns;
3. do not create parallel implementations where an existing flow can be extended;
4. check every implementation change against this document;
5. treat every risk control in Section 30 as part of the current feature acceptance criteria;
6. remove temporary/obsolete architecture (`users.is_paid` and member Profile Visibility) rather than carrying it forward behind compatibility checks;
7. update this document whenever an agreed business rule changes.