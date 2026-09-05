# Member Messaging — Product, Development and QA Reference

## 1. Purpose and Source-of-Truth Rule

This document is the **single authoritative Product, Development and QA reference** for member-to-member messaging in SikhanandKaraj.

Messaging is a controlled matrimonial conversation layer, not unrestricted social chat:

```text
Discovery -> Interest -> Conversation -> Contact / Next Step
```

Messaging is gender-independent. Entitlement depends on membership, relationship/Interest state, account state and trust/safety state. Existing profile visibility, female-profile access, contact privacy, blocking, reporting and membership rules remain authoritative and cannot be bypassed through messaging.

The wider membership/profile-access source of truth remains `docs/MEMBERSHIP_AND_MATCHING_RULES.md`. If Product changes messaging behaviour, this document must be updated in the same change.

---

## 2. Product Principles

1. Free members can receive and read member messages but cannot manually send or reply.
2. Paid members can manually send/reply according to their plan limits.
3. Paid members can message Free or Paid members.
4. Free members can send Interests under the existing Interest entitlement; successful Interest creation creates/reuses a conversation and adds one system-generated Interest event/message.
5. Interest-generated text must be visually identified as a system/Interest event, never disguised as text personally written by the sender.
6. Higher payment increases legitimate conversation capacity but never buys permission to harass, spam, override rejection, bypass privacy or bypass a block.
7. Declined Interest closes messaging for the relationship.
8. Blocking immediately stops communication in both directions.
9. Admin moderation is auditable and non-destructive.
10. V1 is text-only.
11. Server-side enforcement is authoritative; hidden/disabled UI is not authorization.
12. Commercial plan limits and trust/safety limits are different concepts. Safety rules apply to every plan, including Pro.

---

## 3. Membership Messaging Matrix and Limits

The current membership hierarchy is:

```text
FREE < GO < PLUS < PRO
```

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

Values must be centralized/authoritative, not independently hard-coded in multiple controllers/views. Existing Interest rules remain authoritative.

### 3.1 New manual conversations/day

This is the number of **different members newly approached manually** during the applicable day.

Example: Go can manually initiate conversations with 5 previously unmessaged members. After reaching 5, the member cannot manually initiate a sixth that day, but can continue existing conversations subject to the other limits.

An Interest-generated system event does not consume this limit.

### 3.2 Messages/member/day

This limits outgoing manual messages from A to the same B during the applicable day.

Example: Plus allows 15. Message 16 to B is rejected even when A still has total daily capacity.

### 3.3 Total outgoing messages/day

This limits all outgoing manual messages across all conversations.

Example for Go:

```text
A: 8
B: 5
C: 4
D: 5
E: 3
Total = 25
Next outgoing manual message -> not allowed that day
```

### 3.4 Universal 3-unanswered-message rule

Every Paid plan has a maximum of **3 consecutive unanswered manual messages to the same recipient**.

```text
A -> B: Message 1
A -> B: Message 2
A -> B: Message 3
B has not replied
A -> B: Message 4 -> BLOCKED
```

Suggested copy:

> You have already sent messages to this member. Please wait for them to respond.

A valid manual reply from B resets A's consecutive-unanswered counter. This is a safety rule and must not increase by plan.

### 3.5 Commercial wording vs safety ceilings

Pricing should sell capability, not anti-harassment thresholds:

| Plan | Suggested positioning |
|---|---|
| Free | Receive and read messages |
| Go | Start conversations and reply |
| Plus | More conversations and replies |
| Pro | Highest / generous conversation access |

Do not market per-recipient or unanswered-message safety limits as premium benefits. If Product later uses wording such as `Unlimited Messaging`, it means commercially generous access within intended use and remains subject to fair-use, rate limits, block, decline and anti-abuse enforcement. No plan is technically unlimited from a trust/safety perspective.

---

## 4. Sender/Receiver Behaviour

