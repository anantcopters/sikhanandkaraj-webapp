<?php

declare(strict_types=1);

namespace App\Validation\PartnerPreference;

final class LifestylePartnerPreferenceValidation
{
    /**
     * @return array<string,array<string,mixed>>
     */
    public static function rules(): array
    {
        return [
            'lifestyle_option_ids' => [
                'label' =>
                'Lifestyle preferences',

                'rules' => [
                    'required',
                ],

                'errors' => [
                    'required' =>
                    'Please select at least one lifestyle preference.',
                ],
            ],

            'lifestyle_option_ids.*' => [
                'label' =>
                'Lifestyle preference',

                'rules' => [
                    'required',
                    'is_natural_no_zero',
                ],

                'errors' => [
                    'required' =>
                    'Please select a valid lifestyle preference.',

                    'is_natural_no_zero' =>
                    'Please select a valid lifestyle preference.',
                ],
            ],

            'is_compulsory' => [
                'label' =>
                'Preference mode',

                'rules' => [
                    'required',
                    'in_list[0,1]',
                ],
            ],
        ];
    }
}
