<?php

declare(strict_types=1);

namespace App\Services\Sms;

/**
 * Development and QA SMS provider.
 *
 * The SMS is written to the CodeIgniter log and is not sent externally.
 */
final class LogSmsProvider implements SmsProviderInterface
{
    public function send(
        SmsMessage $message
    ): SmsSendResult {
        log_message(
            'debug',
            'SMS provider log: mobile={mobile}, message={message}',
            [
                'mobile' => $message->mobileNumber,
                'message' => $message->message,
            ]
        );

        return SmsSendResult::success(
            'local-log'
        );
    }
}