# Membership and Matching Rules

## 1. Purpose

This document is the product and architecture source of truth for Free and Paid membership, profile access, membership usage, matching, ranking, search, privacy, and membership enforcement in Sikhanandkaraj.

The goals are to:

- provide useful discovery to Free members without exposing paid functionality;
- provide clear value to Go, Plus, and Pro members;
- protect member privacy, especially female member contact/profile access;
- keep safety features available regardless of plan;
- keep interests unrestricted;
- make Verified Profile and Live Introduction usage fair, concurrency-safe, and auditable;
- keep identity verification, including Aadhaar Verification, available independently of commercial membership;
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
| Verified Profiles | 0 | 50 | 100 | 300 |
| Daily new Verified Profile views | 0 | 5 | 10 | 20 |
| Live Introduction views | 0 | 10 | 30 | 80 |
| Interests sent | Unlimited | Unlimited | Unlimited | Unlimited |
| Interests received | Unlimited | Unlimited | Unlimited | Unlimited |
| Dedicated Match Manager | No | No | No | Yes |

### 2.1 Definition of a Verified Profile

For membership access and pricing, a **Verified Profile** is a candidate profile with at least one currently valid verified credential:

- Mobile; or
- Email; or
- Aadhaar; or
- Live Introduction.

Verification is independent of membership plan. A Verified Profile may belong to a Free, Go, Plus, or Pro member.

Mobile, Email and Aadhaar Verification are available to both Free and Paid members. Live Introduction remains subject to its applicable membership entitlement.

A profile does not need all four credentials to qualify. One valid credential is sufficient.

Because normal member registration verifies Mobile, and Aadhaar Verification is also available independently of commercial membership, a Free member can qualify as a Verified Profile through the same authoritative verification model used for Paid members.

The purpose of the Verified Profile rule is not to restrict profile access to Paid candidates. It ensures that Paid members consume their Verified Profile allowance only when accessing candidates having at least one currently valid trust credential.

### 2.2 Meaning of the 50 / 100 / 300 allowance

The pricing term **Verified Profiles** is correct and must be retained.

Go, Plus, and Pro respectively allow access to 50, 100, and 300 distinct Verified Profiles during the applicable membership period.

Internally, consumption is unique per candidate within a membership so repeat Full Profile access does not consume another allowance. `Unique` or `distinct` describes the accounting mechanism; it is not the customer-facing pricing label.

```text
Candidate B has Mobile verified only
-> Candidate B is a Verified Profile

First successful Full Profile access to B during Membership X
-> consume 1 Verified Profile allowance

Second/tenth access to B during Membership X
-> consume 0 additional allowance
```

The pricing UI and server enforcement must use the same authoritative plan definition. Plan values must not be independently hard-coded in views and services.

---

## 3. Membership State and Removal of Temporary `is_paid`

The existing `users.is_paid` field was a temporary QA flag and is not part of the production membership architecture.

It must be removed as part of the membership implementation after all existing references are migrated.

Production authorization must never use `users.is_paid`, `session.is_paid`, or scattered `if ($isPaid)` checks as the source of truth.

Membership state must be resolved from authoritative membership records and current time.

```text
FREE < GO < PLUS < PRO
```

`FREE` means there is no currently active Paid membership. Application code should ask for a capability/entitlement rather than repeatedly asking whether a member is Paid.

---

## 4. Product Principle

- **Free:** Discovery + Intent + Safety + Identity Verification
- **Paid:** Discovery + Intent + Safety + Identity Verification + Deeper Access

Free membership must remain useful enough to discover relevant candidates, send/receive interests, verify identity, and evaluate platform value.

Identity verification is not treated as a commercial upgrade. Mobile, Email and Aadhaar Verification are available to Free as well as Paid members and use the same authoritative verification and moderation workflows.

Paid membership unlocks deeper profile access, advanced discovery, Shortlist, Live Introduction creation/viewing, and controlled access to Verified Profiles subject to the applicable plan limits.

Safety and core identity-verification functionality must never require payment.

---

## 5. Feature Entitlement Matrix

