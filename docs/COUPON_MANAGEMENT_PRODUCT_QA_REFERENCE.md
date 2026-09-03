# Coupon Management — Product, Development & QA Reference

**Project:** SikhAnandKaraj  
**Feature:** Coupon Management  
**Primary Role:** Superadmin  
**Initial Payment Channel:** Offline Payment  
**Future Payment Channel:** Online Payment Gateway  
**Document purpose:** Product specification and common reference for Development and QA.

---

## 1. Product Objective

Coupon Management provides Superadmin with a controlled way to create promotional pricing rules for paid membership plans, restrict eligibility, apply coupons during payment collection, and report on coupon usage.

A coupon must be treated as a **controlled pricing rule attached to a payment transaction**, not merely as a code that subtracts an amount.

The initial implementation will support offline payments. The coupon domain and validation rules must remain payment-channel independent so that the same rules can later be consumed by the online payment gateway without redesigning Coupon Management.

---

## 2. V1 Scope

V1 includes:

- Coupon creation by Superadmin.
- Unique coupon codes.
- One or multiple applicable paid membership plans.
- Percentage or flat-value discounts.
- Start and expiry validity.
- Maximum successful-redemption limit.
- Eligibility for all members, explicitly selected members, or members of a selected gender (Male/Female).
- Optional geographic restriction.
- Active/inactive management.
- Coupon application during offline membership payment.
- Server-authoritative eligibility validation.
- Coupon redemption tracking.
- Coupon reporting.
- Auditability of material coupon actions.

V1 explicitly excludes:

- Member-facing online coupon redemption.
- Payment-gateway integration.
- Coupon stacking.
- Referral + coupon combinations.
- Automatic coupon application.
- Minimum-order-value campaigns.
- Public promotional banners.
- Complex campaign/rule engines.

---

## 3. Access and Authorization

Coupon Management is a **Superadmin-only** feature.

Authorization must be enforced server-side for coupon listing, creation, editing, activation/deactivation, reports, validation during offline payment and redemption. UI hiding alone is not authorization.

---

## 4. Coupon Definition

| Property | Product Rule |
|---|---|
| Coupon Code | Required, unique and case-insensitive |
| Internal Description | Optional administrative note; never member-facing by default |
| Applicable Plans | One or more paid plans; at least one required |
| Discount Type | Percentage OR Flat Amount |
| Discount Value | Required and validated according to discount type |
| Start | Date/time; default is current application/business time |
| Expiry | Selected date remains valid through 23:59:59 in business timezone |
| Usage Limit | Maximum number of successful redemptions |
| Member Eligibility | All Members OR Selected Members OR Gender; exactly one mode required |
| Gender | Required only when Member Eligibility = Gender; Male OR Female |
| Geographic Restriction | Optional country/state/city restriction |
| Administrative Status | Active or Inactive |

Coupon codes must be normalized for comparison so `KOTA25`, `kota25` and `Kota25` cannot represent separate coupons.

---

## 5. Coupon Status

Recommended effective states are **Scheduled**, **Active**, **Inactive**, **Expired** and **Exhausted**. Scheduled means administratively active but not yet started. Active means within validity with remaining capacity. Inactive is deliberately disabled. Expired means the expiry has passed. Exhausted means successful redemptions have reached the usage limit. Expired and Exhausted are derived automatically.

---

## 6. Date and Time Rules

Start date/time defaults to current application/business time. If Superadmin selects an expiry date, the coupon remains valid through the end of that selected calendar day.

Example: selected expiry `30 September 2026` means effective expiry `30 September 2026 23:59:59` in the configured business timezone. Do not model the selected expiry as `00:00` at the beginning of that date.

---

## 7. Plan Eligibility

Superadmin may select one or multiple **paid membership plans**. At least one plan is mandatory. Plan eligibility must be checked again when the payment transaction is finalized, not only when the coupon is initially applied.

---

## 8. Discount Rules

Exactly one discount type is allowed.

### Percentage Discount

Recommended V1 range is greater than 0% and maximum 90% unless the business deliberately introduces complimentary/100%-discount memberships.

### Flat Discount

Must be greater than zero and must not exceed the eligible plan price. A coupon that would create a negative payable amount must be rejected rather than silently converted into a zero-price transaction.

---

## 9. Member Eligibility

Exactly one eligibility mode is required:

1. **All Members** — every otherwise eligible member can use the coupon.
2. **Selected Members** — only one or more explicitly selected members can use the coupon.
3. **Gender** — only members of the selected gender can use the coupon. Superadmin must select exactly one of **Male** or **Female**.

