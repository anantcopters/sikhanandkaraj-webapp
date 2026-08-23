<?php

declare(strict_types=1);

namespace App\Validation\Prelaunch;

use App\Validation\Profile\BasicDetailsValidation;

/**
 * Validation definitions for the standalone prelaunch form.
 */
final class PrelaunchProfileValidation
{
    private const NEAREST_GURUDWARA_MAX_LENGTH = 300;

    private const PARENT_CONTACT_NUMBER_LENGTH = 10;

    private const FEMALE_MINIMUM_AGE = 18;

    private const MALE_MINIMUM_AGE = 21;

    /**
     * Return the complete profile-creation validation rules.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function createRules(
        bool $enableFieldOfficerVerification = false,
        string $gender = ''
    ): array {
        /*
         * Reuse shared basic-profile validation and remove only fields
         * which are intentionally not part of the prelaunch workflow.
         */
        $rules = BasicDetailsValidation::rules();

        unset(
            $rules['mother_tongue_id']
        );

        $normalizedGender = mb_strtoupper(
            trim($gender)
        );

        $minimumAge = $normalizedGender === 'MALE'
            ? self::MALE_MINIMUM_AGE
            : self::FEMALE_MINIMUM_AGE;

        $rules['date_of_birth'] = [
            'label' => 'Date of birth',
            'rules' => [
                'required',
                'valid_date[Y-m-d]',
                'minimum_age[' . $minimumAge . ']',
            ],
            'errors' => [
                'required' =>
                'Please select the member’s date of birth.',

                'valid_date' =>
                'Please enter a valid date of birth.',

                'minimum_age' =>
                'The member must be at least '
                    . $minimumAge
                    . ' years old.',
            ],
        ];

        $rules = array_merge(
            $rules,
            [
                'profile_created_for' => [
                    'label' => 'Profile created for',
                    'rules' => [
                        'required',
                        'in_list[SELF,SON,DAUGHTER,BROTHER,SISTER]',
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
                        'required' =>
                        'Please select gender.',

                        'in_list' =>
                        'Please select a valid gender.',
                    ],
                ],

                'email' => [
                    'label' => 'Email',
                    'rules' => [
                        'permit_empty',
                        'valid_email',
                        'max_length[190]',
                    ],
                    'errors' => [
                        'valid_email' =>
                        'Please enter a valid email address.',

                        'max_length' =>
                        'Email address cannot exceed 190 characters.',
                    ],
                ],

                'country_code' => [
                    'label' => 'Country code',
                    'rules' => [
                        'required',
                        'regex_match[/^\+[1-9][0-9]{0,3}$/]',
                    ],
                    'errors' => [
                        'required' =>
                        'Country code is required.',

                        'regex_match' =>
                        'Please provide a valid country code.',
                    ],
                ],

                'mobile_number' => [
                    'label' => 'Mobile number',
                    'rules' => [
                        'required',
                        'regex_match[/^[0-9]{10,15}$/]',
                    ],
                    'errors' => [
                        'required' =>
                        'Please enter mobile number.',

                        'regex_match' =>
                        'Please enter a valid mobile number.',
                    ],
                ],

                'highest_education_id' => [
                    'label' => 'Highest education',
                    'rules' => [
                        'required',
                        'is_natural_no_zero',
                    ],
                    'errors' => [
                        'required' =>
                        'Please select highest education.',

                        'is_natural_no_zero' =>
                        'Please select a valid highest education.',
                    ],
                ],

                'employed_in' => [
                    'label' => 'Employed in',
                    'rules' => [
                        'required',
                        'in_list[GOVERNMENT_PSU,PRIVATE,BUSINESS,DEFENSE,SELF_EMPLOYED,NOT_WORKING]',
                    ],
                    'errors' => [
                        'required' =>
                        'Please select employment type.',

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
                        'Please select occupation.',

                        'is_natural_no_zero' =>
                        'Please select a valid occupation.',
                    ],
                ],

                'father_name' => self::personNameRule(
                    'Father’s name'
                ),

                'mother_name' => self::personNameRule(
                    'Mother’s name'
                ),

                'parent_contact_number' => [
                    'label' =>
                    'Any Parent/Guardian Contact Number',

                    'rules' => [
                        'required',
                        'regex_match[/^[6-9][0-9]{9}$/]',
                        'differs[mobile_number]',
                    ],

                    'errors' => [
                        'required' =>
                        'Please enter a contact number '
                            . 'for either parent/guardian.',

                        'regex_match' =>
                        'Please enter a valid 10-digit Indian '
                            . 'parent/guardian contact number.',

                        'differs' =>
                        'Parent/Guardian mobile number cannot '
                            . 'be the same as the member mobile number.',
                    ],
                ],

                'sikh_community_id' => self::masterRule(
                    'Community'
                ),

                'gotra' => [
                    'label' => 'Gotra',
                    'rules' => [
                        'required',
                        'min_length[2]',
                        'max_length[100]',
                        'regex_match[/^[\p{L}\p{M} .\'-]+$/u]',
                    ],
                    'errors' => [
                        'required' =>
                        'Please enter gotra.',

                        'min_length' =>
                        'Gotra must contain at least 2 characters.',

                        'max_length' =>
                        'Gotra cannot exceed 100 characters.',

                        'regex_match' =>
                        'Gotra may contain letters, spaces, apostrophes, full stops and hyphens only.',
                    ],
                ],

                'nearest_gurudwara' => [
                    'label' => 'Nearest Gurudwara',
                    'rules' => [
                        'required',
                        'max_length['
                            . self::NEAREST_GURUDWARA_MAX_LENGTH
                            . ']',
                    ],
                    'errors' => [
                        'required' =>
                        'Please enter the nearest Gurudwara name or location.',

                        'max_length' =>
                        'Nearest Gurudwara cannot exceed '
                            . self::NEAREST_GURUDWARA_MAX_LENGTH
                            . ' characters.',
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

        if ($enableFieldOfficerVerification) {
            $rules['field_officer_code'] = [
                'label' => 'SAK Volunteer ID',

                'rules' => [
                    'permit_empty',
                    'exact_length[11]',
                    'regex_match[/^FOSAK[0-9]{6}$/]',
                ],

                'errors' => [
                    'exact_length' =>
                    'Please enter a valid SAK Volunteer ID.',

                    'regex_match' =>
                    'Please enter a valid SAK Volunteer ID.',
                ],
            ];

            $rules['verified_field_officer_id'] = [
                'label' => 'Verified SAK Volunteer',

                'rules' => [
                    'permit_empty',
                    'is_natural_no_zero',
                ],

                'errors' => [
                    'is_natural_no_zero' =>
                    'Please verify a valid SAK Volunteer.',
                ],
            ];
        }

        return $rules;
    }

    /**
     * Return validation rules used for administrator contact updates.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function adminContactRules(): array
    {
        return [
            'email' => [
                'label' => 'Email',
                'rules' => [
                    'permit_empty',
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
     * Build a required person-name validation rule.
     *
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
            'errors' => [
                'required' =>
                "Please enter {$label}.",

                'min_length' =>
                "{$label} must contain at least 2 characters.",

                'max_length' =>
                "{$label} cannot exceed 100 characters.",

                'regex_match' =>
                "{$label} may contain letters, spaces, apostrophes, full stops and hyphens only.",
            ],
        ];
    }

    /**
     * Build a required positive master-record ID rule.
     *
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
            'errors' => [
                'required' =>
                "Please select {$label}.",

                'is_natural_no_zero' =>
                "Please select a valid {$label}.",
            ],
        ];
    }
}
