<?php

declare(strict_types=1);

namespace App\Validation\Profile;

/**
 * Server-side validation rules for Family Details.
 */
final class FamilyDetailsValidation
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function rules(): array
    {
        return [
            'family_value_id' => self::requiredMaster(
                'Family value',
                'Please select your family value.'
            ),

            'family_type_id' => self::requiredMaster(
                'Family type',
                'Please select your family type.'
            ),

            'family_status_id' => self::requiredMaster(
                'Family status',
                'Please select your family status.'
            ),

            'community_id' => self::requiredMaster(
                'Community',
                'Please select your community.'
            ),

            'subcommunity_id' => self::requiredMaster(
                'Sub-community',
                'Please select your sub-community.'
            ),

            'father_occupation_id' => [
                'label' => "Father's occupation",
                'rules' => [
                    'permit_empty',
                    'is_natural_no_zero',
                ],
                'errors' => [
                    'is_natural_no_zero' =>
                    "Please select a valid father's occupation.",
                ],
            ],

            'mother_occupation_id' => [
                'label' => "Mother's occupation",
                'rules' => [
                    'permit_empty',
                    'is_natural_no_zero',
                ],
                'errors' => [
                    'is_natural_no_zero' =>
                    "Please select a valid mother's occupation.",
                ],
            ],

            'brothers_count' => self::siblingCountRules(
                'Number of brothers'
            ),

            'married_brothers_count' =>
            self::siblingCountRules(
                'Married brothers'
            ),

            'sisters_count' => self::siblingCountRules(
                'Number of sisters'
            ),

            'married_sisters_count' =>
            self::siblingCountRules(
                'Married sisters'
            ),

            'country_id' => self::requiredMaster(
                'Country',
                'Please select a valid country.'
            ),

            'state_id' => self::requiredMaster(
                'State',
                'Please select your family state.'
            ),

            'city_id' => self::requiredMaster(
                'City',
                'Please select your family city.'
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function requiredMaster(
        string $label,
        string $requiredMessage
    ): array {
        return [
            'label' => $label,
            'rules' => [
                'required',
                'is_natural_no_zero',
            ],
            'errors' => [
                'required' => $requiredMessage,
                'is_natural_no_zero' => $requiredMessage,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function siblingCountRules(
        string $label
    ): array {
        return [
            'label' => $label,
            'rules' => [
                'required',
                'integer',
                'greater_than_equal_to[0]',
                'less_than_equal_to[10]',
            ],
        ];
    }
}
