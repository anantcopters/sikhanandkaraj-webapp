<?php

declare(strict_types=1);

namespace App\Validation;

/**
 * Provides common password creation rules.
 *
 * All registration, activation, reset-password and change-password
 * workflows must reuse these rules.
 */
final class PasswordValidation
{
    /**
     * Return rules for creating a password.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function passwordRules(
        bool $includeConfirmation = true
    ): array {
        $rules = [
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
                    'required' =>
                    'Please enter a password.',

                    'min_length' =>
                    'Password must contain at least 10 characters.',

                    'max_length' =>
                    'Password cannot exceed 128 characters.',

                    'regex_match' =>
                    'Use uppercase, lowercase, number and special character.',
                ],
            ],
        ];

        if ($includeConfirmation) {
            $rules['password_confirmation'] = [
                'label' => 'Confirm password',

                'rules' => [
                    'required',
                    'matches[password]',
                ],

                'errors' => [
                    'required' =>
                    'Please confirm the password.',

                    'matches' =>
                    'The passwords do not match.',
                ],
            ];
        }

        return $rules;
    }

    private function __construct() {}
}