| Feature | Free | Go | Plus | Pro |
|---|---|---|---|---|
| Create/Edit profile | Yes | Yes | Yes | Yes |
| Partner preferences | Yes | Yes | Yes | Yes |
| Upload/manage photos | Yes | Yes | Yes | Yes |
| Mobile verification | Yes | Yes | Yes | Yes |
| Email verification | Yes | Yes | Yes | Yes |
| Basic Search | Yes | Yes | Yes | Yes |
| Advanced Search | Locked | Yes | Yes | Yes |
| Dashboard match sections | Yes | Yes | Yes | Yes |
| ProfileThumbnail | Yes | Yes | Yes | Yes |
| ProfileCard | Yes | Yes | Yes | Yes |
| ProfileInterestCard | Yes | Yes | Yes | Yes |
| Search by Profile ID | ProfileCard | Subject to Full Profile policy | Subject to Full Profile policy | Subject to Full Profile policy |
| Send Interests | Unlimited | Unlimited | Unlimited | Unlimited |
| Receive Interests | Unlimited | Unlimited | Unlimited | Unlimited |
| Accept/Decline Interest | Yes | Yes | Yes | Yes |
| Full Profile of Verified candidate | Locked | Limited | Limited | Limited |
| Full Profile of completely unverified candidate | No | No | No | No |
| Aadhaar Verification | Yes | Yes | Yes | Yes |
| Create Live Introduction | Locked | Yes | Yes | Yes |
| Watch Live Introduction | Locked | Limited | Limited | Limited |
| Report Profile | Yes | Yes | Yes | Yes |
| Block Profile | Yes | Yes | Yes | Yes |
| Shortlist Profile | Locked | Yes | Yes | Yes |
| Membership Usage | Not applicable | Yes | Yes | Yes |
| Dedicated Match Manager | No | No | No | Yes |

Full Profile access remains subject to verification eligibility, gender/interest rules, block/moderation rules, active membership, and usage limits.

---

## 6. Locked Feature UX

Paid-only features remain discoverable to Free members where useful instead of disappearing completely. Examples include Advanced Search, Live Introduction, Shortlist, and Full Profile View.

Aadhaar Verification is not a Paid-only feature. Free, Go, Plus and Pro members may submit Aadhaar for verification subject to the same upload validation, workflow-state restrictions, privacy controls and administrator moderation process.

A Free member reaching a Paid-only feature receives a consistent upgrade message. UI locks are presentation only; protected endpoints require server-side enforcement.

A member must never receive an upgrade message merely because they are attempting Aadhaar Verification. Aadhaar upload availability depends on the current Aadhaar workflow state rather than commercial membership.

A Paid member blocked by a non-payment rule must receive the correct reason. Report and Block are safety features and must never be Paid-only.

### 6.1 Aadhaar Verification

Aadhaar Verification is available to Free, Go, Plus and Pro members.

Commercial membership must not be used as a condition for starting or re-submitting Aadhaar Verification.

The existing Aadhaar workflow remains authoritative:

```text
NOT_ADDED
    -> upload permitted

UNDER_REVIEW
    -> another upload is not permitted

REJECTED
    -> re-upload permitted

APPROVED
    -> another upload is not permitted
```
---

## 7. Interests

Interest sending and receiving are unlimited for Free, Go, Plus, and Pro.

All members may send, receive, accept, and decline interests. A Free member must never be required to purchase a plan merely to accept or decline an interest.

Interest state and membership state are independent. An accepted interest remains accepted when a membership expires.

---

## 8. Report, Block, and Shortlist

### Report

Available to Free and Paid members. ProfileCard and ProfileInterestCard must expose the existing report workflow.

### Block

Available to Free and Paid members. ProfileCard and ProfileInterestCard must expose the existing block workflow. Blocking overrides membership entitlement immediately.

### Shortlist

Paid only. Free UI may show the action as locked, and the server must deny direct Free shortlist requests.

Existing Report/Block actions that assume Full Profile as the return destination must support a safe allowlisted origin/return context so Free members can use safety actions from cards without being redirected to an inaccessible Full Profile.

---

## 9. Full Profile Access Policy

Full Profile access to another member requires an active Paid membership plus all applicable rules.

Member-configurable Profile Visibility is obsolete. `ALL_MEMBERS` / `PAID_MEMBERS_ONLY` semantics must be removed from member product flow, authorization, and matching. Photo Visibility remains a separate feature.

### 9.1 Verified Profile gate

Before Full Profile access can be granted, the candidate must have at least one currently valid verified credential:

