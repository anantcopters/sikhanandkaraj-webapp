<?php

declare(strict_types=1);

namespace App\Validation\Prelaunch;

use App\Validation\Profile\BasicDetailsValidation;

/**
 * Validation definitions for the standalone pre-launch form.
 */
final class PrelaunchProfileValidation
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function createRules(): array
    {
        /*
         * Reuse the current Basic Details rules rather than duplicating
         * full name, DOB, marital status, height, mother tongue and
         * location validation.
         */
        $rules = BasicDetailsValidation::rules();

        return array_merge(
            $rules,
            [
                'profile_created_for' => [
                    'label' => 'Profile created for',
                    'rules' => [
                        'required',
                        'in_list[SELF,SON,DAUGHTER,BROTHER,SISTER,RELATIVE,FRIEND]',
                    ],
                    'errors' => [
                        'required' =>
                        'Please select who this profile is for.',
                        'in_list' =>
                        'Please select a valid profile creator option.',
                    ],
                ],

                'gender' => [
                    'label' => 'Gender',
                    'rules' => [
                        'required',
                        'in_list[MALE,FEMALE]',
                    ],
                    'errors' => [
                        'required' => 'Please select gender.',
                        'in_list' => 'Please select a valid gender.',
                    ],
                ],

                'email' => [
                    'label' => 'Email',
                    'rules' => [
                        'required',
                        'valid_email',
                        'max_length[190]',
                    ],
                ],

                'country_code' => [
                    'label' => 'Country code',
                    'rules' => [
                        'required',
                        'regex_match[/^\+[1-9][0-9]{0,3}$/]',
                    ],
                ],

                'mobile_number' => [
                    'label' => 'Mobile number',
                    'rules' => [
                        'required',
                        'regex_match[/^[0-9]{10,15}$/]',
                    ],
                ],

                'highest_education_id' => [
                    'label' => 'Highest education',
                    'rules' => [
                        'required',
                        'is_natural_no_zero',
                    ],
                ],

                'employed_in' => [
                    'label' => 'Employed in',
                    'rules' => [
                        'required',
                        'in_list[GOVERNMENT_PSU,PRIVATE,BUSINESS,DEFENSE,SELF_EMPLOYED,NOT_WORKING]',
                    ],
                ],

                'occupation_id' => [
                    'label' => 'Occupation',
                    'rules' => [
                        'required',
                        'is_natural_no_zero',
                    ],
                ],

                'father_name' => self::personNameRule(
                    'Father name'
                ),

                'mother_name' => self::personNameRule(
                    'Mother name'
                ),

                'family_value_id' => self::masterRule(
                    'Family values'
                ),

                'family_type_id' => self::masterRule(
                    'Family type'
                ),

                'family_status_id' => self::masterRule(
                    'Family status'
                ),

                'sikh_community_id' => self::masterRule(
                    'Community'
                ),

                'sikh_subcommunity_id' => self::masterRule(
                    'Sub-community'
                ),

                'field_officer_code' => [
                    'label' => 'Field Officer code',
                    'rules' => [
                        'required',
                        'min_length[4]',
                        'max_length[20]',
                        'regex_match[/^[A-Za-z0-9-]+$/]',
                    ],
                    'errors' => [
                        'required' =>
                        'Please enter the Field Officer code.',

                        'min_length' =>
                        'The Field Officer code is too short.',

                        'max_length' =>
                        'The Field Officer code cannot exceed 20 characters.',

                        'regex_match' =>
                        'The Field Officer code may contain only letters, numbers and hyphens.',
                    ],
                ],

                'verified_field_officer_id' => [
                    'label' => 'Verified Field Officer',
                    'rules' => [
                        'required',
                        'is_natural_no_zero',
                    ],
                    'errors' => [
                        'required' =>
                        'Please verify the Field Officer before saving the profile.',

                        'is_natural_no_zero' =>
                        'Please verify a valid Field Officer.',
                    ],
                ],

                'consent' => [
                    'label' => 'Consent',
                    'rules' => [
                        'required',
                        'in_list[1]',
                    ],
                    'errors' => [
                        'required' =>
                        'Member consent is required.',
                        'in_list' =>
                        'Member consent is required.',
                    ],
                ],
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function adminContactRules(): array
    {
        return [
            'email' => [
                'label' => 'Email',
                'rules' => [
                    'required',
                    'valid_email',
                    'max_length[190]',
                ],
            ],

            'country_code' => [
                'label' => 'Country code',
                'rules' => [
                    'required',
                    'regex_match[/^\+[1-9][0-9]{0,3}$/]',
                ],
            ],

            'mobile_number' => [
                'label' => 'Mobile number',
                'rules' => [
                    'required',
                    'regex_match[/^[0-9]{10,15}$/]',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function personNameRule(
        string $label
    ): array {
        return [
            'label' => $label,
            'rules' => [
                'required',
                'min_length[2]',
                'max_length[100]',
                'regex_match[/^[\p{L}\p{M} .\'-]+$/u]',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function masterRule(
        string $label
    ): array {
        return [
            'label' => $label,
            'rules' => [
                'required',
                'is_natural_no_zero',
            ],
        ];
    }
}
