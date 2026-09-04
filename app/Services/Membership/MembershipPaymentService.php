<?php

declare(strict_types=1);

namespace App\Services\Membership;

use App\Models\MemberMembershipModel;
use App\Models\MemberPaymentModel;
use App\Models\MembershipPlanModel;
use App\Services\Email\MemberEmailService;
use CodeIgniter\Database\BaseConnection;
use RuntimeException;
use Throwable;

final class MembershipPaymentService
{
    public function __construct(
        private readonly BaseConnection
        $database,

        private readonly MembershipPlanModel
        $planModel,

        private readonly MemberPaymentModel
        $paymentModel,

        private readonly MemberMembershipModel
        $membershipModel,

        private readonly MembershipPurchaseService
        $purchaseService,

        /*
        * Membership email is a downstream communication channel.
        *
        * It must never participate in payment authenticity or membership
        * activation decisions.
        */
        private readonly MemberEmailService
        $memberEmailService,

        private readonly CouponService
        $couponService
    ) {}

    /**
     * Create the application's payment/order record before sending the member
     * to a payment provider.
     *
     * Future gateway integration begins after this method.
     *
     * @return array<string, mixed>
     */
    public function createPayment(
        int $userId,
        string $requestedPlanCode
    ): array {
        if ($userId <= 0) {
            throw new RuntimeException(
                'A valid member account is required.'
            );
        }

        $decision =
            $this
            ->purchaseService
            ->evaluate(
                $userId,
                $requestedPlanCode
            );

        if (!$decision->allowed) {
            throw new RuntimeException(
                $decision->message
            );
        }

        $plan =
            $this
            ->planModel
            ->findActiveByCode(
                $requestedPlanCode
            );

        if (!is_array($plan)) {
            throw new RuntimeException(
                'The selected membership plan is not currently available.'
            );
        }

        $planId =
            max(
                0,
                (int) (
                    $plan['id']
                    ?? 0
                )
            );

        if ($planId <= 0) {
            throw new RuntimeException(
                'The selected membership plan is invalid.'
            );
        }

        $transactionReference =
            $this->createTransactionReference();

        $paymentId =
            $this
            ->paymentModel
            ->insert(
                [
                    'user_id' =>
                    $userId,

                    'membership_plan_id' =>
                    $planId,

                    'member_membership_id' =>
                    null,

                    'transaction_reference' =>
                    $transactionReference,

                    /*
                     * Development is currently the only provider capable of
                     * completing checkout.
                     *
                     * When the real gateway is introduced this value will be
                     * the gateway identifier.
                     */
                    'provider' =>
                    MemberPaymentModel::PROVIDER_DEVELOPMENT,

                    'provider_order_id' =>
                    null,

                    'provider_payment_id' =>
                    null,

                    'provider_event_id' =>
                    null,

                    'status' =>
                    MemberPaymentModel::STATUS_CREATED,

                    'plan_code_snapshot' =>
                    mb_strtoupper(
                        trim(
                            (string) (
                                $plan['code']
                                ?? ''
                            )
                        )
                    ),

                    'plan_name_snapshot' =>
                    trim(
                        (string) (
                            $plan['name']
                            ?? ''
                        )
                    ),

                    'amount_paise' =>
                    max(
                        0,
                        (int) (
                            $plan['price_paise']
                            ?? 0
                        )
                    ),

                    'currency' =>
                    'INR',

                    'purchase_action' =>
                    $decision->action,

                    'provider_response' =>
                    null,

                    'paid_at' =>
                    null,

                    'processed_at' =>
                    null,
                ],
                true
            );

        if (
            !is_numeric($paymentId)
            || (int) $paymentId <= 0
        ) {
            throw new RuntimeException(
                'The payment order could not be created.'
            );
        }

        $payment =
            $this
            ->paymentModel
            ->find(
                (int) $paymentId
            );

        if (!is_array($payment)) {
            throw new RuntimeException(
                'The payment order could not be loaded.'
            );
        }

        return $payment;
    }

