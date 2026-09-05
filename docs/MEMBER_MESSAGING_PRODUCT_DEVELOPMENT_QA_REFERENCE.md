# Member Messaging — Product, Development and QA Reference

## 1. Purpose

This document is the product source of truth for member-to-member messaging in SikhanandKaraj. It is intended to be used jointly by Product, Development and QA.

Messaging exists to enable controlled matrimonial conversation after discovery while protecting privacy, preventing harassment and creating meaningful Paid membership value. It must not become an unrestricted social-chat system.

Core journey:

```text
Discovery -> Interest -> Conversation -> Contact / Next Step
```

Messaging is gender-independent. Permissions depend on membership entitlement, relationship/interest state, account state and trust/safety state — never gender alone.

Existing profile-visibility, female-profile-access, contact-privacy, blocking, reporting and membership rules remain authoritative. Messaging must never be used to bypass them.

---

## 2. Product Principles

1. Free members can receive and read member messages, but cannot manually send or reply.
2. Paid members can manually send and reply according to plan limits.
3. Paid members can message Free or Paid members.
4. Free members can send Interests under the existing Interest entitlement; an Interest automatically creates a conversation and a system-generated Interest message.
5. An Interest-generated message is not presented as text personally written by the sender.
6. Safety limits apply to every Paid plan, including the highest plan.
7. Higher payment must never buy permission to harass, spam or bypass a block/rejection.
8. Declining an Interest closes messaging for that relationship.
9. Blocking immediately prevents communication in both directions.
10. Admin moderation preserves an audit trail; messages are moderated/removed, not destructively erased.
11. V1 is text-only.
12. Server-side entitlement and safety enforcement is mandatory; hiding/disabling UI is not authorization.

---

## 3. Membership Terminology

The current authoritative membership order is:

```text
FREE < GO < PLUS < PRO
```

Existing plan durations and other membership entitlements remain defined in `docs/MEMBERSHIP_AND_MATCHING_RULES.md`.

For messaging, the proposed V1 limits are:

| Messaging Feature / Limit | Free | Go | Plus | Pro |
|---|---:|---:|---:|---:|
| Receive messages | Yes | Yes | Yes | Yes |
| Read messages | Yes | Yes | Yes | Yes |
| Manual reply | No | Yes | Yes | Yes |
| Manually start conversation | No | Yes | Yes | Yes |
| New manual conversations/day | 0 | 5 | 10 | 20 |
| Messages to same member/day | 0 | 10 | 15 | 25 |
| Total outgoing manual messages/day | 0 | 25 | 50 | 100 |
| Consecutive unanswered outgoing messages | 0 | 3 | 3 | 3 |
| Send Interest | Existing rule: Unlimited | Unlimited | Unlimited | Unlimited |
| Interest-generated system message | Yes | Yes | Yes | Yes |

These values should be centrally configurable/authoritative rather than scattered through controllers/views. Product may tune commercial limits after observing real usage without changing the safety principle.

### 3.1 Why three separate limits exist

#### New manual conversations/day

Controls how many different members a Paid member can newly approach manually in a day.

Example: Go has a limit of 5. A member manually messages five previously unmessaged profiles. They may continue existing conversations, but cannot manually initiate a sixth new conversation that day.

This controls mass solicitation.

An automatic Interest-generated system message does **not** consume this limit.

#### Messages/member/day

Controls how many outgoing manual messages Member A can send Member B during the daily window.

Example: Plus allows 15 messages/member/day. Even if the member has total daily capacity remaining, message 16 to the same recipient is rejected until the next applicable daily window.

This protects an individual recipient.

#### Total outgoing messages/day

Controls all manually written outgoing messages across all conversations.

Example for Go (25/day):

```text
A: 8 messages
B: 5 messages
C: 4 messages
D: 5 messages
E: 3 messages
Total = 25
```

The next outgoing manual message is not allowed that day even if a per-recipient limit has not been reached.

This controls overall platform activity and automated/spam-like use.

### 3.2 Universal unanswered-message rule

Every Paid plan has a maximum of **3 consecutive unanswered manual messages** to the same recipient.

