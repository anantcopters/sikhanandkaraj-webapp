# Member Video Introduction — Feature Requirement

## 1. Purpose

Replace the current member selfie feature with a moderated **Video Introduction** recorded directly through the website.

The feature gives members a short personal introduction for trust and profile engagement. It is not formal identity verification and must never be described as a guarantee that SikhanAndKaraj has verified the member's identity.

All member/admin UI references to selfie recording, selfie verification and selfie status must be removed or replaced. Existing selfie data must not automatically become a Video Introduction.

## 2. Scope

The feature includes:

- browser-based camera and microphone recording;
- member instructions and explicit privacy consent;
- preview, retake and submission;
- private S3 storage and asynchronous video processing;
- web-optimised playback output and poster/thumbnail generation;
- administrator moderation;
- member notifications;
- Account Settings management;
- viewer-aware CloudFront delivery;
- Video Introduction badge;
- privacy, hide, replacement and deletion controls;
- retention and audit history.

Uploading a prerecorded file is out of scope unless approved as a later requirement.

## 3. Recording rules

| Rule | Requirement |
|---|---|
| Minimum duration | 15 seconds |
| Maximum duration | 30 seconds |
| Recommended duration | 20–30 seconds |
| Capture source | Direct website recording only |
| Required tracks | Video and audio |
| Preview | Required before submission |
| Autoplay | Not allowed |
| Server validation | Mandatory and authoritative |

The earlier 45-second concept is superseded by the final 30-second maximum. The application, configuration, validation, countdown and user messages must use the same limit.

## 4. Member entry points

### 4.1 Profile

Add a **Video Introduction** section/action to the member's own profile/profile-edit journey. The action shown depends on the current lifecycle state:

- Record Video Introduction
- View Submission
- Retry/Retake
- Resubmit
- Manage Video
- View Moderation Status

### 4.2 Account Settings

Add **Video Introduction** to Account Settings. A member can:

- view the submitted/approved video;
- view processing and moderation status;
- read rejection or resubmission instructions;
- select an allowed visibility setting;
- hide/unhide an approved video;
- replace the video when permitted;
- delete the video after the lock expires.

## 5. Instructions and consent

Instructions and consent must be displayed before camera/microphone permissions are requested.

### 5.1 Recording guidance

> Introduce yourself, mention your interests, education or profession, and what you are looking for in a life partner. Do not share your phone number, email address, residential address or social-media details.

Optional prompts displayed beside/below the recorder:

- First name and city
- Education or profession
- Hobbies and interests
- A few words about family values
- Qualities expected in a life partner

### 5.2 Moderation and privacy conditions

The member must be told that:

- one person must be clearly visible and audible;
- the video must appear to be an original personal introduction;
- content must be respectful and relevant;
- phone numbers, email addresses, addresses and social-media handles must not be spoken or displayed;
- offensive, misleading or promotional content is prohibited;
- another person's private information must not be disclosed;
- copyrighted background music must not be used;
- the member must not claim that SikhanAndKaraj guarantees or certifies their identity;
- the video will be processed and reviewed before it is shown;
- an approved Video Introduction is not formal identity verification;
- the video cannot be deleted or replaced during the seven-day lock;
- the video can be hidden at any time;
- hiding retains the badge but prevents playback;
- deletion removes the badge.

### 5.3 Consent

Use an unchecked required consent control:

> I confirm that I have read and agree to the Video Introduction guidelines, privacy conditions and seven-day deletion restriction.

Consent must be enforced on both client and server and recorded with member ID, submission/version ID, consent version and UTC timestamp.

## 6. Member flow

1. Authenticated member opens Video Introduction.
2. Server loads the member's current video state and allowed actions.
3. Instructions, privacy conditions and consent are displayed.
4. Member accepts consent.
5. Browser requests camera and microphone permissions.
6. Member sees the live preview and optional prompts.
7. Member starts recording.
8. UI displays elapsed time and a visible remaining-time countdown.
9. Recording stops automatically at 30 seconds.
10. A recording under 15 seconds cannot be submitted.
11. Member previews the recording without autoplay.
12. Member chooses **Retake** or **Submit**.
13. Submission controls prevent duplicate requests.
14. UI shows upload progress.
15. Server validates ownership, consent, format, size, duration and media content.
16. Original video is stored privately in S3.
17. Database record and processing job are created safely.
18. Member sees confirmation and may leave the page.
19. Asynchronous processing creates playback and poster assets.
20. Successful processing moves the submission to the admin moderation queue.
21. Member is notified of relevant processing/moderation changes.

