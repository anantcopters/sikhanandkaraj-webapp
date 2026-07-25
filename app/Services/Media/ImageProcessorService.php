<?php

declare(strict_types=1);

namespace App\Services\Media;

use Config\MemberMedia;
use GdImage;
use RuntimeException;
use Throwable;

/**
 * Validates, sanitizes, resizes, crops and watermarks images.
 *
 * Every output is re-encoded. Consequently, EXIF, GPS, device and
 * author metadata are not copied to the resulting files.
 */
final class ImageProcessorService
{
    private const MEDIUM_SIZE = 800;

    private const THUMBNAIL_SIZE = 250;

    private const MEDIUM_QUALITY = 85;

    private const THUMBNAIL_QUALITY = 80;

    public function __construct(
        private readonly MemberMedia $config
    ) {}

    /**
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

        $this->ensureDirectory($workingDirectory);

        $sourceImage = $this->createImageResource(
            $sourcePath,
            $actualMimeType
        );

        try {
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
             * Re-encoding the original strips EXIF metadata.
             */
            $originalImage = $this->cloneImage(
                $sourceImage
            );

            $this->applyWatermark($originalImage);

            $this->saveOriginal(
                $originalImage,
                $originalPath,
                $actualMimeType
            );

            imagedestroy($originalImage);

            $mediumImage = $this->createSquareVariant(
                $sourceImage,
                self::MEDIUM_SIZE
            );

            $this->applyWatermark($mediumImage);

            $this->saveWebp(
                $mediumImage,
                $mediumPath,
                self::MEDIUM_QUALITY
            );

            imagedestroy($mediumImage);

            $thumbnailImage = $this->createSquareVariant(
                $sourceImage,
                self::THUMBNAIL_SIZE
            );

            $this->applyWatermark($thumbnailImage);

            $this->saveWebp(
                $thumbnailImage,
                $thumbnailPath,
                self::THUMBNAIL_QUALITY
            );

            imagedestroy($thumbnailImage);

            return [
                'mimeType' => $actualMimeType,
                'extension' => $extension,
                'originalFileSize' =>
                (int) filesize($originalPath),
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

    private function detectMimeType(string $path): string
    {
        $fileInfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $fileInfo->file($path);

        if (!is_string($mimeType)) {
            throw new RuntimeException(
                'The uploaded photo type could not be verified.'
            );
        }

        return strtolower(trim($mimeType));
    }

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

        $exif = @exif_read_data($sourcePath);

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
        }

        return $rotated;
    }

    private function createSquareVariant(
        GdImage $source,
        int $targetSize
    ): GdImage {
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);

        $cropSize = min($sourceWidth, $sourceHeight);

        $sourceX = (int) floor(
            ($sourceWidth - $cropSize) / 2
        );

        $sourceY = (int) floor(
            ($sourceHeight - $cropSize) / 2
        );

        $destination = imagecreatetruecolor(
            $targetSize,
            $targetSize
        );

        if (!$destination instanceof GdImage) {
            throw new RuntimeException(
                'Image canvas creation failed.'
            );
        }

        $this->prepareTransparentCanvas(
            $destination
        );

        $copied = imagecopyresampled(
            $destination,
            $source,
            0,
            0,
            $sourceX,
            $sourceY,
            $targetSize,
            $targetSize,
            $cropSize,
            $cropSize
        );

        if (!$copied) {
            imagedestroy($destination);

            throw new RuntimeException(
                'Image resizing failed.'
            );
        }

        return $destination;
    }

    private function cloneImage(GdImage $source): GdImage
    {
        $width = imagesx($source);
        $height = imagesy($source);

        $clone = imagecreatetruecolor($width, $height);

        if (!$clone instanceof GdImage) {
            throw new RuntimeException(
                'Image canvas creation failed.'
            );
        }

        $this->prepareTransparentCanvas($clone);

        imagecopy(
            $clone,
            $source,
            0,
            0,
            0,
            0,
            $width,
            $height
        );

        return $clone;
    }

    private function applyWatermark(GdImage $image): void
    {
        $text = 'Sikhanandkaraj.com';

        $width = imagesx($image);
        $height = imagesy($image);

        /*
         * Built-in GD font avoids storing or distributing font files.
         */
        $font = 5;

        $textWidth = imagefontwidth($font)
            * strlen($text);

        $textHeight = imagefontheight($font);

        $padding = max(
            12,
            (int) floor(min($width, $height) * 0.025)
        );

        $x = max(
            $padding,
            $width - $textWidth - $padding
        );

        $y = max(
            $padding,
            $height - $textHeight - $padding
        );

        /*
         * GD alpha: 0 is opaque and 127 is transparent.
         * This produces a deliberately light watermark.
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

        imagestring(
            $image,
            $font,
            $x + 1,
            $y + 1,
            $text,
            $shadow
        );

        imagestring(
            $image,
            $font,
            $x,
            $y,
            $text,
            $foreground
        );
    }

    private function saveOriginal(
        GdImage $image,
        string $path,
        string $mimeType
    ): void {
        $saved = match ($mimeType) {
            'image/jpeg' => imagejpeg($image, $path, 92),
            'image/png' => imagepng($image, $path, 6),
            'image/webp' => imagewebp($image, $path, 90),
            default => false,
        };

        if (!$saved) {
            throw new RuntimeException(
                'Original image creation failed.'
            );
        }
    }

    private function saveWebp(
        GdImage $image,
        string $path,
        int $quality
    ): void {
        if (!imagewebp($image, $path, $quality)) {
            throw new RuntimeException(
                'WEBP image creation failed.'
            );
        }
    }

    private function prepareTransparentCanvas(
        GdImage $image
    ): void {
        imagealphablending($image, false);

        $transparent = imagecolorallocatealpha(
            $image,
            0,
            0,
            0,
            127
        );

        imagefill($image, 0, 0, $transparent);

        imagesavealpha($image, true);
        imagealphablending($image, true);
    }

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

    private function assertGdAvailable(): void
    {
        $requiredFunctions = [
            'imagecreatetruecolor',
            'imagecreatefromjpeg',
            'imagecreatefrompng',
            'imagecreatefromwebp',
            'imagewebp',
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
