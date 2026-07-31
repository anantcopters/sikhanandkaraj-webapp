<?php

declare(strict_types=1);

namespace App\Validation\Profile;

final class LifestyleValidation
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function rules(): array
    {
        return [
            'lifestyle_option_ids' => [
                'label' => 'Lifestyle options',
                'rules' => [
                    'permit_empty',
                ],
            ],
        ];
    }
}