```text
Mobile OR Email OR Aadhaar OR Live Introduction
```

If the candidate has zero verified credentials:

- Full Profile is unavailable to every other member, including Paid members;
- no profile allowance is consumed;
- the candidate may still appear in discovery/cards when otherwise eligible;
- the viewer receives an appropriate `awaiting verification` message rather than an upgrade message.

Verification must be evaluated from authoritative current verification state, not merely from a cached badge count that can become stale.

### 9.2 Female viewer viewing a male profile

A female member with an active Paid membership may view a male member's Full Profile when the male is a Verified Profile and all block, moderation, target eligibility/status, and usage rules pass. Accepted interest is not required.

### 9.3 Male viewer viewing a female profile

A male member may view a female member's Full Profile only when:

1. the male has an active Paid membership;
2. the female is a Verified Profile;
3. the female has accepted the male member's interest;
4. neither member blocks the other;
5. the target remains eligible/active and is not excluded by moderation;
6. usage limits permit new consumption, unless already consumed in the same membership.

### 9.4 Membership expiry after accepted interest

Accepted interest remains accepted, but Paid Full Profile access ends immediately when membership entitlement expires. A later Paid membership restores access only if all current rules pass and uses the new membership's accounting.

### 9.5 Accepted interest while male is Free

The interest may be accepted, but Full Profile remains locked until the male activates a Paid membership and all current rules pass.

### 9.6 Mandatory authorization and consumption order

```text
Candidate
   ↓
Hard target/block/moderation eligibility
   ↓
At least one verified credential?
   ├── NO → Full Profile unavailable; no allowance consumed
   └── YES
        ↓
      Active Paid membership?
        ↓
      Gender / interest / privacy access rules
        ↓
      Previously consumed this membership?
        ├── YES → Allow, no new consumption
        └── NO → Check daily + membership quota atomically
                 → Consume exactly 1 Verified Profile allowance
                 → Load sensitive profile/contact/media
```

Sensitive Full Profile data must never be fetched/exposed before authorization succeeds.

---

## 10. Female Contact Privacy

When Full Profile access succeeds for a female candidate, the existing female contact presentation must be preserved.

### 10.1 Parent contact exists

- display the female member's primary mobile in masked form;
- retain the **Verified** badge when her primary mobile is OTP-verified;
- display the full parent contact beneath the masked female mobile.

### 10.2 Parent contact does not exist

- display the female member's full primary mobile;
- retain the **Verified** badge when that primary mobile is OTP-verified.

### 10.3 Male profiles

Male profiles continue displaying their normal primary mobile according to the existing profile presentation flow.

### 10.4 Authorization boundary

These rules affect presentation only after Full Profile authorization succeeds. Contact information must not be exposed to an unauthorized viewer.

---

## 11. Photo Visibility

Photo Visibility remains separate from removed Profile Visibility.

For Free viewers, photos in ProfileThumbnail, ProfileCard, and ProfileInterestCard are shown only when the existing photo visibility policy permits that viewer. Photo authorization must remain centralized in the existing photo-policy/service architecture.

---

## 12. Verified Profile Consumption

### 12.1 Consumption identity

The first successful Full Profile access of a Verified Candidate B by Viewer A during a membership consumes one allowance.

```text
membership_id + viewer_member_id + viewed_member_id
```

The database must enforce uniqueness.

### 12.2 Repeat access

Reopening the same candidate during the same membership consumes nothing additional and remains allowed even after daily/total limits are reached, provided all current authorization rules still pass.

### 12.3 Daily limit

Daily limits count newly consumed Verified Profiles that day. Reopening an already consumed profile does not consume daily or membership allowance.

### 12.4 Total membership limit

When the total allowance is exhausted, previously consumed profiles remain accessible while membership and current access rules remain valid. New Verified Profiles cannot be consumed.

### 12.5 Completely unverified candidate

A candidate with zero verified credentials cannot be Full Viewed and never consumes the allowance.

### 12.6 Candidate verification changes after consumption

If a previously consumed candidate later has all verification credentials revoked/invalidated, current Full Profile access must fail the Verified Profile gate even though a historical usage record exists. The historical usage record is not deleted or refunded automatically.

If the candidate later becomes Verified again during the same membership, the existing consumption record means reopening does not consume another allowance.

### 12.7 New membership

