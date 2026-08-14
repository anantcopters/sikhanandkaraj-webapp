<?php

declare(strict_types=1);

namespace App\Services\Admin\Authentication;

use App\Models\AdminPasswordResetVerificationModel;
use App\Models\AdminUserModel;
use App\Services\Sms\SmsMessage;
use App\Services\Sms\SmsProviderInterface;
use App\Support\BooleanValue;
use App\Support\IndianMobileNormalizer;
use App\Support\OtpGenerator;
use CodeIgniter\Database\BaseConnection;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;
use Throwable;

/**
 * Handles administrator password reset.
 *
 * This service mirrors Member password-reset behaviour while keeping
 * administrator data in the Admin authentication context.
 *
 * Flow:
 *
 * 1. Resolve registered email/mobile.
 * 2. Confirm Admin account is eligible.
 * 3. Confirm verified mobile.
 * 4. Generate OTP through shared OtpGenerator.
 * 5. Deliver OTP through shared SmsProviderInterface.
 * 6. Verify OTP.
 * 7. Replace password.
 * 8. Consume OTP authorization.
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
     * Resolve the supplied email/mobile and send an OTP to the
     * administrator's verified mobile number.
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

        if (
            !$this->isPasswordResetAllowedForAdmin(
                $admin
            )
        ) {
            return AdminPasswordResetResult::failure(
                'Password reset is not available for this administrator account.'
            );
        }

        /*
         * If email was supplied, follow the Member rule:
         * the identifier itself must be verified.
         */
        if (
            filter_var(
                $identifier,
                FILTER_VALIDATE_EMAIL
            ) !== false
            && !BooleanValue::fromDatabase(
                $admin['is_email_verified']
                    ?? false
            )
        ) {
            return AdminPasswordResetResult::failure(
                'This email address has not been verified. '
                    . 'Please enter your verified mobile number.'
            );
        }

        /*
         * OTP always goes to the verified mobile number,
         * regardless of whether email or mobile was supplied.
         */
        if (
            !BooleanValue::fromDatabase(
                $admin['is_mobile_verified']
                    ?? false
            )
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
     * Resend password-reset OTP.
     */
    public function resendOtp(
        int $adminUserId
    ): AdminPasswordResetResult {
        if (
            !$this->isValidResetAdmin(
                $adminUserId
            )
        ) {
            return AdminPasswordResetResult::failure(
                'The password reset request is no longer valid.'
            );
        }

        $admin =
            $this->adminModel->find(
                $adminUserId
            );

        if (!is_array($admin)) {
            return AdminPasswordResetResult::failure(
                'The password reset request is no longer valid.'
            );
        }

        return $this->issueOtp(
            $adminUserId,
            (string) (
                $admin['mobile_number']
                ?? ''
            )
        );
    }

    /**
     * Verify password-reset OTP.
     */
    public function verifyOtp(
        int $adminUserId,
        string $submittedOtp
    ): AdminPasswordResetResult {
        if (
            preg_match(
                '/^\d{4}$/',
                $submittedOtp
            ) !== 1
        ) {
            return AdminPasswordResetResult::failure(
                'Please enter a valid four-digit OTP.'
            );
        }

        if (
            !$this->isValidResetAdmin(
                $adminUserId
            )
        ) {
            return AdminPasswordResetResult::failure(
                'The password reset request is no longer valid.'
            );
        }

        $this->database->transBegin();

        try {
            $verification =
                $this->verificationModel
                ->findLatestPending(
                    $adminUserId
                );

            if ($verification === null) {
                $this->database->transRollback();

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
                $this->verificationModel
                    ->markExpired(
                        (int) $verification['id']
                    );

                $this->commitOrFail();

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

                $this->commitOrFail();

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

                $this->commitOrFail();

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

            /*
             * Cancel any other pending reset OTP.
             */
            $cancelled =
                $this->verificationModel
                ->cancelPending(
                    $adminUserId
                );

            if (!$cancelled) {
                throw new RuntimeException(
                    'Unable to cancel previous password reset OTPs.'
                );
            }

            $this->commitOrFail();

            return AdminPasswordResetResult::success(
                'OTP verified successfully.',
                $adminUserId
            );
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }
    }

    /**
     * Replace the administrator's password after successful OTP verification.
     */
    public function resetPassword(
        int $adminUserId,
        string $password
    ): AdminPasswordResetResult {
        if (
            !$this->isValidResetAdmin(
                $adminUserId
            )
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

        $verificationId =
            (int) (
                $verifiedOtp['id']
                ?? 0
            );

        if ($verificationId <= 0) {
            return AdminPasswordResetResult::failure(
                'The password reset authorization is invalid.'
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
            /*
             * Re-lock the verified authorization in the transaction.
             *
             * This prevents concurrent reuse of the same OTP.
             */
            $currentVerification =
                $this->verificationModel
                ->lockVerified(
                    $verificationId,
                    $adminUserId
                );

            if (!is_array($currentVerification)) {
                $this->database->transRollback();

                return AdminPasswordResetResult::failure(
                    'This password reset authorization has already been used.'
                );
            }

            $lockedVerifiedAt =
                $this->parseUtcTimestamp(
                    (string) (
                        $currentVerification['verified_at']
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
                    $verificationId,
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

            $passwordUpdated =
                $this->adminModel->update(
                    $adminUserId,
                    [
                        'password_hash' =>
                        $passwordHash,

                        'password_set_at' =>
                        $this->utcNow(),
                    ]
                );

            if ($passwordUpdated === false) {
                throw new RuntimeException(
                    'Unable to update the administrator password.'
                );
            }

            /*
             * Consume the verified reset authorization.
             */
            $verificationConsumed =
                $this->verificationModel->update(
                    $verificationId,
                    [
                        'status' =>
                        AdminPasswordResetVerificationModel::STATUS_CANCELLED,
                    ]
                );

            if ($verificationConsumed === false) {
                throw new RuntimeException(
                    'Unable to consume the password reset authorization.'
                );
            }

            /*
             * Cancel every other usable reset authorization for this Admin.
             */
            $this->verificationModel
                ->where(
                    'admin_user_id',
                    $adminUserId
                )
                ->where(
                    'id !=',
                    $verificationId
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
     * Create and deliver a password-reset OTP.
     */
    private function issueOtp(
        int $adminUserId,
        string $mobileNumber
    ): AdminPasswordResetResult {
        if (
            !$this->isValidResetAdmin(
                $adminUserId
            )
        ) {
            return AdminPasswordResetResult::failure(
                'The password reset request is no longer valid.'
            );
        }

        $mobileNumber =
            IndianMobileNormalizer::normalize(
                $mobileNumber
            );

        if ($mobileNumber === null) {
            return AdminPasswordResetResult::failure(
                'The verified mobile number could not be found.'
            );
        }

        /*
         * SAME generator as Member.
         *
         * DEV / QA:
         * OTP_FIXED_VALUE may be used.
         *
         * PROD:
         * fixed OTP is prohibited and secure random generation is used.
         */
        $otp =
            OtpGenerator::generate(
                self::OTP_LENGTH
            );

        $now =
            new DateTimeImmutable(
                'now',
                new DateTimeZone('UTC')
            );

        $expiresAt =
            $now->add(
                new DateInterval(
                    'PT'
                        . self::OTP_EXPIRY_MINUTES
                        . 'M'
                )
            );

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

        $verificationId = null;

        $this->database->transBegin();

        try {
            /*
             * Respect the same resend cooldown as Member.
             */
            $pendingVerification =
                $this->verificationModel
                ->findLatestPending(
                    $adminUserId
                );

            if (is_array($pendingVerification)) {
                $cooldownRemaining =
                    $this->getCooldownRemainingSeconds(
                        $pendingVerification
                    );

                if ($cooldownRemaining > 0) {
                    $this->database->transRollback();

                    return AdminPasswordResetResult::failure(
                        sprintf(
                            'Please wait %d second%s before requesting another OTP.',
                            $cooldownRemaining,
                            $cooldownRemaining === 1
                                ? ''
                                : 's'
                        )
                    );
                }
            }

            /*
             * DELIVERY_FAILED records do not consume daily OTP allowance,
             * matching Member PasswordResetService.
             */
            $issuedCount =
                $this->verificationModel
                ->countDeliveredOrPendingSince(
                    $adminUserId,
                    $since
                );

            if (
                $issuedCount >=
                self::SEND_LIMIT_PER_DAY
            ) {
                $this->database->transRollback();

                return AdminPasswordResetResult::failure(
                    'The OTP request limit has been reached. '
                        . 'Please try again later.'
                );
            }

            $pendingCancelled =
                $this->verificationModel
                ->cancelPending(
                    $adminUserId
                );

            if (!$pendingCancelled) {
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
                    'Unable to create the administrator password reset OTP.'
                );
            }

            $this->commitOrFail();
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }

        /*
         * Same SMS abstraction/template configuration as Member.
         */
        try {
            $smsResult =
                $this->smsProvider->send(
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
                'Admin password reset OTP SMS failed: '
                    . 'admin_id={adminId}, error={error}',
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
     * Find administrator by registered email/mobile.
     *
     * This deliberately follows Member identifier normalization:
     *
     * - email -> trim + lowercase
     * - mobile -> IndianMobileNormalizer
     *
     * AdminUserModel remains the canonical Admin lookup.
     *
     * @return array<string, mixed>|null
     */
    private function findAdminByIdentifier(
        string $identifier
    ): ?array {
        $identifier =
            trim($identifier);

        if ($identifier === '') {
            return null;
        }

        if (
            filter_var(
                $identifier,
                FILTER_VALIDATE_EMAIL
            ) !== false
        ) {
            return $this->adminModel
                ->findByIdentifier(
                    mb_strtolower(
                        $identifier
                    )
                );
        }

        $normalizedMobile =
            IndianMobileNormalizer::normalize(
                $identifier
            );

        if ($normalizedMobile === null) {
            return null;
        }

        return $this->adminModel
            ->findByIdentifier(
                $normalizedMobile
            );
    }

    /**
     * Confirm the Admin remains eligible during the complete reset flow.
     *
     * This mirrors Member isValidResetContact().
     */
    private function isValidResetAdmin(
        int $adminUserId
    ): bool {
        if ($adminUserId <= 0) {
            return false;
        }

        $admin =
            $this->adminModel->find(
                $adminUserId
            );

        if (
            !is_array($admin)
            || !$this->isPasswordResetAllowedForAdmin(
                $admin
            )
        ) {
            return false;
        }

        return BooleanValue::fromDatabase(
            $admin['is_mobile_verified']
                ?? false
        );
    }

    /**
     * Determine whether the administrator may reset their password.
     *
     * Status is normalized before comparison, matching Member behaviour.
     *
     * @param array<string, mixed> $admin
     */
    private function isPasswordResetAllowedForAdmin(
        array $admin
    ): bool {
        $accountStatus =
            strtoupper(
                trim(
                    (string) (
                        $admin['account_status']
                        ?? ''
                    )
                )
            );

        if ($accountStatus === '') {
            return false;
        }

        return $accountStatus ===
            AdminUserModel::STATUS_VERIFIED;
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

        $updated =
            $this->verificationModel->update(
                $verificationId,
                [
                    'status' =>
                    AdminPasswordResetVerificationModel::STATUS_DELIVERY_FAILED,
                ]
            );

        if ($updated === false) {
            log_message(
                'critical',
                'Unable to mark failed Admin password reset OTP delivery: '
                    . 'verification_id={verificationId}',
                [
                    'verificationId' =>
                    $verificationId,
                ]
            );
        }
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
     * Parse database UTC timestamp.
     */
    private function parseUtcTimestamp(
        string $value
    ): ?int {
        if ($value === '') {
            return null;
        }

        try {
            return (
                new DateTimeImmutable(
                    $value,
                    new DateTimeZone('UTC')
                )
            )->getTimestamp();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Complete an active database transaction.
     */
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
     * Return remaining OTP resend cooldown.
     *
     * @param array<string, mixed> $verification
     */
    private function getCooldownRemainingSeconds(
        array $verification
    ): int {
        $createdAt =
            trim(
                (string) (
                    $verification['created_at']
                    ?? ''
                )
            );

        if ($createdAt === '') {
            return 0;
        }

        try {
            $createdAtTimestamp =
                (
                    new DateTimeImmutable(
                        $createdAt
                    )
                )->getTimestamp();
        } catch (Throwable) {
            /*
             * Malformed historical data must not permanently
             * block password reset.
             */
            return 0;
        }

        $resendAllowedAt =
            $createdAtTimestamp
            + self::OTP_RESEND_COOLDOWN_SECONDS;

        return max(
            0,
            $resendAllowedAt
                - time()
        );
    }
}