| Sender | Receiver | Interest | Interest System Message | Manual Message | Receiver Manual Reply |
|---|---|---:|---:|---:|---:|
| Free | Free | Yes | Yes | No | No |
| Free | Paid | Yes | Yes | No | Yes |
| Paid | Free | Yes | Yes | Yes | No while Free |
| Paid | Paid | Yes | Yes | Yes | Yes |

A Free member receiving a Paid message can read it. Replace the composer with a contextual CTA:

> Want to continue this conversation? Upgrade your membership to reply.

Payment must never be presented as a way to override rejection, block or safety restrictions.

---

## 5. Interest Integration

### 5.1 Interest Sent

Successful Interest creation creates/reuses the appropriate conversation and appends exactly one system event, for example:

```text
[Interest]
SAK123456 has expressed interest in your profile.
```

Exact text is Superadmin-configurable. The event must remain visually distinguishable from member-authored content.

Interest-generated events do **not** consume new-conversation, per-member, total-message or unanswered-message quotas.

Duplicate clicks, retries and replay/direct requests must not duplicate the Interest, conversation or system event.

### 5.2 Interest Accepted

Append a system event such as:

```text
Interest Accepted
```

Messaging continues according to current membership and safety rules.

### 5.3 Interest Declined

Decline is the recipient's decision and closes messaging for the relationship. Preserve history but disable further messaging in both directions.

> This conversation is closed because the Interest was declined.

Never show an upgrade CTA implying payment can override rejection.

### 5.4 Interest Withdrawn — final V1 decision

Withdrawal is different from Decline.

**Before the recipient has manually replied:** close the Interest-originated conversation, preserve history and append one idempotent `Interest Withdrawn` system event.

> This conversation is closed because the Interest was withdrawn.

**After the recipient has manually replied:** do not silently destroy/close an established mutual conversation solely because the original Interest was withdrawn. Mark Interest `Withdrawn`, append the system event, preserve history and allow the existing mutual conversation to continue subject to membership, block, moderation and safety rules.

Examples:

```text
A sends Interest -> B reads but does not reply -> A withdraws
=> Interest Withdrawn + conversation closed
```

```text
A sends Interest -> B replies -> A replies -> A withdraws
=> Interest Withdrawn event + established conversation remains
```

Withdrawal never deletes history, refunds consumed manual quota, bypasses block/moderation or duplicates system events on retries.

---

## 6. Core Journeys

### Free -> Free

Interest/system event can be sent/received/read. Neither member can manually message while Free.

### Free -> Paid

Free sends Interest -> Paid receives system event -> Paid can reply -> Free can read reply but cannot reply -> contextual upgrade CTA -> after authoritative upgrade, same conversation becomes replyable if otherwise eligible.

### Paid -> Free

Paid may manually message Free subject to access and messaging rules -> Free receives/reads -> Free cannot reply -> upgrade CTA -> after upgrade, same conversation/history continues.

### Paid -> Paid

Full two-way manual messaging subject to plan and safety rules.

### Membership expiry

History remains readable and incoming messages can still be received/read where account/safety state permits. Manual send/reply is immediately removed and composer becomes renewal CTA.

### Upgrade during conversation

After authoritative activation, eligible messaging becomes available without creating a duplicate conversation or losing history. UX should return the member to the same conversation where practical.

---

## 7. Messaging Entry Points

Show a consistent Message action/icon on:

- Profile Card;
- Interest Card;
- Full Member/Profile View.

Paid: open/create conversation when permitted.

Free: keep the feature discoverable but explain:

> Messaging is available with membership. You can receive and read messages from members. Upgrade to start conversations and reply.

Actions: `View Plans`, `Cancel`.

---

## 8. Messages Screen and UI Flow

Add primary member navigation `Messages` with unread indicator/count.

### 8.1 Desktop

Use a responsive two-pane layout: conversation list on left, active conversation on right.

Conversation list includes permitted photo, permitted display name, Profile ID, latest preview, timestamp and unread indicator/count. Initial filters: `All`, `Unread`. Search: Name/Profile ID.

