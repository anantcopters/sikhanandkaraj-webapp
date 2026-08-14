<?php

declare(strict_types=1);

namespace App\Services\Admin\Authentication;

/**
 * Immutable result object returned by the Admin password-reset service.
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
        public ?array $admin = null
    ) {}

    /**
     * Create a successful result.
     *
     * @param array<string, mixed>|null $admin
     */
    public static function success(
        string $message,
        ?int $adminUserId = null,
        ?array $admin = null
    ): self {
        return new self(
            true,
            $message,
            $adminUserId,
            $admin
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
