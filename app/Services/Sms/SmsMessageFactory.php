<?php

declare(strict_types=1);

namespace App\Services\Sms;

/**
 * Creates standardized application SMS messages.
 *
 * SMS text approved with the provider/DLT must be defined centrally so
 * individual business services cannot accidentally send different wording.
 *
 * Future approved transactional SMS messages such as:
 *
 * - payment successful;
 * - Prelaunch Profile migrated to Member;
 *
 * should be added here only after their final approved SMS text is available.
 */
final class SmsMessageFactory
{
    private const OTP_TEMPLATE =
    'Your Sikhanandkaraj verification code is %s. '
        . 'Do not share this code with anyone. - KIRAT';

    private function __construct() {}

    /**
     * Build the standardized OTP SMS used by every OTP workflow.
     */
    public static function otp(
        string $mobileNumber,
        string $otp
    ): SmsMessage {
        $otp =
            trim(
                $otp
            );

        if (
            preg_match(
                '/^[0-9]{4}$/',
                $otp
            ) !== 1
        ) {
            throw new \InvalidArgumentException(
                'OTP SMS requires a four-digit verification code.'
            );
        }

        return new SmsMessage(
            mobileNumber: $mobileNumber,

            /*
             * The registered mTalkz/DLT template uses {#var#}.
             *
             * The actual SMS request contains the generated OTP in that
             * position.
             */
            message: sprintf(
                self::OTP_TEMPLATE,
                $otp
            ),

            variables: [
                'otp' =>
                $otp,
            ]
        );
    }
}
