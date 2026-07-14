<?php

declare(strict_types=1);

namespace App\Services\Registration;

use App\Models\UserContactModel;
use App\Models\UserModel;
use CodeIgniter\Database\BaseConnection;
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
     * @param array<string, mixed> $data Validated registration data.
     */
    public function register(array $data): RegisterFreeResult
    {
        $mobile = $this->normalizeIndianMobile(
            (string) $data['mobile_number']
        );

        $email = $this->normalizeEmail(
            (string) $data['email']
        );

        $gender = $this->resolveGender($data);

        $passwordHash = $this->hashPassword(
            (string) $data['password']
        );

        $this->database->transBegin();

        try {
            /**
             * Search by normalized E.164-style mobile number.
             *
             * Example:
             * 9876543210 becomes +919876543210.
             */
            $existingMobile = $this->userContactModel
                ->findByNormalizedValue(
                    UserContactModel::TYPE_MOBILE,
                    $mobile
                );

            /**
             * -------------------------------------------------------------
             * Case A: verified mobile already exists
             * -------------------------------------------------------------
             *
             * A verified mobile belongs to an existing account and cannot
             * be reused through the public registration form.
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

            /**
             * -------------------------------------------------------------
             * Case B: unverified mobile already exists
             * -------------------------------------------------------------
             */
            if ($existingMobile !== null) {
                $result = $this->updatePendingRegistration(
                    existingMobile: $existingMobile,
                    data: $data,
                    email: $email,
                    gender: $gender,
                    passwordHash: $passwordHash
                );

                /**
                 * A business-rule failure must not commit changes.
                 *
                 * For example, an unverified mobile attached to an ACTIVE
                 * or SUSPENDED account cannot be overwritten.
                 */
                if (!$result->successful) {
                    $this->database->transRollback();

                    return $result;
                }

                /**
                 * CHANGE:
                 * Commit user/contact changes before issuing an OTP.
                 */
                $this->commitOrFail();

                /**
                 * CHANGE:
                 * Issue the OTP through the shared OTP service.
                 *
                 * This ensures:
                 * - initial registration OTPs count towards the limit;
                 * - registration resubmissions count towards the limit;
                 * - resend requests use the same limit;
                 * - old pending OTPs are cancelled consistently.
                 */
                return $this->issueOtpForRegistration($result);
            }

            /**
             * -------------------------------------------------------------
             * Case C: mobile does not exist
             * -------------------------------------------------------------
             */
            $result = $this->createPendingRegistration(
                data: $data,
                mobile: $mobile,
                email: $email,
                gender: $gender,
                passwordHash: $passwordHash
            );

            /**
             * CHANGE:
             * Commit the new pending user and contacts before issuing OTP.
             */
            $this->commitOrFail();

            /**
             * CHANGE:
             * Apply the same OTP issue and rate-limit logic.
             */
            return $this->issueOtpForRegistration($result);
        } catch (Throwable $exception) {
            /**
             * Rollback is safe even when the transaction has already been
             * committed. The database layer will have no active work to undo.
             *
             * Do not condition rollback on transStatus(), because a failed
             * query normally changes transaction status to false.
             */
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
     * Case B: update an existing pending registration.
     *
     * The mobile contact is already known to be unverified before this
     * method is called. This method additionally confirms that its user
     * account is still PENDING.
     *
     * @param array<string, mixed> $existingMobile
     * @param array<string, mixed> $data
     */
    private function updatePendingRegistration(
        array $existingMobile,
        array $data,
        string $email,
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

        /**
         * Only PENDING accounts may be updated through registration.
         *
         * This prevents an unverified mobile belonging to an ACTIVE,
         * SUSPENDED or DELETED account from being reset to PENDING.
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

        /**
         * Update the latest details submitted by the user.
         *
         * A pending registration may be resubmitted, so the most recently
         * submitted password becomes the pending account password.
         */
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

                /**
             * account_status is deliberately not changed here.
             *
             * It has already been confirmed as PENDING.
             */
            ]
        );

        if ($userUpdated === false) {
            throw new RuntimeException(
                'Unable to update the pending user.'
            );
        }

        /**
         * Keep the mobile unverified until the newly issued OTP has been
         * successfully verified.
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

        /**
         * Update or create the primary email contact.
         */
        $this->upsertEmailContact(
            userId: $userId,
            email: $email
        );

        /**
         * CHANGE:
         * OTP is not generated inside this method anymore.
         *
         * It will be issued only after the user/contact transaction commits.
         */

        return RegisterFreeResult::success(
            RegistrationAction::PENDING_UPDATED,
            $userId,
            $mobileContactId,
            (string) $user['profile_ref_number']
        );
    }

    /**
     * Case C: create a completely new pending registration.
     *
     * @param array<string, mixed> $data
     */
    private function createPendingRegistration(
        array $data,
        string $mobile,
        string $email,
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

                /**
                 * The account becomes ACTIVE only after successful
                 * mobile OTP verification.
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

        /**
         * Create the unverified primary mobile contact.
         */
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

        $mobileContactId = (int) $mobileContactId;

        /**
         * Create the unverified primary email contact.
         */
        $emailContactId = $this->userContactModel->insert(
            [
                'user_id' => $userId,

                'contact_type' =>
                UserContactModel::TYPE_EMAIL,

                'contact_value' => $email,
                'normalized_value' => $email,

                'is_primary' => true,
                'is_verified' => false,
                'verified_at' => null,
            ],
            true
        );

        if (!is_numeric($emailContactId)) {
            throw new RuntimeException(
                'Unable to create the email contact.'
            );
        }

        /**
         * CHANGE:
         * OTP is not generated here.
         *
         * It will be generated after the pending user and contacts have
         * been committed successfully.
         */

        return RegisterFreeResult::success(
            RegistrationAction::CREATED,
            $userId,
            $mobileContactId,
            $profileReference
        );
    }

    /**
     * Insert or update the user's primary email contact.
     */
    private function upsertEmailContact(
        int $userId,
        string $email
    ): void {
        $existingEmail = $this->userContactModel
            ->findPrimaryForUser(
                $userId,
                UserContactModel::TYPE_EMAIL
            );

        /**
         * No email contact exists yet, so create one.
         */
        if ($existingEmail === null) {
            $emailContactId =
                $this->userContactModel->insert(
                    [
                        'user_id' => $userId,

                        'contact_type' =>
                        UserContactModel::TYPE_EMAIL,

                        'contact_value' => $email,
                        'normalized_value' => $email,

                        'is_primary' => true,
                        'is_verified' => false,
                        'verified_at' => null,
                    ],
                    true
                );

            if (!is_numeric($emailContactId)) {
                throw new RuntimeException(
                    'Unable to create the email contact.'
                );
            }

            return;
        }

        /**
         * A resubmitted or changed email must be verified again.
         */
        $emailUpdated = $this->userContactModel->update(
            (int) $existingEmail['id'],
            [
                'contact_value' => $email,
                'normalized_value' => $email,
                'is_primary' => true,
                'is_verified' => false,
                'verified_at' => null,
            ]
        );

        if ($emailUpdated === false) {
            throw new RuntimeException(
                'Unable to update the email contact.'
            );
        }
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
     * Normalize an Indian mobile number for consistent lookup/storage.
     *
     * The validated form supplies ten digits. The normalized database
     * value is stored in an E.164-style format such as +919876543210.
     */
    private function normalizeIndianMobile(
        string $mobileNumber
    ): string {
        $digits = preg_replace(
            '/\D+/',
            '',
            $mobileNumber
        ) ?? '';

        /**
         * Prevent accidentally duplicating the country code if this method
         * receives a value that already includes 91.
         */
        if (
            strlen($digits) === 12
            && str_starts_with($digits, '91')
        ) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) !== 10) {
            throw new RuntimeException(
                'A valid ten-digit mobile number is required.'
            );
        }

        return '+91' . $digits;
    }

    /**
     * Normalize email for comparison and storage.
     */
    private function normalizeEmail(string $email): string
    {
        return mb_strtolower(
            trim($email)
        );
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
        return filter_var(
            $contact['is_verified'] ?? false,
            FILTER_VALIDATE_BOOLEAN
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
