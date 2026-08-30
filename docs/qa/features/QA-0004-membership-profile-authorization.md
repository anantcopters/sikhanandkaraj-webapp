# QA-0004 - Membership and Profile Authorization Release Gate

## Review information

- **Feature:** Free/Paid Membership Authority and Protected Profile Access
- **Requirement/source:** Permanent regression gate covering membership entitlement, Full Profile authorization, male-to-female accepted-Interest privacy, protected profile media, quotas, direct-request bypasses, blocking and moderation
- **Branch:** development
- **Implementation commit:** Current development branch
- **QA review date:** 2026-08-30
- **QA iteration:** 1
- **Overall status:** PERMANENT RELEASE GATE

## Requirement summary

SikhanandKaraj must enforce membership and matrimonial privacy rules on the server. A Free member must never acquire paid-only authority by manipulating the UI, calling routes directly, changing request parameters, or establishing an Interest relationship. A paid member receives only the authority granted by the active membership and remains subject to target eligibility, blocking, moderation, quota and matrimonial privacy rules.

For Male -> Female protected profile access, the male viewer must have an active paid membership and the female must have accepted the Interest sent by that male. Accepted Interest alone must never upgrade a Free member.

Every protected resource, including Full Profile, profile PDF and profile-detail media, must independently enforce its applicable server-side authorization. Hiding or disabling UI is not an authorization boundary.

## Standard QA test data

Maintain reusable QA accounts/data for every release:

| Alias | Required state |
| --- | --- |
| FREE_MALE | Active Male, Free membership |
| FREE_FEMALE | Active Female, Free membership |
| PAID_MALE_GO | Active Male, active GO membership |
| PAID_MALE_PLUS | Active Male, active PLUS membership |
| PAID_MALE_PRO | Active Male, active PRO membership |
| PAID_FEMALE | Active Female, active paid membership |
| EXPIRED_MALE | Male whose paid membership has expired |
| VERIFIED_FEMALE | Active Verified Female with approved photos |
| VERIFIED_MALE | Active Verified Male with approved photos |
| UNVERIFIED_TARGET | Active member who is not a Verified Profile |
| HIDDEN_TARGET | Active member globally hidden by moderation |
| BLOCKED_TARGET | Active member with a block relationship to viewer |

At least one target must have an approved `PUBLIC` gallery photo and at least one approved `INTERESTED_MEMBERS` gallery photo. Record valid profile references and photo IDs before executing direct-resource tests.

## Affected components

| Type | Path / Object | Reason |
| --- | --- | --- |
| Route | `app/Config/Routes.php` | Member profile, PDF, media and paid-feature routes |
| Controller | `app/Controllers/Web/MemberProfileController.php` | Full Profile interactions and protected medium-photo endpoint |
| Controller | `app/Controllers/Web/MemberProfilePdfController.php` | Another-member protected PDF |
| Controller | `app/Controllers/Web/SearchController.php` | Basic/Advanced Search entry point |
| Service/Support | `app/Services/Matchmaking/MemberProfileViewService.php` | Protected another-member profile/media authorization flow |
| Service/Support | `app/Services/Membership/ProfileAccessPolicy.php` | Central Full Profile and relationship/privacy policy |
| Service/Support | `app/Services/Membership/MembershipEntitlementService.php` | Paid capability authority |
| Service/Support | `app/Services/Membership/MembershipService.php` | Active membership resolution |
| Service/Support | `app/Services/Membership/MembershipProfileUsageService.php` | Full Profile quota authority |
| Service/Support | `app/Services/Profile/MemberPhotoUrlService.php` | Viewer-safe approved photo URL generation |
| Service/Support | `app/Services/Matchmaking/MemberInteractionService.php` | Interest, block and shortlist relationship state |
| Database | Membership/interest/profile-view/photo/report persistence | Authority and state transitions |
| Tests | This QA feature document | Permanent pre-release regression gate |

# 1. Free Member Authority

## TC-AUTH-001 - Free member cannot open Full Profile

