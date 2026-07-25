<?php

declare(strict_types=1);

namespace App\Services\Aws;

use Aws\CloudFront\CloudFrontClient;
use Config\MemberMedia;
use InvalidArgumentException;
use RuntimeException;

/**
 * Generates short-lived CloudFront signed media URLs.
 */
final class CloudFrontService
{
    private string $privateKey;

    public function __construct(
        private readonly CloudFrontClient $client,
        private readonly MemberMedia $config
    ) {
        $this->privateKey = $this->loadPrivateKey(
            $config->cloudFrontPrivateKeyPath
        );
    }

    public function signedUrl(
        string $objectKey,
        int $ttlSeconds
    ): string {
        $objectKey = $this->normalizeObjectKey(
            $objectKey
        );

        if ($objectKey === '') {
            throw new InvalidArgumentException(
                'A CloudFront object key is required.'
            );
        }

        $expiresAt = time() + max(60, $ttlSeconds);

        $resourceUrl = sprintf(
            'https://%s/%s',
            $this->config->cloudFrontDomain,
            $this->encodeObjectKey($objectKey)
        );

        try {
            return $this->client->getSignedUrl([
                'url' => $resourceUrl,
                'expires' => $expiresAt,
                'private_key' => $this->privateKey,
                'key_pair_id' =>
                $this->config->cloudFrontKeyPairId,
            ]);
        } catch (\Throwable $exception) {
            /*
             * Do not log the signed URL or private key.
             */
            log_message(
                'error',
                'CloudFront URL signing failed for '
                    . 'object {objectKey}: {message}',
                [
                    'objectKey' => $objectKey,
                    'message' => $exception->getMessage(),
                ]
            );

            throw new RuntimeException(
                'The photo could not be displayed.',
                0,
                $exception
            );
        }
    }

    private function loadPrivateKey(string $path): string
    {
        if (
            $path === ''
            || !is_file($path)
            || !is_readable($path)
        ) {
            throw new RuntimeException(
                'CloudFront private signing key '
                    . 'is unavailable.'
            );
        }

        $key = file_get_contents($path);

        if (
            $key === false
            || trim($key) === ''
            || !str_contains(
                $key,
                'BEGIN PRIVATE KEY'
            )
        ) {
            throw new RuntimeException(
                'CloudFront private signing key is invalid.'
            );
        }

        return $key;
    }

    private function normalizeObjectKey(
        string $objectKey
    ): string {
        return ltrim(
            str_replace('\\', '/', trim($objectKey)),
            '/'
        );
    }

    private function encodeObjectKey(
        string $objectKey
    ): string {
        $segments = explode('/', $objectKey);

        $segments = array_map(
            static fn(string $segment): string =>
            rawurlencode($segment),
            $segments
        );

        return implode('/', $segments);
    }
}
