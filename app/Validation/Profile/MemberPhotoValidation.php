<?php

declare(strict_types=1);

namespace App\Validation\Profile;

use Config\MemberMedia;

/**
 * Request validation rules for member photo operations.
 */
final class MemberPhotoValidation
{
    /**
     * Return configuration-driven member-photo upload rules.
     *
     * Image dimensions are also verified again by ImageProcessorService
     * after decoding the actual source image.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function uploadRules(
        MemberMedia $config
    ): array {
        $maximumSizeMb = max(
            1,
            (int) ceil(
                $config->profileMaxSizeKb / 1024
            )
        );

        $rules = [
            'photo' => [
                'label' => 'Photo',

                'rules' => [
                    'uploaded[photo]',
                    'is_image[photo]',

                    'mime_in['
                        . 'photo,image/jpeg,image/png'
                        . ']',

                    'ext_in['
                        . 'photo,jpg,jpeg,png'
                        . ']',

                    'max_size['
                        . 'photo,'
                        . $config->profileMaxSizeKb
                        . ']',

                    'min_dims['
                        . 'photo,'
                        . $config->minimumWidth
                        . ','
                        . $config->minimumHeight
                        . ']',

                    'max_dims['
                        . 'photo,'
                        . $config->maximumWidth
                        . ','
                        . $config->maximumHeight
                        . ']',
                ],

                'errors' => [
                    'uploaded' =>
                    'Please choose a photo to upload.',

                    'is_image' =>
                    'The selected file is not a valid image.',

                    'mime_in' =>
                    'Only JPEG and PNG photos are allowed.',

                    'ext_in' =>
                    'Only JPEG and PNG photos are allowed.',

                    'max_size' =>
                    'The photo must not exceed '
                        . $maximumSizeMb
                        . ' MB.',

                    'min_dims' =>
                    'The photo must be at least '
                        . $config->minimumWidth
                        . ' × '
                        . $config->minimumHeight
                        . ' pixels.',

                    'max_dims' =>
                    'The photo must not exceed '
                        . $config->maximumWidth
                        . ' × '
                        . $config->maximumHeight
                        . ' pixels.',
                ],
            ],

            'visibility' => [
                'label' => 'Photo visibility',

                'rules' => [
                    'required',
                    'in_list[PUBLIC,INTERESTED_MEMBERS]',
                ],

                'errors' => [
                    'required' =>
                    'Please select who can view this photo.',

                    'in_list' =>
                    'Please select a valid photo visibility option.',
                ],
            ],
        ];

        return array_merge(
            $rules,
            self::focalPositionRules()
        );
    }

    /**
     * @return array<string, string>
     */
    public static function visibilityRules(): array
    {
        return [
            'visibility' =>
            'required|in_list[PUBLIC,INTERESTED_MEMBERS]',
        ];
    }

    /**
     * Validate member-selected photo focal position.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function focalPositionRules(): array
    {
        return [
            'focal_x' => [
                'label' => 'Horizontal photo position',
                'rules' => [
                    'required',
                    'integer',
                    'greater_than_equal_to[0]',
                    'less_than_equal_to[100]',
                ],
                'errors' => [
                    'required' =>
                    'Please adjust the photo position.',
                    'integer' =>
                    'The photo position is invalid.',
                    'greater_than_equal_to' =>
                    'The photo position is invalid.',
                    'less_than_equal_to' =>
                    'The photo position is invalid.',
                ],
            ],

            'focal_y' => [
                'label' => 'Vertical photo position',
                'rules' => [
                    'required',
                    'integer',
                    'greater_than_equal_to[0]',
                    'less_than_equal_to[100]',
                ],
                'errors' => [
                    'required' =>
                    'Please adjust the photo position.',
                    'integer' =>
                    'The photo position is invalid.',
                    'greater_than_equal_to' =>
                    'The photo position is invalid.',
                    'less_than_equal_to' =>
                    'The photo position is invalid.',
                ],
            ],
        ];
    }
}