- **Priority:** CRITICAL
- **Precondition:** `FREE_MALE` and an eligible verified target exist.
- **Steps:**
  1. Login as `FREE_MALE`.
  2. Locate a target through Dashboard/Search/Matches.
  3. Attempt View Profile normally.
  4. Copy the target profile reference.
  5. Directly request `/members/{ref}`.
- **Expected:** Full Profile is unavailable through UI and direct URL. Server independently rejects access using the current paid-membership response.
- **Fail condition:** Any protected Full Profile information is returned.

## TC-AUTH-002 - Accepted Interest does not upgrade Free authority

- **Priority:** CRITICAL
- **Precondition:** `FREE_MALE` sends Interest to `VERIFIED_FEMALE`; Female accepts it.
- **Steps:**
  1. Login as `FREE_MALE`.
  2. Verify the Interest is Accepted.
  3. Attempt View Profile.
  4. Directly request `/members/{female-ref}`.
  5. Attempt protected profile resources including PDF and medium gallery media.
- **Expected:** Full Profile and protected detail resources remain unavailable because the viewer is Free. Accepted Interest satisfies relationship state only; it does not provide paid entitlement.
- **Fail condition:** Accepted Interest grants any paid-only capability.

## TC-AUTH-003 - Free Female cannot open Male Full Profile

- **Priority:** CRITICAL
- **Steps:**
  1. Login as `FREE_FEMALE`.
  2. Locate `VERIFIED_MALE`.
  3. Attempt View Profile.
  4. Directly request `/members/{male-ref}`.
- **Expected:** Denied because Full Profile requires paid membership.

## TC-AUTH-004 - Paid Female can view eligible Male profile

- **Priority:** HIGH
- **Steps:**
  1. Login as `PAID_FEMALE`.
  2. Select `VERIFIED_MALE`.
  3. Ensure no Interest is required for the test.
  4. Open Full Profile.
- **Expected:** Profile opens when membership allowance and all other eligibility conditions are satisfied. No accepted Interest is required for Female -> Male under the current policy.

# 2. Male -> Female Privacy Gate

## TC-AUTH-010 - Paid Male cannot view Female without Interest

- **Priority:** CRITICAL
- **Steps:**
  1. Login as `PAID_MALE_GO`.
  2. Locate `VERIFIED_FEMALE`.
  3. Ensure no Interest relationship exists.
  4. Click View Profile.
  5. Directly request `/members/{female-ref}`.
- **Expected:** Denied. Accepted Interest is required.

## TC-AUTH-011 - Pending Interest does not unlock Female profile

- **Priority:** CRITICAL
- **Steps:**
  1. Login as a paid Male.
  2. Send Interest to `VERIFIED_FEMALE`.
  3. Leave the Interest Pending.
  4. Attempt Full Profile through UI.
  5. Attempt Full Profile through direct URL.
- **Expected:** Denied through both paths.

## TC-AUTH-012 - Declined Interest does not unlock Female profile

- **Priority:** CRITICAL
- **Steps:**
  1. Paid Male sends Interest.
  2. Female declines it.
  3. Login as the Male.
  4. Attempt Full Profile through UI and direct URL.
- **Expected:** Denied.

## TC-AUTH-013 - Accepted Interest unlocks Female profile for Paid Male

- **Priority:** CRITICAL
- **Steps:**
  1. Paid Male sends Interest to Female.
  2. Login as Female and accept the Interest.
  3. Login as Paid Male.
  4. Open Female Full Profile.
- **Expected:** Full Profile opens subject to active membership, quota, target verification, block/moderation and other normal authorization requirements.
- **Repeat for:** GO, PLUS and PRO.

## TC-AUTH-014 - Female-originated Interest does not satisfy Male -> Female accepted-sent rule

- **Priority:** CRITICAL
- **Steps:**
  1. Female sends Interest to Paid Male.
  2. Male accepts the Female's Interest.
  3. Male attempts to open Female Full Profile.
- **Expected:** Access remains denied under the current policy because the required relationship is the Interest sent by the Male and accepted by the Female.

