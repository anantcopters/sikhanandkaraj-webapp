# Coupon Management — Final V1 Product, Development & QA Reference

**Project:** SikhAnandKaraj  
**Feature:** Coupon Management  
**Version:** V1 Final  
**Primary Role:** Superadmin  
**Payment Channel in V1:** Offline Payment  
**Future Payment Channel:** Online Payment Gateway  
**Business Timezone:** Asia/Kolkata  
**Document purpose:** Final product behaviour, development contract and QA reference for Coupon Management V1.

---

## 1. Product Objective

Coupon Management gives Superadmin a controlled way to create promotional pricing rules for paid membership plans, restrict eligibility, apply coupons during offline payment collection and report on successful coupon usage.

A coupon is a **controlled pricing rule attached to a payment transaction**, not merely a client-side code that subtracts an amount. Coupon eligibility and pricing are server-authoritative.

V1 integrates coupons with the existing offline membership-payment lifecycle while keeping the coupon domain suitable for future reuse by an online payment gateway.

---

## 2. Final V1 Scope

V1 includes:

- Superadmin-only coupon administration.
- Unique, case-insensitive coupon codes.
- One or multiple applicable paid membership plans.
- Percentage discounts from 1% through 100%.
- Flat discounts in rupees, including paise to a maximum of two decimal places.
- Zero-payable memberships when the authoritative coupon calculation discounts the full plan price.
- Coupon start at creation time and expiry at end of the selected expiry date.
- Maximum successful-redemption limit.
- All Members, Selected Members or Gender eligibility.
- Male or Female gender targeting.
- Optional Country / State / City geographic restriction.
- Active / inactive administration from the coupon Edit screen and derived effective status on the listing.
- Coupon preview through **Apply Coupon** during offline payment.
- Final server-side coupon revalidation during successful payment processing.
- Offline-payment coupon validity evaluated against the recorded payment date.
- Transactional membership activation and coupon redemption.
- One successful redemption per member per coupon.
- Historical pricing snapshots.
- Coupon listing and utilization/reporting.
- Audit trail for material V1 coupon actions.

V1 explicitly excludes:

- Member-facing online coupon redemption.
- Payment-gateway integration.
- Future-start coupon scheduling through the Superadmin UI.
- Payment/redemption void or reversal workflow.
- Coupon stacking.
- Referral + coupon combinations.
- Automatic coupon application.
- Minimum-order-value campaigns.
- Public promotional banners.
- Complex campaign/rule engines.

---

## 3. Access and Authorization

Coupon Management is a **Superadmin-only** feature.

Authorization must be enforced server-side for listing, creation, editing, status changes, reports, coupon preview during offline payment and final redemption. Hiding controls in the UI is not authorization.

Coupon preview is a POST operation and remains protected by the application's normal CSRF protection.

---

## 4. Coupon Definition

| Property | Final V1 Rule |
|---|---|
| Coupon Code | Required, unique and case-insensitive |
| Internal Description | Optional administrative note |
| Applicable Plans | One or more paid plans; at least one required |
| Discount Type | Percentage OR Flat |
| Percentage Value | Whole number from 1 through 100 |
| Flat Value | Greater than zero; maximum two decimal places; cannot exceed the highest selected plan price |
| Start | Automatically current application/business time when created |
| Expiry | Selected date remains valid through 23:59:59 Asia/Kolkata |
| Usage Limit | Maximum number of successful redemptions |
| Member Eligibility | All Members OR Selected Members OR Gender |
| Gender | Male OR Female when Gender eligibility is selected |
| Geographic Restriction | Optional Country / State / City |
| Administrative Status | New coupons start Active; Active/Inactive is managed from Edit |

Coupon codes are normalized so `KOTA25`, `kota25` and `Kota25` cannot represent separate coupons.

---

## 5. Coupon Status

The implementation supports these effective states:

- **Active** — administratively active, within validity and below usage limit.
- **Inactive** — deliberately disabled by Superadmin.
- **Expired** — expiry time has passed.
- **Exhausted** — successful redemptions have reached the usage limit.
- **Scheduled** — defensively supported as a derived state when persisted data has a future start, but V1 does **not** expose future-start scheduling in the Superadmin creation UI.

