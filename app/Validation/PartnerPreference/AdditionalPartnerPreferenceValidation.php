<?php

declare(strict_types=1);

namespace App\Validation\PartnerPreference;

use App\Support\PartnerPreference\AdditionalPreferenceItem;

/**
 * Server-side validation for remaining partner preferences.
 */
final class AdditionalPartnerPreferenceValidation
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function rules(string $item): array
    {
        return match ($item) {
            AdditionalPreferenceItem::COMMUNITY => [
                'community_ids' =>
                self::requiredArray(
                    'communities'
                ),

                'community_ids.*' =>
                self::masterIdRule(
                    'community'
                ),

                'is_compulsory' =>
                self::matchModeRule(),
            ],

            AdditionalPreferenceItem::EDUCATION => [
                'education_ids' =>
                self::requiredArray(
                    'education qualifications'
                ),

                'education_ids.*' =>
                self::masterIdRule(
                    'education'
                ),

                'is_compulsory' =>
                self::matchModeRule(),
            ],

            AdditionalPreferenceItem::EMPLOYED_IN => [
                'employed_in_values' =>
                self::requiredArray(
                    'employment types'
                ),

                'employed_in_values.*' => [
                    'label' => 'Employment type',
                    'rules' => [
                        'in_list['
                            . 'GOVERNMENT_PSU,'
                            . 'PRIVATE,'
                            . 'BUSINESS,'
                            . 'DEFENSE,'
                            . 'SELF_EMPLOYED,'
                            . 'NOT_WORKING'
                            . ']',
                    ],
                    'errors' => [
                        'in_list' =>
                        'Please select valid employment types.',
                    ],
                ],

                'is_compulsory' =>
                self::matchModeRule(),
            ],

            AdditionalPreferenceItem::OCCUPATION => [
                'occupation_ids' =>
                self::requiredArray(
                    'occupations'
                ),

                'occupation_ids.*' =>
                self::masterIdRule(
                    'occupation'
                ),

                'is_compulsory' =>
                self::matchModeRule(),
            ],

            AdditionalPreferenceItem::ANNUAL_INCOME => [
                'annual_income_from_id' => [
                    'label' => 'Annual income from',
                    'rules' => [
                        'required',
                        'is_natural_no_zero',
                    ],
                    'errors' => [
                        'required' =>
                        'Please select minimum annual income.',
                        'is_natural_no_zero' =>
                        'Please select a valid minimum income.',
                    ],
                ],

                'annual_income_to_id' => [
                    'label' => 'Annual income to',
                    'rules' => [
                        'required',
                        'is_natural_no_zero',
                    ],
                    'errors' => [
                        'required' =>
                        'Please select maximum annual income.',
                        'is_natural_no_zero' =>
                        'Please select a valid maximum income.',
                    ],
                ],

                'is_compulsory' =>
                self::matchModeRule(),
            ],

            AdditionalPreferenceItem::LOCATION => [
                'state_id' => [
                    'label' => 'State',
                    'rules' => [
                        'required',
                        'is_natural_no_zero',
                    ],
                    'errors' => [
                        'required' =>
                        'Please select a state.',
                        'is_natural_no_zero' =>
                        'Please select a valid state.',
                    ],
                ],

                'city_id' => [
                    'label' => 'City',
                    'rules' => [
                        'required',
                        'is_natural_no_zero',
                    ],
                    'errors' => [
                        'required' =>
                        'Please select a city.',
                        'is_natural_no_zero' =>
                        'Please select a valid city.',
                    ],
                ],

                'is_compulsory' =>
                self::matchModeRule(),
            ],

            AdditionalPreferenceItem::SPECIAL_REQUEST => [
                'request_text' => [
                    'label' => 'Special request',
                    'rules' => [
                        'required',
                        'min_length[10]',
                        'max_length[1000]',
                    ],
                    'errors' => [
                        'required' =>
                        'Please enter your special request.',
                        'min_length' =>
                        'Special request must contain at least 10 characters.',
                        'max_length' =>
                        'Special request cannot exceed 1000 characters.',
                    ],
                ],
            ],

            default => [],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private static function requiredArray(
        string $label
    ): array {
        return [
            'label' => ucfirst($label),
            'rules' => [
                'required',
            ],
            'errors' => [
                'required' =>
                'Please select at least one '
                    . $label
                    . '.',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function masterIdRule(
        string $label
    ): array {
        return [
            'label' => ucfirst($label),
            'rules' => [
                'is_natural_no_zero',
            ],
            'errors' => [
                'is_natural_no_zero' =>
                'Please select valid '
                    . $label
                    . ' values.',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function matchModeRule(): array
    {
        return [
            'label' => 'Matching preference',
            'rules' => [
                'required',
                'in_list[0,1]',
            ],
            'errors' => [
                'required' =>
                'Please select a matching preference.',
                'in_list' =>
                'Please select a valid matching preference.',
            ],
        ];
    }
}
