<?php

declare(strict_types=1);

namespace App\Validation\Admin;

final class CouponValidation
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function rules(): array
    {
        return [
            'code' => [
                'label' =>
                'Coupon Code',

                'rules' =>
                'required|max_length[40]'
                    . '|regex_match[/^[A-Za-z0-9_-]+$/]',
            ],

            'description' => [
                'label' =>
                'Internal Description',

                'rules' =>
                'permit_empty|max_length[255]',
            ],

            'discount_type' => [
                'label' =>
                'Discount Type',

                'rules' =>
                'required|in_list[PERCENTAGE,FLAT]',
            ],

            'discount_value' => [
                'label' =>
                'Discount Value',

                'rules' =>
                'required|integer|greater_than[0]',
            ],

            'eligibility_type' => [
                'label' =>
                'Member Eligibility',

                'rules' =>
                'required|in_list[ALL,SELECTED,GENDER]',
            ],

            'eligible_gender' => [
                'label' =>
                'Gender',

                'rules' =>
                'permit_empty|in_list[MALE,FEMALE]',
            ],

            'usage_limit' => [
                'label' =>
                'Usage Limit',

                'rules' =>
                'required|integer|greater_than[0]',
            ],

            'expiry_date' => [
                'label' =>
                'Expiry Date',

                'rules' =>
                'required|valid_date[Y-m-d]',
            ],
        ];
    }
}
