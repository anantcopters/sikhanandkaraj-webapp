<?php

declare(strict_types=1);

namespace App\Services\Membership;

use App\Models\MemberMembershipModel;
use App\Models\MemberPaymentModel;
use App\Models\MembershipPlanModel;
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
        $purchaseService
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
        array $providerResponse = []
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
                            $nowUtc,
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
