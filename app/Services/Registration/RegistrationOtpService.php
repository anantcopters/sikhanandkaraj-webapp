<?php

declare(strict_types=1);

namespace App\Services\Registration;

use App\Models\ContactVerificationModel;
use App\Models\UserContactModel;
use App\Models\UserModel;
use CodeIgniter\Database\BaseConnection;
use App\Services\Sms\SmsMessage;
use App\Services\Sms\SmsProviderInterface;
use DateInterval;
use DateTimeImmutable;
use RuntimeException;
use Throwable;
use DateTimeZone;

/**
 * Handles issuing, resending and verifying registration OTPs.
 */
final class RegistrationOtpService
{
    public function __construct(
        private readonly UserModel $userModel,
        private readonly UserContactModel $contactModel,
        private readonly ContactVerificationModel $verificationModel,
        private readonly BaseConnection $database,

        /**
         * SMS provider is injected so the OTP service does not know
         * whether MSG91, Twilio or another provider is being used.
         */
        private readonly SmsProviderInterface $smsProvider
    ) {}

    /**
     * Issue a registration OTP.
     *
     * The initial registration and every resend use this same method,
     * ensuring that all OTP sends are subject to one limit.
     */
    public function issue(
        int $mobileContactId
    ): RegistrationOtpResult {
        $this->database->transBegin();

        try {
            $contact = $this->contactModel->find(
                $mobileContactId
            );

            if (
                !is_array($contact)
                || (string) ($contact['contact_type'] ?? '')
                !== UserContactModel::TYPE_MOBILE
            ) {
                throw new RuntimeException(
                    'The mobile contact was not found.'
                );
            }

            if ($this->toBoolean(
                $contact['is_verified'] ?? false
            )) {
                $this->database->transRollback();

                return RegistrationOtpResult::failure(
                    'This mobile number has already been verified.'
                );
            }

            $limitResult = $this->checkSendLimit(
                $mobileContactId
            );

            if (!$limitResult->successful) {
                $this->database->transRollback();

                return $limitResult;
            }

            /**
             * Keep history rather than deleting old OTP rows.
             *
             * This is better for auditing, fraud investigation and
             * enforcing rate limits.
             */
            if (!$this->verificationModel
                ->cancelPendingForContact(
                    $mobileContactId,
                    ContactVerificationModel::PURPOSE_REGISTER
                )) {
                throw new RuntimeException(
                    'Unable to cancel the previous OTP.'
                );
            }

            /**
             * Development and QA environments use a fixed OTP
             * to simplify testing.
             *
             * Production always generates a cryptographically
             * secure random OTP.
             */
            $fixedOtp = trim((string) env('OTP_FIXED_VALUE'));

            $otp = $fixedOtp !== ''
                ? $fixedOtp
                : (string) random_int(1000, 9999);

            /**
             * Always generate and store OTP timestamps in UTC.
             *
             * The database value uses a timezone-free datetime format, so the timezone
             * must be explicitly controlled both when writing and reading it.
             */
            $utcTimezone = new DateTimeZone('UTC');

            $now = new DateTimeImmutable(
                'now',
                $utcTimezone
            );

            $expiresAt = $now->add(
                new DateInterval(
                    'PT' . OTP_EXPIRY_MINUTES . 'M'
                )
            );

            $verificationId = $this->verificationModel
                ->insert([
                    'user_contact_id' => $mobileContactId,
                    'purpose' =>
                    ContactVerificationModel::PURPOSE_REGISTER,
                    'otp_hash' => password_hash(
                        $otp,
                        PASSWORD_DEFAULT
                    ),
                    /**
                     * Include the UTC offset when writing to PostgreSQL.
                     *
                     * Without the offset PostgreSQL interprets the datetime using the
                     * database-session timezone, which may be Asia/Kolkata.
                     */
                    'expires_at' =>
                    $expiresAt->format('Y-m-d H:i:sP'),
                    'attempt_count' => 0,
                    'resend_count' => 0,
                    'status' =>
                    ContactVerificationModel::STATUS_PENDING,
                    'verified_at' => null,
                ], true);

            if (!is_numeric($verificationId)) {
                throw new RuntimeException(
                    'Unable to create the OTP record.'
                );
            }

            $this->commitOrFail();

            /**
             * Send the SMS only after the OTP record has been committed.
             *
             * This prevents sending an OTP that does not exist in the database.
             */
            $smsResult = $this->smsProvider->send(
                new SmsMessage(
                    mobileNumber: (string) $contact['normalized_value'],

                    message: 'Your Sikh Anand Karaj verification OTP is '
                        . $otp
                        . '. It is valid for '
                        . OTP_EXPIRY_MINUTES
                        . ' minutes.',

                    templateId: trim(
                        (string) env(
                            'sms.registrationTemplateId'
                        )
                    ) ?: null,

                    variables: [
                        'otp' => $otp,
                        'expiry_minutes' =>
                        (string) OTP_EXPIRY_MINUTES,
                    ]
                )
            );

            if (!$smsResult->successful) {
                /**
                 * OTP record already exists, but delivery failed.
                 *
                 * For now return an error to the user. Later, queue-based delivery
                 * can retry this automatically.
                 */
                log_message(
                    'error',
                    'Registration OTP SMS failed: contact_id={contactId}, error={error}',
                    [
                        'contactId' => $mobileContactId,
                        'error' =>
                        $smsResult->errorMessage
                            ?? 'Unknown SMS provider error',
                    ]
                );

                return RegistrationOtpResult::failure(
                    'We could not send the OTP. Please try again.'
                );
            }

            return RegistrationOtpResult::success(
                'OTP sent successfully.',
                $expiresAt->getTimestamp()
            );

            return RegistrationOtpResult::success(
                'OTP sent successfully.',
                $expiresAt->getTimestamp()
            );
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }
    }

