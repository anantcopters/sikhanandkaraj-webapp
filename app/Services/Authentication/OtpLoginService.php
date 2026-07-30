<?php

declare(strict_types=1);

namespace App\Services\Authentication;

use App\Models\ContactVerificationModel;
use App\Models\UserContactModel;
use App\Models\UserModel;
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
 * Handles passwordless member authentication through a verified mobile OTP.
 *
 * This service:
 *
 * - accepts only verified mobile contacts;
 * - accepts only ACTIVE member accounts;
 * - creates purpose-specific LOGIN OTP records;
 * - enforces resend cooldown, expiry, daily limits and attempt limits;
 * - never creates an authenticated web session itself.
 *
 * Session creation remains a controller responsibility because the service
 * is independent of HTTP and browser-session state.
 */
final class OtpLoginService
{
    private const OTP_LENGTH = 4;

    private const OTP_EXPIRY_MINUTES = 3;

    private const OTP_RESEND_COOLDOWN_SECONDS = 120;

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
     * Validate the submitted mobile and issue a login OTP.
     */
    public function requestOtp(
        string $mobileNumber
    ): OtpLoginResult {
        $normalizedMobile =
            IndianMobileNormalizer::normalize(
                $mobileNumber
            );

        if ($normalizedMobile === null) {
            return OtpLoginResult::failure(
                'Please enter a valid 10-digit Indian mobile number.',
                'mobile_number'
            );
        }

        $contact = $this->contactModel
            ->findByNormalizedValue(
                UserContactModel::TYPE_MOBILE,
                $normalizedMobile
            );

        /*
         * Use a generic message when no contact exists to reduce account
         * enumeration through the public OTP-login form.
         */
        if (!is_array($contact)) {
            return $this->invalidLoginResult();
        }

        if (
            !BooleanValue::fromDatabase(
                $contact['is_verified'] ?? false
            )
        ) {
            return OtpLoginResult::failure(
                'OTP login is available only for a verified mobile number.',
                'mobile_number'
            );
        }

        $userId = $contact['user_id'] ?? null;
        $contactId = $contact['id'] ?? null;

        if (
            !is_numeric($userId)
            || !is_numeric($contactId)
        ) {
            return $this->invalidLoginResult();
        }

        $userId = (int) $userId;
        $contactId = (int) $contactId;

        $user = $this->userModel->find(
            $userId
        );

        if (!is_array($user)) {
            return $this->invalidLoginResult();
        }

        $accountStatus = mb_strtoupper(
            trim(
                (string) (
                    $user['account_status']
                    ?? ''
                )
            )
        );

        if (
            $accountStatus
            !== UserModel::STATUS_ACTIVE
        ) {
            return $this->inactiveAccountResult(
                $accountStatus
            );
        }

        return $this->issueOtp(
            $userId,
            $contactId,
            $normalizedMobile
        );
    }

    /**
     * Resend the login OTP for the same verified contact.
     */
    public function resendOtp(
        int $userId,
        int $mobileContactId
    ): OtpLoginResult {
        $context = $this->resolveEligibleLoginContext(
            $userId,
            $mobileContactId
        );

        if ($context === null) {
            return OtpLoginResult::failure(
                'The OTP login request is no longer valid.'
            );
        }

        return $this->issueOtp(
            $userId,
            $mobileContactId,
            $context['normalizedMobile']
        );
    }

