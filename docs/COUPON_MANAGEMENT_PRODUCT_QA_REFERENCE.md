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
- Eligibility for all members or explicitly selected members.
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
- Gender-based coupons.
- Minimum-order-value campaigns.
- Public promotional banners.
- Complex campaign/rule engines.

---

## 3. Access and Authorization

Coupon Management is a **Superadmin-only** feature.

Authorization must be enforced server-side for:

- Coupon listing.
- Coupon creation.
- Coupon editing.
- Activation/deactivation.
- Coupon reports.
- Coupon validation during offline payment.
- Coupon redemption.

UI hiding alone is not authorization.

---

## 4. Coupon Definition

A coupon contains the following business information.

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
| Member Eligibility | All Members OR Selected Members; exactly one mode required |
| Geographic Restriction | Optional country/state/city restriction |
| Administrative Status | Active or Inactive |

Coupon codes must be normalized for comparison so `KOTA25`, `kota25` and `Kota25` cannot represent separate coupons.

---

## 5. Coupon Status

The UI should communicate effective coupon state rather than relying only on an Active boolean.

Recommended effective states:

### Scheduled

Coupon is administratively Active but its start date/time has not arrived.

### Active

Coupon is administratively Active, within its validity period and has remaining redemption capacity.

### Inactive

Coupon has been deliberately disabled by Superadmin.

### Expired

Current business time is later than the coupon expiry.

### Exhausted

Successful redemption count has reached the usage limit.

Expired and Exhausted states should be derived automatically and should not require manual Superadmin intervention.

---

## 6. Date and Time Rules

Start date/time defaults to current application/business time.

If Superadmin selects an expiry date, the coupon remains valid through the end of that selected calendar day.

Example:

- Selected expiry: `30 September 2026`
- Effective expiry: `30 September 2026 23:59:59` in the configured business timezone.

Do not model the selected expiry as `00:00` at the beginning of that date.

All coupon validation, listing state and payment redemption must use a consistent application/business timezone.

---

## 7. Plan Eligibility

Superadmin may select one or multiple **paid membership plans**.

At least one plan is mandatory.

A coupon cannot be applied to a payment for a plan that is not included in the coupon's eligible plans.

Plan eligibility must be checked again when the payment transaction is finalized, not only when the coupon is initially applied.

---

## 8. Discount Rules

Exactly one discount type is allowed.

### Percentage Discount

Recommended V1 range:

- Greater than 0%.
- Maximum 90% unless the business deliberately introduces complimentary/100%-discount memberships.

A 100% coupon is effectively a complimentary membership mechanism and must not be introduced accidentally through normal promotional configuration.

### Flat Discount

- Must be greater than zero.
- Must not exceed the eligible plan price.
- A coupon that would create a negative payable amount must be rejected.

The system must not silently convert an invalid discount into a zero-price transaction.

---

## 9. Member Eligibility

Exactly one eligibility mode is required:

1. **All Members**
2. **Selected Members**

Selected Members should support one or multiple explicit members rather than being limited to a single Member ID.

The administrative selection experience may allow searching by existing member identifiers/details, but persisted eligibility must be tied to the canonical member identity.

A member must not redeem the same coupon more than once.

This rule is **member + coupon based**, not member + coupon + plan based.

Therefore, if a member uses a coupon for one plan, the same coupon cannot later be reused by that member for another purchase, renewal or upgrade.

If marketing wants another promotion for the same member, a different coupon should be issued.

---

## 10. Geographic Eligibility

Geographic restriction is optional.

The design should not assume India-only operation because SikhAnandKaraj supports more than one country.

The eligibility hierarchy should conceptually support:

- Country
- State/Province
- City

A coupon with no geographic restriction is available across all otherwise eligible locations.

When geography is configured, eligibility is evaluated against the member's **current location stored in Basic Details at redemption time**.

Examples:

- Rajasthan — eligible members whose current state is Rajasthan.
- Rajasthan / Kota — eligible members whose current city is Kota within Rajasthan.
- Canada / Ontario — future-compatible without redesigning coupon rules.