Success message after safe persistence:

> Your Video Introduction has been saved. Processing and moderation will continue in the background. You can safely leave this page.

## 7. Permission and recording error handling

The UI must handle:

- camera permission denied;
- microphone permission denied;
- no camera or microphone available;
- device already in use;
- unsupported browser/codec;
- permission revoked during recording;
- mobile interruption, navigation or incoming call;
- recording stopped before 15 seconds;
- browser tab closed during recording;
- upload failure or interrupted network;
- corrupted or undecodable recording.

Errors must be safe, actionable and consistent with existing UI patterns.

## 8. Server-side validation

Client validation exists for usability only. Server validation is authoritative.

Validate:

- authenticated member and ownership;
- member/account eligibility;
- CSRF protection;
- recorded consent;
- action allowed for the current lifecycle state;
- only one current/pending submission operation per member;
- idempotency/replay protection;
- actual MIME/content, not filename alone;
- allowed container and codec;
- video track exists;
- audio track exists;
- media is decodable;
- duration is between 15 and 30 seconds;
- configured maximum file size;
- configured resolution/dimensions;
- seven-day replacement/deletion lock;
- privacy choice is allowed for the member;
- object/database consistency.

The final maximum file size and supported codec matrix must be configuration-driven and finalized after testing recordings from supported Android, iPhone, Windows and macOS browsers.

## 9. Lifecycle

Moderation status and visibility are separate concepts.

Recommended lifecycle statuses:

- `UPLOADING`
- `PROCESSING`
- `PROCESSING_FAILED`
- `PENDING_REVIEW`
- `APPROVED`
- `REJECTED`
- `RESUBMISSION_REQUESTED`
- `REPLACING`
- `DELETED`
- `EXPIRED` for abandoned/incomplete uploads, if needed

Recommended transitions:

```text
UPLOADING -> PROCESSING -> PENDING_REVIEW
PROCESSING -> PROCESSING_FAILED -> PROCESSING (retry)
PENDING_REVIEW -> APPROVED
PENDING_REVIEW -> REJECTED
PENDING_REVIEW -> RESUBMISSION_REQUESTED
REJECTED/RESUBMISSION_REQUESTED -> PROCESSING (new version)
APPROVED -> REPLACING -> PROCESSING (new version)
APPROVED -> DELETED
```

Every transition must be validated atomically. Stale screens, duplicate requests and concurrent admin actions must not create invalid transitions.

## 10. Asynchronous processing

Processing starts only after the original object and required database state are safely persisted.

The background job must:

1. inspect and validate the original again;
2. read duration, codecs, dimensions and audio metadata;
3. create a web-optimised playback version;
4. generate a poster/thumbnail image;
5. store processed object keys and metadata;
6. move the record to `PENDING_REVIEW`;
7. notify the member of success/failure where appropriate.

Jobs must be retryable and idempotent. Retry must not create duplicate database records or orphaned S3 objects. A failed or non-playable video must not enter the moderation queue.

Queue/network calls must not hold long database transactions.

## 11. Storage and secure delivery

Follow the existing authorized media architecture:

```text
Authorized controller/service
  -> video/media service
  -> private S3 original and processed objects
  -> short-lived signed CloudFront access after viewer authorization
```

Requirements:

- keep S3 Block Public Access enabled;
- store original, processed video and poster privately;
- never store or return signed URLs as persistent database values;
- never expose raw S3 URLs;
- use non-guessable, versioned object keys;
- store object keys and metadata in the database;
- encrypt objects at rest;
- sign CloudFront access only after current viewer authorization;
- use short-lived signed access;
- revoke effective access quickly after hide/delete/block/privacy/plan changes;
- serve the web-optimised asset, never the original;
- do not show an explicit download action;
- do not autoplay, especially with sound.

The website cannot completely prevent downloading or screen recording. Secure delivery and short-lived authorization only reduce casual copying.

## 12. Moderation

Add a Video Introductions queue for authorized Admin/Super Admin roles.

The review screen should show:

- member ID and permitted member details;
- submission/version ID;
- submission date/time;
- duration and processing metadata;
- video player and poster;
- current privacy selection;
- consent record/version;
- previous submission and moderation history;
- current state;
- safe moderation controls.

Moderator actions:

### 12.1 Approve

- require confirmation;
- validate the current state;
- record moderator and UTC timestamp;
- make the badge/display eligibility active;
- apply the current allowed visibility setting;
- notify the member.

### 12.2 Reject

