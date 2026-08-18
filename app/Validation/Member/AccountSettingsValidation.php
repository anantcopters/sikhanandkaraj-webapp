<?php

declare(strict_types=1);

namespace App\Validation\Member;

use App\Validation\PasswordValidation;

/**
 * Server-side Account Settings rules.
 */
final class AccountSettingsValidation
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function changePasswordRules(): array
    {
        return array_merge(
            [
                'current_password' => [
                    'label' =>
                    'Current password',

                    'rules' => [
                        'required',
                        'max_length[128]',
                    ],

                    'errors' => [
                        'required' =>
                        'Please enter your current password.',

                        'max_length' =>
                        'The current password is invalid.',
                    ],
                ],
            ],
            PasswordValidation::passwordRules(
                true
            )
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function emailRules(): array
    {
        return [
            'email_address' => [
                'label' =>
                'Email address',

                'rules' => [
                    'required',
                    'valid_email',
                    'max_length[254]',
                ],

                'errors' => [
                    'required' =>
                    'Please enter an email address.',

                    'valid_email' =>
                    'Please enter a valid email address.',

                    'max_length' =>
                    'Email address cannot exceed 254 characters.',
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function visibilityRules(): array
    {
        return [
            'profile_visibility' => [
                'label' =>
                'Profile visibility',

                'rules' => [
                    'required',
                    'in_list[ALL_MEMBERS,PAID_MEMBERS_ONLY]',
                ],

                'errors' => [
                    'required' =>
                    'Please select who can view your profile.',

                    'in_list' =>
                    'The selected profile visibility is invalid.',
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function contactRules(): array
    {
        return [
            'message' => [
                'label' =>
                'Message',

                'rules' => [
                    'required',
                    'min_length[10]',
                    'max_length[255]',
                ],

                'errors' => [
                    'required' =>
                    'Please enter your message.',

                    'min_length' =>
                    'Please enter at least 10 characters.',

                    'max_length' =>
                    'Message cannot exceed 255 characters.',
                ],
            ],

            'captcha_answer' => [
                'label' =>
                'Security answer',

                'rules' => [
                    'required',
                    'regex_match[/^[0-9]{1,2}$/]',
                ],

                'errors' => [
                    'required' =>
                    'Please enter the security answer.',

                    'regex_match' =>
                    'Please enter a valid security answer.',
                ],
            ],
        ];
    }

    private function __construct() {}
}
