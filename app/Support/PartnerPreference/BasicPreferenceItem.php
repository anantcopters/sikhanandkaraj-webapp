<?php

declare(strict_types=1);

namespace App\Support\PartnerPreference;

/**
 * Defines the supported Basic Partner Preference item keys.
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
            self::AGE => 'Age',
            self::HEIGHT => 'Height',
            self::MARITAL_STATUS => 'Marital Status',
            self::HAVE_CHILDREN => 'Have Children',
            self::MOTHER_TONGUE => 'Mother Tongue',
            self::PHYSICAL_STATUS => 'Physical Status',
            self::EATING_HABITS => 'Eating Habits',
            self::DRINKING_HABITS => 'Drinking Habits',
            default => '',
        };
    }

    /**
     * Return text used beside the compulsory checkbox.
     */
    public static function compulsoryText(string $item): string
    {
        $criterion = match ($item) {
            self::AGE => 'age range',
            self::HEIGHT => 'height range',
            self::MARITAL_STATUS => 'marital status',
            self::HAVE_CHILDREN => 'children preference',
            self::MOTHER_TONGUE => 'mother tongue criteria',
            self::PHYSICAL_STATUS => 'physical status',
            self::EATING_HABITS => 'eating habit criteria',
            self::DRINKING_HABITS => 'drinking habit criteria',
            default => 'selected criteria',
        };

        return sprintf(
            'Mark as compulsory to get matches exactly as per '
                . 'the specified %s.',
            $criterion
        );
    }
}
