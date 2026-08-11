<?php

declare(strict_types=1);

namespace App\Services\FieldOfficer;

use App\Models\FieldOfficerLoginOtpModel;
use App\Models\FieldOfficerModel;
use App\Services\Sms\SmsMessage;
use App\Services\Sms\SmsProviderInterface;
use App\Support\IndianMobileNormalizer;
use App\Support\OtpGenerator;
use CodeIgniter\Database\BaseConnection;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;
use Throwable;

final class FieldOfficerLoginService
{
    private const OTP_LENGTH = 4;

    private const OTP_EXPIRY_MINUTES = 3;

    private const RESEND_COOLDOWN_SECONDS =
    120;

    private const VERIFY_ATTEMPT_LIMIT = 5;

    private const SEND_LIMIT_PER_DAY = 5;

    private const SEND_WINDOW_HOURS = 24;

    public function __construct(
        private readonly FieldOfficerModel
        $fieldOfficerModel,

        private readonly FieldOfficerLoginOtpModel
        $otpModel,

        private readonly BaseConnection
        $database,

        private readonly SmsProviderInterface
        $smsProvider
    ) {}

    public function requestOtp(
        string $mobileNumber
    ): FieldOfficerLoginResult {
        $normalizedMobile =
            IndianMobileNormalizer::normalize(
                $mobileNumber
            );

        if ($normalizedMobile === null) {
            return FieldOfficerLoginResult
                ::failure(
                    'Enter a valid 10-digit Indian mobile number.'
                );
        }

        $localMobile = substr(
            $normalizedMobile,
            -10
        );

        /*
         * Do not reveal whether the mobile exists but is inactive.
         */
        $fieldOfficer =
            $this->fieldOfficerModel
            ->findActiveByMobile(
                $localMobile
            );

        if (!is_array($fieldOfficer)) {
            return $this
                ->invalidLoginResult();
        }

        return $this->issueOtp(
            $fieldOfficer,
            $normalizedMobile
        );
    }

    public function resendOtp(
        int $fieldOfficerId
    ): FieldOfficerLoginResult {
        $fieldOfficer =
            $this->fieldOfficerModel
            ->findActiveById(
                $fieldOfficerId
            );

        if (!is_array($fieldOfficer)) {
            return FieldOfficerLoginResult
                ::failure(
                    'The SAK Volunteer login request is no longer valid.'
                );
        }

        $normalizedMobile =
            IndianMobileNormalizer::normalize(
                (string) (
                    $fieldOfficer['mobile_number'] ?? ''
                )
            );

        if ($normalizedMobile === null) {
            return FieldOfficerLoginResult
                ::failure(
                    'The SAK Volunteer login request is no longer valid.'
                );
        }

        return $this->issueOtp(
            $fieldOfficer,
            $normalizedMobile
        );
    }

    public function pendingExpiryTimestamp(
        int $fieldOfficerId
    ): ?int {
        $otp = $this->otpModel
            ->findLatestPending(
                $fieldOfficerId
            );

        if (!is_array($otp)) {
            return null;
        }

        return $this->parseTimestamp(
            (string) (
                $otp['expires_at']
                ?? ''
            )
        );
    }

