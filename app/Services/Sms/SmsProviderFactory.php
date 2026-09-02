<?php

declare(strict_types=1);

namespace App\Services\Sms;

use App\Models\SmsDeliveryLogModel;
use RuntimeException;

/**
 * Creates the explicitly configured SMS provider.
 *
 * OTP generation and SMS delivery remain independent:
 *
 * Normal Development / QA:
 *
 * OTP_FIXED_VALUE=0000
 * sms.provider=log
 *
 * Development real SMS test:
 *
 * OTP_FIXED_VALUE=
 * sms.provider=mtalkz
 *
 * Production:
 *
 * OTP_FIXED_VALUE=
 * sms.provider=mtalkz
 *
 * Every configured provider is wrapped by LoggingSmsProvider so operational
 * logging is applied once at the common provider boundary.
 */
final class SmsProviderFactory
{
    public static function create(): SmsProviderInterface
    {
        /*
         * Fail safe to LOG.
         *
         * Missing SMS configuration must never unexpectedly consume credits
         * or send an external SMS.
         */
        $providerName =
            mb_strtolower(
                trim(
                    (string) env(
                        'sms.provider',
                        'log'
                    )
                )
            );

        $provider =
            match ($providerName) {
                'log' =>
                new LogSmsProvider(),

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

                default =>
                throw new RuntimeException(
                    'Unsupported SMS provider: '
                        . (
                            $providerName !== ''
                            ? $providerName
                            : '[empty]'
                        )
                ),
            };

        /*
         * Central operational logging decorator.
         *
         * Individual OTP/business services remain unchanged.
         */
        return new LoggingSmsProvider(
            $provider,
            new SmsDeliveryLogModel(
                db_connect()
            ),
            $providerName
        );
    }
}
