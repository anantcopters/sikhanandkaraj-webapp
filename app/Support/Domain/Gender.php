<?php

declare(strict_types=1);

namespace App\Support\Domain;

final class Gender
{
    public const MALE = 'M';

    public const FEMALE = 'F';

    private const LABELS = [
        self::MALE => 'Male',
        self::FEMALE => 'Female',
    ];

    private function __construct() {}

    public static function isMale(
        mixed $value
    ): bool {
        return self::normalize($value)
            === self::MALE;
    }

    public static function isFemale(
        mixed $value
    ): bool {
        return self::normalize($value)
            === self::FEMALE;
    }

    public static function isValid(
        mixed $value
    ): bool {
        return array_key_exists(
            self::normalize($value),
            self::LABELS
        );
    }

    public static function label(
        mixed $value
    ): string {
        return self::LABELS[self::normalize($value)] ?? '';
    }

    public static function values(): array
    {
        return array_keys(
            self::LABELS
        );
    }

    public static function normalize(
        mixed $value
    ): string {
        return mb_strtoupper(
            trim(
                (string) $value
            )
        );
    }
}
