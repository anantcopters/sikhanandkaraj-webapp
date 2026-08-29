<?php

declare(strict_types=1);

namespace App\Services\Development;

use App\Services\Membership\MembershipPaymentService;
use RuntimeException;

final class DevelopmentMembershipPaymentSimulator
{
    public function __construct(
        private readonly MembershipPaymentService
        $paymentService
    ) {}

    /**
     * Simulate the successful provider callback.
     *
     * SECURITY:
     *
     * Never weaken this environment check.
     *
     * QA and production must fail even if somebody manually submits the
     * development checkout endpoint.
     *
     * @return array<string, mixed>
     */
    public function simulateSuccessfulPayment(
        string $transactionReference
    ): array {
        if (ENVIRONMENT !== 'development') {
            throw new RuntimeException(
                'Development payment simulation is unavailable.'
            );
        }

        $suffix =
            mb_strtoupper(
                bin2hex(
                    random_bytes(8)
                )
            );

        return $this
            ->paymentService
            ->processSuccessfulPayment(
                $transactionReference,
                'DEV-PAY-' . $suffix,
                'DEV-EVENT-' . $suffix,
                [
                    'environment' =>
                    'development',

                    'simulated' =>
                    true,

                    'payment_status' =>
                    'SUCCESS',

                    'transaction_reference' =>
                    $transactionReference,
                ]
            );
    }
}
