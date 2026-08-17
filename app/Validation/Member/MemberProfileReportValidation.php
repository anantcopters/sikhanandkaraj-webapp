<?php

declare(strict_types=1);

namespace App\Validation\Member;

/**
 * Server-side member profile-report rules.
 */
final class MemberProfileReportValidation
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function rules(): array
    {
        return [
            'description' => [
                'label' =>
                'Report description',

                'rules' => [
                    'required',
                    'min_length[10]',
                    'max_length[1000]',
                ],

                'errors' => [
                    'required' =>
                    'Please explain why you are reporting this profile.',

                    'min_length' =>
                    'Please enter at least 10 characters.',

                    'max_length' =>
                    'Report description cannot exceed 1000 characters.',
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
