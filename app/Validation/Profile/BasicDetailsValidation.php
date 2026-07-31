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
                ],
                'errors' => [
                    'required' =>
                    'Please select your date of birth.',
                    'valid_date' =>
                    'Please enter a valid date of birth.',
                ],
            ],

            'marital_status_id' => [
                'label' => 'Marital status',
                'rules' => [
                    'required',
                    'is_natural_no_zero',
                ],
                'errors' => [
                    'required' =>
                    'Please select your marital status.',
                    'is_natural_no_zero' =>
                    'Please select a valid marital status.',
                ],
            ],

            'height_id' => [
                'label' => 'Height',
                'rules' => [
                    'required',
                    'is_natural_no_zero',
                ],
                'errors' => [
                    'required' =>
                    'Please select your height.',
                    'is_natural_no_zero' =>
                    'Please select a valid height.',
                ],
            ],

            'mother_tongue_id' => [
                'label' => 'Mother tongue',
                'rules' => [
                    'required',
                    'is_natural_no_zero',
                ],
                'errors' => [
                    'required' =>
                    'Please select your mother tongue.',
                    'is_natural_no_zero' =>
                    'Please select a valid mother tongue.',
                ],
            ],

            'country_id' => [
                'label' => 'Country',
                'rules' => [
                    'required',
                    'is_natural_no_zero',
                ],
                'errors' => [
                    'required' =>
                    'Country is required.',
                    'is_natural_no_zero' =>
                    'Please select a valid country.',
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
                    'Please select your state.',
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
                    'Please select your city.',
                    'is_natural_no_zero' =>
                    'Please select a valid city.',
                ],
            ],
        ];
    }
}
