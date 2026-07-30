<?php

declare(strict_types=1);

namespace App\Services\Registration;

use App\Models\UserContactModel;
use App\Models\UserModel;
use CodeIgniter\Database\BaseConnection;
use App\Support\IndianMobileNormalizer;
use App\Support\BooleanValue;
use RuntimeException;
use Throwable;

/**
 * Coordinates Register Free business logic.
 *
 * Registration cases:
 *
 * Case A:
 *     Existing verified mobile -> reject registration.
 *
 * Case B:
 *     Existing unverified mobile attached to a PENDING user
 *     -> update the pending registration.
 *
 * Case C:
 *     New mobile -> create a pending user and contact records.
 *
 * OTP handling:
 *     The user/contact transaction is committed first.
 *     RegistrationOtpService then applies rate limits and issues the OTP.
 *
 * This separation avoids creating an OTP inside an unfinished database
 * transaction and ensures initial OTPs and resends follow the same limits.
 */
final class RegisterFreeService
{
    /**
     * Maximum attempts when generating a random profile reference.
     */
    private const PROFILE_REFERENCE_ATTEMPTS = 20;

    /**
     * CHANGE:
     * ContactVerificationModel is no longer required directly here.
     *
     * OTP creation, cancellation and rate limiting are now handled by
     * RegistrationOtpService.
     */
    public function __construct(
        private readonly UserModel $userModel,
        private readonly UserContactModel $userContactModel,
        private readonly BaseConnection $database,
        private readonly RegistrationOtpService $otpService
    ) {}

    /**
     * Process the Register Free request.
     *
     * Registration creates only the mobile contact. Email can be added later
     * through a separate authenticated account-management workflow.
     *
     * @param array<string, mixed> $data Validated registration data.
     */
    public function register(array $data): RegisterFreeResult
    {
        $mobile = IndianMobileNormalizer::normalize(
            (string) $data['mobile_number']
        );

        if ($mobile === null) {
            throw new RuntimeException(
                'A valid Indian mobile number is required.'
            );
        }

        $gender = $this->resolveGender($data);

        $passwordHash = $this->hashPassword(
            (string) $data['password']
        );

        $this->database->transBegin();

        try {
            $existingMobile = $this->userContactModel
                ->findByNormalizedValue(
                    UserContactModel::TYPE_MOBILE,
                    $mobile
                );

            /*
         * A verified mobile belongs to an existing account and cannot start
         * another public registration.
         */
            if (
                $existingMobile !== null
                && $this->isVerified($existingMobile)
            ) {
                $this->database->transRollback();

                return RegisterFreeResult::fieldFailure(
                    RegistrationAction::VERIFIED_MOBILE_EXISTS,
                    'mobile_number',
                    'An account already exists with this mobile number. '
                        . 'Please log in or recover your account.'
                );
            }

            /*
         * Resume an existing unverified PENDING registration.
         */
            if ($existingMobile !== null) {
                $result = $this->updatePendingRegistration(
                    existingMobile: $existingMobile,
                    data: $data,
                    gender: $gender,
                    passwordHash: $passwordHash
                );

                if (!$result->successful) {
                    $this->database->transRollback();

                    return $result;
                }

                $this->commitOrFail();

                return $this->issueOtpForRegistration($result);
            }

            /*
         * Create a new pending user with one MOBILE contact row.
         */
            $result = $this->createPendingRegistration(
                data: $data,
                mobile: $mobile,
                gender: $gender,
                passwordHash: $passwordHash
            );

            $this->commitOrFail();

            return $this->issueOtpForRegistration($result);
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }
    }

    /**
     * CHANGE:
     * Issue an OTP after the pending registration transaction has committed.
     *
     * If the OTP send limit has been reached, the pending user remains in
     * the database. This is intentional:
     *
     * - the same mobile cannot bypass the limit by registering again;
     * - the user can continue after the 24-hour restriction ends;
     * - incomplete registration history remains auditable.
     */
    private function issueOtpForRegistration(
        RegisterFreeResult $registrationResult
    ): RegisterFreeResult {
        if (
            !$registrationResult->successful
            || $registrationResult->mobileContactId === null
        ) {
            throw new RuntimeException(
                'A successful pending registration is required '
                    . 'before issuing an OTP.'
            );
        }

        $otpResult = $this->otpService->issue(
            $registrationResult->mobileContactId
        );

        if (!$otpResult->successful) {
            return RegisterFreeResult::fieldFailure(
                RegistrationAction::OTP_LIMIT_REACHED,
                'mobile_number',
                $otpResult->message
            );
        }

        /**
         * Preserve the original registration result.
         *
         * The controller requires:
         * - userId
         * - mobileContactId
         * - profileReference
         *
         * It stores those values in the pending-registration session.
         */
        return $registrationResult;
    }

