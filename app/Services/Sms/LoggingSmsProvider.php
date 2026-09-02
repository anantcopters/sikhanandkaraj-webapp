<?php

declare(strict_types=1);

namespace App\Services\Sms;

use App\Models\SmsDeliveryLogModel;
use DateTimeImmutable;
use DateTimeZone;
use Throwable;

/**
 * Decorates the configured SMS provider with centralized operational logging.
 *
 * This keeps:
 *
 * - Registration;
 * - Member OTP Login;
 * - Password Reset / Setup;
 * - SAK Volunteer Login;
 * - future transactional SMS
 *
 * completely unaware of SMS operational persistence.
 *
 * SMS logging is best-effort. A logging failure must never change the actual
 * provider result returned to the business workflow.
 */
final class LoggingSmsProvider implements SmsProviderInterface
{
    public function __construct(
        private readonly SmsProviderInterface $provider,
        private readonly SmsDeliveryLogModel $deliveryLogModel,
        private readonly string $providerName
    ) {}

    public function send(
        SmsMessage $message
    ): SmsSendResult {
        /*
         * Let the real provider remain authoritative for whether the SMS
         * request succeeded.
         */
        $result =
            $this
            ->provider
            ->send(
                $message
            );

        /*
         * Operational logging must never convert a successful SMS into a
         * failed business workflow.
         */
        try {
            $this->record(
                $message,
                $result
            );
        } catch (Throwable $exception) {
            service(
                'applicationErrorLogger'
            )->exception(
                $exception
            );
        }

        return $result;
    }

    private function record(
        SmsMessage $message,
        SmsSendResult $result
    ): void {
        $now =
            (
                new DateTimeImmutable(
                    'now',
                    new DateTimeZone(
                        'UTC'
                    )
                )
            )->format(
                'Y-m-d H:i:s'
            );

        $mobileNumber =
            $this->normalizeMobile(
                $message
                    ->mobileNumber
            );

        if ($mobileNumber === '') {
            /*
             * Retain a safe operational marker rather than throwing after the
             * underlying provider has already returned its result.
             */
            $mobileNumber =
                'UNKNOWN';
        }

        $this
            ->deliveryLogModel
            ->insert(
                [
                    'message_type' =>
                    mb_substr(
                        mb_strtoupper(
                            trim(
                                $message
                                    ->messageType
                            )
                        ),
                        0,
                        50
                    ),

                    'recipient_mobile' =>
                    mb_substr(
                        $mobileNumber,
                        0,
                        20
                    ),

                    'provider' =>
                    mb_substr(
                        mb_strtoupper(
                            trim(
                                $this
                                    ->providerName
                            )
                        ),
                        0,
                        30
                    ),

                    'provider_message_id' =>
                    $result
                        ->providerMessageId
                        !== null
                        ? mb_substr(
                            trim(
                                $result
                                    ->providerMessageId
                            ),
                            0,
                            255
                        )
                        : null,

                    'status' =>
                    $result
                        ->successful
                        ? SmsDeliveryLogModel
                        ::STATUS_SENT
                        : SmsDeliveryLogModel
                        ::STATUS_FAILED,

                    /*
                     * Store only the safe application/provider error returned
                     * through SmsSendResult.
                     *
                     * Raw provider payloads and SMS bodies are never persisted.
                     */
                    'error_message' =>
                    !$result
                        ->successful
                        && $result
                        ->errorMessage
                        !== null
                        ? mb_substr(
                            trim(
                                $result
                                    ->errorMessage
                            ),
                            0,
                            500
                        )
                        : null,

                    'sent_at' =>
                    $result
                        ->successful
                        ? $now
                        : null,

                    'failed_at' =>
                    !$result
                        ->successful
                        ? $now
                        : null,
                ]
            );
    }

    private function normalizeMobile(
        string $mobileNumber
    ): string {
        $digits =
            preg_replace(
                '/\D+/',
                '',
                $mobileNumber
            );

        return is_string(
            $digits
        )
            ? $digits
            : '';
    }
}
