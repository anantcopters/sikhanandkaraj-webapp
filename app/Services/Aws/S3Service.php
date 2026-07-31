<?php

declare(strict_types=1);

namespace App\Services\Aws;

use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use Config\MemberMedia;
use RuntimeException;
use Throwable;

/**
 * Centralizes private Amazon S3 object operations.
 */
final class S3Service
{
    public function __construct(
        private readonly S3Client $client,
        private readonly MemberMedia $config
    ) {}

    /**
     * Upload a private media object and return its object key.
     *
     * @param array<string, string> $metadata
     */
    public function upload(
        string $localPath,
        string $objectKey,
        string $mimeType,
        array $metadata = []
    ): string {
        if (!is_file($localPath) || !is_readable($localPath)) {
            throw new RuntimeException(
                'The media file is not available for upload.'
            );
        }

        $stream = fopen($localPath, 'rb');

        if ($stream === false) {
            throw new RuntimeException(
                'The media file could not be opened.'
            );
        }

        try {
            $this->client->putObject([
                'Bucket' => $this->config->s3Bucket,
                'Key' => $objectKey,
                'Body' => $stream,
                'ContentType' => $mimeType,

                /*
                 * Objects use UUID names and are immutable.
                 */
                'CacheControl' =>
                'public, max-age=31536000, immutable',

                /*
                 * Do not use public-read ACL.
                 * Bucket policy/OAC keeps direct S3 access private.
                 */
                'Metadata' => $this->sanitizeMetadata(
                    $metadata
                ),
            ]);

            return $objectKey;
        } catch (AwsException $exception) {
            log_message(
                'error',
                'S3 upload failed for object {objectKey}: '
                    . '{awsCode}',
                [
                    'objectKey' => $objectKey,
                    'awsCode' =>
                    $exception->getAwsErrorCode()
                        ?? 'unknown',
                ]
            );

            throw new RuntimeException(
                'The photo could not be stored.',
                0,
                $exception
            );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Unexpected S3 upload failure for object '
                    . '{objectKey}: {message}',
                [
                    'objectKey' => $objectKey,
                    'message' => $exception->getMessage(),
                ]
            );

            throw new RuntimeException(
                'The photo could not be stored.',
                0,
                $exception
            );
        } finally {
            fclose($stream);
        }
    }

    public function delete(string $objectKey): bool
    {
        if ($objectKey === '') {
            return true;
        }

        try {
            $this->client->deleteObject([
                'Bucket' => $this->config->s3Bucket,
                'Key' => $objectKey,
            ]);

            return true;
        } catch (AwsException $exception) {
            log_message(
                'error',
                'S3 delete failed for object {objectKey}: '
                    . '{awsCode}',
                [
                    'objectKey' => $objectKey,
                    'awsCode' =>
                    $exception->getAwsErrorCode()
                        ?? 'unknown',
                ]
            );

            return false;
        }
    }

    /**
     * Delete multiple objects without exposing individual failures.
     *
     * @param list<string> $objectKeys
     */
    public function deleteMany(array $objectKeys): bool
    {
        $objects = [];

        foreach (array_unique($objectKeys) as $objectKey) {
            if ($objectKey !== '') {
                $objects[] = [
                    'Key' => $objectKey,
                ];
            }
        }

        if ($objects === []) {
            return true;
        }

        try {
            $result = $this->client->deleteObjects([
                'Bucket' => $this->config->s3Bucket,
                'Delete' => [
                    'Objects' => $objects,
                    'Quiet' => true,
                ],
            ]);

            $errors = $result->get('Errors');

            if (is_array($errors) && $errors !== []) {
                log_message(
                    'error',
                    'One or more S3 media objects could '
                        . 'not be deleted.'
                );

                return false;
            }

            return true;
        } catch (AwsException $exception) {
            log_message(
                'error',
                'Bulk S3 deletion failed: {awsCode}',
                [
                    'awsCode' =>
                    $exception->getAwsErrorCode()
                        ?? 'unknown',
                ]
            );

            return false;
        }
    }

    public function exists(string $objectKey): bool
    {
        try {
            return $this->client->doesObjectExistV2(
                $this->config->s3Bucket,
                $objectKey
            );
        } catch (AwsException $exception) {
            log_message(
                'error',
                'S3 existence check failed for '
                    . '{objectKey}: {awsCode}',
                [
                    'objectKey' => $objectKey,
                    'awsCode' =>
                    $exception->getAwsErrorCode()
                        ?? 'unknown',
                ]
            );

            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(string $objectKey): array
    {
        try {
            $result = $this->client->headObject([
                'Bucket' => $this->config->s3Bucket,
                'Key' => $objectKey,
            ]);

            return $result->toArray();
        } catch (AwsException $exception) {
            throw new RuntimeException(
                'The media metadata could not be read.',
                0,
                $exception
            );
        }
    }

    public function copy(
        string $sourceObjectKey,
        string $destinationObjectKey
    ): string {
        try {
            $this->client->copyObject([
                'Bucket' => $this->config->s3Bucket,
                'CopySource' =>
                rawurlencode($this->config->s3Bucket)
                    . '/'
                    . str_replace(
                        '%2F',
                        '/',
                        rawurlencode($sourceObjectKey)
                    ),
                'Key' => $destinationObjectKey,
                'MetadataDirective' => 'COPY',
            ]);

            return $destinationObjectKey;
        } catch (AwsException $exception) {
            throw new RuntimeException(
                'The media object could not be copied.',
                0,
                $exception
            );
        }
    }

    public function move(
        string $sourceObjectKey,
        string $destinationObjectKey
    ): string {
        $this->copy(
            $sourceObjectKey,
            $destinationObjectKey
        );

        if (!$this->delete($sourceObjectKey)) {
            /*
             * Destination remains available. Log cleanup failure
             * without deleting the successfully copied destination.
             */
            log_message(
                'warning',
                'S3 move copied {sourceKey} to '
                    . '{destinationKey}, but source cleanup failed.',
                [
                    'sourceKey' => $sourceObjectKey,
                    'destinationKey' =>
                    $destinationObjectKey,
                ]
            );
        }

        return $destinationObjectKey;
    }

    /**
     * S3 user metadata must remain simple and non-sensitive.
     *
     * @param array<string, string> $metadata
     *
     * @return array<string, string>
     */
    private function sanitizeMetadata(array $metadata): array
    {
        $clean = [];

        foreach ($metadata as $key => $value) {
            $cleanKey = strtolower(
                preg_replace(
                    '/[^a-z0-9-]/i',
                    '-',
                    trim($key)
                ) ?? ''
            );

            if ($cleanKey === '') {
                continue;
            }

            $clean[$cleanKey] = mb_substr(
                trim($value),
                0,
                200
            );
        }

        return $clean;
    }
}
