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
            'family_value' => [
                'label' => 'Family value',
                'rules' => [
                    'required',
                    'in_list[ORTHODOX,TRADITIONAL,MODERATE,LIBERAL]',
                ],
                'errors' => [
                    'required' =>
                    'Please select your family value.',
                    'in_list' =>
                    'Please select a valid family value.',
                ],
            ],

            'family_type' => [
                'label' => 'Family type',
                'rules' => [
                    'required',
                    'in_list[JOINT_FAMILY,NUCLEAR_FAMILY,OTHERS]',
                ],
                'errors' => [
                    'required' =>
                    'Please select your family type.',
                    'in_list' =>
                    'Please select a valid family type.',
                ],
            ],

            'family_status' => [
                'label' => 'Family status',
                'rules' => [
                    'required',
                    'in_list[MIDDLE_CLASS,UPPER_MIDDLE_CLASS,HIGH_CLASS,RICH_AFFLUENT]',
                ],
                'errors' => [
                    'required' =>
                    'Please select your family status.',
                    'in_list' =>
                    'Please select a valid family status.',
                ],
            ],

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

            'brothers_count' => [
                'label' => 'Number of brothers',
                'rules' => [
                    'required',
                    'integer',
                    'greater_than_equal_to[0]',
                    'less_than_equal_to[10]',
                ],
            ],

            'married_brothers_count' => [
                'label' => 'Married brothers',
                'rules' => [
                    'required',
                    'integer',
                    'greater_than_equal_to[0]',
                    'less_than_equal_to[10]',
                ],
            ],

            'sisters_count' => [
                'label' => 'Number of sisters',
                'rules' => [
                    'required',
                    'integer',
                    'greater_than_equal_to[0]',
                    'less_than_equal_to[10]',
                ],
            ],

            'married_sisters_count' => [
                'label' => 'Married sisters',
                'rules' => [
                    'required',
                    'integer',
                    'greater_than_equal_to[0]',
                    'less_than_equal_to[10]',
                ],
            ],

            'country_id' => [
                'label' => 'Country',
                'rules' => [
                    'required',
                    'is_natural_no_zero',
                ],
            ],

            'state_id' => [
                'label' => 'State',
                'rules' => [
                    'required',
                    'is_natural_no_zero',
                ],
                'errors' => [
                    'required' =>
                    'Please select your family state.',
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
                    'Please select your family city.',
                ],
            ],
        ];
    }
}
