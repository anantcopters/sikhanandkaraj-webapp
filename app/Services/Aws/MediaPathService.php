<?php

declare(strict_types=1);

namespace App\Services\Aws;

use InvalidArgumentException;

/**
 * Produces predictable, non-personally-identifiable S3 object keys.
 */
final class MediaPathService
{
    /**
     * @return array{
     *     original:string,
     *     medium:string,
     *     thumbnail:string
     * }
     */
    public function profilePhotoPaths(
        string $uuid,
        string $sourceExtension
    ): array {
        $uuid = strtolower(trim($uuid));
        $sourceExtension = strtolower(
            trim($sourceExtension)
        );

        if (!$this->isUuid($uuid)) {
            throw new InvalidArgumentException(
                'A valid media UUID is required.'
            );
        }

        if (!in_array(
            $sourceExtension,
            ['jpg', 'png', 'webp'],
            true
        )) {
            throw new InvalidArgumentException(
                'Unsupported profile-photo extension.'
            );
        }

        return [
            'original' =>
            'members/profile/original/'
                . $uuid
                . '.'
                . $sourceExtension,

            'medium' =>
            'members/profile/medium/'
                . $uuid
                . '.webp',

            'thumbnail' =>
            'members/profile/thumbnail/'
                . $uuid
                . '.webp',
        ];
    }

    private function isUuid(string $uuid): bool
    {
        return preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-'
                . '[1-5][0-9a-f]{3}-'
                . '[89ab][0-9a-f]{3}-'
                . '[0-9a-f]{12}$/',
            $uuid
        ) === 1;
    }
}
