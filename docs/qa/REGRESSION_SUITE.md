# Permanent Regression Suite

## Purpose

This file contains regression cases that must survive individual feature work. It grows as the application baseline is established, features are QA-reviewed, and defects are fixed.

Do not populate cases from assumptions. Add a case after current behavior/requirement is confirmed by code review, accepted requirement, or resolved defect.

## Rules

- Every case has a stable unique ID.
- Do not reuse deleted IDs.
- A defect that could recur should result in a regression case.
- Feature QA must identify which existing regression cases are affected.
- Re-QA must execute/review the regression cases affected by the fix.
- Automated and manual cases may coexist.
- A case that was not actually executed is `NOT RUN`, not PASS.

## ID convention

Use `REG-<AREA>-NNN`.

Suggested area codes: AUTH, PROF, MEDIA, MATCH, INT, MSG, ADMIN, MASTER, PRE, DB, SEC.

## Execution status

- `PASS`
- `FAIL`
- `NOT RUN`
- `BLOCKED`
- `RETIRED`

## Authentication

No permanent cases recorded yet.

## Member Profile

No permanent cases recorded yet.

## Photos / Media

No permanent cases recorded yet.

## Matches / Search

No permanent cases recorded yet.

## Interests

No permanent cases recorded yet.

## Messages / Notifications

No permanent cases recorded yet.

## Admin

No permanent cases recorded yet.

## Master Data

No permanent cases recorded yet.

## Prelaunch Profile

### REG-PRE-001 - Valid public prelaunch profile save
**Origin:** Feature `QA-0001`  
**Expected:** Valid required data, consent and exactly two valid photos create one DRAFT profile plus two photo records atomically and redirect to success.  
**Automation:** Manual pending automation  
**Last result:** NOT RUN

### REG-PRE-002 - Duplicate member mobile rejected
**Origin:** Feature `QA-0001`  
**Expected:** A mobile already used by a prelaunch profile or live member contact is rejected and no partial profile/photo state is committed.  
**Automation:** Manual pending automation  
**Last result:** NOT RUN

### REG-PRE-003 - Optional email behavior
**Origin:** Feature `QA-0001`  
**Expected:** Missing email is accepted as NULL; supplied email must be valid and unique according to the prelaunch/member rules.  
**Automation:** Manual pending automation  
**Last result:** NOT RUN

### REG-PRE-004 - Photo upload safety
**Origin:** Feature `QA-0001`  
**Expected:** Invalid type, undecodable content, undersized/oversized images and duplicate raw photos are rejected without partial persistent state.  
**Automation:** Manual pending automation  
**Last result:** NOT RUN

### REG-PRE-005 - Admin prelaunch authorization
**Origin:** Feature `QA-0001`  
**Expected:** Admin list/review/photo/moderation/migration endpoints are inaccessible without a valid authenticated administrator session.  
**Automation:** Manual pending automation  
**Last result:** NOT RUN

### REG-PRE-006 - Approval requires approved photo
**Origin:** Feature `QA-0001`  
**Expected:** A DRAFT profile with zero approved photos cannot be approved/migrated; one or more approved photos satisfies the media prerequisite.  
**Automation:** Manual pending automation  
**Last result:** NOT RUN

### REG-PRE-007 - Migration is one-time
**Origin:** Feature `QA-0001`  
**Expected:** Concurrent/repeated approval attempts create at most one member for a prelaunch profile.  
**Automation:** Integration/manual pending automation  
**Last result:** NOT RUN

### REG-PRE-008 - Migration rollback consistency
**Origin:** Feature `QA-0001`  
**Expected:** A migration failure rolls back DB changes and removes S3 objects created by that failed attempt where cleanup succeeds.  
**Automation:** Integration/manual fault injection  
**Last result:** NOT RUN

### REG-PRE-009 - Relationship and gender contract
**Origin:** Feature `QA-0001`, finding `QA-PRE-002`  
**Expected:** SELF uses a valid submitted gender; SON/BROTHER resolve to MALE; DAUGHTER/SISTER resolve to FEMALE; unsupported relationship values fail validation cleanly.  
**Automation:** Manual pending automation  
**Last result:** NOT RUN

### REG-PRE-010 - Responsive form states
**Origin:** Feature `QA-0001`  
**Expected:** Desktop/tablet/mobile layouts, dependent dropdowns, validation, loading, duplicate-submit prevention, back/refresh and large-photo save states remain usable and consistent.  
**Automation:** Manual / Playwright candidate  
**Last result:** NOT RUN

## International Location Hierarchy

### REG-MASTER-001 - Country dependent location options
**Origin:** Canada international-location rollout

**Expected:** India remains the default. Changing country clears stale state/city values and loads only active states for the newest country request; changing state loads only active cities for the newest state request.

**Automation:** Integration/manual pending automation

**Last result:** NOT RUN

### REG-MASTER-002 - Location hierarchy tampering
**Origin:** Canada international-location rollout

**Expected:** Prelaunch and live member saves reject a country, state and city combination when the state does not belong to the country or the city does not belong to the state.

**Automation:** Integration test pending

**Last result:** NOT RUN

