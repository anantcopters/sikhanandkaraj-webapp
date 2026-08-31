<?php

declare(strict_types=1);

namespace App\Services\Sms;

use App\Support\InfrastructureErrorContext;
use CodeIgniter\HTTP\CURLRequest;
use Throwable;

/**
 * Production SMS provider for mTalkz.
 *
 * Provider-specific HTTP details remain isolated here so OTP, registration,
 * authentication and future transactional SMS services continue depending
 * only on SmsProviderInterface.
 */
final class MtalkzSmsProvider implements SmsProviderInterface
{
    public function __construct(
        private readonly CURLRequest $httpClient,
        private readonly string $apiUrl,
        private readonly string $apiKey,
        private readonly string $senderId
    ) {}

    /**
     * Send one SMS through mTalkz.
     */
    public function send(
        SmsMessage $message
    ): SmsSendResult {
        if (!$this->configurationValid()) {
            service(
                'applicationErrorLogger'
            )->error(
                'mTalkz SMS provider configuration is incomplete.',
                InfrastructureErrorContext::forOperation(
                    operation: 'mtalkz_sms_provider_configuration',

                    component: self::class,

                    method: __FUNCTION__,

                    additionalContext: [
                        'api_url_configured' =>
                        trim(
                            $this->apiUrl
                        ) !== '',

                        'api_key_configured' =>
                        trim(
                            $this->apiKey
                        ) !== '',

                        'sender_id_configured' =>
                        trim(
                            $this->senderId
                        ) !== '',
                    ]
                ),
                'critical'
            );

            return SmsSendResult::failure(
                'SMS provider is not configured.'
            );
        }

        $mobileNumber =
            $this->normalizeMobileNumber(
                $message->mobileNumber
            );

        if ($mobileNumber === null) {
            return SmsSendResult::failure(
                'SMS recipient mobile number is invalid.'
            );
        }

        try {
            /*
             * mTalkz expects the API key inside the JSON body for this API.
             *
             * Do not log this payload because it contains both the API key
             * and the OTP/message content.
             */
            $response =
                $this
                ->httpClient
                ->post(
                    $this->apiUrl,
                    [
                        'headers' => [
                            'Accept' =>
                            'application/json',

                            'Content-Type' =>
                            'application/json',
                        ],

                        'json' => [
                            'apikey' =>
                            $this->apiKey,

                            'senderid' =>
                            $this->senderId,

                            'number' =>
                            $mobileNumber,

                            'message' =>
                            $message->message,

                            'format' =>
                            'json',
                        ],

                        'connect_timeout' =>
                        5,

                        'timeout' =>
                        15,

                        'http_errors' =>
                        false,
                    ]
                );

            $statusCode =
                $response
                ->getStatusCode();

            $responseBody =
                trim(
                    (string) $response
                        ->getBody()
                );

            if (
                $statusCode < 200
                || $statusCode >= 300
            ) {
                $this->logProviderFailure(
                    $message,
                    $statusCode,
                    $responseBody
                );

                return SmsSendResult::failure(
                    'mTalkz returned HTTP '
                        . $statusCode
                        . '.'
                );
            }

            /*
             * mTalkz may return provider-level failure information inside an
             * otherwise successful HTTP response.
             *
             * Reject known failure responses rather than treating every HTTP
             * 200 response as successful.
             */
            $decodedResponse =
                json_decode(
                    $responseBody,
                    true
                );

            if (
                $this->responseIndicatesFailure(
                    $decodedResponse,
                    $responseBody
                )
            ) {
                $this->logProviderFailure(
                    $message,
                    $statusCode,
                    $responseBody
                );

                return SmsSendResult::failure(
                    'mTalkz rejected the SMS request.'
                );
            }

            return SmsSendResult::success(
                $this->providerMessageId(
                    $decodedResponse
                )
            );
        } catch (Throwable $exception) {
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'error',
                InfrastructureErrorContext::forOperation(
                    operation: 'mtalkz_sms_provider_request',

                    component: self::class,

                    method: __FUNCTION__,

                    additionalContext: [
                        'mobile_suffix' =>
                        InfrastructureErrorContext
                            ::mobileSuffix(
                                $message
                                    ->mobileNumber
                            ),
                    ]
                )
            );

            return SmsSendResult::failure(
                'Unable to connect to the SMS provider.'
            );
        }
    }

    /**
     * mTalkz examples use the local 10-digit Indian mobile number.
     *
     * Existing application services may provide either:
     *
     * - 9876543210
     * - 919876543210
     * - +919876543210
     *
     * Normalize all three to the provider's 10-digit format.
     */
    private function normalizeMobileNumber(
        string $mobileNumber
    ): ?string {
        $digits =
            preg_replace(
                '/\D+/',
                '',
                $mobileNumber
            );

        if (!is_string($digits)) {
            return null;
        }

        if (
            strlen($digits) === 12
            && str_starts_with(
                $digits,
                '91'
            )
        ) {
            $digits =
                substr(
                    $digits,
                    2
                );
        }

        if (
            preg_match(
                '/^[6-9][0-9]{9}$/',
                $digits
            ) !== 1
        ) {
            return null;
        }

        return $digits;
    }

    /**
     * Detect explicit provider-level failure responses.
     *
     * Keep this deliberately conservative. Unknown successful response
     * structures should not be rejected merely because mTalkz changes or
     * extends response metadata.
     *
     * @param mixed $decodedResponse
     */
    private function responseIndicatesFailure(
        mixed $decodedResponse,
        string $responseBody
    ): bool {
        if ($responseBody === '') {
            return true;
        }

        if (!is_array($decodedResponse)) {
            $normalizedBody =
                mb_strtolower(
                    $responseBody
                );

            return str_contains(
                $normalizedBody,
                'error'
            )
                || str_contains(
                    $normalizedBody,
                    'failed'
                )
                || str_contains(
                    $normalizedBody,
                    'invalid'
                );
        }

        if (
            isset(
                $decodedResponse['status']
            )
        ) {
            $status =
                mb_strtolower(
                    trim(
                        (string)
                        $decodedResponse['status']
                    )
                );

            if (
                in_array(
                    $status,
                    [
                        'error',
                        'failed',
                        'failure',
                        'invalid',
                    ],
                    true
                )
            ) {
                return true;
            }
        }

        if (
            isset(
                $decodedResponse['success']
            )
            && $decodedResponse['success']
            === false
        ) {
            return true;
        }

        return false;
    }

    /**
     * Extract the provider reference when mTalkz returns one.
     *
     * @param mixed $decodedResponse
     */
    private function providerMessageId(
        mixed $decodedResponse
    ): ?string {
        if (!is_array($decodedResponse)) {
            return null;
        }

        $providerMessageId =
            $decodedResponse['message_id']
            ?? $decodedResponse['messageid']
            ?? $decodedResponse['request_id']
            ?? $decodedResponse['requestid']
            ?? $decodedResponse['id']
            ?? null;

        if (!is_scalar($providerMessageId)) {
            return null;
        }

        $providerMessageId =
            trim(
                (string)
                $providerMessageId
            );

        return $providerMessageId !== ''
            ? mb_substr(
                $providerMessageId,
                0,
                255
            )
            : null;
    }

    private function logProviderFailure(
        SmsMessage $message,
        int $statusCode,
        string $responseBody
    ): void {
        service(
            'applicationErrorLogger'
        )->error(
            'mTalkz SMS provider rejected an SMS request.',
            InfrastructureErrorContext::forOperation(
                operation: 'mtalkz_sms_provider_response',

                component: self::class,

                method: __FUNCTION__,

                additionalContext: [
                    'http_status' =>
                    $statusCode,

                    'mobile_suffix' =>
                    InfrastructureErrorContext
                        ::mobileSuffix(
                            $message
                                ->mobileNumber
                        ),

                    /*
                     * Do not log the provider response itself because provider
                     * responses may echo mobile/message information.
                     */
                    'response_size_bytes' =>
                    strlen(
                        $responseBody
                    ),
                ]
            ),
            'error'
        );
    }

    private function configurationValid(): bool
    {
        return trim(
            $this->apiUrl
        ) !== ''
            && trim(
                $this->apiKey
            ) !== ''
            && trim(
                $this->senderId
            ) !== '';
    }
}
