<?php

declare(strict_types=1);

namespace App\Services\Aws;

use App\Support\InfrastructureErrorContext;
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
        if (
            !is_file($localPath)
            || !is_readable($localPath)
        ) {
            throw new RuntimeException(
                'The media file is not available for upload.'
            );
        }

        $stream = fopen(
            $localPath,
            'rb'
        );

        if ($stream === false) {
            throw new RuntimeException(
                'The media file could not be opened.'
            );
        }

        try {
            $this->client->putObject([
                'Bucket' =>
                $this->config->s3Bucket,

                'Key' =>
                $objectKey,

                'Body' =>
                $stream,

                'ContentType' =>
                $mimeType,

                /*
                 * Objects use UUID names and are immutable.
                 */
                'CacheControl' =>
                'public, max-age=31536000, immutable',

                /*
                 * Do not use public-read ACL.
                 * Bucket policy/OAC keeps direct S3 access private.
                 */
                'Metadata' =>
                $this->sanitizeMetadata(
                    $metadata
                ),
            ]);

            return $objectKey;
        } catch (AwsException $exception) {
            /*
             * Upload propagates the error to the workflow owner.
             *
             * Do not log here because MemberPhotoController or the relevant
             * migration/controller will log the final failed operation once.
             */
            throw new RuntimeException(
                'The photo could not be stored.',
                0,
                $exception
            );
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'The photo could not be stored.',
                0,
                $exception
            );
        } finally {
            fclose($stream);
        }
    }

    /**
     * Delete one private object.
     *
     * This method returns false instead of throwing, so it owns failure
     * logging.
     */
    public function delete(
        string $objectKey
    ): bool {
        if (trim($objectKey) === '') {
            return true;
        }

        try {
            $this->client->deleteObject([
                'Bucket' =>
                $this->config->s3Bucket,

                'Key' =>
                $objectKey,
            ]);

            return true;
        } catch (AwsException $exception) {
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'warning',
                InfrastructureErrorContext::forOperation(
                    operation: 's3_object_delete',

                    component: self::class,

                    method: __FUNCTION__,

                    additionalContext: [
                        'object_key_hash' =>
                        InfrastructureErrorContext
                            ::objectKeyHash(
                                $objectKey
                            ),

                        'aws_error_code' =>
                        $exception
                            ->getAwsErrorCode()
                            ?? 'unknown',

                        'bucket_configured' =>
                        trim(
                            $this->config
                                ->s3Bucket
                        ) !== '',
                    ]
                )
            );

            return false;
        } catch (Throwable $exception) {
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'warning',
                InfrastructureErrorContext::forOperation(
                    operation: 's3_object_delete',

                    component: self::class,

                    method: __FUNCTION__,

                    additionalContext: [
                        'object_key_hash' =>
                        InfrastructureErrorContext
                            ::objectKeyHash(
                                $objectKey
                            ),
                    ]
                )
            );

            return false;
        }
    }

    /**
     * Delete multiple objects without exposing individual failures.
     *
     * @param list<string> $objectKeys
     */
    public function deleteMany(
        array $objectKeys
    ): bool {
        $objects = [];

        foreach (
            array_unique($objectKeys)
            as $objectKey
        ) {
            $resolvedObjectKey = trim(
                (string) $objectKey
            );

            if ($resolvedObjectKey !== '') {
                $objects[] = [
                    'Key' =>
                    $resolvedObjectKey,
                ];
            }
        }

        if ($objects === []) {
            return true;
        }

        try {
            $result = $this->client
                ->deleteObjects([
                    'Bucket' =>
                    $this->config->s3Bucket,

                    'Delete' => [
                        'Objects' =>
                        $objects,

                        'Quiet' =>
                        true,
                    ],
                ]);

            $errors = $result->get(
                'Errors'
            );

            if (
                is_array($errors)
                && $errors !== []
            ) {
                service(
                    'applicationErrorLogger'
                )->error(
                    'One or more S3 media objects could not be deleted.',
                    InfrastructureErrorContext::forOperation(
                        operation: 's3_bulk_delete_partial_failure',

                        component: self::class,

                        method: __FUNCTION__,

                        additionalContext: [
                            'requested_object_count' =>
                            count($objects),

                            'failed_object_count' =>
                            count($errors),
                        ]
                    ),
                    'warning'
                );

                return false;
            }

            return true;
        } catch (AwsException $exception) {
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'warning',
                InfrastructureErrorContext::forOperation(
                    operation: 's3_bulk_delete',

                    component: self::class,

                    method: __FUNCTION__,

                    additionalContext: [
                        'requested_object_count' =>
                        count($objects),

                        'aws_error_code' =>
                        $exception
                            ->getAwsErrorCode()
                            ?? 'unknown',
                    ]
                )
            );

            return false;
        } catch (Throwable $exception) {
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'warning',
                InfrastructureErrorContext::forOperation(
                    operation: 's3_bulk_delete',

                    component: self::class,

                    method: __FUNCTION__,

                    additionalContext: [
                        'requested_object_count' =>
                        count($objects),
                    ]
                )
            );

            return false;
        }
    }

    /**
     * Check whether one object exists.
     *
     * False may mean either absent or an infrastructure failure. The latter is
     * logged because this method intentionally returns false.
     */
    public function exists(
        string $objectKey
    ): bool {
        if (trim($objectKey) === '') {
            return false;
        }

        try {
            return $this->client
                ->doesObjectExistV2(
                    $this->config->s3Bucket,
                    $objectKey
                );
        } catch (AwsException $exception) {
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'warning',
                InfrastructureErrorContext::forOperation(
                    operation: 's3_object_exists_check',

                    component: self::class,

                    method: __FUNCTION__,

                    additionalContext: [
                        'object_key_hash' =>
                        InfrastructureErrorContext
                            ::objectKeyHash(
                                $objectKey
                            ),

                        'aws_error_code' =>
                        $exception
                            ->getAwsErrorCode()
                            ?? 'unknown',
                    ]
                )
            );

            return false;
        } catch (Throwable $exception) {
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'warning',
                InfrastructureErrorContext::forOperation(
                    operation: 's3_object_exists_check',

                    component: self::class,

                    method: __FUNCTION__,

                    additionalContext: [
                        'object_key_hash' =>
                        InfrastructureErrorContext
                            ::objectKeyHash(
                                $objectKey
                            ),
                    ]
                )
            );

            return false;
        }
    }

    /**
     * Return S3 object metadata.
     *
     * @return array<string, mixed>
     */
    public function getMetadata(
        string $objectKey
    ): array {
        try {
            $result = $this->client
                ->headObject([
                    'Bucket' =>
                    $this->config->s3Bucket,

                    'Key' =>
                    $objectKey,
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

    /**
     * Copy one private S3 object.
     */
    public function copy(
        string $sourceObjectKey,
        string $destinationObjectKey
    ): string {
        try {
            $this->client->copyObject([
                'Bucket' =>
                $this->config->s3Bucket,

                'CopySource' =>
                rawurlencode(
                    $this->config
                        ->s3Bucket
                )
                    . '/'
                    . str_replace(
                        '%2F',
                        '/',
                        rawurlencode(
                            $sourceObjectKey
                        )
                    ),

                'Key' =>
                $destinationObjectKey,

                'MetadataDirective' =>
                'COPY',
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

    /**
     * Move one object by copying and then deleting the source.
     */
    public function move(
        string $sourceObjectKey,
        string $destinationObjectKey
    ): string {
        $this->copy(
            $sourceObjectKey,
            $destinationObjectKey
        );

        /*
         * delete() logs its own swallowed failure. Do not create a second
         * application_error_logs row here.
         */
        $this->delete(
            $sourceObjectKey
        );

        return $destinationObjectKey;
    }

    /**
     * S3 user metadata must remain simple and non-sensitive.
     *
     * @param array<string, string> $metadata
     *
     * @return array<string, string>
     */
    private function sanitizeMetadata(
        array $metadata
    ): array {
        $clean = [];

        foreach (
            $metadata
            as $key => $value
        ) {
            $cleanKey = mb_strtolower(
                preg_replace(
                    '/[^a-z0-9-]/i',
                    '-',
                    trim(
                        (string) $key
                    )
                ) ?? ''
            );

            if ($cleanKey === '') {
                continue;
            }

            $clean[$cleanKey] =
                mb_substr(
                    trim(
                        (string) $value
                    ),
                    0,
                    200
                );
        }

        return $clean;
    }
}