### 8.2 Mobile

Use sequential navigation:

```text
Messages list -> Conversation -> Back to Messages
```

Do not compress both desktop panes side-by-side on small screens.

### 8.3 Conversation header

Display permitted profile image/name, Profile ID, age where allowed, city/country, verification badges, `View Profile`, and three-dot Block/Report actions.

A conversation must not expose otherwise-hidden phone, email, Aadhaar or private profile information.

### 8.4 Composer

V1 is plain text, `1–200` characters, with character counter and both client/server validation.

Do not support in V1: attachments, images, PDFs, Aadhaar/documents, voice notes, video attachments/calls, arbitrary files, reactions, disappearing messages, typing indicator, online/last-seen presence or group/family chat.

### 8.5 Message state

Member-visible V1 states: `Sent`, `Read`. Do not add online status, last seen or typing indicator.

### 8.6 Safety guidance

Persist a compact warning near the composer:

> Stay safe: Avoid sharing OTPs, financial information, Aadhaar details or sending money. Report suspicious behaviour.

Phone/email-like content should initially trigger privacy guidance rather than automatic hard blocking unless Product later defines a stricter policy.

### 8.7 Non-composer states

Free:

> Want to continue this conversation? Upgrade your membership to reply.

Expired:

> Your membership has expired. Renew your membership to continue this conversation.

Declined:

> This conversation is closed because the Interest was declined.

Withdrawn before mutual conversation:

> This conversation is closed because the Interest was withdrawn.

Blocked:

> You have blocked this member.

Do not show upgrade CTA for a safety/rejection closure.

### 8.8 Empty states

No conversations:

> Your conversations will appear here. Send an Interest or explore profiles to start connecting.

Action: `Explore Matches`.

No unread:

> You're all caught up. You have no unread messages.

---

## 9. Notifications

1. In-app unread indication is primary.
2. External SMS/email must not include full private message body by default.
3. Safe example: `You have a new message from SAK123456.`
4. Rapid message bursts should be throttled/bundled instead of one external notification per message.
5. Notification failure must not roll back a successfully persisted message.
6. Notifications must not reveal profile/contact information unavailable under existing privacy rules.

---

## 10. Trust, Safety and Abuse Prevention

Before every manual send, server-side logic must validate at minimum:

1. authenticated/active sender;
2. valid/eligible recipient;
3. current manual-send entitlement;
4. relationship/Interest state;
5. block state;
6. suspension/deactivation/moderation state;
7. new-conversation quota when applicable;
8. per-recipient daily quota;
9. total outgoing daily quota;
10. 3-consecutive-unanswered rule;
11. message content/length;
12. concurrency-safe state immediately before persistence.

Log/detect patterns needed for later abuse controls: mass solicitation, duplicate/near-duplicate text, rapid automated sending, repeated phone/email/URL solicitation, financial requests, repeated independent reports, post-block/post-decline attempts and concurrent/direct-endpoint quota bypass attempts.

---

## 11. Report and Block

### Report Message

Each member-authored message should allow `Report message` with reasons such as harassment/abuse, asking for money, fake/suspicious identity, inappropriate content, repeated unwanted contact, spam and Other.

Capture immutable reporter, reported member, conversation, message and timestamp context. Reporting must not destroy evidence.

### Block

Block overrides every Paid entitlement. Stop new messages/replies immediately in both directions, disable composer, reject direct endpoint attempts and preserve history for moderation/audit. Unblocking does not automatically reverse an Interest decline or other independent restriction.

---

## 12. Admin Experience and Privacy

### 12.1 Admin Member View

Add Messaging Activity showing at least conversations, sent, received, unread, reported and Admin-removed counts plus `View Conversations`.

### 12.2 Purpose of Admin message access

Private-message inspection exists for authorized member support, complaint investigation, reported-message review, fraud/safety investigation and operational troubleshooting where message state is relevant. It is **not routine browsing of private conversations**.

