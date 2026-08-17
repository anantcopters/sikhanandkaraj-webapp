<?php

declare(strict_types=1);

namespace App\Validation\Admin;

/**
 * Server-side administrator support-queue validation.
 */
final class MemberSupportValidation
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function reportRules(): array
    {
        return [
            'status' => [
                'label' =>
                'Report status',

                'rules' => [
                    'required',
                    'in_list[REVIEWED,DISMISSED,ACTION_TAKEN]',
                ],

                'errors' => [
                    'required' =>
                    'Please select the report status.',

                    'in_list' =>
                    'The selected report status is invalid.',
                ],
            ],

            'resolution_note' => [
                'label' =>
                'Resolution note',

                'rules' => [
                    'required',
                    'min_length[5]',
                    'max_length[1000]',
                ],

                'errors' => [
                    'required' =>
                    'Please enter a resolution note.',

                    'min_length' =>
                    'Resolution note must contain '
                        . 'at least 5 characters.',

                    'max_length' =>
                    'Resolution note cannot exceed '
                        . '1000 characters.',
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
            'status' => [
                'label' =>
                'Request status',

                'rules' => [
                    'required',
                    'in_list[IN_PROGRESS,RESOLVED,CLOSED]',
                ],

                'errors' => [
                    'required' =>
                    'Please select the request status.',

                    'in_list' =>
                    'The selected request status is invalid.',
                ],
            ],

            'response_note' => [
                'label' =>
                'Response note',

                'rules' => [
                    'required',
                    'min_length[5]',
                    'max_length[2000]',
                ],

                'errors' => [
                    'required' =>
                    'Please enter a response note.',

                    'min_length' =>
                    'Response note must contain '
                        . 'at least 5 characters.',

                    'max_length' =>
                    'Response note cannot exceed '
                        . '2000 characters.',
                ],
            ],
        ];
    }

    private function __construct() {}
}