Expired and Exhausted are derived states and are not manually selected.

The Coupon List displays the effective status but does not provide an Activate/Deactivate action. Administrative Active/Inactive changes are performed from the Edit Coupon screen. PostgreSQL boolean values must be normalized through the project's `BooleanValue::fromDatabase()` support class before PHP/UI status decisions.

---

## 6. Date and Time Rules

On V1 creation, `starts_at` is the current application/business time. Superadmin does not select a future start date in V1.

The selected expiry date is converted to **23:59:59 in Asia/Kolkata**.

Example: expiry date `30 September 2026` remains valid through `30 September 2026 23:59:59` business time.

An expiry that is already before the creation/start time must be rejected.

After successful redemption has begun, expiry may be extended but not shortened.

### Offline-payment effective date

Offline payment is different from a live online transaction because Superadmin may record a payment after the money was actually received. The recorded **Payment Date** is therefore the authoritative temporal reference for coupon start/expiry validation in the offline-payment flow.

Rules:

- Payment Date is interpreted in `Asia/Kolkata`.
- Payment Date must be a valid date and cannot be in the future.
- When an offline payment uses a coupon, coupon start/expiry eligibility is evaluated against the recorded Payment Date on the server.
- The same effective payment date must be used during both pre-payment coupon revalidation and the final locked transactional redemption revalidation.
- Apply Coupon remains a preview and may use current business time because the final payment save is authoritative.
- Future online-payment processing continues to use the actual transaction timestamp/current business time unless that payment channel defines another authoritative payment timestamp.

Where the offline UI captures only a calendar date and not an exact payment time, QA must verify the agreed date-boundary behaviour and must not allow an arbitrary browser-supplied time to become authoritative.

---

## 7. Plan Eligibility

A coupon must be mapped to at least one membership plan and may apply to multiple plans.

The selected plan is checked again when the coupon is previewed and again when payment is finalized. Browser-supplied pricing is never authoritative.

The Coupon Management form supplies active plans. Final redemption independently requires an active authoritative plan and verifies that the coupon is mapped to that plan.

---

## 8. Discount Rules

Exactly one discount type is allowed.

### 8.1 Percentage

- Whole-number percentage only.
- Minimum 1%.
- Maximum 100%.
- Decimal percentage values are rejected.
- 100% is valid and may produce Final Payable = ₹0.

### 8.2 Flat

- Entered in rupees.
- Greater than zero.
- Supports zero, one or two decimal places, for example `500`, `500.5`, `500.50`.
- More than two decimal places are rejected rather than silently rounded.
- Persisted/calculated in paise.
- At coupon create/edit time, the configured flat amount must not exceed the **highest selected applicable plan price**.
- At actual redemption, the effective flat discount must never exceed the authoritative selected plan price; for a cheaper applicable plan it is capped at that plan price.
- A valid full discount may therefore produce Final Payable = ₹0.

Examples when applicable plans are ₹1,000 and ₹2,000:

- ₹500 flat discount — valid.
- ₹1,500 flat discount — valid configuration because it does not exceed the highest selected plan price; redemption against the ₹1,000 plan is capped at ₹1,000.
- ₹2,000 flat discount — valid and may fully discount the ₹2,000 plan.
- ₹2,001 flat discount — reject.

Client-side maximums are UX only. The service must independently resolve authoritative plan prices and enforce the rule server-side.

---

## 9. Member Eligibility

Exactly one eligibility mode is required:

1. **All Members** — every otherwise eligible member.
2. **Selected Members** — one or multiple explicitly selected members.
3. **Gender** — members whose canonical gender matches Male or Female.

The modes are mutually exclusive. Stale values belonging to another eligibility mode must not change effective eligibility.

Selected Members are persisted against canonical member IDs.

Gender eligibility is evaluated against the member's canonical stored gender. Client-supplied gender never determines eligibility.

A member may successfully redeem the same coupon only once. This is a **member + coupon** rule, not member + coupon + plan.

Remote member search and a maximum selected-member count are not part of the current V1 requirement; the existing member-selection flow remains acceptable for this iteration.

---

## 10. Geographic Eligibility

Geographic restriction is optional and is an additional AND restriction on the selected member-eligibility mode.

Supported hierarchy:

`Country -> State/Province -> City`

Rules:

- State requires Country.
- City requires State.
- State must belong to the selected Country.
- City must belong to the selected State.
- Relationships are validated server-side, not only by dependent dropdowns.

Examples:

- All Members + Rajasthan.
- Female Members + Rajasthan.
- Male Members + Rajasthan + Kota.
- Selected Members + a geographic restriction.

Eligibility is evaluated against the member's current Basic Details location at redemption time. Historical financial reporting uses transaction snapshots and is not recalculated after later profile changes.

---

## 11. Usage Limit and Concurrency

Usage limit is the maximum number of **successful completed coupon redemptions**.

These do not consume usage:

- Opening the offline-payment modal.
- Entering a coupon.
- Selecting Apply Coupon.
- Successful coupon preview.
- Abandoning payment.
- Failed payment/activation.

Usage is consumed only when the successful-payment lifecycle completes the associated coupon redemption.

The limit is absolute. If usage is 99 of 100, two concurrent transactions must not both become redemption #100. Final coupon evaluation locks/revalidates the coupon inside the payment transaction.

---

## 12. Coupon Creation Journey

The V1 form covers:

- Coupon Details.
- Applicable Plans.
- Discount.
- Expiry / validity.
- Usage Limit.
- Member Eligibility.
- Optional Geographic Restriction.

New coupons are Active by default. Administrative Active/Inactive control is presented on Edit, not Create or the listing action area.

Creation rules include:

- Valid normalized code.
- At least one plan.
- Valid type-specific discount.
- Percentage between 1 and 100.
- Flat discount not greater than the highest selected plan price.
- Positive usage limit.
- Valid expiry date.
- Exactly one member-eligibility mode.
- At least one member when Selected Members is chosen.
- Exactly Male or Female when Gender is chosen.
- Valid geography hierarchy when geography is supplied.

Coupon creation must fail without leaving a partially configured coupon when required persistence fails.

The Create/Edit form should reuse the established narrower Admin form layout rather than unnecessarily occupying the full available width. Save and Cancel actions are compact and right-aligned using existing project button/loader classes.

---

## 13. Coupon Listing

The V1 listing provides operational visibility including:

- Coupon code.
- Discount.
- Applicable plan names.
- Eligibility summary.
- Validity.
- Used count / usage limit.
- Effective status.
- Edit action.
- Report action.

The list does **not** provide a separate Activate/Deactivate action. Active/Inactive administration belongs to Edit Coupon.

Hard delete is not an operational Coupon Management action.

---

## 14. Editing Rules

### Before First Successful Redemption

Superadmin may edit the commercial/eligibility configuration, including applicable plans, discount, eligibility, geography, usage limit and expiry, subject to normal validation. Administrative Active/Inactive status is also controlled from the Edit screen.

### After First Successful Redemption

The following become immutable:

- Coupon code.
- Discount type/value.
- Applicable plans.
- Eligibility mode.
- Selected-member targets.
- Gender target.
- Geographic restrictions.

The following operational changes remain allowed:

- Increase usage limit.
- Extend expiry.
- Deactivate.
- Reactivate when other validity rules permit use.

Usage limit cannot be reduced and expiry cannot be shortened after redemption begins.

---

## 15. Offline Payment Journey

1. Superadmin opens the member's offline-payment flow.
2. Selects a paid membership plan.
3. System displays authoritative current plan pricing.
4. Superadmin optionally enters a Coupon Code.
5. Superadmin selects **Apply Coupon**.
6. A CSRF-protected server request validates the coupon for the member and selected plan as a preview.
7. If valid, the UI displays Plan Price, Coupon Discount and Final Payable.
8. Superadmin enters Payment Date, payment details and Amount Received.
9. On final save, the server validates Payment Date and independently recalculates/revalidates the coupon using the recorded Payment Date for the coupon validity window.
10. Successful payment processing performs a second locked coupon revalidation using the same effective offline payment date, activates membership and records coupon redemption transactionally.

Existing offline payment without a coupon remains supported.

---

## 16. Apply Coupon Is Preview Only

Apply Coupon is a convenience/preview operation. It does **not** reserve coupon capacity and does **not** create a redemption.

