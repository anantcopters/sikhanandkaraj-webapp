<?php

declare(strict_types=1);

namespace App\Services\Sms;

/**
 * Immutable SMS message passed to an SMS provider.
 *
 * messageType is operational metadata only.
 *
 * It identifies the class of SMS without requiring the delivery log to
 * persist or inspect the SMS body.
 */
final readonly class SmsMessage
{
    /**
     * @param array<string, string> $variables
     */
    public function __construct(
        public string $mobileNumber,
        public string $message,
        public ?string $templateId = null,
        public array $variables = [],
        public string $messageType = 'GENERAL'
    ) {}
}