### REG-MASTER-003 - Migrated Canadian profile editing
**Origin:** Canada international-location rollout

**Expected:** A Canadian prelaunch profile can migrate and then reopen/save Basic Details, Family Details and Sikh/Religious birth location without being forced back to India.

**Automation:** End-to-end/manual pending automation

**Last result:** NOT RUN

### REG-MASTER-004 - International Search and Partner Preference
**Origin:** Canada international-location rollout

**Expected:** Search and Partner Preference show active Canadian provinces/territories with country-qualified labels and load cities for selected Canadian states.

**Automation:** Integration/manual pending automation

**Last result:** NOT RUN

## Cross-module Database Integrity

No dedicated permanent DB cases recorded yet. Prelaunch migration integrity is covered by `REG-PRE-007` and `REG-PRE-008` pending DDL baseline verification.

## Cross-module Security

### REG-SEC-001 - Migrated accounts do not share one reusable login secret
**Origin:** Feature `QA-0001`, finding `QA-PRE-001`  
**Expected:** Initial access for one migrated member cannot be reused to authenticate another migrated member; bootstrap access must be member-specific or prove control of that member's verified contact.  
**Automation:** Authentication integration/manual pending automation  
**Last result:** NOT RUN

### REG-SEC-003 - Migrated-member password setup isolation
**Origin:** Prelaunch migration, Account Settings and Forgot Password

**Expected:** Public Forgot Password and authenticated migrated-member password
setup have separate session flow identifiers and separate OTP purposes.

Public Forgot Password uses `PASSWORD_RESET`. Authenticated initial-password
creation uses `PASSWORD_SETUP`. Starting either flow clears previous temporary
password authorization state in that browser.

The authenticated setup route accepts no user, mobile or email identifier. The
member ID comes only from `auth_user_id`, and the service resolves that member's
verified primary mobile. Every verify, resend, password-form and password-update
step confirms that the authenticated member still matches the setup member.

An OTP issued for `PASSWORD_RESET` cannot verify or authorize
`PASSWORD_SETUP`, and an OTP issued for `PASSWORD_SETUP` cannot verify or
authorize `PASSWORD_RESET`.

After successful password creation or reset, the browser session is destroyed
and the member must log in using the new password.

**Required cases:**

1. Public reset OTP cannot be entered in a prelaunch setup flow.
2. Prelaunch setup OTP cannot be entered in public Forgot Password.
3. Starting public reset does not retain authenticated setup session state.
4. Starting authenticated setup does not retain public reset state.
5. Direct setup POST without authentication is rejected.
6. Setup rejects another member ID, mobile number or email input.
7. Setup rejects a non-migrated member.
8. Setup rejects a migrated member whose password is already set.
9. Setup rejects an unverified or non-primary mobile contact.
10. Logout or authenticated-user change invalidates an active setup flow.
11. OTP expiry, resend cooldown, attempt limit and daily quota remain enforced.
12. A consumed OTP cannot be reused during concurrent password submissions.
13. Successful password creation destroys the authenticated session.
14. Account Settings no longer shows setup guidance after password creation.

**Automation:** Integration/manual pending automation

**Last result:** NOT RUN

### REG-MATCH-005 - Dashboard Match View All navigation
**Origin:** Member Dashboard and Matches navigation

**Expected:** Every Dashboard match/activity section displays View All at the
top-right when that section contains at least one profile.

The destinations are:

- All Matches → `web.matches`
- New Matches → `activity=new-profiles`
- Profiles Shortlisted By You → `activity=shortlisted-by-you`
- Who Shortlisted You → `activity=shortlisted-you`
- Who Viewed Your Profile → `activity=viewed-you`
- Profiles You Viewed → `activity=viewed-by-you`

Each destination uses the existing Match/Search service collection and does not
create a separate profile query. The Matches header remains active for every
destination.

View All is hidden when a section has no records. With one record, View All is
shown and carousel controls are hidden. With two or more records, View All and
carousel controls are shown.

**Required cases:**

1. A section with zero profiles does not display View All.
2. A section with one profile displays View All without carousel controls.
3. A section with two or more profiles displays View All and carousel controls.
4. Every View All destination opens the corresponding complete collection.
5. Query-string manipulation is restricted by the existing activity allowlist.
6. Blocked and admin-actioned reported profiles remain excluded.
7. The Matches header remains active for all six destinations.
8. Desktop and mobile section headers remain aligned.
9. View All links have visible keyboard focus and meaningful accessible text.

**Automation:** Integration/manual pending automation

**Last result:** NOT RUN

### REG-MATCH-006 - Thumbnail member account type
**Origin:** Shared member presentation and Dashboard ProfileThumbnail

**Expected:** Every Dashboard ProfileThumbnail receives its account type through
the backend `MemberProfilePresentationService` contract. The view does not
hardcode or independently determine the account type.

Until subscription entitlements are implemented, the backend supplies
`Free Account`. ProfileThumbnail displays `Account: Free Account`.

The existing logged-in-member `accountPlan` Dashboard value must not be reused
for candidate thumbnails because it represents the viewer rather than the
member displayed by the thumbnail.

