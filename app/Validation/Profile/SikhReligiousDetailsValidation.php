<?php

declare(strict_types=1);

namespace App\Validation\Profile;

/**
 * CI4 validation rules for Sikh and Religious Details.
 */
final class SikhReligiousDetailsValidation
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function rules(): array
    {
        return [
            'community_id' => [
                'label' => 'Community',
                'rules' => [
                    'required',
                    'is_natural_no_zero',
                ],
                'errors' => [
                    'required' =>
                    'Please select your community.',
                    'is_natural_no_zero' =>
                    'Please select a valid community.',
                ],
            ],

            'subcommunity_id' => [
                'label' => 'Sub-community',
                'rules' => [
                    'required',
                    'is_natural_no_zero',
                ],
                'errors' => [
                    'required' =>
                    'Please select your sub-community.',
                    'is_natural_no_zero' =>
                    'Please select a valid sub-community.',
                ],
            ],

            'birth_hour' => [
                'label' => 'Birth hour',
                'rules' => [
                    'required',
                    'integer',
                    'greater_than_equal_to[1]',
                    'less_than_equal_to[12]',
                ],
                'errors' => [
                    'required' =>
                    'Please select the birth hour.',
                    'integer' =>
                    'Please select a valid birth hour.',
                    'greater_than_equal_to' =>
                    'Please select a valid birth hour.',
                    'less_than_equal_to' =>
                    'Please select a valid birth hour.',
                ],
            ],

            'birth_minute' => [
                'label' => 'Birth minute',
                'rules' => [
                    'required',
                    'integer',
                    'greater_than_equal_to[0]',
                    'less_than_equal_to[59]',
                ],
                'errors' => [
                    'required' =>
                    'Please select the birth minute.',
                    'integer' =>
                    'Please select a valid birth hour.',
                    'greater_than_equal_to' =>
                    'Please select a valid birth hour.',
                    'less_than_equal_to' =>
                    'Please select a valid birth hour.',
                ],
            ],

            'birth_meridiem' => [
                'label' => 'AM or PM',
                'rules' => [
                    'required',
                    'in_list[AM,PM]',
                ],
                'errors' => [
                    'required' =>
                    'Please select AM or PM.',
                    'in_list' =>
                    'Please select a valid AM or PM value.',
                ],
            ],

            'birth_country_id' => [
                'label' => 'Birth country',
                'rules' => [
                    'required',
                    'is_natural_no_zero',
                ],
            ],

            'birth_state_id' => [
                'label' => 'Birth state',
                'rules' => [
                    'required',
                    'is_natural_no_zero',
                ],
                'errors' => [
                    'required' =>
                    'Please select the state of birth.',
                ],
            ],

            'birth_city_id' => [
                'label' => 'Birth city',
                'rules' => [
                    'required',
                    'is_natural_no_zero',
                ],
                'errors' => [
                    'required' =>
                    'Please select the city of birth.',
                ],
            ],

            'gotra' => [
                'label' => 'Gotra',
                'rules' => [
                    'permit_empty',
                    'max_length[100]',
                    'regex_match[/^[\pL\pN .\'\-]+$/u]',
                ],
                'errors' => [
                    'regex_match' =>
                    'Gotra contains unsupported characters.',
                ],
            ],

            'moon_sign_id' => [
                'label' => 'Moon sign',
                'rules' => [
                    'permit_empty',
                    'is_natural_no_zero',
                ],
            ],

            'birth_star_id' => [
                'label' => 'Birth star',
                'rules' => [
                    'permit_empty',
                    'is_natural_no_zero',
                ],
            ],

            'has_dosh' => [
                'label' => 'Dosh',
                'rules' => [
                    'permit_empty',
                    'in_list[NO,YES,DONT_KNOW,NOT_APPLICABLE]',
                ],
            ],
        ];
    }
}