# 3. Female -> Male Privacy Gate

## TC-AUTH-020 - Paid Female can view eligible Male without Interest

- **Priority:** HIGH
- **Steps:** Login as `PAID_FEMALE` and open `VERIFIED_MALE` with no Interest relationship.
- **Expected:** Allowed subject to normal paid entitlement/quota and target eligibility.

## TC-AUTH-021 - Free Female remains denied even with Accepted Interest

- **Priority:** CRITICAL
- **Steps:**
  1. Establish an Accepted Interest involving `FREE_FEMALE` and `VERIFIED_MALE`.
  2. Attempt Male Full Profile as `FREE_FEMALE`.
- **Expected:** Denied because Accepted Interest cannot replace paid entitlement.

## TC-AUTH-022 - Paid Female cannot view unverified/blocked/hidden Male

- **Priority:** CRITICAL
- **Steps:** Attempt Full Profile separately against `UNVERIFIED_TARGET`, `BLOCKED_TARGET` and `HIDDEN_TARGET`.
- **Expected:** Each request is denied according to its server-side policy.

# 4. Medium Photo Security Gate

The following cases permanently reproduce the authorization class that previously allowed direct access through:

`GET /members/{ref}/photos/{photoId}/medium-url`

## TC-MEDIA-001 - Free member cannot retrieve PUBLIC medium photo

- **Priority:** CRITICAL
- **Steps:**
  1. Login as `FREE_MALE`.
  2. Obtain a valid approved `PUBLIC` photo ID for `VERIFIED_FEMALE`.
  3. Directly request `/members/{female-ref}/photos/{photoId}/medium-url`.
- **Expected:** HTTP 403. No signed `mediumUrl` is returned.
- **Fail condition:** HTTP 200 or any usable signed URL is returned.

## TC-MEDIA-002 - Free member with Accepted Interest still cannot retrieve medium photo

- **Priority:** CRITICAL
- **Steps:** Establish Accepted Male -> Female Interest while Male remains Free, then call the medium endpoint directly.
- **Expected:** HTTP 403; no signed URL.

## TC-MEDIA-003 - Paid Male without Interest cannot retrieve Female medium photo

- **Priority:** CRITICAL
- **Steps:** Login as paid Male with no Interest and directly request a valid Female medium photo.
- **Expected:** HTTP 403; no signed URL.

## TC-MEDIA-004 - Pending Interest cannot retrieve Female medium photo

- **Priority:** CRITICAL
- **Steps:** Paid Male sends Interest, leaves it Pending, then directly requests a valid Female medium photo.
- **Expected:** HTTP 403.

## TC-MEDIA-005 - Declined Interest cannot retrieve Female medium photo

- **Priority:** CRITICAL
- **Steps:** Female declines Paid Male's Interest; Male directly requests a valid Female medium photo.
- **Expected:** HTTP 403.

## TC-MEDIA-006 - Accepted Interest permits authorized medium photo

- **Priority:** CRITICAL
- **Steps:** Paid Male sends Interest, Female accepts, then Male requests an approved visible medium photo.
- **Expected:** HTTP 200 with an authorized signed `mediumUrl`, provided quota, target eligibility and photo visibility requirements are satisfied.

## TC-MEDIA-007 - Cross-member photo-ID substitution

- **Priority:** CRITICAL
- **Steps:**
  1. Obtain authorization for Member A.
  2. Obtain/identify a valid photo ID belonging to Member B.
  3. Request `/members/{member-A-ref}/photos/{member-B-photoId}/medium-url`.
- **Expected:** HTTP 404. Member B's signed URL is never returned.

## TC-MEDIA-008 - Invalid photo ID

- **Priority:** HIGH
- **Steps:** Request photo ID `0`, a nonexistent positive ID and a very large valid integer.
- **Expected:** HTTP 404; no internal/database information is disclosed.

## TC-MEDIA-009 - Unauthenticated medium request

- **Priority:** CRITICAL
- **Steps:** Logout/delete session and directly call the medium endpoint.
- **Expected:** Existing authentication layer rejects/redirects the request. No signed URL is returned.

