<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Creates safe diagnostic context for public and Admin prelaunch operations.
 *
 * Submitted profile data, contact values, image paths and uploaded file names
 * are deliberately excluded.
 */
final class PrelaunchErrorContext
{
    /**
     * Build standard prelaunch context.
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
        return array_merge(
            [
                'operation' =>
                $operation,

                'component' =>
                $component,

                'method' =>
                $method,
            ],
            $additionalContext
        );
    }

    private function __construct() {}
}
