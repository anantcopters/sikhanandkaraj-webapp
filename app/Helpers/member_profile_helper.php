<?php

declare(strict_types=1);

if (!function_exists('member_profile_placeholder')) {
    /**
     * Return the standard gender-based profile placeholder.
     *
     * This helper is only a presentation fallback.
     *
     * Actual member-photo authorization must be completed before
     * calling this helper. A placeholder must never be used as a
     * substitute for the application's photo privacy checks.
     */
    function member_profile_placeholder(
        mixed $gender
    ): string {
        $resolvedGender =
            mb_strtoupper(
                trim(
                    (string) $gender
                )
            );

        return base_url(
            in_array(
                $resolvedGender,
                [
                    'F',
                    'FEMALE',
                ],
                true
            )
                ? 'assets/images/Girl_Thumbnail.png'
                : 'assets/images/Boy_Thumbnail.png'
        );
    }
}
