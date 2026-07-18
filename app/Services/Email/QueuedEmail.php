<?php

declare(strict_types=1);

namespace App\Services\Email;

final readonly class QueuedEmail
{
    /**
     * @param array<string, mixed> $viewData
     */
    public function __construct(
        public int $id,
        public string $recipientEmail,
        public string $recipientName,
        public string $subject,
        public string $viewName,
        public array $viewData,
        public int $attemptNumber,
        public int $maxAttempts
    ) {}
}