    /**
     * Record an administrator-verified offline payment.
     *
     * Offline payment is only another authoritative payment provider.
     * Membership purchase/renewal/upgrade rules remain owned exclusively
     * by MembershipPurchaseService.
     *
     * IMPORTANT:
     *
     * Coupon validation performed by the browser/AJAX "Apply Coupon"
     * action is only a preview.
     *
     * The coupon MUST be evaluated again here before the payment record is
     * created. This prevents a coupon that has expired, become exhausted,
     * become inactive, become ineligible for the member/plan/location, or
     * already been redeemed from being trusted merely because it was
     * successfully previewed earlier.
     *
     * processSuccessfulPayment() performs the final locked redemption check
     * again inside the successful-payment transaction.
     *
     * @return array<string, mixed>
     */
    public function recordOfflinePayment(
        int $userId,
        string $requestedPlanCode,
        int $amountPaise,
        string $paymentMethod,
        string $paymentDate,
        string $externalReference,
        string $paymentNote,
        int $adminUserId,
        string $couponCode = ''
    ): array {
        if (
            $userId <= 0
            || $adminUserId <= 0
        ) {
            throw new RuntimeException(
                'A valid member and administrator are required.'
            );
        }

        if ($amountPaise <= 0) {
            throw new RuntimeException(
                'The payment amount must be greater than zero.'
            );
        }

        /*
        * Normalize values before any business-rule evaluation.
        */
        $requestedPlanCode =
            mb_strtoupper(
                trim(
                    $requestedPlanCode
                )
            );

        $couponCode =
            mb_strtoupper(
                trim(
                    $couponCode
                )
            );

        $paymentMethod =
            mb_strtoupper(
                trim(
                    $paymentMethod
                )
            );

        $externalReference =
            trim(
                $externalReference
            );

        $paymentNote =
            trim(
                $paymentNote
            );

        /*
        * Validate the administrator-selected offline payment source.
        */
        $allowedMethods = [
            MemberPaymentModel::PAYMENT_METHOD_BANK_TRANSFER,
            MemberPaymentModel::PAYMENT_METHOD_UPI,
            MemberPaymentModel::PAYMENT_METHOD_CASH,
            MemberPaymentModel::PAYMENT_METHOD_OTHER,
        ];

        if (
            !in_array(
                $paymentMethod,
                $allowedMethods,
                true
            )
        ) {
            throw new RuntimeException(
                'The payment source is invalid.'
            );
        }

        /*
        * Validate the payment date.
        */
        $paymentDateObject =
            \DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                trim(
                    $paymentDate
                ),
                new \DateTimeZone(
                    'UTC'
                )
            );

        if (
            !$paymentDateObject
                instanceof \DateTimeImmutable
        ) {
            throw new RuntimeException(
                'The payment date is invalid.'
            );
        }

        $todayUtc =
            new \DateTimeImmutable(
                'today',
                new \DateTimeZone(
                    'UTC'
                )
            );

        if (
            $paymentDateObject
            > $todayUtc
        ) {
            throw new RuntimeException(
                'The payment date cannot be in the future.'
            );
        }

        /*
        * Reuse the authoritative membership purchase decision.
        *
        * This determines whether the operation is:
        *
        * - PURCHASE
        * - RENEWAL
        * - UPGRADE
        *
        * and prevents an invalid downgrade or otherwise disallowed
        * membership transition.
        */
        $decision =
            $this
            ->purchaseService
            ->evaluate(
                $userId,
                $requestedPlanCode
            );

        if (!$decision->allowed) {
            throw new RuntimeException(
                $decision->message
            );
        }

        /*
        * Resolve the plan from the server-side membership plan master.
        *
        * Never trust plan price or plan ID supplied by the browser.
        */
        $plan =
            $this
            ->planModel
            ->findActiveByCode(
                $requestedPlanCode
            );

        if (!is_array($plan)) {
            throw new RuntimeException(
                'The selected membership plan is not currently available.'
            );
        }

        $planId =
            max(
                0,
                (int) (
                    $plan['id']
                    ?? 0
                )
            );

        if ($planId <= 0) {
            throw new RuntimeException(
                'The selected membership plan is invalid.'
            );
        }

        $planPricePaise =
            max(
                0,
                (int) (
                    $plan['price_paise']
                    ?? 0
                )
            );

