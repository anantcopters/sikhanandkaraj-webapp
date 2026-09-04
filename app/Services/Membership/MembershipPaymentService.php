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

        $allowedMethods = [
            MemberPaymentModel::PAYMENT_METHOD_BANK_TRANSFER,
            MemberPaymentModel::PAYMENT_METHOD_UPI,
            MemberPaymentModel::PAYMENT_METHOD_CASH,
            MemberPaymentModel::PAYMENT_METHOD_OTHER,
        ];

        $paymentMethod = mb_strtoupper(
            trim($paymentMethod)
        );

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

        $paymentDateObject =
            \DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                trim($paymentDate),
                new \DateTimeZone('UTC')
            );

        if (
            !$paymentDateObject
                instanceof \DateTimeImmutable
        ) {
            throw new RuntimeException(
                'The payment date is invalid.'
            );
        }

        $todayUtc = new \DateTimeImmutable(
            'today',
            new \DateTimeZone('UTC')
        );

        if ($paymentDateObject > $todayUtc) {
            throw new RuntimeException(
                'The payment date cannot be in the future.'
            );
        }

        /*
     * Reuse the authoritative purchase decision.
     *
     * This blocks an active-plan downgrade and determines whether the
     * transaction is PURCHASE, RENEWAL or UPGRADE.
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

        $planId = max(
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

        /*
     * provider_payment_id/provider_event_id are generated internally for
     * idempotency. The administrator-entered bank/UPI reference remains
     * separate and may legitimately be blank for cash.
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
                 * Actual amount received is authoritative for the
                 * payment ledger. Plan pricing remains preserved by
                 * the membership snapshot created by the existing
                 * purchase service.
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
                    trim($paymentNote) !== ''
                        ? trim($paymentNote)
                        : null,

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

        $couponEvaluation =
            null;

        if (
            trim($couponCode) !== ''
        ) {
            $couponEvaluation =
                $this->couponService
                ->evaluate(
                    $userId,
                    $requestedPlanCode,
                    $couponCode
                );
        }

        return $this->processSuccessfulPayment(
            transactionReference: $transactionReference,

            providerPaymentId: $offlinePaymentId,

            providerEventId: $offlineEventId,

            providerResponse: [
                'payment_source' =>
                $paymentMethod,

                'external_reference' =>
                trim($externalReference),

                'payment_date' =>
                $paymentDateObject->format(
                    'Y-m-d'
                ),

                'recorded_by_admin_user_id' =>
                $adminUserId,

                'coupon_id' =>
                $couponEvaluation !== null
                    ? (int) $couponEvaluation['couponId']
                    : null,

                'plan_price_paise' =>
                $couponEvaluation !== null
                    ? (int) $couponEvaluation['planPricePaise']
                    : max(
                        0,
                        (int) (
                            $plan['price_paise']
                            ?? 0
                        )
                    ),

                'coupon_discount_paise' =>
                $couponEvaluation !== null
                    ? (int) $couponEvaluation['discountAmountPaise']
                    : 0,

                'final_payable_paise' =>
                $couponEvaluation !== null
                    ? (int) $couponEvaluation['finalPayablePaise']
                    : max(
                        0,
                        (int) (
                            $plan['price_paise']
                            ?? 0
                        )
                    ),
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

            if ($couponId > 0) {
                $couponEvaluation =
                    $this->couponService
                    ->evaluateForRedemption(
                        $couponId,
                        (int) $payment['user_id'],
                        (string) $payment['plan_code_snapshot']
                    );
            }

            /*
             * IDEMPOTENCY
             * ===========
             *
             * Payment providers may retry the same webhook.
             *
             * Once the payment has produced a membership, the existing result
             * is returned. MembershipPurchaseService is NOT called again.
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
             * Do not duplicate purchase/renewal/upgrade rules here.
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
 * ------------------------------------------------------------------
 * Load the immutable membership snapshot
 * ------------------------------------------------------------------
 *
 * The activated membership contains the exact commercial values that
 * belonged to this purchase. Do not use the current plan master because
 * pricing/duration may change later.
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
                * ------------------------------------------------------------------
                * Downstream transactional email
                * ------------------------------------------------------------------
                *
                * Payment and membership activation are already committed.
                *
                * MemberEmailService is failure-safe, therefore SES/queue/recipient
                * problems cannot alter successful payment processing.
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
