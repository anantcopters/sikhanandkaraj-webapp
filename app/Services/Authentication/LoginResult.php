<?php

declare(strict_types=1);

namespace App\Services\Authentication;

/**
 * Immutable result returned by the login service.
 */
final class LoginResult
{
    private function __construct(
        public readonly bool $successful,
        public readonly ?array $user,
        public readonly ?string $message,
        public readonly ?string $field
    ) {
    }

    /**
     * @param array<string, mixed> $user
     */
    public static function success(array $user): self
    {
        return new self(
            true,
            $user,
            null,
            null
        );
    }

    public static function failure(
        string $message,
        ?string $field = null
    ): self {
        return new self(
            false,
            null,
            $message,
            $field
        );
    }
}

