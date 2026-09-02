<?php

declare(strict_types=1);

namespace App\Services\Email;

final readonly class EmailDefinition
{
    /**
     * @param array<string, mixed> $previewData
     */
    public function __construct(
        public string $key,
        public string $name,
        public string $category,
        public string $subject,
        public string $viewName,
        public array $previewData,
        public int $priority = 100,
        public int $maxAttempts = 3
    ) {}
}
