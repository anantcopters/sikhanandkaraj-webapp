<?php

declare(strict_types=1);

namespace App\Services\Maintenance;

/**
 * Represents the outcome of one configured filesystem cleanup job.
 */
final readonly class FileCleanupResult
{
    public function __construct(
        public string $jobName,
        public int $processedProfiles,
        public int $deletedFiles,
        public int $deletedDirectories,
        public int $failedProfiles,
        public bool $successful,
        public ?string $errorMessage = null
    ) {}

    public static function success(
        string $jobName,
        int $processedProfiles,
        int $deletedFiles,
        int $deletedDirectories,
        int $failedProfiles
    ): self {
        return new self(
            jobName: $jobName,
            processedProfiles: $processedProfiles,
            deletedFiles: $deletedFiles,
            deletedDirectories: $deletedDirectories,
            failedProfiles: $failedProfiles,
            successful: $failedProfiles === 0
        );
    }

    public static function failure(
        string $jobName,
        string $errorMessage
    ): self {
        return new self(
            jobName: $jobName,
            processedProfiles: 0,
            deletedFiles: 0,
            deletedDirectories: 0,
            failedProfiles: 0,
            successful: false,
            errorMessage: $errorMessage
        );
    }
}
