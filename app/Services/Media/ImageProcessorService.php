<?php

declare(strict_types=1);

namespace App\Services\Media;

use Config\MemberMedia;
use GdImage;
use RuntimeException;
use Throwable;

/**
 * Validates, sanitizes, proportionally resizes and watermarks images.
 *
 * Every output is re-encoded. Consequently, EXIF, GPS, device and
 * author metadata are not copied to the resulting files.
 *
 * Medium and thumbnail variants preserve the complete uploaded photo
 * and its original aspect ratio. No cropping is performed.
 */
final class ImageProcessorService
{
    /**
     * Maximum width or height of the medium image.
     *
     * This is not a forced width and height. The longest side is limited
     * to this value while the original aspect ratio is preserved.
     */
    private const MEDIUM_MAX_DIMENSION = 1200;

    /**
     * Maximum width or height of the thumbnail.
     *
     * This is not a forced width and height. The longest side is limited
     * to this value while the original aspect ratio is preserved.
     */
    private const THUMBNAIL_MAX_DIMENSION = 300;

    /**
     * WebP quality values provide visually high-quality display images
     * without targeting a specific file size in bytes.
     */
    private const MEDIUM_QUALITY = 88;

    private const THUMBNAIL_QUALITY = 82;

    /**
     * Built-in GD fonts are numbered from 1 to 5.
     */
    private const WATERMARK_MAX_FONT = 5;

    private const WATERMARK_MIN_FONT = 1;

    /**
     * Minimum internal spacing between the watermark and image edges.
     */
    private const WATERMARK_MIN_PADDING = 6;

    public function __construct(
        private readonly MemberMedia $config
    ) {}