Usage belongs to a membership instance. A new membership starts a new accounting period, so a previously viewed Verified Profile may consume one allowance when first accessed under the new membership.

---

## 13. Live Introduction Consumption

Live Introduction uses the same distinct-candidate accounting principle:

```text
membership_id + viewer_member_id + video_owner_member_id
```

First successful access consumes one allowance; replay does not. Re-upload/replacement by the same owner does not consume another allowance within the same membership. Existing video visibility/privacy and approval rules remain applicable. Membership expiry removes access. Full Profile and Live Introduction usage are separate counters.

Database uniqueness and transactional enforcement are mandatory.

---

## 14. Membership Usage Screen

Paid members receive **Membership Usage** in Account Settings.

Profile usage displays Verified Profile allowance, total used/remaining, today's used/daily limit, date-wise history, viewed member name using existing masking rules, and Member ID.

Live Introduction usage displays allowance, used, remaining, date-wise history, member name, and Member ID.

Usage records are auditable domain records, not analytics/log-derived counters.

---

## 15. Membership Lifecycle

A member may have many historical memberships but only one active membership.

Statuses:

```text
ACTIVE
EXPIRED
REPLACED
CANCELLED
```

While active, only upgrades are allowed:

```text
GO -> PLUS
GO -> PRO
PLUS -> PRO
```

Active downgrades are blocked. An upgrade starts immediately and the previous membership becomes `REPLACED`. Unused duration/usage does not carry forward unless a later commercial policy says otherwise.

Active entitlement requires:

```text
status = ACTIVE
AND starts_at <= current_time
AND expires_at > current_time
```

A daily expiry cron updates elapsed records as housekeeping, but authorization must use current membership/time directly and remain correct if the cron fails.

---

## 16. Membership History and Auditability

Historical membership records retain member, plan, start/expiry, status, purchased entitlement snapshot or immutable plan version, amount, future payment reference, replacement/cancellation metadata, timestamps, and administrative audit information.

Historical records must not be overwritten when plans expire or are replaced.

---

## 17. Match Eligibility: Mandatory First Stage

A candidate must first pass the hard eligibility rules applicable to the collection being requested. Common hard eligibility includes opposite gender, active/searchable state, blocks, moderation/report exclusions, and any other existing collection-independent safety/status conditions.

Collection-specific eligibility is then applied according to the rules in Section 21. Partner Preference is **not a universal hard-eligibility condition**. The >=30% Partner Preference threshold applies only to **All Matches / Default Matches** and collections explicitly defined as subsets of All Matches.

Profile Visibility is not an eligibility input.

```text
Collection eligibility first -> Match Score second -> Sorting third
```

The Verified Profile gate is specifically a **Full Profile access gate**. It must not automatically remove an otherwise eligible completely unverified candidate from discovery unless another product rule does so.

---

## 18. Match Score

Every candidate that survives the applicable collection eligibility receives a normalized Match Score using:

1. Partner Preference / Relevance;
2. Profile Completion;
3. Approved Photos;
4. Trust / Verification;
5. Commercial / Membership Priority.

Initial Superadmin-configurable defaults:

| Component | Weight |
|---|---:|
| Partner Preference / Relevance | 55% |
| Profile Completion | 10% |
| Approved Photos | 10% |
| Trust / Verification | 15% |
| Commercial / Membership | 10% |
| **Total** | **100%** |

Configuration must total 100%, reject negative values, be audited/historically traceable, have a safe fallback, and use sensible server-side ranges so Commercial cannot accidentally dominate matchmaking.

**Partner Preference remains exactly one weighted Match Score component.** Except for the explicit >=30% eligibility rule of All Matches / Default Matches, Partner Preference must not independently determine eligibility and must not receive an additional ranking advantage after Match Score has been calculated.

---

## 19. Match Score Components and Ordering

Partner Preference uses the existing preference matching logic and remains viewer-specific. Profile Completion uses the application's authoritative completion calculation. Approved Photos counts only approved photos and must be capped/normalized. Trust uses current verification state.

Recommended Trust points:

| Verification | Points |
|---|---:|
| Mobile | 1 |
| Email | 1 |
| Aadhaar | 3 |
| Live Introduction | 3 |
| **Maximum** | **8** |

Commercial priority:

```text
FREE = 0
GO   = 1
PLUS = 2
PRO  = 3
```

