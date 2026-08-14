<?php

declare(strict_types=1);

namespace App\Services\Admin\Authentication;

use App\Models\AdminPasswordResetVerificationModel;
use App\Models\AdminUserModel;
use App\Services\Sms\SmsMessage;
use App\Services\Sms\SmsProviderInterface;
use App\Support\IndianMobileNormalizer;
use CodeIgniter\Database\BaseConnection;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;
use Throwable;

/**
 * Handles the complete administrator password-reset workflow.
 *
 * Flow:
 *
 * 1. Resolve Admin using registered email/mobile.
 * 2. Confirm account is eligible.
 * 3. Send OTP to verified mobile.
 * 4. Verify OTP.
 * 5. Allow new password.
 * 6. Replace password.
 * 7. Consume OTP authorization.
 *
 * This service never authenticates an administrator.
 * It only authorizes password replacement after OTP verification.
 */
final class AdminPasswordResetService
{
    private const OTP_LENGTH = 4;

    private const OTP_EXPIRY_MINUTES = 3;

    private const OTP_RESEND_COOLDOWN_SECONDS = 120;

    private const VERIFY_ATTEMPT_LIMIT = 5;

    private const SEND_LIMIT_PER_DAY = 5;

    private const SEND_WINDOW_HOURS = 24;

    private const VERIFIED_PASSWORD_WINDOW_SECONDS = 900;

    public function __construct(
        private readonly AdminUserModel $adminModel,
        private readonly AdminPasswordResetVerificationModel $verificationModel,
        private readonly BaseConnection $database,
        private readonly SmsProviderInterface $smsProvider
    ) {}

    /**
     * Resolve Admin and send password-reset OTP.
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
                'This administrator account is not eligible for password reset.'
            );
        }

        if (
            !$this->isMobileVerified($admin)
        ) {
            return AdminPasswordResetResult::failure(
                'Password reset is available only after mobile verification.'
            );
        }

        $adminUserId =
            (int) $admin['id'];

        return $this->issueOtp(
            $adminUserId,
            (string) $admin['mobile_number']
        );
    }

    /**
     * Resend OTP after the existing OTP expires.
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
     * Verify the four-digit OTP.
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
     * Replace the administrator password after OTP verification.
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
                'Your password reset session has expired. '
                    . 'Please request another OTP.'
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
                    'Your password reset session has expired. '
                        . 'Please request another OTP.'
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
     * Return pending OTP expiry timestamp.
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
     * Resolve administrator by email or normalized mobile.
     *
     * @return array<string, mixed>|null
     */
    private function findAdminByIdentifier(
        string $identifier
    ): ?array {
        if (
            filter_var(
                $identifier,
                FILTER_VALIDATE_EMAIL
            ) !== false
        ) {
            $admin =
                $this->adminModel
                ->where(
                    'email_address',
                    strtolower($identifier)
                )
                ->first();

            return is_array($admin)
                ? $admin
                : null;
        }

        $mobile =
            preg_replace(
                '/\D+/',
                '',
                $identifier
            ) ?? '';

        $normalized =
            IndianMobileNormalizer::normalize(
                $mobile
            );

        if ($normalized === null) {
            return null;
        }

        $admin =
            $this->adminModel
            ->where(
                'mobile_number',
                $normalized
            )
            ->first();

        return is_array($admin)
            ? $admin
            : null;
    }

    /**
     * Check whether an Admin may reset a password.
     */
    private function isEligible(
        array $admin
    ): bool {
        return in_array(
            strtoupper(
                (string) (
                    $admin['account_status']
                    ?? ''
                )
            ),
            [
                AdminUserModel::STATUS_VERIFIED,
            ],
            true
        );
    }

    /**
     * Only verified mobile numbers may receive the OTP.
     */
    private function isMobileVerified(
        array $admin
    ): bool {
        return (bool) (
            $admin['is_mobile_verified']
            ?? false
        );
    }