These modes are mutually exclusive. For example, a coupon cannot simultaneously be configured as Selected Members and Female Members.

Selected Members should support one or multiple explicit members rather than being limited to a single Member ID. Persisted eligibility must be tied to the canonical member identity.

For Gender eligibility, the member's canonical profile gender stored by SikhAnandKaraj is authoritative. Gender must be validated server-side at both coupon application and final payment completion. Client-supplied gender values must never determine eligibility.

A member must not redeem the same coupon more than once. This rule is **member + coupon based**, not member + coupon + plan based. If a member uses a coupon for one plan, the same coupon cannot later be reused by that member for another purchase, renewal or upgrade.

---

## 10. Geographic Eligibility

Geographic restriction is optional and can be combined with any of the three Member Eligibility modes. It therefore acts as an additional restriction rather than another Member Eligibility mode.

The eligibility hierarchy should support Country, State/Province and City and must not assume India-only operation.

Examples:

- All Members + Rajasthan means all otherwise eligible members currently in Rajasthan.
- Female + Rajasthan means only otherwise eligible female members currently in Rajasthan.
- Male + Rajasthan / Kota means only otherwise eligible male members currently in Kota, Rajasthan.
- Selected Members + geography means selected members must also satisfy the configured geographic restriction.

Geography is evaluated against the member's current location stored in Basic Details at redemption time. A later location change must not alter historical coupon reporting.

---

## 11. Usage Limit

Usage limit means the maximum number of **successful coupon redemptions**. Opening the payment screen, entering/validating a coupon, calculating a price, abandoning payment or a failed transaction does not consume usage. Usage is consumed only when payment/membership activation successfully completes.

### Concurrency Rule

The usage limit is absolute. If limit is 100 and usage is 99, two concurrent transactions cannot both become redemption #100. Final eligibility and capacity must be revalidated transactionally.

---

## 12. Coupon Creation Journey

Recommended sections are Coupon Details, Applicable Plans, Discount, Validity, Usage, Member Eligibility, Geographic Restriction and Status.

### Member Eligibility

Superadmin must select exactly one:

- All Members.
- Selected Members — show member selection; at least one member required.
- Gender — show gender selection; exactly one of Male or Female required.

Controls that do not belong to the selected mode should be cleared/ignored server-side so stale client values cannot create ambiguous eligibility.

Geographic restriction remains optional for all three modes.

Coupon creation must fail atomically if any required rule is invalid.

---

## 13. Coupon Listing

Recommended listing information: Coupon code, Discount, Applicable plans, Eligibility summary, Start date, Expiry date, Usage limit, Number used, Effective status and Actions.

Eligibility summary should clearly distinguish **All Members**, **Selected Members**, **Male Members** and **Female Members**, and may additionally summarize geographic restriction.

Recommended actions are Edit, Activate/Deactivate where applicable, and View Report. Hard deletion should not be offered as the normal operational action.

---

## 14. Editing Rules

### Before First Redemption

Superadmin may edit applicable plans, discount type/value, member eligibility (including All/Selected/Gender and Male/Female selection), geographic restrictions, usage limit, expiry and active/inactive status.

### After First Successful Redemption

Do not allow changes to Coupon code, Discount type/value, Applicable plans, Member eligibility mode, Selected Members, Gender selection, or Geographic restrictions.

Allow only controlled operational changes: increase usage limit, extend expiry, deactivate, or reactivate when other eligibility conditions still permit use.

After redemption begins, usage limit can only increase and expiry may be extended but not shortened.

---

## 15. Offline Payment Journey

1. Superadmin selects the member.
2. Superadmin selects the paid membership plan.
3. System determines current plan price.
4. Superadmin optionally enters a Coupon Code.
5. Superadmin selects **Apply Coupon**.
6. Server validates coupon eligibility, including member eligibility mode and gender where applicable.
7. If valid, system displays discount and calculated final payable amount.
8. Superadmin enters offline payment details.
9. On final save/activation, server revalidates coupon eligibility and capacity.
10. Payment, membership activation and coupon redemption complete as one consistent transaction.

Existing valid offline payments without coupons must continue to work.

---

## 16. Pricing Breakdown

After a valid coupon is applied, display Plan Price, Coupon Discount and **Final Payable**. Final Payable is system-calculated.

If the offline workflow separately records **Amount Received**, it may remain editable because it represents actual collection, but it must not redefine coupon calculation. A mismatch should produce an explicit warning/confirmation according to offline-payment rules.

---

## 17. Coupon Validation Sequence

When a coupon is applied, the server should evaluate at least:

