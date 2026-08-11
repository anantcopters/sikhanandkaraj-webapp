<?php

declare(strict_types=1);

namespace App\Validation\Admin;

/**
 * Validation rules for member block and unblock actions.
 */
final class MemberAccountStatusValidation
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function reasonRules(): array
    {
        return [
            'reason' => [
                'label' => 'Reason',
                'rules' => [
                    'required',
                    'max_length[64]',
                ],
                'errors' => [
                    'required' =>
                    'Please enter the reason.',
                    'max_length' =>
                    'The reason cannot exceed 64 characters.',
                ],
            ],
        ];
    }

    private function __construct() {}
}
