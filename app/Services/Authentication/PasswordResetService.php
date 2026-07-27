<?php

declare(strict_types=1);

namespace App\Services\Authentication;

use App\Models\ContactVerificationModel;
use App\Models\UserContactModel;
use App\Models\UserModel;
use App\Services\Sms\SmsMessage;
use App\Services\Sms\SmsProviderInterface;
use CodeIgniter\Database\BaseConnection;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;
use Throwable;

/**
 * Handles forgot-password identification, OTP delivery, OTP verification
 * and final password replacement.
 *
 * This service intentionally does not inspect or change account_status.
 */
final class PasswordResetService
{
    private const OTP_LENGTH = 4;

    private const OTP_EXPIRY_MINUTES = 5;

    private const VERIFY_ATTEMPT_LIMIT = 5;

    private const SEND_LIMIT_PER_DAY = 5;

    private const SEND_WINDOW_HOURS = 24;

    public function __construct(
        private readonly UserModel $userModel,
        private readonly UserContactModel $contactModel,
        private readonly ContactVerificationModel $verificationModel,
        private readonly BaseConnection $database,
        private readonly SmsProviderInterface $smsProvider
    ) {}

    /**
     * Resolve the submitted email/mobile and send an OTP to the member's
     * verified primary mobile.
     */
    public function requestOtp(
        string $identifier
    ): PasswordResetResult {
        $identifier = trim($identifier);

        if ($identifier === '') {
            return PasswordResetResult::failure(
                'Please enter your email address or mobile number.'
            );
        }

        $identifierContact = $this->findIdentifierContact(
            $identifier
        );

        /*
         * A generic message can be used here to reduce account enumeration.
         * The project requirement asks for a specific unverified-email error,
         * so a verified contact that exists is handled explicitly below.
         */
        if ($identifierContact === null) {
            return PasswordResetResult::failure(
                'We could not find an account with these details.'
            );
        }

        $userId = (int) (
            $identifierContact['user_id'] ?? 0
        );

        if ($userId <= 0) {
            return PasswordResetResult::failure(
                'We could not find an account with these details.'
            );
        }

        $identifierType = (string) (
            $identifierContact['contact_type'] ?? ''
        );

        if (
            $identifierType === UserContactModel::TYPE_EMAIL
            && !$this->toBoolean(
                $identifierContact['is_verified'] ?? false
            )
        ) {
            return PasswordResetResult::failure(
                'This email address has not been verified. '
                    . 'Please enter your verified mobile number.'
            );
        }

        if (
            $identifierType === UserContactModel::TYPE_MOBILE
            && !$this->toBoolean(
                $identifierContact['is_verified'] ?? false
            )
        ) {
            return PasswordResetResult::failure(
                'This mobile number has not been verified.'
            );
        }

        /*
         * OTP always goes to the verified primary mobile, including when the
         * member entered a verified email address.
         */
        $mobileContact = $this->contactModel
            ->findPrimaryForUser(
                $userId,
                UserContactModel::TYPE_MOBILE
            );

        if (
            !is_array($mobileContact)
            || !$this->toBoolean(
                $mobileContact['is_verified'] ?? false
            )
        ) {
            return PasswordResetResult::failure(
                'Password reset is available only after mobile verification.'
            );
        }

        return $this->issueOtp(
            $userId,
            (int) $mobileContact['id']
        );
    }

    /**
     * Resend an OTP to the same password-reset mobile.
     */
    public function resendOtp(
        int $userId,
        int $mobileContactId
    ): PasswordResetResult {
        if (!$this->isValidResetContact(
            $userId,
            $mobileContactId
        )) {
            return PasswordResetResult::failure(
                'The password reset request is no longer valid.'
            );
        }

        $expiry = $this->getPendingExpiryTimestamp(
            $mobileContactId
        );

        if ($expiry !== null && $expiry > time()) {
            return PasswordResetResult::failure(
                'Please wait until the current OTP expires.'
            );
        }

        return $this->issueOtp(
            $userId,
            $mobileContactId
        );
    }