1. Coupon exists.
2. Coupon is administratively Active.
3. Current business time is on/after start.
4. Current business time is on/before expiry.
5. Successful redemption count is below usage limit.
6. Selected plan is eligible.
7. Member satisfies the configured Member Eligibility mode:
   - All Members; or
   - explicitly included in Selected Members; or
   - member's canonical gender matches the configured Male/Female gender.
8. Member satisfies any geographic restriction.
9. Member has not previously successfully redeemed the coupon.
10. Discount produces a valid payable amount.

The final payment save must repeat all time-sensitive and transactional validations. Client-side validation may improve UX but can never replace server-side coupon validation.

---

## 18. Redemption Transaction Rule

**Applying a coupon is not redemption.** Redemption occurs only when the related payment and membership activation complete successfully.

The desired logical transaction is:

`Validate Coupon -> Confirm Payment -> Activate Membership -> Record Coupon Redemption`

If membership activation or payment persistence fails, coupon usage must not be consumed. If coupon redemption cannot safely complete, the transaction must not leave the member activated with an unrecorded promotional discount.

---

## 19. Financial Snapshot Requirement

Historical coupon/payment reporting must never depend on current plan prices or current coupon configuration.

At transaction time preserve the plan purchased, original plan price, coupon identity/code, discount type/value, actual discount amount, final payable, amount received where applicable, payment mode, payment date/time and administrative actor.

Historical reporting should also preserve enough coupon context to explain the redemption without relying on later coupon edits.

---

## 20. Coupon Report

Coupon Report should show campaign utilization and individual redemptions.

### Summary

Recommended information includes Coupon code, Effective status, Validity, Applicable plans, Eligibility mode/target, Usage, Original gross value, Total discount given and Net/final payable value.

For gender coupons the report summary should explicitly show **Male Members** or **Female Members**.

### Redemption Rows

Recommended information includes Redemption/payment date/time, Member ID, appropriate member display identity, Plan, Original plan price, Discount amount, Final payable, Payment mode, Transaction state and administrative actor where useful.

Do not unnecessarily expose sensitive matrimonial-profile data such as Aadhaar information, DOB, full contact details or unrelated verification data.

---

## 21. Audit Trail

Material coupon actions should be auditable, including coupon creation/editing, activation/deactivation, usage-limit increases, expiry extensions, redemption and payment/redemption voids.

If Member Eligibility or Gender is changed before first redemption, the audit trail should preserve old and new values.

Audit information should identify Actor, Timestamp, Action, Previous value and New value. Historical audit records must not be overwritten.

---

## 22. Voids / Incorrect Offline Entries

Financial/redemption history should not be hard-deleted when an offline payment was entered incorrectly.

If a completed offline payment is formally voided, the historical transaction remains auditable, the associated coupon redemption is marked voided rather than deleted, and effective coupon capacity is restored exactly once.

---

## 23. User-Facing / Administrative Error Behaviour

Use safe, actionable reasons where possible, including:

- Coupon does not exist.
- Coupon is inactive/not active yet/expired/exhausted.
- Coupon is not valid for the selected plan.
- Member is not eligible for this coupon.
- Coupon is restricted to Male members.
- Coupon is restricted to Female members.
- Coupon is restricted to another location.
- Member has already used this coupon.
- Discount is invalid for the selected plan price.

Do not reveal unnecessary internal implementation details.

---

## 24. Abuse and Misuse Risks

Development and QA must explicitly consider repeated redemption, coupon-code case manipulation, direct request manipulation, expired/inactive coupon replay, usage-limit races, financial tampering, unauthorized administration and historical manipulation.

### Gender Eligibility Manipulation

A request must not become eligible by altering a submitted gender value. Eligibility must be evaluated against the member's canonical stored gender. Direct requests attempting to use a Male-only coupon for a Female member, or vice versa, must be rejected even if client validation/UI is bypassed.

---

## 25. QA Core Test Journeys

QA should cover at least:

1. Create an all-member coupon for one plan.
2. Create an all-member coupon for multiple plans.
3. Create a coupon for one selected member.
4. Create a coupon for multiple selected members.
5. Create a Male Members coupon and redeem for an eligible male member.
6. Create a Female Members coupon and redeem for an eligible female member.
7. Reject Male coupon for a female member.
8. Reject Female coupon for a male member.
9. Combine gender eligibility with state restriction and verify both rules are required.
10. Combine gender eligibility with city restriction and verify both rules are required.
11. Create a geographically restricted coupon.
12. Create a future-start coupon and verify Scheduled behaviour.
13. Validate start-time and expiry end-of-day boundaries.
14. Deactivate/reactivate a valid unused coupon.
15. Apply valid percentage and flat coupons to offline payment.
16. Reject coupon for wrong plan/member/geography/gender.
17. Reject inactive, expired and exhausted coupons.
18. Reject second redemption by the same member.
19. Apply coupon and abandon payment; usage remains unchanged.
20. Force payment/activation failure; usage remains unchanged.
21. Successfully complete payment; usage increments exactly once.
22. Verify final validation catches coupon deactivated/exhausted after initial Apply.
23. Verify concurrent final redemption cannot exceed usage limit.
24. Verify post-redemption immutable fields, including gender target, cannot be changed.
25. Increase usage limit and extend expiry after redemption.
26. Reject usage-limit reduction and expiry shortening after redemption.
27. Change plan price after historical redemption; report remains unchanged.
28. Verify coupon report totals against transactions.
29. Void erroneous offline transaction; capacity restored exactly once and history retained.
30. Verify unauthorized roles/direct URLs cannot access Superadmin coupon functions.
31. Verify request tampering cannot alter discount/final payable or gender eligibility.
32. Verify coupon-code case variations cannot create duplicates or enable reuse.
33. Verify existing offline payment works normally without a coupon.

---

## 26. Boundary and Negative Cases for QA

QA should specifically test empty/whitespace/duplicate-case coupon codes; invalid code characters; no plan; manipulated multiple discount types; zero/negative/excessive discounts; zero/negative usage limits; Selected Members with no members; Gender mode with no gender; Gender mode with both Male and Female values submitted through a manipulated request; invalid member IDs; invalid geography relationships; expiry before start; exact date/time boundaries; usage exactly at limit; concurrent final redemption; repeated submits; retry after success; coupon changed between Apply and Save; member location changed between Apply and Save; member gender tampering; selected plan changed after Apply; coupon removed after Apply; and client-side amount tampering.

---

## 27. Acceptance Criteria

The feature is acceptable when:

1. Only Superadmin can access Coupon Management and administrative actions.
2. Coupon code is required, unique and case-insensitive.
3. At least one paid membership plan is required.
4. Exactly one valid discount type/value is required.
5. Exactly one Member Eligibility mode is required: All Members, Selected Members, or Gender.
6. Selected Members requires at least one valid member.
7. Gender eligibility requires exactly one target: Male or Female.
8. Gender eligibility is validated against canonical member data server-side.
9. Geographic restriction is optional and can further restrict any eligibility mode.
10. Expiry remains valid through the end of the selected calendar day in the configured business timezone.
11. Scheduled, Active, Inactive, Expired and Exhausted states behave correctly.
12. Coupon can only be applied to an eligible member, plan, gender and geography as configured.
13. A member can successfully redeem a given coupon only once.
14. Usage limit counts successful effective redemptions only.
15. Applying/validating a coupon does not consume usage.
16. Final eligibility is revalidated during payment completion.
17. Successful payment/membership activation and coupon redemption behave transactionally.
18. Coupon usage cannot exceed its limit under concurrent requests.
19. After first redemption, financial and eligibility rules, including gender target, become immutable.
20. Usage limit can only increase after redemption begins.
21. Expiry can be extended but not shortened after redemption begins.
22. Historical transactions preserve original plan price, discount and final payable values.
23. Server-calculated pricing is authoritative.
24. Coupon report accurately represents eligibility, utilization and financial impact.
25. Voided transactions remain auditable and restore capacity exactly once.
26. Material Coupon Management actions are auditable.
27. Existing offline membership payment remains functional without a coupon.
28. Coupon rules are reusable by a future online-payment channel.

---

## 28. Long-Term Maintainability Principle

Coupon rules must remain separate from the mechanics of a particular payment channel.

`Payment Channel -> Coupon Eligibility/Calculation -> Payment Completion -> Coupon Redemption`

Offline payment is the first consumer. A future online payment gateway should call the same authoritative coupon eligibility, calculation and redemption behaviour rather than implementing a second coupon system.

Avoid building V1 as a generic promotions engine. Keep the domain focused on membership-plan coupons while preserving clean boundaries for future payment integration.

---

## 29. Definition of Done

Coupon Management is complete only when product rules are implemented or explicitly revised; client/server validation is covered; authorization is server-side; offline payment works with and without coupons; All/Selected/Gender eligibility is correctly enforced; concurrency prevents over-redemption; historical financial values remain stable; reporting reconciles with completed/voided transactions; material actions are auditable; and QA has executed happy, negative, boundary, direct-access/security and regression scenarios.

---

**Reference status:** Product baseline for Coupon Management V1. Development and QA should use this document as the common source of expected behaviour unless a later approved product decision supersedes it.
