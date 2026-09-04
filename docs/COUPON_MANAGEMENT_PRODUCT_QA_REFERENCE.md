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
- Percentage discounts.
- Flat discounts in rupees, including paise to a maximum of two decimal places.
- Coupon start at creation time and expiry at end of the selected expiry date.
- Maximum successful-redemption limit.
- All Members, Selected Members or Gender eligibility.
- Male or Female gender targeting.
- Optional Country / State / City geographic restriction.
- Active / inactive administration and derived effective status.
- Coupon preview through **Apply Coupon** during offline payment.
- Final server-side coupon revalidation during successful payment processing.
- Transactional membership activation and coupon redemption.
- One successful redemption per member per coupon.
- Historical pricing snapshots.
- Coupon listing and utilization/reporting.
- Audit trail for material V1 coupon actions.

V1 explicitly excludes:

- Member-facing online coupon redemption.
- Payment-gateway integration.
- Future-start coupon scheduling through the Superadmin UI.
- Complimentary or zero-payable coupon memberships.
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

Authorization must be enforced server-side for listing, creation, editing, activation/deactivation, reports, coupon preview during offline payment and final redemption. Hiding controls in the UI is not authorization.

Coupon preview is a POST operation and remains protected by the application's normal CSRF protection.

---

## 4. Coupon Definition

| Property | Final V1 Rule |
|---|---|
| Coupon Code | Required, unique and case-insensitive |
| Internal Description | Optional administrative note |
| Applicable Plans | One or more paid plans; at least one required |
| Discount Type | Percentage OR Flat |
| Percentage Value | Whole number from 1 through 90 |
| Flat Value | Greater than zero; maximum two decimal places; must produce a positive final payable |
| Start | Automatically current application/business time when created |
| Expiry | Selected date remains valid through 23:59:59 Asia/Kolkata |
| Usage Limit | Maximum number of successful redemptions |
| Member Eligibility | All Members OR Selected Members OR Gender |
| Gender | Male OR Female when Gender eligibility is selected |
| Geographic Restriction | Optional Country / State / City |
| Administrative Status | Active or Inactive |

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

---

## 6. Date and Time Rules

On V1 creation, `starts_at` is the current application/business time. Superadmin does not select a future start date in V1.

The selected expiry date is converted to **23:59:59 in Asia/Kolkata**.

Example: expiry date `30 September 2026` remains valid through `30 September 2026 23:59:59` business time.

An expiry that is already before the creation/start time must be rejected.

After successful redemption has begun, expiry may be extended but not shortened.

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
- Maximum 90%.
- Decimal percentage values are rejected.

### 8.2 Flat

- Entered in rupees.
- Greater than zero.
- Supports zero, one or two decimal places, for example `500`, `500.5`, `500.50`.
- More than two decimal places are rejected rather than silently rounded.
- Persisted/calculated in paise.
- The resulting discount must be **strictly less than the selected plan price**.

V1 does not support a coupon that makes Final Payable zero or negative.

Examples for a ₹2,000 plan:

- ₹1,999 flat discount — valid.
- ₹2,000 flat discount — reject.
- ₹2,001 flat discount — reject.

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
- Administrative Status.

Creation rules include:

- Valid normalized code.
- At least one plan.
- Valid type-specific discount.
- Positive usage limit.
- Valid expiry date.
- Exactly one member-eligibility mode.
- At least one member when Selected Members is chosen.
- Exactly Male or Female when Gender is chosen.
- Valid geography hierarchy when geography is supplied.

Coupon creation must fail without leaving a partially configured coupon when required persistence fails.

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
- Activate/Deactivate action.
- Report action.

Hard delete is not an operational Coupon Management action.

---

## 14. Editing Rules

### Before First Successful Redemption

Superadmin may edit the commercial/eligibility configuration, including applicable plans, discount, eligibility, geography, usage limit and expiry, subject to normal validation.

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
6. A CSRF-protected server request validates the coupon for the member and selected plan.
7. If valid, the UI displays Plan Price, Coupon Discount and Final Payable.
8. Superadmin enters payment details and Amount Received.
9. On final save, the server independently recalculates/revalidates the coupon.
10. Successful payment processing activates membership and records coupon redemption transactionally.

Existing offline payment without a coupon remains supported.

---

## 16. Apply Coupon Is Preview Only

Apply Coupon is a convenience/preview operation. It does **not** reserve coupon capacity and does **not** create a redemption.

Changing the selected plan invalidates the displayed coupon calculation. Editing the coupon code after preview also invalidates the displayed result until Apply Coupon is selected again.

