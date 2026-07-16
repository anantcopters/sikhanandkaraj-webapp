<?php

declare(strict_types=1);

namespace App\Validation;

/**
 * Server-side rules for password login.
 */
final class LoginValidation
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
                        'The email or mobile number is too long.',
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