Commercial priority is a score input, never an eligibility bypass or absolute sorting override.

Primary ordering is `match_score DESC`. Deterministic tie-breakers may use non-duplicative member-level signals such as trust, profile completion, approved photos, freshness timestamp, and a stable member identifier. **Partner Preference must not be used as a separate tie-breaker**, because it is already represented in Match Score.

---

## 20. New Matches

**New Matches is a subset of All Matches.** A candidate must therefore first satisfy the All Matches / Default Matches eligibility rule, including the configured Partner Preference threshold (default 30%), and then satisfy the configured recent-days restriction.

The recent-days value is configuration-driven (`newMatchDays` in the current implementation) rather than a permanently hard-coded 30-day product rule. The implementation should use the appropriate existing creation/activation timestamp unless a dedicated publish timestamp is introduced later.

```text
All Matches eligibility
+ configured recent-days restriction
-> Match Score
-> Match Score DESC
```

---

## 21. Dashboard Match Sections and Search

### 21.1 All Matches / Default Matches

All Matches is the default discovery collection. A candidate must satisfy common hard eligibility and achieve the configured Partner Preference eligibility threshold, currently/default **>=30%**.

Candidates that qualify are ranked by final Match Score.

```text
Common hard eligibility
+ Partner Preference >= configured threshold (default 30%)
-> Match Score
-> Match Score DESC
```

### 21.2 New Matches

New Matches uses **All Matches eligibility** and adds only the configured recent-days restriction. It does not define an independent Partner Preference rule.

### 21.3 Filtered Basic / Advanced Search

When the member supplies one or more candidate search filters, those requested search filters determine candidate eligibility in addition to common hard eligibility.

Partner Preference does **not** independently filter the result set in filtered search. It remains one weighted input to Match Score.

```text
Common hard eligibility
+ requested search filters
-> Match Score
-> Match Score DESC
```

Advanced Search remains subject to membership entitlement. Free direct requests for Advanced Search must be denied with the Paid-feature response rather than silently downgraded.

### 21.4 Matches Specific Filters

For Matches collections such as Same State, Same City, Same Community, Profiles With Photos, or another explicit Matches filter, the selected section criterion determines collection eligibility in addition to common hard eligibility.

The All Matches >=30% Partner Preference threshold must **not** be silently added to these specific-filter collections unless the product explicitly defines that collection as a subset of All Matches.

Partner Preference remains a weighted Match Score component only.

```text
Common hard eligibility
+ selected Matches-specific criterion
-> Match Score
-> Match Score DESC
```

### 21.5 No-filter Basic / Advanced Search

If Basic or Advanced Search is submitted without any effective candidate filters, it falls back to **All Matches / Default Matches**. Therefore the configured Partner Preference eligibility threshold (default 30%) applies in this no-filter case.

### 21.6 Shared ranking pipeline

Basic Search, Advanced Search, Matches-specific filters, All Matches, and New Matches should share the same ranking engine after their respective candidate eligibility stage.

```text
Request / Collection
-> Common hard eligibility
-> Collection-specific eligibility
-> Candidate Set
-> Viewer Preference/Relevance + Member-level Signals
-> Match Score
-> Match Score DESC + deterministic non-duplicative tie-breakers
-> Pagination
```

---

## 22. Fast and Scalable Search Architecture

Do not persist every viewer/candidate Match Score pair.

Precompute or efficiently expose member-level signals such as active/searchable state, filter IDs, profile completion, approved-photo count, verification/trust state, active plan priority, and freshness timestamp.

Apply indexed hard filters first, then viewer-specific preference calculation to the reduced candidate set. Avoid N+1 queries for membership, verification, photos, interest, block, preference, and ranking inputs. Filtering/scoring/ordering/pagination should remain database-oriented rather than loading all candidates into PHP.

PostgreSQL remains the primary search engine until measured scale demonstrates a need for an external engine.

---

## 23. Centralized Entitlement Architecture

Membership logic must be centralized. Controllers enforce authorization; views render resolved capability state. Direct requests pass the same policies. Exact class names follow the existing project architecture rather than creating parallel implementations.

---

## 24. Usage Concurrency and Atomicity

Concurrency controls are mandatory current-feature requirements.

For Verified Profile access:

