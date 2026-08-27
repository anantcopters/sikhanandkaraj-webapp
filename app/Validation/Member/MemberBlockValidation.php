<?php

declare(strict_types=1);

namespace App\Validation\Member;

/**
 * Validation rules for member-to-member blocking.
 */
final class MemberBlockValidation
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function rules(): array
    {
        return [
            'comment' => [
                'label' =>
                'Comment',

                'rules' => [
                    'required',
                    'max_length[250]',
                ],

                'errors' => [
                    'required' =>
                    'Please enter a comment.',

                    'max_length' =>
                    'The comment cannot exceed 250 characters.',
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
