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
                'label' => 'Email or mobile number',
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
                'label' => 'Password',
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
        ];
    }
}
