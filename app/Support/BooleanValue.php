<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Converts database and request boolean representations into PHP bool.
 */
final class BooleanValue
{
    public static function fromDatabase(
        mixed $value
    ): bool {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        if (is_string($value)) {
            return in_array(
                strtolower(trim($value)),
                [
                    '1',
                    't',
                    'true',
                    'yes',
                    'y',
                    'on',
                ],
                true
            );
        }

        return false;
    }
}