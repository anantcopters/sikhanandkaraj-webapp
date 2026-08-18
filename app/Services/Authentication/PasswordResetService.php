<?php

declare(strict_types=1);

namespace App\Services\Authentication;

use App\Models\ContactVerificationModel;
use App\Models\UserContactModel;
use App\Models\UserModel;
use App\Services\Sms\SmsMessage;
use App\Services\Sms\SmsProviderInterface;
use App\Support\IndianMobileNormalizer;
use App\Support\BooleanValue;
use App\Support\OtpGenerator;
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
 * Password reset is permitted only for PENDING and ACTIVE accounts.
 * This service validates account status but never changes it.
 */
final class PasswordResetService
{
    private const OTP_LENGTH = 4;

    private const OTP_EXPIRY_MINUTES = 3;

    /**
     * Minimum time before another OTP can be issued.
     */
    private const OTP_RESEND_COOLDOWN_SECONDS = 120;

    private const VERIFY_ATTEMPT_LIMIT = 5;

    private const SEND_LIMIT_PER_DAY = 5;

    private const SEND_WINDOW_HOURS = 24;

    /**
     * Account statuses that are eligible for password reset.
     *
     * @var list<string>
     */
    private const PASSWORD_RESET_ALLOWED_STATUSES = [
        UserModel::STATUS_ACTIVE,
    ];

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
                'Please enter your registered mobile number or verified email address.'
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

        /**
         * Load the account independently of the contact record.
         *
         * UserModel uses soft deletes, so deleted accounts are not returned by
         * find() and are automatically ineligible for password reset.
         */
        $user = $this->userModel->find(
            $userId
        );

        if (! is_array($user)) {
            return PasswordResetResult::failure(
                'We could not find an account with these details.'
            );
        }

        if (! $this->isPasswordResetAllowedForUser($user)) {
            return PasswordResetResult::failure(
                'Password reset is available only for pending or active accounts.'
            );
        }

        $identifierType = strtoupper(
            trim(
                (string) (
                    $identifierContact['contact_type'] ?? ''
                )
            )
        );