1. validate target eligibility and current verification;
2. validate active membership and relationship/access policy;
3. check existing consumption for the membership/viewer/candidate;
4. if existing, allow without increment;
5. if new, atomically verify daily and total allowance;
6. insert exactly one unique usage record;
7. load/return protected profile data.

Do not use an unsafe `SELECT count -> PHP check -> INSERT` sequence. Database uniqueness plus transactions/appropriate locking must prevent duplicate consumption and final-slot quota races. Live Introduction consumption requires equivalent protection.

---

## 25. Membership Expiry and Session Safety

Do not cache Paid state in the login session as authorization. Membership expiry/replacement must take effect on the next protected request without logout/login. Any cache must never extend entitlement beyond `expires_at`.

---

## 26. Pricing Page and Plan Source of Truth

The pricing implementation must use the same authoritative plan definition as membership services. Historical memberships retain a purchased entitlement snapshot or immutable plan-version relationship.

The pricing page must retain the customer-facing term **Verified Profiles**. Do not replace `50 Verified Profiles`, `100 Verified Profiles`, and `300 Verified Profiles` with generic `Full Profile Views` merely because repeat views are de-duplicated internally.

---

## 27. Removed Profile Visibility Feature

The member Profile Visibility feature is obsolete and must not participate in Account Settings, Full Profile authorization, matching, or search. Database cleanup follows the project's incremental SQL deployment rules. This does not remove Photo Visibility.

---

## 28. Mandatory Risk Controls

1. remove temporary `users.is_paid` dependencies;
2. enforce every Paid feature server-side, not only in UI;
3. prevent duplicate Profile/Video consumption under concurrency;
4. prevent daily/total quota final-slot races;
5. never load sensitive female/private Full Profile data before authorization;
6. support safe allowlisted return contexts for card Report/Block;
7. prevent Match Score N+1 queries;
8. do not persist every viewer/candidate score pair;
9. validate/audit Superadmin scoring and constrain unsafe weighting;
10. keep pricing and entitlement values in one authoritative plan source;
11. remove Profile Visibility conflicts;
12. evaluate the Verified Profile gate before consumption;
13. never consume an allowance for a completely unverified candidate;
14. do not treat a historical consumption record as proof that a candidate remains currently verified;
15. do not apply the All Matches Partner Preference threshold to filtered Basic/Advanced Search or Matches-specific filters;
16. do not give Partner Preference a second ranking advantage outside its configured Match Score weight.

---

## 29. Important Corner Cases

| Scenario | Required Behaviour |
|---|---|
| Paid plan expires while logged in | Protected access stops on next request |
| Expiry cron has not run | `expires_at` still prevents Paid access |
| Accepted interest exists but male plan expires | Interest remains accepted; female Full Profile locks |
| Female accepts Free male interest | Accepted; Full Profile remains locked until Paid |
| Candidate has any one valid credential | Candidate is a Verified Profile |
| Candidate has zero verified credentials | Full Profile unavailable; no allowance consumed |
| Viewer reopens same Verified Profile | No additional consumption |
| Candidate loses all verification after prior consumption | Current Full Profile access denied; historical usage retained |
| Viewer reaches daily/total limit | Previously consumed profiles remain accessible; new consumption blocked |
| Two tabs open same new profile | One consumption only |
| Two new profiles race for final daily/total slot | Quota cannot be exceeded |
| Viewer replays same Live Introduction | No additional consumption |
| New membership starts | Usage starts fresh for that membership |
| Active Go buys Plus/Pro | Upgrade allowed; previous membership replaced |
| Active Plus/Pro attempts downgrade | Downgrade blocked |
| Free member receives interest | Can Accept/Decline |
| Free member tries Advanced Search | Locked; server denies |
| Free member needs Report/Block | Allowed |
| Free member tries Shortlist | Locked/denied |
| All Matches candidate has PP below threshold | Excluded from All Matches |
| New Matches candidate has PP below All Matches threshold | Excluded because New Matches is an All Matches subset |
| Filtered Basic/Advanced Search candidate has PP below 30% | May remain eligible; PP affects Match Score only |
| Matches-specific filter candidate has PP below 30% | May remain eligible; PP affects Match Score only |
| Basic/Advanced Search has no effective filters | Falls back to All Matches threshold/ranking |
| Match Score weights change | New searches use new active configuration; no pairwise rebuild |

---

## 30. Security Rules

