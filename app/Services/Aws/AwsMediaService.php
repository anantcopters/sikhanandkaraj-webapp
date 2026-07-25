<?php

declare(strict_types=1);

namespace App\Services\Aws;

use App\Services\Media\ImageProcessorService;
use CodeIgniter\HTTP\Files\UploadedFile;
use Config\MemberMedia;
use RuntimeException;
use Throwable;

/**
 * Coordinates temporary storage, image processing and S3 operations.
 */
final class AwsMediaService
{
    public function __construct(
        private readonly S3Service $s3Service,
        private readonly CloudFrontService $cloudFrontService,
        private readonly MediaPathService $mediaPathService,
        private readonly ImageProcessorService $imageProcessor,
        private readonly MemberMedia $config
    ) {}

    /**
     * @return array{
     *     uuid:string,
     *     originalObjectKey:string,
     *     mediumObjectKey:string,
     *     thumbnailObjectKey:string,
     *     originalFilename:string,
     *     mimeType:string,
     *     extension:string,
     *     fileSize:int,
     *     width:int,
     *     height:int
     * }
     */
    public function uploadProfilePhoto(
        UploadedFile $uploadedFile,
        int $memberId
    ): array {
        $uuid = $this->uuidV4();

        $requestDirectory = WRITEPATH
            . 'uploads'
            . DIRECTORY_SEPARATOR
            . 'temp'
            . DIRECTORY_SEPARATOR
            . $uuid;

        $sourcePath = '';

        $uploadedObjectKeys = [];

        try {
            $this->createPrivateDirectory(
                $requestDirectory
            );

            $temporaryFilename = 'source-upload';

            $uploadedFile->move(
                $requestDirectory,
                $temporaryFilename,
                true
            );

            $sourcePath = $requestDirectory
                . DIRECTORY_SEPARATOR
                . $temporaryFilename;

            $processed = $this
                ->imageProcessor
                ->processProfilePhoto(
                    $sourcePath,
                    $requestDirectory,
                    $uuid
                );

            $paths = $this
                ->mediaPathService
                ->profilePhotoPaths(
                    $uuid,
                    $processed['extension']
                );

            $metadata = [
                'uploaded-by' => (string) $memberId,
                'uploaded-at' =>
                gmdate('Y-m-d\TH:i:s\Z'),
                'application' =>
                $this->config->applicationName,
                'environment' =>
                $this->config->environmentName,
                'media-uuid' => $uuid,
            ];

            $uploadedObjectKeys[] =
                $this->s3Service->upload(
                    $processed['originalPath'],
                    $paths['original'],
                    $processed['mimeType'],
                    $metadata + [
                        'variant' => 'original',
                    ]
                );

            $uploadedObjectKeys[] =
                $this->s3Service->upload(
                    $processed['mediumPath'],
                    $paths['medium'],
                    'image/webp',
                    $metadata + [
                        'variant' => 'medium',
                    ]
                );

            $uploadedObjectKeys[] =
                $this->s3Service->upload(
                    $processed['thumbnailPath'],
                    $paths['thumbnail'],
                    'image/webp',
                    $metadata + [
                        'variant' => 'thumbnail',
                    ]
                );

            return [
                'uuid' => $uuid,
                'originalObjectKey' =>
                $paths['original'],
                'mediumObjectKey' => $paths['medium'],
                'thumbnailObjectKey' =>
                $paths['thumbnail'],
                'originalFilename' =>
                $this->safeOriginalFilename(
                    $uploadedFile->getClientName()
                ),
                'mimeType' => $processed['mimeType'],
                'extension' => $processed['extension'],
                'fileSize' =>
                $processed['originalFileSize'],
                'width' => $processed['originalWidth'],
                'height' => $processed['originalHeight'],
            ];
        } catch (Throwable $exception) {
            if ($uploadedObjectKeys !== []) {
                $this->s3Service->deleteMany(
                    $uploadedObjectKeys
                );
            }

            throw $exception;
        } finally {
            $this->removeDirectory($requestDirectory);
        }
    }

    /**
     * @param array<string, mixed> $photo
     *
     * @return array{
     *     originalUrl:string,
     *     mediumUrl:string,
     *     thumbnailUrl:string
     * }
     */
    public function profilePhotoUrls(
        array $photo
    ): array {
        return [
            'originalUrl' =>
            $this->cloudFrontService->signedUrl(
                (string) $photo['original_object_key'],
                $this->config->profileUrlTtlSeconds
            ),

            'mediumUrl' =>
            $this->cloudFrontService->signedUrl(
                (string) $photo['medium_object_key'],
                $this->config->profileUrlTtlSeconds
            ),

            'thumbnailUrl' =>
            $this->cloudFrontService->signedUrl(
                (string) $photo['thumbnail_object_key'],
                $this->config->profileUrlTtlSeconds
            ),
        ];
    }

    /**
     * @param array<string, mixed> $photo
     */
    public function deleteProfilePhotoObjects(
        array $photo
    ): bool {
        return $this->s3Service->deleteMany([
            (string) (
                $photo['original_object_key'] ?? ''
            ),
            (string) (
                $photo['medium_object_key'] ?? ''
            ),
            (string) (
                $photo['thumbnail_object_key'] ?? ''
            ),
        ]);
    }

    /**
     * @param list<string> $objectKeys
     */
    public function deleteObjectKeys(array $objectKeys): bool
    {
        return $this->s3Service->deleteMany(
            $objectKeys
        );
    }

    private function safeOriginalFilename(
        string $filename
    ): string {
        $filename = basename(
            str_replace('\\', '/', $filename)
        );

        $filename = preg_replace(
            '/[^\pL\pN._ -]/u',
            '',
            $filename
        ) ?? 'photo';

        return mb_substr(
            trim($filename) !== ''
                ? trim($filename)
                : 'photo',
            0,
            255
        );
    }

    private function createPrivateDirectory(
        string $directory
    ): void {
        if (
            !is_dir($directory)
            && !mkdir($directory, 0700, true)
            && !is_dir($directory)
        ) {
            throw new RuntimeException(
                'Temporary upload storage '
                    . 'could not be prepared.'
            );
        }
    }

    private function removeDirectory(
        string $directory
    ): void {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        if (!is_array($items)) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory
                . DIRECTORY_SEPARATOR
                . $item;

            if (is_file($path) || is_link($path)) {
                @unlink($path);
            }
        }

        @rmdir($directory);
    }

    private function uuidV4(): string
    {
        $bytes = random_bytes(16);

        $bytes[6] = chr(
            (ord($bytes[6]) & 0x0f) | 0x40
        );

        $bytes[8] = chr(
            (ord($bytes[8]) & 0x3f) | 0x80
        );

        return vsprintf(
            '%s%s-%s-%s-%s-%s%s%s',
            str_split(bin2hex($bytes), 4)
        );
    }
}
