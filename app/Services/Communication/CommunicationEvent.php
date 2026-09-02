<?php

declare(strict_types=1);

namespace App\Services\Communication;

/**
 * Immutable channel-independent communication event.
 */
final class CommunicationEvent
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public readonly string $eventKey,
        public readonly int $recipientUserId,
        public readonly array $payload = [],
        public readonly ?string $referenceType = null,
        public readonly ?int $referenceId = null
    ) {}
}
