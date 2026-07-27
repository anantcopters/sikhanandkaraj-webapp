<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Stores global, non-sensitive application settings.
 *
 * Environment-specific values may override matching properties through
 * the .env file by using the "site.propertyName" format.
 */
final class Site extends BaseConfig
{
    /**
     * Public application name displayed throughout the website.
     */
    public string $name = 'SikhAnandKaraj';

    /**
     * Short application name used where space is limited.
     */
    public string $shortName = 'SAK';

    /**
     * Public-facing application tagline.
     */
    public string $tagline = 'Meaningful connections within the Sikh community.';

    /**
     * Public support email address.
     *
     * This is not a secret, but it may differ between environments.
     */
    public string $supportEmail = 'info@sikhanandkaraj.com';

    /**
     * Default number of records returned by list endpoints.
     */
    public int $defaultPageSize = 20;

    /**
     * Maximum page size accepted by REST API list endpoints.
     */
    public int $maximumPageSize = 100;

    /**
     * Default expiry, in seconds, for temporary private-media URLs.
     */
    public int $temporaryMediaUrlExpiry = 900;

    /**
     * Maximum accepted image size in bytes.
     *
     * Current value: 5 MB.
     */
    public int $maximumImageSize = 5 * 1024 * 1024;

    /**
     * Maximum accepted video size in bytes.
     *
     * Current value: 100 MB.
     */
    public int $maximumVideoSize = 100 * 1024 * 1024;

    /**
     * MIME types accepted for profile images.
     *
     * @var list<string>
     */
    public array $allowedImageMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    /**
     * MIME types accepted for profile videos.
     *
     * @var list<string>
     */
    public array $allowedVideoMimeTypes = [
        'video/mp4',
        'video/quicktime',
        'video/webm',
    ];
}