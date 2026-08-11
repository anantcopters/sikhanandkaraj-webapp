<?php

declare(strict_types=1);

namespace App\Controllers\Development;

use App\Controllers\BaseController;
use Aws\CloudFront\CloudFrontClient;
use Aws\Credentials\CredentialProvider;
use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;
use Throwable;

/**
 * Development-only test controller for private member-media infrastructure.
 *
 * This controller verifies:
 *
 * 1. Local AWS shared-credentials profile loading.
 * 2. S3 authentication.
 * 3. S3 PutObject permission.
 * 4. S3 HeadObject permission.
 * 5. CloudFront signed URL generation.
 * 6. CloudFront delivery of the private S3 object.
 * 7. S3 DeleteObject permission.
 *
 * This controller must never be available outside the development environment.
 */
final class MemberMediaTestController extends BaseController
{
    /**
     * Test object MIME type.
     */
    private const TEST_CONTENT_TYPE = 'text/plain; charset=utf-8';

    /**
     * Run the complete member-media integration test.
     */
    public function index(): ResponseInterface
    {
        if (ENVIRONMENT !== 'development') {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Page not found.',
                ]);
        }

        $startedAt = microtime(true);

        /** @var array<string, array<string, mixed>> $tests */
        $tests = [];

        $objectKey = sprintf(
            'development-tests/%s/member-media-test-%s.txt',
            date('Y-m-d'),
            bin2hex(random_bytes(12))
        );

        $bucket = '';
        $s3Client = null;
        $objectUploaded = false;
        $signedUrl = null;

        try {
            $configuration = $this->loadConfiguration();

            $bucket = $configuration['bucket'];

            $tests['configuration'] = $this->passedTest(
                'Required member-media configuration is present.',
                [
                    'region'                  => $configuration['region'],
                    'bucket'                  => $configuration['bucket'],
                    'profile'                 => $configuration['profile'],
                    'credentials_file'        =>
                    $configuration['credentialsFile'],
                    'cloudfront_domain'       =>
                    $configuration['cloudFrontDomain'],
                    'cloudfront_key_pair_id'  =>
                    $configuration['cloudFrontKeyPairId'],
                    'signed_url_ttl_seconds'  =>
                    $configuration['signedUrlTtl'],
                ]
            );

            $credentialsProvider = $this->createCredentialsProvider(
                $configuration['profile'],
                $configuration['credentialsFile']
            );

            /*
             * Resolve the credentials now so authentication failures are reported
             * separately from S3 operation failures.
             */
            $credentials = $credentialsProvider()->wait();

            $tests['credentials'] = $this->passedTest(
                'AWS credentials profile loaded successfully.',
                [
                    'profile'       => $configuration['profile'],
                    'access_key_id' => $this->maskAccessKey(
                        $credentials->getAccessKeyId()
                    ),
                ]
            );

            $s3Client = new S3Client([
                'version'     => 'latest',
                'region'      => $configuration['region'],
                'credentials' => $credentialsProvider,
            ]);

            /*
             * GetBucketLocation confirms that the SDK can authenticate and that
             * the IAM identity has access to the configured bucket.
             */
            $locationResult = $s3Client->getBucketLocation([
                'Bucket' => $bucket,
            ]);

            $reportedLocation = (string) (
                $locationResult->get('LocationConstraint') ?? ''
            );

            /*
             * AWS returns an empty location for the historic us-east-1 region.
             */
            if ($reportedLocation === '') {
                $reportedLocation = 'us-east-1';
            }

            $tests['bucket_access'] = $this->passedTest(
                'S3 bucket is accessible.',
                [
                    'bucket'          => $bucket,
                    'bucket_location' => $reportedLocation,
                ]
            );

            $testBody = implode(PHP_EOL, [
                'Sikhanandkaraj member-media integration test',
                'Generated at: ' . date(DATE_ATOM),
                'Environment: ' . ENVIRONMENT,
                'Object key: ' . $objectKey,
            ]);

            $putResult = $s3Client->putObject([
                'Bucket'      => $bucket,
                'Key'         => $objectKey,
                'Body'        => $testBody,
                'ContentType' => self::TEST_CONTENT_TYPE,

                /*
                 * Do not set ACL. The bucket uses Bucket Owner Enforced and
                 * public access remains blocked.
                 */
                'Metadata' => [
                    'purpose'     => 'development-integration-test',
                    'environment' => ENVIRONMENT,
                ],
            ]);

            $objectUploaded = true;

            $tests['s3_upload'] = $this->passedTest(
                'Test object uploaded privately to S3.',
                [
                    'object_key' => $objectKey,
                    'etag'       => trim(
                        (string) $putResult->get('ETag'),
                        '"'
                    ),
                    'version_id' => $putResult->get('VersionId'),
                ]
            );

            $headResult = $s3Client->headObject([
                'Bucket' => $bucket,
                'Key'    => $objectKey,
            ]);

            $tests['s3_exists'] = $this->passedTest(
                'Uploaded object exists in S3.',
                [
                    'content_type'   => $headResult->get('ContentType'),
                    'content_length' => $headResult->get('ContentLength'),
                    'last_modified'  => $headResult->get('LastModified')
                        ? $headResult
                        ->get('LastModified')
                        ->format(DATE_ATOM)
                        : null,
                ]
            );

            $signedUrl = $this->generateSignedUrl(
                $configuration['cloudFrontDomain'],
                $objectKey,
                $configuration['cloudFrontKeyPairId'],
                $configuration['cloudFrontPrivateKeyPath'],
                $configuration['signedUrlTtl']
            );

            $tests['cloudfront_signed_url'] = $this->passedTest(
                'CloudFront signed URL generated successfully.',
                [
                    'expires_in_seconds' =>
                    $configuration['signedUrlTtl'],
                    'signed_url' => $signedUrl,
                ]
            );

            /*
             * Perform an HTTP request through CloudFront. This verifies:
             *
             * - CloudFront URL signing
             * - CloudFront trusted key group
             * - CloudFront OAC
             * - S3 bucket policy
             * - Actual private-object delivery
             */
            $cloudFrontResult = $this->testCloudFrontUrl(
                $signedUrl,
                $testBody
            );

            $tests['cloudfront_delivery'] = $this->passedTest(
                'CloudFront returned the expected private object.',
                $cloudFrontResult
            );
        } catch (AwsException $exception) {
            $tests['failure'] = $this->failedTest(
                'AWS request failed.',
                $this->formatAwsException($exception)
            );
        } catch (Throwable $exception) {
            $tests['failure'] = $this->failedTest(
                'Member-media test failed.',
                [
                    'exception' => $exception::class,
                    'message'   => $exception->getMessage(),
                ]
            );
        } finally {
            /*
             * Always attempt cleanup after a successful upload, including when
             * CloudFront validation fails.
             */
            if (
                $objectUploaded
                && $s3Client instanceof S3Client
                && $bucket !== ''
            ) {
                try {
                    $s3Client->deleteObject([
                        'Bucket' => $bucket,
                        'Key'    => $objectKey,
                    ]);

                    $tests['s3_delete'] = $this->passedTest(
                        'Temporary S3 test object deleted.',
                        [
                            'object_key' => $objectKey,
                        ]
                    );
                } catch (Throwable $cleanupException) {
                    $tests['s3_delete'] = $this->failedTest(
                        'Temporary object could not be deleted.',
                        [
                            'object_key' => $objectKey,
                            'exception'  => $cleanupException::class,
                            'message'    => $cleanupException->getMessage(),
                        ]
                    );
                }
            }
        }

        $allPassed = $this->allTestsPassed($tests);

        return $this->response
            ->setStatusCode(
                $allPassed
                    ? ResponseInterface::HTTP_OK
                    : ResponseInterface::HTTP_INTERNAL_SERVER_ERROR
            )
            ->setJSON([
                'status' => $allPassed ? 'success' : 'error',
                'message' => $allPassed
                    ? 'All member-media integration tests passed.'
                    : 'One or more member-media integration tests failed.',
                'environment'         => ENVIRONMENT,
                'temporary_object_key' => $objectKey,
                'signed_url'          => $signedUrl,
                'temporary_object_deleted' =>
                isset($tests['s3_delete'])
                    && $tests['s3_delete']['status'] === 'pass',
                'duration_ms' => round(
                    (microtime(true) - $startedAt) * 1000,
                    2
                ),
                'tests' => $tests,
            ]);
    }

    /**
     * Load and validate configuration values from the environment.
     *
     * @return array{
     *     region: string,
     *     bucket: string,
     *     profile: string,
     *     credentialsFile: string,
     *     cloudFrontDomain: string,
     *     cloudFrontKeyPairId: string,
     *     cloudFrontPrivateKeyPath: string,
     *     signedUrlTtl: int
     * }
     */
    private function loadConfiguration(): array
    {
        $region = trim(
            (string) env('memberMedia.awsRegion', 'ap-south-1')
        );

        $bucket = trim(
            (string) env('memberMedia.awsBucket', '')
        );

        $profile = trim(
            (string) env(
                'memberMedia.awsProfile',
                'sikhanandkaraj-local'
            )
        );

        $credentialsFile = $this->normaliseWindowsPath(
            trim(
                (string) env(
                    'memberMedia.awsCredentialsFile',
                    ''
                )
            )
        );

        $cloudFrontDomain = rtrim(
            trim(
                (string) env(
                    'memberMedia.cloudFrontDomain',
                    ''
                )
            ),
            '/'
        );

        $cloudFrontKeyPairId = trim(
            (string) env(
                'memberMedia.cloudFrontKeyPairId',
                ''
            )
        );

        $cloudFrontPrivateKeyPath = $this->normaliseWindowsPath(
            trim(
                (string) env(
                    'memberMedia.cloudFrontPrivateKeyPath',
                    ''
                )
            )
        );

        $signedUrlTtl = (int) env(
            'memberMedia.signedUrlTtl',
            300
        );

        $missing = [];

        $requiredValues = [
            'memberMedia.awsRegion' =>
            $region,
            'memberMedia.awsBucket' =>
            $bucket,
            'memberMedia.awsProfile' =>
            $profile,
            'memberMedia.awsCredentialsFile' =>
            $credentialsFile,
            'memberMedia.cloudFrontDomain' =>
            $cloudFrontDomain,
            'memberMedia.cloudFrontKeyPairId' =>
            $cloudFrontKeyPairId,
            'memberMedia.cloudFrontPrivateKeyPath' =>
            $cloudFrontPrivateKeyPath,
        ];

        foreach ($requiredValues as $key => $value) {
            if ($value === '') {
                $missing[] = $key;
            }
        }

        if ($missing !== []) {
            throw new RuntimeException(
                'Missing environment values: '
                    . implode(', ', $missing)
            );
        }

        if (! is_file($credentialsFile)) {
            throw new RuntimeException(
                sprintf(
                    'AWS credentials file does not exist: %s',
                    $credentialsFile
                )
            );
        }

        if (! is_readable($credentialsFile)) {
            throw new RuntimeException(
                sprintf(
                    'AWS credentials file is not readable by Apache/PHP: %s',
                    $credentialsFile
                )
            );
        }

        if (! is_file($cloudFrontPrivateKeyPath)) {
            throw new RuntimeException(
                sprintf(
                    'CloudFront private key does not exist: %s',
                    $cloudFrontPrivateKeyPath
                )
            );
        }

        if (! is_readable($cloudFrontPrivateKeyPath)) {
            throw new RuntimeException(
                sprintf(
                    'CloudFront private key is not readable by Apache/PHP: %s',
                    $cloudFrontPrivateKeyPath
                )
            );
        }

        if ($signedUrlTtl < 60 || $signedUrlTtl > 3600) {
            throw new RuntimeException(
                'memberMedia.signedUrlTtl must be between 60 and 3600 seconds.'
            );
        }

        if (
            ! str_starts_with(
                $cloudFrontDomain,
                'https://'
            )
        ) {
            throw new RuntimeException(
                'memberMedia.cloudFrontDomain must start with https://.'
            );
        }

        return [
            'region'                   => $region,
            'bucket'                   => $bucket,
            'profile'                  => $profile,
            'credentialsFile'          => $credentialsFile,
            'cloudFrontDomain'         => $cloudFrontDomain,
            'cloudFrontKeyPairId'      => $cloudFrontKeyPairId,
            'cloudFrontPrivateKeyPath' => $cloudFrontPrivateKeyPath,
            'signedUrlTtl'             => $signedUrlTtl,
        ];
    }

    /**
     * Create a deferred AWS shared-credentials provider.
     */
    private function createCredentialsProvider(
        string $profile,
        string $credentialsFile
    ): callable {
        return CredentialProvider::memoize(
            CredentialProvider::ini(
                $profile,
                $credentialsFile
            )
        );
    }

    /**
     * Generate a signed CloudFront URL.
     */
    private function generateSignedUrl(
        string $cloudFrontDomain,
        string $objectKey,
        string $keyPairId,
        string $privateKeyPath,
        int $ttlSeconds
    ): string {
        $cloudFront = new CloudFrontClient([
            'version' => 'latest',
            'region'  => 'us-east-1',
        ]);

        $resourceUrl = sprintf(
            '%s/%s',
            $cloudFrontDomain,
            implode(
                '/',
                array_map(
                    'rawurlencode',
                    explode('/', $objectKey)
                )
            )
        );

        return $cloudFront->getSignedUrl([
            'url'         => $resourceUrl,
            'expires'     => time() + $ttlSeconds,
            'private_key' => $privateKeyPath,
            'key_pair_id' => $keyPairId,
        ]);
    }

    /**
     * Request the signed URL and compare its response with the uploaded body.
     *
     * @return array<string, mixed>
     */
    private function testCloudFrontUrl(
        string $signedUrl,
        string $expectedBody
    ): array {
        $curl = curl_init();

        if ($curl === false) {
            throw new RuntimeException(
                'Unable to initialise cURL.'
            );
        }

        curl_setopt_array($curl, [
            CURLOPT_URL            => $signedUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT      =>
            'Sikhanandkaraj-MemberMedia-Test/1.0',
        ]);

        $responseBody = curl_exec($curl);

        if ($responseBody === false) {
            $error = curl_error($curl);
            curl_close($curl);

            throw new RuntimeException(
                'CloudFront request failed: ' . $error
            );
        }

        $httpStatus = (int) curl_getinfo(
            $curl,
            CURLINFO_RESPONSE_CODE
        );

        $contentType = (string) curl_getinfo(
            $curl,
            CURLINFO_CONTENT_TYPE
        );

        $totalTime = (float) curl_getinfo(
            $curl,
            CURLINFO_TOTAL_TIME
        );

        curl_close($curl);

        if ($httpStatus !== ResponseInterface::HTTP_OK) {
            throw new RuntimeException(
                sprintf(
                    'CloudFront returned HTTP %d. Response: %s',
                    $httpStatus,
                    mb_substr((string) $responseBody, 0, 500)
                )
            );
        }

        if (! hash_equals($expectedBody, (string) $responseBody)) {
            throw new RuntimeException(
                'CloudFront response did not match the uploaded object.'
            );
        }

        return [
            'http_status'         => $httpStatus,
            'content_type'        => $contentType,
            'response_bytes'      => strlen((string) $responseBody),
            'response_matches_s3' => true,
            'request_time_ms'     => round($totalTime * 1000, 2),
        ];
    }

    /**
     * Convert AWS exceptions to a safe development response.
     *
     * Access keys and secret values are never returned.
     *
     * @return array<string, mixed>
     */
    private function formatAwsException(
        AwsException $exception
    ): array {
        return [
            'aws_error_code'    => $exception->getAwsErrorCode(),
            'aws_error_type'    => $exception->getAwsErrorType(),
            'aws_error_message' => $exception->getAwsErrorMessage(),
            'status_code'       => $exception->getStatusCode(),
            'request_id'        => $exception->getAwsRequestId(),
            'message'           => $exception->getMessage(),
        ];
    }

    /**
     * Normalise a Windows filesystem path.
     */
    private function normaliseWindowsPath(
        string $path
    ): string {
        return str_replace('\\', '/', $path);
    }

    /**
     * Mask an AWS access-key ID before returning it in the response.
     */
    private function maskAccessKey(
        string $accessKeyId
    ): string {
        $length = strlen($accessKeyId);

        if ($length <= 8) {
            return str_repeat('*', $length);
        }

        return substr($accessKeyId, 0, 4)
            . str_repeat('*', $length - 8)
            . substr($accessKeyId, -4);
    }

    /**
     * Create a successful test result.
     *
     * @param array<string, mixed> $details
     *
     * @return array<string, mixed>
     */
    private function passedTest(
        string $message,
        array $details = []
    ): array {
        return [
            'status'  => 'pass',
            'message' => $message,
            'details' => $details,
        ];
    }

    /**
     * Create a failed test result.
     *
     * @param array<string, mixed> $details
     *
     * @return array<string, mixed>
     */
    private function failedTest(
        string $message,
        array $details = []
    ): array {
        return [
            'status'  => 'fail',
            'message' => $message,
            'details' => $details,
        ];
    }

    /**
     * Determine whether every recorded test passed.
     *
     * @param array<string, array<string, mixed>> $tests
     */
    private function allTestsPassed(
        array $tests
    ): bool {
        if ($tests === []) {
            return false;
        }

        foreach ($tests as $test) {
            if (($test['status'] ?? null) !== 'pass') {
                return false;
            }
        }

        return true;
    }
}
