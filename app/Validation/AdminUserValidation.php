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
     * Return password rules for administrator invitation acceptance.
     *
     * Administrator password creation requires password confirmation.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function passwordRules(): array
    {
        return PasswordValidation::passwordRules(
            includeConfirmation: true
        );
    }
}
