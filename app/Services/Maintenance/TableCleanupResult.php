<?php

declare(strict_types=1);

namespace App\Services\Maintenance;

/**
 * Represents the outcome of one configured table-cleanup job.
 */
final readonly class TableCleanupResult
{
    public function __construct(
        public string $jobName,
        public int $deletedCount,
        public bool $successful,
        public ?string $errorMessage = null
    ) {}

    /**
     * Create a successful cleanup result.
     */
    public static function success(
        string $jobName,
        int $deletedCount
    ): self {
        return new self(
            jobName: $jobName,
            deletedCount: $deletedCount,
            successful: true
        );
    }

    /**
     * Create a failed cleanup result.
     */
    public static function failure(
        string $jobName,
        string $errorMessage
    ): self {
        return new self(
            jobName: $jobName,
            deletedCount: 0,
            successful: false,
            errorMessage: $errorMessage
        );
    }
}
