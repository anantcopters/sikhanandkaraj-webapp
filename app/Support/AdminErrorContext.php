<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Creates safe structured diagnostic context for administrator operations.
 *
 * Successful administrator activity must continue to use AdminAuditService.
 * This helper is only for unexpected operational/runtime failures.
 */
final class AdminErrorContext
{
    /**
     * Build context for an administrator operation.
     *
     * @param array<string, mixed> $additionalContext
     *
     * @return array<string, mixed>
     */
    public static function forOperation(
        string $operation,
        string $component,
        string $method,
        array $additionalContext = []
    ): array {
        $context = [
            'operation' =>
            $operation,

            'component' =>
            $component,

            'method' =>
            $method,
        ];

        $adminUserId = self::activeAdminUserId();

        if ($adminUserId !== null) {
            $context['admin_user_id'] =
                $adminUserId;
        }

        return array_merge(
            $context,
            $additionalContext
        );
    }

    /**
     * Return the authenticated Admin ID without starting a new session.
     */
    private static function activeAdminUserId(): ?int
    {
        if (
            !function_exists('session')
            || session_status() !== PHP_SESSION_ACTIVE
        ) {
            return null;
        }

        $adminUserId = session(
            'admin_user_id'
        );

        if (!is_numeric($adminUserId)) {
            return null;
        }

        $resolvedAdminUserId =
            (int) $adminUserId;

        return $resolvedAdminUserId > 0
            ? $resolvedAdminUserId
            : null;
    }

    private function __construct() {}
}