**Required cases:**

1. Every Dashboard collection supplies `accountType`.
2. ProfileThumbnail displays `Account: Free Account`.
3. Missing or empty `accountType` does not produce an empty Account label.
4. Dynamic output is escaped.
5. Search ProfileCard and ProfileInterestCard remain visually unchanged.
6. Blocked and globally hidden reported profiles remain excluded.
7. Mobile and desktop thumbnail layouts remain aligned.

**Automation:** Unit/manual pending automation

**Last result:** NOT RUN

### REG-MATCH-007 - Profile card account and verification indicators
**Origin:** Common member presentation, ProfileCard and ProfileInterestCard

**Expected:** ProfileCard and ProfileInterestCard display the backend-supplied
account type. Until subscription entitlement integration is implemented, the
backend supplies `Free Account`.

Both cards display one bottom verification row containing badges only for
successfully verified profile attributes:

- Mobile Verified
- Email Verified
- Aadhaar Verified
- Video Introduction

Verification values are loaded by MemberMatchCandidateModel and normalized by
MemberProfilePresentationService using the existing BooleanValue support class.
Views do not interpret raw PostgreSQL booleans and do not determine verification
state.

Unverified, pending, rejected and missing verification values must not be shown
as verified.

**Required cases:**

1. ProfileCard displays the backend-supplied account type.
2. ProfileInterestCard displays the backend-supplied account type.
3. A verified primary mobile displays Mobile Verified.
4. A verified primary email displays Email Verified.
5. Mobile, Email, Aadhaar and Video Introduction states match backend values.
6. Approved Aadhaar displays Aadhaar Verified.
7. Under-review, rejected or missing Aadhaar does not display Aadhaar Verified.
8. An active approved Video Introduction displays the Video Introduction badge.
9. Processing, pending-review, rejected, resubmission-requested, replaced and deleted videos do not display the badge.
10. All four verified badges remain in one horizontally scrollable row on
    narrow screens.
11. Dynamic labels and icon classes are escaped.
12. Blocked and globally hidden reported members remain excluded.
13. Search pagination does not duplicate candidates after contact joins.

**Automation:** Integration/manual pending automation

**Last result:** NOT RUN

### REG-MATCH-007 - Profile card account and verification indicators
**Origin:** Shared member presentation, ProfileCard and ProfileInterestCard

**Expected:** ProfileCard and ProfileInterestCard use the same member-summary
hierarchy.

Both cards display the backend-supplied Account Type immediately below the
member photo. Interest status remains at the top-right of ProfileInterestCard.

Both cards render the shared VerificationBadges component outside card-body so
the verification strip occupies the complete card width. Only successfully
verified attributes appear.

ProfileInterestCard retains its Interest-specific behaviour:

- Pending received Interest displays Decline and Accept Interest.
- Sent, accepted and declined Interests display View Profile.
- All forms retain CSRF protection and the existing submit-loader contract.

## Video Introduction regression

1. Consent is unchecked by default.
2. Camera and microphone permissions are not requested before consent.
3. Recording cannot be submitted before 15 seconds.
4. Recording automatically stops at 30 seconds.
5. Preview does not autoplay.
6. Member can retake before submission.
7. Processing and pending-review versions do not display a badge.
8. An approved visible version displays the badge.
9. An approved hidden version retains the badge but denies playback.
10. Female members cannot select `VISIBLE_PRO`.
11. A forged female `VISIBLE_PRO` request is rejected server-side.
12. Non-Pro viewers cannot play a `VISIBLE_PRO` video.
13. Accepted-interest visibility requires a currently accepted Interest.
14. Blocking either member prevents new playback authorization.
15. A globally hidden reported profile prevents playback.
16. Seven-day replacement and deletion restrictions are enforced server-side.
17. Rejection and resubmission permit a corrective recording.
18. A replacement does not retire the previous approved video before approval.
19. Deleting the active video removes the badge and does not expose an older replaced version.
20. Two Admin decisions cannot update the same pending version.
21. Reject and Resubmit require a 10–500 character reason on client and server.
22. Corrupt, missing-audio, missing-video and out-of-range recordings do not enter moderation.
23. Processing retries do not create duplicate notifications.
24. Raw S3 URLs are never returned.
25. Playback uses short-lived authenticated CloudFront URLs.
26. Videos never autoplay.
27. Cleanup does not delete active approved assets.

**Required cases:**

1. Account Type appears below the image in both card types.
2. Missing Account Type does not leave empty badge spacing.
3. Verification strip spans the complete card width.
4. Verification strip is hidden when no verification is true.
5. Mobile, Email, Aadhaar and Selfie states match backend values.
6. Pending received Interest retains Accept and Decline actions.
7. Non-actionable Interest retains View Profile.
8. Status badge remains visible at the top-right.
9. Desktop and mobile layouts remain aligned.
10. Blocked and admin-hidden reported members remain excluded.

**Automation:** Integration/manual pending automation

**Last result:** NOT RUN

## Retired cases

Keep retired cases here or retain their original section with status `RETIRED` and the reason. Do not erase regression history without explanation.


---