The final payment save does not trust displayed/browser values.

---

## 17. Pricing and Amount Received

For a valid coupon, display:

- Plan Price.
- Coupon Discount.
- Final Payable.

Final Payable is system-calculated from authoritative plan and coupon data.

**Amount Received** remains a separate offline-payment field representing what was actually collected. It must not redefine coupon pricing. A mismatch between Amount Received and calculated Final Payable is surfaced to Superadmin according to the existing offline-payment UI behaviour.

---

## 18. Authoritative Coupon Validation Sequence

Coupon validation covers at least:

1. Coupon exists.
2. Coupon is administratively active.
3. Current business time is on/after start.
4. Current business time is on/before expiry.
5. Successful redemption count is below usage limit.
6. Selected membership plan is active and coupon-applicable.
7. Member satisfies All / Selected / Gender eligibility.
8. Canonical gender matches when Gender mode is used.
9. Member satisfies configured geography.
10. Member has not already completed a redemption for the coupon.
11. Discount produces a positive valid payable amount.

Apply Coupon performs server-side preview validation. Final payment repeats authoritative validation inside the successful-payment transaction.

---

## 19. Successful Payment, Transaction and Idempotency

Applying a coupon is not redemption. Redemption occurs only through successful payment/membership processing.

Logical lifecycle:

`Lock Payment -> Idempotency Check -> Lock/Revalidate Coupon -> Record Payment Success -> Activate Membership -> Mark Payment Processed -> Record Coupon Redemption/Audit -> Commit`

Important rules:

- An already-PROCESSED payment returns idempotently before coupon revalidation.
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

Payment records preserve the associated payment method/source and Amount Received according to the offline-payment model.

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

Use safe, actionable messages where possible, including:

- Coupon does not exist.
- Coupon is inactive.
- Coupon is not active yet.
- Coupon has expired.
- Coupon is exhausted.
- Coupon is not valid for the selected plan.
- Member is not eligible.
- Coupon is restricted to Male members.
- Coupon is restricted to Female members.
- Coupon is restricted to another location.
- Member has already used the coupon.
- Discount is invalid for the selected plan price.

Do not expose unnecessary implementation details or sensitive member information.

---

## 25. Security, Abuse and Direct-Request Rules

Development and QA must consider:

- Repeated redemption.
- Case manipulation of coupon codes.
- Direct URL/request manipulation.
- CSRF on Apply Coupon and administrative POST operations.
- Inactive/expired/exhausted replay.
- Usage-limit races.
- Browser-side price/final-payable manipulation.
- Gender manipulation.
- Geography manipulation.
- Unauthorized administrative access.
- Historical-data manipulation.

Canonical member data is authoritative for eligibility. Client-supplied gender, geography or pricing must not make an otherwise ineligible transaction valid.

The Coupon Management UI supplies active plans. Final coupon redemption independently validates the authoritative active plan and coupon-plan mapping. Additional create/edit hardening that rejects a manipulated inactive plan ID before persistence is desirable defence-in-depth but is not relied upon for financial/redemption authorization.

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
14. Deactivate/reactivate a valid coupon.
15. Apply valid percentage coupons at 1% and 90%; reject 0%, 91% and decimal percentages.
16. Apply valid flat coupons including a two-decimal value.
17. Reject flat values with more than two decimals.
18. For a ₹2,000 plan, accept ₹1,999 and reject ₹2,000/₹2,001 flat discounts.
19. Reject coupon for wrong plan/member/geography/gender.
20. Reject inactive, expired and exhausted coupons.
21. Reject second successful redemption by the same member.
22. Apply coupon and abandon payment; usage remains unchanged.
23. Force payment/membership failure; usage remains unchanged.
24. Successfully complete payment; usage increments exactly once.
25. Verify final save catches coupon deactivated or exhausted after preview.
26. Verify two concurrent final redemptions cannot exceed the last available usage slot.
27. Retry an already successful/processed payment; membership/redemption are not duplicated.
28. Verify post-redemption immutable fields cannot be changed.
29. Increase usage limit and extend expiry after redemption.
30. Reject usage-limit reduction and expiry shortening after redemption.
31. Change plan price after historical redemption; report remains unchanged.
32. Reconcile Original Gross - Total Discount = Final Payable in the report.
33. Verify historical payment method is shown in redemption rows.
34. Verify unauthorized roles/direct URLs cannot access Superadmin coupon functions.
35. Verify Apply Coupon succeeds with normal CSRF protection and fails without a valid token according to application policy.
36. Verify request tampering cannot alter authoritative discount/final payable or gender eligibility.
37. Verify coupon-code case variations cannot create duplicates or enable reuse.
38. Verify changing plan after Apply Coupon invalidates the preview.
39. Verify editing coupon code after Apply Coupon invalidates the preview.
40. Verify existing offline payment works normally without a coupon.

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
- Flat values with 0, 1, 2 and more than 2 decimal places.
- Flat discount equal to or greater than plan price.
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