        /*
        * ------------------------------------------------------------------
        * FINAL SERVER-SIDE COUPON VALIDATION BEFORE PAYMENT CREATION
        * ------------------------------------------------------------------
        *
        * The Apply Coupon AJAX request is NOT authoritative.
        *
        * Re-evaluate the coupon now against the current database state.
        *
        * CouponService owns:
        *
        * - active/inactive check;
        * - start date;
        * - expiry;
        * - usage limit;
        * - applicable membership plan;
        * - All / Selected / Gender eligibility;
        * - geographic eligibility;
        * - previous member redemption;
        * - discount calculation.
        *
        * If any rule has changed since the AJAX preview, evaluate()
        * throws and NO payment record is created.
        */
        $couponEvaluation =
            null;

        if ($couponCode !== '') {
            $couponEvaluation =
                $this
                ->couponService
                ->evaluate(
                    $userId,
                    $requestedPlanCode,
                    $couponCode
                );
        }

        /*
        * Build authoritative pricing snapshots.
        *
        * Amount Received remains separate because an administrator may
        * legitimately record an amount different from Final Payable.
        *
        * The browser-calculated value is never trusted.
        */
        $couponId =
            $couponEvaluation !== null
            ? max(
                0,
                (int) (
                    $couponEvaluation['couponId']
                    ?? 0
                )
            )
            : 0;

        $pricingPlanPricePaise =
            $couponEvaluation !== null
            ? max(
                0,
                (int) (
                    $couponEvaluation['planPricePaise']
                    ?? 0
                )
            )
            : $planPricePaise;

        $couponDiscountPaise =
            $couponEvaluation !== null
            ? max(
                0,
                (int) (
                    $couponEvaluation['discountAmountPaise']
                    ?? 0
                )
            )
            : 0;

        $finalPayablePaise =
            $couponEvaluation !== null
            ? max(
                0,
                (int) (
                    $couponEvaluation['finalPayablePaise']
                    ?? 0
                )
            )
            : $planPricePaise;

        /*
        * Defensive pricing checks.
        *
        * CouponService should already guarantee these invariants, but
        * payment creation must not persist an impossible financial
        * snapshot even if CouponService is changed later.
        */
        if (
            $couponEvaluation !== null
            && $couponId <= 0
        ) {
            throw new RuntimeException(
                'The coupon evaluation is invalid.'
            );
        }

        if (
            $pricingPlanPricePaise
            !== $planPricePaise
        ) {
            throw new RuntimeException(
                'The coupon pricing does not match the selected membership plan.'
            );
        }

        if (
            $couponDiscountPaise
            > $pricingPlanPricePaise
        ) {
            throw new RuntimeException(
                'The coupon discount is invalid.'
            );
        }

        if (
            $finalPayablePaise
            !== (
                $pricingPlanPricePaise
                - $couponDiscountPaise
            )
        ) {
            throw new RuntimeException(
                'The calculated coupon amount is invalid.'
            );
        }

        /*
        * Generate payment identifiers only after every request-level and
        * coupon-level validation has passed.
        */
        $transactionReference =
            $this
            ->createTransactionReference();

        /*
        * provider_payment_id/provider_event_id are generated internally
        * for idempotency.
        *
        * The administrator-entered Bank/UPI reference remains separate
        * and may legitimately be blank for Cash.
        */
        $offlinePaymentId =
            'OFFLINE-'
            . mb_strtoupper(
                bin2hex(
                    random_bytes(8)
                )
            );

        $offlineEventId =
            'OFFLINE-EVENT-'
            . mb_strtoupper(
                bin2hex(
                    random_bytes(8)
                )
            );

