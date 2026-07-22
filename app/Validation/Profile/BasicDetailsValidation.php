<?php

declare(strict_types=1);

namespace App\Validation\Profile;

/**
 * Defines server-side validation for member basic details.
 */
final class BasicDetailsValidation
{
    /**
     * Return validation rules for the Basic Details section.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function rules(): array
    {
        $maximumDateOfBirth = date(
            'Y-m-d',
            strtotime('-18 years')
        );

        return [
            'full_name' => [
                'label' => 'Full name',
                'rules' => [
                    'required',
                    'min_length[2]',
                    'max_length[100]',
                    'regex_match[/^[\p{L}\p{M} .\'-]+$/u]',
                ],
                'errors' => [
                    'required' =>
                    'Please enter your full name.',
                    'min_length' =>
                    'Full name must contain at least 2 characters.',
                    'max_length' =>
                    'Full name cannot exceed 100 characters.',
                    'regex_match' =>
                    'Full name contains unsupported characters.',
                ],
            ],

            'date_of_birth' => [
                'label' => 'Date of birth',
                'rules' => [
                    'required',
                    'valid_date[Y-m-d]',
                    'less_than_equal_to[' . $maximumDateOfBirth . ']',
                ],
                'errors' => [
                    'required' =>
                    'Please select your date of birth.',
                    'valid_date' =>
                    'Please enter a valid date of birth.',
                    'less_than_equal_to' =>
                    'The member must be at least 18 years old.',
                ],
            ],

            'marital_status' => [
                'label' => 'Marital status',
                'rules' => [
                    'required',
                    'in_list[NEVER_MARRIED,DIVORCED,WIDOWED,ANNULLED,AWAITING_DIVORCE]',
                ],
                'errors' => [
                    'required' =>
                    'Please select your marital status.',
                    'in_list' =>
                    'Please select a valid marital status.',
                ],
            ],

            'height_cm' => [
                'label' => 'Height',
                'rules' => [
                    'required',
                    'integer',
                    'greater_than_equal_to[120]',
                    'less_than_equal_to[220]',
                ],
                'errors' => [
                    'required' =>
                    'Please select your height.',
                    'integer' =>
                    'Please select a valid height.',
                    'greater_than_equal_to' =>
                    'Height must be at least 120 cm.',
                    'less_than_equal_to' =>
                    'Height cannot exceed 220 cm.',
                ],
            ],

            'mother_tongue' => [
                'label' => 'Mother tongue',
                'rules' => [
                    'required',
                    'in_list[PUNJABI,HINDI,ENGLISH,URDU,OTHER]',
                ],
                'errors' => [
                    'required' =>
                    'Please select your mother tongue.',
                    'in_list' =>
                    'Please select a valid mother tongue.',
                ],
            ],

            'current_city' => [
                'label' => 'Current city',
                'rules' => [
                    'required',
                    'min_length[2]',
                    'max_length[100]',
                    'regex_match[/^[\p{L}\p{M} .\'-]+$/u]',
                ],
                'errors' => [
                    'required' =>
                    'Please enter your current city.',
                    'regex_match' =>
                    'Current city contains unsupported characters.',
                ],
            ],

            'current_state' => [
                'label' => 'Current state',
                'rules' => [
                    'required',
                    'min_length[2]',
                    'max_length[100]',
                    'regex_match[/^[\p{L}\p{M} .\'-]+$/u]',
                ],
                'errors' => [
                    'required' =>
                    'Please enter your current state.',
                    'regex_match' =>
                    'Current state contains unsupported characters.',
                ],
            ],

            'country_code' => [
                'label' => 'Country',
                'rules' => [
                    'required',
                    'exact_length[2]',
                    'alpha',
                ],
                'errors' => [
                    'required' =>
                    'Please select your country.',
                    'exact_length' =>
                    'Please select a valid country.',
                    'alpha' =>
                    'Please select a valid country.',
                ],
            ],
        ];
    }
}