    /**
     * Verify password-reset OTP.
     *
     * This method does not authenticate the member and does not update
     * mobile/email/account verification status.
     */
    public function verifyOtp(
        int $userId,
        int $mobileContactId,
        string $submittedOtp
    ): PasswordResetResult {
        if (!preg_match('/^\d{4}$/', $submittedOtp)) {
            return PasswordResetResult::failure(
                'Please enter a valid four-digit OTP.'
            );
        }

        if (!$this->isValidResetContact(
            $userId,
            $mobileContactId
        )) {
            return PasswordResetResult::failure(
                'The password reset request is no longer valid.'
            );
        }

        $this->database->transBegin();

        try {
            $verification = $this->verificationModel
                ->findLatestPendingForContact(
                    $mobileContactId,
                    ContactVerificationModel::PURPOSE_PASSWORD_RESET
                );

            if ($verification === null) {
                $this->database->transRollback();

                return PasswordResetResult::failure(
                    'The OTP is no longer valid. Please request a new OTP.'
                );
            }

            $expiresAt = $this->parseUtcTimestamp(
                (string) ($verification['expires_at'] ?? '')
            );

            if ($expiresAt === null || $expiresAt <= time()) {
                $this->verificationModel->markExpired(
                    (int) $verification['id']
                );

                $this->commitOrFail();

                return PasswordResetResult::failure(
                    'The OTP has expired. Please request a new OTP.'
                );
            }

            $attemptCount = (int) (
                $verification['attempt_count'] ?? 0
            );

            if ($attemptCount >= self::VERIFY_ATTEMPT_LIMIT) {
                $this->verificationModel->update(
                    (int) $verification['id'],
                    [
                        'status' =>
                        ContactVerificationModel::STATUS_CANCELLED,
                    ]
                );

                $this->commitOrFail();

                return PasswordResetResult::failure(
                    'Too many incorrect attempts. Please request a new OTP.'
                );
            }

            $matches = password_verify(
                $submittedOtp,
                (string) ($verification['otp_hash'] ?? '')
            );

            if (!$matches) {
                $this->verificationModel
                    ->incrementAttemptCount(
                        (int) $verification['id']
                    );

                $remaining = max(
                    0,
                    self::VERIFY_ATTEMPT_LIMIT
                        - $attemptCount
                        - 1
                );

                if ($remaining === 0) {
                    $this->verificationModel->update(
                        (int) $verification['id'],
                        [
                            'status' =>
                            ContactVerificationModel::STATUS_CANCELLED,
                        ]
                    );
                }

                $this->commitOrFail();

                return PasswordResetResult::failure(
                    $remaining > 0
                        ? 'Incorrect OTP. '
                        . $remaining
                        . ' attempt(s) remaining.'
                        : 'Too many incorrect attempts. '
                        . 'Please request a new OTP.'
                );
            }

            $verifiedAt = (new DateTimeImmutable(
                'now',
                new DateTimeZone('UTC')
            ))->format('Y-m-d H:i:sP');

            $updated = $this->verificationModel->update(
                (int) $verification['id'],
                [
                    'status' =>
                    ContactVerificationModel::STATUS_VERIFIED,
                    'verified_at' => $verifiedAt,
                ]
            );

            if ($updated === false) {
                throw new RuntimeException(
                    'Unable to complete OTP verification.'
                );
            }

            $this->verificationModel->cancelPendingForContact(
                $mobileContactId,
                ContactVerificationModel::PURPOSE_PASSWORD_RESET
            );

            $this->commitOrFail();

            return PasswordResetResult::success(
                'OTP verified successfully.',
                $userId,
                $mobileContactId
            );
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }
    }

