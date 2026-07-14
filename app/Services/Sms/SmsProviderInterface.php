<?php

declare(strict_types=1);

namespace App\Services\Sms;

/**
 * Every SMS provider must implement this contract.
 *
 * Registration and OTP business logic should depend only on this
 * interface, not directly on MSG91, Twilio or another vendor.
 */
interface SmsProviderInterface
{
    public function send(
        SmsMessage $message
    ): SmsSendResult;
}