---

## 28. Acceptance Criteria

Coupon Management V1 is acceptable when:

1. Only Superadmin can administer coupons and use administrative coupon endpoints.
2. Coupon code is required, normalized, unique and case-insensitive.
3. At least one plan is required.
4. Percentage discounts accept only whole values 1–90.
5. Flat discounts are positive, accept at most two decimal places and cannot make Final Payable zero/negative.
6. Exactly one eligibility mode is effective: All, Selected or Gender.
7. Selected Members requires one or more canonical members.
8. Gender requires exactly Male or Female and is evaluated from canonical member data.
9. Optional geography is validated server-side with correct hierarchy.
10. Coupon starts at current business time in the V1 creation flow.
11. Expiry is valid through 23:59:59 Asia/Kolkata.
12. Apply Coupon is CSRF-protected, server-authoritative and preview-only.
13. Apply Coupon does not consume usage.
14. Final payment independently revalidates coupon eligibility and capacity.
15. Client/browser values cannot override authoritative plan price, discount or Final Payable.
16. One member cannot successfully redeem the same coupon twice.
17. Usage limit cannot be exceeded by concurrent successful transactions.
18. Membership activation and coupon redemption remain transactionally consistent.
19. Successful-payment retries are idempotent.
20. Historical financial reporting uses immutable transaction/redemption snapshots.
21. Coupon listing shows applicable plans, usage and effective status.
22. After first redemption, commercial/eligibility configuration is immutable; only allowed operational changes remain.
23. Coupon report reconciles successful redemption count, Original Gross, Total Discount and Final Payable and shows redemption-level payment method.
24. Material V1 coupon actions and successful redemptions are auditable.
25. Existing offline payment without a coupon continues to work.
26. No coupon-only hard-delete/void mechanism is treated as part of V1.

---

## 29. Development Architecture Contract

V1 must preserve these boundaries:

- Coupon administration belongs to the existing Admin coupon flow.
- Coupon eligibility/pricing belongs to the central coupon service, not duplicated controller/UI logic.
- Offline-payment processing remains centralized in the membership-payment service.
- Membership activation continues through the existing membership-purchase service.
- Apply Coupon is only a preview and never becomes the source of truth for final payment.
- Final successful payment performs authoritative coupon revalidation under transaction/locking.
- Financial history uses integer paise and immutable snapshots.
- UI validation improves usability but never replaces server validation.
- Existing CSRF, Superadmin authorization, validation and application patterns are reused rather than bypassed.
- No parallel payment or membership-activation architecture should be introduced for coupons.

---

## 30. Deferred / Future Enhancements

The following are explicitly outside V1 and must not be treated as missing V1 defects:

- Online payment-gateway coupon entry/redemption.
- Future-start coupon scheduling UI.
- Complimentary/100%-discount memberships.
- Formal offline-payment reversal/void and coupon-capacity restoration.
- Coupon stacking.
- Referral integration.
- Automatic promotion selection.
- Minimum-order/value campaign rules.
- Public/member promotional banners.
- Advanced campaign analytics beyond the V1 utilization/financial report.
- More detailed report summary cards or actor presentation where operationally useful.

Any future payment reversal must be implemented through the authoritative payment lifecycle rather than deleting coupon-redemption history.

---

## 31. Definition of Done — V1

Coupon Management V1 development is complete when:

- V1 schema and incremental deployment SQL are available under the project's DB deployment rules.
- Superadmin routes/controllers/services/views are integrated with existing architecture.
- Coupon create/list/edit/status/report flows work.
- Offline Apply Coupon works with CSRF protection.
- Final payment revalidates coupon server-side.
- Transaction/concurrency/idempotency protections are present.
- Successful redemption and material coupon changes are auditable.
- Historical report values are snapshot-based.
- Existing no-coupon offline payment is not regressed.
- The QA journeys in this document are executed in the target environment before production release.

This document describes the **final agreed Coupon Management V1 behaviour**. Future-scope items must be implemented through explicit product/development changes rather than being inferred as unfinished V1 work.
