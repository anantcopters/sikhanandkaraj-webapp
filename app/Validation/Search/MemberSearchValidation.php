<?php

declare(strict_types=1);

namespace App\Validation\Search;

/**
 * Provides authoritative scalar validation for member Search.
 *
 * Multi-value master IDs are validated by MemberSearchService against active
 * master values.
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
     * Supported result sorting.
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
     * Return scalar Search validation rules.
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
                    'less_than_equal_to[80]',
                ],
            ],

            'age_max' => [
                'label' =>
                'Maximum age',

                'rules' => [
                    'permit_empty',
                    'integer',
                    'greater_than_equal_to[18]',
                    'less_than_equal_to[80]',
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
