<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use RuntimeException;
use Throwable;

final class MembershipPaymentController extends BaseController
{
    /**
     * Begin membership checkout.
     *
     * At present only development can complete checkout.
     *
     * Future implementation:
     *
     * createPayment()
     *      -> gateway create-order API
     *      -> redirect/open gateway checkout
     *
     * Development implementation:
     *
     * createPayment()
     *      -> simulator
     *      -> common successful-payment processor
     */
    public function purchase(): RedirectResponse
    {
        $plansUrl =
            route_to(
                'web.account.settings.section',
                'plans'
            );

        /*
         * Fail closed at the HTTP boundary as well as inside the simulator.
         *
         * This means a QA/production user cannot manually POST the endpoint.
         */
        if (ENVIRONMENT !== 'development') {
            return redirect()
                ->to(
                    $plansUrl
                )
                ->with(
                    'accountNotice',
                    [
                        'type' =>
                        'warning',

                        'title' =>
                        'Online payment unavailable',

                        'message' =>
                        'Online membership purchase is not available yet.',
                    ]
                );
        }

        $planCode =
            mb_strtoupper(
                trim(
                    (string) $this
                        ->request
                        ->getPost(
                            'plan_code'
                        )
                )
            );

        if ($planCode === '') {
            return redirect()
                ->to(
                    $plansUrl
                )
                ->with(
                    'accountNotice',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Unable to purchase plan',

                        'message' =>
                        'Please select a valid membership plan.',
                    ]
                );
        }

        try {
            $payment =
                service(
                    'membershipPaymentService'
                )->createPayment(
                    $this->authenticatedUserId(),
                    $planCode
                );

            $transactionReference =
                trim(
                    (string) (
                        $payment['transaction_reference']
                        ?? ''
                    )
                );

            if ($transactionReference === '') {
                throw new RuntimeException(
                    'The payment transaction reference was not created.'
                );
            }

            /*
             * This call represents the provider + verified webhook that will
             * exist later.
             */
            service(
                'developmentMembershipPaymentSimulator'
            )->simulateSuccessfulPayment(
                $transactionReference
            );

            return redirect()
                ->to(
                    route_to(
                        'web.membership.payment.success',
                        $transactionReference
                    )
                );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Development membership purchase failed. '
                    . 'Member: {memberId}; '
                    . 'Plan: {plan}; '
                    . 'Reason: {message}',
                [
                    'memberId' =>
                    $this->authenticatedUserId(),

                    'plan' =>
                    $planCode,

                    'message' =>
                    $exception->getMessage(),
                ]
            );

            return redirect()
                ->to(
                    $plansUrl
                )
                ->with(
                    'accountNotice',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Payment could not be completed',

                        'message' =>
                        $exception->getMessage(),
                    ]
                );
        }
    }

    /**
     * Successful-payment receipt.
     *
     * Transaction details are loaded from persistence and scoped to the
     * authenticated member.
     */
    public function success(
        string $transactionReference
    ): string|RedirectResponse {
        $transactionReference =
            trim(
                $transactionReference
            );

        $payment =
            service(
                'membershipPaymentService'
            )->successfulPaymentForMember(
                $this->authenticatedUserId(),
                $transactionReference
            );

        if (!is_array($payment)) {
            return redirect()
                ->to(
                    route_to(
                        'web.account.settings.section',
                        'plans'
                    )
                );
        }

        $membership =
            is_array(
                $payment['membership']
                    ?? null
            )
            ? $payment['membership']
            : [];

        $amountPaise =
            max(
                0,
                (int) (
                    $payment['amount_paise']
                    ?? 0
                )
            );

        $expiresAt =
            trim(
                (string) (
                    $membership['expires_at']
                    ?? ''
                )
            );

        $expiresAtDisplay =
            '';

        if ($expiresAt !== '') {
            $timestamp =
                strtotime(
                    $expiresAt
                );

            if ($timestamp !== false) {
                $expiresAtDisplay =
                    date(
                        'd M Y',
                        $timestamp
                    );
            }
        }

        return view(
            'Pages/Membership/PaymentSuccess',
            [
                'pageTitle' =>
                'Payment Successful | Sikhanandkaraj',

                'payment' =>
                $payment,

                'amountDisplay' =>
                number_format(
                    $amountPaise / 100,
                    0
                ),

                'expiresAtDisplay' =>
                $expiresAtDisplay,
            ]
        );
    }
}