1. Every Paid feature is protected server-side.
2. UI locks are never authorization.
3. Candidate verification eligibility is checked before Full Profile consumption/access.
4. Sensitive contact/private details are loaded only after authorization.
5. Block state overrides Paid membership.
6. Membership expiry uses authoritative membership/time, not `is_paid` or stale session state.
7. Usage limits use transactions and database uniqueness.
8. Superadmin scoring changes require authorization, validation, safe ranges, and audit history.
9. Direct URL/action requests pass the same policies as UI requests.
10. A completely unverified profile cannot be Full Viewed by another member.

---

## 31. Recommended Implementation Sequence

### Phase 1 - Membership foundation

Authoritative plan definition, membership/history storage, active membership resolution, `users.is_paid` removal, lifecycle rules, expiry, and entitlement snapshots.

### Phase 2 - Entitlement and visibility cleanup

Centralized entitlement service, Verified Profile gate, Profile Visibility removal, Full Profile access policy, privacy rules, and server-side guards.

### Phase 3 - Usage accounting

Verified Profile and Live Introduction usage, daily/total caps, concurrency protection, and Membership Usage/history.

### Phase 4 - UI enforcement

Paid-feature locks, Report/Block card access, safe return contexts, current-plan UI, and awaiting-verification state.

### Phase 5 - Match Score and collection rules

Superadmin weights/ranges/audit; preference, completion, approved-photo, trust, and commercial components; All Matches >= configured threshold; New Matches recent-days subset; filtered search and Matches-specific eligibility separation; no duplicate Partner Preference ranking advantage.

### Phase 6 - Search optimization

Candidate filtering, Match Score ordering, dashboard/search reuse, indexes, N+1 prevention, query-plan/performance testing, and deterministic pagination.

### Phase 7 - Production hardening

Entitlement, access, expiry, concurrency, quota, privacy, pricing, ranking, collection-eligibility, configuration, performance, and audit regression tests.

---

## 32. Acceptance Principles

Implementation is correct only when:

- `users.is_paid` is removed as an authorization/presentation dependency;
- Profile Visibility no longer affects discovery or Full Profile authorization;
- Photo Visibility remains separately enforced;
- Free members can discover candidates and exchange unlimited interests;
- Report/Block remain Free and Paid safety features;
- Paid features enforce server-side rules;
- **Verified Profile means at least one verified Mobile, Email, Aadhaar, or Live Introduction credential**;
- completely unverified candidates cannot be Full Viewed and consume no allowance;
- each distinct Verified Profile consumes at most once per membership;
- repeat access does not consume again;
- concurrency cannot double-consume or exceed quota;
- female privacy and gender/interest rules are preserved;
- only one membership is active and active downgrades are prevented;
- expiry works even if cron fails;
- membership history remains auditable;
- All Matches / Default Matches enforce the configured Partner Preference threshold, default 30%;
- New Matches is All Matches plus the configured recent-days restriction;
- filtered Basic/Advanced Search uses requested filters for eligibility and Partner Preference only as a Match Score component;
- Matches-specific filters use their specific criterion for eligibility and Partner Preference only as a Match Score component;
- no-filter Basic/Advanced Search falls back to All Matches;
- Partner Preference receives no extra tie-break/ranking advantage after being included in Match Score;
- Match Score remains configurable, relevance-led, database-oriented, and scalable;
- pricing and enforcement use one source of truth;
- sensitive data is never loaded before authorization.

---

## 33. Deferred Scope

Separate future specifications will cover payment gateway integration, callbacks/reconciliation, refunds/chargebacks, invoices/GST, renewals/discounts/coupons, Pro Match Manager operational workflow, and automatic renewal.

---

## 34. Source of Truth

This document is the agreed product/technical baseline for Membership and Matching behaviour.

For implementation:

1. read the latest active development branch for the feature;
2. reuse existing project architecture, services, validation, UI classes, routes, security and deployment rules;
3. extend existing flows rather than creating parallel implementations;
4. check every implementation change against this document;
5. treat all risk controls as current-feature acceptance requirements;
6. remove temporary/obsolete `users.is_paid` and Profile Visibility architecture rather than preserving compatibility checks;
7. retain **Verified Profiles** as the pricing concept and enforce the verification gate before Full Profile consumption;
8. update this document whenever an agreed business rule changes.