    /**
     * Verify a submitted registration OTP.
     */
    public function verify(
        int $userId,
        int $mobileContactId,
        string $submittedOtp
    ): RegistrationOtpResult {
        if (!preg_match('/^\d{4}$/', $submittedOtp)) {
            return RegistrationOtpResult::failure(
                'Please enter a valid four-digit OTP.'
            );
        }

        $this->database->transBegin();

        try {
            $user = $this->userModel->find($userId);

            $contact = $this->contactModel->find(
                $mobileContactId
            );

            if (
                !is_array($user)
                || !is_array($contact)
                || (int) ($contact['user_id'] ?? 0) !== $userId
                || (string) ($contact['contact_type'] ?? '')
                !== UserContactModel::TYPE_MOBILE
            ) {
                throw new RuntimeException(
                    'The pending registration could not be found.'
                );
            }

            if (
                (string) ($user['account_status'] ?? '')
                !== 'PENDING'
            ) {
                $this->database->transRollback();

                return RegistrationOtpResult::failure(
                    'This registration is no longer pending.'
                );
            }

            $verification = $this->verificationModel
                ->findLatestPendingForContact(
                    $mobileContactId,
                    ContactVerificationModel::PURPOSE_REGISTER
                );

            if ($verification === null) {
                $this->database->transRollback();

                return RegistrationOtpResult::failure(
                    'The OTP is no longer valid. Please request a new OTP.'
                );
            }

            $expiresAt = $this->parseUtcTimestamp(
                (string) $verification['expires_at']
            );

            if (
                $expiresAt === null
                || $expiresAt <= time()
            ) {
                $this->verificationModel->markExpired(
                    (int) $verification['id']
                );

                $this->commitOrFail();

                return RegistrationOtpResult::failure(
                    'The OTP has expired. Please request a new OTP.'
                );
            }

            $attemptCount = (int) (
                $verification['attempt_count'] ?? 0
            );

            if (
                $attemptCount
                >= REGISTRATION_OTP_VERIFY_ATTEMPT_LIMIT
            ) {
                $this->verificationModel->update(
                    (int) $verification['id'],
                    [
                        'status' =>
                        ContactVerificationModel::STATUS_CANCELLED,
                    ]
                );

                $this->commitOrFail();

                return RegistrationOtpResult::failure(
                    'Too many incorrect attempts. Please request a new OTP.'
                );
            }

            $otpMatches = password_verify(
                $submittedOtp,
                (string) $verification['otp_hash']
            );

            if (!$otpMatches) {
                $this->verificationModel
                    ->incrementAttemptCount(
                        (int) $verification['id']
                    );

                $remainingAttempts = max(
                    0,
                    REGISTRATION_OTP_VERIFY_ATTEMPT_LIMIT
                        - $attemptCount
                        - 1
                );

                if ($remainingAttempts === 0) {
                    $this->verificationModel->update(
                        (int) $verification['id'],
                        [
                            'status' =>
                            ContactVerificationModel::STATUS_CANCELLED,
                        ]
                    );
                }

                $this->commitOrFail();

                return RegistrationOtpResult::failure(
                    $remainingAttempts > 0
                        ? 'Incorrect OTP. '
                        . $remainingAttempts
                        . ' attempt(s) remaining.'
                        : 'Too many incorrect attempts. '
                        . 'Please request a new OTP.'
                );
            }

            $verifiedAt = (new DateTimeImmutable(
                'now',
                new DateTimeZone('UTC')
            ))->format('Y-m-d H:i:sP');

            $userUpdated = $this->userModel->update(
                $userId,
                [
                    'account_status' => 'ACTIVE',
                ]
            );

            $contactUpdated = $this->contactModel->update(
                $mobileContactId,
                [
                    'is_verified' => true,
                    'verified_at' => $verifiedAt,
                ]
            );

            $otpUpdated = $this->verificationModel->update(
                (int) $verification['id'],
                [
                    'status' =>
                    ContactVerificationModel::STATUS_VERIFIED,
                    'verified_at' => $verifiedAt,
                ]
            );

            if (
                $userUpdated === false
                || $contactUpdated === false
                || $otpUpdated === false
            ) {
                throw new RuntimeException(
                    'Unable to complete OTP verification.'
                );
            }

            /**
             * Cancel any other pending OTP created by a concurrent
             * or previously interrupted request.
             */
            $this->verificationModel
                ->cancelPendingForContact(
                    $mobileContactId,
                    ContactVerificationModel::PURPOSE_REGISTER
                );

            $this->commitOrFail();

            return RegistrationOtpResult::success(
                'Your mobile number has been verified.'
            );
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }
    }