Changing the selected plan invalidates the displayed coupon calculation. Editing the coupon code after preview also invalidates the displayed result until Apply Coupon is selected again.

The final payment save does not trust displayed/browser values. For offline payments, the final save also does not trust preview-time coupon date validity; it revalidates the coupon against the authoritative Payment Date supplied to the offline-payment server flow.

---

## 17. Pricing and Amount Received

For a valid coupon, display:

- Plan Price.
- Coupon Discount.
- Final Payable.

Final Payable is system-calculated from authoritative plan and coupon data and may be zero for a valid 100% or otherwise full discount.

**Amount Received** remains a separate offline-payment field representing what was actually collected. It must not redefine coupon pricing. A mismatch between Amount Received and calculated Final Payable is surfaced to Superadmin according to the existing offline-payment UI behaviour.

A zero Amount Received must never make an ordinary payment valid by itself. It is valid only where the authoritative server-side pricing result legitimately produces a zero Final Payable.

---

## 18. Authoritative Coupon Validation Sequence

Coupon validation covers at least:

1. Coupon exists.
2. Coupon is administratively active.
3. Effective business date/time is on/after start.
4. Effective business date/time is on/before expiry.
5. Successful redemption count is below usage limit.
6. Selected membership plan is active and coupon-applicable.
7. Member satisfies All / Selected / Gender eligibility.
8. Canonical gender matches when Gender mode is used.
9. Member satisfies configured geography.
10. Member has not already completed a redemption for the coupon.
11. Discount and Final Payable are financially valid, including legitimate zero-payable results.

For normal/current coupon evaluation, the effective time is current business time in Asia/Kolkata. For final offline-payment processing, the recorded Payment Date is the authoritative temporal reference for coupon start/expiry validity.

Apply Coupon performs server-side preview validation. Final payment repeats authoritative validation before payment creation and again under transaction/locking during successful-payment processing.

---

## 19. Successful Payment, Transaction and Idempotency

Applying a coupon is not redemption. Redemption occurs only through successful payment/membership processing.

Logical lifecycle:

`Validate Offline Payment Date -> Revalidate Coupon -> Create Payment -> Lock Payment -> Idempotency Check -> Lock/Revalidate Coupon -> Record Payment Success -> Activate Membership -> Mark Payment Processed -> Record Coupon Redemption/Audit -> Commit`

Important rules:

- An already-PROCESSED payment returns idempotently before coupon revalidation.
- For offline coupon payments, both final server-side coupon validations use the same authoritative payment-date context for the coupon validity window.
- Membership activation and coupon redemption are part of the same successful-payment transaction.
- If activation or redemption fails, the transaction must not leave an activated membership with an unrecorded coupon redemption.
- Repeated success handling must not create a second redemption or consume usage twice.

---

## 20. Financial Snapshot Requirement

Historical coupon reporting must not depend on current plan prices or later coupon edits.

At transaction/redemption time the system preserves the relevant commercial snapshot, including:

- Membership plan identity.
- Original plan price.
- Coupon identity/code.
- Discount type/value.
- Discount amount.
- Final payable.
- Associated member payment.
- Administrative actor for redemption/audit.

Payment records preserve the associated payment method/source, Amount Received and authoritative paid date according to the offline-payment model.

---

## 21. Coupon Report — Final V1

The Coupon Report is an administrative utilization/financial report.

### Summary

V1 shows:

- Coupon code in report context.
- Successful Redemptions against Usage Limit.
- Original Gross.
- Total Discount.
- Final Payable.

The financial totals are calculated from immutable redemption snapshots rather than current plan prices.

### Redemption Rows

V1 rows show:

- Member identity / Profile ID.
- Plan.
- Original plan price.
- Discount amount.
- Final payable.
- Historical payment method.
- Redemption status.
- Redemption date/time.

Sensitive matrimonial data such as Aadhaar, DOB, private contact details and unrelated verification information is not part of this report.

Campaign-level status/validity/eligibility summary cards and additional administrative-actor presentation may be added later if operationally useful; they are not required for V1 acceptance.

---

## 22. Audit Trail — Final V1

Material V1 coupon actions are auditable, including:

- Creation.
- Editing.
- Activation/deactivation.
- Usage-limit changes.
- Expiry changes.
- Successful redemption.

