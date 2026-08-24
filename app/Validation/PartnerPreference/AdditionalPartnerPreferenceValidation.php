<?php

declare(strict_types=1);

namespace App\Validation\PartnerPreference;

use App\Support\PartnerPreference\AdditionalPreferenceItem;

/**
 * Server-side validation for non-Basic partner preferences.
 */
final class AdditionalPartnerPreferenceValidation
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function rules(
        string $item
    ): array {
        return match ($item) {
            AdditionalPreferenceItem::COMMUNITY => [
                'community_ids' =>
                self::requiredSelection(
                    'communities'
                ),

                'community_ids.*' =>
                self::positiveIdRule(
                    'community'
                ),

                'is_compulsory' =>
                self::matchModeRule(),
            ],

            AdditionalPreferenceItem::EDUCATION => [
                'education_ids' =>
                self::requiredSelection(
                    'education qualifications'
                ),

                'education_ids.*' =>
                self::positiveIdRule(
                    'education'
                ),

                'is_compulsory' =>
                self::matchModeRule(),
            ],

            AdditionalPreferenceItem::EMPLOYED_IN => [
                'employed_in_values' =>
                self::requiredSelection(
                    'employment types'
                ),

                'employed_in_values.*' => [
                    'label' =>
                    'Employment type',

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
                self::requiredSelection(
                    'occupations'
                ),

                'occupation_ids.*' =>
                self::positiveIdRule(
                    'occupation'
                ),

                'is_compulsory' =>
                self::matchModeRule(),
            ],

            AdditionalPreferenceItem::ANNUAL_INCOME => [
                'annual_income_ids' =>
                self::requiredSelection(
                    'annual income options'
                ),

                'annual_income_ids.*' =>
                self::positiveIdRule(
                    'annual income'
                ),

                'is_compulsory' =>
                self::matchModeRule(),
            ],

            AdditionalPreferenceItem::LOCATION => [
                /*
                * Country is the top-level Location preference.
                * At least one Country must be selected.
                */
                'country_ids' =>
                self::requiredSelection(
                    'countries'
                ),

                'country_ids.*' =>
                self::positiveIdRule(
                    'country'
                ),

                /*
                * State is optional.
                *
                * Empty State means:
                * Any State within the selected Countries.
                *
                * Do not add a wildcard rule here because the
                * field can legitimately be absent from POST.
                * State IDs are subsequently checked against
                * master data by the service when supplied.
                */
                'state_ids' => [
                    'label' =>
                    'States',

                    'rules' => [
                        'permit_empty',
                    ],
                ],

                /*
                * City is optional.
                *
                * Empty City means:
                * Any City within the selected States.
                *
                * Do not add a wildcard rule here because City
                * can legitimately be absent from POST, including
                * when the control is disabled because no State
                * has been selected.
                *
                * Supplied City IDs are subsequently checked
                * against master data by the service.
                */
                'city_ids' => [
                    'label' =>
                    'Cities',

                    'rules' => [
                        'permit_empty',
                    ],
                ],

                'is_compulsory' =>
                self::matchModeRule(),
            ],

            AdditionalPreferenceItem::SPECIAL_REQUEST => [
                'request_text' => [
                    'label' =>
                    'Special request',

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
    private static function requiredSelection(
        string $label
    ): array {
        return [
            'label' =>
            ucfirst($label),

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
    private static function positiveIdRule(
        string $label
    ): array {
        return [
            'label' =>
            ucfirst($label),

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
            'label' =>
            'Matching preference',

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
