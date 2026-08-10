<?php

declare(strict_types=1);

namespace App\Validation\Search;

/**
 * Provides authoritative scalar validation for member Search requests.
 *
 * Master-data IDs and array relationships are additionally validated by
 * MemberSearchService against currently active master data.
 */
final class MemberSearchValidation
{
    /**
     * Supported Search modes.
     *
     * @var list<string>
     */
    public const MODES = [
        'basic',
        'advanced',
    ];

    /**
     * Supported Search result sorting.
     *
     * @var list<string>
     */
    public const SORTS = [
        'default',
        'latest',
        'oldest',
        'last_login',
    ];

    /**
     * Return server-side Search validation rules.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function rules(): array
    {
        return [
            'mode' => [
                'label' =>
                'Search type',

                'rules' => [
                    'permit_empty',
                    'in_list['
                        . implode(
                            ',',
                            self::MODES
                        )
                        . ']',
                ],

                'errors' => [
                    'in_list' =>
                    'Please select a valid search type.',
                ],
            ],

            'age_min' => [
                'label' =>
                'Minimum age',

                'rules' => [
                    'permit_empty',
                    'integer',
                    'greater_than_equal_to[18]',
                    'less_than_equal_to[100]',
                ],

                'errors' => [
                    'integer' =>
                    'Please enter a valid minimum age.',

                    'greater_than_equal_to' =>
                    'Minimum age cannot be below 18.',

                    'less_than_equal_to' =>
                    'Minimum age cannot exceed 100.',
                ],
            ],

            'age_max' => [
                'label' =>
                'Maximum age',

                'rules' => [
                    'permit_empty',
                    'integer',
                    'greater_than_equal_to[18]',
                    'less_than_equal_to[100]',
                ],

                'errors' => [
                    'integer' =>
                    'Please enter a valid maximum age.',

                    'greater_than_equal_to' =>
                    'Maximum age cannot be below 18.',

                    'less_than_equal_to' =>
                    'Maximum age cannot exceed 100.',
                ],
            ],

            'height_min_id' => [
                'label' =>
                'Minimum height',

                'rules' => [
                    'permit_empty',
                    'integer',
                    'greater_than[0]',
                ],
            ],

            'height_max_id' => [
                'label' =>
                'Maximum height',

                'rules' => [
                    'permit_empty',
                    'integer',
                    'greater_than[0]',
                ],
            ],

            'annual_income_from_id' => [
                'label' =>
                'Minimum annual income',

                'rules' => [
                    'permit_empty',
                    'integer',
                    'greater_than[0]',
                ],
            ],

            'annual_income_to_id' => [
                'label' =>
                'Maximum annual income',

                'rules' => [
                    'permit_empty',
                    'integer',
                    'greater_than[0]',
                ],
            ],

            'sort' => [
                'label' =>
                'Sort order',

                'rules' => [
                    'permit_empty',
                    'in_list['
                        . implode(
                            ',',
                            self::SORTS
                        )
                        . ']',
                ],

                'errors' => [
                    'in_list' =>
                    'Please select a valid sort order.',
                ],
            ],

            'page' => [
                'label' =>
                'Page',

                'rules' => [
                    'permit_empty',
                    'integer',
                    'greater_than[0]',
                ],
            ],
        ];
    }

    /**
     * Static utility class.
     */
    private function __construct() {}
}