A change to the member's location after a completed redemption must not alter historical coupon reporting.

---

## 11. Usage Limit

Usage limit means the maximum number of **successful coupon redemptions**.

The following do not consume usage:

- Opening the payment screen.
- Entering a coupon.
- Successfully validating a coupon.
- Calculating the discounted price.
- Abandoning/closing the payment flow.
- A payment transaction that ultimately fails.

Usage is consumed only when the payment/membership transaction successfully completes.

### Concurrency Rule

The usage limit is absolute.

Example:

- Limit: 100
- Used: 99
- Two admins/transactions attempt the final coupon redemption concurrently.

Only one transaction may become redemption #100.

Coupon eligibility and capacity must therefore be revalidated during final transaction completion using concurrency-safe/transactional behaviour.

---

## 12. Coupon Creation Journey

Superadmin opens Coupon Management and chooses **Create Coupon**.

Recommended sections:

### Coupon Details

- Coupon Code — required.
- Internal Description — optional.

### Applicable Plans

- One or multiple paid plans.
- At least one required.

### Discount

- Percentage OR Flat Amount.
- Corresponding value required.

### Validity

- Start date/time defaults to current time.
- Expiry date required according to agreed business validation.

### Usage

- Maximum redemptions required.

### Member Eligibility

- All Members OR Selected Members.
- If Selected Members, at least one member required.

### Geographic Restriction

- Optional.
- Country/state/city selections must follow valid master-data relationships.

### Status

- Active or Inactive.

Coupon creation must fail atomically if any required rule is invalid.

---

## 13. Coupon Listing

Recommended listing information:

- Coupon code.
- Discount.
- Applicable plans.
- Eligibility summary.
- Start date.
- Expiry date.
- Usage limit.
- Number used.
- Effective status.
- Actions.

Recommended actions:

- Edit.
- Activate/Deactivate where applicable.
- View Report.

Hard deletion should not be offered as the normal operational action. Deactivation preserves campaign and financial history.

---

## 14. Editing Rules

### Before First Redemption

Superadmin may edit:

- Applicable plans.
- Discount type/value.
- Member eligibility.
- Geographic restrictions.
- Usage limit.
- Expiry.
- Active/inactive status.

Coupon code should preferably remain stable once created. If editing the code is allowed before first use, uniqueness and case-insensitive normalization still apply.

### After First Successful Redemption

The historical meaning of the coupon must be protected.

Do not allow changes to:

- Coupon code.
- Discount type.
- Discount value.
- Applicable plans.
- Member eligibility.
- Geographic restrictions.

Allow only controlled operational changes:

- Increase usage limit.
- Extend expiry date.
- Deactivate coupon.
- Reactivate only when other eligibility conditions still permit use.

### Usage Limit Editing

After redemption begins, usage limit can only increase.

Example:

- Existing limit: 100
- Used: 20
- `100 -> 150` is allowed.
- `100 -> 50` is not allowed even though 50 is greater than current usage.

This prevents changing the meaning/capacity of a campaign that may already have been communicated.

### Expiry Editing

After redemption begins, expiry may be extended but should not be shortened.

---

## 15. Offline Payment Journey

Coupon application belongs inside the existing offline membership payment/activation workflow.

Recommended journey:

1. Superadmin selects the member.
2. Superadmin selects the paid membership plan.
3. System determines current plan price.
4. Superadmin optionally enters a Coupon Code.
5. Superadmin selects **Apply Coupon**.
6. Server validates coupon eligibility.
7. If valid, system displays discount and calculated final payable amount.
8. Superadmin enters offline payment details.
9. On final save/activation, server revalidates coupon eligibility and capacity.
10. Payment, membership activation and coupon redemption complete as one consistent transaction.

Coupon entry is optional. Existing valid offline payments without coupons must continue to work.

---

## 16. Pricing Breakdown

After a valid coupon is applied, Superadmin should see a clear breakdown.

Example:

| Item | Amount |
|---|---:|
| Plan Price | ₹4,999.00 |
| Coupon Discount — KOTA25 (25%) | -₹1,249.75 |
| **Final Payable** | **₹3,749.25** |

The **Final Payable** amount is system-calculated.

If the offline workflow separately records **Amount Received**, it may remain editable because it represents actual collection, but it must not redefine the coupon calculation.

If Amount Received differs from Final Payable, the system should provide an explicit warning/confirmation according to the offline-payment product rules rather than silently treating the values as equivalent.

---

## 17. Coupon Validation Sequence

When a coupon is applied, the server should evaluate at least:

1. Coupon exists.
2. Coupon is administratively Active.
3. Current business time is on/after start.
4. Current business time is on/before expiry.
5. Successful redemption count is below usage limit.
6. Selected plan is eligible.
7. Member is eligible under All Members/Selected Members rule.
8. Member satisfies any geographic restriction.
9. Member has not previously successfully redeemed the coupon.
10. Discount produces a valid payable amount.

The final payment save must repeat all time-sensitive and transactional validations.

Client-side validation may improve UX but can never replace server-side coupon validation.

---

## 18. Redemption Transaction Rule

**Applying a coupon is not redemption.**

Redemption occurs only when the related payment and membership activation complete successfully.

The desired logical transaction is:

`Validate Coupon -> Confirm Payment -> Activate Membership -> Record Coupon Redemption`

These steps must behave atomically from the product perspective.

If membership activation or payment persistence fails, coupon usage must not be consumed.

If coupon redemption cannot be safely completed, the transaction must not leave the member activated with an unrecorded promotional discount.

---

## 19. Financial Snapshot Requirement

Historical coupon/payment reporting must never depend on current plan prices or current coupon configuration.

At transaction time, preserve the financial facts required to reconstruct the transaction, including conceptually:

- Plan purchased.
- Original plan price.
- Coupon code/identity.
- Discount type.
- Discount value/rate.
- Actual discount amount.
- Final payable amount.
- Amount received where applicable.
- Payment mode.
- Payment date/time.
- Administrative actor.

Example:

If Plus costs ₹4,999 when KOTA25 is redeemed and later changes to ₹5,999, the historical transaction must continue to report the original ₹4,999 price and its original discount.

---

## 20. Coupon Report

Coupon Report should show campaign utilization and individual redemptions.

### Summary

Recommended information:

- Coupon code.
- Effective status.
- Validity.
- Applicable plans.
- Usage: `23 / 100`.
- Original gross value represented by redeemed transactions.
- Total discount given.
- Net/final payable value represented by redeemed transactions.

### Redemption Rows

Recommended information:

- Redemption/payment date and time.
- Member ID.
- Appropriate member display identity.
- Plan.
- Original plan price.
- Discount amount.
- Final payable amount.
- Payment mode.
- Transaction state.
- Superadmin/admin actor where useful.

Do not unnecessarily expose sensitive matrimonial-profile data such as Aadhaar information, DOB, full contact details or unrelated verification data in Coupon Report.

---

## 21. Audit Trail

Material coupon actions should be auditable.

At minimum retain evidence for:

- Coupon created.
- Coupon edited.
- Coupon activated.
- Coupon deactivated.
- Usage limit increased.
- Expiry extended.
- Coupon redeemed.
- Related payment/redemption voided where applicable.

Audit information should identify:

- Actor.
- Timestamp.
- Action.
- Relevant previous value.
- Relevant new value.

Historical audit records must not be overwritten by later edits.

---

## 22. Voids / Incorrect Offline Entries

Financial/redemption history should not be hard-deleted when an offline payment was entered incorrectly.

Recommended conceptual transaction states include:

- Completed.
- Cancelled/Voided.

If an erroneous completed offline payment is formally voided:

- The historical transaction remains visible/auditable.
- The associated coupon redemption is marked voided rather than deleted.
- Effective coupon capacity is restored.

Example:

- Limit: 100
- Completed coupon redemptions: 23
- One redemption is voided
- Effective used count: 22 / 100
- Report still contains the voided transaction with its state.

The implementation must ensure a void cannot restore coupon capacity more than once.

---

## 23. User-Facing / Administrative Error Behaviour

Avoid generic `Invalid coupon` errors when a safe, actionable administrative reason can be provided.

Examples:

- Coupon does not exist.
- Coupon is inactive.
- Coupon is not active yet.
- Coupon has expired.
- Coupon usage limit has been reached.
- Coupon is not valid for the selected plan.
- Member is not eligible for this coupon.
- Coupon is restricted to another location.
- Member has already used this coupon.
- Discount is invalid for the selected plan price.

Do not reveal unnecessary internal implementation details in errors.

---

## 24. Abuse and Misuse Risks

Development and QA must explicitly consider:

### Repeated Redemption

A member must not repeatedly use the same coupon across purchases/plans.

### Code Case Manipulation

`KOTA25` and `kota25` must not bypass uniqueness or redemption rules.

### Direct Request Manipulation

Changing member ID, plan ID, discount amount, coupon ID or calculated final amount in client requests must not bypass server rules.

### Expired/Inactive Coupon Replay

A previously validated coupon must not remain valid if it expires, is deactivated or becomes exhausted before payment completion.

### Usage-Limit Race

Concurrent final redemptions must not exceed the configured usage limit.

### Financial Tampering

Client-provided discount/final payable values must not be authoritative. The server must calculate/verify financial values from canonical plan and coupon rules.

### Unauthorized Administration

Non-Superadmin users must not create, edit, activate, deactivate or report on coupons through direct URL/API requests.

### Historical Manipulation

Coupon edits must not change previously recorded transaction values.

---

## 25. QA Core Test Journeys

QA should cover at least the following end-to-end journeys.

1. Create an all-member coupon for one plan.
2. Create an all-member coupon for multiple plans.
3. Create a coupon for one selected member.
4. Create a coupon for multiple selected members.
5. Create a geographically restricted coupon.
6. Create a future-start coupon and verify Scheduled behaviour.
7. Validate start-time boundary.
8. Validate expiry-date end-of-day boundary.
9. Deactivate an unused coupon.
10. Reactivate a valid coupon.
11. Apply valid percentage coupon to offline payment.
12. Apply valid flat coupon to offline payment.
13. Reject coupon for wrong plan.
14. Reject coupon for wrong member.
15. Reject coupon for wrong geography.
16. Reject inactive coupon.
17. Reject expired coupon.
18. Reject exhausted coupon.
19. Reject second redemption by same member.
20. Apply coupon and abandon payment; verify usage is unchanged.
21. Force payment/activation failure; verify coupon usage is unchanged.
22. Successfully complete payment; verify usage increments exactly once.
23. Verify final validation catches coupon deactivated after initial Apply.
24. Verify final validation catches coupon exhausted after initial Apply.
25. Verify concurrent final redemption cannot exceed usage limit.
26. Verify post-redemption immutable fields cannot be changed.
27. Increase usage limit after redemption and verify new capacity.
28. Attempt to reduce usage limit after redemption and verify rejection.
29. Extend expiry after redemption.
30. Attempt to shorten expiry after redemption and verify rejection.
31. Change plan price after a historical redemption and verify report remains unchanged.
32. Verify coupon report totals against underlying successful transactions.
33. Void an erroneous offline transaction and verify effective coupon capacity is restored once.
34. Verify report retains the voided transaction.
35. Verify unauthorized roles/direct URLs cannot access Superadmin coupon functions.
36. Verify request tampering cannot alter calculated discount/final payable amount.
37. Verify coupon-code case variations cannot create duplicates or enable reuse.
38. Verify existing offline payment flow works normally when no coupon is entered.

---

## 26. Boundary and Negative Cases for QA

QA should specifically test:

