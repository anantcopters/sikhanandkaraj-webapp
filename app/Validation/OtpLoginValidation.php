<?php

declare(strict_types=1);

namespace App\Validation;

/**
 * Validation rules for passwordless mobile OTP login.
 */
final class OtpLoginValidation
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function mobileRules(): array
    {
        return [
            'mobile_number' => [
                'label' => 'Mobile number',
                'rules' => [
                    'required',
                    'regex_match[/^[6-9][0-9]{9}$/]',
                ],
                'errors' => [
                    'required' =>
                    'Please enter your registered mobile number.',

                    'regex_match' =>
                    'Please enter a valid 10-digit Indian mobile number.',
                ],
            ],
        ];
    }
}