- require a member-facing reason;
- validate the current state;
- prevent profile playback and badge display;
- permit a new submission according to the finalized lock rule;
- apply retention;
- notify the member.

### 12.3 Request resubmission

Use for correctable issues such as poor audio, poor visibility, contact information, multiple people, irrelevant content or insufficient quality.

- require a member-facing reason;
- prevent public/member playback;
- allow resubmission according to the finalized lock rule;
- notify the member.

All moderation actions require an immutable audit history. Two moderators acting concurrently must not overwrite each other's decision.

## 13. Privacy and playback authorization

Visibility values:

- `VISIBLE_PLUS` — visible to eligible authenticated Plus viewers;
- `VISIBLE_AFTER_ACCEPTED_INTEREST` — visible only when interest is currently accepted;
- `HIDDEN` — no other member can play the video.

"Visible to all Plus profiles" does not mean public internet access.

### 13.1 Female member restriction

For female members:

- default is `VISIBLE_AFTER_ACCEPTED_INTEREST`;
- `VISIBLE_PLUS` is not selectable;
- `HIDDEN` remains available.

This restriction must be enforced server-side.

### 13.2 Authorization decision

Playback is allowed only when all relevant rules pass:

- video is `APPROVED`;
- video is not hidden/deleted;
- owner account/profile is active and visible;
- viewer is authenticated and eligible;
- required Plus entitlement is active;
- gender/match eligibility permits profile access;
- neither member has blocked the other;
- accepted interest currently exists when required;
- normal profile/media privacy allows the viewer;
- no administrative restriction applies.

Video privacy may add restrictions but must never bypass profile privacy.

If interest is withdrawn/reversed, a block is added, a Plus plan expires, the owner hides the video or the profile becomes unavailable, new playback authorization must stop immediately.

## 14. Badge rules

Use a trust badge such as **Video Introduction** or **Intro Video Available**.

Do not use identity claims such as **Identity Verified**, **Verified Identity** or **Live Verified**.

| Condition | Badge | Playback |
|---|---:|---:|
| Uploading/processing | No | Owner only where available |
| Pending review | No | Owner only |
| Approved and visible | Yes | Authorized viewers |
| Approved but hidden | Yes | No |
| Rejected | No | No |
| Resubmission requested | No | No |
| Deleted | No | No |

When an approved video is hidden, clicking the retained badge may show:

> This member has an approved Video Introduction but has currently hidden it from viewing.

The badge must be integrated into the existing four member profile presentation contexts rather than creating a fifth card/profile implementation.

## 15. Seven-day lock, hiding, replacement and deletion

Recommended rule:

- lock starts at the successful submission UTC timestamp;
- during seven days, the member cannot delete or replace that submitted version;
- the member may hide it immediately at any time;
- Admin/Super Admin safety removal, rejection, account deletion and legal/privacy action override the lock;
- after seven days, an eligible member may replace or delete it.

### 15.1 Replacement

Recommended behavior:

- keep the existing approved video active while a replacement is processed/moderated;
- create a new version rather than overwriting the active object/record;
- allow the member to hide the active version while replacement is pending;
- atomically activate the replacement only after approval;
- retire the previous approved version after replacement approval;
- retain the previous approved version if the replacement is rejected.

### 15.2 Deletion

Deletion must:

- require confirmation;
- revalidate ownership and lock status;
- mark the video unavailable immediately;
- revoke new playback access;
- remove the badge immediately;
- queue physical asset removal;
- retain only permitted audit metadata.

## 16. Notifications

Use the existing notification architecture. Notifications may be required for:

- video safely saved;
- processing completed/failed;
- pending moderation;
- approved;
- rejected;
- resubmission requested;
- replacement approved/rejected;
- deleted or administratively removed.

Retries must not send duplicate notifications.

## 17. Retention

Initial recommended policy:

| State/asset | Retention |
|---|---:|
| Active approved video | Until replaced/deleted/account policy applies |
| Rejected video | 7–14 days |
| Replaced video | 7 days after replacement |
| Processing failure | 3–7 days |
| Abandoned upload | 24 hours |
| Deleted physical assets | Remove asynchronously within 24 hours |
| Audit metadata | Existing audit/privacy policy |

Final retention periods must be configuration-driven, documented in the privacy notice and approved before implementation.

## 18. Data and integrity expectations

Implementation should provide:

- one member-to-many immutable video versions;
- at most one active approved version per member;
- at most one current processing/moderation replacement operation per member;
- separate moderation status and visibility;
- consent version/timestamp;
- original, processed and poster object keys;
- measured metadata such as duration, codec, size and dimensions;
- lock timestamps;
- moderator action history;
- timestamps in UTC;
- constraints/indexes preventing invalid duplicates and orphan records.

