<?php

declare(strict_types=1);

namespace App\Services\Sms;

/**
 * Standard result returned by all SMS providers.
 */
final readonly class SmsSendResult
{
    private function __construct(
        public bool $successful,
        public ?string $providerMessageId = null,
        public ?string $errorMessage = null
    ) {
    }

    public static function success(
        ?string $providerMessageId = null
    ): self {
        return new self(
            successful: true,
            providerMessageId: $providerMessageId
        );
    }

    public static function failure(
        string $errorMessage
    ): self {
        return new self(
            successful: false,
            errorMessage: $errorMessage
        );
    }
}