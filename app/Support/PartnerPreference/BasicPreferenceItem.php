<?php

declare(strict_types=1);

namespace App\Support\PartnerPreference;

/**
 * Defines supported Basic Partner Preference item keys,
 * labels and helper messages.
 */
final class BasicPreferenceItem
{
    public const AGE = 'age';

    public const HEIGHT = 'height';

    public const MARITAL_STATUS = 'marital-status';

    public const HAVE_CHILDREN = 'have-children';

    public const MOTHER_TONGUE = 'mother-tongue';

    public const PHYSICAL_STATUS = 'physical-status';

    public const EATING_HABITS = 'eating-habits';

    public const DRINKING_HABITS = 'drinking-habits';

    /**
     * Return all supported Basic Preference item keys.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::AGE,
            self::HEIGHT,
            self::MARITAL_STATUS,
            self::HAVE_CHILDREN,
            self::MOTHER_TONGUE,
            self::PHYSICAL_STATUS,
            self::EATING_HABITS,
            self::DRINKING_HABITS,
        ];
    }

    /**
     * Determine whether the supplied URL item is supported.
     */
    public static function isValid(string $item): bool
    {
        return in_array(
            $item,
            self::all(),
            true
        );
    }

    /**
     * Return the display title for an item.
     */
    public static function title(string $item): string
    {
        return match ($item) {
            self::AGE =>
            'Age',

            self::HEIGHT =>
            'Height',

            self::MARITAL_STATUS =>
            'Marital Status',

            self::HAVE_CHILDREN =>
            'Have Children',

            self::MOTHER_TONGUE =>
            'Mother Tongue',

            self::PHYSICAL_STATUS =>
            'Physical Status',

            self::EATING_HABITS =>
            'Eating Habits',

            self::DRINKING_HABITS =>
            'Drinking Habits',

            default => '',
        };
    }

    /**
     * Return user-friendly text for the compulsory checkbox.
     */
    public static function compulsoryText(
        string $item
    ): string {
        return match ($item) {
            self::AGE =>
            'Show only matches within this preferred age range.',

            self::HEIGHT =>
            'Show only matches within this preferred height range.',

            self::MARITAL_STATUS =>
            'Show only matches with the selected marital status.',

            self::HAVE_CHILDREN =>
            'Show only matches that meet this children preference.',

            self::MOTHER_TONGUE =>
            'Show only matches with one of the selected mother tongues.',

            self::PHYSICAL_STATUS =>
            'Show only matches with the selected physical status.',

            self::EATING_HABITS =>
            'Show only matches with one of the selected eating habits.',

            self::DRINKING_HABITS =>
            'Show only matches with one of the selected drinking habits.',

            default =>
            'Show only matches that meet this preference.',
        };
    }
}