Database evolution must use the next immutable numbered PostgreSQL script under `database/` and the `deployment_sql_history` ledger. Do not modify the baseline for a later feature.

## 19. Security and abuse requirements

- enforce authentication, role authorization and ownership server-side;
- prevent IDOR on member, video and moderation endpoints;
- protect state-changing browser requests with CSRF;
- reject hidden/browser-controlled lifecycle or privacy decisions that are not currently allowed;
- escape all displayed member/admin content;
- do not log raw video URLs, signing credentials or unnecessary personal information;
- use rate limits/idempotency for recording submission and moderation actions;
- scan/validate actual media before moderation/playback;
- never let approval alone bypass viewer authorization;
- provide a viewer report path for inappropriate video content;
- support urgent administrative takedown;
- audit access-sensitive state changes.

## 20. Important corner cases

### Recording/upload

- unsupported browser-generated codec/container;
- camera allowed but microphone denied;
- incoming call/app switch pauses mobile recording;
- browser reports incorrect/missing duration;
- valid-looking extension with invalid media content;
- video exists but audio is silent/missing;
- network fails after S3 upload but before client response;
- database succeeds while queue creation fails;
- upload succeeds twice from retries/multiple tabs;
- processing job is stuck or runs twice;
- orphaned original/processed objects;
- member closes the page immediately after upload.

### Moderation/concurrency

- two admins review the same version;
- member changes privacy while admin reviews;
- account is suspended/deleted during moderation;
- admin reviews a superseded version;
- approval must be reversed after a safety report;
- contact details appear visually but are not spoken;
- another person/private information is visible in the background;
- copyrighted television/music is audible.

### Privacy/access

- signed URL copied before the owner hides the video;
- owner blocks viewer during playback;
- accepted interest is later withdrawn/reversed;
- viewer's Plus entitlement expires;
- cached badge remains after deletion;
- owner changes profile privacy;
- owner or viewer account becomes inactive.

### Lock/replacement/deletion

- admin rejection occurs during the seven-day lock;
- account deletion occurs during the lock;
- urgent privacy/safety removal is required;
- replacement is submitted while another replacement is pending;
- previous approved video state when replacement fails;
- UTC/date-boundary handling.

## 21. Acceptance criteria

The feature is ready for QA when:

1. All user-visible selfie feature references have been removed/replaced.
2. Member must accept recorded consent before permissions/recording.
3. Supported browsers can record video and audio directly.
4. Countdown and automatic 30-second stop work.
5. Videos shorter than 15 seconds or longer than 30 seconds cannot be submitted.
6. Member can preview, retake and submit.
7. Client and authoritative server validation are consistent.
8. Original and derived assets remain private.
9. Processing continues asynchronously after the member leaves.
10. Failed processing is retryable and does not enter moderation.
11. Admin can approve, reject or request resubmission with audit history.
12. Approved playback follows profile, plan, interest, gender, block and video privacy rules.
13. Female members cannot select `VISIBLE_PLUS`.
14. Member can hide an approved video immediately.
15. Seven-day replacement/deletion rules are enforced server-side.
16. Replacement uses immutable versioning and preserves the previous approved video until the replacement is approved.
17. Badge state follows the documented lifecycle.
18. Delete revokes access and removes the badge.
19. Raw S3 URLs and original video objects are never exposed.
20. Notifications are idempotent.
21. Retention cleanup is scheduled and auditable.
22. The feature is reviewed under Requirement, Code, UI, Validation, Database, Security and Regression QA.
23. The final QA Gate is not marked PASSED without executed evidence.

## 22. Decisions required before implementation

The following items remain explicit product/technical decisions and must not be silently assumed:

1. Confirm whether seven days starts at submission or approval. Recommendation: submission.
2. Confirm whether rejection/resubmission overrides the member's replacement lock. Recommendation: allow correction after moderator decision.
3. Define whether Plus entitlement is required for the viewer only, owner only or both. Recommendation: viewer eligibility controls viewing.
4. Approve final browser/codec support and maximum upload size after device testing.
5. Approve exact rejected/replaced/deleted retention periods.
6. Select the final badge label.
7. Define moderation SLA and member-facing wording.
8. Confirm whether automated transcription/on-screen text detection will be part of version one or a later moderation aid.
9. Define how legacy selfie records and stored assets will be retired and retained.
10. Confirm whether playback view events require a dedicated audit/analytics record.