    /**
     * Return the expiry timestamp of the current pending login OTP.
     */
    public function getPendingExpiryTimestamp(
        int $mobileContactId
    ): ?int {
        if ($mobileContactId <= 0) {
            return null;
        }

        $verification = $this->verificationModel
            ->findLatestPendingForContact(
                $mobileContactId,
                ContactVerificationModel::PURPOSE_LOGIN
            );

        if (!is_array($verification)) {
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
     * Verify a LOGIN OTP and return the authenticated user record.
     *
     * This method does not modify contact verification status because the
     * submitted mobile must already be verified before an OTP is issued.
     */
    public function verifyOtp(
        int $userId,
        int $mobileContactId,
        string $submittedOtp
    ): OtpLoginResult {
        if (
            !preg_match(
                '/^\d{4}$/',
                $submittedOtp
            )
        ) {
            return OtpLoginResult::failure(
                'Please enter a valid four-digit OTP.'
            );
        }

        if (
            $this->resolveEligibleLoginContext(
                $userId,
                $mobileContactId
            ) === null
        ) {
            return OtpLoginResult::failure(
                'The OTP login request is no longer valid.'
            );
        }

        $this->database->transBegin();

        try {
            $verification =
                $this->verificationModel
                ->findLatestPendingForContact(
                    $mobileContactId,
                    ContactVerificationModel
                    ::PURPOSE_LOGIN
                );

            if (!is_array($verification)) {
                $this->database->transRollback();

                return OtpLoginResult::failure(
                    'The OTP is no longer valid. '
                        . 'Please request a new OTP.'
                );
            }

            $verificationId = (int) (
                $verification['id']
                ?? 0
            );

            if ($verificationId <= 0) {
                throw new RuntimeException(
                    'The login OTP record has an invalid identifier.'
                );
            }

            $expiresAt = $this->parseUtcTimestamp(
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
                        $verificationId
                    );

                $this->commitOrFail();

                return OtpLoginResult::failure(
                    'The OTP has expired. '
                        . 'Please request a new OTP.'
                );
            }

            $attemptCount = (int) (
                $verification['attempt_count']
                ?? 0
            );

            if (
                $attemptCount
                >= self::VERIFY_ATTEMPT_LIMIT
            ) {
                $this->verificationModel->update(
                    $verificationId,
                    [
                        'status' =>
                        ContactVerificationModel
                        ::STATUS_CANCELLED,
                    ]
                );

                $this->commitOrFail();

                return OtpLoginResult::failure(
                    'Too many incorrect attempts. '
                        . 'Please request a new OTP.'
                );
            }

            $otpMatches = password_verify(
                $submittedOtp,
                (string) (
                    $verification['otp_hash']
                    ?? ''
                )
            );

            if (!$otpMatches) {
                $this->verificationModel
                    ->incrementAttemptCount(
                        $verificationId
                    );

                $remainingAttempts = max(
                    0,
                    self::VERIFY_ATTEMPT_LIMIT
                        - $attemptCount
                        - 1
                );

                if ($remainingAttempts === 0) {
                    $this->verificationModel->update(
                        $verificationId,
                        [
                            'status' =>
                            ContactVerificationModel
                            ::STATUS_CANCELLED,
                        ]
                    );
                }

                $this->commitOrFail();

                return OtpLoginResult::failure(
                    $remainingAttempts > 0
                        ? 'Incorrect OTP. '
                        . $remainingAttempts
                        . ' attempt(s) remaining.'
                        : 'Too many incorrect attempts. '
                        . 'Please request a new OTP.'
                );
            }

            /*
             * Reload and recheck the account before completing login.
             *
             * The account may have been suspended after OTP issuance.
             */
            $context =
                $this->resolveEligibleLoginContext(
                    $userId,
                    $mobileContactId
                );

            if ($context === null) {
                $this->verificationModel->update(
                    $verificationId,
                    [
                        'status' =>
                        ContactVerificationModel
                        ::STATUS_CANCELLED,
                    ]
                );

                $this->commitOrFail();

                return OtpLoginResult::failure(
                    'Your account is not eligible for OTP login.'
                );
            }

            $verifiedAt = (
                new DateTimeImmutable(
                    'now',
                    new DateTimeZone('UTC')
                )
            )->format('Y-m-d H:i:sP');

            $updated =
                $this->verificationModel->update(
                    $verificationId,
                    [
                        'status' =>
                        ContactVerificationModel
                        ::STATUS_VERIFIED,

                        'verified_at' =>
                        $verifiedAt,
                    ]
                );

            if ($updated === false) {
                throw new RuntimeException(
                    'The login OTP could not be completed.'
                );
            }

            /*
             * No other pending LOGIN OTP may remain usable for the contact.
             */
            $this->verificationModel
                ->cancelPendingForContact(
                    $mobileContactId,
                    ContactVerificationModel
                    ::PURPOSE_LOGIN
                );

            $this->commitOrFail();

            return OtpLoginResult::authenticated(
                $context['user']
            );
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }
    }

    /**
     * Generate, persist and deliver a login OTP.
     */
    private function issueOtp(
        int $userId,
        int $mobileContactId,
        string $normalizedMobile
    ): OtpLoginResult {
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
            ->format('Y-m-d H:i:sP');

        $issuedCount = $this->verificationModel
            ->countDeliveredOrPendingSince(
                $mobileContactId,
                ContactVerificationModel::PURPOSE_LOGIN,
                $windowStart
            );

        if (
            $issuedCount
            >= self::SEND_LIMIT_PER_DAY
        ) {
            return OtpLoginResult::failure(
                'The OTP request limit has been reached. '
                    . 'Please try again later.'
            );
        }

        $pending = $this->verificationModel
            ->findLatestPendingForContact(
                $mobileContactId,
                ContactVerificationModel::PURPOSE_LOGIN
            );

        if (is_array($pending)) {
            $pendingCreatedAt =
                $this->parseUtcTimestamp(
                    (string) (
                        $pending['created_at']
                        ?? ''
                    )
                );

            if (
                $pendingCreatedAt !== null
                && (
                    time() - $pendingCreatedAt
                ) < self::OTP_RESEND_COOLDOWN_SECONDS
            ) {
                $retryAfter = max(
                    1,
                    self::OTP_RESEND_COOLDOWN_SECONDS
                        - (
                            time()
                            - $pendingCreatedAt
                        )
                );

                return OtpLoginResult::failure(
                    'Please wait '
                        . $retryAfter
                        . ' second(s) before requesting another OTP.'
                );
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
                'The login OTP could not be secured.'
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
            ->format('Y-m-d H:i:sP');

        $this->database->transBegin();

        try {
            $this->verificationModel
                ->cancelPendingForContact(
                    $mobileContactId,
                    ContactVerificationModel
                    ::PURPOSE_LOGIN
                );

            $verificationId =
                $this->verificationModel->insert(
                    [
                        'user_contact_id' =>
                        $mobileContactId,

                        'purpose' =>
                        ContactVerificationModel
                        ::PURPOSE_LOGIN,

                        'otp_hash' =>
                        $otpHash,

                        'expires_at' =>
                        $expiresAt,

                        'attempt_count' =>
                        0,

                        'resend_count' =>
                        0,

                        'status' =>
                        ContactVerificationModel
                        ::STATUS_PENDING,
                    ],
                    true
                );

            if ($verificationId === false) {
                throw new RuntimeException(
                    'The login OTP record could not be created.'
                );
            }

            $message = new SmsMessage(
                destination: $normalizedMobile,
                message: 'Your SikhAnandKaraj login OTP is '
                    . $otp
                    . '. It is valid for '
                    . self::OTP_EXPIRY_MINUTES
                    . ' minutes.'
            );

            try {
                $this->smsProvider->send(
                    $message
                );
            } catch (Throwable $exception) {
                $this->verificationModel->update(
                    (int) $verificationId,
                    [
                        'status' =>
                        ContactVerificationModel
                        ::STATUS_DELIVERY_FAILED,
                    ]
                );

                $this->commitOrFail();

                log_message(
                    'error',
                    'Login OTP delivery failed for user {userId}: {message}',
                    [
                        'userId' => $userId,
                        'message' =>
                        $exception->getMessage(),
                    ]
                );

                return OtpLoginResult::failure(
                    'We could not send the OTP. '
                        . 'Please try again after a few moments.'
                );
            }

            $this->commitOrFail();

            return OtpLoginResult::otpIssued(
                $userId,
                $mobileContactId,
                'An OTP has been sent to your '
                    . 'verified mobile number.'
            );
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }
    }

    /**
     * Resolve an active user and verified mobile contact.
     *
     * @return array{
     *     user: array<string, mixed>,
     *     contact: array<string, mixed>,
     *     normalizedMobile: string
     * }|null
     */
    private function resolveEligibleLoginContext(
        int $userId,
        int $mobileContactId
    ): ?array {
        if (
            $userId <= 0
            || $mobileContactId <= 0
        ) {
            return null;
        }

        $user = $this->userModel->find(
            $userId
        );

        if (!is_array($user)) {
            return null;
        }

        $accountStatus = mb_strtoupper(
            trim(
                (string) (
                    $user['account_status']
                    ?? ''
                )
            )
        );

        if (
            $accountStatus
            !== UserModel::STATUS_ACTIVE
        ) {
            return null;
        }

        $contact = $this->contactModel->find(
            $mobileContactId
        );

        if (!is_array($contact)) {
            return null;
        }

        if (
            (int) (
                $contact['user_id']
                ?? 0
            ) !== $userId
        ) {
            return null;
        }

        if (
            mb_strtoupper(
                trim(
                    (string) (
                        $contact['contact_type']
                        ?? ''
                    )
                )
            )
            !== UserContactModel::TYPE_MOBILE
        ) {
            return null;
        }

        if (
            !BooleanValue::fromDatabase(
                $contact['is_verified']
                    ?? false
            )
        ) {
            return null;
        }

        $normalizedMobile = trim(
            (string) (
                $contact['normalized_value']
                ?? ''
            )
        );

        if ($normalizedMobile === '') {
            return null;
        }

        return [
            'user' => $user,
            'contact' => $contact,
            'normalizedMobile' =>
            $normalizedMobile,
        ];
    }

    private function inactiveAccountResult(
        string $accountStatus
    ): OtpLoginResult {
        return match ($accountStatus) {
            UserModel::STATUS_PENDING =>
            OtpLoginResult::failure(
                'Your registration is not complete. '
                    . 'Please complete mobile verification first.'
            ),

            UserModel::STATUS_SUSPENDED =>
            OtpLoginResult::failure(
                'Your account has been suspended. '
                    . 'Please contact support for assistance.'
            ),

            UserModel::STATUS_DELETED =>
            OtpLoginResult::failure(
                'This account is no longer available. '
                    . 'Please contact support for assistance.'
            ),

            default =>
            OtpLoginResult::failure(
                'Your account is not currently active. '
                    . 'Please contact support for assistance.'
            ),
        };
    }

    private function invalidLoginResult(): OtpLoginResult
    {
        return OtpLoginResult::failure(
            'OTP login is unavailable for the entered mobile number.',
            'mobile_number'
        );
    }

    private function parseUtcTimestamp(
        string $value
    ): ?int {
        $value = trim($value);

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

    private function commitOrFail(): void
    {
        if (
            $this->database->transStatus()
            === false
        ) {
            $this->database->transRollback();

            throw new RuntimeException(
                'The login OTP transaction failed.'
            );
        }

        $this->database->transCommit();
    }
}
