<?php

declare(strict_types=1);

namespace App\Validation\Prelaunch;

/**
 * Upload validation rules for exactly three pre-launch photos.
 */
final class PrelaunchPhotoValidation
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function rules(): array
    {
        $rules = [];

        foreach ([1, 2, 3] as $sequence) {
            $field = 'photo_' . $sequence;

            $rules[$field] = [
                'label' => 'Photo ' . $sequence,
                'rules' => [
                    'uploaded[' . $field . ']',
                    'max_size[' . $field . ',5120]',
                    'is_image[' . $field . ']',
                    'mime_in[' . $field
                        . ',image/jpeg,image/png,image/webp]',
                    'ext_in[' . $field . ',jpg,jpeg,png,webp]',
                    'max_dims[' . $field . ',6000,6000]',
                    'min_dims[' . $field . ',400,400]',
                ],
                'errors' => [
                    'uploaded' =>
                    'Please upload photo ' . $sequence . '.',
                    'max_size' =>
                    'Photo ' . $sequence
                        . ' must not exceed 5 MB.',
                    'is_image' =>
                    'Photo ' . $sequence
                        . ' must be a valid image.',
                    'mime_in' =>
                    'Photo ' . $sequence
                        . ' must be JPG, PNG or WebP.',
                    'min_dims' =>
                    'Photo ' . $sequence
                        . ' must be at least 400 × 400 pixels.',
                ],
            ];
        }

        return $rules;
    }
}