Audit records preserve the actor/action/timestamp and relevant old/new or redemption snapshot context. Audit history must not be overwritten to hide earlier actions.

Payment/redemption reversal audit is outside V1 because reversal itself is outside V1.

---

## 23. Payment Reversal / Void — Future Scope

Payment reversal/void handling is **outside Coupon Management V1**.

V1 must not provide a coupon-only delete/void action to restore capacity, and completed coupon-redemption records must not be manually deleted merely to change coupon usage.

If formal offline-payment reversal is introduced later, coupon capacity, redemption history, membership consequences and audit history must be handled through the authoritative payment-reversal lifecycle. That future design must ensure capacity is restored at most once and historical records remain auditable.

---

## 24. Administrative Error Behaviour

Use safe, actionable messages appropriate to the context, including:

- Coupon does not exist.
- Coupon is inactive.
- Coupon is not active yet.
- Coupon has expired.
- Coupon was not active/valid on the recorded payment date.
- Coupon is exhausted.
- Coupon is not valid for the selected plan.
- Member is not eligible.
- Coupon is restricted to Male members.
- Coupon is restricted to Female members.
- Coupon is restricted to another location.
- Member has already used the coupon.
- Discount is invalid for the selected plan price.
- Payment date is invalid.
- Payment date cannot be in the future.

Do not expose unnecessary implementation details or sensitive member information.

---

## 25. Security, Abuse and Direct-Request Rules

Development and QA must consider:

- Repeated redemption.
- Case manipulation of coupon codes.
- Direct URL/request manipulation.
- CSRF on Apply Coupon and administrative POST operations.
- Inactive/expired/exhausted replay.
- Historical Payment Date manipulation.
- Future Payment Date submission.
- Coupon valid on preview date but invalid on recorded Payment Date, and vice versa.
- Usage-limit races.
- Browser-side price/final-payable manipulation.
- Gender manipulation.
- Geography manipulation.
- Unauthorized administrative access.
- Historical-data manipulation.

Canonical member data is authoritative for eligibility. Client-supplied gender, geography or pricing must not make an otherwise ineligible transaction valid.

The Coupon Management UI supplies active plans. Final coupon redemption independently validates the authoritative active plan and coupon-plan mapping. Client-side discount maximums are UX only; authoritative discount validation belongs to the service/database rules.

---

## 26. QA Core Test Journeys

QA should cover at least:

1. Create an All Members coupon for one plan.
2. Create an All Members coupon for multiple plans.
3. Create a coupon for one Selected Member.
4. Create a coupon for multiple Selected Members.
5. Create a Male Members coupon and redeem for an eligible male member.
6. Create a Female Members coupon and redeem for an eligible female member.
7. Reject Male coupon for a female member.
8. Reject Female coupon for a male member.
9. Combine Gender eligibility with State restriction and require both conditions.
10. Combine Gender eligibility with City restriction and require both conditions.
11. Create a geographically restricted coupon and test Country/State/City hierarchy.
12. Verify a newly created coupon starts at current business time; V1 has no future-start UI.
13. Validate expiry through 23:59:59 Asia/Kolkata and exact boundary behaviour.
14. Deactivate/reactivate a valid coupon from Edit; verify the list only displays effective status and has no status action.
15. Apply valid percentage coupons at 1%, 99% and 100%; reject 0%, 101% and decimal percentages.
16. Verify a 100% coupon can produce Final Payable = ₹0 through authoritative pricing.
17. Apply valid flat coupons including a two-decimal value.
18. Reject flat values with more than two decimals.
19. With multiple selected plans, accept a flat value up to the highest selected plan price and reject a value above it.
20. Verify a flat discount greater than a cheaper applicable plan price is safely capped at that plan price during redemption and never produces a negative payable.
21. Reject coupon for wrong plan/member/geography/gender.
22. Reject inactive, expired and exhausted coupons.
23. Reject second successful redemption by the same member.
24. Apply coupon and abandon payment; usage remains unchanged.
25. Force payment/membership failure; usage remains unchanged.
26. Successfully complete payment; usage increments exactly once.
27. Verify final save catches coupon deactivated or exhausted after preview.
28. Verify two concurrent final redemptions cannot exceed the last available usage slot.
29. Retry an already successful/processed payment; membership/redemption are not duplicated.
30. Verify post-redemption immutable fields cannot be changed.
31. Increase usage limit and extend expiry after redemption.
32. Reject usage-limit reduction and expiry shortening after redemption.
33. Change plan price after historical redemption; report remains unchanged.
34. Reconcile Original Gross - Total Discount = Final Payable in the report.
35. Verify historical payment method is shown in redemption rows.
36. Verify unauthorized roles/direct URLs cannot access Superadmin coupon functions.
37. Verify Apply Coupon succeeds with normal CSRF protection and fails without a valid token according to application policy.
38. Verify request tampering cannot alter authoritative discount/final payable or gender eligibility.
39. Verify coupon-code case variations cannot create duplicates or enable reuse.
40. Verify changing plan after Apply Coupon invalidates the preview.
41. Verify editing coupon code after Apply Coupon invalidates the preview.
42. Verify existing offline payment works normally without a coupon.
43. Record an offline payment today with a coupon valid today; accept.
44. Record an earlier Payment Date that falls inside the coupon validity window even when the coupon is expired on the admin-recording date; temporal validation should use Payment Date.
45. Reject an offline Payment Date before the coupon start date.
46. Reject an offline Payment Date after the coupon expiry date.
47. Reject a future Payment Date.
48. Verify the first final server-side coupon validation and the locked transactional revalidation use the same offline effective payment date.
49. Verify Asia/Kolkata date boundaries around coupon start and expiry.
50. Verify normal/current coupon evaluation still uses current business time when no offline effective date is supplied.

