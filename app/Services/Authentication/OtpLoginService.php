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
     *
     * Public responses deliberately do not reveal whether:
     *
     * - the mobile number exists;
     * - the mobile number is verified;
     * - the account is pending;
     * - the account is suspended;
     * - the account has been deleted.
     *
     * Returning the same failure for each ineligible state reduces account
     * enumeration through the public OTP-login endpoint.
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

        if (!is_array($contact)) {
            return $this->invalidLoginResult();
        }

        /*
        * Do not tell the public caller whether this contact exists but remains
        * unverified. That distinction would expose registered mobile numbers.
        */
        if (
            !BooleanValue::fromDatabase(
                $contact['is_verified']
                    ?? false
            )
        ) {
            return $this->invalidLoginResult();
        }

        $userId = $contact['user_id']
            ?? null;

        $contactId = $contact['id']
            ?? null;

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

        /*
        * UserModel soft-delete handling means deleted users normally do not
        * reach the status check below. Use the same generic public response.
        */
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

        /*
        * Do not expose PENDING, SUSPENDED, DELETED or unknown account states
        * through passwordless login initiation.
        */
        if (
            $accountStatus
            !== UserModel::STATUS_ACTIVE
        ) {
            return $this->invalidLoginResult();
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

            /*
            * Complete the OTP authentication transaction first.
            *
            * last_login_at is operational metadata and must not be allowed to roll back
            * an otherwise successfully verified OTP.
            */
            $this->commitOrFail();

            /*
            * Authentication has now succeeded.
            *
            * Record the activity outside the OTP transaction. Failure to update this
            * auxiliary timestamp must not invalidate the successful OTP authentication.
            */
            $loginRecorded =
                $this->userModel
                ->recordSuccessfulLogin(
                    $userId
                );

            if (!$loginRecorded) {
                log_message(
                    'warning',
                    'Successful OTP login activity could not be recorded. '
                        . 'Member: {memberId}.',
                    [
                        'memberId' =>
                        $userId,
                    ]
                );
            }

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
     *
     * The database transaction is completed before calling the external SMS
     * provider. Network calls must not keep a database transaction open.
     *
     * When delivery fails, the OTP record is marked DELIVERY_FAILED so it cannot
     * be used and does not consume the successful delivery quota.
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

        /*
     * Count OTPs that were successfully delivered or remain potentially
     * usable. DELIVERY_FAILED records are excluded by the model.
     */
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

        $pendingVerification =
            $this->verificationModel
            ->findLatestPendingForContact(
                $mobileContactId,
                ContactVerificationModel::PURPOSE_LOGIN
            );

        if (is_array($pendingVerification)) {
            $pendingCreatedAt =
                $this->parseUtcTimestamp(
                    (string) (
                        $pendingVerification['created_at']
                        ?? ''
                    )
                );

            if ($pendingCreatedAt !== null) {
                $elapsedSeconds =
                    time() - $pendingCreatedAt;

                if (
                    $elapsedSeconds
                    < self::OTP_RESEND_COOLDOWN_SECONDS
                ) {
                    $retryAfter = max(
                        1,
                        self::OTP_RESEND_COOLDOWN_SECONDS
                            - $elapsedSeconds
                    );

                    return OtpLoginResult::failure(
                        sprintf(
                            'Please wait %d second%s before requesting another OTP.',
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

        $verificationId = null;

        $this->database->transBegin();

        try {
            /*
         * Only one pending LOGIN OTP may remain usable for this mobile
         * contact. Cancellation and replacement happen atomically.
         */
            $pendingCancelled =
                $this->verificationModel
                ->cancelPendingForContact(
                    $mobileContactId,
                    ContactVerificationModel::PURPOSE_LOGIN
                );

            if (!$pendingCancelled) {
                throw new RuntimeException(
                    'Unable to cancel the previous login OTP.'
                );
            }

            $verificationId =
                $this->verificationModel->insert(
                    [
                        'user_contact_id' =>
                        $mobileContactId,

                        'purpose' =>
                        ContactVerificationModel::PURPOSE_LOGIN,

                        'otp_hash' =>
                        $otpHash,

                        'expires_at' =>
                        $expiresAt,

                        'attempt_count' =>
                        0,

                        'resend_count' =>
                        0,

                        'status' =>
                        ContactVerificationModel::STATUS_PENDING,

                        'verified_at' =>
                        null,
                    ],
                    true
                );

            if (!is_numeric($verificationId)) {
                throw new RuntimeException(
                    'The login OTP record could not be created.'
                );
            }

            /*
         * Commit before contacting the external SMS service. Holding an open
         * transaction during a remote API call can unnecessarily lock database
         * resources and make failures harder to recover from.
         */
            $this->commitOrFail();
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }

        try {
            $smsResult = $this->smsProvider->send(
                new SmsMessage(
                    mobileNumber: $normalizedMobile,

                    message: 'Your Sikhanandkaraj login OTP is '
                        . $otp
                        . '. It is valid for '
                        . self::OTP_EXPIRY_MINUTES
                        . ' minutes.',

                    /*
                 * Add this environment key for providers that require a
                 * pre-approved DLT or SMS template.
                 */
                    templateId: trim(
                        (string) env(
                            'sms.loginOtpTemplateId'
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

            log_message(
                'error',
                'Login OTP SMS threw an exception: '
                    . 'user_id={userId}, contact_id={contactId}, error={error}',
                [
                    'userId' =>
                    $userId,

                    'contactId' =>
                    $mobileContactId,

                    'error' =>
                    $exception->getMessage(),
                ]
            );

            return OtpLoginResult::failure(
                'We could not send the OTP. '
                    . 'Please try again after a few moments.'
            );
        }

        /*
        * SMS providers may report a normal failure result rather than throwing
        * an exception. Both conditions must be handled.
        */
        if (!$smsResult->successful) {
            $this->markOtpDeliveryFailed(
                (int) $verificationId
            );

            log_message(
                'error',
                'Login OTP SMS failed: '
                    . 'user_id={userId}, contact_id={contactId}, error={error}',
                [
                    'userId' =>
                    $userId,

                    'contactId' =>
                    $mobileContactId,

                    'error' =>
                    $smsResult->errorMessage
                        ?? 'Unknown SMS provider error.',
                ]
            );

            return OtpLoginResult::failure(
                'We could not send the OTP. '
                    . 'Please try again after a few moments.'
            );
        }

        return OtpLoginResult::otpIssued(
            $userId,
            $mobileContactId,
            'An OTP has been sent to your verified mobile number.'
        );
    }

    /**
     * Mark an OTP as unusable when its SMS delivery fails.
     *
     * A separate update is used because the OTP record is committed before the
     * external provider is called.
     */
    private function markOtpDeliveryFailed(
        int $verificationId
    ): void {
        if ($verificationId <= 0) {
            return;
        }

        try {
            $updated =
                $this->verificationModel->update(
                    $verificationId,
                    [
                        'status' =>
                        ContactVerificationModel
                        ::STATUS_DELIVERY_FAILED,
                    ]
                );

            if ($updated === false) {
                log_message(
                    'critical',
                    'Unable to mark login OTP delivery as failed: '
                        . 'verification_id={verificationId}',
                    [
                        'verificationId' =>
                        $verificationId,
                    ]
                );
            }
        } catch (Throwable $exception) {
            /*
         * Preserve the original SMS-delivery result. A secondary database
         * cleanup failure must be logged but should not mask it.
         */
            log_message(
                'critical',
                'Exception while marking login OTP delivery as failed: '
                    . 'verification_id={verificationId}, error={error}',
                [
                    'verificationId' =>
                    $verificationId,

                    'error' =>
                    $exception->getMessage(),
                ]
            );
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

    /**
     * Return the public failure used for every ineligible OTP-login identity.
     *
     * Using one response prevents callers from determining whether a mobile
     * number exists, is verified, or belongs to an inactive account.
     */
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
