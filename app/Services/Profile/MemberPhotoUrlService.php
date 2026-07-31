<?php

declare(strict_types=1);

namespace App\Services\Profile;

use App\Models\MemberPhotoModel;
use App\Services\Aws\CloudFrontService;
use Config\MemberMedia;
use Throwable;

/**
 * Provides read-only access to member profile photo URLs.
 */
final class MemberPhotoUrlService
{
    public function __construct(
        private readonly MemberPhotoModel $photoModel,
        private readonly CloudFrontService $cloudFrontService,
        private readonly MemberMedia $config
    ) {}

    /**
     * Return the approved primary profile photo URL.
     */
    public function getApprovedPrimaryUrl(
        int $memberId,
        string $variant = 'medium'
    ): string {
        $photo = $this->photoModel
            ->findApprovedPrimaryForMember($memberId);

        if (!is_array($photo)) {
            return '';
        }

        $column = match ($variant) {
            'original' => 'original_object_key',
            'thumbnail' => 'thumbnail_object_key',
            default => 'medium_object_key',
        };

        $objectKey = trim(
            (string) ($photo[$column] ?? '')
        );

        if ($objectKey === '') {
            return '';
        }

        try {
            return $this->cloudFrontService->signedUrl(
                $objectKey,
                $this->config->profileUrlTtlSeconds
            );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Primary photo URL generation failed for '
                    . 'member {memberId}: {message}',
                [
                    'memberId' => $memberId,
                    'message' => $exception->getMessage(),
                ]
            );

            return '';
        }
    }
}
