<?php

declare(strict_types=1);

namespace App\Validation\Member;

final class VideoIntroductionValidation
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function submissionRules(
        int $maximumUploadSizeKb
    ): array {
        return [
            'privacy_consent' => [
                'label' => 'privacy consent',
                'rules' => [
                    'required',
                    'in_list[1]',
                ],
                'errors' => [
                    'required' =>
                    'You must accept the Video Introduction guidelines.',

                    'in_list' =>
                    'You must accept the Video Introduction guidelines.',
                ],
            ],

            'video_introduction' => [
                'label' => 'Video Introduction',
                'rules' => [
                    'uploaded[video_introduction]',
                    'max_size[video_introduction,'
                        . max(1024, $maximumUploadSizeKb)
                        . ']',
                    'mime_in[video_introduction,'
                        . 'video/webm,video/mp4,video/quicktime]',
                ],
                'errors' => [
                    'uploaded' =>
                    'Please record your Video Introduction.',

                    'max_size' =>
                    'The recorded video exceeds the allowed file size.',

                    'mime_in' =>
                    'This browser video format is not supported.',
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
            'video_visibility' => [
                'label' => 'video visibility',
                'rules' => [
                    'required',
                    'in_list['
                        . 'VISIBLE_PRO,'
                        . 'VISIBLE_AFTER_ACCEPTED_INTEREST,'
                        . 'HIDDEN'
                        . ']',
                ],
                'errors' => [
                    'required' =>
                    'Please select who can view the video.',

                    'in_list' =>
                    'Please select a valid video visibility.',
                ],
            ],
        ];
    }
}
