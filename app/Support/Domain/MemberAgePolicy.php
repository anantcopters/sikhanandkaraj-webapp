<?php

declare(strict_types=1);

namespace App\Support\Domain;

final class MemberAgePolicy
{
    public const MALE_MINIMUM_AGE = 21;

    public const FEMALE_MINIMUM_AGE = 18;

    private function __construct() {}

    public static function minimumAgeForGender(
        mixed $gender
    ): int {
        return Gender::isMale($gender)
            ? self::MALE_MINIMUM_AGE
            : self::FEMALE_MINIMUM_AGE;
    }
}