- Empty coupon code.
- Whitespace around coupon code.
- Same code with different casing.
- Invalid/special-character coupon code according to final validation rule.
- No applicable plan selected.
- Both discount types submitted through manipulated request.
- Zero discount.
- Negative discount.
- Percentage above allowed maximum.
- Flat discount equal to plan price.
- Flat discount greater than plan price.
- Zero usage limit.
- Negative usage limit.
- Selected Members mode with no members.
- Invalid/nonexistent member ID submitted directly.
- Geography with invalid country/state/city relationship.
- Expiry before start.
- Coupon at exact start boundary.
- Coupon at exact expiry boundary.
- Coupon immediately after expiry.
- Usage exactly at limit.
- Multiple browser tabs applying the same final remaining redemption.
- Repeated payment-submit requests/double click.
- Refresh/retry after successful transaction.
- Coupon edited/deactivated between Apply and Save.
- Member location changed between Apply and Save.
- Selected plan changed after Apply.
- Coupon removed after Apply.
- Client-side calculated amount altered before submission.

---

## 27. Acceptance Criteria

The feature is acceptable when all of the following are true:

1. Only Superadmin can access Coupon Management and coupon administrative actions.
2. Coupon code is required, unique and compared case-insensitively.
3. At least one paid membership plan is required.
4. Exactly one valid discount type/value is required.
5. Coupon requires either All Members or one/more Selected Members.
6. Geographic restriction is optional and respects valid country/state/city relationships.
7. Expiry remains valid through the end of the selected calendar day in the configured business timezone.
8. Coupon can be administratively activated/deactivated.
9. Scheduled, Active, Inactive, Expired and Exhausted effective states behave correctly.
10. Coupon cannot be hard-deleted as a normal operational action once financial/history integrity is relevant.
11. Coupon can only be applied to an eligible member, plan and geography.
12. A member can successfully redeem a given coupon only once.
13. Usage limit counts successful effective redemptions only.
14. Applying/validating a coupon does not consume usage.
15. Final coupon eligibility is revalidated during payment completion.
16. Successful payment/membership activation and coupon redemption behave transactionally.
17. Coupon usage cannot exceed its configured limit under concurrent requests.
18. After first redemption, coupon financial and eligibility rules become immutable.
19. Usage limit can only be increased after redemption begins.
20. Expiry can be extended but not shortened after redemption begins.
21. Historical transactions preserve the original plan price, discount and final payable values.
22. Server-calculated pricing is authoritative; client tampering cannot alter the discount.
23. Coupon report accurately represents utilization and financial impact.
24. Voided transactions remain auditable and restore effective coupon capacity exactly once.
25. Material Coupon Management actions are auditable.
26. Existing offline membership payment remains functional without a coupon.
27. Coupon domain/validation logic is reusable by a future online-payment channel without redefining product rules.

---

## 28. Long-Term Maintainability Principle

Coupon rules must remain separate from the mechanics of a particular payment channel.

Conceptually:

`Payment Channel -> Coupon Eligibility/Calculation -> Payment Completion -> Coupon Redemption`

Offline payment is the first consumer.

A future online payment gateway should call the same authoritative coupon eligibility, calculation and redemption behaviour rather than implementing a second coupon system.

Avoid building V1 as a generic promotions engine. Keep the domain focused on membership-plan coupons while preserving clean boundaries for future payment integration.

---

## 29. Definition of Done

Coupon Management is considered complete only when:

- Product rules in this document are implemented or explicitly revised.
- Client validation and server validation are covered.
- Authorization is enforced server-side.
- Offline payment works both with and without coupons.
- Transaction/concurrency behaviour prevents over-redemption.
- Historical financial values remain stable.
- Coupon report reconciles with completed/voided transactions.
- Audit trail is available for material administrative actions.
- Happy paths, negative cases, boundary cases, direct-access/security cases and regression scenarios have been executed by QA.
- Any deliberate deviation from this reference is documented before release.

---

**Reference status:** Product baseline for Coupon Management V1. Development and QA should use this document as the common source of expected behaviour unless a later approved product decision supersedes it.
