<?php

declare(strict_types=1);

namespace App\Validation\Profile;

final class AboutMeValidation
{
    public const MAX_WORDS = 120;

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function rules(): array
    {
        return [
            'about_me' => [
                'label' => 'About Me',
                'rules' => [
                    'required',

                    /*
                     * Retain a defensive character ceiling.
                     *
                     * The authoritative 120-word check is performed in the
                     * controller/service flow because CI4 max_length is a
                     * character rule, not a word-count rule.
                     */
                    'max_length[5000]',
                ],
                'errors' => [
                    'required' =>
                    'Please write a short introduction about yourself.',

                    'max_length' =>
                    'About Me is too long.',
                ],
            ],
        ];
    }
}
