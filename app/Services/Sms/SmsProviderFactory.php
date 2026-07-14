<?php

declare(strict_types=1);

namespace App\Services\Sms;

use RuntimeException;

/**
 * Creates the SMS provider configured for the current environment.
 */
final class SmsProviderFactory
{
    public static function create(): SmsProviderInterface
    {
        $provider = strtolower(
            trim((string) env('sms.provider', 'log'))
        );

        return match ($provider) {
            /**
             * Development and QA.
             */
            'log' => new LogSmsProvider(),

            /**
             * Production HTTP provider.
             */
            'http' => new HttpSmsProvider(
                service('curlrequest'),
                trim((string) env('sms.apiUrl')),
                trim((string) env('sms.apiKey')),
                trim((string) env('sms.senderId'))
            ),

            default => throw new RuntimeException(
                'Unsupported SMS provider: '
                    . $provider
            ),
        };
    }
}