    /**
     * Replace the password after successful OTP verification.
     *
     * The password update and OTP consumption are performed in a single
     * transaction. This prevents a verified OTP from remaining reusable when
     * its consumption fails after the password has already changed.
     */
    public function resetPassword(
        int $userId,
        int $mobileContactId,
        string $password
    ): PasswordResetResult {
        if (! $this->isValidResetContact(
            $userId,
            $mobileContactId
        )) {
            return PasswordResetResult::failure(
                'The password reset request is no longer valid.'
            );
        }

        /**
         * Verify that a PASSWORD_RESET OTP was actually verified for this
         * mobile contact.
         *
         * A browser session flag alone must never authorize a password change.
         */
        $verifiedOtp = $this->verificationModel
            ->where(
                'user_contact_id',
                $mobileContactId
            )
            ->where(
                'purpose',
                ContactVerificationModel::PURPOSE_PASSWORD_RESET
            )
            ->where(
                'status',
                ContactVerificationModel::STATUS_VERIFIED
            )
            ->orderBy('id', 'DESC')
            ->first();

        if (! is_array($verifiedOtp)) {
            return PasswordResetResult::failure(
                'Please verify the OTP before setting a new password.'
            );
        }

        $verificationId = (int) (
            $verifiedOtp['id'] ?? 0
        );

        if ($verificationId <= 0) {
            return PasswordResetResult::failure(
                'The password reset authorization is invalid.'
            );
        }

        /**
         * Limit how long a verified OTP may authorize password replacement.
         */
        $verifiedAt = $this->parseUtcTimestamp(
            (string) ($verifiedOtp['verified_at'] ?? '')
        );

        if (
            $verifiedAt === null
            || $verifiedAt < time() - 900
        ) {
            return PasswordResetResult::failure(
                'Your password reset session has expired. '
                    . 'Please request another OTP.'
            );
        }

        $passwordHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        if ($passwordHash === false) {
            throw new RuntimeException(
                'Unable to securely hash the password.'
            );
        }

        $this->database->transBegin();

        try {
            /**
             * Recheck the OTP status inside the transaction.
             *
             * This prevents a second concurrent request from using an OTP that
             * another request has already consumed.
             */
            $currentVerification =
                $this->verificationModel
                ->lockVerifiedPasswordReset(
                    $verificationId,
                    $mobileContactId
                );

            if (! is_array($currentVerification)) {
                $this->database->transRollback();

                return PasswordResetResult::failure(
                    'This password reset authorization has already been used.'
                );
            }

            $lockedVerifiedAt = $this->parseUtcTimestamp(
                (string) (
                    $currentVerification['verified_at'] ?? ''
                )
            );

            if (
                $lockedVerifiedAt === null
                || $lockedVerifiedAt < time() - 900
            ) {
                $this->verificationModel->update(
                    $verificationId,
                    [
                        'status' =>
                        ContactVerificationModel::STATUS_EXPIRED,
                    ]
                );

                $this->commitOrFail();

                return PasswordResetResult::failure(
                    'Your password reset session has expired. '
                        . 'Please request another OTP.'
                );
            }

            $passwordUpdated = $this->userModel->update(
                $userId,
                [
                    'password_hash' => $passwordHash,
                ]
            );

            if ($passwordUpdated === false) {
                throw new RuntimeException(
                    'Unable to update the password.'
                );
            }

            /**
             * Consume the verified OTP authorization.
             */
            $verificationConsumed =
                $this->verificationModel->update(
                    $verificationId,
                    [
                        'status' =>
                        ContactVerificationModel::STATUS_CANCELLED,
                    ]
                );

            if ($verificationConsumed === false) {
                throw new RuntimeException(
                    'Unable to consume the password reset authorization.'
                );
            }

            /**
             * Cancel any other password-reset verification records for the same
             * mobile contact so no older authorization can be reused.
             */
            $this->verificationModel
                ->where(
                    'user_contact_id',
                    $mobileContactId
                )
                ->where(
                    'purpose',
                    ContactVerificationModel::PURPOSE_PASSWORD_RESET
                )
                ->where(
                    'id !=',
                    $verificationId
                )
                ->whereIn(
                    'status',
                    [
                        ContactVerificationModel::STATUS_PENDING,
                        ContactVerificationModel::STATUS_VERIFIED,
                    ]
                )
                ->set([
                    'status' =>
                    ContactVerificationModel::STATUS_CANCELLED,
                ])
                ->update();

            $this->commitOrFail();

            return PasswordResetResult::success(
                'Your password has been changed successfully.'
            );
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }
    }

    public function getPendingExpiryTimestamp(
        int $mobileContactId
    ): ?int {
        $verification = $this->verificationModel
            ->findLatestPendingForContact(
                $mobileContactId,
                ContactVerificationModel::PURPOSE_PASSWORD_RESET
            );

        if ($verification === null) {
            return null;
        }

        return $this->parseUtcTimestamp(
            (string) ($verification['expires_at'] ?? '')
        );
    }

