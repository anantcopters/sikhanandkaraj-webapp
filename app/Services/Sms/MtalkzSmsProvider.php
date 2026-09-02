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
                    $this->providerErrorMessage(
                        $decodedResponse
                    )
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
     * Detect an explicit mTalkz provider-level failure.
     *
     * mTalkz returns HTTP 200 for both successful and rejected API requests,
     * therefore the JSON status is authoritative.
     *
     * Known response contract:
     *
     * Success:
     *
     * {
     *     "status": "OK",
     *     "data": [...],
     *     "msgid": "9813100982",
     *     "message": "message Submitted successfully"
     * }
     *
     * Failure:
     *
     * {
     *     "status": "AZQ02",
     *     "message": "Invalid Api Key"
     * }
     *
     * @param mixed $decodedResponse
     */
    private function responseIndicatesFailure(
        mixed $decodedResponse,
        string $responseBody
    ): bool {
        /*
     * A successful provider request must return a usable JSON response.
     *
     * Empty/non-JSON responses cannot safely be treated as accepted SMS.
     */
        if (
            $responseBody === ''
            || !is_array(
                $decodedResponse
            )
        ) {
            return true;
        }

        $status =
            mb_strtoupper(
                trim(
                    (string) (
                        $decodedResponse['status']
                        ?? ''
                    )
                )
            );

        /*
     * mTalkz documents OK as the successful API status.
     *
     * Everything else is treated as rejected rather than trying to infer
     * success from HTTP 200.
     */
        return $status !== 'OK';
    }

    /**
     * Return a safe provider error for operational logging.
     *
     * Only documented mTalkz status/message metadata is retained. The raw
     * provider response is deliberately not returned because future provider
     * responses could contain recipient or message information.
     *
     * @param mixed $decodedResponse
     */
    private function providerErrorMessage(
        mixed $decodedResponse
    ): string {
        if (!is_array($decodedResponse)) {
            return 'mTalkz returned an invalid response.';
        }

        $status =
            mb_strtoupper(
                trim(
                    (string) (
                        $decodedResponse['status']
                        ?? ''
                    )
                )
            );

        $message =
            trim(
                (string) (
                    $decodedResponse['message']
                    ?? ''
                )
            );

        if (
            $status !== ''
            && $message !== ''
        ) {
            return mb_substr(
                $status
                    . ' - '
                    . $message,
                0,
                500
            );
        }

        if ($status !== '') {
            return mb_substr(
                'mTalkz status: '
                    . $status,
                0,
                500
            );
        }

        return 'mTalkz rejected the SMS request.';
    }

    /**
     * Extract the provider reference returned by mTalkz.
     *
     * mTalkz returns:
     *
     * - msgid: request/campaign reference;
     * - data[n].id: individual submitted SMS reference.
     *
     * Current application SMS calls contain one recipient, therefore prefer the
     * individual message ID when available and fall back to msgid.
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
            $decodedResponse['data'][0]['id']
            ?? $decodedResponse['msgid']
            ?? null;

        if (!is_scalar($providerMessageId)) {
            return null;
        }

        $providerMessageId =
            trim(
                (string) $providerMessageId
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
