<?php

declare(strict_types=1);

namespace App\Services\Admin\Audit;

final readonly class AdminAuditEvent
{
    /**
     * @param array<string, mixed>|null $beforeData
     * @param array<string, mixed>|null $afterData
     * @param array<string, mixed>|null $metadata
     */
    public function __construct(
        public string $action,
        public string $outcome = 'SUCCESS',
        public ?int $actorAdminId = null,
        public ?string $actorName = null,
        public ?string $actorRole = null,
        public ?string $targetType = null,
        public ?int $targetId = null,
        public ?string $targetLabel = null,
        public ?string $description = null,
        public ?array $beforeData = null,
        public ?array $afterData = null,
        public ?array $metadata = null
    ) {}
}