Example:

```text
A -> B: Sat Sri Akal.
A -> B: I liked your profile and would like to connect.
A -> B: Please let me know if you would like to talk.
B has not replied.
A attempts a fourth message -> BLOCKED.
```

Suggested UI:

> You have already sent messages to this member. Please wait for them to respond.

A valid manual reply from B resets A's consecutive-unanswered counter for that conversation.

This is a safety rule, not a commercial entitlement, and must not increase with plan price.

---

## 4. Sender/Receiver Behaviour Matrix

| Sender | Receiver | Send Interest | Automatic Interest Message | Manual Message | Receiver Manual Reply |
|---|---|---:|---:|---:|---:|
| Free | Free | Yes | Yes | No | No |
| Free | Paid | Yes | Yes | No | Yes |
| Paid | Free | Yes | Yes | Yes | No while Free |
| Paid | Paid | Yes | Yes | Yes | Yes |

A Free member receiving a Paid member's message can read it. The conversation must then show a contextual membership CTA instead of a working composer.

Suggested text:

> Want to continue this conversation? Upgrade your membership to reply.

This is an intentional Free-to-Paid conversion journey.

---

## 5. Interest Integration

### 5.1 Sending an Interest

A successful Interest action must create/reuse the member pair's appropriate conversation and append exactly one system-generated Interest event/message.

Example:

```text
[Interest]
SAK123456 has expressed interest in your profile.
```

The exact customer-facing template is controlled by Superadmin.

The UI must visually distinguish this from a manually authored member message, for example with an `Interest` or `System` treatment.

### 5.2 Accounting

Interest-generated messages do not consume:

- new manual conversation/day limit;
- messages/member/day limit;
- total outgoing manual messages/day limit;
- consecutive unanswered-message limit.

Existing Interest entitlements remain authoritative.

### 5.3 Idempotency

Duplicate browser submissions, retries or direct endpoint calls must not create duplicate Interests, conversations or duplicate Interest-generated messages for the same Interest event.

### 5.4 Interest Accepted

Append a system event such as:

```text
Interest Accepted
```

Messaging then continues according to current membership and safety rules.

### 5.5 Interest Declined

Declining an Interest closes messaging for that relationship. Existing history remains visible/readable as permitted, but no member can send another message in that closed relationship.

Suggested state:

> This conversation is closed because the Interest was declined.

No upgrade CTA should imply that payment can override a rejection.

---

## 6. Core User Journeys

### Journey A — Free -> Free

1. Free A sends Interest to Free B.
2. System Interest message is created.
3. B can read it.
4. Neither A nor B can manually message while Free.
5. Upgrade messaging may be shown where appropriate.

### Journey B — Free -> Paid

1. Free A sends Interest.
2. Paid B receives the Interest system message.
3. Paid B manually replies.
4. Free A receives and reads the reply.
5. A cannot reply while Free.
6. A sees `Upgrade to reply`.
7. If A upgrades and relationship/safety rules permit, composer becomes available immediately.

### Journey C — Paid -> Free

1. Paid A opens Free B's permitted card/profile and starts a conversation or sends Interest.
2. B receives and reads the message.
3. B cannot manually reply while Free.
4. B sees contextual upgrade CTA.
5. If B upgrades, B can continue the existing conversation without losing history.

### Journey D — Paid -> Paid

Normal two-way messaging subject to all limits and relationship/safety state.

### Journey E — Interest Declined

1. Conversation exists.
2. Recipient declines Interest.
3. System event records decline/closed state as appropriate.
4. Composer becomes unavailable to both members.
5. Direct API attempts to send must also fail.

### Journey F — Block

1. A blocks B.
2. Messaging stops immediately in both directions.
3. Existing history is preserved for audit/moderation.
4. UI displays blocked state.
5. Direct endpoint attempts are rejected.

### Journey G — Membership Expiry

1. Paid A has an active conversation.
2. Membership expires.
3. Existing messages remain readable.
4. Incoming messages can be received/read subject to account/safety state.
5. Manual sending/reply becomes unavailable immediately.
6. Composer becomes renewal CTA.

