<?php

declare(strict_types=1);

namespace App\Interfaces;

/**
 * Defines storage operations independently of the storage provider.
 */
interface MediaStorageInterface
{
    /**
     * Stores a media file and returns its permanent object key.
     *
     * @param string $temporaryPath Local temporary-file path.
     * @param string $objectKey     Destination object key.
     * @param string $mimeType      Validated MIME type.
     */
    public function upload(
        string $temporaryPath,
        string $objectKey,
        string $mimeType
    ): string;

    /**
     * Generates a temporary authenticated URL for a private object.
     */
    public function createTemporaryUrl(
        string $objectKey,
        int $expirySeconds = 900
    ): string;

    /**
     * Deletes an object from storage.
     */
    public function delete(string $objectKey): bool;
}