    public function verifyOtp(
        int $fieldOfficerId,
        string $submittedOtp
    ): FieldOfficerLoginResult {
        if (
            preg_match(
                '/^[0-9]{4}$/',
                $submittedOtp
            ) !== 1
        ) {
            return FieldOfficerLoginResult
                ::failure(
                    'Please enter the complete four-digit OTP.'
                );
        }

        /*
         * Recheck ACTIVE status immediately before verification.
         *
         * An administrator may have deactivated the FO after
         * OTP issuance.
         */
        $fieldOfficer =
            $this->fieldOfficerModel
            ->findActiveById(
                $fieldOfficerId
            );

        if (!is_array($fieldOfficer)) {
            return FieldOfficerLoginResult
                ::failure(
                    'The SAK Volunteer login request is no longer valid.'
                );
        }

        $this->database->transBegin();

        try {
            $otp = $this->otpModel
                ->findLatestPending(
                    $fieldOfficerId
                );

            if (!is_array($otp)) {
                $this->database
                    ->transRollback();

                return FieldOfficerLoginResult
                    ::failure(
                        'The OTP is no longer valid. '
                            . 'Please request a new OTP.'
                    );
            }

            $otpId = (int) (
                $otp['id']
                ?? 0
            );

            if ($otpId <= 0) {
                throw new RuntimeException(
                    'The SAK Volunteer OTP record '
                        . 'contains an invalid identifier.'
                );
            }

            $expiresAt =
                $this->parseTimestamp(
                    (string) (
                        $otp['expires_at']
                        ?? ''
                    )
                );

            if (
                $expiresAt === null
                || $expiresAt <= time()
            ) {
                $this->otpModel->update(
                    $otpId,
                    [
                        'status' =>
                        FieldOfficerLoginOtpModel
                        ::STATUS_EXPIRED,
                    ]
                );

                $this->commitOrFail();

                return FieldOfficerLoginResult
                    ::failure(
                        'The OTP has expired. '
                            . 'Please request a new OTP.'
                    );
            }

            $attemptCount = (int) (
                $otp['attempt_count']
                ?? 0
            );

            if (
                $attemptCount
                >= self::VERIFY_ATTEMPT_LIMIT
            ) {
                $this->otpModel->update(
                    $otpId,
                    [
                        'status' =>
                        FieldOfficerLoginOtpModel
                        ::STATUS_CANCELLED,
                    ]
                );

                $this->commitOrFail();

                return FieldOfficerLoginResult
                    ::failure(
                        'Too many incorrect attempts. '
                            . 'Please request a new OTP.'
                    );
            }

            if (
                !password_verify(
                    $submittedOtp,
                    (string) (
                        $otp['otp_hash']
                        ?? ''
                    )
                )
            ) {
                $this->otpModel
                    ->incrementAttemptCount(
                        $otpId
                    );

                $remaining = max(
                    0,
                    self::VERIFY_ATTEMPT_LIMIT
                        - $attemptCount
                        - 1
                );

                if ($remaining === 0) {
                    $this->otpModel->update(
                        $otpId,
                        [
                            'status' =>
                            FieldOfficerLoginOtpModel
                            ::STATUS_CANCELLED,
                        ]
                    );
                }

                $this->commitOrFail();

                return FieldOfficerLoginResult
                    ::failure(
                        $remaining > 0
                            ? 'Incorrect OTP. '
                            . $remaining
                            . ' attempt(s) remaining.'
                            : 'Too many incorrect attempts. '
                            . 'Please request a new OTP.'
                    );
            }

            /*
             * ACTIVE status is checked again after OTP comparison.
             */
            $fieldOfficer =
                $this->fieldOfficerModel
                ->findActiveById(
                    $fieldOfficerId
                );

            if (!is_array($fieldOfficer)) {
                $this->otpModel->update(
                    $otpId,
                    [
                        'status' =>
                        FieldOfficerLoginOtpModel
                        ::STATUS_CANCELLED,
                    ]
                );

                $this->commitOrFail();

                return FieldOfficerLoginResult
                    ::failure(
                        'The SAK Volunteer account is not active.'
                    );
            }

            $verifiedAt = (
                new DateTimeImmutable(
                    'now',
                    new DateTimeZone('UTC')
                )
            )->format(
                'Y-m-d H:i:sP'
            );

            $updated =
                $this->otpModel->update(
                    $otpId,
                    [
                        'status' =>
                        FieldOfficerLoginOtpModel
                        ::STATUS_VERIFIED,

                        'verified_at' =>
                        $verifiedAt,
                    ]
                );

            if ($updated === false) {
                throw new RuntimeException(
                    'The OTP verification could not be completed.'
                );
            }

            $this->otpModel
                ->cancelPending(
                    $fieldOfficerId
                );

            $this->commitOrFail();

            /*
             * Auxiliary metadata must never invalidate
             * successful authentication.
             */
            try {
                $this->fieldOfficerModel
                    ->recordSuccessfulLogin(
                        $fieldOfficerId
                    );
            } catch (Throwable $exception) {
                log_message(
                    'warning',
                    'Unable to record SAK Volunteer '
                        . 'last login: {message}',
                    [
                        'message' =>
                        $exception
                            ->getMessage(),
                    ]
                );
            }

            return FieldOfficerLoginResult
                ::authenticated(
                    $fieldOfficer
                );
        } catch (Throwable $exception) {
            $this->database
                ->transRollback();

            throw $exception;
        }
    }

