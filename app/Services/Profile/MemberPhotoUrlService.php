<?php

declare(strict_types=1);

namespace App\Services\Profile;

use App\Models\MemberPhotoModel;
use App\Services\Aws\CloudFrontService;
use App\Support\BooleanValue;
use Config\MemberMedia;
use DomainException;
use Throwable;

/**
 * Provides authorized read-only profile-photo URLs.
 *
 * Media variant rules:
 *
 * - medium: main profile image;
 * - thumbnail: profile gallery and multi-photo listings;
 * - original: generated only after an explicit authorized request.
 */
final class MemberPhotoUrlService
{
    public function __construct(
        private readonly MemberPhotoModel $photoModel,
        private readonly CloudFrontService $cloudFrontService,
        private readonly MemberMedia $config
    ) {}

    /**
     * Return the approved primary profile-photo URL.
     *
     * The profile summary requests the medium variant.
     */
    public function getApprovedPrimaryUrl(
        int $memberId,
        string $variant = 'medium'
    ): string {
        if ($memberId <= 0) {
            return '';
        }

        $photo = $this->photoModel
            ->findApprovedPrimaryForMember(
                $memberId
            );

        if (!is_array($photo)) {
            return '';
        }

        $column = match (strtolower(
            trim($variant)
        )) {
            'original' =>
            'original_object_key',

            'thumbnail' =>
            'thumbnail_object_key',

            default =>
            'medium_object_key',
        };

        $objectKey = trim(
            (string) (
                $photo[$column]
                ?? ''
            )
        );

        if ($objectKey === '') {
            return '';
        }

        return $this->createSignedUrl(
            objectKey: $objectKey,
            context: 'Primary profile photo',
            memberId: $memberId,
            photoId: (int) (
                $photo['id']
                ?? 0
            )
        );
    }

    /**
     * Return approved photos with thumbnail URLs only.
     *
     * Original and medium gallery URLs are not generated during the
     * initial page request.
     *
     * @return list<array{
     *     id:int,
     *     thumbnailUrl:string,
     *     isPrimary:bool
     * }>
     */
    public function getApprovedThumbnailPhotos(
        int $memberId
    ): array {
        if ($memberId <= 0) {
            return [];
        }

        $photos = $this->photoModel
            ->findApprovedForMember(
                $memberId
            );

        $result = [];

        foreach ($photos as $photo) {
            if (!is_array($photo)) {
                continue;
            }

            $photoId = (int) (
                $photo['id']
                ?? 0
            );

            $objectKey = trim(
                (string) (
                    $photo['thumbnail_object_key']
                    ?? ''
                )
            );

            if (
                $photoId <= 0
                || $objectKey === ''
            ) {
                log_message(
                    'error',
                    'Approved thumbnail is unavailable. '
                        . 'Member: {memberId}; '
                        . 'photo: {photoId}.',
                    [
                        'memberId' => $memberId,
                        'photoId' => $photoId,
                    ]
                );

                continue;
            }

            $thumbnailUrl = $this->createSignedUrl(
                objectKey: $objectKey,
                context: 'Profile gallery thumbnail',
                memberId: $memberId,
                photoId: $photoId
            );

            if ($thumbnailUrl === '') {
                continue;
            }

            $result[] = [
                'id' => $photoId,

                'thumbnailUrl' =>
                $thumbnailUrl,

                'isPrimary' =>
                BooleanValue::fromDatabase(
                    $photo['is_primary']
                        ?? false
                ),
            ];
        }

        return $result;
    }

    /**
     * Return original and medium URLs for one approved photo.
     *
     * The original URL is requested only when the member explicitly opens
     * a photo in the modal. Medium is returned as an authorized fallback.
     *
     * @return array{
     *     photoId:int,
     *     originalUrl:string,
     *     mediumUrl:string
     * }
     *
     * @throws DomainException
     */
    public function getOwnedApprovedModalUrls(
        int $memberId,
        int $photoId
    ): array {
        if (
            $memberId <= 0
            || $photoId <= 0
        ) {
            throw new DomainException(
                'The requested photo is invalid.'
            );
        }

        $photo = $this->photoModel
            ->findOwnedApprovedPhoto(
                $photoId,
                $memberId
            );

        if (!is_array($photo)) {
            /*
             * Return the same response for an unknown, foreign, pending,
             * rejected or deleted photo.
             */
            throw new DomainException(
                'The requested photo is unavailable.'
            );
        }

        $originalObjectKey = trim(
            (string) (
                $photo['original_object_key']
                ?? ''
            )
        );

        $mediumObjectKey = trim(
            (string) (
                $photo['medium_object_key']
                ?? ''
            )
        );

        if ($originalObjectKey === '') {
            throw new DomainException(
                'The original photo is unavailable.'
            );
        }

        $originalUrl = $this->createSignedUrl(
            objectKey: $originalObjectKey,
            context: 'Original profile photo',
            memberId: $memberId,
            photoId: $photoId
        );

        if ($originalUrl === '') {
            throw new DomainException(
                'The original photo is unavailable.'
            );
        }

        $mediumUrl = '';

        if ($mediumObjectKey !== '') {
            $mediumUrl = $this->createSignedUrl(
                objectKey: $mediumObjectKey,
                context: 'Medium profile photo fallback',
                memberId: $memberId,
                photoId: $photoId
            );
        }

        return [
            'photoId' => $photoId,
            'originalUrl' => $originalUrl,
            'mediumUrl' => $mediumUrl,
        ];
    }

    /**
     * Generate a short-lived CloudFront signed URL.
     */
    private function createSignedUrl(
        string $objectKey,
        string $context,
        int $memberId,
        int $photoId
    ): string {
        try {
            return $this->cloudFrontService
                ->signedUrl(
                    $objectKey,
                    $this->config
                        ->profileUrlTtlSeconds
                );
        } catch (Throwable $exception) {
            log_message(
                'error',
                '{context} URL generation failed. '
                    . 'Member: {memberId}; '
                    . 'photo: {photoId}; '
                    . 'reason: {message}',
                [
                    'context' => $context,
                    'memberId' => $memberId,
                    'photoId' => $photoId,
                    'message' =>
                    $exception->getMessage(),
                ]
            );

            return '';
        }
    }
}