Authorized access must be auditable with Admin ID, member/context, conversation ID and timestamp. Preserve the ability to require access reason by role later. Admin viewing must never alter member read/unread state.

### 12.3 Admin removal/moderation

Use `Remove/Moderate Message`, not destructive delete. Reason is mandatory, e.g. personal/sensitive information, abuse, financial solicitation, spam, inappropriate content, safety violation or Other.

Member sees:

> This message was removed by SikhanandKaraj moderation.

Internally preserve original content, sender/recipient references, Admin ID, reason and moderation timestamp.

### 12.4 Member deletion

No `Delete for everyone` in V1. A future local `Hide conversation` may be considered but must never erase moderation/audit evidence.

---

## 13. Superadmin Configuration

Central settings should support:

- global messaging enable/disable;
- Interest system-message template;
- maximum message length;
- per-plan new manual conversations/day;
- per-plan messages/member/day;
- per-plan total outgoing messages/day;
- maximum consecutive unanswered messages;
- safety-warning text.

Configuration changes affect future enforcement and never erase historical conversations. The Interest template remains visually system-generated regardless of wording.

---

## 14. Edge and Corner Cases

### Membership/account lifecycle

Test Paid expiry with composer open; Free upgrade mid-conversation; plan upgrade/downgrade; Admin activation/deactivation; sender/recipient suspension/deactivation; archived/deleted profile; profile/photo visibility change; verification change. Current permitted identity/photo data must be rendered, not stale private data.

### Limits/concurrency

Test immediately below, exactly at and above every limit; third unanswered succeeds/fourth fails; recipient reply resets unanswered count; concurrent tabs/devices at final allowance; request retry/replay; consistent authoritative timezone/day-boundary policy.

### Interest transitions

Test Sent/Accepted/Declined/Withdrawn; notification failure after successful Interest persistence; duplicate Interest; status change from another tab/device; decline while sender composer is open; withdrawal before reply; withdrawal after reply; duplicate withdrawal; withdrawal/message race; no Interest event consumes manual quota.

### Block/report races

Test B blocks while A sends; authoritative ordering prevents later message bypass; report preserves evidence; unblock does not reopen declined relationship; block still overrides a post-withdrawal mutual conversation.

### Content

Test whitespace-only, 1 char, exactly 200, 201, Unicode/Gurmukhi/emoji, line breaks, HTML/script, URLs, phone/email patterns, malicious strings, repeated copy/paste and rapid submissions. Render user content safely as text.

### Read/unread

Sender viewing own sent content does not mark recipient read. Recipient opening follows the defined read rule. Test multiple devices/tabs. Admin viewing never marks member messages read. Explicitly define unread behaviour for system and moderated messages. Navigation badge and list must agree.

### Admin moderation

Test already-reported removal, concurrent second-Admin removal, mandatory reason server-side, authorization boundaries, original evidence visibility only through authorized moderation/audit path, placeholder visibility and audit logging.

### Privacy

Messaging must not expose hidden phone/email/Aadhaar, must preserve name masking and photo/profile visibility, must keep external notifications private, and must prevent unauthorized Admin/member conversation access.

---

## 15. Development Behavioural Requirements

1. Reuse the authoritative membership model; no messaging-specific `is_paid` flag.
2. Centralize messaging entitlement/limit decisions.
3. Every write endpoint independently enforces authorization, relationship, safety and quotas.
4. Quota accounting is concurrency-safe.
5. Interest/message operations are idempotent where retries are possible.
6. Admin moderation is auditable/non-destructive.
7. Reuse existing block/report/privacy architecture.
8. UI capability/state comes from authoritative backend rules.
9. Structurally distinguish system events from member messages.
10. Design indexes/query patterns for conversation list, unread counts, message pagination, moderation and quota enforcement.
11. Paginate long conversations.
12. Prefer customer-facing Profile ID over raw internal IDs.
13. Follow existing SikhanandKaraj incremental DB deployment rules.
14. Notification delivery is independent of successful message persistence.
15. Do not cache previously visible private profile data as a messaging bypass.