    /**
     * @param array<string, mixed> $fieldOfficer
     */
    private function issueOtp(
        array $fieldOfficer,
        string $normalizedMobile
    ): FieldOfficerLoginResult {
        $fieldOfficerId = (int) (
            $fieldOfficer['id']
            ?? 0
        );

        if ($fieldOfficerId <= 0) {
            return $this
                ->invalidLoginResult();
        }

        $now = new DateTimeImmutable(
            'now',
            new DateTimeZone('UTC')
        );

        $windowStart = $now
            ->sub(
                new DateInterval(
                    'PT'
                        . self::SEND_WINDOW_HOURS
                        . 'H'
                )
            )
            ->format(
                'Y-m-d H:i:sP'
            );

        if (
            $this->otpModel
            ->countDeliveredSince(
                $fieldOfficerId,
                $windowStart
            )
            >= self::SEND_LIMIT_PER_DAY
        ) {
            return FieldOfficerLoginResult
                ::failure(
                    'The OTP request limit has been reached. '
                        . 'Please try again later.'
                );
        }

        $pending = $this->otpModel
            ->findLatestPending(
                $fieldOfficerId
            );

        if (is_array($pending)) {
            $createdAt =
                $this->parseTimestamp(
                    (string) (
                        $pending['created_at']
                        ?? ''
                    )
                );

            if ($createdAt !== null) {
                $elapsed =
                    time() - $createdAt;

                if (
                    $elapsed
                    < self::RESEND_COOLDOWN_SECONDS
                ) {
                    $retryAfter =
                        self::RESEND_COOLDOWN_SECONDS
                        - $elapsed;

                    return FieldOfficerLoginResult
                        ::failure(
                            sprintf(
                                'Please wait %d second%s '
                                    . 'before requesting another OTP.',
                                $retryAfter,
                                $retryAfter === 1
                                    ? ''
                                    : 's'
                            )
                        );
                }
            }
        }

        $otp = OtpGenerator::generate(
            self::OTP_LENGTH
        );

        $otpHash = password_hash(
            $otp,
            PASSWORD_DEFAULT
        );

        if (!is_string($otpHash)) {
            throw new RuntimeException(
                'The SAK Volunteer OTP could not be secured.'
            );
        }

        $expiresAt = $now
            ->add(
                new DateInterval(
                    'PT'
                        . self::OTP_EXPIRY_MINUTES
                        . 'M'
                )
            )
            ->format(
                'Y-m-d H:i:sP'
            );

        $this->database->transBegin();

        try {
            $this->otpModel
                ->cancelPending(
                    $fieldOfficerId
                );

            $otpId = $this->otpModel
                ->insert(
                    [
                        'field_officer_id' =>
                        $fieldOfficerId,

                        'mobile_number' =>
                        $normalizedMobile,

                        'otp_hash' =>
                        $otpHash,

                        'expires_at' =>
                        $expiresAt,

                        'attempt_count' =>
                        0,

                        'status' =>
                        FieldOfficerLoginOtpModel
                        ::STATUS_PENDING,

                        'verified_at' =>
                        null,
                    ],
                    true
                );

            if (!is_numeric($otpId)) {
                throw new RuntimeException(
                    'The SAK Volunteer OTP record '
                        . 'could not be created.'
                );
            }

            $this->commitOrFail();
        } catch (Throwable $exception) {
            $this->database
                ->transRollback();

            throw $exception;
        }

        try {
            $smsResult =
                $this->smsProvider
                ->send(
                    new SmsMessage(
                        mobileNumber: $normalizedMobile,

                        message: 'Your Sikhanandkaraj SAK Volunteer '
                            . 'login OTP is '
                            . $otp
                            . '. It is valid for '
                            . self::OTP_EXPIRY_MINUTES
                            . ' minutes.',

                        templateId: trim(
                            (string) env(
                                'sms.fieldOfficerLoginOtpTemplateId'
                            )
                        ) ?: null,

                        variables: [
                            'otp' =>
                            $otp,

                            'expiry_minutes' =>
                            (string)
                            self::OTP_EXPIRY_MINUTES,
                        ]
                    )
                );
        } catch (Throwable $exception) {
            $this->markDeliveryFailed(
                (int) $otpId
            );

            log_message(
                'error',
                'SAK Volunteer OTP SMS failed: {message}',
                [
                    'message' =>
                    $exception->getMessage(),
                ]
            );

            return FieldOfficerLoginResult
                ::failure(
                    'We could not send the OTP. '
                        . 'Please try again.'
                );
        }

        if (!$smsResult->successful) {
            $this->markDeliveryFailed(
                (int) $otpId
            );

            return FieldOfficerLoginResult
                ::failure(
                    'We could not send the OTP. '
                        . 'Please try again.'
                );
        }

        return FieldOfficerLoginResult
            ::otpIssued(
                $fieldOfficerId,
                'An OTP has been sent to your mobile number.'
            );
    }

    private function invalidLoginResult(): FieldOfficerLoginResult
    {
        return FieldOfficerLoginResult
            ::failure(
                'This mobile number is not eligible '
                    . 'for SAK Volunteer login.'
            );
    }

    private function markDeliveryFailed(
        int $otpId
    ): void {
        if ($otpId <= 0) {
            return;
        }

        try {
            $this->otpModel->update(
                $otpId,
                [
                    'status' =>
                    FieldOfficerLoginOtpModel
                    ::STATUS_DELIVERY_FAILED,
                ]
            );
        } catch (Throwable $exception) {
            log_message(
                'critical',
                'Unable to mark SAK Volunteer OTP '
                    . 'delivery failure: {message}',
                [
                    'message' =>
                    $exception->getMessage(),
                ]
            );
        }
    }

    private function commitOrFail(): void
    {
        if (
            $this->database
            ->transStatus() === false
        ) {
            throw new RuntimeException(
                'The SAK Volunteer authentication '
                    . 'transaction failed.'
            );
        }

        $this->database
            ->transCommit();
    }

    private function parseTimestamp(
        string $value
    ): ?int {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        try {
            return (
                new DateTimeImmutable(
                    $value
                )
            )->getTimestamp();
        } catch (Throwable) {
            return null;
        }
    }
}