### Journey H — Upgrade During Conversation

1. Free member receives a message.
2. Free member clicks upgrade CTA.
3. Membership becomes active through the authoritative membership flow.
4. User should return to or be able to continue the same conversation.
5. Composer becomes available immediately if no block/decline/suspension/limit prevents it.

---

## 7. Messaging Entry Points

A Message icon/action should be visible consistently on:

- Profile Card;
- Interest Card;
- Full Member/Profile View.

Paid member: Message opens/creates the conversation if permitted.

Free member: keep the Message affordance visible for feature discovery, but manual send is unavailable. Suggested explanation:

> Messaging is available with membership. You can receive and read messages from members. Upgrade to start conversations and reply.

Actions:

- View Plans
- Cancel

The UI must not imply that upgrading overrides blocks, declined Interests, suspended accounts or privacy rules.

---

## 8. Main Messages Screen — UX/UI Reference

Add a primary member navigation entry:

```text
Messages (3)
```

where the badge represents unread conversation/message state according to the final unread definition.

### 8.1 Desktop layout

Use a responsive two-pane layout:

```text
+------------------------------------------------------------------+
| Messages                                          [ Search ]      |
+------------------------+-----------------------------------------+
| Conversations          | Harpreet Kaur              SAK123456   |
| [All] [Unread]         | Kota, Rajasthan                         |
|                        | Verified badges                         |
| Harpreet Kaur      2   | [View Profile]                    [...] |
| SAK123456              +-----------------------------------------+
| Thanks for...          |                                         |
| 10:42 AM               |      [Interest]                         |
|                        |      Interest sent                      |
| Jasleen Kaur           |                                         |
| SAK234567              |      Hello, I went through your         |
| Sounds good...         |      profile...              10:20 AM   |
| Yesterday              |                                         |
|                        |      Thank you. I would like to know    |
|                        |      more about your family.            |
|                        +-----------------------------------------+
|                        | Stay safe: do not share OTPs, financial |
|                        | information or sensitive documents.     |
|                        |                                         |
|                        | [ Write a message...          ] [Send]  |
+------------------------+-----------------------------------------+
```

### 8.2 Mobile layout

Mobile should use sequential navigation:

```text
Messages list -> tap conversation -> Conversation screen -> Back to Messages
```

Do not squeeze both panes side-by-side on small screens.

### 8.3 Conversation list item

Display:

- profile photo subject to existing photo-visibility rules;
- display name subject to existing name-masking rules;
- Profile ID;
- latest message preview;
- timestamp;
- unread indicator/count.

Search should initially support Name and Profile ID.

Initial filters:

- All
- Unread

Avoid excessive V1 filters.

### 8.4 Conversation header

Display enough identity context to avoid confusion:

- permitted profile image;
- permitted display name;
- Profile ID;
- age where existing rules permit;
- city/country;
- applicable verification badges;
- View Profile action;
- three-dot menu for Block/Report.

Messaging must not reveal mobile/email/Aadhaar/private profile data merely because a conversation exists.

### 8.5 Composer

V1 supports plain text only.

Recommended length:

```text
1–500 characters
```

Show character counter, e.g. `124 / 500`.

Validate client-side for usability and server-side authoritatively.

Do not support in V1:

- image attachments;
- PDF/document attachments;
- Aadhaar/document sharing;
- voice notes;
- video attachments;
- video calls;
- arbitrary files;
- reactions;
- disappearing messages;
- typing indicator;
- online/last-seen indicator;
- group/family chat.

### 8.6 Safety guidance

Show a compact persistent warning near the composer:

> Stay safe: Avoid sharing OTPs, financial information, Aadhaar details or sending money. Report suspicious behaviour.

For phone/email-like content, V1 may warn rather than hard-block unless Product later defines a stricter policy:

> For your privacy, consider keeping conversations on SikhanandKaraj until you are comfortable sharing contact information.

Existing contact/privacy rules remain separate from messaging.

### 8.7 Message states

Recommended V1 member-visible states:

- Sent
- Read

Do not add online presence or last-seen status in V1.

### 8.8 Free/expired composer state

