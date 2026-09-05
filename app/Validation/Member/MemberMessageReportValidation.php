<?php

declare(strict_types=1);

namespace App\Validation\Member;

final class MemberMessageReportValidation
{
    /**
     * @return array<string,array<string,mixed>>
     */
    public static function rules(): array
    {
        return [
            'reason' => [
                'label' =>
                'Report reason',

                'rules' => [
                    'required',
                    'in_list[
                        HARASSMENT,
                        ASKING_FOR_MONEY,
                        FAKE_IDENTITY,
                        INAPPROPRIATE,
                        UNWANTED_CONTACT,
                        SPAM,
                        OTHER
                    ]',
                ],

                'errors' => [
                    'required' =>
                    'Please select why you are reporting this message.',

                    'in_list' =>
                    'Please select a valid report reason.',
                ],
            ],

            'comment' => [
                'label' =>
                'Additional details',

                'rules' => [
                    'permit_empty',
                    'max_length[500]',
                ],

                'errors' => [
                    'max_length' =>
                    'Additional details cannot exceed 500 characters.',
                ],
            ],
        ];
    }

    private function __construct() {}
}
