<?php

declare(strict_types=1);

namespace App\Services\Prelaunch;

use App\Models\Prelaunch\PrelaunchPhotoModel;
use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Prelaunch;
use Config\Services;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Converts and stores prelaunch photographs in the secure CI4 writable
 * directory.
 *
 * Uploaded JPG, PNG and WebP files are never copied directly into permanent
 * local storage. Every photograph is decoded and stored as optimized WebP.
 */
final class PrelaunchPhotoService
{
    /**
     * MIME type used by every permanently stored prelaunch photograph.
     */
    private const STORED_MIME_TYPE = 'image/webp';

    /**
     * Extension used by every permanently stored prelaunch photograph.
     */
    private const STORED_EXTENSION = 'webp';

    /**
     * Supported source MIME types.
     *
     * @var list<string>
     */
    private const ALLOWED_SOURCE_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    /**
     * Additional output-size reduction factors used when WebP quality
     * reduction alone cannot reach the configured five-megabyte target.
     *
     * @var list<float>
     */
    private const DIMENSION_REDUCTION_FACTORS = [
        1.00,
        0.85,
        0.70,
        0.55,
    ];

    /**
     * @param PrelaunchPhotoModel $photoModel Prelaunch photo persistence model.
     */
    public function __construct(
        private readonly PrelaunchPhotoModel $photoModel
    ) {}

    /**
     * Store exactly the configured number of uploaded photographs.
     *
     * User-correctable upload failures are raised as
     * InvalidArgumentException so the controller can render them through
     * the existing formAlert mechanism.
     *
     * Infrastructure, filesystem, image-processing and database failures
     * continue to use RuntimeException and must not be exposed directly.
     *
     * @param int                       $profileId        Prelaunch profile ID.
     * @param string                    $profileReference Public profile reference.
     * @param array<int, UploadedFile>  $photos           Uploaded photographs.
     */
    public function storeProfilePhotos(
        int $profileId,
        string $profileReference,
        array $photos
    ): void {
        /** @var Prelaunch $config */
        $config = config('Prelaunch');

        if (count($photos) !== $config->maximumPhotos) {
            throw new InvalidArgumentException(
                sprintf(
                    'Exactly %d photographs are required.',
                    $config->maximumPhotos
                )
            );
        }

        if (
            $this->photoModel->countByProfile(
                $profileId
            ) !== 0
        ) {
            throw new InvalidArgumentException(
                'Photographs have already been uploaded for this profile.'
            );
        }

        /*
         * Validate every photograph before creating directories, variants
         * or database records. This avoids partial filesystem writes when
         * duplicate or unreadable images are submitted.
         */
        $this->ensurePhotosAreValidAndUnique(
            $photos
        );

        $root = $this->profileDirectory(
            $profileReference
        );

        $this->prepareDirectories(
            $root
        );

        /**
         * Files created during this operation.
         *
         * These files are deleted when image processing or database
         * persistence fails.
         *
         * @var list<string> $createdFiles
         */
        $createdFiles = [];

        try {
            foreach ($photos as $index => $photo) {
                $sequence = $index + 1;

                $stored = $this->storeSinglePhoto(
                    $sequence,
                    $photo,
                    $root
                );

                $createdFiles = array_merge(
                    $createdFiles,
                    [
                        $stored['absolute_original']
                    ]
                );

                $photoId = $this->photoModel->insert(
                    [
                        'prelaunch_profile_id' =>
                        $profileId,

                        'sequence_no' =>
                        $sequence,

                        'original_path' =>
                        $stored['relative_original'],

                        'medium_path' =>
                        null,

                        'thumbnail_path' =>
                        null,

                        /*
                         * Retain the user's source filename for administrative
                         * traceability. The stored file itself has a random
                         * WebP filename.
                         */
                        'original_filename' =>
                        $photo->getClientName(),

                        'mime_type' =>
                        self::STORED_MIME_TYPE,

                        'file_extension' =>
                        self::STORED_EXTENSION,

                        'file_size_bytes' =>
                        $stored['file_size'],

                        /*
                         * These dimensions describe the optimized stored
                         * original, not the raw source upload.
                         */
                        'width_px' =>
                        $stored['width'],

                        'height_px' =>
                        $stored['height'],

                        /*
                         * The checksum is generated after WebP conversion.
                         */
                        'checksum_sha256' =>
                        $stored['checksum'],

                        'approval_status' =>
                        PrelaunchPhotoModel::STATUS_PENDING,
                    ],
                    true
                );

                if ($photoId === false) {
                    throw new RuntimeException(
                        sprintf(
                            'Photograph %d metadata could not be saved.',
                            $sequence
                        )
                    );
                }
            }
        } catch (Throwable $exception) {
            /*
             * A database rollback cannot delete files generated before the
             * failure. Remove every generated file before rethrowing.
             */
            foreach ($createdFiles as $path) {
                if (is_file($path)) {
                    @unlink($path);
                }
            }

            throw $exception;
        }
    }