## TC-MEDIA-010 - Unverified target cannot expose medium photo

- **Priority:** CRITICAL
- **Steps:** Use otherwise-authorized Paid viewer and request a valid medium photo belonging to `UNVERIFIED_TARGET`.
- **Expected:** Denied; no signed URL.

## TC-MEDIA-011 - Blocked or globally hidden target cannot expose medium photo

- **Priority:** CRITICAL
- **Steps:** Repeat direct medium request separately for a blocked pair and globally hidden target.
- **Expected:** HTTP 404/unavailable; no signed URL.

# 5. Membership Lifecycle Gate

## TC-MEM-001 - Free -> Paid activates entitlement

- **Priority:** HIGH
- **Steps:** Verify Full Profile denial as Free, activate a valid paid membership, then retry an otherwise eligible target.
- **Expected:** Paid capabilities become available according to the purchased plan.

## TC-MEM-002 - Expired membership immediately loses paid authority

- **Priority:** CRITICAL
- **Steps:**
  1. Open an authorized profile while membership is active.
  2. Expire the membership.
  3. Without relying on logout/login, request another Full Profile.
  4. Directly request protected medium media.
- **Expected:** Paid access is denied immediately.

## TC-MEM-003 - Cancelled/replaced membership cannot retain authority

- **Priority:** CRITICAL
- **Steps:** Cancel or replace the active membership, then replay Full Profile and protected-resource requests from the existing session.
- **Expected:** Old membership cannot authorize new paid access.

## TC-MEM-004 - Future membership cannot be used early

- **Priority:** CRITICAL
- **Precondition:** Membership has a future `starts_at`.
- **Expected:** Member behaves as Free until the membership becomes active.

## TC-MEM-005 - Unknown/corrupt plan fails closed

- **Priority:** CRITICAL
- **Precondition:** QA-only data contains an unsupported plan snapshot/code.
- **Expected:** Member never gains paid capability; authorization fails closed.

## TC-MEM-006 - Existing session does not preserve expired authority

- **Priority:** CRITICAL
- **Steps:** Login while paid, expire/cancel membership without ending the session, then call paid-only endpoints directly.
- **Expected:** Every new request uses current server-side membership state and is denied.

# 6. Quota Gate

## TC-QUOTA-001 - New Full Profile consumes allowance

- **Priority:** HIGH
- **Steps:** Record starting usage, open one new authorized Full Profile, then verify usage.
- **Expected:** One unique-profile allowance is consumed.

## TC-QUOTA-002 - Repeat Full Profile does not consume another unique allowance

- **Priority:** HIGH
- **Steps:** Open the same target again after initial authorized view.
- **Expected:** Target is treated as repeat view and no second unique-profile allowance is consumed.

## TC-QUOTA-003 - Medium image cannot bypass exhausted quota

- **Priority:** CRITICAL
- **Steps:** Exhaust applicable Full Profile quota, identify a valid photo for a new target, then call the medium endpoint directly.
- **Expected:** HTTP 429; no signed URL.

## TC-QUOTA-004 - Concurrent final allowance cannot exceed limit

- **Priority:** CRITICAL
- **Steps:** Leave exactly one available allowance and send concurrent first-time requests for two different eligible targets.
- **Expected:** System must not consume or authorize beyond the configured limit.

# 7. Blocking, Moderation and Target Eligibility

## TC-SEC-001 - Viewer blocks target

- **Priority:** CRITICAL
- **Steps:** Establish otherwise-valid paid/Interest authorization, block target, then directly request Full Profile, PDF and medium media.
- **Expected:** Protected resources are unavailable.

## TC-SEC-002 - Target blocks viewer

- **Priority:** CRITICAL
- **Steps:** Target blocks viewer; viewer directly requests Full Profile, PDF and medium media.
- **Expected:** Protected resources are unavailable.

## TC-SEC-003 - Globally hidden target