        /*
        * Create the payment only AFTER the coupon has successfully passed
        * the current server-side eligibility/pricing validation.
        *
        * This avoids orphan CREATED payments for invalid coupons.
        */
        $paymentId =
            $this
            ->paymentModel
            ->insert(
                [
                    'user_id' =>
                    $userId,

                    'membership_plan_id' =>
                    $planId,

                    'member_membership_id' =>
                    null,

                    'transaction_reference' =>
                    $transactionReference,

                    'provider' =>
                    MemberPaymentModel::PROVIDER_OFFLINE,

                    'provider_order_id' =>
                    null,

                    'provider_payment_id' =>
                    null,

                    'provider_event_id' =>
                    null,

                    'status' =>
                    MemberPaymentModel::STATUS_CREATED,

                    'plan_code_snapshot' =>
                    mb_strtoupper(
                        trim(
                            (string) (
                                $plan['code']
                                ?? ''
                            )
                        )
                    ),

                    'plan_name_snapshot' =>
                    trim(
                        (string) (
                            $plan['name']
                            ?? ''
                        )
                    ),

                    /*
                    * Amount Received is the actual money recorded by
                    * Superadmin.
                    *
                    * Do not replace this with Final Payable.
                    */
                    'amount_paise' =>
                    $amountPaise,

                    'currency' =>
                    'INR',

                    'purchase_action' =>
                    $decision->action,

                    'payment_method' =>
                    $paymentMethod,

                    'recorded_by_admin_user_id' =>
                    $adminUserId,

                    'payment_note' =>
                    $paymentNote !== ''
                        ? $paymentNote
                        : null,

                    /*
                    * Persist the coupon/pricing snapshot on the payment.
                    *
                    * processSuccessfulPayment() reads coupon_id from the
                    * locked payment row and performs the final redemption
                    * validation inside its transaction.
                    */
                    'coupon_id' =>
                    $couponId > 0
                        ? $couponId
                        : null,

                    'plan_price_paise' =>
                    $pricingPlanPricePaise,

                    'coupon_discount_paise' =>
                    $couponDiscountPaise,

                    'final_payable_paise' =>
                    $finalPayablePaise,

                    'provider_response' =>
                    null,

                    'paid_at' =>
                    null,

                    'processed_at' =>
                    null,
                ],
                true
            );

        if (
            !is_numeric($paymentId)
            || (int) $paymentId <= 0
        ) {
            throw new RuntimeException(
                'The offline payment could not be recorded.'
            );
        }

