<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Resolves a member name according to viewer visibility permissions.
 *
 * This class contains presentation privacy only. It must not query the
 * database or decide whether a viewer is Admin, Super Admin or paid.
 * The calling service supplies the resolved entitlement.
 */
final class MemberNameVisibility
{
    private const FALLBACK_NAME = 'Member';

    /**
     * Return the member name which may safely be shown to the viewer.
     *
     * Female names are masked for viewers without full-name access:
     *
     * Jaswant Kaur      -> J Kaur
     * Jaswant Deep Kaur -> J Deep Kaur
     * Jaswant           -> J
     *
     * The remaining name parts retain their original casing.
     *
     * Admin/Super Admin services and future paid-member authorization
     * may set $canViewFullName to true.
     */
    public static function forDisplay(
        mixed $fullName,
        mixed $gender,
        bool $canViewFullName = false
    ): string {
        $normalizedName =
            self::normalizeName(
                $fullName
            );

        if ($normalizedName === '') {
            return self::FALLBACK_NAME;
        }

        if (
            $canViewFullName
            || !self::isFemale(
                $gender
            )
        ) {
            return $normalizedName;
        }

        return self::maskFirstName(
            $normalizedName
        );
    }

    /**
     * Normalize whitespace without changing name casing.
     */
    private static function normalizeName(
        mixed $fullName
    ): string {
        return preg_replace(
            '/\s+/u',
            ' ',
            trim(
                (string) $fullName
            )
        ) ?? '';
    }

    /**
     * Recognize the gender values currently used by the application.
     */
    private static function isFemale(
        mixed $gender
    ): bool {
        return in_array(
            mb_strtoupper(
                trim(
                    (string) $gender
                )
            ),
            [
                'F',
                'FEMALE',
            ],
            true
        );
    }

    /**
     * Replace only the first name with its first Unicode character.
     */
    private static function maskFirstName(
        string $fullName
    ): string {
        $nameParts = preg_split(
            '/\s+/u',
            $fullName,
            -1,
            PREG_SPLIT_NO_EMPTY
        );

        if (
            !is_array($nameParts)
            || $nameParts === []
        ) {
            return self::FALLBACK_NAME;
        }

        $firstNameInitial = mb_substr(
            (string) $nameParts[0],
            0,
            1
        );

        if ($firstNameInitial === '') {
            return self::FALLBACK_NAME;
        }

        $nameParts[0] =
            $firstNameInitial;

        return implode(
            ' ',
            $nameParts
        );
    }
}