    /**
     * Create and deliver an OTP.
     */
    private function issueOtp(
        int $adminUserId,
        string $mobileNumber
    ): AdminPasswordResetResult {
        $now =
            new DateTimeImmutable(
                'now',
                new DateTimeZone('UTC')
            );

        $cooldownSince =
            $now->sub(
                new DateInterval(
                    'PT'
                        . self::OTP_RESEND_COOLDOWN_SECONDS
                        . 'S'
                )
            );

        $recent =
            $this->verificationModel
            ->where(
                'admin_user_id',
                $adminUserId
            )
            ->where(
                'created_at >=',
                $cooldownSince
                    ->format(
                        'Y-m-d H:i:sP'
                    )
            )
            ->orderBy(
                'id',
                'DESC'
            )
            ->first();

        if (is_array($recent)) {
            return AdminPasswordResetResult::failure(
                'Please wait before requesting another OTP.'
            );
        }

        $dailySince =
            $now->sub(
                new DateInterval(
                    'PT'
                        . self::SEND_WINDOW_HOURS
                        . 'H'
                )
            );

        $sentCount =
            $this->verificationModel
            ->countIssuedSince(
                $adminUserId,
                $dailySince
                    ->format(
                        'Y-m-d H:i:sP'
                    )
            );

        if (
            $sentCount >=
            self::SEND_LIMIT_PER_DAY
        ) {
            return AdminPasswordResetResult::failure(
                'You have reached the maximum number of password reset OTP requests. '
                    . 'Please try again later.'
            );
        }

        $this->verificationModel
            ->cancelPending(
                $adminUserId
            );

        $otp =
            str_pad(
                (string) random_int(
                    0,
                    9999
                ),
                self::OTP_LENGTH,
                '0',
                STR_PAD_LEFT
            );

        $otpHash =
            password_hash(
                $otp,
                PASSWORD_DEFAULT
            );

        if ($otpHash === false) {
            throw new RuntimeException(
                'Unable to generate secure OTP hash.'
            );
        }

        $expiresAt =
            $now->add(
                new DateInterval(
                    'PT'
                        . self::OTP_EXPIRY_MINUTES
                        . 'M'
                )
            );

        $verificationId =
            $this->verificationModel->insert(
                [
                    'admin_user_id' =>
                    $adminUserId,

                    'otp_hash' =>
                    $otpHash,

                    'expires_at' =>
                    $expiresAt
                        ->format(
                            'Y-m-d H:i:sP'
                        ),

                    'attempt_count' =>
                    0,

                    'resend_count' =>
                    0,

                    'status' =>
                    AdminPasswordResetVerificationModel::STATUS_PENDING,
                ],
                true
            );

        if (!is_numeric($verificationId)) {
            throw new RuntimeException(
                'Unable to create administrator password reset OTP.'
            );
        }

        $message =
            'Your SAK administrator password reset OTP is '
            . $otp
            . '. It expires in '
            . self::OTP_EXPIRY_MINUTES
            . ' minutes.';

        $smsResult =
            $this->smsProvider->send(
                new SmsMessage(
                    mobileNumber: $mobileNumber,
                    message: $message
                )
            );

        if (!$smsResult->successful) {
            $this->verificationModel->update(
                (int) $verificationId,
                [
                    'status' =>
                    AdminPasswordResetVerificationModel::STATUS_DELIVERY_FAILED,
                ]
            );

            return AdminPasswordResetResult::failure(
                'We could not send the OTP. Please try again.'
            );
        }

        return AdminPasswordResetResult::success(
            'OTP sent successfully.',
            $adminUserId
        );
    }

    /**
     * Return the current UTC timestamp.
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
     * Convert an application timestamp into a Unix timestamp.
     */
    private function parseUtcTimestamp(
        string $timestamp
    ): ?int {
        if ($timestamp === '') {
            return null;
        }

        try {
            return (new DateTimeImmutable(
                $timestamp,
                new DateTimeZone('UTC')
            ))->getTimestamp();
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
