<?php

declare(strict_types=1);

namespace App\Validation\Member;

final class MemberMessageValidation
{
    public static function rules(): array
    {
        return [
            'message' => [
                'label' =>
                'Message',

                'rules' => [
                    'required',
                    'max_length[200]',
                ],

                'errors' => [
                    'required' =>
                    'Please enter a message.',

                    'max_length' =>
                    'Message cannot exceed 200 characters.',
                ],
            ],

            'client_request_id' => [
                'label' =>
                'Message request',

                'rules' => [
                    'required',
                    'max_length[64]',
                ],

                'errors' => [
                    'required' =>
                    'The message request is invalid.',
                ],
            ],
        ];
    }

    private function __construct() {}
}
