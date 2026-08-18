<?php

declare(strict_types=1);

namespace App\Validation\Profile;

/**
 * Server-side rules for Aadhaar document upload and review.
 */
final class MemberAadhaarValidation
{
    public const MAXIMUM_SIZE_KB = 1024;

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function uploadRules(): array
    {
        return [
            'return_context' => [
                'label' =>
                'Return context',

                'rules' => [
                    'required',
                    'in_list[DASHBOARD,PROFILE_EDIT]',
                ],

                'errors' => [
                    'required' =>
                    'The Aadhaar upload context is required.',

                    'in_list' =>
                    'The Aadhaar upload context is invalid.',
                ],
            ],

            'aadhaar_document' => [
                'label' =>
                'Aadhaar document',

                'rules' => [
                    'uploaded[aadhaar_document]',
                    'mime_in[aadhaar_document,image/jpeg,image/png,application/pdf]',
                    'ext_in[aadhaar_document,jpg,jpeg,png,pdf]',
                    'max_size[aadhaar_document,'
                        . self::MAXIMUM_SIZE_KB
                        . ']',
                ],

                'errors' => [
                    'uploaded' =>
                    'Please select an Aadhaar document.',

                    'mime_in' =>
                    'Only JPG, JPEG, PNG or PDF files are allowed.',

                    'ext_in' =>
                    'Only JPG, JPEG, PNG or PDF files are allowed.',

                    'max_size' =>
                    'The Aadhaar document must be smaller than 1 MB.',
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function approvalRules(): array
    {
        return [
            'aadhaar_name' => [
                'label' => 'Aadhaar name',
                'rules' => [
                    'required',
                    'min_length[2]',
                    'max_length[100]',
                    'regex_match[/^[\p{L}\p{M} .\'-]+$/u]',
                ],
                'errors' => [
                    'required' => 'Please enter the name shown on Aadhaar.',
                    'min_length' => 'Aadhaar name must contain at least 2 characters.',
                    'max_length' => 'Aadhaar name cannot exceed 100 characters.',
                    'regex_match' => 'Aadhaar name contains unsupported characters.',
                ],
            ],
            'date_of_birth' => [
                'label' => 'Aadhaar date of birth',
                'rules' => [
                    'required',
                    'valid_date[Y-m-d]',
                    'minimum_age[18]',
                ],
                'errors' => [
                    'required' => 'Please select the Aadhaar date of birth.',
                    'valid_date' => 'Please select a valid Aadhaar date of birth.',
                    'minimum_age' => 'The member must be at least 18 years old.',
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function rejectionRules(): array
    {
        return [
            'rejection_reason' => [
                'label' => 'Rejection reason',
                'rules' => ['required', 'min_length[3]', 'max_length[500]'],
                'errors' => [
                    'required' => 'Please enter a rejection reason.',
                    'min_length' => 'Rejection reason must contain at least 3 characters.',
                    'max_length' => 'Rejection reason cannot exceed 500 characters.',
                ],
            ],
        ];
    }
}
