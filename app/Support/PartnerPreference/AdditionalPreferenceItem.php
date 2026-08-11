<?php

declare(strict_types=1);

namespace App\Support\PartnerPreference;

/**
 * Defines partner-preference items outside the Basic section.
 */
final class AdditionalPreferenceItem
{
    public const COMMUNITY = 'community';

    public const EDUCATION = 'education';

    public const EMPLOYED_IN = 'employed-in';

    public const OCCUPATION = 'occupation';

    public const ANNUAL_INCOME = 'annual-income';

    public const LOCATION = 'location';

    public const SPECIAL_REQUEST = 'special-request';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::COMMUNITY,
            self::EDUCATION,
            self::EMPLOYED_IN,
            self::OCCUPATION,
            self::ANNUAL_INCOME,
            self::LOCATION,
            self::SPECIAL_REQUEST,
        ];
    }

    public static function isValid(string $item): bool
    {
        return in_array(
            $item,
            self::all(),
            true
        );
    }

    public static function title(string $item): string
    {
        return match ($item) {
            self::COMMUNITY =>
            'Community',

            self::EDUCATION =>
            'Education',

            self::EMPLOYED_IN =>
            'Employed In',

            self::OCCUPATION =>
            'Occupation',

            self::ANNUAL_INCOME =>
            'Annual Income',

            self::LOCATION =>
            'Location',

            self::SPECIAL_REQUEST =>
            'Any Special Request',

            default => '',
        };
    }

    public static function section(string $item): string
    {
        return match ($item) {
            self::COMMUNITY =>
            'religious',

            self::EDUCATION,
            self::EMPLOYED_IN,
            self::OCCUPATION,
            self::ANNUAL_INCOME =>
            'professional',

            self::LOCATION =>
            'location',

            self::SPECIAL_REQUEST =>
            'special-request',

            default => '',
        };
    }
}
