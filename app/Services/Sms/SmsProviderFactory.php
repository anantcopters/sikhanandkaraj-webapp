<?php

declare(strict_types=1);

namespace App\Services\Sms;

use RuntimeException;

/**
 * Creates the SMS provider configured for the current deployment.
 *
 * External SMS delivery is deliberately prohibited outside production.
 */
final class SmsProviderFactory
{
    public static function create(): SmsProviderInterface
    {
        $deployment =
            mb_strtolower(
                trim(
                    (string) env(
                        'APP_DEPLOYMENT',
                        'development'
                    )
                )
            );

        /*
         * Development and QA never contact mTalkz.
         *
         * OTP_FIXED_VALUE=0000 is already handled centrally by OtpGenerator.
         * LogSmsProvider preserves the complete application flow without
         * consuming SMS credits or sending real messages.
         */
        if ($deployment !== 'production') {
            return new LogSmsProvider();
        }

        $provider =
            mb_strtolower(
                trim(
                    (string) env(
                        'sms.provider',
                        'mtalkz'
                    )
                )
            );

        return match ($provider) {
            'mtalkz' =>
            new MtalkzSmsProvider(
                service(
                    'curlrequest'
                ),

                trim(
                    (string) env(
                        'sms.apiUrl'
                    )
                ),

                trim(
                    (string) env(
                        'sms.apiKey'
                    )
                ),

                trim(
                    (string) env(
                        'sms.senderId'
                    )
                )
            ),

            /*
             * Keep log available as an explicit production-safe fallback for
             * maintenance/testing, but production configuration should normally
             * use mtalkz.
             */
            'log' =>
            new LogSmsProvider(),

            default =>
            throw new RuntimeException(
                'Unsupported SMS provider: '
                    . $provider
            ),
        };
    }
}
