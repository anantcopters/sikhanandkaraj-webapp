# Member Messaging — 33-Point Review Addendum

## Purpose

This addendum records the line-by-line reconciliation of `MEMBER_MESSAGING_PRODUCT_DEVELOPMENT_QA_REFERENCE.md` against the 33 Product recommendations agreed during feature discovery.

The main feature document remains the primary Product/Development/QA reference. This addendum closes items that were only implicit or not explicitly resolved in the first version and provides traceability so future changes can be checked against the original Product decisions.

---

## 1. Reconciliation Summary

The main document already covered the substantial behaviour of the 33 recommendations. The review identified the following areas that needed to be made explicit:

1. **Interest Withdrawn behaviour** was previously listed only as a discussion/edge case in the original recommendation set and was not explicitly resolved in the feature document.
2. **Customer-facing plan wording vs backend safety ceilings** was present, but this addendum makes the commercial wording rule explicit so Product/Pricing does not advertise safety ceilings as benefits.
3. **Admin access purpose/audit expectations** were present, but this addendum clarifies that message inspection is for support, safety and moderation — not routine browsing.
4. **Message notification privacy and burst behaviour** were present, but this addendum records the exact Product principle for QA traceability.
5. A formal **33-point coverage matrix** was missing.

No previously agreed core behaviour is reversed by this addendum.

---

## 2. Explicit Decision — Interest Withdrawn

### 2.1 Product rule

An **Interest Withdrawn** action is different from **Interest Declined**.

- `Declined` is a recipient decision and closes messaging for the relationship.
- `Withdrawn` is a sender decision and must not be used to erase an existing legitimate two-way conversation.

Recommended V1 behaviour:

### Case A — Interest withdrawn before the recipient has manually replied

Close the Interest-originated conversation for further messaging.

Reason: the sender has explicitly withdrawn their matrimonial intent and there is not yet a mutual conversation to preserve.

Suggested system event:

```text
Interest Withdrawn
```

Suggested closed-state copy:

> This conversation is closed because the Interest was withdrawn.

No upgrade CTA should be displayed on a conversation closed for this reason.

### Case B — Interest withdrawn after the recipient has manually replied

Do **not** silently erase or close an established two-way conversation solely because the original Interest was withdrawn.

The Interest status becomes `Withdrawn`, a system event is appended, and the existing conversation may continue subject to membership, block, moderation and safety rules.

Rationale: once both members have voluntarily participated in the conversation, the conversation has become an independent mutual interaction. Using Interest withdrawal as a hidden conversation-delete mechanism would be confusing and could remove context from the other member.

Either member can still explicitly Block the other member to end communication immediately.

### 2.2 Examples

#### Example 1 — No reply yet

```text
A sends Interest
System Interest message created
B reads but does not reply
A withdraws Interest
-> Interest = Withdrawn
-> Conversation = Closed
-> No further manual messaging
```

#### Example 2 — Mutual conversation exists

```text
A sends Interest
B replies
A replies
A later withdraws Interest
-> Interest = Withdrawn
-> System event records withdrawal
-> Existing conversation remains available
-> Normal membership/safety rules continue
```

### 2.3 QA cases

QA must verify:

- withdrawal before first manual recipient reply closes conversation;
- withdrawal after recipient manual reply does not destroy conversation/history;
- history is never deleted by withdrawal;
- duplicate withdrawal requests do not duplicate system events;
- sender cannot race a message against a withdrawal to bypass the resulting state;
- block still overrides the post-withdrawal conversation state;
- direct endpoint/API attempts respect the same rule;
- withdrawal never refunds or alters messaging quota already legitimately consumed.

---

## 3. Explicit Decision — Commercial Wording vs Safety Limits

Pricing and plan UI should sell **capability and conversation access**, not advertise anti-abuse thresholds as premium benefits.

Recommended customer-facing positioning:

| Plan | Suggested messaging positioning |
|---|---|
| Free | Receive and read messages |
| Go | Start conversations and reply |
| Plus | More conversations and replies |
| Pro | Highest conversation access / generous messaging access |

Do not market wording such as:

```text
Pro: Send 25 messages to the same person every day
```

The per-recipient and 3-unanswered-message controls are safety protections, not benefits.

If Product later chooses customer-facing wording such as `Unlimited Conversations`, it must mean commercially unrestricted within normal intended use and must always remain subject to fair-use, rate-limit, block, decline and anti-abuse enforcement.

No plan — including Pro — is technically unlimited from a trust-and-safety perspective.

---

## 4. Explicit Decision — Admin Message Access Purpose

Admin message inspection exists for:

- member support;
- complaint investigation;
- reported-message review;
- fraud/safety investigation;
- operational troubleshooting where message state is relevant.

It is **not** intended for routine browsing of private member conversations.

At minimum, the system must retain an audit record when authorized Admin/Superadmin accesses private conversation content. Product should preserve the ability to require an access reason by role in the future.

Admin viewing must never alter member read/unread state.

---

## 5. Explicit Decision — Notification Privacy

Message notifications must follow these rules:

1. In-app unread indication is the primary notification mechanism.
2. External SMS/email notifications must not include the full private message body by default.
3. A safe example is:

```text
You have a new message from SAK123456.
```

4. Rapid message bursts should be throttled/bundled rather than generating one external notification per message.
5. Notification delivery failure must not roll back a successfully persisted message.
6. A notification must never reveal profile/contact information that the recipient could not otherwise view under existing privacy rules.

---

## 6. 33-Point Product Recommendation Coverage Matrix