- **Priority:** CRITICAL
- **Steps:** Admin/moderation globally hides target; otherwise-authorized viewer requests Full Profile, PDF and medium media.
- **Expected:** Generic unavailable/404 behavior; moderation state is not disclosed.

## TC-SEC-004 - Unverified target

- **Priority:** HIGH
- **Steps:** Paid viewer requests Full Profile and medium media for active but unverified target.
- **Expected:** Protected access is denied.

## TC-SEC-005 - Self-access through another-member resource

- **Priority:** HIGH
- **Steps:** Request own public profile reference through another-member Full Profile/media routes.
- **Expected:** Generic unavailable/404 behavior.

# 8. Direct Request / UI Bypass Gate

## TC-BYPASS-001 - DOM manipulation cannot enable Full Profile

- **Priority:** CRITICAL
- **Steps:** Login Free, use browser developer tools to remove hidden/disabled restrictions, reconstruct the paid Full Profile request and submit it.
- **Expected:** Server denies the request.

## TC-BYPASS-002 - Request parameters cannot change membership authority

- **Priority:** CRITICAL
- **Steps:** Add or modify values such as `plan=PRO`, `membership=paid`, `isPaid=1`, `accountType=PRO`, `canViewFullProfile=1` in query/POST data wherever technically possible.
- **Expected:** No effect. Membership authority is resolved from server-side state.

## TC-BYPASS-003 - Direct another-member PDF access

- **Priority:** CRITICAL
- **Steps:**
  1. Login Free and directly POST to the another-member PDF route using a valid profile reference.
  2. Repeat as Paid Male -> Female with no Interest.
  3. Repeat with Pending Interest.
  4. Repeat with Declined Interest.
  5. Repeat after Female accepts the Paid Male's Interest.
- **Expected:** Free is always denied. Paid Male is denied until required Accepted Interest exists; accepted and otherwise-authorized Paid Male succeeds subject to quota/policy.

## TC-BYPASS-004 - Advanced Search direct URL

- **Priority:** HIGH
- **Steps:** Login Free, bypass UI and construct `/search/results?mode=advanced&...`.
- **Expected:** Server rejects Advanced Search entitlement.

## TC-BYPASS-005 - Direct paid-action POST replay

- **Priority:** CRITICAL
- **Steps:** Capture a valid paid-member request and replay the equivalent operation while authenticated as Free.
- **Scope:** Advanced Search, Aadhaar, Live Introduction, Shortlist creation, Full Profile, profile PDF, medium gallery media and every newly introduced paid-only feature.
- **Expected:** Every paid-only operation is rejected server-side.

# 9. Existing Data / Downgrade Regression

## TC-DATA-001 - Previously Paid -> Free

- **Priority:** HIGH
- **Steps:** Paid member creates/uses paid features, membership expires, then member continues using the same account as Free.
- **Expected:** Historical data may remain where intentionally retained, but the member cannot create/use new paid-only capability.

## TC-DATA-002 - Existing shortlist after downgrade

- **Priority:** HIGH
- **Steps:** Paid member shortlists a profile, membership expires, then attempt to remove the existing shortlist and add a new shortlist.
- **Expected:** Existing shortlist may be removed according to the current cleanup design; creation of a new paid-only shortlist is denied.

# 10. Security QA Matrix

| Check | Required result |
| --- | --- |
| Authentication | Protected routes reject unauthenticated requests |
| Membership authorization | Current server-side membership is authoritative |
| Male -> Female privacy | Paid Male requires Female acceptance of Male-sent Interest |
| Ownership / IDOR | Cross-member resource substitution fails |
| Direct URL access | Same policy as UI flow |
| Request tampering | Client-supplied plan/capability flags have no authority |
| Block protection | Bidirectional block prevents protected access |
| Moderation protection | Globally hidden target remains unavailable |
| Verification | Unverified target cannot expose protected profile resources |
| Quota | Direct media/PDF/profile routes cannot bypass commercial allowance |
| Sensitive media | Signed URLs generated only after applicable authorization |
| UI-only protection | Never accepted as sufficient authorization |

