<?php

declare(strict_types=1);

namespace App\Validation;

/**
 * Field Officer validation rules used by administrative screens.
 */
final class FieldOfficerValidation
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function createRules(): array
    {
        return [
            'full_name' => [
                'label' => 'Name',

                'rules' => [
                    'required',
                    'string',
                    'min_length[2]',
                    'max_length[150]',
                    'regex_match[/^[\pL\pM .\'-]+$/u]',
                ],

                'errors' => [
                    'required' =>
                    'Name is required.',

                    'min_length' =>
                    'Name must contain at least 2 characters.',

                    'max_length' =>
                    'Name must not exceed 150 characters.',

                    'regex_match' =>
                    'Name contains unsupported characters.',
                ],
            ],

            'mobile_number' => [
                'label' => 'Mobile Number',

                'rules' => [
                    'required',
                    'regex_match[/^[6-9][0-9]{9}$/]',
                ],

                'errors' => [
                    'required' =>
                    'Mobile number is required.',

                    'regex_match' =>
                    'Enter a valid 10-digit Indian mobile number.',
                ],
            ],

            'aadhaar_number' =>
            self::aadhaarRules(),

            'pan_number' =>
            self::panRules(),

            'country_id' =>
            self::requiredIdentifierRules(
                'Country'
            ),

            'state_id' =>
            self::requiredIdentifierRules(
                'State'
            ),

            'city_id' =>
            self::requiredIdentifierRules(
                'City'
            ),

            'address' =>
            self::addressRules(),

            'upi_id' =>
            self::upiRules(),
        ];
    }

    /**
     * Name, mobile number and officer code remain immutable.
     *
     * Aadhaar and PAN remain required but may be changed.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function updateRules(): array
    {
        return [
            'aadhaar_number' =>
            self::aadhaarRules(),

            'pan_number' =>
            self::panRules(),

            'country_id' =>
            self::requiredIdentifierRules(
                'Country'
            ),

            'state_id' =>
            self::requiredIdentifierRules(
                'State'
            ),

            'city_id' =>
            self::requiredIdentifierRules(
                'City'
            ),

            'address' =>
            self::addressRules(),

            'upi_id' =>
            self::upiRules(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function aadhaarRules(): array
    {
        return [
            'label' =>
            'Aadhaar Number',

            'rules' => [
                'required',
                'exact_length[12]',
                'regex_match[/^[0-9]{12}$/]',
            ],

            'errors' => [
                'required' =>
                'Aadhaar number is required.',

                'exact_length' =>
                'Aadhaar number must contain exactly 12 digits.',

                'regex_match' =>
                'Enter a valid 12-digit Aadhaar number.',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function panRules(): array
    {
        return [
            'label' =>
            'PAN Number',

            'rules' => [
                'required',
                'exact_length[10]',
                'regex_match[/^[A-Z]{5}[0-9]{4}[A-Z]$/]',
            ],

            'errors' => [
                'required' =>
                'PAN number is required.',

                'exact_length' =>
                'PAN number must contain exactly 10 characters.',

                'regex_match' =>
                'Enter a valid PAN number, for example ABCDE1234F.',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function upiRules(): array
    {
        return [
            'label' =>
            'UPI ID',

            'rules' => [
                'permit_empty',
                'max_length[150]',
                'regex_match[/^[A-Za-z0-9._-]{2,256}@[A-Za-z][A-Za-z0-9.-]{1,63}$/]',
            ],

            'errors' => [
                'max_length' =>
                'UPI ID must not exceed 150 characters.',

                'regex_match' =>
                'Enter a valid UPI ID, for example name@bank.',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function addressRules(): array
    {
        return [
            'label' =>
            'Address',

            'rules' => [
                'permit_empty',
                'string',
                'max_length[500]',
            ],

            'errors' => [
                'max_length' =>
                'Address must not exceed 500 characters.',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function requiredIdentifierRules(
        string $label
    ): array {
        return [
            'label' =>
            $label,

            'rules' => [
                'required',
                'is_natural_no_zero',
            ],

            'errors' => [
                'required' =>
                $label . ' is required.',

                'is_natural_no_zero' =>
                'Select a valid '
                    . strtolower($label)
                    . '.',
            ],
        ];
    }
}
