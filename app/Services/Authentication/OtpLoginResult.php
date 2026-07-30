<?php

declare(strict_types=1);

namespace App\Services\Authentication;

/**
 * Immutable result returned by the OTP login service.
 */
final class OtpLoginResult
{
    /**
     * @param array<string, mixed>|null $user
     */
    private function __construct(
        public readonly bool $successful,
        public readonly ?string $message = null,
        public readonly ?string $field = null,
        public readonly ?int $userId = null,
        public readonly ?int $mobileContactId = null,
        public readonly ?array $user = null
    ) {}

    /**
     * Return a successful OTP request result.
     */
    public static function otpIssued(
        int $userId,
        int $mobileContactId,
        string $message
    ): self {
        return new self(
            successful: true,
            message: $message,
            userId: $userId,
            mobileContactId: $mobileContactId
        );
    }

    /**
     * Return a successful OTP verification result.
     *
     * @param array<string, mixed> $user
     */
    public static function authenticated(
        array $user
    ): self {
        $userId = $user['id'] ?? null;

        return new self(
            successful: true,
            message: 'OTP verified successfully.',
            userId: is_numeric($userId)
                ? (int) $userId
                : null,
            user: $user
        );
    }

    /**
     * Return a safe business failure.
     */
    public static function failure(
        string $message,
        ?string $field = null
    ): self {
        return new self(
            successful: false,
            message: $message,
            field: $field
        );
    }
}
