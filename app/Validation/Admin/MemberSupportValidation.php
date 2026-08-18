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
                    'in_list[RESOLVED]',
                ],

                'errors' => [
                    'required' =>
                    'Please select the request status.',

                    'in_list' =>
                    'The request can only be marked as resolved.',
                ],
            ],

            'response_note' => [
                'label' =>
                'Resolution message',

                'rules' => [
                    'required',
                    'min_length[5]',
                    'max_length[255]',
                ],

                'errors' => [
                    'required' =>
                    'Please enter the resolution message.',

                    'min_length' =>
                    'Resolution message must contain '
                        . 'at least 5 characters.',

                    'max_length' =>
                    'Resolution message cannot exceed '
                        . '255 characters.',
                ],
            ],
        ];
    }

    private function __construct() {}
}
