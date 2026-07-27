<?php

declare(strict_types=1);

namespace App\Services\Authentication;

/**
 * Immutable result returned by password-reset operations.
 */
final readonly class PasswordResetResult
{
    private function __construct(
        public bool $successful,
        public string $message,
        public ?int $userId = null,
        public ?int $mobileContactId = null,
        public ?int $expiresAtTimestamp = null
    ) {}

    public static function success(
        string $message,
        ?int $userId = null,
        ?int $mobileContactId = null,
        ?int $expiresAtTimestamp = null
    ): self {
        return new self(
            successful: true,
            message: $message,
            userId: $userId,
            mobileContactId: $mobileContactId,
            expiresAtTimestamp: $expiresAtTimestamp
        );
    }

    public static function failure(string $message): self
    {
        return new self(
            successful: false,
            message: $message
        );
    }
}
