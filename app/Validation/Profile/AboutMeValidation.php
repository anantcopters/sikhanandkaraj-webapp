<?php

declare(strict_types=1);

namespace App\Validation\Profile;

final class AboutMeValidation
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function rules(): array
    {
        return [
            'about_me' => [
                'label' => 'About Me',
                'rules' => [
                    'required',
                    'max_length[5000]',
                ],
                'errors' => [
                    'required' =>
                    'Please write a short introduction about yourself.',
                    'max_length' =>
                    'About Me is too long. Please keep it within 500 words.',
                ],
            ],
        ];
    }
}
