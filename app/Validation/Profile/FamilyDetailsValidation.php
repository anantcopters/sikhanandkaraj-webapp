<?php

declare(strict_types=1);

namespace App\Validation\Profile;

/**
 * Server-side validation rules for Family Details.
 */
final class FamilyDetailsValidation
{
    private const PARENT_NAME_MAX_LENGTH = 150;

    private const GOTRA_MAX_LENGTH = 100;

    private const GURUDWARA_MAX_LENGTH = 300;

    private const REFERENCE_PERSON_MAX_LENGTH = 200;

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function rules(): array
    {
        return [
            /*
             * Optional master fields.
             *
             * When a value is supplied, it must still be a positive integer.
             * Active-master validation is performed again in the service.
             */
            'family_value_id' => self::optionalMaster(
                'Family value',
                'Please select a valid family value.'
            ),

            'family_type_id' => self::optionalMaster(
                'Family type',
                'Please select a valid family type.'
            ),

            'family_status_id' => self::optionalMaster(
                'Family status',
                'Please select a valid family status.'
            ),

            'community_id' => self::requiredMaster(
                'Community',
                'Please select your community.'
            ),

            'gotra' => [
                'label' => 'Father Gotra',
                'rules' => [
                    'required',
                    'max_length['
                        . self::GOTRA_MAX_LENGTH
                        . ']',
                ],
                'errors' => [
                    'required' =>
                    'Please enter your Father Gotra.',

                    'max_length' =>
                    'Father Gotra cannot exceed '
                        . self::GOTRA_MAX_LENGTH
                        . ' characters.',
                ],
            ],

            'gotra_maternal' => [
                'label' => 'Mother Gotra (Maternal Side)',
                'rules' => [
                    'required',
                    'max_length['
                        . self::GOTRA_MAX_LENGTH
                        . ']',
                ],
                'errors' => [
                    'required' =>
                    'Please enter your Mother Gotra (Maternal Side).',

                    'max_length' =>
                    'Mother Gotra (Maternal Side) cannot exceed '
                        . self::GOTRA_MAX_LENGTH
                        . ' characters.',
                ],
            ],

            'father_name' => self::parentNameRules(
                "Father's name",
                "Please enter your father's name."
            ),

            'mother_name' => self::parentNameRules(
                "Mother's name",
                "Please enter your mother's name."
            ),

            'parent_contact_number' => [
                'label' => 'Any Parent/Guardian Contact Number',
                'rules' => [
                    'required',
                    'regex_match[/^[6-9][0-9]{9}$/]',
                ],
                'errors' => [
                    'required' =>
                    'Please enter a contact number for either parent/guardian.',

                    'regex_match' =>
                    'Please enter a valid 10-digit Indian parent/guardian contact number.',
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

            'brothers_count' => self::siblingCountRules(
                'Number of brothers'
            ),

            'sisters_count' => self::siblingCountRules(
                'Number of sisters'
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

            'nearest_gurudwara' => [
                'label' => 'Nearest Gurudwara',
                'rules' => [
                    'required',
                    'max_length['
                        . self::GURUDWARA_MAX_LENGTH
                        . ']',
                ],
                'errors' => [
                    'required' =>
                    'Please enter the nearest Gurudwara name or location.',

                    'max_length' =>
                    'Nearest Gurudwara cannot exceed '
                        . self::GURUDWARA_MAX_LENGTH
                        . ' characters.',
                ],
            ],

            'reference_person_1' => self::optionalText(
                'First reference person',
                self::REFERENCE_PERSON_MAX_LENGTH
            ),

            'reference_person_2' => self::optionalText(
                'Second reference person',
                self::REFERENCE_PERSON_MAX_LENGTH
            ),

            'field_officer_code' => [
                'label' => 'SAK Volunteer ID',
                'rules' => [
                    'permit_empty',
                    'max_length[11]',
                    'regex_match[/^FOSAK[0-9]{6}$/]',
                ],
                'errors' => [
                    'max_length' =>
                    'Please enter a valid SAK Volunteer ID.',

                    'regex_match' =>
                    'Please enter a valid SAK Volunteer ID.',
                ],
            ],
        ];
    }

    /**
     * Validation rules for Father and Mother name.
     *
     * Names are not restricted to alpha_space because genuine names can
     * contain apostrophes, hyphens and periods.
     *
     * @return array<string, mixed>
     */
    private static function parentNameRules(
        string $label,
        string $requiredMessage
    ): array {
        return [
            'label' => $label,
            'rules' => [
                'required',
                'max_length['
                    . self::PARENT_NAME_MAX_LENGTH
                    . ']',
            ],
            'errors' => [
                'required' => $requiredMessage,

                'max_length' =>
                $label . ' cannot exceed '
                    . self::PARENT_NAME_MAX_LENGTH
                    . ' characters.',
            ],
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
     * Validate an optional master-data identifier.
     *
     * @return array<string, mixed>
     */
    private static function optionalMaster(
        string $label,
        string $invalidMessage
    ): array {
        return [
            'label' => $label,
            'rules' => [
                'permit_empty',
                'is_natural_no_zero',
            ],
            'errors' => [
                'is_natural_no_zero' =>
                $invalidMessage,
            ],
        ];
    }

    /**
     * Validate an optional free-text Family Details field.
     *
     * @return array<string, mixed>
     */
    private static function optionalText(
        string $label,
        int $maximumLength
    ): array {
        return [
            'label' => $label,
            'rules' => [
                'permit_empty',
                'max_length[' . $maximumLength . ']',
            ],
            'errors' => [
                'max_length' =>
                $label . ' cannot exceed '
                    . $maximumLength
                    . ' characters.',
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
            'errors' => [
                'required' =>
                'Please select the '
                    . strtolower($label)
                    . '.',

                'integer' =>
                'Please select a valid sibling count.',

                'greater_than_equal_to' =>
                'Sibling count cannot be less than zero.',

                'less_than_equal_to' =>
                'Sibling count cannot exceed 10.',
            ],
        ];
    }
}
