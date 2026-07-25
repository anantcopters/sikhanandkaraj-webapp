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

    public int $profileUrlTtlSeconds;

    public int $galleryUrlTtlSeconds;

    public int $profileMaxFiles;

    public int $profileMaxSizeKb;

    public int $minimumWidth;

    public int $minimumHeight;

    public int $maximumWidth;

    public int $maximumHeight;

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

        $this->profileUrlTtlSeconds = max(
            60,
            (int) env(
                'memberMedia.profileUrlTtlSeconds',
                900
            )
        );

        $this->galleryUrlTtlSeconds = max(
            60,
            (int) env(
                'memberMedia.galleryUrlTtlSeconds',
                900
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
            (int) env('memberMedia.minimumWidth', 400)
        );

        $this->minimumHeight = max(
            1,
            (int) env('memberMedia.minimumHeight', 400)
        );

        $this->maximumWidth = max(
            $this->minimumWidth,
            (int) env('memberMedia.maximumWidth', 8000)
        );

        $this->maximumHeight = max(
            $this->minimumHeight,
            (int) env('memberMedia.maximumHeight', 8000)
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
