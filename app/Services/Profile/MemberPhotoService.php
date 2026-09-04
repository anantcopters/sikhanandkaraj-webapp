<?php

declare(strict_types=1);

namespace App\Services\Profile;

use App\Support\BooleanValue;
use App\Models\MemberPhotoModel;
use App\Models\UserModel;
use App\Services\Aws\AwsMediaService;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\Files\UploadedFile;
use Config\MemberMedia;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * Implements member-owned photo workflows.
 */
final class MemberPhotoService
{
    public function __construct(
        private readonly UserModel $userModel,
        private readonly MemberPhotoModel $photoModel,
        private readonly AwsMediaService $awsMediaService,
        private readonly BaseConnection $database,
        private readonly MemberMedia $config
    ) {}

    /**
     * Return member photos with short-lived signed thumbnail URLs.
     *
     * If active photos exist but none is marked as primary, the earliest
     * uploaded active photo is automatically selected as the main photo.
     *
     * @return array{
     *     user:array<string, mixed>,
     *     photos:list<array<string, mixed>>,
     *     count:int,
     *     approvedCount:int,
     *     maximum:int,
     *     remaining:int
     * }
     */
    public function getForMember(int $memberId): array
    {
        $user = $this->userModel->find($memberId);

        if (!is_array($user)) {
            throw new DomainException(
                'Member account was not found.'
            );
        }

        /*
     * Always load the complete active-photo collection first.
     */
        $photos = $this
            ->photoModel
            ->findActiveForMember($memberId);

        /*
     * Repair legacy or manually modified data where active photos
     * exist but none is marked as the main profile photo.
     */
        if ($photos !== []) {
            $primaryPhoto = $this
                ->photoModel
                ->findPrimaryForMember($memberId);

            if ($primaryPhoto === null) {
                $firstPhoto = $this
                    ->photoModel
                    ->findFirstActiveForMember($memberId);

                if ($firstPhoto !== null) {
                    $this->setPrimary(
                        $memberId,
                        (int) $firstPhoto['id']
                    );

                    /*
                 * Reload so the returned collection contains the
                 * updated is_primary value and primary-first ordering.
                 */
                    $photos = $this
                        ->photoModel
                        ->findActiveForMember($memberId);
                }
            }
        }

        foreach ($photos as &$photo) {
            /*
         * Defensive protection against an unexpected repository result.
         */
            if (!is_array($photo)) {
                continue;
            }

            /*
            * Normalize PostgreSQL boolean representations before the data
            * reaches controllers and views.
            */
            $photo['is_primary'] = BooleanValue::fromDatabase(
                $photo['is_primary'] ?? false
            );

            try {
                $photo['signedUrls'] = $this
                    ->awsMediaService
                    ->profilePhotoUrls($photo);
            } catch (Throwable $exception) {
                /*
             * One unavailable image must not prevent the complete
             * Manage Photos page from loading.
             */
                $photo['signedUrls'] = [
                    'originalUrl' => '',
                    'mediumUrl' => '',
                    'thumbnailUrl' => '',
                ];

                log_message(
                    'error',
                    'Photo URL generation failed for '
                        . 'photo {photoId}: {message}',
                    [
                        'photoId' => (int) (
                            $photo['id'] ?? 0
                        ),
                        'message' =>
                        $exception->getMessage(),
                    ]
                );
            }
        }

        unset($photo);

        $count = count($photos);

        $approvedCount = $this
            ->photoModel
            ->countApprovedForMember($memberId);

        return [
            'user' => $user,
            'photos' => $photos,
            'count' => $count,
            'approvedCount' => $approvedCount,
            'maximum' => $this->config->profileMaxFiles,
            'remaining' => max(
                0,
                $this->config->profileMaxFiles - $count
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function upload(
        int $memberId,
        UploadedFile $uploadedFile,
        string $visibility,
        bool $makePrimary,
        int $focalX,
        int $focalY
    ): array {
        $this->assertMemberExists($memberId);

        $this->assertVisibility($visibility);

        $this->assertFocalPosition(
            $focalX,
            $focalY
        );

        /*
         * Fast pre-check. The transaction performs a second check.
         */
        if (
            $this->photoModel->countActiveForMember(
                $memberId
            ) >= $this->config->profileMaxFiles
        ) {
            throw new DomainException(
                'You can upload a maximum of five photos. '
                    . 'Delete one photo before uploading another.'
            );
        }

        $media = $this
            ->awsMediaService
            ->uploadProfilePhoto(
                $uploadedFile,
                $memberId
            );

        $objectKeys = [
            $media['originalObjectKey'],
            $media['mediumObjectKey'],
            $media['thumbnailObjectKey'],
        ];

        $this->database->transBegin();

        try {
            /*
             * Serialize uploads for the same member and prevent two
             * concurrent requests from exceeding the five-photo limit.
             */
            $this->database
                ->query(
                    'SELECT id FROM users '
                        . 'WHERE id = ? FOR UPDATE',
                    [$memberId]
                );

            $currentCount = $this
                ->photoModel
                ->countActiveForMember($memberId);

            if (
                $currentCount
                >= $this->config->profileMaxFiles
            ) {
                throw new DomainException(
                    'You can upload a maximum of five photos.'
                );
            }

            /*
             * The first photo automatically becomes the selected main
             * photo unless the member explicitly selects another later.
             */
            $isPrimary = $makePrimary
                || $currentCount === 0;

            if ($isPrimary) {
                $this->photoModel->clearPrimaryForMember(
                    $memberId
                );
            }

            $photoId = $this->photoModel->insert([
                'uuid' => $media['uuid'],
                'member_id' => $memberId,
                'media_type' => 'PROFILE_PHOTO',
                'original_object_key' =>
                $media['originalObjectKey'],
                'medium_object_key' =>
                $media['mediumObjectKey'],
                'thumbnail_object_key' =>
                $media['thumbnailObjectKey'],
                'original_filename' =>
                $media['originalFilename'],
                'original_mime_type' =>
                $media['mimeType'],
                'original_extension' =>
                $media['extension'],
                'original_file_size' =>
                $media['fileSize'],
                'original_width' => $media['width'],
                'original_height' => $media['height'],
                'status' => 'PENDING',
                'visibility' => $visibility,
                'is_primary' => $isPrimary,
                'focal_x' => $focalX,
                'focal_y' => $focalY,
                'uploaded_by_type' => 'MEMBER',
                'uploaded_by_id' => $memberId,
            ], true);

            if ($photoId === false) {
                throw new RuntimeException(
                    'The photo record could not be created.'
                );
            }

            if (
                $this->database->transStatus() === false
            ) {
                throw new RuntimeException(
                    'The photo transaction failed.'
                );
            }

            $this->database->transCommit();

            $photo = $this->photoModel->find(
                (int) $photoId
            );

            if (!is_array($photo)) {
                throw new RuntimeException(
                    'The uploaded photo could not be read.'
                );
            }

            log_message(
                'info',
                'Member photo uploaded successfully. '
                    . 'Member: {memberId}; photo: {photoId}; '
                    . 'status: PENDING.',
                [
                    'memberId' => $memberId,
                    'photoId' => (int) $photoId,
                ]
            );

            return $photo;
        } catch (Throwable $exception) {
            if (
                $this->database->transStatus() !== false
            ) {
                $this->database->transRollback();
            }

            $this->awsMediaService->deleteObjectKeys(
                $objectKeys
            );

            log_message(
                'error',
                'Member photo upload failed after S3 upload. '
                    . 'Member: {memberId}; reason: {message}',
                [
                    'memberId' => $memberId,
                    'message' => $exception->getMessage(),
                ]
            );

            throw $exception;
        }
    }

    public function setPrimary(
        int $memberId,
        int $photoId
    ): void {
        $photo = $this->requireOwnedPhoto(
            $photoId,
            $memberId
        );

        $this->database->transBegin();

        try {
            $this->photoModel->clearPrimaryForMember(
                $memberId,
                (int) $photo['id']
            );

            if (!$this->photoModel->update(
                (int) $photo['id'],
                [
                    'is_primary' => true,
                ]
            )) {
                throw new RuntimeException(
                    'The main photo could not be updated.'
                );
            }

            if (
                $this->database->transStatus() === false
            ) {
                throw new RuntimeException(
                    'The main photo transaction failed.'
                );
            }

            $this->database->transCommit();
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }
    }

    public function updateVisibility(
        int $memberId,
        int $photoId,
        string $visibility
    ): void {
        $this->assertVisibility($visibility);

        $photo = $this->requireOwnedPhoto(
            $photoId,
            $memberId
        );

        if (!$this->photoModel->update(
            (int) $photo['id'],
            [
                'visibility' => $visibility,
            ]
        )) {
            throw new RuntimeException(
                'Photo visibility could not be updated.'
            );
        }
    }

    public function delete(
        int $memberId,
        int $photoId
    ): void {
        $photo = $this->requireOwnedPhoto(
            $photoId,
            $memberId
        );

        /*
         * Mark deleted first so the photo is immediately unavailable
         * even if S3 cleanup encounters a temporary AWS error.
         */
        $this->database->transBegin();

        try {
            if (!$this->photoModel->update(
                (int) $photo['id'],
                [
                    'status' => 'DELETED',
                    'is_primary' => false,
                    'deleted_at' => date('Y-m-d H:i:s'),
                ]
            )) {
                throw new RuntimeException(
                    'The photo could not be deleted.'
                );
            }

            if (
                $this->database->transStatus() === false
            ) {
                throw new RuntimeException(
                    'The photo deletion transaction failed.'
                );
            }

            $this->database->transCommit();
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }

        $objectsDeleted = $this
            ->awsMediaService
            ->deleteProfilePhotoObjects($photo);

        if (!$objectsDeleted) {
            /*
             * The record remains deleted and inaccessible. A scheduled
             * cleanup process can retry orphan-object removal later.
             */
            log_message(
                'warning',
                'Member photo record {photoId} was deleted, '
                    . 'but S3 cleanup requires retry.',
                [
                    'photoId' => (int) $photo['id'],
                ]
            );
        }

        /*
         * When the selected primary photo is deleted, select the newest
         * remaining photo. It remains pending/approved according to its
         * existing moderation status.
         */
        $deletedPhotoWasPrimary =
            BooleanValue::fromDatabase(
                $photo['is_primary'] ?? false
            );

        if ($deletedPhotoWasPrimary) {
            $firstRemainingPhoto = $this
                ->photoModel
                ->findFirstActiveForMember(
                    $memberId
                );

            if ($firstRemainingPhoto !== null) {
                $this->setPrimary(
                    $memberId,
                    (int) $firstRemainingPhoto['id']
                );
            }
        }
    }

    /**
     * Used by profile/dashboard presentation.
     */
    public function getApprovedPrimaryUrl(
        int $memberId,
        string $variant = 'medium'
    ): string {
        $photo = $this
            ->photoModel
            ->findApprovedPrimaryForMember($memberId);

        if ($photo === null) {
            return '';
        }

        $urls = $this
            ->awsMediaService
            ->profilePhotoUrls($photo);

        return match ($variant) {
            'thumbnail' =>
            $urls['thumbnailUrl'],
            'original' =>
            $urls['originalUrl'],
            default =>
            $urls['mediumUrl'],
        };
    }

    /**
     * Return photo-count information for profile presentation.
     *
     * A pending photo counts toward profile-journey completion because
     * the member has completed the upload action. Public display still
     * requires an approved main photo.
     *
     * @return array{
     *     uploadedCount:int,
     *     approvedCount:int,
     *     hasUploadedPhoto:bool
     * }
     */
    public function getPhotoSummary(
        int $memberId
    ): array {
        $this->assertMemberExists($memberId);

        $uploadedCount = $this
            ->photoModel
            ->countActiveForMember($memberId);

        $approvedCount = $this
            ->photoModel
            ->countApprovedForMember($memberId);

        return [
            'uploadedCount' => $uploadedCount,
            'approvedCount' => $approvedCount,
            'hasUploadedPhoto' => $uploadedCount > 0,
        ];
    }

    /**
     * Update presentation position for one owned photo.
     *
     * This changes presentation metadata only. It deliberately does not
     * modify the photograph, moderation status or S3 objects.
     */
    public function updateFocalPosition(
        int $memberId,
        int $photoId,
        int $focalX,
        int $focalY
    ): void {
        $this->assertFocalPosition(
            $focalX,
            $focalY
        );

        $photo = $this->requireOwnedPhoto(
            $photoId,
            $memberId
        );

        if (!$this->photoModel->update(
            (int) $photo['id'],
            [
                'focal_x' => $focalX,
                'focal_y' => $focalY,
            ]
        )) {
            throw new RuntimeException(
                'Photo position could not be updated.'
            );
        }
    }

    private function assertFocalPosition(
        int $focalX,
        int $focalY
    ): void {
        if (
            $focalX < 0
            || $focalX > 100
            || $focalY < 0
            || $focalY > 100
        ) {
            throw new DomainException(
                'Please select a valid photo position.'
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function requireOwnedPhoto(
        int $photoId,
        int $memberId
    ): array {
        $photo = $this
            ->photoModel
            ->findOwnedActivePhoto(
                $photoId,
                $memberId
            );

        if ($photo === null) {
            /*
             * Deliberately avoid distinguishing a non-existent photo
             * from another member's photo.
             */
            throw new DomainException(
                'The selected photo was not found.'
            );
        }

        return $photo;
    }

    private function assertMemberExists(
        int $memberId
    ): void {
        if (!is_array($this->userModel->find($memberId))) {
            throw new DomainException(
                'Member account was not found.'
            );
        }
    }

    private function assertVisibility(
        string $visibility
    ): void {
        if (!in_array(
            $visibility,
            ['PUBLIC', 'INTERESTED_MEMBERS'],
            true
        )) {
            throw new DomainException(
                'Please select a valid photo visibility.'
            );
        }
    }
}
