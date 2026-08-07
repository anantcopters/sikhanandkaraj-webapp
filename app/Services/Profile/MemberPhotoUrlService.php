<?php

declare(strict_types=1);

namespace App\Services\Profile;

use App\Models\MemberPhotoModel;
use App\Services\Aws\CloudFrontService;
use App\Support\ProfileErrorContext;
use App\Support\BooleanValue;
use Config\MemberMedia;
use DomainException;
use Throwable;

/**
 * Provides authorized, short-lived member-photo URLs.
 *
 * Member-facing methods expose approved photos only.
 * Administrator methods may expose retained approved, pending and rejected
 * photos after administrator authorization has already been applied.
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

        $column = match (mb_strtolower(
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
     * Return approved photographs with thumbnail URLs.
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
                continue;
            }

            $thumbnailUrl = $this->createSignedUrl(
                objectKey: $objectKey,
                context: 'Approved profile thumbnail',
                memberId: $memberId,
                photoId: $photoId
            );

            if ($thumbnailUrl === '') {
                continue;
            }

            $result[] = [
                'id' =>
                $photoId,

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
     * Return every retained administrator-visible member photograph.
     *
     * Deleted rows and rows marked DELETED are excluded by
     * MemberPhotoModel::findActiveForMember().
     *
     * @return list<array{
     *     id:int,
     *     status:string,
     *     thumbnailUrl:string,
     *     isPrimary:bool,
     *     visibility:string,
     *     rejectionReason:string,
     *     createdAt:string
     * }>
     */
    public function getAdminThumbnailPhotos(
        int $memberId
    ): array {
        if ($memberId <= 0) {
            return [];
        }

        $photos = $this->photoModel
            ->findActiveForMember(
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

            $status = mb_strtoupper(
                trim(
                    (string) (
                        $photo['status']
                        ?? ''
                    )
                )
            );

            if (
                $photoId <= 0
                || !in_array(
                    $status,
                    [
                        MemberPhotoModel::STATUS_PENDING,
                        MemberPhotoModel::STATUS_APPROVED,
                        MemberPhotoModel::STATUS_REJECTED,
                    ],
                    true
                )
            ) {
                continue;
            }

            /*
             * Prefer the thumbnail. Use medium only when an older retained
             * record does not contain a thumbnail object key.
             */
            $objectKey = trim(
                (string) (
                    $photo['thumbnail_object_key']
                    ?? ''
                )
            );

            if ($objectKey === '') {
                $objectKey = trim(
                    (string) (
                        $photo['medium_object_key']
                        ?? ''
                    )
                );
            }

            if ($objectKey === '') {
                continue;
            }

            $thumbnailUrl = $this->createSignedUrl(
                objectKey: $objectKey,
                context: 'Administrator profile thumbnail',
                memberId: $memberId,
                photoId: $photoId
            );

            if ($thumbnailUrl === '') {
                continue;
            }

            $result[] = [
                'id' =>
                $photoId,

                'status' =>
                $status,

                'thumbnailUrl' =>
                $thumbnailUrl,

                'isPrimary' =>
                BooleanValue::fromDatabase(
                    $photo['is_primary']
                        ?? false
                ),

                'visibility' =>
                trim(
                    (string) (
                        $photo['visibility']
                        ?? ''
                    )
                ),

                'rejectionReason' =>
                trim(
                    (string) (
                        $photo['rejection_reason']
                        ?? ''
                    )
                ),

                'createdAt' =>
                trim(
                    (string) (
                        $photo['created_at']
                        ?? ''
                    )
                ),
            ];
        }

        return $result;
    }

    /**
     * Return original and medium URLs for one approved member-owned photo.
     *
     * @return array{
     *     photoId:int,
     *     originalUrl:string,
     *     mediumUrl:string
     * }
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
            throw new DomainException(
                'The requested photo is unavailable.'
            );
        }

        return $this->createModalUrls(
            $photo,
            $memberId,
            $photoId,
            'Approved member photo'
        );
    }

    /**
     * Return original and medium URLs for one retained Admin-visible photo.
     *
     * This permits approved, pending and rejected photographs, but still
     * requires the photo to belong to the supplied member and remain active.
     *
     * @return array{
     *     photoId:int,
     *     originalUrl:string,
     *     mediumUrl:string
     * }
     */
    public function getAdminModalUrls(
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
            ->findOwnedActivePhoto(
                $photoId,
                $memberId
            );

        if (!is_array($photo)) {
            throw new DomainException(
                'The requested photo is unavailable.'
            );
        }

        $status = mb_strtoupper(
            trim(
                (string) (
                    $photo['status']
                    ?? ''
                )
            )
        );

        if (
            !in_array(
                $status,
                [
                    MemberPhotoModel::STATUS_PENDING,
                    MemberPhotoModel::STATUS_APPROVED,
                    MemberPhotoModel::STATUS_REJECTED,
                ],
                true
            )
        ) {
            throw new DomainException(
                'The requested photo is unavailable.'
            );
        }

        return $this->createModalUrls(
            $photo,
            $memberId,
            $photoId,
            'Administrator member photo'
        );
    }

    /**
     * Generate original and medium signed URLs.
     *
     * @param array<string, mixed> $photo
     *
     * @return array{
     *     photoId:int,
     *     originalUrl:string,
     *     mediumUrl:string
     * }
     */
    private function createModalUrls(
        array $photo,
        int $memberId,
        int $photoId,
        string $context
    ): array {
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
            context: $context . ' original',
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
                context: $context . ' medium',
                memberId: $memberId,
                photoId: $photoId
            );
        }

        return [
            'photoId' =>
            $photoId,

            'originalUrl' =>
            $originalUrl,

            'mediumUrl' =>
            $mediumUrl,
        ];
    }

    /**
     * Generate one short-lived signed CloudFront URL.
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
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'warning',
                ProfileErrorContext::forMember(
                    memberId: $memberId,

                    operation: 'member_photo_signed_url_generation',

                    component: self::class,

                    method: __FUNCTION__,

                    additionalContext: [
                        'photo_id' =>
                        $photoId,

                        'photo_context' =>
                        mb_substr(
                            trim($context),
                            0,
                            100
                        ),

                        /*
                 * Never store the actual private S3 object key.
                 */
                        'object_key_hash' =>
                        hash(
                            'sha256',
                            $objectKey
                        ),
                    ]
                )
            );

            return '';
        }
    }

    /**
     * Return an approved primary photo URL for another authenticated member.
     *
     * This method is deliberately separate from getApprovedPrimaryUrl().
     *
     * getApprovedPrimaryUrl():
     *     Owner/internal profile-summary context.
     *
     * getApprovedPrimaryUrlForViewer():
     *     Another-member context where photo visibility must be enforced.
     *
     * PUBLIC photos may be viewed by any otherwise-authorized member.
     *
     * INTERESTED_MEMBERS photos may be viewed only when the calling service
     * has confirmed that an interest relationship exists in either direction.
     *
     * Original images are deliberately unavailable from this method.
     * Member listings use thumbnail and profile-detail pages use medium.
     */
    public function getApprovedPrimaryUrlForViewer(
        int $memberId,
        int $viewerUserId,
        bool $hasInterestRelationship,
        string $variant = 'medium'
    ): string {
        if (
            $memberId <= 0
            || $viewerUserId <= 0
            || $memberId === $viewerUserId
        ) {
            return '';
        }

        $photo = $this->photoModel
            ->findApprovedPrimaryForMember(
                $memberId
            );

        if (!is_array($photo)) {
            return '';
        }

        $visibility = mb_strtoupper(
            trim(
                (string) (
                    $photo['visibility']
                    ?? ''
                )
            )
        );

        /*
     * Unknown visibility values fail closed.
     *
     * Do not assume an invalid/missing value means PUBLIC.
     */
        if (
            !in_array(
                $visibility,
                [
                    'PUBLIC',
                    'INTERESTED_MEMBERS',
                ],
                true
            )
        ) {
            return '';
        }

        /*
     * PRIVATE-TO-INTEREST visibility requires a confirmed relationship.
     *
     * Interest in either direction is sufficient:
     *
     * viewer -> member
     * OR
     * member -> viewer
     */
        if (
            $visibility === 'INTERESTED_MEMBERS'
            && !$hasInterestRelationship
        ) {
            return '';
        }

        /*
     * Another-member pages are deliberately limited to:
     *
     * thumbnail -> dashboard/search/matches
     * medium    -> member profile page
     *
     * Original must go through a separate explicit authorization flow.
     */
        $normalizedVariant = mb_strtolower(
            trim($variant)
        );

        $column = match ($normalizedVariant) {
            'thumbnail' =>
            'thumbnail_object_key',

            'medium' =>
            'medium_object_key',

            default =>
            null,
        };

        if ($column === null) {
            return '';
        }

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

            context: 'Viewer-authorized primary profile photo',

            memberId: $memberId,

            photoId: (int) (
                $photo['id']
                ?? 0
            )
        );
    }
}
