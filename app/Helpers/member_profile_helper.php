<?php

declare(strict_types=1);

if (!function_exists('member_profile_placeholder')) {
    /**
     * Return the standard gender-based profile placeholder.
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

        $image = match ($resolvedGender) {
            'F',
            'FEMALE' =>
            'assets/images/Girl_Thumbnail.png',

            'M',
            'MALE' =>
            'assets/images/Boy_Thumbnail.png',

            /*
             * Current user model supports Male/Female only.
             * Retain Boy as the defensive visual fallback,
             * but log unexpected application data.
             */
            default =>
            'assets/images/user-dummy-img.jpg',
        };

        if (
            !in_array(
                $resolvedGender,
                [
                    'M',
                    'MALE',
                    'F',
                    'FEMALE',
                ],
                true
            )
        ) {
            log_message(
                'warning',
                'Member profile placeholder received invalid gender: {gender}',
                [
                    'gender' =>
                    $resolvedGender,
                ]
            );
        }

        return base_url(
            $image
        );
    }
}
