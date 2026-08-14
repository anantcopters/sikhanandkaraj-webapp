<?php

declare(strict_types=1);

namespace App\Services\Admin\Authentication;

/**
 * Result object for administrator password-reset operations.
 */
final readonly class AdminPasswordResetResult
{
    /**
     * @param array<string, mixed>|null $admin
     */
    private function __construct(
        public bool $successful,
        public string $message,
        public ?int $adminUserId = null,
        public ?array $admin = null,
        public ?int $expiresAtTimestamp = null
    ) {}

    /**
     * Create a successful result.
     *
     * @param array<string, mixed>|null $admin
     */
    public static function success(
        string $message,
        ?int $adminUserId = null,
        ?array $admin = null,
        ?int $expiresAtTimestamp = null
    ): self {
        return new self(
            true,
            $message,
            $adminUserId,
            $admin,
            $expiresAtTimestamp
        );
    }

    /**
     * Create a failed result.
     */
    public static function failure(
        string $message
    ): self {
        return new self(
            false,
            $message
        );
    }
}