    /**
     * Update an existing unverified pending registration.
     *
     * @param array<string, mixed> $existingMobile
     * @param array<string, mixed> $data
     */
    private function updatePendingRegistration(
        array $existingMobile,
        array $data,
        string $gender,
        string $passwordHash
    ): RegisterFreeResult {
        $userId = (int) ($existingMobile['user_id'] ?? 0);

        if ($userId <= 0) {
            throw new RuntimeException(
                'The mobile contact does not contain a valid user ID.'
            );
        }

        $user = $this->userModel->find($userId);

        if (!is_array($user)) {
            throw new RuntimeException(
                'The user associated with this mobile number was not found.'
            );
        }

        /*
     * Only PENDING accounts can be changed through public registration.
     *
     * This prevents an unverified contact attached to an ACTIVE, SUSPENDED
     * or DELETED account from being used to reset account details.
     */
        if (
            (string) ($user['account_status'] ?? '')
            !== 'PENDING'
        ) {
            return RegisterFreeResult::fieldFailure(
                RegistrationAction::VERIFIED_MOBILE_EXISTS,
                'mobile_number',
                'This mobile number is already associated with an account. '
                    . 'Please log in or recover your account.'
            );
        }

        $mobileContactId = (int) (
            $existingMobile['id'] ?? 0
        );

        if ($mobileContactId <= 0) {
            throw new RuntimeException(
                'The existing mobile contact ID is invalid.'
            );
        }

        $userUpdated = $this->userModel->update(
            $userId,
            [
                'profile_created_for' =>
                (string) $data['profile_created_for'],

                'gender' => $gender,

                'full_name' => trim(
                    (string) $data['full_name']
                ),

                'password_hash' => $passwordHash,

                /*
             * account_status remains PENDING until OTP verification.
             */
            ]
        );

        if ($userUpdated === false) {
            throw new RuntimeException(
                'Unable to update the pending user.'
            );
        }

        /*
     * Keep the mobile unverified until the newly issued OTP succeeds.
     */
        $mobileUpdated = $this->userContactModel->update(
            $mobileContactId,
            [
                'is_primary' => true,
                'is_verified' => false,
                'verified_at' => null,
            ]
        );

        if ($mobileUpdated === false) {
            throw new RuntimeException(
                'Unable to update the mobile contact.'
            );
        }

        return RegisterFreeResult::success(
            RegistrationAction::PENDING_UPDATED,
            $userId,
            $mobileContactId,
            (string) $user['profile_ref_number']
        );
    }

    /**
     * Create a completely new pending registration.
     *
     * Exactly one contact row is created: the primary mobile contact.
     *
     * @param array<string, mixed> $data
     */
    private function createPendingRegistration(
        array $data,
        string $mobile,
        string $gender,
        string $passwordHash
    ): RegisterFreeResult {
        $profileReference =
            $this->generateUniqueProfileReference();

        $userId = $this->userModel->insert(
            [
                'profile_ref_number' =>
                $profileReference,

                'profile_created_for' =>
                (string) $data['profile_created_for'],

                'gender' => $gender,

                'full_name' => trim(
                    (string) $data['full_name']
                ),

                'password_hash' => $passwordHash,

                /*
             * Mobile OTP verification activates the account.
             */
                'account_status' => 'PENDING',
            ],
            true
        );

        if (!is_numeric($userId)) {
            throw new RuntimeException(
                'Unable to create the pending user.'
            );
        }

        $userId = (int) $userId;

        $mobileContactId = $this->userContactModel->insert(
            [
                'user_id' => $userId,

                'contact_type' =>
                UserContactModel::TYPE_MOBILE,

                'contact_value' => $mobile,
                'normalized_value' => $mobile,

                'is_primary' => true,
                'is_verified' => false,
                'verified_at' => null,
            ],
            true
        );

        if (!is_numeric($mobileContactId)) {
            throw new RuntimeException(
                'Unable to create the mobile contact.'
            );
        }

        return RegisterFreeResult::success(
            RegistrationAction::CREATED,
            $userId,
            (int) $mobileContactId,
            $profileReference
        );
    }

    /**
     * Infer gender when profile_created_for makes it unambiguous.
     *
     * @param array<string, mixed> $data
     */
    private function resolveGender(array $data): string
    {
        return match ((string) $data['profile_created_for']) {
            'self' => (string) $data['gender'],

            'son',
            'brother' => 'M',

            'daughter',
            'sister' => 'F',

            default => throw new RuntimeException(
                'Unsupported profile relationship.'
            ),
        };
    }

    /**
     * Safely interpret PostgreSQL/CodeIgniter boolean values.
     *
     * Depending on the database driver, a boolean may be returned as:
     *
     * - true/false
     * - 1/0
     * - "t"/"f"
     * - "true"/"false"
     *
     * @param array<string, mixed> $contact
     */
    private function isVerified(array $contact): bool
    {
        return BooleanValue::fromDatabase(
            $contact['is_verified'] ?? false
        );
    }

    /**
     * Commit the registration transaction or fail the entire operation.
     */
    private function commitOrFail(): void
    {
        if (!$this->database->transStatus()) {
            $this->database->transRollback();

            throw new RuntimeException(
                'The registration transaction failed.'
            );
        }

        $this->database->transCommit();
    }

    /**
     * Generate a unique profile reference in the format SAK1234567.
     *
     * The database unique index remains the final protection against
     * duplicate references caused by concurrent registration requests.
     */
    private function generateUniqueProfileReference(): string
    {
        for (
            $attempt = 1;
            $attempt <= self::PROFILE_REFERENCE_ATTEMPTS;
            $attempt++
        ) {
            $reference = 'SAK' . str_pad(
                (string) random_int(0, 9_999_999),
                7,
                '0',
                STR_PAD_LEFT
            );

            if (
                !$this->userModel
                    ->profileReferenceExists($reference)
            ) {
                return $reference;
            }
        }

        throw new RuntimeException(
            'Unable to generate a unique profile reference.'
        );
    }

    /**
     * Create a secure one-way password hash.
     *
     * The plain password must never be stored, logged or returned.
     */
    private function hashPassword(
        string $password
    ): string {
        $passwordHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        if (!is_string($passwordHash)) {
            throw new RuntimeException(
                'Unable to secure the password.'
            );
        }

        return $passwordHash;
    }
}