    /**
     * Create and deliver a password-reset OTP.
     *
     * Database creation is committed before contacting the external SMS provider,
     * so a slow provider does not hold a database transaction open.
     *
     * When delivery fails, the newly created verification record is immediately
     * marked DELIVERY_FAILED and cannot be verified or block resend.
     */
    private function issueOtp(
        int $userId,
        int $mobileContactId
    ): PasswordResetResult {
        if (! $this->isValidResetContact(
            $userId,
            $mobileContactId
        )) {
            return PasswordResetResult::failure(
                'The verified mobile number could not be found.'
            );
        }

        $mobileContact = $this->contactModel->find(
            $mobileContactId
        );

        if (! is_array($mobileContact)) {
            return PasswordResetResult::failure(
                'The verified mobile number could not be found.'
            );
        }

        $mobileNumber = trim(
            (string) (
                $mobileContact['normalized_value'] ?? ''
            )
        );

        if ($mobileNumber === '') {
            return PasswordResetResult::failure(
                'The verified mobile number could not be found.'
            );
        }

        $otp = $this->generateOtp();

        $now = new DateTimeImmutable(
            'now',
            new DateTimeZone('UTC')
        );

        $expiresAt = $now->add(
            new DateInterval(
                'PT' . self::OTP_EXPIRY_MINUTES . 'M'
            )
        );

        $since = $now
            ->sub(
                new DateInterval(
                    'PT' . self::SEND_WINDOW_HOURS . 'H'
                )
            )
            ->format('Y-m-d H:i:sP');

        $verificationId = null;

        $this->database->transBegin();

        try {
            $issuedCount = $this->verificationModel
                ->countDeliveredOrPendingSince(
                    $mobileContactId,
                    ContactVerificationModel::PURPOSE_PASSWORD_RESET,
                    $since
                );

            if ($issuedCount >= self::SEND_LIMIT_PER_DAY) {
                $this->database->transRollback();

                return PasswordResetResult::failure(
                    'The OTP request limit has been reached. '
                        . 'Please try again later.'
                );
            }

            /**
             * Do not cancel the old pending OTP until the replacement record is
             * successfully created.
             */
            $verificationId = $this->verificationModel
                ->insert([
                    'user_contact_id' =>
                    $mobileContactId,

                    'purpose' =>
                    ContactVerificationModel::PURPOSE_PASSWORD_RESET,

                    'otp_hash' =>
                    password_hash(
                        $otp,
                        PASSWORD_DEFAULT
                    ),

                    'expires_at' =>
                    $expiresAt->format('Y-m-d H:i:sP'),

                    'attempt_count' =>
                    0,

                    'resend_count' =>
                    0,

                    'status' =>
                    ContactVerificationModel::STATUS_PENDING,

                    'verified_at' =>
                    null,
                ], true);

            if (! is_numeric($verificationId)) {
                throw new RuntimeException(
                    'Unable to create the password reset OTP.'
                );
            }

            /**
             * Cancel older pending records only after the replacement record has
             * been inserted successfully.
             */
            $olderPendingCancelled = $this->verificationModel
                ->cancelOtherPendingForContact(
                    $mobileContactId,
                    ContactVerificationModel::PURPOSE_PASSWORD_RESET,
                    (int) $verificationId
                );

            if (! $olderPendingCancelled) {
                throw new RuntimeException(
                    'Unable to replace the previous password reset OTP.'
                );
            }

            $this->commitOrFail();
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }

        try {
            $smsResult = $this->smsProvider->send(
                new SmsMessage(
                    mobileNumber: $mobileNumber,

                    message: 'Your Sikh Anand Karaj password reset OTP is '
                        . $otp
                        . '. It is valid for '
                        . self::OTP_EXPIRY_MINUTES
                        . ' minutes.',

                    templateId: trim(
                        (string) env(
                            'sms.passwordResetTemplateId'
                        )
                    ) ?: null,

                    variables: [
                        'otp' =>
                        $otp,

                        'expiry_minutes' =>
                        (string) self::OTP_EXPIRY_MINUTES,
                    ]
                )
            );
        } catch (Throwable $exception) {
            $this->markOtpDeliveryFailed(
                (int) $verificationId
            );

            throw $exception;
        }

        if (! $smsResult->successful) {
            $this->markOtpDeliveryFailed(
                (int) $verificationId
            );

            log_message(
                'error',
                'Password reset OTP SMS failed: '
                    . 'contact_id={contactId}, error={error}',
                [
                    'contactId' =>
                    $mobileContactId,

                    'error' =>
                    $smsResult->errorMessage
                        ?? 'Unknown SMS provider error',
                ]
            );

            return PasswordResetResult::failure(
                'We could not send the OTP. Please try again.'
            );
        }

        return PasswordResetResult::success(
            'OTP sent successfully.',
            $userId,
            $mobileContactId,
            $expiresAt->getTimestamp()
        );
    }