---

## 16. QA Matrix

QA must test both UI and direct endpoint/API behaviour.

### Membership combinations

- Free -> Free
- Free -> Go/Plus/Pro
- Go/Plus/Pro -> Free
- Paid -> same Paid plan
- Paid -> different Paid plan

### State combinations

- no Interest
- Interest Sent
- Accepted
- Declined
- Withdrawn before reply
- Withdrawn after reply
- blocked by either party
- reported
- active
- expired
- upgraded/downgraded
- suspended/deactivated
- just below / at / above each limit

### Security/negative tests

- Free manually POSTs message
- expired member POSTs
- tampered recipient/conversation ID
- read another member's conversation
- send into another pair's conversation
- send after block/decline/closed withdrawal
- concurrent quota bypass
- duplicate/replayed request
- XSS/HTML injection
- CSRF according to application protection
- unauthorized Admin access
- Admin removal without reason
- client-validation bypass

---

## 17. Acceptance Criteria

V1 is accepted only when:

1. Messaging entitlement is gender-independent.
2. Free can receive/read but cannot manually send/reply.
3. Paid can manually message Free and Paid subject to rules.
4. Each successful Interest produces exactly one system Interest event.
5. System events are visually distinct from member-authored messages.
6. Interest events consume no manual messaging quota.
7. Go limits: 5 new conversations/day, 10 messages/member/day, 25 outgoing/day.
8. Plus limits: 10, 15, 50 respectively.
9. Pro limits: 20, 25, 100 respectively.
10. All Paid plans enforce 3 consecutive unanswered messages maximum.
11. Limits are server-side and concurrency-safe.
12. Existing conversations remain usable after new-conversation quota is reached, subject to other limits.
13. Block immediately prevents communication regardless of plan.
14. Decline closes messaging and cannot be overridden by payment.
15. Profile/contact/privacy rules cannot be bypassed.
16. Message entry points exist on Profile Card, Interest Card and Full Member View.
17. Dedicated responsive Messages screen exists.
18. Free/expired/blocked/declined/closed-withdrawal states show correct non-composer UI.
19. V1 is text-only and length is enforced server-side.
20. Safety warning is displayed near composer.
21. Report Message captures message/conversation context.
22. Admin Member View exposes authorized messaging summary/review.
23. Admin private-message access is auditable.
24. Admin removal requires reason and preserves evidence.
25. Removed messages show moderation placeholder.
26. Membership expiry removes manual-send entitlement without deleting history.
27. Upgrade enables eligible messaging without duplicate history/conversation.
28. Unread state is consistent across navigation/list.
29. Admin viewing does not mark member messages read.
30. Retries/replays do not duplicate messages/quota consumption.
31. Unauthorized users cannot read/send into others' conversations.
32. Message content is safely rendered and cannot execute script/HTML.
33. Long conversations are paginated.
34. Notifications do not expose private message content by default.
35. Suspension/deactivation is respected on every send.
36. Withdrawal before recipient manual reply closes the Interest-originated conversation and preserves history.
37. Withdrawal after recipient manual reply records withdrawal but does not silently close/destroy the established mutual conversation.
38. Withdrawal is idempotent and creates no duplicate system events.
39. Withdrawal cannot bypass block, moderation or quotas.
40. Customer-facing plan messaging does not present anti-harassment limits as premium benefits.
41. Any `Unlimited` commercial wording remains subject to fair-use/safety ceilings.
42. Admin message access is for authorized support/moderation/safety purposes and is auditable.
43. Admin viewing never changes member read/unread state.
44. External notifications do not expose full private message bodies by default.
45. Rapid bursts may be throttled/bundled rather than generating one external notification per message.
46. Notification failure does not undo a successfully persisted message.

---

## 18. V1 Scope

### Included