Replace the text composer rather than merely disabling a textbox.

Free:

> Want to continue this conversation? Upgrade your membership to reply.

Expired:

> Your membership has expired. Renew your membership to continue this conversation.

Actions should route into the existing membership-plan flow.

### 8.9 Closed/blocked states

Declined:

> This conversation is closed because the Interest was declined.

Blocked by current member:

> You have blocked this member.

Do not show an upgrade CTA for a relationship closed for safety/rejection reasons.

---

## 9. Empty States

### No conversations

> Your conversations will appear here. Send an Interest or explore profiles to start connecting.

Action: `Explore Matches`

### No unread conversations

> You're all caught up. You have no unread messages.

### Free member with received conversation

Show conversation normally, but replace composer with upgrade CTA.

---

## 10. Notifications

V1 should support an in-app unread indicator.

External notifications, when enabled through existing communications infrastructure, should preserve privacy. SMS/email should not contain full private message content by default.

Example:

> You have a new message from SAK123456.

Avoid one SMS/email per rapid message burst; notification bundling/throttling should be considered operationally.

---

## 11. Trust, Safety and Abuse Prevention

### 11.1 Required server-side checks before every manual send

At minimum validate:

1. sender is authenticated and active;
2. recipient exists and is eligible for interaction;
3. sender has current manual-send entitlement;
4. relationship/conversation is not closed by declined Interest;
5. neither applicable block state prevents messaging;
6. neither account is suspended/deactivated in a way that prevents messaging;
7. new-conversation limit if this is a new manual conversation;
8. per-recipient daily limit;
9. total outgoing daily limit;
10. consecutive unanswered limit;
11. message length/content validation;
12. any moderation restriction;
13. race/concurrency-safe revalidation before persistence.

### 11.2 Abuse patterns to detect/log

- one message sent to a very large number of profiles;
- identical/near-identical text sent repeatedly;
- rapid automated sending;
- repeated phone/email/URL solicitation;
- money/payment requests;
- repeated reports from independent members;
- attempts to message after decline/block;
- attempts to bypass limits through direct endpoints/concurrent requests.

Automated detection can mature after V1, but the data model/logging should not prevent later moderation analytics.

### 11.3 Safety limits are universal

Higher plans may have larger commercial usage allowances but must not receive relaxed block, decline or unanswered-message safety rules.

---

## 12. Report Message

Each manually authored message should provide an action such as:

```text
... -> Report message
```

Suggested reasons:

- Harassment or abusive language
- Asking for money
- Fake/suspicious identity
- Sharing inappropriate content
- Repeated unwanted contact
- Spam
- Other

A message report should capture enough immutable context for Admin review, including reporter, reported member, conversation, message reference and timestamp.

Reporting must not silently destroy the message under review.

---

## 13. Blocking

Blocking is a safety capability and overrides Paid entitlement.

On block:

- stop new manual messages;
- stop replies;
- disable/replace composer;
- prevent messaging through direct URL/API;
- preserve historical messages for audit/moderation;
- continue to respect the existing platform-wide blocked-member behaviour.

Unblocking must not automatically undo an Interest decline or other independent restriction.

---

## 14. Admin Experience

### 14.1 Admin Member View

Add a Messaging section to the existing Admin Member View.

Suggested summary:

```text
Messaging Activity

Conversations             14
Messages Sent              67
Messages Received          54
Unread                      3
Reported Messages           1
Admin Removed               0

[ View Conversations ]
```

Conversation list should show enough moderation context, for example:

```text
Conversation with SAK123456
Status: Active
Started: 03 Sep 2026
Interest: Accepted
Messages: 67
[View Conversation]
```

### 14.2 Admin privacy principle

Private-message content should not be treated as casual profile metadata. Admin access should be permission-controlled and auditable.

Recommended audit fields:

- Admin ID;
- Member ID/context;
- Conversation ID;
- access timestamp;
- optional/required access reason depending on future role policy.

### 14.3 Admin message removal

Use **Remove/Moderate Message**, not destructive delete.

Reason is mandatory.

Suggested reasons:

- Personal/sensitive information
- Abusive content
- Financial solicitation
- Spam
- Inappropriate content
- Safety violation
- Other

Member-visible replacement:

> This message was removed by SikhanandKaraj moderation.

Internally preserve original content, original sender/recipient reference, Admin ID, reason and moderation timestamp for audit/dispute handling.

### 14.4 Member deletion behaviour

V1 should not support `Delete for everyone`.

A future `Hide conversation from my inbox` feature may be considered, but must not erase moderation/audit records.

---

## 15. Superadmin Configuration

Messaging policy should eventually be centrally configurable.

Recommended settings:

- Messaging globally enabled/disabled;
- Interest system-message template;
- maximum message length;
- per-plan new manual conversations/day;
- per-plan messages/member/day;
- per-plan total outgoing messages/day;
- maximum consecutive unanswered messages;
- safety-warning text.

Configuration changes must not retroactively erase history. Entitlement checks should use current effective configuration unless Product explicitly versions a rule.

The Interest template must be clearly system-generated even if Superadmin changes its wording.

---

## 16. Edge and Corner Cases

### 16.1 Membership changes

**Paid expires while conversation is open:** next send must re-check entitlement server-side and fail safely; UI refreshes to renewal state.

**Free upgrades while conversation is open:** after authoritative activation, manual messaging becomes available without creating a duplicate conversation.

**Plan upgrades/downgrades:** current effective plan determines subsequent limits. Historical messages remain unchanged.

**Admin manually activates/deactivates membership:** same authoritative entitlement path applies; do not maintain a separate messaging-only paid flag.

### 16.2 Limit boundaries

- exactly at 5/10/20 new-conversation boundary;
- exactly at per-member message boundary;
- exactly at total daily boundary;
- third unanswered message succeeds; fourth fails;
- recipient replies after third; sender can send again subject to other limits;
- concurrent tabs attempt messages at the final remaining allowance;
- retried HTTP request must not double-count/double-send;
- define one authoritative timezone/day-boundary policy and use it consistently in server logic, UI messaging and QA tests.

### 16.3 Interest states

- Interest send succeeds but notification fails: Interest/message transaction must remain internally consistent and notification can retry independently;
- duplicate Interest click/request does not duplicate system message;
- Interest accepted after messages already exist;
- Interest declined while sender has composer open: subsequent send fails server-side and UI refreshes closed state;
- Interest status changed from another tab/device;
- Interest-generated message never consumes manual quota.

### 16.4 Block/report races

- B blocks A while A has composer open;
- A sends at same time B blocks;
- block must win according to transaction/authoritative ordering and no later message may bypass it;
- report does not itself destroy evidence;
- unblock does not automatically reopen a declined relationship.

### 16.5 Account lifecycle

- recipient deactivated;
- sender deactivated;
- account suspended by Admin;
- member/profile deleted/archived;
- profile visibility changed;
- verification status changes;
- photo visibility changes after conversation started.

Conversation UI must always render current permitted identity/photo information and must not cache previously visible private data as a bypass.

### 16.6 Content validation

- empty/whitespace-only message;
- 1 character;
- exactly 500 characters;
- 501 characters;
- Unicode/Gurmukhi/emoji;
- HTML/script input rendered safely as text;
- URLs;
- phone/email patterns;
- line breaks;
- repeated copy/paste content;
- malicious payloads/SQL-like strings;
- very fast repeated submission.

### 16.7 Read/unread

- sender opens own sent message: must not mark recipient read;
- recipient opens conversation: read state updates according to defined rule;
- multiple devices/tabs;
- Admin viewing a conversation must not mark member messages read;
- removed/moderated messages and system events should have explicitly defined unread behaviour;
- unread navigation badge must remain consistent with conversation list.

### 16.8 Admin moderation

- Admin removes already reported message;
- second Admin attempts to remove same message;
- removal reason mandatory server-side;
- original content remains available only to authorized moderation/audit path;
- member sees placeholder, not original removed content;
- Admin viewing must be audited;
- normal Admin permissions must not automatically equal Superadmin permissions unless explicitly configured.

### 16.9 Privacy