Formal payment/redemption void testing is not a V1 Coupon Management test because that workflow is deferred.

---

## 27. Boundary and Negative Cases

QA should specifically test:

- Empty/whitespace coupon code.
- Duplicate coupon code with different letter case.
- Invalid code characters.
- No applicable plan.
- Zero/negative/excessive discounts.
- Percentage decimals.
- Percentage 0, 1, 99, 100 and 101.
- Flat values with 0, 1, 2 and more than 2 decimal places.
- Flat discount equal to the highest selected plan price.
- Flat discount greater than the highest selected plan price.
- Flat discount greater than a cheaper selected plan but not greater than the highest selected plan.
- Zero/negative usage limit.
- Selected Members with no selected member.
- Gender mode with no gender.
- Manipulated Gender input.
- Invalid member IDs.
- Invalid Country/State/City relationships.
- Expiry before start/current business time.
- Exact expiry boundary.
- Usage exactly at limit.
- Concurrent final redemption.
- Repeated final submits.
- Retry after successful processing.
- Coupon changed between Apply and Save.
- Member location changed between Apply and Save.
- Member gender changed/tampered between Apply and Save.
- Selected plan changed after Apply.
- Coupon removed/cleared after Apply.
- Client-side Amount Received/final-payable manipulation.
- Missing/invalid CSRF token on coupon preview.
- Invalid Payment Date format.
- Future Payment Date.
- Payment Date before coupon start.
- Payment Date on coupon start calendar date.
- Payment Date on coupon expiry calendar date.
- Payment Date after coupon expiry.
- Historical valid Payment Date entered after the coupon has expired.
- Consistency between pre-payment and locked transactional payment-date revalidation.

---

## 28. Acceptance Criteria

Coupon Management V1 is acceptable when:

