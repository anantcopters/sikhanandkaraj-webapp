<?php

declare(strict_types=1);

namespace App\Services\Sms;

use App\Support\InfrastructureErrorContext;
use CodeIgniter\HTTP\CURLRequest;
use Throwable;

/**
 * Generic HTTP-based SMS provider.
 *
 * Update buildPayload(), headers and response parsing when selecting
 * the actual provider.
 */
final class HttpSmsProvider implements SmsProviderInterface
{
    public function __construct(
        private readonly CURLRequest $httpClient,
        private readonly string $apiUrl,
        private readonly string $apiKey,
        private readonly string $senderId
    ) {}

    /**
     * Send one SMS message.
     *
     * The method returns a result instead of throwing, so it owns failure
     * logging.
     */
    public function send(
        SmsMessage $message
    ): SmsSendResult {
        if (!$this->configurationValid()) {
            service(
                'applicationErrorLogger'
            )->error(
                'SMS provider configuration is incomplete.',
                InfrastructureErrorContext::forOperation(
                    operation: 'sms_provider_configuration',

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

        try {
            $response = $this->httpClient
                ->post(
                    $this->apiUrl,
                    [
                        'headers' => [
                            'Accept' =>
                            'application/json',

                            'Content-Type' =>
                            'application/json',

                            /*
                             * Change this header according to the provider.
                             */
                            'Authorization' =>
                            'Bearer '
                                . $this->apiKey,
                        ],

                        'json' =>
                        $this->buildPayload(
                            $message
                        ),

                        'connect_timeout' =>
                        5,

                        'timeout' =>
                        15,

                        'http_errors' =>
                        false,
                    ]
                );

            $statusCode =
                $response->getStatusCode();

            $responseBody =
                (string) $response
                    ->getBody();

            $decodedResponse = json_decode(
                $responseBody,
                true
            );

            if (
                $statusCode < 200
                || $statusCode >= 300
            ) {
                service(
                    'applicationErrorLogger'
                )->error(
                    'SMS provider returned an unsuccessful HTTP response.',
                    InfrastructureErrorContext::forOperation(
                        operation: 'sms_provider_http_response',

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

                            'template_id' =>
                            mb_substr(
                                trim(
                                    $message
                                        ->templateId
                                ),
                                0,
                                100
                            ),

                            'response_size_bytes' =>
                            strlen(
                                $responseBody
                            ),
                        ]
                    ),
                    'error'
                );

                return SmsSendResult::failure(
                    'SMS provider returned HTTP '
                        . $statusCode
                        . '.'
                );
            }

            /*
             * Adjust these keys for the selected provider.
             */
            $providerMessageId =
                is_array(
                    $decodedResponse
                )
                ? (
                    $decodedResponse['message_id']
                    ?? $decodedResponse['request_id']
                    ?? $decodedResponse['sid']
                    ?? null
                )
                : null;

            return SmsSendResult::success(
                is_scalar(
                    $providerMessageId
                )
                    ? mb_substr(
                        (string) $providerMessageId,
                        0,
                        255
                    )
                    : null
            );
        } catch (Throwable $exception) {
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'error',
                InfrastructureErrorContext::forOperation(
                    operation: 'sms_provider_request',

                    component: self::class,

                    method: __FUNCTION__,

                    additionalContext: [
                        'mobile_suffix' =>
                        InfrastructureErrorContext
                            ::mobileSuffix(
                                $message
                                    ->mobileNumber
                            ),

                        'template_id' =>
                        mb_substr(
                            trim(
                                $message
                                    ->templateId
                            ),
                            0,
                            100
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
     * Generic request payload.
     *
     * Change this method according to the selected provider API.
     *
     * @return array<string, mixed>
     */
    private function buildPayload(
        SmsMessage $message
    ): array {
        return [
            'sender_id' =>
            $this->senderId,

            'mobile' =>
            $message->mobileNumber,

            'message' =>
            $message->message,

            'template_id' =>
            $message->templateId,

            'variables' =>
            $message->variables,
        ];
    }

    /**
     * Confirm mandatory provider settings.
     */
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