- messaging does not expose hidden mobile/email;
- messaging does not expose Aadhaar data;
- name masking remains consistent with existing rules;
- profile/photo visibility remains authoritative;
- SMS/email notifications do not leak private message body;
- Admin message access is not exposed to unauthorized roles.

---

## 17. UX Copy Reference

### Free manual-send attempt

> Messaging is available with membership. You can receive and read messages from members. Upgrade to start conversations and reply.

### Free received-message composer

> Want to continue this conversation? Upgrade your membership to reply.

### Expired membership

> Your membership has expired. Renew your membership to continue this conversation.

### Unanswered-message limit

> You have already sent messages to this member. Please wait for them to respond.

### Daily new-conversation limit

> You've reached today's limit for starting new conversations. You can continue your existing conversations.

### Per-member daily limit

> You've reached today's messaging limit with this member. You can continue later.

### Total daily limit

> You've reached today's messaging limit. Please continue later.

### Interest declined

> This conversation is closed because the Interest was declined.

### Blocked

> You have blocked this member.

### Admin removed message

> This message was removed by SikhanandKaraj moderation.

### Safety guidance

> Stay safe: Avoid sharing OTPs, financial information, Aadhaar details or sending money. Report suspicious behaviour.

---

## 18. Development Behavioural Requirements

This document does not prescribe implementation code, but implementation must preserve these architecture principles:

1. Use the existing authoritative membership model; do not introduce a parallel `is_paid` messaging flag.
2. Centralize messaging entitlement/limit decisions rather than duplicating rules in views/controllers.
3. Every write endpoint independently enforces authorization, relationship state, safety state and limits.
4. Quota accounting must be concurrency-safe.
5. Interest/message creation must be idempotent where retries are possible.
6. Admin moderation is auditable and non-destructive.
7. Existing block/report/profile-privacy rules are reused rather than recreated inconsistently.
8. UI state is derived from authoritative backend capability/state.
9. Store enough structured event information to distinguish system Interest events from member-authored messages.
10. Design indexes/query patterns for conversation list, unread counts, message pagination, moderation lookup and quota enforcement.
11. Use pagination; do not load complete long conversations into memory/DOM indefinitely.
12. Avoid exposing raw internal IDs where Profile ID is the intended customer/admin identifier.
13. Follow existing SikhanandKaraj DB deployment rules for schema changes.

---

## 19. QA Reference Matrix

QA must test UI and direct endpoint/API access. UI hiding is never sufficient evidence of authorization.

### Membership combinations

Test all:

- Free -> Free
- Free -> Go/Plus/Pro
- Go/Plus/Pro -> Free
- Paid -> same Paid plan
- Paid -> different Paid plan

### State combinations

Test:

- no Interest;
- Interest sent;
- Interest accepted;
- Interest declined;
- blocked by sender;
- blocked by recipient;
- reported;
- active account;
- expired membership;
- upgraded membership;
- suspended/deactivated account;
- limit just below threshold;
- exactly at threshold;
- above threshold.

### Security/negative tests

- Free manually POSTs a message;
- expired member manually POSTs;
- sender changes recipient/conversation ID in request;
- member attempts to read conversation not belonging to them;
- member attempts to send into another pair's conversation;
- message after block;
- message after decline;
- quota bypass using concurrent requests;
- duplicate request/replay;
- XSS/HTML injection;
- CSRF according to existing application protection;
- unauthorized Admin message access;
- Admin removal without reason;
- client validation bypass.

---

## 20. Acceptance Criteria

The V1 feature is accepted only when all applicable criteria below are met:

1. Messaging entitlement is gender-independent.
2. Free members can receive/read messages but cannot manually send/reply.
3. Paid members can manually message Free and Paid members subject to rules.
4. Every successful Interest produces exactly one corresponding system Interest message/event.
5. Interest-generated messages are visibly distinguishable from member-authored messages.
6. Interest-generated messages do not consume manual messaging quotas.
7. Go supports proposed 5 new conversations/day, 10 messages/member/day, 25 total outgoing/day.
8. Plus supports proposed 10 new conversations/day, 15 messages/member/day, 50 total outgoing/day.
9. Pro supports proposed 20 new conversations/day, 25 messages/member/day, 100 total outgoing/day.
10. All Paid plans enforce maximum 3 consecutive unanswered messages.
11. Limits are enforced server-side and are concurrency-safe.
12. Existing conversations remain replyable after new-conversation quota is reached, subject to other limits.
13. Block immediately prevents new communication regardless of plan.
14. Interest decline closes messaging and cannot be bypassed by upgrade/payment.
15. Existing profile/contact/privacy rules cannot be bypassed through messaging.
16. Message entry points exist consistently on Profile Card, Interest Card and Full Member View.
17. Dedicated Messages screen supports responsive conversation list and conversation view.
18. Free/expired/blocked/declined states show correct non-composer UI.
19. V1 messages are text-only and enforce the defined length server-side.
20. Safety warning is displayed near the composer.
21. Report Message captures the relevant message/conversation context.
22. Admin Member View exposes messaging summary and authorized conversation review.
23. Admin message-content access is auditable.
24. Admin removal requires a reason and preserves original evidence internally.
25. Removed messages display a moderation placeholder to members.
26. Membership expiry removes manual-send entitlement without deleting history.
27. Membership upgrade enables eligible messaging without creating duplicate conversations/history.
28. Unread counts/states are consistent across navigation and conversation list.
29. Admin viewing does not mark member messages as read.
30. Duplicate/retried requests do not duplicate messages or quota consumption.
31. Unauthorized users cannot read/send into conversations belonging to other members.
32. Message content is safely escaped/rendered and cannot execute HTML/script.
33. Long conversation history is paginated.
34. Notifications do not expose sensitive/private message content by default.
35. Account suspension/deactivation is respected on every send attempt.

---

## 21. V1 Scope

### Included

- text-only one-to-one member messaging;
- Interest-generated system message;
- Free receive/read;
- Paid send/reply;
- Paid -> Free and Paid -> Paid messaging;
- plan usage limits;
- universal unanswered-message safety limit;
- unread state;
- responsive Messages screen;
- message entry points on relevant profile/card views;
- block/report integration;
- message reporting;
- Admin member messaging summary/review;
- auditable Admin moderation/removal;
- safety guidance;
- membership upgrade/expiry behaviour.

### Explicitly deferred

- attachments;
- images/files/documents;
- voice notes;
- video messaging/calls;
- typing indicators;
- online/last-seen presence;
- reactions;
- delete-for-everyone;
- disappearing messages;
- group/family conversations;
- sophisticated AI/content moderation unless separately approved;
- unrestricted external contact-information exchange controls beyond the V1 warning policy.

---

## 22. Product Metrics to Monitor After Launch

Product should monitor at least:

- conversations created via Interest vs manual messaging;
- Free members receiving messages;
- Free -> Paid conversion after receiving a message;
- reply rate;
- accepted-Interest conversation rate;
- average manual messages per active conversation;
- percentage of members hitting each commercial limit;
- percentage hitting 3-unanswered safety limit;
- blocks/reports per 1,000 conversations;
- message moderation/removal rate;
- Paid-member complaints about limits;
- unread-message age;
- messaging-related membership renewal conversion.

Limits should be adjusted based on evidence rather than assumptions, while universal safety rules remain conservative.

---

## 23. Product Decision Summary

The SikhanandKaraj messaging model is intentionally asymmetric for Free members:

```text
FREE
Discover + Interest + Receive + Read
                    |
                    v
             Upgrade to Reply
                    |
                    v
PAID
Start Conversations + Reply + Continue
```

Paid membership increases legitimate conversation capacity. It never overrides privacy, rejection, blocking or safety controls.

The highest plan should be marketed around generous/unrestricted conversation access only where commercially appropriate, but the backend must always retain fair-use and anti-abuse ceilings. In V1 the authoritative proposed Pro ceilings are 20 new manual conversations/day, 25 messages/member/day and 100 total outgoing manual messages/day, with the same 3-unanswered-message rule used by all Paid plans.

This document should be updated whenever Product changes messaging behaviour so Product, Development and QA continue to work from one shared reference.