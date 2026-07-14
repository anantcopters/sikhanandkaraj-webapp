<?php

declare(strict_types=1);

namespace App\Services\Sms;

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
    ) {
    }

    public function send(
        SmsMessage $message
    ): SmsSendResult {
        try {
            /**
             * The real SMS API call is made here.
             */
            $response = $this->httpClient->post(
                $this->apiUrl,
                [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',

                        /**
                         * Change this header according to the provider.
                         *
                         * MSG91, Twilio and other vendors use different
                         * authorization formats.
                         */
                        'Authorization' =>
                            'Bearer ' . $this->apiKey,
                    ],

                    'json' => $this->buildPayload(
                        $message
                    ),

                    'connect_timeout' => 5,
                    'timeout' => 15,
                    'http_errors' => false,
                ]
            );

            $statusCode = $response->getStatusCode();

            $responseBody = json_decode(
                $response->getBody(),
                true
            );

            if (
                $statusCode < 200
                || $statusCode >= 300
            ) {
                log_message(
                    'error',
                    'SMS provider HTTP error: status={status}, response={response}',
                    [
                        'status' => $statusCode,
                        'response' => $response->getBody(),
                    ]
                );

                return SmsSendResult::failure(
                    'SMS provider returned HTTP '
                        . $statusCode
                        . '.'
                );
            }

            /**
             * Adjust these response keys for the chosen provider.
             */
            $providerMessageId = is_array($responseBody)
                ? (
                    $responseBody['message_id']
                    ?? $responseBody['request_id']
                    ?? $responseBody['sid']
                    ?? null
                )
                : null;

            return SmsSendResult::success(
                is_scalar($providerMessageId)
                    ? (string) $providerMessageId
                    : null
            );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'SMS provider request failed: {message}',
                [
                    'message' => $exception->getMessage(),
                ]
            );

            return SmsSendResult::failure(
                'Unable to connect to the SMS provider.'
            );
        }
    }

    /**
     * Generic request payload.
     *
     * Change this method according to the selected provider's API.
     *
     * @return array<string, mixed>
     */
    private function buildPayload(
        SmsMessage $message
    ): array {
        return [
            'sender_id' => $this->senderId,
            'mobile' => $message->mobileNumber,
            'message' => $message->message,
            'template_id' => $message->templateId,
            'variables' => $message->variables,
        ];
    }
}