        /*
        * processSuccessfulPayment() now becomes the FINAL authority.
        *
        * It:
        *
        * 1. starts the DB transaction;
        * 2. locks the payment;
        * 3. sees payment.coupon_id;
        * 4. calls CouponService::evaluateForRedemption();
        * 5. rechecks the coupon under the final transaction;
        * 6. activates/renews/upgrades membership;
        * 7. records coupon redemption.
        *
        * Therefore the coupon is checked twice server-side:
        *
        *     evaluate()
        *         -> before payment creation
        *
        *     evaluateForRedemption()
        *         -> inside final payment transaction
        */
        return $this
            ->processSuccessfulPayment(
                transactionReference: $transactionReference,

                providerPaymentId: $offlinePaymentId,

                providerEventId: $offlineEventId,

                providerResponse: [
                    'payment_source' =>
                    $paymentMethod,

                    'external_reference' =>
                    $externalReference,

                    'payment_date' =>
                    $paymentDateObject
                        ->format(
                            'Y-m-d'
                        ),

                    'recorded_by_admin_user_id' =>
                    $adminUserId,

                    'coupon_id' =>
                    $couponId > 0
                        ? $couponId
                        : null,

                    'plan_price_paise' =>
                    $pricingPlanPricePaise,

                    'coupon_discount_paise' =>
                    $couponDiscountPaise,

                    'final_payable_paise' =>
                    $finalPayablePaise,
                ],

                paidAt: $paymentDateObject
                    ->setTime(
                        12,
                        0
                    )
                    ->format(
                        'Y-m-d H:i:s'
                    )
            );
    }

    /**
     * Common successful-payment processor.
     *
     * IMPORTANT:
     *
     * Production must call this only AFTER provider authenticity/signature
     * verification succeeds.
     *
     * This method deliberately knows nothing about webhook signatures.
     *
     * @return array<string, mixed>
     */
    public function processSuccessfulPayment(
        string $transactionReference,
        string $providerPaymentId,
        string $providerEventId,
        array $providerResponse = [],
        ?string $paidAt = null
    ): array {
        $transactionReference =
            trim(
                $transactionReference
            );

        $providerPaymentId =
            trim(
                $providerPaymentId
            );

        $providerEventId =
            trim(
                $providerEventId
            );

        if (
            $transactionReference === ''
            || $providerPaymentId === ''
            || $providerEventId === ''
        ) {
            throw new RuntimeException(
                'The successful payment response is incomplete.'
            );
        }

        $database =
            $this->database;

        $database->transBegin();

        try {
            $payment =
                $this
                ->paymentModel
                ->lockByReference(
                    $transactionReference
                );

            if (!is_array($payment)) {
                throw new RuntimeException(
                    'The payment transaction could not be found.'
                );
            }

            /*
            * IDEMPOTENCY
            * ===========
            *
            * This check MUST happen before coupon revalidation.
            *
            * A successfully processed coupon payment already has a
            * COMPLETED coupon redemption. If a provider/admin retry reaches
            * this method again and CouponService::evaluateForRedemption()
            * runs first, the coupon service would correctly report that the
            * member has already used the coupon.
            *
            * That would incorrectly turn a legitimate payment retry into an
            * error.
            *
            * Once this payment has already produced its membership, return
            * the existing payment without re-running:
            *
            * - coupon eligibility;
            * - membership activation;
            * - coupon redemption.
            */
            if (
                (string) (
                    $payment['status']
                    ?? ''
                )
                === MemberPaymentModel::STATUS_PROCESSED
            ) {
                $database->transCommit();

                return $payment;
            }

            /*
            * Only CREATED and PAID payments may continue through successful
            * payment processing.
            */
            if (
                !in_array(
                    (string) (
                        $payment['status']
                        ?? ''
                    ),
                    [
                        MemberPaymentModel::STATUS_CREATED,
                        MemberPaymentModel::STATUS_PAID,
                    ],
                    true
                )
            ) {
                throw new RuntimeException(
                    'This payment cannot be processed.'
                );
            }

            /*
            * Resolve coupon information only after idempotency has been
            * handled.
            */
            $couponId =
                max(
                    0,
                    (int) (
                        $payment['coupon_id']
                        ?? 0
                    )
                );

            $couponEvaluation =
                null;

            /*
            * FINAL TRANSACTIONAL COUPON VALIDATION
            * =====================================
            *
            * evaluateForRedemption() locks the coupon and repeats the
            * authoritative eligibility/capacity checks inside this payment
            * transaction.
            *
            * This protects against:
            *
            * - coupon deactivation after Apply Coupon;
            * - expiry after Apply Coupon;
            * - usage-limit exhaustion;
            * - plan/member/gender/location changes;
            * - previous redemption;
            * - concurrent final redemption.
            */
            if ($couponId > 0) {
                $couponEvaluation =
                    $this->couponService
                    ->evaluateForRedemption(
                        $couponId,
                        (int) $payment['user_id'],
                        (string) $payment['plan_code_snapshot']
                    );
            }

            $nowUtc =
                gmdate(
                    'Y-m-d H:i:s'
                );

            /*
            * Persist authoritative provider success before activating the
            * membership.
            */
            if (
                !$this
                    ->paymentModel
                    ->update(
                        (int) $payment['id'],
                        [
                            'provider_payment_id' =>
                            $providerPaymentId,

                            'provider_event_id' =>
                            $providerEventId,

                            'status' =>
                            MemberPaymentModel::STATUS_PAID,

                            'provider_response' =>
                            json_encode(
                                $providerResponse,
                                JSON_UNESCAPED_SLASHES
                                    | JSON_UNESCAPED_UNICODE
                            ) ?: '{}',

                            'paid_at' =>
                            $paidAt !== null
                                && trim($paidAt) !== ''
                                ? trim($paidAt)
                                : $nowUtc,
                        ]
                    )
            ) {
                throw new RuntimeException(
                    'The successful payment could not be recorded.'
                );
            }

            /*
            * Existing authoritative membership activation.
            *
            * Purchase / renewal / upgrade rules continue to belong to
            * MembershipPurchaseService.
            */
            $activation =
                $this
                ->purchaseService
                ->activateAfterSuccessfulPayment(
                    (int) $payment['user_id'],
                    (string) $payment['plan_code_snapshot']
                );

            if ($activation->membershipId <= 0) {
                throw new RuntimeException(
                    'The membership was not activated.'
                );
            }

            if (
                !$this
                    ->paymentModel
                    ->update(
                        (int) $payment['id'],
                        [
                            'member_membership_id' =>
                            $activation->membershipId,

                            'status' =>
                            MemberPaymentModel::STATUS_PROCESSED,

                            'processed_at' =>
                            gmdate(
                                'Y-m-d H:i:s'
                            ),
                        ]
                    )
            ) {
                throw new RuntimeException(
                    'The activated membership could not be linked '
                        . 'to the payment.'
                );
            }

            if (
                $database->transStatus()
                === false
            ) {
                throw new RuntimeException(
                    'Payment processing transaction failed.'
                );
            }

            /*
            * Record coupon redemption only after membership activation and
            * payment processing have succeeded.
            *
            * This remains inside the same transaction.
            */
            if (
                $couponEvaluation !== null
            ) {
                $adminUserId =
                    max(
                        0,
                        (int) (
                            $payment['recorded_by_admin_user_id']
                            ?? 0
                        )
                    );

                if ($adminUserId <= 0) {
                    throw new RuntimeException(
                        'Coupon redemption administrator could not be determined.'
                    );
                }

                $this->couponService
                    ->recordRedemption(
                        $couponEvaluation,
                        (int) $payment['user_id'],
                        (int) $payment['id'],
                        $adminUserId
                    );
            }

            $database->transCommit();

            $processed =
                $this
                ->paymentModel
                ->find(
                    (int) $payment['id']
                );

            if (!is_array($processed)) {
                throw new RuntimeException(
                    'The processed payment could not be loaded.'
                );
            }

            /*
            * Load the immutable membership snapshot.
            *
            * The activated membership contains the exact commercial values
            * that belonged to this purchase. Do not use the current plan
            * master because pricing/duration may change later.
            */
            $membership =
                $this
                ->membershipModel
                ->find(
                    $activation
                        ->membershipId
                );

            if (is_array($membership)) {
                /*
                * Downstream transactional email.
                *
                * Payment and membership activation are already committed.
                * Email failure therefore cannot alter payment processing.
                */
                $this
                    ->memberEmailService
                    ->queueMembershipActivated(
                        recipientUserId: (int) (
                            $processed['user_id']
                            ?? 0
                        ),

                        membershipId: $activation
                            ->membershipId,

                        planName: trim(
                            (string) (
                                $membership['plan_name_snapshot']
                                ?? $processed['plan_name_snapshot']
                                ?? ''
                            )
                        ),

                        amountPaise: max(
                            0,
                            (int) (
                                $processed['amount_paise']
                                ?? 0
                            )
                        ),

                        transactionReference: trim(
                            (string) (
                                $processed['transaction_reference']
                                ?? ''
                            )
                        ),

                        expiresAt: trim(
                            (string) (
                                $membership['expires_at']
                                ?? ''
                            )
                        )
                    );
            }

            return $processed;
        } catch (Throwable $exception) {
            if (
                $database->transStatus()
                !== null
            ) {
                $database->transRollback();
            }

            throw $exception;
        }
    }

    /**
     * Load successful-payment information belonging to the authenticated
     * member.
     *
     * @return array<string, mixed>|null
     */
    public function successfulPaymentForMember(
        int $userId,
        string $transactionReference
    ): ?array {
        $payment =
            $this
            ->paymentModel
            ->findForUserByReference(
                $userId,
                $transactionReference
            );

        if (
            !is_array($payment)
            || (string) (
                $payment['status']
                ?? ''
            )
            !== MemberPaymentModel::STATUS_PROCESSED
        ) {
            return null;
        }

        $membershipId =
            max(
                0,
                (int) (
                    $payment['member_membership_id']
                    ?? 0
                )
            );

        if ($membershipId <= 0) {
            return null;
        }

        $membership =
            $this
            ->membershipModel
            ->find(
                $membershipId
            );

        if (
            !is_array($membership)
            || (int) (
                $membership['user_id']
                ?? 0
            )
            !== $userId
        ) {
            return null;
        }

        $payment['membership'] =
            $membership;

        return $payment;
    }

    private function createTransactionReference(): string
    {
        return 'SAKPAY-'
            . mb_strtoupper(
                bin2hex(
                    random_bytes(8)
                )
            );
    }
}