    /**
     * Ensure all uploaded photographs are valid, readable and unique.
     *
     * Duplicate detection uses checksums of the raw temporary uploads.
     * This must happen before conversion because separately compressed
     * source files may generate similar-looking output.
     *
     * @param array<int, UploadedFile> $photos Uploaded photographs.
     */
    private function ensurePhotosAreValidAndUnique(
        array $photos
    ): void {
        /**
         * Checksum indexed by the first sequence number where it occurred.
         *
         * @var array<string, int> $checksums
         */
        $checksums = [];

        foreach ($photos as $index => $photo) {
            $sequence = $index + 1;

            if (
                !$photo instanceof UploadedFile
                || !$photo->isValid()
            ) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Photograph %d is invalid. Please select another image.',
                        $sequence
                    )
                );
            }

            $temporaryPath =
                $photo->getTempName();

            if (
                $temporaryPath === ''
                || !is_file($temporaryPath)
            ) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Photograph %d could not be read. Please select it again.',
                        $sequence
                    )
                );
            }

            $detectedMime =
                mime_content_type($temporaryPath);

            if (
                $detectedMime === false
                || !in_array(
                    $detectedMime,
                    self::ALLOWED_SOURCE_MIME_TYPES,
                    true
                )
            ) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Photograph %d must be a valid JPG, PNG or WebP image.',
                        $sequence
                    )
                );
            }

            $imageInfo =
                @getimagesize($temporaryPath);

            if ($imageInfo === false) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Photograph %d could not be decoded. Please select another image.',
                        $sequence
                    )
                );
            }

            $checksum = hash_file(
                'sha256',
                $temporaryPath
            );

            if ($checksum === false) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Photograph %d could not be verified. Please select another image.',
                        $sequence
                    )
                );
            }

            if (isset($checksums[$checksum])) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Photographs %d and %d are identical. Please upload different photographs.',
                        $checksums[$checksum],
                        $sequence
                    )
                );
            }

            $checksums[$checksum] =
                $sequence;
        }
    }

    /**
     * Convert one uploaded image into optimized WebP variants.
     *
     * @param int          $sequence Photo sequence number.
     * @param UploadedFile $photo    Uploaded photograph.
     * @param string       $root     Profile storage root.
     *
     * @return array{
     *     absolute_original: string,
     *     absolute_medium: string,
     *     absolute_thumbnail: string,
     *     relative_original: string,
     *     relative_medium: string,
     *     relative_thumbnail: string,
     *     mime_type: string,
     *     extension: string,
     *     file_size: int,
     *     width: int,
     *     height: int,
     *     checksum: string
     * }
     */
    private function storeSinglePhoto(
        int $sequence,
        UploadedFile $photo,
        string $root
    ): array {
        if (!$photo->isValid()) {
            throw new InvalidArgumentException(
                sprintf(
                    'Photograph %d is invalid. Please select another image.',
                    $sequence
                )
            );
        }

        $sourcePath =
            $photo->getTempName();

        if (
            $sourcePath === ''
            || !is_file($sourcePath)
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'Photograph %d could not be read. Please select it again.',
                    $sequence
                )
            );
        }

        $detectedMime =
            mime_content_type($sourcePath);

        if (
            $detectedMime === false
            || !in_array(
                $detectedMime,
                self::ALLOWED_SOURCE_MIME_TYPES,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Only JPG, PNG and WebP photographs are allowed.'
            );
        }

        $sourceImageInfo =
            @getimagesize($sourcePath);

        if ($sourceImageInfo === false) {
            throw new InvalidArgumentException(
                'The uploaded photograph could not be decoded.'
            );
        }

        [$sourceWidth, $sourceHeight] =
            $sourceImageInfo;

        $randomName = sprintf(
            '%02d_%s.webp',
            $sequence,
            bin2hex(random_bytes(16))
        );

        $originalPath =
            $root
            . DIRECTORY_SEPARATOR
            . 'original'
            . DIRECTORY_SEPARATOR
            . $randomName;

        // $mediumPath =
        //     $root
        //     . DIRECTORY_SEPARATOR
        //     . 'medium'
        //     . DIRECTORY_SEPARATOR
        //     . $randomName;

        // $thumbnailPath =
        //     $root
        //     . DIRECTORY_SEPARATOR
        //     . 'thumbnail'
        //     . DIRECTORY_SEPARATOR
        //     . $randomName;

        /*
         * Generate the optimized WebP original directly from the temporary
         * upload. The raw JPG, PNG or WebP source is not retained.
         */
        $this->createOptimizedOriginal(
            $sourcePath,
            $originalPath,
            $sourceWidth,
            $sourceHeight
        );

        /*
         * Generate display variants from the optimized WebP original. This
         * avoids repeatedly decoding a potentially very large source upload.
         */
        // $this->createDisplayVariant(
        //     $originalPath,
        //     $mediumPath,
        //     'medium'
        // );

        // $this->createDisplayVariant(
        //     $originalPath,
        //     $thumbnailPath,
        //     'thumbnail'
        // );

        $storedImageInfo =
            @getimagesize($originalPath);

        if ($storedImageInfo === false) {
            throw new RuntimeException(
                'The optimized photograph metadata could not be read.'
            );
        }

        [$storedWidth, $storedHeight] =
            $storedImageInfo;

        $storedFileSize =
            filesize($originalPath);

        if ($storedFileSize === false) {
            throw new RuntimeException(
                'The optimized photograph size could not be determined.'
            );
        }

        $storedChecksum =
            hash_file(
                'sha256',
                $originalPath
            );

        if ($storedChecksum === false) {
            throw new RuntimeException(
                'The optimized photograph checksum could not be generated.'
            );
        }

        return [
            'absolute_original' =>
            $originalPath,

            // 'absolute_medium' =>
            // $mediumPath,

            // 'absolute_thumbnail' =>
            // $thumbnailPath,

            'relative_original' =>
            $this->relativePath(
                $originalPath
            ),

            // 'relative_medium' =>
            // $this->relativePath(
            //     $mediumPath
            // ),

            // 'relative_thumbnail' =>
            // $this->relativePath(
            //     $thumbnailPath
            // ),

            'mime_type' =>
            self::STORED_MIME_TYPE,

            'extension' =>
            self::STORED_EXTENSION,

            'file_size' =>
            $storedFileSize,

            'width' =>
            $storedWidth,

            'height' =>
            $storedHeight,

            'checksum' =>
            $storedChecksum,
        ];
    }

    /**
     * Create a high-quality WebP original below the configured size limit.
     *
     * The method first reduces image dimensions to a safe web-oriented size.
     * It then attempts multiple WebP quality levels. When reducing quality is
     * insufficient, dimensions are progressively reduced.
     *
     * Smaller source images are never enlarged.
     */
    private function createOptimizedOriginal(
        string $sourcePath,
        string $destinationPath,
        int $sourceWidth,
        int $sourceHeight
    ): void {
        /** @var \Config\Prelaunch $config */
        $config = config('Prelaunch');

        $baseDimensions = $this->calculateContainedDimensions(
            $sourceWidth,
            $sourceHeight,
            $config->optimizedOriginalWidth,
            $config->optimizedOriginalHeight
        );

        foreach (
            self::DIMENSION_REDUCTION_FACTORS
            as $reductionFactor
        ) {
            $attemptWidth = max(
                1,
                (int) floor(
                    $baseDimensions['width']
                        * $reductionFactor
                )
            );

            $attemptHeight = max(
                1,
                (int) floor(
                    $baseDimensions['height']
                        * $reductionFactor
                )
            );

            foreach (
                $config->optimizedWebpQualities
                as $quality
            ) {
                /*
             * A fresh handler is required for every compression attempt.
             * This prevents previous resize/save state from affecting the
             * next attempt.
             */
                $image = Services::image();

                $image->withFile(
                    $sourcePath
                );

                /*
             * Correct EXIF orientation for photographs taken on phones.
             *
             * CI4 uses reorient(), not orient().
             */
                $image->reorient();

                /*
             * Resize only when the image is larger than the current
             * boundary. Smaller images are never enlarged.
             */
                if (
                    $sourceWidth > $attemptWidth
                    || $sourceHeight > $attemptHeight
                ) {
                    $image->resize(
                        $attemptWidth,
                        $attemptHeight,
                        true,
                        $this->resizeMasterDimension(
                            $sourceWidth,
                            $sourceHeight,
                            $attemptWidth,
                            $attemptHeight
                        )
                    );
                }

                /*
             * convert() explicitly instructs the GD driver to encode WebP.
             * ImageMagick also respects the .webp destination extension.
             */
                $image->convert(
                    IMAGETYPE_WEBP
                );

                $image->save(
                    $destinationPath,
                    $quality
                );

                clearstatcache(
                    true,
                    $destinationPath
                );

                $storedSize =
                    filesize($destinationPath);

                if (
                    $storedSize !== false
                    && $storedSize
                    <= $config->maximumStoredPhotoSizeBytes
                ) {
                    return;
                }

                /*
             * Remove an oversized attempt before trying another quality
             * or dimension.
             */
                if (is_file($destinationPath)) {
                    @unlink($destinationPath);
                }
            }
        }

        throw new InvalidArgumentException(
            'One photograph could not be optimized below 5 MB. '
                . 'Please select a different photograph.'
        );
    }

    /**
     * Calculate dimensions that fit completely inside the requested boundary
     * while preserving the original aspect ratio.
     *
     * The returned dimensions never exceed the source dimensions, so smaller
     * photographs are not enlarged.
     *
     * @return array{width: int, height: int}
     */
    private function calculateContainedDimensions(
        int $sourceWidth,
        int $sourceHeight,
        int $maximumWidth,
        int $maximumHeight
    ): array {
        if (
            $sourceWidth <= 0
            || $sourceHeight <= 0
            || $maximumWidth <= 0
            || $maximumHeight <= 0
        ) {
            throw new RuntimeException(
                'Invalid photograph dimensions were provided.'
            );
        }

        $scale = min(
            $maximumWidth / $sourceWidth,
            $maximumHeight / $sourceHeight,
            1
        );

        return [
            'width' => max(
                1,
                (int) floor(
                    $sourceWidth * $scale
                )
            ),

            'height' => max(
                1,
                (int) floor(
                    $sourceHeight * $scale
                )
            ),
        ];
    }

    /**
     * Determine which resize boundary CI4 should strictly honour while
     * maintaining the source aspect ratio.
     */
    private function resizeMasterDimension(
        int $sourceWidth,
        int $sourceHeight,
        int $targetWidth,
        int $targetHeight
    ): string {
        $widthScale =
            $targetWidth / $sourceWidth;

        $heightScale =
            $targetHeight / $sourceHeight;

        /*
     * The smaller scale is the restrictive boundary. Honouring that
     * dimension ensures neither output dimension exceeds its target.
     */
        return $widthScale <= $heightScale
            ? 'width'
            : 'height';
    }

    /**
     * Create an unwatermarked prelaunch display variant in WebP format.
     *
     * Prelaunch photographs are staging assets. The final watermark is applied
     * later by the standard member-media S3 import pipeline.
     */
    // private function createDisplayVariant(
    //     string $sourcePath,
    //     string $destinationPath,
    //     string $variant
    // ): void {
    //     /** @var \Config\Prelaunch $config */
    //     $config = config('Prelaunch');

    //     if ($variant === 'medium') {
    //         $width =
    //             $config->mediumPhotoWidth;

    //         $height =
    //             $config->mediumPhotoHeight;

    //         $quality =
    //             $config->mediumWebpQuality;
    //     } elseif ($variant === 'thumbnail') {
    //         $width =
    //             $config->thumbnailPhotoWidth;

    //         $height =
    //             $config->thumbnailPhotoHeight;

    //         $quality =
    //             $config->thumbnailWebpQuality;
    //     } else {
    //         throw new RuntimeException(
    //             'Unsupported prelaunch photograph variant.'
    //         );
    //     }

    //     $image = Services::image();

    //     $image->withFile(
    //         $sourcePath
    //     );

    //     $image->fit(
    //         $width,
    //         $height,
    //         'center'
    //     );

    //     $image->convert(
    //         IMAGETYPE_WEBP
    //     );

    //     $image->save(
    //         $destinationPath,
    //         $quality
    //     );

    //     if (
    //         !is_file($destinationPath)
    //         || filesize($destinationPath) === false
    //     ) {
    //         throw new RuntimeException(
    //             sprintf(
    //                 'The %s photograph variant could not be generated.',
    //                 $variant
    //             )
    //         );
    //     }
    // }

    /**
     * Create all secure local profile directories.
     */
    private function prepareDirectories(
        string $root
    ): void {
        $directories = [
            $root,

            $root
                . DIRECTORY_SEPARATOR
                . 'original',
        ];

        foreach ($directories as $directory) {
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

    /**
     * Return the absolute secure local directory for one profile.
     */
    private function profileDirectory(
        string $profileReference
    ): string {
        $safeReference = preg_replace(
            '/[^A-Z0-9-]/',
            '',
            mb_strtoupper(
                $profileReference
            )
        );

        if (
            !is_string($safeReference)
            || $safeReference === ''
        ) {
            throw new RuntimeException(
                'Invalid prelaunch profile reference.'
            );
        }

        return WRITEPATH
            . 'uploads'
            . DIRECTORY_SEPARATOR
            . 'prelaunch'
            . DIRECTORY_SEPARATOR
            . $safeReference;
    }

    /**
     * Convert an absolute writable path into the stored relative path.
     */
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

    /**
     * Resolve a stored relative prelaunch path into an absolute path.
     */
    public function absolutePath(
        string $relativePath
    ): string {
        $normalized = str_replace(
            [
                '../',
                '..\\',
            ],
            '',
            $relativePath
        );

        return WRITEPATH
            . ltrim(
                $normalized,
                DIRECTORY_SEPARATOR
            );
    }
}
