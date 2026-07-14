<?php

declare(strict_types=1);

namespace App\Services\Sms;

/**
 * Immutable SMS message passed to an SMS provider.
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
        public array $variables = []
    ) {
    }
}