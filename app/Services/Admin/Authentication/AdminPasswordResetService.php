<?php

declare(strict_types=1);

namespace App\Services\Admin\Authentication;

use App\Models\AdminPasswordResetVerificationModel;
use App\Models\AdminUserModel;
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

/**
 * Handles the administrator forgot-password workflow.
 *
 * Flow:
 *
 * 1. Resolve Admin using registered email/mobile.
 * 2. Confirm the account is eligible.
 * 3. Generate OTP using the SAME OtpGenerator used by Member.
 * 4. Send OTP using the SAME SmsProviderInterface used by Member.
 * 5. Verify OTP.
 * 6. Set a new password.
 * 7. Consume the verified OTP.
 *
 * The Admin authentication context remains separate from Member.
 */
final class AdminPasswordResetService
{
    /**
     * Keep this identical to Member password reset.
     */
    private const OTP_LENGTH = 4;

    /**
     * Keep this identical to Member password reset.
     */
    private const OTP_EXPIRY_MINUTES = 3;

    /**
     * Keep this identical to Member password reset.
     */
    private const OTP_RESEND_COOLDOWN_SECONDS = 120;

    /**
     * Keep this identical to Member password reset.
     */
    private const VERIFY_ATTEMPT_LIMIT = 5;

    /**
     * Keep this identical to Member password reset.
     */
    private const SEND_LIMIT_PER_DAY = 5;

    /**
     * Keep this identical to Member password reset.
     */
    private const SEND_WINDOW_HOURS = 24;

    /**
     * Maximum time after OTP verification in which the password
     * can be changed.
     */
    private const VERIFIED_PASSWORD_WINDOW_SECONDS = 900;

    public function __construct(
        private readonly AdminUserModel $adminModel,
        private readonly AdminPasswordResetVerificationModel $verificationModel,
        private readonly BaseConnection $database,
        private readonly SmsProviderInterface $smsProvider
    ) {}

    /**
     * Resolve an Admin and send a password-reset OTP.
     */
    public function requestOtp(
        string $identifier
    ): AdminPasswordResetResult {
        $identifier =
            trim($identifier);

        if ($identifier === '') {
            return AdminPasswordResetResult::failure(
                'Please enter your registered mobile number or email address.'
            );
        }

        $admin =
            $this->findAdminByIdentifier(
                $identifier
            );

        if ($admin === null) {
            return AdminPasswordResetResult::failure(
                'We could not find an administrator with these details.'
            );
        }

        if (!$this->isEligible($admin)) {
            return AdminPasswordResetResult::failure(
                'Password reset is not available for this administrator account.'
            );
        }

        if (
            !$this->isMobileVerified($admin)
        ) {
            return AdminPasswordResetResult::failure(
                'Password reset is available only after mobile verification.'
            );
        }

        return $this->issueOtp(
            (int) $admin['id'],
            (string) $admin['mobile_number']
        );
    }

    /**
     * Resend the password-reset OTP.
     */
    public function resendOtp(
        int $adminUserId
    ): AdminPasswordResetResult {
        $admin =
            $this->adminModel->find(
                $adminUserId
            );

        if (
            !is_array($admin)
            || !$this->isEligible($admin)
            || !$this->isMobileVerified($admin)
        ) {
            return AdminPasswordResetResult::failure(
                'The password reset request is no longer valid.'
            );
        }

        return $this->issueOtp(
            $adminUserId,
            (string) $admin['mobile_number']
        );
    }

    /**
     * Verify the submitted four-digit OTP.
     */
    public function verifyOtp(
        int $adminUserId,
        string $submittedOtp
    ): AdminPasswordResetResult {
        if (
            !preg_match(
                '/^\d{4}$/',
                $submittedOtp
            )
        ) {
            return AdminPasswordResetResult::failure(
                'Please enter a valid four-digit OTP.'
            );
        }

        $verification =
            $this->verificationModel
            ->findLatestPending(
                $adminUserId
            );

        if ($verification === null) {
            return AdminPasswordResetResult::failure(
                'The OTP is no longer valid. Please request a new OTP.'
            );
        }

        $expiresAt =
            $this->parseUtcTimestamp(
                (string) (
                    $verification['expires_at']
                    ?? ''
                )
            );

        if (
            $expiresAt === null
            || $expiresAt <= time()
        ) {
            $this->verificationModel->update(
                (int) $verification['id'],
                [
                    'status' =>
                    AdminPasswordResetVerificationModel::STATUS_EXPIRED,
                ]
            );

            return AdminPasswordResetResult::failure(
                'The OTP has expired. Please request a new OTP.'
            );
        }

        $attemptCount =
            (int) (
                $verification['attempt_count']
                ?? 0
            );

        if (
            $attemptCount >=
            self::VERIFY_ATTEMPT_LIMIT
        ) {
            $this->verificationModel->update(
                (int) $verification['id'],
                [
                    'status' =>
                    AdminPasswordResetVerificationModel::STATUS_CANCELLED,
                ]
            );

            return AdminPasswordResetResult::failure(
                'Too many incorrect attempts. Please request a new OTP.'
            );
        }

        /**
         * OTP is always stored as a password hash.
         *
         * This is identical to Member password reset.
         */
        $matches =
            password_verify(
                $submittedOtp,
                (string) (
                    $verification['otp_hash']
                    ?? ''
                )
            );

        if (!$matches) {
            $this->verificationModel
                ->incrementAttemptCount(
                    (int) $verification['id']
                );

            $remaining =
                max(
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
                        AdminPasswordResetVerificationModel::STATUS_CANCELLED,
                    ]
                );
            }

