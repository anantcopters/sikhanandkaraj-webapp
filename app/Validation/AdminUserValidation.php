<?php

declare(strict_types=1);

namespace App\Validation;

final class AdminUserValidation
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function createRules(): array
    {
        return [
            'full_name' => [
                'label' => 'Name',
                'rules' => [
                    'required',
                    'min_length[2]',
                    'max_length[150]',
                ],
            ],

            'mobile_number' => [
                'label' => 'Mobile number',
                'rules' => [
                    'required',
                    'regex_match[/^[6-9][0-9]{9}$/]',
                ],
                'errors' => [
                    'regex_match' =>
                    'Enter a valid 10-digit Indian mobile number.',
                ],
            ],

            'email_address' => [
                'label' => 'Email address',
                'rules' => [
                    'required',
                    'valid_email',
                    'max_length[254]',
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function passwordRules(): array
    {
        return [
            'password' => [
                'label' => 'Password',
                'rules' => [
                    'required',
                    'min_length[10]',
                    'max_length[128]',
                    'regex_match[/[A-Z]/]',
                    'regex_match[/[a-z]/]',
                    'regex_match[/[0-9]/]',
                    'regex_match[/[^A-Za-z0-9]/]',
                ],
                'errors' => [
                    'min_length' =>
                    'Password must contain at least 10 characters.',
                    'regex_match' =>
                    'Use uppercase, lowercase, number and special character.',
                ],
            ],

            'password_confirmation' => [
                'label' => 'Confirm password',
                'rules' => [
                    'required',
                    'matches[password]',
                ],
                'errors' => [
                    'matches' =>
                    'The passwords do not match.',
                ],
            ],
        ];
    }
}
