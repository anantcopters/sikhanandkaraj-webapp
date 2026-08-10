<?php

declare(strict_types=1);

namespace App\Validation\Search;

final class MemberSearchValidation
{
    public const MODES = [
        'basic',
        'advanced',
    ];

    public const SORTS = [
        'default',
        'latest',
        'oldest',
        'last_login',
    ];

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function rules(): array
    {
        return [
            'mode' => [
                'label' => 'Search type',
                'rules' => [
                    'permit_empty',
                    'in_list['
                        . implode(',', self::MODES)
                        . ']',
                ],
            ],

            'age_min' => [
                'label' => 'Minimum age',
                'rules' => [
                    'permit_empty',
                    'integer',
                    'greater_than_equal_to[18]',
                    'less_than_equal_to[100]',
                ],
            ],

            'age_max' => [
                'label' => 'Maximum age',
                'rules' => [
                    'permit_empty',
                    'integer',
                    'greater_than_equal_to[18]',
                    'less_than_equal_to[100]',
                ],
            ],

            'height_min_id' => [
                'label' => 'Minimum height',
                'rules' => [
                    'permit_empty',
                    'integer',
                    'greater_than[0]',
                ],
            ],

            'height_max_id' => [
                'label' => 'Maximum height',
                'rules' => [
                    'permit_empty',
                    'integer',
                    'greater_than[0]',
                ],
            ],

            'sort' => [
                'label' => 'Sort order',
                'rules' => [
                    'permit_empty',
                    'in_list['
                        . implode(',', self::SORTS)
                        . ']',
                ],
            ],

            'page' => [
                'label' => 'Page',
                'rules' => [
                    'permit_empty',
                    'integer',
                    'greater_than[0]',
                ],
            ],
        ];
    }

    private function __construct() {}
}
