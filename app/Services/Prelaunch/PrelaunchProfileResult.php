<?php

declare(strict_types=1);

namespace App\Services\Prelaunch;

/**
 * Immutable result returned after attempting to create
 * a prelaunch profile.
 *
 * User-correctable business failures are returned as field-level
 * validation errors. Unexpected infrastructure failures continue
 * to be handled through exceptions.
 */
final readonly class PrelaunchProfileResult
{
    public function __construct(
        public bool $successful,
        public ?int $profileId = null,
        public ?string $profileReference = null,
        public ?string $field = null,
        public ?string $message = null
    ) {}

    /**
     * Create a successful profile-creation result.
     */
    public static function success(
        int $profileId,
        string $profileReference
    ): self {
        return new self(
            successful: true,
            profileId: $profileId,
            profileReference: $profileReference
        );
    }

    /**
     * Create a field-specific validation failure.
     *
     * The field name must match the corresponding HTML input name.
     */
    public static function fieldFailure(
        string $field,
        string $message
    ): self {
        return new self(
            successful: false,
            field: $field,
            message: $message
        );
    }

    /**
     * Create a form-level business failure.
     */
    public static function failure(
        string $message
    ): self {
        return new self(
            successful: false,
            message: $message
        );
    }
}
