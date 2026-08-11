<?php

declare(strict_types=1);

namespace App\Validation\PartnerPreference;

use App\Support\PartnerPreference\BasicPreferenceItem;

/**
 * Returns server-side validation rules for one Basic Preference item.
 */
final class BasicPartnerPreferenceValidation
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function rules(string $item): array
    {
        return match ($item) {
            BasicPreferenceItem::AGE =>
            self::ageRules(),

            BasicPreferenceItem::HEIGHT =>
            self::heightRules(),

            BasicPreferenceItem::MARITAL_STATUS =>
            self::singleMasterRule(
                'marital_status_id',
                'marital status'
            ),

            BasicPreferenceItem::HAVE_CHILDREN => [
                'have_children' => [
                    'label' => 'Have children',
                    'rules' => [
                        'required',
                        'in_list[0,1]',
                    ],
                    'errors' => [
                        'required' =>
                        'Please select your children preference.',
                        'in_list' =>
                        'Please select a valid children preference.',
                    ],
                ],
                'is_compulsory' =>
                self::compulsoryRule(),
            ],

            BasicPreferenceItem::MOTHER_TONGUE => [
                'mother_tongue_ids' => [
                    'label' => 'Mother tongues',
                    'rules' => [
                        'required',
                    ],
                    'errors' => [
                        'required' =>
                        'Please select at least one mother tongue.',
                    ],
                ],
                'mother_tongue_ids.*' => [
                    'label' => 'Mother tongue',
                    'rules' => [
                        'is_natural_no_zero',
                    ],
                    'errors' => [
                        'is_natural_no_zero' =>
                        'Please select valid mother tongues.',
                    ],
                ],
                'is_compulsory' =>
                self::compulsoryRule(),
            ],

            BasicPreferenceItem::PHYSICAL_STATUS =>
            self::singleMasterRule(
                'physical_status_id',
                'physical status'
            ),

            BasicPreferenceItem::EATING_HABITS => [
                'eating_habit_ids' => [
                    'label' => 'Eating habits',
                    'rules' => [
                        'required',
                    ],
                    'errors' => [
                        'required' =>
                        'Please select at least one eating habit.',
                    ],
                ],
                'eating_habit_ids.*' => [
                    'label' => 'Eating habit',
                    'rules' => [
                        'is_natural_no_zero',
                    ],
                    'errors' => [
                        'is_natural_no_zero' =>
                        'Please select valid eating habits.',
                    ],
                ],
                'is_compulsory' =>
                self::compulsoryRule(),
            ],

            BasicPreferenceItem::DRINKING_HABITS => [
                'drinking_habit_ids' => [
                    'label' => 'Drinking habits',
                    'rules' => [
                        'required',
                    ],
                    'errors' => [
                        'required' =>
                        'Please select at least one drinking habit.',
                    ],
                ],
                'drinking_habit_ids.*' => [
                    'label' => 'Drinking habit',
                    'rules' => [
                        'is_natural_no_zero',
                    ],
                    'errors' => [
                        'is_natural_no_zero' =>
                        'Please select valid drinking habits.',
                    ],
                ],
                'is_compulsory' =>
                self::compulsoryRule(),
            ],

            default => [],
        };
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function ageRules(): array
    {
        return [
            'age_from' => [
                'label' => 'Age from',
                'rules' => [
                    'required',
                    'integer',
                    'greater_than_equal_to[18]',
                    'less_than_equal_to[80]',
                ],
                'errors' => [
                    'required' =>
                    'Please select the minimum age.',
                    'integer' =>
                    'Please select a valid minimum age.',
                    'greater_than_equal_to' =>
                    'Minimum age cannot be below 18.',
                    'less_than_equal_to' =>
                    'Minimum age cannot exceed 80.',
                ],
            ],
            'age_to' => [
                'label' => 'Age to',
                'rules' => [
                    'required',
                    'integer',
                    'greater_than_equal_to[18]',
                    'less_than_equal_to[80]',
                ],
                'errors' => [
                    'required' =>
                    'Please select the maximum age.',
                    'integer' =>
                    'Please select a valid maximum age.',
                    'greater_than_equal_to' =>
                    'Maximum age cannot be below 18.',
                    'less_than_equal_to' =>
                    'Maximum age cannot exceed 80.',
                ],
            ],
            'is_compulsory' =>
            self::compulsoryRule(),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function heightRules(): array
    {
        return [
            'height_from_id' => [
                'label' => 'Height from',
                'rules' => [
                    'required',
                    'is_natural_no_zero',
                ],
                'errors' => [
                    'required' =>
                    'Please select the minimum height.',
                    'is_natural_no_zero' =>
                    'Please select a valid minimum height.',
                ],
            ],
            'height_to_id' => [
                'label' => 'Height to',
                'rules' => [
                    'required',
                    'is_natural_no_zero',
                ],
                'errors' => [
                    'required' =>
                    'Please select the maximum height.',
                    'is_natural_no_zero' =>
                    'Please select a valid maximum height.',
                ],
            ],
            'is_compulsory' =>
            self::compulsoryRule(),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function singleMasterRule(
        string $field,
        string $label
    ): array {
        return [
            $field => [
                'label' => ucfirst($label),
                'rules' => [
                    'required',
                    'is_natural_no_zero',
                ],
                'errors' => [
                    'required' =>
                    sprintf(
                        'Please select the preferred %s.',
                        $label
                    ),
                    'is_natural_no_zero' =>
                    sprintf(
                        'Please select a valid %s.',
                        $label
                    ),
                ],
            ],
            'is_compulsory' =>
            self::compulsoryRule(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function compulsoryRule(): array
    {
        return [
            'label' => 'Compulsory preference',
            'rules' => [
                'permit_empty',
                'in_list[0,1]',
            ],
            'errors' => [
                'in_list' =>
                'The compulsory preference value is invalid.',
            ],
        ];
    }
}