| # | Original recommendation / decision | Coverage status | Authoritative treatment |
|---:|---|---|---|
| 1 | Messaging is controlled matrimonial communication, not unrestricted chat | Covered | Main doc §§1–2 |
| 2 | Sender/receiver behaviour matrix | Covered | Main doc §4 |
| 3 | Paid -> Free messaging must be allowed | Covered | Main doc §§2, 4, Journey C |
| 4 | Interest sends a clearly system-generated message | Covered | Main doc §5 |
| 5 | Membership limits: new conversations, per-member/day, total/day | Covered | Main doc §3 |
| 6 | Do not make highest plan literally unlimited from safety perspective | Covered + clarified | Main doc §23 + Addendum §3 |
| 7 | Free member receives/reads messages; upgrade required to reply | Covered | Main doc §§4, 8.8 |
| 8 | Free -> Paid Interest; Paid can reply; Free upgrades to continue | Covered | Main doc Journey B |
| 9 | Messaging permissions are gender-independent | Covered | Main doc §§1–2, AC #1 |
| 10 | Message icon on Profile Card, Interest Card and Full Profile View | Covered | Main doc §7 |
| 11 | Dedicated Messages inbox with unread indicator | Covered | Main doc §8 |
| 12 | Conversation header carries identity context without leaking private data | Covered | Main doc §8.4 |
| 13 | Text-only V1 composer, no attachments | Covered | Main doc §8.5, §21 |
| 14 | Persistent safety warning | Covered | Main doc §8.6 |
| 15 | Contact-number/email handling should warn initially rather than blindly expose/block | Covered | Main doc §8.6 |
| 16 | Sent/Read only; no online/last-seen/typing pressure in V1 | Covered | Main doc §§8.5, 8.7 |
| 17 | Interest Accepted is represented in conversation | Covered | Main doc §5.4 |
| 18 | Interest Declined closes messaging | Covered | Main doc §5.5 |
| 19 | Block immediately prevents communication and preserves history | Covered | Main doc §13 |
| 20 | Report individual messages with structured reasons | Covered | Main doc §12 |
| 21 | Admin Member View shows messaging activity/conversations | Covered | Main doc §14.1 |
| 22 | Admin private-message access should be permissioned/audited | Covered + clarified | Main doc §14.2 + Addendum §4 |
| 23 | Admin removal is non-destructive and requires reason | Covered | Main doc §14.3 |
| 24 | No member `Delete for everyone` in V1 | Covered | Main doc §14.4 |
| 25 | Membership expiry retains history/read but removes send entitlement | Covered | Main doc Journey G, §16.1 |
| 26 | Upgrade immediately enables eligible continuation of same conversation | Covered | Main doc Journey H |
| 27 | Interest Withdrawn behaviour | **Added in review** | Addendum §2 |
| 28 | Edge cases for deactivate/suspend/delete/privacy/status changes | Covered | Main doc §16 |
| 29 | Abuse controls: per-recipient, unanswered, mass solicitation, rapid/duplicate sending | Covered | Main doc §§3.2, 11 |
| 30 | Plan communication should emphasize access, not confusing raw counters | Covered + clarified | Main doc §23 + Addendum §3 |
| 31 | Simple All/Unread inbox filters, Name/Profile ID search, empty states | Covered | Main doc §§8.3, 9 |
| 32 | Notifications should protect private message content and avoid burst spam | Covered + clarified | Main doc §10 + Addendum §5 |
| 33 | Superadmin-configurable messaging policy and shared Product/Dev/QA acceptance criteria | Covered | Main doc §§15, 18–20 |

---

## 7. Additional Acceptance Criteria Introduced by This Review

The following acceptance criteria are additive to the main document:

36. Interest withdrawal before any manual recipient reply closes the Interest-originated conversation and preserves history.
37. Interest withdrawal after a manual recipient reply records the withdrawal but does not silently destroy/close the established mutual conversation.
38. Interest withdrawal is idempotent and cannot generate duplicate system events.
39. Interest withdrawal cannot bypass block, moderation or messaging limits.
40. Customer-facing plan messaging must not present anti-harassment limits as premium benefits.
41. Any `Unlimited` commercial wording remains subject to backend fair-use and safety ceilings.
42. Admin private-message access is for authorized support/moderation/safety purposes and is auditable.
43. Admin viewing never changes member read/unread state.
44. External message notifications do not expose full private message bodies by default.
45. Rapid message bursts do not require one SMS/email notification per individual message; notification throttling/bundling is permitted and recommended.
46. Notification failure does not undo a successfully persisted message.

---

## 8. QA Regression Checklist for the Reconciliation

Before messaging is considered release-ready, QA should explicitly cross-check the main feature document plus this addendum and confirm:

- all four Free/Paid sender-receiver combinations;
- Interest system-message generation and idempotency;
- Accepted, Declined and Withdrawn Interest transitions;
- Free -> Paid conversion path;
- all three commercial quota dimensions;
- universal 3-unanswered-message safety rule;
- block/report precedence;
- membership expiry/upgrade/downgrade transitions;
- Admin access audit and non-destructive moderation;
- unread behaviour including Admin viewing;
- desktop and mobile conversation UI states;
- privacy of profile/contact data inside messaging;
- notification privacy/failure behaviour;
- direct endpoint/API bypass attempts;
- concurrency at quota, block, decline and withdrawal boundaries.

---

## 9. Reference Rule

For implementation and QA, read these together:

1. `docs/MEMBER_MESSAGING_PRODUCT_DEVELOPMENT_QA_REFERENCE.md` — primary feature specification.
2. `docs/MEMBER_MESSAGING_33_POINT_REVIEW_ADDENDUM.md` — reconciliation decisions and explicit closure of the original 33 Product recommendations.
3. `docs/MEMBERSHIP_AND_MATCHING_RULES.md` — authoritative wider membership/profile-access rules.

If a future messaging requirement changes any of these decisions, Product must update the feature documentation in the same change so Development and QA do not work from an outdated behavioural contract.