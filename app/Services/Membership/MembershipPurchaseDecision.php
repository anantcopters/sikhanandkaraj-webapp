<?php

declare(strict_types=1);

namespace App\Services\Membership;

/**
 * Immutable result of evaluating whether a member may purchase a plan.
 *
 * Evaluation and activation are intentionally separate.
 *
 * UI/payment-order creation may use the evaluation result to explain the
 * action, but successful payment activation MUST evaluate again inside the
 * activation transaction because membership state may have changed while the
 * member was completing payment.
 */
final class MembershipPurchaseDecision
{
    public const ACTION_PURCHASE =
    'PURCHASE';

    public const ACTION_RENEWAL =
    'RENEWAL';

    public const ACTION_UPGRADE =
    'UPGRADE';

    public const ACTION_DOWNGRADE =
    'DOWNGRADE';

    public const ACTION_UNAVAILABLE =
    'UNAVAILABLE';

    public function __construct(
        public readonly bool $allowed,
        public readonly string $action,
        public readonly string $message,
        public readonly string $requestedPlanCode,
        public readonly ?string $currentPlanCode = null,
        public readonly ?int $currentMembershipId = null
    ) {}

    /**
     * Convert the decision into controller/view-safe data.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'allowed' =>
            $this->allowed,

            'action' =>
            $this->action,

            'message' =>
            $this->message,

            'requestedPlanCode' =>
            $this->requestedPlanCode,

            'currentPlanCode' =>
            $this->currentPlanCode,

            'currentMembershipId' =>
            $this->currentMembershipId,
        ];
    }
}
