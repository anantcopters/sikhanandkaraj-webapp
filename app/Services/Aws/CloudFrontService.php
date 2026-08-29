<?php

declare(strict_types=1);

namespace App\Services\Aws;

use Aws\CloudFront\CloudFrontClient;
use CodeIgniter\Cache\CacheInterface;
use Config\MemberMedia;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Generates short-lived CloudFront signed media URLs.
 *
 * This low-level service throws failures to the caller. The workflow service
 * that has member/photo context owns logging.
 *
 
 *
 * CloudFront RSA signing was confirmed by Membership-28 profiling to consume
 * approximately 21-22 ms for one Search result page.
 *
 * A signed CloudFront URL is not viewer-specific. Viewer authorization is
 * performed by the calling workflow before this service is invoked.
 *
 * Therefore an already-issued URL may safely be reused for the same object key
 * and TTL while it remains valid.
 */
final class CloudFrontService
{
    /**
     * Safety margin between cache expiry and actual CloudFront URL expiry.
     *
     * A cached URL must disappear before CloudFront itself considers it
     * expired.
     */
    private const CACHE_EXPIRY_SAFETY_SECONDS = 30;

    /**
     * Smallest useful cache lifetime.
     */
    private const MINIMUM_CACHE_SECONDS = 30;

    private string $privateKey;

    public function __construct(
        private readonly CloudFrontClient $client,
        private readonly MemberMedia $config,
        private readonly CacheInterface $cache
    ) {
        $this->privateKey =
            $this->loadPrivateKey(
                $config->cloudFrontPrivateKeyPath
            );
    }

    /**
     * Generate or reuse one short-lived signed CloudFront URL.
     *
     
     *
     * The cache key contains no private object-key text. Only a SHA-256 digest
     * of signing-relevant state is stored in the cache key.
     *
     * Cache lifetime is deliberately shorter than the actual signed URL
     * lifetime so a cached URL is never returned after CloudFront expiry.
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

        $domain =
            trim(
                $this->config->cloudFrontDomain
            );

        $keyPairId =
            trim(
                $this->config->cloudFrontKeyPairId
            );

        if (
            $domain === ''
            || $keyPairId === ''
        ) {
            throw new RuntimeException(
                'CloudFront signing configuration is incomplete.'
            );
        }

        /*
         * CloudFrontService already enforces a minimum 60-second signed URL
         * lifetime. Keep that existing contract unchanged.
         */
        $effectiveTtlSeconds =
            max(
                60,
                $ttlSeconds
            );

        /*
         * Cache entries expire before the signed URL itself.
         *
         * For the current defaults this gives approximately:
         *
         * thumbnail 600s -> cache 570s
         * medium    300s -> cache 270s
         * original  120s -> cache  90s
         */
        $cacheTtlSeconds =
            max(
                self::MINIMUM_CACHE_SECONDS,
                $effectiveTtlSeconds
                    - self::CACHE_EXPIRY_SAFETY_SECONDS
            );

        $cacheKey =
            $this->signedUrlCacheKey(
                $normalizedObjectKey,
                $effectiveTtlSeconds,
                $domain,
                $keyPairId
            );

        /*
         * Cache failures must never make member media unavailable.
         *
         * Signing remains the authoritative fallback.
         */
        try {
            $cachedUrl =
                $this->cache
                ->get(
                    $cacheKey
                );

            if (
                is_string($cachedUrl)
                && trim($cachedUrl) !== ''
            ) {
                return $cachedUrl;
            }
        } catch (Throwable) {
            /*
             * Fail open to normal signing.
             *
             * The caller still owns media-context logging if signing itself
             * fails.
             */
        }

        $expiresAt =
            time()
            + $effectiveTtlSeconds;

        $resourceUrl =
            sprintf(
                'https://%s/%s',
                $domain,
                $this->encodeObjectKey(
                    $normalizedObjectKey
                )
            );

        try {
            $signedUrl =
                $this->client
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
             * Do not log here. The caller has member/photo/document context and
             * logs once if it swallows or converts this exception.
             */
            throw new RuntimeException(
                'The photo could not be displayed.',
                0,
                $exception
            );
        }

        /*
         * Cache only successfully generated URLs.
         *
         * Cache failure is deliberately ignored because media delivery must not
         * depend on the cache backend.
         */
        try {
            $this->cache
                ->save(
                    $cacheKey,
                    $signedUrl,
                    $cacheTtlSeconds
                );
        } catch (Throwable) {
            // Normal signed URL remains valid for this request.
        }

        return $signedUrl;
    }

    /**
     * Build a cache key without exposing the private storage object key.
     *
     * Domain and Key Pair ID are included so environment/configuration changes
     * cannot accidentally reuse a URL generated under different CloudFront
     * signing configuration.
     */
    private function signedUrlCacheKey(
        string $objectKey,
        int $ttlSeconds,
        string $domain,
        string $keyPairId
    ): string {
        return 'cloudfront_signed_url_'
            . hash(
                'sha256',
                implode(
                    '|',
                    [
                        $this->config->environmentName,
                        $domain,
                        $keyPairId,
                        $objectKey,
                        (string) $ttlSeconds,
                    ]
                )
            );
    }

    /**
     * Load and validate the CloudFront private signing key.
     */
    private function loadPrivateKey(
        string $path
    ): string {
        $resolvedPath =
            trim(
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

        $key =
            file_get_contents(
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
                trim(
                    $objectKey
                )
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
        $segments =
            explode(
                '/',
                $objectKey
            );

        $encodedSegments =
            array_map(
                static fn(
                    string $segment
                ): string =>
                rawurlencode(
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