Text-only one-to-one messaging; Interest system events; Free receive/read; Paid send/reply; Paid->Free and Paid->Paid; plan limits; 3-unanswered safety rule; unread state; responsive Messages UI; profile/card entry points; block/report integration; message reporting; Admin summary/review; auditable moderation; safety guidance; membership upgrade/expiry; Interest Accepted/Declined/Withdrawn behaviour.

### Deferred

Attachments, images/files/documents, voice notes, video messaging/calls, typing indicators, online/last-seen presence, reactions, delete-for-everyone, disappearing messages, group/family conversations, sophisticated AI moderation unless separately approved, and stricter external contact-information controls beyond the V1 warning policy.

---

## 19. Product Metrics After Launch

Monitor conversations created by Interest vs manual initiation, Free members receiving messages, Free->Paid conversion after received message, reply rate, accepted-Interest conversation rate, messages/conversation, members hitting each commercial limit, 3-unanswered limit hits, blocks/reports per 1,000 conversations, moderation/removal rate, Paid complaints about limits, unread-message age and messaging-related renewal conversion.

Tune commercial limits from evidence while keeping safety rules conservative.

---

## 20. 33-Point Product Recommendation Reconciliation

This primary document has been reconciled against all 33 recommendations agreed during feature discovery:

| # | Recommendation | Authoritative coverage |
|---:|---|---|
| 1 | Controlled matrimonial communication, not unrestricted chat | §§1–2 |
| 2 | Sender/receiver behaviour matrix | §4 |
| 3 | Paid -> Free allowed | §§2, 4, 6 |
| 4 | Interest creates clear system message | §5 |
| 5 | Three commercial quota dimensions | §3 |
| 6 | Highest plan never unlimited from safety perspective | §3.5 |
| 7 | Free receive/read, upgrade to reply | §§4, 8 |
| 8 | Free -> Paid Interest/reply conversion journey | §6 |
| 9 | Gender-independent permissions | §§1–2 |
| 10 | Message action on Profile/Interest cards and Full View | §7 |
| 11 | Dedicated Messages inbox/unread | §8 |
| 12 | Identity context without private-data leakage | §8.3 |
| 13 | Text-only V1/no attachments | §8.4, §18 |
| 14 | Persistent safety warning | §8.6 |
| 15 | Phone/email privacy warning approach | §8.6 |
| 16 | Sent/Read; no online/last-seen/typing | §§8.4–8.5 |
| 17 | Interest Accepted event | §5.2 |
| 18 | Interest Declined closes messaging | §5.3 |
| 19 | Block immediately stops communication/preserves history | §11 |
| 20 | Per-message reporting with reasons | §11 |
| 21 | Admin Member View messaging activity | §12.1 |
| 22 | Permissioned/audited Admin message access | §12.2 |
| 23 | Non-destructive Admin removal with reason | §12.3 |
| 24 | No Delete-for-everyone V1 | §12.4 |
| 25 | Expiry preserves history/read but removes send | §6 |
| 26 | Upgrade continues same conversation | §6 |
| 27 | Interest Withdrawn behaviour | §5.4 |
| 28 | Lifecycle/privacy/status edge cases | §14 |
| 29 | Abuse controls | §§3.4, 10 |
| 30 | Plan communication emphasizes access, not safety counters | §3.5 |
| 31 | All/Unread, Name/Profile-ID search, empty states | §8 |
| 32 | Notification privacy/burst handling | §9 |
| 33 | Superadmin policy + shared Product/Dev/QA contract | §§13, 15–17 |

All 33 recommendations are therefore represented in this single source of truth.

---

## 21. Final Reference Rule

For messaging implementation and QA, use:

1. **`docs/MEMBER_MESSAGING_PRODUCT_DEVELOPMENT_QA_REFERENCE.md` — single authoritative messaging feature specification.**
2. `docs/MEMBERSHIP_AND_MATCHING_RULES.md` — authoritative wider membership/profile-access rules where messaging depends on them.

There is intentionally no separate messaging addendum. Future Product decisions must be merged into this primary document so Development and QA never need to reconcile competing messaging specifications.