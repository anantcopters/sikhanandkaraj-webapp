<?php

declare(strict_types=1);

namespace App\Services\FieldOfficer;

final readonly class FieldOfficerLoginResult
{
    private function __construct(
        public bool $successful,
        public ?string $message = null,
        public ?int $fieldOfficerId = null,
        public ?array $fieldOfficer = null
    ) {}

    public static function failure(
        string $message
    ): self {
        return new self(
            successful: false,
            message: $message
        );
    }

    public static function otpIssued(
        int $fieldOfficerId,
        string $message
    ): self {
        return new self(
            successful: true,
            message: $message,
            fieldOfficerId: $fieldOfficerId
        );
    }

    /**
     * @param array<string, mixed> $fieldOfficer
     */
    public static function authenticated(
        array $fieldOfficer
    ): self {
        return new self(
            successful: true,
            fieldOfficerId: (int) $fieldOfficer['id'],
            fieldOfficer: $fieldOfficer
        );
    }
}
