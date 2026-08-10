<?php

declare(strict_types=1);

namespace App\Validation;

final class AdminLoginValidation
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function rules(): array
    {
        return [
            'identifier' => [
                'label' =>
                'Email or mobile number',

                'rules' => [
                    'required',
                    'max_length[254]',
                ],

                'errors' => [
                    'required' =>
                    'Please enter your email address or mobile number.',

                    'max_length' =>
                    'The login identifier is too long.',
                ],
            ],

            'password' => [
                'label' =>
                'Password',

                'rules' => [
                    'required',
                    'max_length[128]',
                ],

                'errors' => [
                    'required' =>
                    'Please enter your password.',

                    'max_length' =>
                    'The password is too long.',
                ],
            ],

            'captcha_answer' => [
                'label' =>
                'Security verification',

                'rules' => [
                    'required',
                    'regex_match[/^[0-9]{1,2}$/]',
                ],

                'errors' => [
                    'required' =>
                    'Please enter the security verification answer.',

                    'regex_match' =>
                    'Please enter a valid security verification answer.',
                ],
            ],
        ];
    }
}