# 11. Permanent Release Gate Checklist

QA must complete this checklist before every production release that touches membership, profile access, search, interests, cards, media, PDF, blocking, moderation, plans or authorization.

| Gate | Result |
| --- | --- |
| Free cannot access Full Profile | [ ] PASS [ ] FAIL |
| Accepted Interest does not upgrade Free | [ ] PASS [ ] FAIL |
| Paid Male -> Female privacy enforced | [ ] PASS [ ] FAIL |
| Paid Female -> eligible Male flow works | [ ] PASS [ ] FAIL |
| Direct medium-photo bypass remains closed | [ ] PASS [ ] FAIL |
| Cross-member photo-ID substitution denied | [ ] PASS [ ] FAIL |
| PDF bypass remains closed | [ ] PASS [ ] FAIL |
| Advanced Search bypass remains closed | [ ] PASS [ ] FAIL |
| Paid-only POSTs protected server-side | [ ] PASS [ ] FAIL |
| Block/moderation authorization works | [ ] PASS [ ] FAIL |
| Unverified targets remain protected | [ ] PASS [ ] FAIL |
| Expired/cancelled/replaced membership denied | [ ] PASS [ ] FAIL |
| Future/unknown membership fails closed | [ ] PASS [ ] FAIL |
| Full Profile quotas cannot be bypassed | [ ] PASS [ ] FAIL |
| No UI-only authorization discovered | [ ] PASS [ ] FAIL |

# QA Gate Rules

The following are zero-tolerance release blockers:

- **AUTH-01:** A Free member must never obtain Full Profile/detail protected resources regardless of Interest state.
- **AUTH-02:** A paid Male must never obtain a Female's protected profile resources until the required accepted-Interest state exists.
- **AUTH-03:** Client/UI restrictions must never be the authorization boundary.
- **AUTH-04:** Every alternate resource path, including PDF, media URLs, AJAX endpoints and direct URLs, must independently enforce its applicable server-side policy.
- **AUTH-05:** A stale, expired, cancelled, replaced, future or unknown membership must never provide paid authority.
- **AUTH-06:** Cross-member identifiers must never allow IDOR/resource substitution.
- **AUTH-07:** Protected-resource endpoints must not bypass membership/daily commercial quotas.

## Release decision

**PASS:** All CRITICAL and HIGH authorization regression cases pass and no protected capability is available outside the current membership/relationship authority.

**FAIL / NO RELEASE:** Any test allows a member to acquire a paid capability, Full Profile information, PDF, protected media or another protected resource outside their current server-authorized membership and relationship state.

## Manual verification still required

- Execute direct HTTP cases against the deployed QA environment rather than relying only on browser UI behavior.
- Verify actual HTTP status and response body for 403/404/429 cases.
- Verify failed media requests never contain a signed URL.
- Verify browser network history does not reveal protected media before authorization.
- Execute quota concurrency test using simultaneous requests.
- Re-run GO, PLUS and PRO where plan capabilities/limits differ.

## QA Gate

| QA Area | Result |
| --- | --- |
| Requirement QA | PERMANENT GATE |
| Code QA | REQUIRED WHEN AUTHORIZATION CODE CHANGES |
| UI QA | REQUIRED WHEN MEMBERSHIP/PROFILE UI CHANGES |
| Validation QA | REQUIRED FOR NEW/MODIFIED REQUEST INPUT |
| Database QA | REQUIRED WHEN MEMBERSHIP/INTEREST/QUOTA DATA CHANGES |
| Security QA | MANDATORY |
| Regression QA | MANDATORY |
| **FINAL QA GATE** | **NO RELEASE ON CRITICAL/HIGH FAILURE** |

## Re-QA history

| Iteration | Date/Commit | Findings retested | Regression scope | Gate |
| --- | --- | --- | --- | --- |
| 1 | 2026-08-30 / development | Initial permanent membership/profile authorization suite | Free/Paid authority, Male/Female privacy, Full Profile, media, PDF, quota, direct bypass | ESTABLISHED |