1. Only Superadmin can administer coupons and use administrative coupon endpoints.
2. Coupon code is required, normalized, unique and case-insensitive.
3. At least one plan is required.
4. Percentage discounts accept only whole values 1–100.
5. Flat discounts are positive, accept at most two decimal places and cannot exceed the highest selected plan price at configuration time.
6. Authoritative redemption pricing never allows discount greater than the actual selected plan price and may legitimately produce Final Payable = ₹0.
7. Exactly one eligibility mode is effective: All, Selected or Gender.
8. Selected Members requires one or more canonical members.
9. Gender requires exactly Male or Female and is evaluated from canonical member data.
10. Optional geography is validated server-side with correct hierarchy.
11. Coupon starts at current business time in the V1 creation flow.
12. Expiry is valid through 23:59:59 Asia/Kolkata.
13. New coupons are Active by default; Active/Inactive is managed from Edit and the listing displays effective status without a status action.
14. Apply Coupon is CSRF-protected, server-authoritative and preview-only.
15. Apply Coupon does not consume usage.
16. Final payment independently revalidates coupon eligibility and capacity.
17. Offline final payment validates coupon start/expiry against the authoritative recorded Payment Date in Asia/Kolkata, not merely the admin-recording time.
18. The final locked transactional coupon revalidation uses the same offline payment-date context.
19. Client/browser values cannot override authoritative plan price, discount or Final Payable.
20. One member cannot successfully redeem the same coupon twice.
21. Usage limit cannot be exceeded by concurrent successful transactions.
22. Membership activation and coupon redemption remain transactionally consistent.
23. Successful-payment retries are idempotent.
24. Historical financial reporting uses immutable transaction/redemption snapshots.
25. Coupon listing shows applicable plans, usage and effective status.
26. After first redemption, commercial/eligibility configuration is immutable; only allowed operational changes remain.
27. Coupon report reconciles successful redemption count, Original Gross, Total Discount and Final Payable and shows redemption-level payment method.
28. Material V1 coupon actions and successful redemptions are auditable.
29. Existing offline payment without a coupon continues to work.
30. No coupon-only hard-delete/void mechanism is treated as part of V1.

---

## 29. Development Architecture Contract

V1 must preserve these boundaries:

- Coupon administration belongs to the existing Admin coupon flow.
- Coupon eligibility/pricing belongs to the central coupon service, not duplicated controller/UI logic.
- Offline-payment processing remains centralized in the membership-payment service.
- Membership activation continues through the existing membership-purchase service.
- Apply Coupon is only a preview and never becomes the source of truth for final payment.
- Final successful payment performs authoritative coupon revalidation under transaction/locking.
- CouponService may receive an explicit effective date/time from an authoritative payment flow; when omitted it uses current Asia/Kolkata business time.
- Offline Payment Date validation belongs on the server and must be consistently propagated into final coupon revalidation.
- PostgreSQL boolean values are normalized using the existing `App\Support\BooleanValue::fromDatabase()` helper where required.
- Financial history uses integer paise and immutable snapshots.
- UI validation improves usability but never replaces server validation.
- Existing CSRF, Superadmin authorization, validation and application patterns are reused rather than bypassed.
- No parallel payment or membership-activation architecture should be introduced for coupons.

---

## 30. Deferred / Future Enhancements

The following are explicitly outside V1 and must not be treated as missing V1 defects:

- Online payment-gateway coupon entry/redemption.
- Future-start coupon scheduling UI.
- Formal offline-payment reversal/void and coupon-capacity restoration.
- Coupon stacking.
- Referral integration.
- Automatic promotion selection.
- Minimum-order/value campaign rules.
- Public/member promotional banners.
- Advanced campaign analytics beyond the V1 utilization/financial report.
- More detailed report summary cards or actor presentation where operationally useful.
- Remote Selected Member search / delayed search loading and an explicit maximum selected-member count; these were intentionally deferred from the current iteration.

Any future payment reversal must be implemented through the authoritative payment lifecycle rather than deleting coupon-redemption history.

---

## 31. Definition of Done — V1

Coupon Management V1 development is complete when:

- V1 schema and incremental deployment SQL are available under the project's DB deployment rules.
- Superadmin routes/controllers/services/views are integrated with existing architecture.
- Coupon create/list/edit/status/report flows work according to the current UI contract.
- Percentage discounts through 100% and valid zero-payable outcomes work end-to-end.
- Flat discount configuration is bounded by the highest selected plan price and redemption remains safe for cheaper applicable plans.
- Offline Apply Coupon works with CSRF protection.
- Final offline payment revalidates coupon server-side using the recorded Payment Date for temporal validity.
- Final locked redemption repeats validation with the same offline payment-date context.
- Transaction/concurrency/idempotency protections are present.
- Successful redemption and material coupon changes are auditable.
- Historical report values are snapshot-based.
- Existing no-coupon offline payment is not regressed.
- The QA journeys in this document are executed in the target environment before production release.

This document describes the **final agreed Coupon Management V1 behaviour**. Future-scope items must be implemented through explicit product/development changes rather than being inferred as unfinished V1 work.
