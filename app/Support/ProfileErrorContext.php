<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Creates safe structured context for profile-related errors.
 *
 * Profile form values are intentionally excluded because they may contain
 * sensitive matrimonial, family, location and personal information.
 */
final class ProfileErrorContext
{
    /**
     * Create a standard profile-operation context.
     *
     * @param array<string, mixed> $additionalContext
     *
     * @return array<string, mixed>
     */
    public static function forMember(
        int $memberId,
        string $operation,
        string $component,
        string $method,
        array $additionalContext = []
    ): array {
        $context = [
            'member_user_id' =>
            $memberId,

            'operation' =>
            $operation,

            'component' =>
            $component,

            'method' =>
            $method,
        ];

        return array_merge(
            $context,
            $additionalContext
        );
    }

    private function __construct() {}
}
