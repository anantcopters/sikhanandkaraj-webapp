<?php

declare(strict_types=1);

namespace App\Services\Membership;

/**
 * Immutable result of a successful membership activation.
 *
 * Payment integration can safely persist/reference these IDs after its own
 * authoritative payment verification succeeds.
 */
final class MembershipActivationResult
{
    public function __construct(
        public readonly int $membershipId,
        public readonly string $action,
        public readonly string $planCode,
        public readonly string $startsAt,
        public readonly string $expiresAt,
        public readonly ?int $replacedMembershipId
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'membershipId' =>
            $this->membershipId,

            'action' =>
            $this->action,

            'planCode' =>
            $this->planCode,

            'startsAt' =>
            $this->startsAt,

            'expiresAt' =>
            $this->expiresAt,

            'replacedMembershipId' =>
            $this->replacedMembershipId,
        ];
    }
}
