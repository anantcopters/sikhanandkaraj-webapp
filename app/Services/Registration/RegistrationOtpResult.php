<?php

declare(strict_types=1);

namespace App\Services\Registration;

/**
 * Immutable result returned by registration OTP operations.
 */
final class RegistrationOtpResult
{
    private function __construct(
        public readonly bool $successful,
        public readonly string $message,
        public readonly ?int $expiresAtTimestamp = null,
        public readonly ?int $retryAfterTimestamp = null
    ) {}

    public static function success(
        string $message,
        ?int $expiresAtTimestamp = null
    ): self {
        return new self(
            successful: true,
            message: $message,
            expiresAtTimestamp: $expiresAtTimestamp
        );
    }

    public static function failure(
        string $message,
        ?int $retryAfterTimestamp = null
    ): self {
        return new self(
            successful: false,
            message: $message,
            retryAfterTimestamp: $retryAfterTimestamp
        );
    }
}

