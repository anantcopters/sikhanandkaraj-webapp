<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;
use RuntimeException;

/**
 * Member media and AWS configuration.
 *
 * No secret or environment-specific value is embedded in source code.
 */
final class MemberMedia extends BaseConfig
{
    public string $awsRegion;

    public string $s3Bucket;

    public string $awsAccessKey;

    public string $awsSecretKey;

    public string $cloudFrontDomain;

    public string $cloudFrontKeyPairId;

    public string $cloudFrontPrivateKeyPath;

    public string $applicationName;

    public string $environmentName;

    /**
     * Text written onto protected member-media variants.
     */
    public string $watermarkText;

    /**
     * Signed CloudFront URL lifetime for profile thumbnails.
     *
     * Used by dashboard, search, match cards and other compact
     * member-photo presentations.
     */
    public int $thumbnailUrlTtlSeconds;

    /**
     * Signed CloudFront URL lifetime for medium profile photographs.
     *
     * Used by full member-profile and member gallery views.
     */
    public int $mediumUrlTtlSeconds;

    /**
     * Signed CloudFront URL lifetime for administrator-only
     * original photographs.
     */
    public int $adminOriginalUrlTtlSeconds;

    public int $profileMaxFiles;

    public int $profileMaxSizeKb;

    /**
     * Supported server-verified source image MIME types.
     *
     * Members may upload JPEG or PNG files only.
     *
     * @var array<string, string>
     */
    public array $allowedImageMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    /**
     * Hard minimum dimensions accepted for a member photograph.
     *
     * A photograph below either dimension is rejected because it cannot
     * produce an acceptable profile/search image without upscaling.
     */
    public int $minimumWidth;

    public int $minimumHeight;

    /**
     * Recommended source dimensions shown to members.
     *
     * These values are guidance only. They are deliberately higher than
     * the enforced minimum and must never reject an otherwise valid photo.
     */
    public int $recommendedWidth;

    public int $recommendedHeight;

    public int $maximumWidth;

    public int $maximumHeight;

    public function __construct()
    {
        parent::__construct();

        $this->awsRegion = trim(
            (string) env('memberMedia.awsRegion', '')
        );

        $this->s3Bucket = trim(
            (string) env('memberMedia.s3Bucket', '')
        );

        $this->awsAccessKey = trim(
            (string) env('memberMedia.awsAccessKey', '')
        );

        $this->awsSecretKey = trim(
            (string) env('memberMedia.awsSecretKey', '')
        );

        $this->cloudFrontDomain = $this->normalizeDomain(
            (string) env('memberMedia.cloudFrontDomain', '')
        );

        $this->cloudFrontKeyPairId = trim(
            (string) env('memberMedia.cloudFrontKeyPairId', '')
        );

        $this->cloudFrontPrivateKeyPath = trim(
            (string) env(
                'memberMedia.cloudFrontPrivateKeyPath',
                ''
            )
        );

        $this->applicationName = trim(
            (string) env(
                'memberMedia.applicationName',
                'Sikhanandkaraj'
            )
        );

        $this->environmentName = trim(
            (string) env(
                'memberMedia.environmentName',
                ENVIRONMENT
            )
        );

        $this->watermarkText = trim(
            (string) env(
                'memberMedia.watermarkText',
                'Sikhanandkaraj.com'
            )
        );

        if ($this->watermarkText === '') {
            $this->watermarkText = 'Sikhanandkaraj.com';
        }

        $this->thumbnailUrlTtlSeconds = max(
            60,
            (int) env(
                'memberMedia.thumbnailUrlTtlSeconds',
                600
            )
        );

        $this->mediumUrlTtlSeconds = max(
            60,
            (int) env(
                'memberMedia.mediumUrlTtlSeconds',
                300
            )
        );

        $this->adminOriginalUrlTtlSeconds = max(
            60,
            (int) env(
                'memberMedia.adminOriginalUrlTtlSeconds',
                120
            )
        );

        $this->profileMaxFiles = min(
            5,
            max(
                1,
                (int) env(
                    'memberMedia.profileMaxFiles',
                    5
                )
            )
        );

        $this->profileMaxSizeKb = max(
            1,
            (int) env(
                'memberMedia.profileMaxSizeKb',
                10240
            )
        );

        $this->minimumWidth = max(
            1,
            (int) env(
                'memberMedia.minimumWidth',
                300
            )
        );

        $this->minimumHeight = max(
            1,
            (int) env(
                'memberMedia.minimumHeight',
                300
            )
        );

        /*
        * Recommended quality is intentionally not a validation constraint.
        */
        $this->recommendedWidth = max(
            $this->minimumWidth,
            (int) env(
                'memberMedia.recommendedWidth',
                600
            )
        );

        $this->recommendedHeight = max(
            $this->minimumHeight,
            (int) env(
                'memberMedia.recommendedHeight',
                600
            )
        );

        $this->maximumWidth = max(
            $this->minimumWidth,
            (int) env(
                'memberMedia.maximumWidth',
                8000
            )
        );

        $this->maximumHeight = max(
            $this->minimumHeight,
            (int) env(
                'memberMedia.maximumHeight',
                8000
            )
        );
    }

    /**
     * Fail early when media configuration is incomplete.
     */
    public function assertS3Configured(): void
    {
        $required = [
            'AWS region' => $this->awsRegion,
            'S3 bucket' => $this->s3Bucket,
        ];

        $this->assertRequiredValues($required);
    }

    public function assertCloudFrontConfigured(): void
    {
        $required = [
            'CloudFront domain' => $this->cloudFrontDomain,
            'CloudFront key-pair ID' =>
            $this->cloudFrontKeyPairId,
            'CloudFront private-key path' =>
            $this->cloudFrontPrivateKeyPath,
        ];

        $this->assertRequiredValues($required);
    }

    /**
     * @param array<string, string> $required
     */
    private function assertRequiredValues(array $required): void
    {
        foreach ($required as $label => $value) {
            if ($value === '') {
                throw new RuntimeException(
                    $label . ' is not configured.'
                );
            }
        }
    }

    private function normalizeDomain(string $domain): string
    {
        $domain = trim($domain);

        $domain = preg_replace(
            '#^https?://#i',
            '',
            $domain
        ) ?? '';

        return rtrim($domain, '/');
    }
}
