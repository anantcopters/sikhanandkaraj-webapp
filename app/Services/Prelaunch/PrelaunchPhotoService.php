<?php

declare(strict_types=1);

namespace App\Services\Prelaunch;

use App\Models\Prelaunch\PrelaunchPhotoModel;
use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Services;
use RuntimeException;
use Throwable;

/**
 * Stores pre-launch photographs in the CI4 writable directory.
 */
final class PrelaunchPhotoService
{
    private const MEDIUM_WIDTH = 900;
    private const MEDIUM_HEIGHT = 1200;
    private const THUMBNAIL_WIDTH = 300;
    private const THUMBNAIL_HEIGHT = 400;

    public function __construct(
        private readonly PrelaunchPhotoModel $photoModel
    ) {}

    /**
     * Store exactly three uploaded photographs.
     *
     * @param array<int, UploadedFile> $photos
     */
    public function storeProfilePhotos(
        int $profileId,
        string $profileReference,
        array $photos
    ): void {
        if (count($photos) !== 3) {
            throw new RuntimeException(
                'Exactly three photographs are required.'
            );
        }

        if (
            $this->photoModel->countByProfile($profileId)
            !== 0
        ) {
            throw new RuntimeException(
                'Photographs have already been uploaded for this profile.'
            );
        }

        $root = $this->profileDirectory(
            $profileReference
        );

        $this->prepareDirectories($root);

        $createdFiles = [];

        try {
            foreach ($photos as $index => $photo) {
                $sequence = $index + 1;

                $stored = $this->storeSinglePhoto(
                    $profileId,
                    $sequence,
                    $photo,
                    $root
                );

                $createdFiles = array_merge(
                    $createdFiles,
                    [
                        $stored['absolute_original'],
                        $stored['absolute_medium'],
                        $stored['absolute_thumbnail'],
                    ]
                );

                $this->photoModel->insert([
                    'prelaunch_profile_id' =>
                    $profileId,

                    'sequence_no' =>
                    $sequence,

                    'original_path' =>
                    $stored['relative_original'],

                    'medium_path' =>
                    $stored['relative_medium'],

                    'thumbnail_path' =>
                    $stored['relative_thumbnail'],

                    'original_filename' =>
                    $photo->getClientName(),

                    'mime_type' =>
                    $stored['mime_type'],

                    'file_extension' =>
                    $stored['extension'],

                    'file_size_bytes' =>
                    $stored['file_size'],

                    'width_px' =>
                    $stored['width'],

                    'height_px' =>
                    $stored['height'],

                    'checksum_sha256' =>
                    $stored['checksum'],

                    'approval_status' =>
                    PrelaunchPhotoModel::STATUS_PENDING,
                ]);
            }
        } catch (Throwable $exception) {
            foreach ($createdFiles as $path) {
                if (is_file($path)) {
                    @unlink($path);
                }
            }

            throw $exception;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function storeSinglePhoto(
        int $profileId,
        int $sequence,
        UploadedFile $photo,
        string $root
    ): array {
        if (!$photo->isValid()) {
            throw new RuntimeException(
                'One of the uploaded photographs is invalid.'
            );
        }

        $detectedMime = mime_content_type(
            $photo->getTempName()
        );

        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (
            $detectedMime === false
            || !isset($extensions[$detectedMime])
        ) {
            throw new RuntimeException(
                'Only JPG, PNG and WebP photographs are allowed.'
            );
        }

        $imageInfo = getimagesize(
            $photo->getTempName()
        );

        if ($imageInfo === false) {
            throw new RuntimeException(
                'The uploaded photograph could not be decoded.'
            );
        }

        [$width, $height] = $imageInfo;

        if ($width < 400 || $height < 400) {
            throw new RuntimeException(
                'Every photograph must be at least 400 × 400 pixels.'
            );
        }

        $extension = $extensions[$detectedMime];
        $randomName = sprintf(
            '%02d_%s.%s',
            $sequence,
            bin2hex(random_bytes(16)),
            $extension
        );

        $originalPath =
            $root . DIRECTORY_SEPARATOR
            . 'original' . DIRECTORY_SEPARATOR
            . $randomName;

        $mediumPath =
            $root . DIRECTORY_SEPARATOR
            . 'medium' . DIRECTORY_SEPARATOR
            . $randomName;

        $thumbnailPath =
            $root . DIRECTORY_SEPARATOR
            . 'thumbnail' . DIRECTORY_SEPARATOR
            . $randomName;

        if (!copy($photo->getTempName(), $originalPath)) {
            throw new RuntimeException(
                'The original photograph could not be stored.'
            );
        }

        /*
         * CI4 image service is reused. No new third-party image package
         * or separate image architecture is introduced.
         */
        $this->createVariant(
            $originalPath,
            $mediumPath,
            self::MEDIUM_WIDTH,
            self::MEDIUM_HEIGHT,
            true
        );

        $this->createVariant(
            $originalPath,
            $thumbnailPath,
            self::THUMBNAIL_WIDTH,
            self::THUMBNAIL_HEIGHT,
            true
        );

        return [
            'absolute_original' => $originalPath,
            'absolute_medium' => $mediumPath,
            'absolute_thumbnail' => $thumbnailPath,

            'relative_original' =>
            $this->relativePath($originalPath),

            'relative_medium' =>
            $this->relativePath($mediumPath),

            'relative_thumbnail' =>
            $this->relativePath($thumbnailPath),

            'mime_type' => $detectedMime,
            'extension' => $extension,
            'file_size' => filesize($originalPath) ?: 0,
            'width' => $width,
            'height' => $height,
            'checksum' => hash_file(
                'sha256',
                $originalPath
            ),
        ];
    }

    private function createVariant(
        string $sourcePath,
        string $destinationPath,
        int $width,
        int $height,
        bool $watermark
    ): void {
        $image = Services::image()
            ->withFile($sourcePath)
            ->fit(
                $width,
                $height,
                'center'
            );

        /*
         * Watermark is applied only to display variants.
         * The original is retained without modification.
         */
        if ($watermark) {
            $image->text(
                'SikhAnandKaraj.com',
                [
                    'color' => '#FFFFFF',
                    'opacity' => 0.28,
                    'withShadow' => true,
                    'hAlign' => 'center',
                    'vAlign' => 'bottom',
                    'vOffset' => 24,
                    'fontSize' => 22,
                ]
            );
        }

        if (!$image->save($destinationPath, 85)) {
            throw new RuntimeException(
                'A photograph variant could not be generated.'
            );
        }
    }

    private function prepareDirectories(
        string $root
    ): void {
        foreach (
            [
                $root,
                $root . DIRECTORY_SEPARATOR . 'original',
                $root . DIRECTORY_SEPARATOR . 'medium',
                $root . DIRECTORY_SEPARATOR . 'thumbnail',
            ] as $directory
        ) {
            if (
                !is_dir($directory)
                && !mkdir(
                    $directory,
                    0750,
                    true
                )
                && !is_dir($directory)
            ) {
                throw new RuntimeException(
                    'The secure photograph directory could not be created.'
                );
            }
        }
    }

    private function profileDirectory(
        string $profileReference
    ): string {
        $safeReference = preg_replace(
            '/[^A-Z0-9-]/',
            '',
            mb_strtoupper($profileReference)
        );

        if ($safeReference === '') {
            throw new RuntimeException(
                'Invalid pre-launch profile reference.'
            );
        }

        return WRITEPATH
            . 'uploads'
            . DIRECTORY_SEPARATOR
            . 'prelaunch'
            . DIRECTORY_SEPARATOR
            . $safeReference;
    }

    private function relativePath(
        string $absolutePath
    ): string {
        return ltrim(
            str_replace(
                WRITEPATH,
                '',
                $absolutePath
            ),
            DIRECTORY_SEPARATOR
        );
    }

    public function absolutePath(
        string $relativePath
    ): string {
        $normalized = str_replace(
            ['../', '..\\'],
            '',
            $relativePath
        );

        return WRITEPATH . ltrim(
            $normalized,
            DIRECTORY_SEPARATOR
        );
    }
}