            return AdminPasswordResetResult::failure(
                $remaining > 0
                    ? 'Incorrect OTP. '
                    . $remaining
                    . ' attempt(s) remaining.'
                    : 'Too many incorrect attempts. '
                    . 'Please request a new OTP.'
            );
        }

        $verifiedAt =
            $this->utcNow();

        $updated =
            $this->verificationModel->update(
                (int) $verification['id'],
                [
                    'status' =>
                    AdminPasswordResetVerificationModel::STATUS_VERIFIED,

                    'verified_at' =>
                    $verifiedAt,
                ]
            );

        if ($updated === false) {
            throw new RuntimeException(
                'Unable to complete OTP verification.'
            );
        }

        return AdminPasswordResetResult::success(
            'OTP verified successfully.',
            $adminUserId
        );
    }

    /**
     * Replace the administrator password.
     *
     * The verified OTP is checked again inside a transaction so that
     * the authorization cannot be reused concurrently.
     */
    public function resetPassword(
        int $adminUserId,
        string $password
    ): AdminPasswordResetResult {
        $admin =
            $this->adminModel->find(
                $adminUserId
            );

        if (
            !is_array($admin)
            || !$this->isEligible($admin)
        ) {
            return AdminPasswordResetResult::failure(
                'The password reset request is no longer valid.'
            );
        }

        $verifiedOtp =
            $this->verificationModel
            ->findLatestVerified(
                $adminUserId
            );

        if ($verifiedOtp === null) {
            return AdminPasswordResetResult::failure(
                'Please verify the OTP before setting a new password.'
            );
        }

        $verifiedAt =
            $this->parseUtcTimestamp(
                (string) (
                    $verifiedOtp['verified_at']
                    ?? ''
                )
            );

        if (
            $verifiedAt === null
            || $verifiedAt <
            time()
            - self::VERIFIED_PASSWORD_WINDOW_SECONDS
        ) {
            $this->verificationModel->update(
                (int) $verifiedOtp['id'],
                [
                    'status' =>
                    AdminPasswordResetVerificationModel::STATUS_EXPIRED,
                ]
            );

            return AdminPasswordResetResult::failure(
                'Your password reset session has expired. Please request another OTP.'
            );
        }

        $passwordHash =
            password_hash(
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
            $verification =
                $this->verificationModel
                ->lockVerified(
                    (int) $verifiedOtp['id'],
                    $adminUserId
                );

            if (!is_array($verification)) {
                $this->database->transRollback();

                return AdminPasswordResetResult::failure(
                    'This password reset authorization has already been used.'
                );
            }

            $lockedVerifiedAt =
                $this->parseUtcTimestamp(
                    (string) (
                        $verification['verified_at']
                        ?? ''
                    )
                );

            if (
                $lockedVerifiedAt === null
                || $lockedVerifiedAt <
                time()
                - self::VERIFIED_PASSWORD_WINDOW_SECONDS
            ) {
                $this->verificationModel->update(
                    (int) $verification['id'],
                    [
                        'status' =>
                        AdminPasswordResetVerificationModel::STATUS_EXPIRED,
                    ]
                );

                $this->commitOrFail();

                return AdminPasswordResetResult::failure(
                    'Your password reset session has expired. Please request another OTP.'
                );
            }

            $updated =
                $this->adminModel->update(
                    $adminUserId,
                    [
                        'password_hash' =>
                        $passwordHash,

                        'password_set_at' =>
                        $this->utcNow(),
                    ]
                );

            if ($updated === false) {
                throw new RuntimeException(
                    'Unable to update the administrator password.'
                );
            }

            /**
             * Consume the verified OTP.
             */
            $consumed =
                $this->verificationModel->update(
                    (int) $verification['id'],
                    [
                        'status' =>
                        AdminPasswordResetVerificationModel::STATUS_CANCELLED,
                    ]
                );

            if ($consumed === false) {
                throw new RuntimeException(
                    'Unable to consume the password reset authorization.'
                );
            }

            /**
             * Cancel any other outstanding reset authorizations.
             */
            $this->verificationModel
                ->where(
                    'admin_user_id',
                    $adminUserId
                )
                ->where(
                    'id !=',
                    (int) $verification['id']
                )
                ->whereIn(
                    'status',
                    [
                        AdminPasswordResetVerificationModel::STATUS_PENDING,
                        AdminPasswordResetVerificationModel::STATUS_VERIFIED,
                    ]
                )
                ->set([
                    'status' =>
                    AdminPasswordResetVerificationModel::STATUS_CANCELLED,
                ])
                ->update();

            $this->commitOrFail();

            return AdminPasswordResetResult::success(
                'Your administrator password has been changed successfully.'
            );
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }
    }

    /**
     * Return the expiry timestamp for the currently pending OTP.
     */
    public function getPendingExpiryTimestamp(
        int $adminUserId
    ): ?int {
        $verification =
            $this->verificationModel
            ->findLatestPending(
                $adminUserId
            );

        if ($verification === null) {
            return null;
        }

        return $this->parseUtcTimestamp(
            (string) (
                $verification['expires_at']
                ?? ''
            )
        );
    }

    /**
     * Issue a new password-reset OTP.
     *
     * IMPORTANT:
     *
     * OTP generation deliberately uses OtpGenerator rather than random_int().
     *
     * Therefore:
     *
     * Development/QA:
     * OTP_FIXED_VALUE is respected.
     *
     * Production:
     * OTP_FIXED_VALUE is rejected and a secure random OTP is generated.
     */
    private function issueOtp(
        int $adminUserId,
        string $mobileNumber
    ): AdminPasswordResetResult {
        $mobileNumber =
            IndianMobileNormalizer::normalize(
                $mobileNumber
            );

        if ($mobileNumber === null) {
            return AdminPasswordResetResult::failure(
                'The verified mobile number could not be found.'
            );
        }

        $now =
            new DateTimeImmutable(
                'now',
                new DateTimeZone('UTC')
            );

        $pending =
            $this->verificationModel
            ->findLatestPending(
                $adminUserId
            );

        if (is_array($pending)) {
            $createdAt =
                $this->parseUtcTimestamp(
                    (string) (
                        $pending['created_at']
                        ?? ''
                    )
                );

            if (
                $createdAt !== null
                && $createdAt
                + self::OTP_RESEND_COOLDOWN_SECONDS
                > time()
            ) {
                $remaining =
                    (
                        $createdAt
                        + self::OTP_RESEND_COOLDOWN_SECONDS
                    )
                    - time();

                return AdminPasswordResetResult::failure(
                    sprintf(
                        'Please wait %d second%s before requesting another OTP.',
                        $remaining,
                        $remaining === 1
                            ? ''
                            : 's'
                    )
                );
            }
        }

        $since =
            $now->sub(
                new DateInterval(
                    'PT'
                        . self::SEND_WINDOW_HOURS
                        . 'H'
                )
            )->format(
                'Y-m-d H:i:sP'
            );

        $issuedCount =
            $this->verificationModel
            ->countIssuedSince(
                $adminUserId,
                $since
            );

        if (
            $issuedCount >=
            self::SEND_LIMIT_PER_DAY
        ) {
            return AdminPasswordResetResult::failure(
                'The OTP request limit has been reached. Please try again later.'
            );
        }

        /**
         * SAME OTP GENERATOR AS MEMBER.
         *
         * This is what makes OTP_FIXED_VALUE work in DEV/QA.
         */
        $otp =
            OtpGenerator::generate(
                self::OTP_LENGTH
            );

        $expiresAt =
            $now->add(
                new DateInterval(
                    'PT'
                        . self::OTP_EXPIRY_MINUTES
                        . 'M'
                )
            );

        /**
         * Replace the previous pending OTP.
         */
        $this->database->transBegin();

        try {
            $cancelled =
                $this->verificationModel
                ->cancelPending(
                    $adminUserId
                );

            if (!$cancelled) {
                throw new RuntimeException(
                    'Unable to cancel the previous password reset OTP.'
                );
            }

            $otpHash =
                password_hash(
                    $otp,
                    PASSWORD_DEFAULT
                );

            if ($otpHash === false) {
                throw new RuntimeException(
                    'Unable to securely hash the OTP.'
                );
            }

            $verificationId =
                $this->verificationModel->insert(
                    [
                        'admin_user_id' =>
                        $adminUserId,

                        'otp_hash' =>
                        $otpHash,

                        'expires_at' =>
                        $expiresAt->format(
                            'Y-m-d H:i:sP'
                        ),

                        'attempt_count' =>
                        0,

                        'resend_count' =>
                        0,

                        'status' =>
                        AdminPasswordResetVerificationModel::STATUS_PENDING,

                        'verified_at' =>
                        null,
                    ],
                    true
                );

            if (!is_numeric($verificationId)) {
                throw new RuntimeException(
                    'Unable to create the password reset OTP.'
                );
            }

            $this->commitOrFail();
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }

        /**
         * Use the same SmsMessage/SmsProvider abstraction as Member.
         *
         * The provider itself is selected by SmsProviderFactory.
         */
        try {
            $smsResult =
                $this->smsProvider->send(
                    new SmsMessage(
                        mobileNumber: $mobileNumber,

                        message: 'Your Sikhanandkaraj administrator password reset OTP is '
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
                            (string)
                            self::OTP_EXPIRY_MINUTES,
                        ]
                    )
                );
        } catch (Throwable $exception) {
            $this->markOtpDeliveryFailed(
                (int) $verificationId
            );

            throw $exception;
        }

        if (!$smsResult->successful) {
            $this->markOtpDeliveryFailed(
                (int) $verificationId
            );

            log_message(
                'error',
                'Admin password reset OTP SMS failed: admin_id={adminId}, error={error}',
                [
                    'adminId' =>
                    $adminUserId,

                    'error' =>
                    $smsResult->errorMessage
                        ?? 'Unknown SMS provider error',
                ]
            );

            return AdminPasswordResetResult::failure(
                'We could not send the OTP. Please try again.'
            );
        }

        return AdminPasswordResetResult::success(
            'OTP sent successfully.',
            $adminUserId,
            null,
            $expiresAt->getTimestamp()
        );
    }

    /**
     * Mark an OTP as undelivered.
     */
    private function markOtpDeliveryFailed(
        int $verificationId
    ): void {
        if ($verificationId <= 0) {
            return;
        }

        $this->verificationModel->update(
            $verificationId,
            [
                'status' =>
                AdminPasswordResetVerificationModel::STATUS_DELIVERY_FAILED,
            ]
        );
    }

    /**
     * Find an administrator using the existing AdminUserModel lookup.
     *
     * AdminUserModel already handles the email/mobile lookup and
     * CodeIgniter's soft-delete behavior.
     *
     * @return array<string, mixed>|null
     */
    private function findAdminByIdentifier(
        string $identifier
    ): ?array {
        $identifier = trim($identifier);

        if ($identifier === '') {
            return null;
        }

        /*
     * Email lookup.
     *
     * AdminUserModel::findByIdentifier() expects the actual
     * email_address value stored in admin_users.
     */
        if (
            filter_var(
                $identifier,
                FILTER_VALIDATE_EMAIL
            ) !== false
        ) {
            return $this->adminModel
                ->findByIdentifier(
                    strtolower($identifier)
                );
        }

        /*
     * Mobile lookup.
     *
     * Normalize the supplied number before passing it to the
     * existing AdminUserModel lookup method.
     */
        $mobileNumber =
            IndianMobileNormalizer::normalize(
                $identifier
            );

        if ($mobileNumber === null) {
            return null;
        }

        return $this->adminModel
            ->findByIdentifier(
                $mobileNumber
            );
    }

    /**
     * Only verified Admin accounts may reset their passwords.
     */
    private function isEligible(
        array $admin
    ): bool {
        return strtoupper(
            trim(
                (string) (
                    $admin['account_status']
                    ?? ''
                )
            )
        ) === AdminUserModel::STATUS_VERIFIED;
    }

    /**
     * Password reset requires verified mobile.
     */
    private function isMobileVerified(
        array $admin
    ): bool {
        return filter_var(
            $admin['is_mobile_verified'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );
    }

    /**
     * Return current UTC timestamp.
     */
    private function utcNow(): string
    {
        return (
            new DateTimeImmutable(
                'now',
                new DateTimeZone('UTC')
            )
        )->format(
            'Y-m-d H:i:sP'
        );
    }

    /**
     * Parse an application timestamp.
     */
    private function parseUtcTimestamp(
        string $timestamp
    ): ?int {
        if ($timestamp === '') {
            return null;
        }

        try {
            return (
                new DateTimeImmutable(
                    $timestamp,
                    new DateTimeZone('UTC')
                )
            )->getTimestamp();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Complete the current database transaction.
     */
    private function commitOrFail(): void
    {
        if (!$this->database->transCommit()) {
            throw new RuntimeException(
                'Unable to commit the password reset transaction.'
            );
        }
    }
}
