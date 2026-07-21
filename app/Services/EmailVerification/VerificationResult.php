<?php

declare(strict_types=1);

namespace App\Services\EmailVerification;

final readonly class VerificationResult
{
    public function __construct(
        public bool $success,
        public string $message
    ) {}

    public static function success(
        string $message
    ): self {
        return new self(true, $message);
    }

    public static function failure(
        string $message
    ): self {
        return new self(false, $message);
    }

    public readonly ?int $retryAfter;
}

