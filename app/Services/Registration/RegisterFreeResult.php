<?php

declare(strict_types=1);

namespace App\Services\Registration;

/**
 * Immutable result returned by the registration service.
 */
final readonly class RegisterFreeResult
{
    public function __construct(
        public bool $successful,
        public RegistrationAction $action,
        public ?int $userId = null,
        public ?int $mobileContactId = null,
        public ?string $profileReference = null,
        public ?string $field = null,
        public ?string $message = null
    ) {
    }

    /**
     * Create a successful result.
     */
    public static function success(
        RegistrationAction $action,
        int $userId,
        int $mobileContactId,
        string $profileReference
    ): self {
        return new self(
            successful: true,
            action: $action,
            userId: $userId,
            mobileContactId: $mobileContactId,
            profileReference: $profileReference
        );
    }

    /**
     * Create a field-specific failure result.
     */
    public static function fieldFailure(
        RegistrationAction $action,
        string $field,
        string $message
    ): self {
        return new self(
            successful: false,
            action: $action,
            field: $field,
            message: $message
        );
    }
}