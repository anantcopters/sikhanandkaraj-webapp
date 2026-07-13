<?php

declare(strict_types=1);

namespace App\Services\Registration;

use App\Models\ContactVerificationModel;
use App\Models\UserContactModel;
use App\Models\UserModel;
use CodeIgniter\Database\BaseConnection;
use RuntimeException;
use Throwable;

/**
 * Coordinates Register Free business logic.
 *
 * This service handles:
 *
 * Case A:
 *     Existing verified mobile -> reject registration.
 *
 * Case B:
 *     Existing unverified mobile -> update pending registration.
 *
 * Case C:
 *     New mobile -> create pending user and contact records.
 */
final class RegisterFreeService
{
    public function __construct(
        private readonly UserModel $userModel,
        private readonly UserContactModel $userContactModel,
        private readonly ContactVerificationModel $verificationModel,
        private readonly BaseConnection $database
    ) {}

    /**
     * Process the Register Free request.
     *
     * @param array<string, mixed> $data
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

        $this->database->transBegin();

        try {
            $existingMobile = $this->userContactModel
                ->findByNormalizedValue(
                    UserContactModel::TYPE_MOBILE,
                    $mobile
                );

            /**
             * Case A:
             * The mobile already belongs to a verified account.
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
             * Case B:
             * Mobile exists but is not verified.
             *
             * The existing account must also be PENDING. An ACTIVE,
             * SUSPENDED or DELETED account must never be overwritten
             * through the Register Free form.
             */
            if ($existingMobile !== null) {
                $result = $this->updatePendingRegistration(
                    existingMobile: $existingMobile,
                    data: $data,
                    email: $email,
                    gender: $gender
                );

                /**
                 * A business-rule failure is returned without committing
                 * any database changes.
                 */
                if (!$result->successful) {
                    $this->database->transRollback();

                    return $result;
                }

                $this->commitOrFail();

                return $result;
            }

            /**
             * Case C:
             * No account currently uses this mobile.
             */
            $result = $this->createPendingRegistration(
                data: $data,
                mobile: $mobile,
                email: $email,
                gender: $gender
            );

            $this->commitOrFail();

