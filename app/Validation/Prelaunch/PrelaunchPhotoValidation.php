<?php

declare(strict_types=1);

namespace App\Validation\Prelaunch;

use Config\Prelaunch;

/**
 * Upload validation rules for exactly three pre-launch photos.
 */
final class PrelaunchPhotoValidation
{
    /**
     * Return upload validation rules for all required photographs.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function rules(): array
    {
        /** @var Prelaunch $config */
        $config = config('Prelaunch');

        $maximumPhotoSizeKilobytes =
            $config->maximumPhotoSizeKilobytes;

        $maximumPhotoSizeMegabytes =
            (int) ceil(
                $maximumPhotoSizeKilobytes / 1024
            );

        $rules = [];

        foreach ([1, 2, 3] as $sequence) {
            $field = 'photo_' . $sequence;

            $rules[$field] = [
                'label' => 'Photo ' . $sequence,

                'rules' => [
                    'uploaded[' . $field . ']',

                    'max_size['
                        . $field
                        . ','
                        . $maximumPhotoSizeKilobytes
                        . ']',

                    'is_image[' . $field . ']',

                    'mime_in['
                        . $field
                        . ',image/jpeg,image/png,image/webp'
                        . ']',

                    'ext_in['
                        . $field
                        . ',jpg,jpeg,png,webp'
                        . ']',

                    'max_dims['
                        . $field
                        . ',6000,6000'
                        . ']',
                ],

                'errors' => [
                    'uploaded' =>
                    'Please upload photo '
                        . $sequence
                        . '.',

                    'max_size' =>
                    'Photo '
                        . $sequence
                        . ' must not exceed '
                        . $maximumPhotoSizeMegabytes
                        . ' MB.',

                    'is_image' =>
                    'Photo '
                        . $sequence
                        . ' must be a valid image.',

                    'mime_in' =>
                    'Photo '
                        . $sequence
                        . ' must be JPG, PNG or WebP.',

                    'ext_in' =>
                    'Photo '
                        . $sequence
                        . ' must be JPG, PNG or WebP.',

                    'max_dims' =>
                    'Photo '
                        . $sequence
                        . ' dimensions must not exceed '
                        . '6000 × 6000 pixels.',
                ],
            ];
        }

        return $rules;
    }
}