    /**
     * Read the current OTP expiry for page refreshes.
     */
    public function getPendingExpiryTimestamp(
        int $mobileContactId
    ): ?int {
        $verification = $this->verificationModel
            ->findLatestPendingForContact(
                $mobileContactId,
                ContactVerificationModel::PURPOSE_REGISTER
            );

        if ($verification === null) {
            return null;
        }

        $rawExpiresAt = $verification['expires_at'] ?? null;

        // log_message(
        //     'debug',
        //     'Raw OTP expiry: value={value}, type={type}, otp_id={otpId}',
        //     [
        //         'value' => is_scalar($rawExpiresAt)
        //             ? (string) $rawExpiresAt
        //             : json_encode($rawExpiresAt),
        //         'type' => get_debug_type($rawExpiresAt),
        //         'otpId' => $verification['id'] ?? 0,
        //     ]
        // );

        $timestamp = $this->parseUtcTimestamp(
            (string) $rawExpiresAt
        );

        // log_message(
        //     'debug',
        //     'Parsed OTP expiry timestamp: {timestamp}',
        //     [
        //         'timestamp' => $timestamp ?? 0,
        //     ]
        // );

        return $timestamp;
    }

    /**
     * Enforce three sends during a rolling 24-hour period.
     */
    private function checkSendLimit(
        int $mobileContactId
    ): RegistrationOtpResult {
        $sinceTimestamp = time() - DAY;

        $since = date(
            'Y-m-d H:i:s',
            $sinceTimestamp
        );

        $sendCount = $this->verificationModel
            ->countIssuedSince(
                $mobileContactId,
                ContactVerificationModel::PURPOSE_REGISTER,
                $since
            );

        if (
            $sendCount
            < REGISTRATION_OTP_DAILY_SEND_LIMIT
        ) {
            return RegistrationOtpResult::success(
                'OTP may be issued.'
            );
        }

        $oldest = $this->verificationModel
            ->findOldestIssuedSince(
                $mobileContactId,
                ContactVerificationModel::PURPOSE_REGISTER,
                $since
            );

        $oldestCreatedAt = isset($oldest['created_at'])
            ? $this->parseUtcTimestamp(
                (string) $oldest['created_at']
            )
            : null;

        $retryAfter = $oldestCreatedAt !== null
            ? $oldestCreatedAt + DAY
            : time() + DAY;

        return RegistrationOtpResult::failure(
            'Only three OTPs are allowed for this mobile number '
                . 'within 24 hours. You can request another OTP after '
                . date('d M Y, h:i A', $retryAfter)
                . '.',
            $retryAfter
        );
    }

    private function toBoolean(mixed $value): bool
    {
        return filter_var(
            $value,
            FILTER_VALIDATE_BOOLEAN
        );
    }

    private function commitOrFail(): void
    {
        if (!$this->database->transStatus()) {
            $this->database->transRollback();

            throw new RuntimeException(
                'The OTP transaction failed.'
            );
        }

        $this->database->transCommit();
    }

    /**
     * Convert a database datetime value into a Unix timestamp.
     *
     * Supports PostgreSQL values with:
     * - no timezone
     * - timezone offsets
     * - fractional seconds
     */
    private function parseUtcTimestamp(
        string $dateTime
    ): ?int {
        $dateTime = trim($dateTime);

        if ($dateTime === '') {
            return null;
        }

        $utc = new DateTimeZone('UTC');

        /**
         * Try the possible PostgreSQL datetime formats explicitly.
         */
        $formats = [
            'Y-m-d H:i:s.uP',
            'Y-m-d H:i:sP',
            'Y-m-d H:i:s.uO',
            'Y-m-d H:i:sO',
            'Y-m-d H:i:s.u',
            'Y-m-d H:i:s',
        ];

        foreach ($formats as $format) {
            $parsed = DateTimeImmutable::createFromFormat(
                '!' . $format,
                $dateTime,
                $utc
            );

            if ($parsed instanceof DateTimeImmutable) {
                return $parsed->getTimestamp();
            }
        }

        /**
         * Final fallback for valid ISO/PostgreSQL date strings.
         */
        try {
            $parsed = new DateTimeImmutable(
                $dateTime,
                $utc
            );

            return $parsed->getTimestamp();
        } catch (\Exception $exception) {
            // log_message(
            //     'error',
            //     'Unable to parse OTP datetime: value={value}, error={error}',
            //     [
            //         'value' => $dateTime,
            //         'error' => $exception->getMessage(),
            //     ]
            // );

            return null;
        }
    }
}
