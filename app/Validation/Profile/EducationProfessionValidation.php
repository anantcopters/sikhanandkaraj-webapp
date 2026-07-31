<?php

declare(strict_types=1);

namespace App\Validation\Profile;

/**
 * Defines server-side validation for Education & Profession.
 */
final class EducationProfessionValidation
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function rules(): array
    {
        return [
            'highest_education_id' => [
                'label' => 'Highest education',
                'rules' => [
                    'required',
                    'is_natural_no_zero',
                ],
                'errors' => [
                    'required' =>
                    'Please select your highest education.',
                    'is_natural_no_zero' =>
                    'Please select a valid highest education.',
                ],
            ],

            'education_detail' => [
                'label' => 'Education in detail',
                'rules' => [
                    'permit_empty',
                    'max_length[500]',
                ],
                'errors' => [
                    'max_length' =>
                    'Education details cannot exceed '
                        . '500 characters.',
                ],
            ],

            'college_institution' => [
                'label' => 'College or institution',
                'rules' => [
                    'permit_empty',
                    'max_length[200]',
                ],
                'errors' => [
                    'max_length' =>
                    'College or institution cannot exceed '
                        . '200 characters.',
                ],
            ],

            'employed_in' => [
                'label' => 'Employed in',
                'rules' => [
                    'required',
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
                    'required' =>
                    'Please select where you are employed.',
                    'in_list' =>
                    'Please select a valid employment type.',
                ],
            ],

            'occupation_id' => [
                'label' => 'Occupation',
                'rules' => [
                    'required',
                    'is_natural_no_zero',
                ],
                'errors' => [
                    'required' =>
                    'Please select your occupation.',
                    'is_natural_no_zero' =>
                    'Please select a valid occupation.',
                ],
            ],

            'occupation_detail' => [
                'label' => 'Occupation in detail',
                'rules' => [
                    'permit_empty',
                    'max_length[500]',
                ],
                'errors' => [
                    'max_length' =>
                    'Occupation details cannot exceed '
                        . '500 characters.',
                ],
            ],

            'organization' => [
                'label' => 'Organization',
                'rules' => [
                    'permit_empty',
                    'max_length[200]',
                ],
                'errors' => [
                    'max_length' =>
                    'Organization cannot exceed '
                        . '200 characters.',
                ],
            ],

            'annual_income_id' => [
                'label' => 'Annual income',
                'rules' => [
                    'permit_empty',
                    'is_natural_no_zero',
                ],
                'errors' => [
                    'is_natural_no_zero' =>
                    'Please select a valid annual income.',
                ],
            ],
        ];
    }
}
