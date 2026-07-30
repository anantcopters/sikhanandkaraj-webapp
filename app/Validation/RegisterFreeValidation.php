<?php

declare(strict_types=1);

namespace App\Validation;

/**
 * Provides server-side validation rules for the Register Free form.
 *
 * Keeping rules outside the controller makes them reusable, testable,
 * and easier to maintain as the registration form expands.
 */
final class RegisterFreeValidation
{
    /**
     * Allowed profile relationships.
     *
     * @var list<string>
     */
    public const PROFILE_TYPES = [
        'self',
        'son',
        'daughter',
        'brother',
        'sister',
    ];

    /**
     * Return registration field validation rules.
     *
     * Email is not collected during registration and is therefore not
     * included in these rules.
     *
     * Gender is added conditionally when the profile is created for Self.
     *
     * @param array<string, mixed> $input
     *
     * @return array<string, array<string, mixed>>
     */
    public static function rulesFor(array $input): array
    {
        $rules = [
            'profile_created_for' => [
                'label' => 'Profile created for',
                'rules' => [
                    'required',
                    'in_list['
                        . implode(',', self::PROFILE_TYPES)
                        . ']',
                ],
                'errors' => [
                    'required' =>
                    'Please select whom this profile is for.',

                    'in_list' =>
                    'Please select a valid profile type.',
                ],
            ],

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
                    'Please enter the full name.',

                    'min_length' =>
                    'The full name must contain at least 2 characters.',

                    'max_length' =>
                    'The full name cannot exceed 100 characters.',

                    'regex_match' =>
                    'The full name contains invalid characters.',
                ],
            ],

            'country_code' => [
                'label' => 'Country code',
                'rules' => [
                    'required',
                    'in_list[+91]',
                ],
                'errors' => [
                    'required' =>
                    'Please select a country code.',

                    'in_list' =>
                    'Please select a valid country code.',
                ],
            ],

            'mobile_number' => [
                'label' => 'Mobile number',
                'rules' => [
                    'required',
                    'regex_match[/^[6-9][0-9]{9}$/]',
                ],
                'errors' => [
                    'required' =>
                    'Please enter the mobile number.',

                    'regex_match' =>
                    'Please enter a valid 10-digit Indian mobile number.',
                ],
            ],
        ];

        $rules = array_merge(
            $rules,
            PasswordValidation::passwordRules(
                includeConfirmation: false
            )
        );

        /*
         * Gender is user-selectable only when creating a profile for Self.
         *
         * For other relationships, the registration service derives gender
         * from the selected relationship.
         */
        if (($input['profile_created_for'] ?? '') === 'self') {
            $rules['gender'] = [
                'label' => 'Gender',
                'rules' => [
                    'required',
                    'in_list[M,F]',
                ],
                'errors' => [
                    'required' =>
                    'Please select gender.',

                    'in_list' =>
                    'Please select a valid gender.',
                ],
            ];
        }

        return $rules;
    }

    /**
     * Prevent instantiation.
     */
    private function __construct() {}
}
