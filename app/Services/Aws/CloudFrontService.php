<?php

declare(strict_types=1);

namespace App\Services\Aws;

use Aws\CloudFront\CloudFrontClient;
use Config\MemberMedia;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Generates short-lived CloudFront signed media URLs.
 *
 * This low-level service throws failures to the caller. The workflow service
 * that has member/photo context owns logging.
 */
final class CloudFrontService
{
    private string $privateKey;

    public function __construct(
        private readonly CloudFrontClient $client,
        private readonly MemberMedia $config
    ) {
        $this->privateKey =
            $this->loadPrivateKey(
                $config
                    ->cloudFrontPrivateKeyPath
            );
    }

    /**
     * Generate one short-lived signed CloudFront URL.
     */
    public function signedUrl(
        string $objectKey,
        int $ttlSeconds
    ): string {
        $normalizedObjectKey =
            $this->normalizeObjectKey(
                $objectKey
            );

        if ($normalizedObjectKey === '') {
            throw new InvalidArgumentException(
                'A CloudFront object key is required.'
            );
        }

        $domain = trim(
            $this->config
                ->cloudFrontDomain
        );

        $keyPairId = trim(
            $this->config
                ->cloudFrontKeyPairId
        );

        if (
            $domain === ''
            || $keyPairId === ''
        ) {
            throw new RuntimeException(
                'CloudFront signing configuration is incomplete.'
            );
        }

        $expiresAt = time()
            + max(
                60,
                $ttlSeconds
            );

        $resourceUrl = sprintf(
            'https://%s/%s',
            $domain,
            $this->encodeObjectKey(
                $normalizedObjectKey
            )
        );

        try {
            return $this->client
                ->getSignedUrl([
                    'url' =>
                    $resourceUrl,

                    'expires' =>
                    $expiresAt,

                    'private_key' =>
                    $this->privateKey,

                    'key_pair_id' =>
                    $keyPairId,
                ]);
        } catch (Throwable $exception) {
            /*
             * Do not log here. The caller has the member/photo context and
             * logs once if it swallows or converts this exception.
             */
            throw new RuntimeException(
                'The photo could not be displayed.',
                0,
                $exception
            );
        }
    }

    /**
     * Load and validate the CloudFront private signing key.
     */
    private function loadPrivateKey(
        string $path
    ): string {
        $resolvedPath = trim(
            $path
        );

        if (
            $resolvedPath === ''
            || !is_file(
                $resolvedPath
            )
            || !is_readable(
                $resolvedPath
            )
        ) {
            throw new RuntimeException(
                'CloudFront private signing key is unavailable.'
            );
        }

        $key = file_get_contents(
            $resolvedPath
        );

        if (
            $key === false
            || trim($key) === ''
            || (
                !str_contains(
                    $key,
                    'BEGIN PRIVATE KEY'
                )
                && !str_contains(
                    $key,
                    'BEGIN RSA PRIVATE KEY'
                )
            )
        ) {
            throw new RuntimeException(
                'CloudFront private signing key is invalid.'
            );
        }

        return $key;
    }

    /**
     * Normalize an object key without exposing or changing its hierarchy.
     */
    private function normalizeObjectKey(
        string $objectKey
    ): string {
        return ltrim(
            str_replace(
                '\\',
                '/',
                trim($objectKey)
            ),
            '/'
        );
    }

    /**
     * Safely encode every object-key path segment.
     */
    private function encodeObjectKey(
        string $objectKey
    ): string {
        $segments = explode(
            '/',
            $objectKey
        );

        $encodedSegments = array_map(
            static fn(
                string $segment
            ): string => rawurlencode(
                $segment
            ),
            $segments
        );

        return implode(
            '/',
            $encodedSegments
        );
    }
}
