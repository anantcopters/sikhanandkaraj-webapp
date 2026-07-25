<?php

declare(strict_types=1);

namespace App\Validation\Profile;

/**
 * Request validation rules for member photo operations.
 */
final class MemberPhotoValidation
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function uploadRules(
        int $maximumSizeKb
    ): array {
        return [
            'photo' => [
                'label' => 'photo',
                'rules' => [
                    'uploaded[photo]',
                    'max_size[photo,' . $maximumSizeKb . ']',
                    'is_image[photo]',
                    'mime_in[photo,image/jpeg,image/png,image/webp]',
                    'ext_in[photo,jpg,jpeg,png,webp]',
                ],
                'errors' => [
                    'uploaded' =>
                    'Please choose a photo to upload.',
                    'max_size' =>
                    'The photo must not exceed 10 MB.',
                    'is_image' =>
                    'The selected file is not a valid image.',
                    'mime_in' =>
                    'Only JPEG, PNG and WEBP photos are allowed.',
                    'ext_in' =>
                    'Only JPEG, PNG and WEBP photos are allowed.',
                ],
            ],

            'visibility' => [
                'label' => 'visibility',
                'rules' => [
                    'required',
                    'in_list[PUBLIC,INTERESTED_MEMBERS]',
                ],
                'errors' => [
                    'required' =>
                    'Please select photo visibility.',
                    'in_list' =>
                    'Please select a valid visibility option.',
                ],
            ],

            'is_primary' => [
                'label' => 'main photo',
                'rules' => [
                    'permit_empty',
                    'in_list[0,1]',
                ],
            ],
        ];
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
}