    /**
     * Generate a valid four-digit OTP.
     *
     * OTP_FIXED_VALUE is allowed only outside production and must contain exactly
     * four digits. An invalid configured value must never create an OTP that the
     * verification method cannot accept.
     */
    private function generateOtp(): string
    {
        $configuredOtp = trim(
            (string) env('OTP_FIXED_VALUE')
        );

        if ($configuredOtp === '') {
            return (string) random_int(
                10 ** (self::OTP_LENGTH - 1),
                (10 ** self::OTP_LENGTH) - 1
            );
        }

        if (ENVIRONMENT === 'production') {
            log_message(
                'critical',
                'OTP_FIXED_VALUE must not be configured in production.'
            );

            throw new RuntimeException(
                'OTP configuration is invalid.'
            );
        }

        if (! preg_match(
            '/^\d{' . self::OTP_LENGTH . '}$/',
            $configuredOtp
        )) {
            throw new RuntimeException(
                'OTP_FIXED_VALUE must contain exactly '
                    . self::OTP_LENGTH
                    . ' digits.'
            );
        }

        return $configuredOtp;
    }

    /**
     * Prevent an undelivered OTP from remaining usable.
     */
    private function markOtpDeliveryFailed(
        int $verificationId
    ): void {
        if ($verificationId <= 0) {
            return;
        }

        $updated = $this->verificationModel->update(
            $verificationId,
            [
                'status' =>
                ContactVerificationModel::STATUS_DELIVERY_FAILED,
            ]
        );

        if ($updated === false) {
            log_message(
                'critical',
                'Unable to mark failed password reset OTP delivery: '
                    . 'verification_id={verificationId}',
                [
                    'verificationId' =>
                    $verificationId,
                ]
            );
        }
    }

    /**
     * Find an email or mobile contact after normalizing its value.
     *
     * @return array<string, mixed>|null
     */
    private function findIdentifierContact(
        string $identifier
    ): ?array {
        if (filter_var(
            $identifier,
            FILTER_VALIDATE_EMAIL
        ) !== false) {
            return $this->contactModel
                ->findByNormalizedValue(
                    UserContactModel::TYPE_EMAIL,
                    mb_strtolower($identifier)
                );
        }

        $mobile = preg_replace(
            '/\D+/',
            '',
            $identifier
        );

        if (!is_string($mobile)) {
            return null;
        }

        /*
         * Match the same normalized mobile convention used during
         * registration. Adjust this line only if registration stores +91.
         */
        if (strlen($mobile) === 12 && str_starts_with(
            $mobile,
            '91'
        )) {
            $mobile = substr($mobile, 2);
        }

        if (!preg_match('/^[6-9]\d{9}$/', $mobile)) {
            return null;
        }

        return $this->contactModel
            ->findByNormalizedValue(
                UserContactModel::TYPE_MOBILE,
                $mobile
            );
    }

    private function isValidResetContact(
        int $userId,
        int $mobileContactId
    ): bool {
        $user = $this->userModel->find($userId);

        $mobile = $this->contactModel->findForUser(
            $mobileContactId,
            $userId,
            UserContactModel::TYPE_MOBILE
        );

        return is_array($user)
            && is_array($mobile)
            && $this->toBoolean(
                $mobile['is_verified'] ?? false
            );
    }

    private function parseUtcTimestamp(
        string $value
    ): ?int {
        if ($value === '') {
            return null;
        }

        try {
            return (new DateTimeImmutable(
                $value,
                new DateTimeZone('UTC')
            ))->getTimestamp();
        } catch (Throwable) {
            return null;
        }
    }

    private function commitOrFail(): void
    {
        if (
            $this->database->transStatus() === false
            || $this->database->transCommit() === false
        ) {
            throw new RuntimeException(
                'Unable to commit the password-reset transaction.'
            );
        }
    }

    private function toBoolean(mixed $value): bool
    {
        return filter_var(
            $value,
            FILTER_VALIDATE_BOOLEAN
        );
    }
}
