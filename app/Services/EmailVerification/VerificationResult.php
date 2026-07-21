<?php

declare(strict_types=1);

namespace App\Services\EmailVerification;

final readonly class VerificationResult
{
    public function __construct(
        public bool $success,
        public string $message,
        public ?int $retryAfter = null
    ) {}

    public static function success(
        string $message
    ): self {
        return new self(
            success: true,
            message: $message
        );
    }

    public static function failure(
        string $message,
        ?int $retryAfter = null
    ): self {
        return new self(
            success: false,
            message: $message,
            retryAfter: $retryAfter
        );
    }
}
