<?php

declare(strict_types=1);

namespace App\Validation\Member;

use Config\MemberMessaging;

final class MemberMessageValidation
{
    /**
     * @return array<string,array<string,mixed>>
     */
    public static function rules(): array
    {
        /** @var MemberMessaging $configuration */
        $configuration = config(
            MemberMessaging::class
        );

        $maximumLength = max(
            1,
            $configuration
                ->maximumMessageLength
        );

        return [
            'message' => [
                'label' =>
                'Message',

                'rules' => [
                    'required',
                    'max_length['
                        . $maximumLength
                        . ']',
                ],

                'errors' => [
                    'required' =>
                    'Please enter a message.',

                    'max_length' =>
                    'Message cannot exceed '
                        . $maximumLength
                        . ' characters.',
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

                    'max_length' =>
                    'The message request is invalid.',
                ],
            ],
        ];
    }

    private function __construct() {}
}