            return $result;
        } catch (Throwable $exception) {
            /**
             * Always roll back when an exception occurs.
             *
             * Do not condition rollback on transStatus(), because a failed
             * query normally changes the transaction status to false.
             */
            $this->database->transRollback();

            throw $exception;
        }
    }

    /**
     * Case B: update an existing pending registration.
     *
     * The mobile contact is known to be unverified before this method
     * is called. This method additionally verifies that the corresponding
     * user account is still PENDING.
     *
     * @param array<string, mixed> $existingMobile
     * @param array<string, mixed> $data
     */
    private function updatePendingRegistration(
        array $existingMobile,
        array $data,
        string $email,
        string $gender
    ): RegisterFreeResult {
        $userId = (int) $existingMobile['user_id'];

        $user = $this->userModel->find($userId);

        if (!is_array($user)) {
            throw new RuntimeException(
                'The user associated with this mobile number was not found.'
            );
        }

        /**
         * Only pending registrations may be updated through this flow.
         *
         * This prevents an unverified contact belonging to an ACTIVE,
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

        $mobileContactId = (int) $existingMobile['id'];

        $updated = $this->userModel->update($userId, [
            'profile_created_for' =>
            (string) $data['profile_created_for'],

            'gender' => $gender,

            'full_name' => trim(
                (string) $data['full_name']
            ),

            /**
         * The existing account is already confirmed as PENDING,
         * so there is no need to overwrite account_status here.
         */
        ]);

        if ($updated === false) {
            throw new RuntimeException(
                'Unable to update the pending user.'
            );
        }

        /**
         * Keep the existing mobile record unverified while a new
         * registration OTP is pending.
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

        $this->upsertEmailContact(
            userId: $userId,
            email: $email
        );

        $this->createRegistrationOtp($mobileContactId);

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
        string $gender
    ): RegisterFreeResult {

        $profileReference = $this->generateUniqueProfileReference();
        $userId = $this->userModel->insert([
            'profile_ref_number' => $profileReference,
            'profile_created_for' =>
            (string) $data['profile_created_for'],

            'gender' => $gender,

            'full_name' => trim(
                (string) $data['full_name']
            ),

            'account_status' => 'PENDING',
        ], true);

        if (!is_numeric($userId)) {
            throw new RuntimeException(
                'Unable to create the pending user.'
            );
        }

        $userId = (int) $userId;

        $mobileContactId = $this->userContactModel->insert([
            'user_id' => $userId,

            'contact_type' =>
            UserContactModel::TYPE_MOBILE,

            'contact_value' => $mobile,
            'normalized_value' => $mobile,

            'is_primary' => true,
            'is_verified' => false,
            'verified_at' => null,
        ], true);

        if (!is_numeric($mobileContactId)) {
            throw new RuntimeException(
                'Unable to create the mobile contact.'
            );
        }

        $mobileContactId = (int) $mobileContactId;

        $emailContactId = $this->userContactModel->insert([
            'user_id' => $userId,

            'contact_type' =>
            UserContactModel::TYPE_EMAIL,

            'contact_value' => $email,
            'normalized_value' => $email,

            'is_primary' => true,
            'is_verified' => false,
            'verified_at' => null,
        ], true);

        if (!is_numeric($emailContactId)) {
            throw new RuntimeException(
                'Unable to create the email contact.'
            );
        }

        $this->createRegistrationOtp($mobileContactId);

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

        if ($existingEmail === null) {
            $emailContactId = $this->userContactModel->insert([
                'user_id' => $userId,

                'contact_type' =>
                UserContactModel::TYPE_EMAIL,

                'contact_value' => $email,
                'normalized_value' => $email,

                'is_primary' => true,
                'is_verified' => false,
                'verified_at' => null,
            ], true);

            if (!is_numeric($emailContactId)) {
                throw new RuntimeException(
                    'Unable to create the email contact.'
                );
            }

            return;
        }

        /**
         * A changed email must be verified again.
         */
        $updated = $this->userContactModel->update(
            (int) $existingEmail['id'],
            [
                'contact_value' => $email,
                'normalized_value' => $email,
                'is_verified' => false,
                'verified_at' => null,
            ]
        );

        if ($updated === false) {
            throw new RuntimeException(
                'Unable to update the email contact.'
            );
        }
    }

    /**
     * Cancel old OTPs and create a new registration OTP.
     *
     * The plain OTP should be passed to the SMS provider after the
     * database transaction succeeds. Only its hash is stored.
     */
    private function createRegistrationOtp(
        int $mobileContactId
    ): int {
        if (!$this->verificationModel->cancelPendingForContact(
            $mobileContactId,
            ContactVerificationModel::PURPOSE_REGISTER
        )) {
            throw new RuntimeException(
                'Unable to cancel previous OTP records.'
            );
        }

        $otp = (string) random_int(1000, 9999);

        $expiresAt = date(
            'Y-m-d H:i:s',
            strtotime(
                '+' . OTP_EXPIRY_MINUTES . ' minutes'
            )
        );

        $verificationId = $this->verificationModel->insert([
            'user_contact_id' => $mobileContactId,

            'purpose' =>
            ContactVerificationModel::PURPOSE_REGISTER,

            'otp_hash' => password_hash(
                $otp,
                PASSWORD_DEFAULT
            ),

            'expires_at' => $expiresAt,

            'attempt_count' => 0,
            'resend_count' => 0,

            'status' =>
            ContactVerificationModel::STATUS_PENDING,

            'verified_at' => null,
        ], true);

        if (!is_numeric($verificationId)) {
            throw new RuntimeException(
                'Unable to create the OTP verification record.'
            );
        }

        /**
         * Do not log the OTP in production.
         *
         * Replace this with an injected OTP sender:
         *
         * $this->otpSender->send($mobile, $otp);
         */
        return (int) $verificationId;
    }

    /**
     * Infer gender for relationships where it is unambiguous.
     *
     * @param array<string, mixed> $data
     */
    private function resolveGender(array $data): string
    {
        return match ((string) $data['profile_created_for']) {
            'self' => (string) $data['gender'],
            'son', 'brother' => 'M',
            'daughter', 'sister' => 'F',

            default => throw new RuntimeException(
                'Unsupported profile relationship.'
            ),
        };
    }

    /**
     * Normalize an Indian mobile to E.164-like storage.
     */
    private function normalizeIndianMobile(
        string $mobileNumber
    ): string {
        $digits = preg_replace(
            '/\D+/',
            '',
            $mobileNumber
        ) ?? '';

        return '+91' . $digits;
    }

    /**
     * Normalize email for comparison and storage.
     */
    private function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    /**
     * Safely interpret PostgreSQL/CI4 boolean values.
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
     * Commit the transaction or fail the complete operation.
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
     * Maximum attempts when generating a random profile reference.
     */
    private const PROFILE_REFERENCE_ATTEMPTS = 20;

    /**
     * Generate a reference in the format SAK1234567.
     *
     * The database unique index remains the final protection against
     * concurrent requests.
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
}