        if (
            $identifierType === UserContactModel::TYPE_EMAIL
            && ! BooleanValue::fromDatabase(
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
            && ! BooleanValue::fromDatabase(
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
            ! is_array($mobileContact)
            || ! BooleanValue::fromDatabase(
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
     * Send a password-setup OTP for an authenticated migrated member.
     *
     * The member ID must come from the authenticated session. This flow does not
     * accept a submitted email address or mobile number, preventing one member
     * from starting password setup for another member.
     */
    public function requestPasswordSetupOtpForUser(
        int $userId
    ): PasswordResetResult {
        if ($userId <= 0) {
            return PasswordResetResult::failure(
                'The member account could not be found.'
            );
        }

        /*
     * UserModel applies soft-delete filtering, so deleted accounts are
     * automatically excluded.
     */
        $user = $this->userModel->find(
            $userId
        );

        if (!is_array($user)) {
            return PasswordResetResult::failure(
                'The member account could not be found.'
            );
        }

        $prelaunchProfileId = $user['prelaunch_profile_id'] ?? null;

        $passwordHash = trim(
            (string) (
                $user['password_hash']
                ?? ''
            )
        );

        /*
     * This authenticated shortcut is only for migrated prelaunch members
     * who have not yet established a password.
     */
        if (
            !is_numeric($prelaunchProfileId)
            || (int) $prelaunchProfileId <= 0
            || $passwordHash !== ''
        ) {
            return PasswordResetResult::failure(
                'Password setup is no longer required for this account.'
            );
        }

        if (!$this->isPasswordResetAllowedForUser($user)) {
            return PasswordResetResult::failure(
                'Password setup is not available for this account.'
            );
        }

        /*
     * Never accept a mobile contact from the request. Resolve the verified
     * primary mobile belonging to the authenticated member.
     */
        $mobileContact = $this->contactModel
            ->findPrimaryForUser(
                $userId,
                UserContactModel::TYPE_MOBILE
            );

        if (
            !is_array($mobileContact)
            || !BooleanValue::fromDatabase(
                $mobileContact['is_verified']
                    ?? false
            )
        ) {
            return PasswordResetResult::failure(
                'Password setup is available only after mobile verification.'
            );
        }

        $mobileContactId = (int) (
            $mobileContact['id']
            ?? 0
        );

        if ($mobileContactId <= 0) {
            return PasswordResetResult::failure(
                'The verified mobile contact could not be found.'
            );
        }

        /*
     * Reuse the existing OTP issue implementation. This preserves purpose,
     * expiry, resend cooldown, daily quota, hashing and provider behaviour.
     */
        return $this->issueOtp(
            $userId,
            $mobileContactId
        );
    }

    /**
     * Resend an OTP to the same password-reset mobile.
     */
    public function resendOtp(
        int $userId,
        int $mobileContactId
    ): PasswordResetResult {
        if (! $this->isValidResetContact(
            $userId,
            $mobileContactId
        )) {
            return PasswordResetResult::failure(
                'The password reset request is no longer valid.'
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

        $otp = OtpGenerator::generate(
            self::OTP_LENGTH
        );

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
            /**
             * Check the latest pending OTP before issuing another one.
             *
             * OTP validity and resend cooldown are separate:
             * - OTP remains valid for five minutes.
             * - Another OTP may be requested after sixty seconds.
             */
            $pendingVerification = $this->verificationModel
                ->findLatestPendingForContact(
                    $mobileContactId,
                    ContactVerificationModel::PURPOSE_PASSWORD_RESET
                );

            if (is_array($pendingVerification)) {
                $cooldownRemaining = $this->getCooldownRemainingSeconds(
                    $pendingVerification
                );

                if ($cooldownRemaining > 0) {
                    $this->database->transRollback();

                    return PasswordResetResult::failure(
                        sprintf(
                            'Please wait %d second%s before requesting another OTP.',
                            $cooldownRemaining,
                            $cooldownRemaining === 1 ? '' : 's'
                        )
                    );
                }
            }

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
             * Only one PENDING OTP is permitted for a contact and purpose.
             *
             * Cancel the previous OTP before inserting its replacement. Both
             * operations are inside the same transaction, so cancellation is
             * rolled back automatically when insertion fails.
             */
            $pendingCancelled = $this->verificationModel
                ->cancelPendingForContact(
                    $mobileContactId,
                    ContactVerificationModel::PURPOSE_PASSWORD_RESET
                );

            if (! $pendingCancelled) {
                throw new RuntimeException(
                    'Unable to cancel the previous password reset OTP.'
                );
            }

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

            $this->commitOrFail();
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }

        try {
            $smsResult = $this->smsProvider->send(
                new SmsMessage(
                    mobileNumber: $mobileNumber,

                    message: 'Your Sikhanandkaraj password reset OTP is '
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
     * Mobile numbers are normalized using the same E.164-style convention used
     * during registration and login:
     *
     * 9876543210     -> +919876543210
     * 919876543210   -> +919876543210
     * +91 98765 43210 -> +919876543210
     *
     * @return array<string, mixed>|null
     */
    private function findIdentifierContact(
        string $identifier
    ): ?array {
        $identifier = trim($identifier);

        if ($identifier === '') {
            return null;
        }

        /**
         * Normalize email addresses exactly as registration and login do.
         */
        if (
            filter_var(
                $identifier,
                FILTER_VALIDATE_EMAIL
            ) !== false
        ) {
            return $this->contactModel
                ->findByNormalizedValue(
                    UserContactModel::TYPE_EMAIL,
                    mb_strtolower($identifier)
                );
        }

        $normalizedMobile =
            IndianMobileNormalizer::normalize(
                $identifier
            );

        if ($normalizedMobile === null) {
            return null;
        }

        return $this->contactModel
            ->findByNormalizedValue(
                UserContactModel::TYPE_MOBILE,
                $normalizedMobile
            );
    }

    /**
     * Confirm that the reset mobile belongs to an eligible user and remains
     * verified.
     *
     * This method is reused by resend, OTP verification and final password
     * replacement, ensuring that an account becoming ineligible during the
     * workflow cannot continue resetting its password.
     */
    private function isValidResetContact(
        int $userId,
        int $mobileContactId
    ): bool {
        if ($userId <= 0 || $mobileContactId <= 0) {
            return false;
        }

        $user = $this->userModel->find(
            $userId
        );

        if (
            ! is_array($user)
            || ! $this->isPasswordResetAllowedForUser($user)
        ) {
            return false;
        }

        $mobile = $this->contactModel->findForUser(
            $mobileContactId,
            $userId,
            UserContactModel::TYPE_MOBILE
        );

        if (! is_array($mobile)) {
            return false;
        }

        return BooleanValue::fromDatabase(
            $mobile['is_verified'] ?? false
        );
    }

    /**
     * Determine whether the supplied account may use password reset.
     *
     * Database status values are normalized before comparison so historical
     * rows containing lowercase values or surrounding whitespace do not cause
     * inconsistent authorization behaviour.
     *
     * Missing or unknown statuses are denied by default.
     *
     * @param array<string, mixed> $user
     */
    private function isPasswordResetAllowedForUser(
        array $user
    ): bool {
        $accountStatus = strtoupper(
            trim(
                (string) (
                    $user['account_status'] ?? ''
                )
            )
        );

        if ($accountStatus === '') {
            return false;
        }

        return in_array(
            $accountStatus,
            self::PASSWORD_RESET_ALLOWED_STATUSES,
            true
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

    /**
     * Return the remaining resend cooldown in seconds.
     *
     * @param array<string, mixed> $verification
     */
    private function getCooldownRemainingSeconds(
        array $verification
    ): int {
        $createdAt = trim(
            (string) (
                $verification['created_at'] ?? ''
            )
        );

        if ($createdAt === '') {
            return 0;
        }

        try {
            $createdAtTimestamp = (
                new DateTimeImmutable($createdAt)
            )->getTimestamp();
        } catch (Throwable) {
            /*
         * Do not permanently block the member because of malformed
         * historical data.
         */
            return 0;
        }

        $resendAllowedAt = $createdAtTimestamp
            + self::OTP_RESEND_COOLDOWN_SECONDS;

        return max(
            0,
            $resendAllowedAt - time()
        );
    }
}