    /**
     * Process an uploaded member profile photo.
     *
     * @return array{
     *     mimeType:string,
     *     extension:string,
     *     originalFileSize:int,
     *     originalWidth:int,
     *     originalHeight:int,
     *     originalPath:string,
     *     mediumPath:string,
     *     thumbnailPath:string
     * }
     */
    public function processProfilePhoto(
        string $sourcePath,
        string $workingDirectory,
        string $uuid
    ): array {
        $this->assertGdAvailable();

        if (!is_file($sourcePath)) {
            throw new RuntimeException(
                'The uploaded photo was not found.'
            );
        }

        $actualMimeType = $this->detectMimeType(
            $sourcePath
        );

        $extension = $this
            ->config
            ->allowedImageMimeTypes[$actualMimeType]
            ?? null;

        if ($extension === null) {
            throw new RuntimeException(
                'Only JPEG and PNG photos are allowed.'
            );
        }

        $imageInfo = @getimagesize($sourcePath);

        if ($imageInfo === false) {
            throw new RuntimeException(
                'The uploaded file is not a valid image.'
            );
        }

        $sourceWidth = (int) $imageInfo[0];
        $sourceHeight = (int) $imageInfo[1];

        $this->assertDimensions(
            $sourceWidth,
            $sourceHeight
        );

        $this->ensureDirectory(
            $workingDirectory
        );

        $sourceImage = $this->createImageResource(
            $sourcePath,
            $actualMimeType
        );

        try {
            /*
             * Correct mobile-camera EXIF orientation before dimensions,
             * resizing, watermarking or output generation are performed.
             */
            $sourceImage = $this->correctOrientation(
                $sourceImage,
                $sourcePath,
                $actualMimeType
            );

            $processedWidth = imagesx($sourceImage);
            $processedHeight = imagesy($sourceImage);

            $originalPath = $workingDirectory
                . DIRECTORY_SEPARATOR
                . $uuid
                . '-original.'
                . $extension;

            $mediumPath = $workingDirectory
                . DIRECTORY_SEPARATOR
                . $uuid
                . '-medium.webp';

            $thumbnailPath = $workingDirectory
                . DIRECTORY_SEPARATOR
                . $uuid
                . '-thumbnail.webp';

            /*
             * Re-encoding strips EXIF and other embedded metadata.
             *
             * The original retains its corrected dimensions and receives
             * the configured watermark.
             */
            $originalImage = $this->cloneImage(
                $sourceImage
            );

            try {
                $this->applyWatermark(
                    $originalImage
                );

                $this->saveOriginal(
                    $originalImage,
                    $originalPath,
                    $actualMimeType
                );
            } finally {
                imagedestroy($originalImage);
            }

            /*
             * Generate the medium display image proportionally.
             *
             * No portion of the uploaded image is cropped. Images already
             * smaller than the configured maximum are not enlarged.
             */
            $mediumImage = $this->createProportionalVariant(
                $sourceImage,
                self::MEDIUM_MAX_DIMENSION
            );

            try {
                $this->applyWatermark(
                    $mediumImage
                );

                $this->saveWebp(
                    $mediumImage,
                    $mediumPath,
                    self::MEDIUM_QUALITY
                );
            } finally {
                imagedestroy($mediumImage);
            }

            /*
             * Generate the thumbnail proportionally.
             *
             * Thumbnails intentionally do not receive text watermarks.
             * They are displayed in small search and match cards where
             * watermark text may cover the photo or exceed narrow image
             * dimensions. Private S3 storage, authorization and signed
             * CloudFront URLs remain the security controls.
             */
            $thumbnailImage = $this->createProportionalVariant(
                $sourceImage,
                self::THUMBNAIL_MAX_DIMENSION
            );

            try {
                $this->saveWebp(
                    $thumbnailImage,
                    $thumbnailPath,
                    self::THUMBNAIL_QUALITY
                );
            } finally {
                imagedestroy($thumbnailImage);
            }

            $originalFileSize = filesize(
                $originalPath
            );

            if (!is_int($originalFileSize)) {
                throw new RuntimeException(
                    'The processed original image size '
                        . 'could not be determined.'
                );
            }

            return [
                'mimeType' => $actualMimeType,
                'extension' => $extension,
                'originalFileSize' => $originalFileSize,
                'originalWidth' => $processedWidth,
                'originalHeight' => $processedHeight,
                'originalPath' => $originalPath,
                'mediumPath' => $mediumPath,
                'thumbnailPath' => $thumbnailPath,
            ];
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'The uploaded photo could not be processed.',
                0,
                $exception
            );
        } finally {
            imagedestroy($sourceImage);
        }
    }

    /**
     * Detect the real MIME type from the uploaded file contents.
     */
    private function detectMimeType(string $path): string
    {
        $fileInfo = new \finfo(
            FILEINFO_MIME_TYPE
        );

        $mimeType = $fileInfo->file($path);

        if (!is_string($mimeType)) {
            throw new RuntimeException(
                'The uploaded photo type could not be verified.'
            );
        }

        return strtolower(
            trim($mimeType)
        );
    }

    /**
     * Validate decoded image dimensions against media configuration.
     */
    private function assertDimensions(
        int $width,
        int $height
    ): void {
        if (
            $width < $this->config->minimumWidth
            || $height < $this->config->minimumHeight
        ) {
            throw new RuntimeException(
                sprintf(
                    'The photo must be at least %d × %d pixels.',
                    $this->config->minimumWidth,
                    $this->config->minimumHeight
                )
            );
        }

        if (
            $width > $this->config->maximumWidth
            || $height > $this->config->maximumHeight
        ) {
            throw new RuntimeException(
                sprintf(
                    'The photo must not exceed %d × %d pixels.',
                    $this->config->maximumWidth,
                    $this->config->maximumHeight
                )
            );
        }
    }

    /**
     * Decode a supported image into a GD image resource.
     */
    private function createImageResource(
        string $path,
        string $mimeType
    ): GdImage {
        $image = match ($mimeType) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            default => false,
        };

        if (!$image instanceof GdImage) {
            throw new RuntimeException(
                'The uploaded image could not be decoded.'
            );
        }

        imagealphablending($image, true);
        imagesavealpha($image, true);

        return $image;
    }

    /**
     * Correct the common JPEG orientation values created by mobile cameras.
     */
    private function correctOrientation(
        GdImage $image,
        string $sourcePath,
        string $mimeType
    ): GdImage {
        if (
            $mimeType !== 'image/jpeg'
            || !function_exists('exif_read_data')
        ) {
            return $image;
        }

        $exif = @exif_read_data(
            $sourcePath
        );

        if (!is_array($exif)) {
            return $image;
        }

        $orientation = (int) (
            $exif['Orientation'] ?? 1
        );

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        if (!$rotated instanceof GdImage) {
            return $image;
        }

        if ($rotated !== $image) {
            imagedestroy($image);

            imagealphablending($rotated, true);
            imagesavealpha($rotated, true);
        }

        return $rotated;
    }

    /**
     * Create a proportionally resized image variant.
     *
     * The complete source image is retained. No width or height is cropped.
     * The longest side is limited to the requested maximum dimension while
     * preserving the source aspect ratio.
     *
     * Images already within the maximum dimension are cloned rather than
     * enlarged because upscaling would reduce visible quality.
     */
    private function createProportionalVariant(
        GdImage $source,
        int $maximumDimension
    ): GdImage {
        if ($maximumDimension <= 0) {
            throw new RuntimeException(
                'The image maximum dimension must be greater than zero.'
            );
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);

        if (
            $sourceWidth <= 0
            || $sourceHeight <= 0
        ) {
            throw new RuntimeException(
                'Invalid source image dimensions.'
            );
        }

        $longestSide = max(
            $sourceWidth,
            $sourceHeight
        );

        /*
         * Never enlarge smaller images.
         */
        $scaleRatio = min(
            1.0,
            $maximumDimension / $longestSide
        );

        $targetWidth = max(
            1,
            (int) round(
                $sourceWidth * $scaleRatio
            )
        );

        $targetHeight = max(
            1,
            (int) round(
                $sourceHeight * $scaleRatio
            )
        );

        $destination = imagecreatetruecolor(
            $targetWidth,
            $targetHeight
        );

        if (!$destination instanceof GdImage) {
            throw new RuntimeException(
                'Image canvas creation failed.'
            );
        }

        $this->prepareTransparentCanvas(
            $destination
        );

        $resampled = imagecopyresampled(
            $destination,
            $source,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $sourceWidth,
            $sourceHeight
        );

        if (!$resampled) {
            imagedestroy($destination);

            throw new RuntimeException(
                'Image resizing failed.'
            );
        }

        return $destination;
    }

    /**
     * Create a metadata-free copy of an image.
     */
    private function cloneImage(
        GdImage $source
    ): GdImage {
        $width = imagesx($source);
        $height = imagesy($source);

        $clone = imagecreatetruecolor(
            $width,
            $height
        );

        if (!$clone instanceof GdImage) {
            throw new RuntimeException(
                'Image canvas creation failed.'
            );
        }

        $this->prepareTransparentCanvas(
            $clone
        );

        $copied = imagecopy(
            $clone,
            $source,
            0,
            0,
            0,
            0,
            $width,
            $height
        );

        if (!$copied) {
            imagedestroy($clone);

            throw new RuntimeException(
                'Image cloning failed.'
            );
        }

        return $clone;
    }

    /**
     * Apply the configured member-media watermark.
     *
     * The largest built-in GD font that safely fits inside the image is
     * selected. If the configured text cannot fit even with the smallest
     * font, watermarking is skipped rather than drawing outside the image.
     */
    private function applyWatermark(
        GdImage $image
    ): void {
        $text = trim(
            $this->config->watermarkText
        );

        if ($text === '') {
            return;
        }

        $width = imagesx($image);
        $height = imagesy($image);

        if (
            $width <= 0
            || $height <= 0
        ) {
            return;
        }

        /*
         * Padding scales with the image but remains practical for smaller
         * proportional images.
         */
        $padding = max(
            self::WATERMARK_MIN_PADDING,
            (int) floor(
                min($width, $height) * 0.025
            )
        );

        $availableWidth = $width
            - ($padding * 2);

        $availableHeight = $height
            - ($padding * 2);

        if (
            $availableWidth <= 0
            || $availableHeight <= 0
        ) {
            return;
        }

        $font = $this->resolveWatermarkFont(
            $text,
            $availableWidth,
            $availableHeight
        );

        /*
         * The watermark is skipped when it cannot be contained completely
         * inside the current image dimensions.
         */
        if ($font === null) {
            return;
        }

        $textWidth = imagefontwidth($font)
            * strlen($text);

        $textHeight = imagefontheight($font);

        /*
         * Position the watermark at the bottom-right while guaranteeing
         * that every pixel remains inside the image.
         */
        $x = $width
            - $textWidth
            - $padding;

        $y = $height
            - $textHeight
            - $padding;

        if (
            $x < $padding
            || $y < $padding
            || ($x + $textWidth) > ($width - $padding)
            || ($y + $textHeight) > ($height - $padding)
        ) {
            return;
        }

        /*
         * GD alpha values:
         * 0   = fully opaque
         * 127 = fully transparent
         */
        $shadow = imagecolorallocatealpha(
            $image,
            0,
            0,
            0,
            95
        );

        $foreground = imagecolorallocatealpha(
            $image,
            255,
            255,
            255,
            70
        );

        /*
         * Draw the subtle shadow first, but only when its one-pixel offset
         * also remains inside the image.
         */
        if (
            ($x + $textWidth + 1) <= ($width - $padding)
            && ($y + $textHeight + 1) <= ($height - $padding)
        ) {
            imagestring(
                $image,
                $font,
                $x + 1,
                $y + 1,
                $text,
                $shadow
            );
        }

        imagestring(
            $image,
            $font,
            $x,
            $y,
            $text,
            $foreground
        );
    }

    /**
     * Find the largest built-in GD font that fits inside the available area.
     */
    private function resolveWatermarkFont(
        string $text,
        int $availableWidth,
        int $availableHeight
    ): ?int {
        for (
            $font = self::WATERMARK_MAX_FONT;
            $font >= self::WATERMARK_MIN_FONT;
            $font--
        ) {
            $textWidth = imagefontwidth($font)
                * strlen($text);

            $textHeight = imagefontheight($font);

            if (
                $textWidth <= $availableWidth
                && $textHeight <= $availableHeight
            ) {
                return $font;
            }
        }

        return null;
    }

    /**
     * Save the metadata-free original in its verified source format.
     */
    private function saveOriginal(
        GdImage $image,
        string $path,
        string $mimeType
    ): void {
        $saved = match ($mimeType) {
            'image/jpeg' => imagejpeg(
                $image,
                $path,
                92
            ),
            'image/png' => imagepng(
                $image,
                $path,
                6
            ),
            default => false,
        };

        if (!$saved) {
            throw new RuntimeException(
                'Original image creation failed.'
            );
        }
    }

    /**
     * Save a display variant as a high-quality WebP image.
     */
    private function saveWebp(
        GdImage $image,
        string $path,
        int $quality
    ): void {
        if (
            $quality < 0
            || $quality > 100
        ) {
            throw new RuntimeException(
                'Invalid WebP quality value.'
            );
        }

        if (!imagewebp($image, $path, $quality)) {
            throw new RuntimeException(
                'WEBP image creation failed.'
            );
        }
    }

    /**
     * Prepare a GD canvas while preserving PNG transparency.
     */
    private function prepareTransparentCanvas(
        GdImage $image
    ): void {
        imagealphablending(
            $image,
            false
        );

        $transparent = imagecolorallocatealpha(
            $image,
            0,
            0,
            0,
            127
        );

        imagefill(
            $image,
            0,
            0,
            $transparent
        );

        imagesavealpha(
            $image,
            true
        );

        imagealphablending(
            $image,
            true
        );
    }

    /**
     * Ensure temporary media storage exists with private permissions.
     */
    private function ensureDirectory(
        string $directory
    ): void {
        if (
            !is_dir($directory)
            && !mkdir($directory, 0700, true)
            && !is_dir($directory)
        ) {
            throw new RuntimeException(
                'Temporary media directory '
                    . 'could not be created.'
            );
        }
    }

    /**
     * Verify that required GD functions are installed.
     */
    private function assertGdAvailable(): void
    {
        $requiredFunctions = [
            'imagecreatetruecolor',
            'imagecreatefromjpeg',
            'imagecreatefrompng',
            'imagecopy',
            'imagecopyresampled',
            'imagejpeg',
            'imagepng',
            'imagewebp',
            'imagestring',
            'imagefontwidth',
            'imagefontheight',
        ];

        foreach ($requiredFunctions as $function) {
            if (!function_exists($function)) {
                throw new RuntimeException(
                    'Required GD image support '
                        . 'is not installed.'
                );
            }
        }
    }
}
