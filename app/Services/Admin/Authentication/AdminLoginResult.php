<?php

declare(strict_types=1);

namespace App\Services\Admin\Authentication;

final readonly class AdminLoginResult
{
    /**
     * @param array<string, mixed>|null $admin
     */
    public function __construct(
        public bool $successful,
        public ?array $admin = null,
        public ?string $message = null
    ) {}

    /**
     * @param array<string, mixed> $admin
     */
    public static function success(
        array $admin
    ): self {
        return new self(
            successful: true,
            admin: $admin
        );
    }

    public static function failure(
        string $message
    ): self {
        return new self(
            successful: false,
            message: $message
        );
    }
}
