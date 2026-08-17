<?php

declare(strict_types=1);

namespace App\Services\Account;

use App\Models\EmailVerificationTokenModel;
use App\Models\UserContactModel;
use App\Models\UserModel;
use App\Services\EmailVerification\EmailVerificationService;
use App\Support\BooleanValue;
use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * Owns member password, email and profile-visibility settings.
 */
final class MemberAccountSettingsService
{
    private const EMAIL_CHANGE_LOCK_HOURS = 24;

    public function __construct(
        private readonly UserModel $userModel,
        private readonly UserContactModel $contactModel,
        private readonly EmailVerificationTokenModel $tokenModel,
        private readonly EmailVerificationService
        $emailVerificationService,
        private readonly BaseConnection $database
    ) {}

    /**
     * Return Account Settings presentation state.
     *
     * @return array<string, mixed>
     */
    public function settingsForUser(
        int $userId
    ): array {
        $user = $this->userModel->find(
            $userId
        );

        if (!is_array($user)) {
            throw new DomainException(
                'The member account could not be found.'
            );
        }

        $primaryEmail = $this
            ->contactModel
            ->findPrimaryForUser(
                $userId,
                UserContactModel::TYPE_EMAIL
            );

        $pendingEmail = $this
            ->contactModel
            ->findPendingEmailForUser(
                $userId
            );

        return [
            'user' =>
            $user,

            'primaryEmail' =>
            $this->emailPresentation(
                $primaryEmail
            ),

            'pendingEmail' =>
            $this->emailPresentation(
                $pendingEmail
            ),

            'profileVisibility' =>
            $this->normaliseVisibility(
                $user['profile_visibility']
                    ?? 'ALL_MEMBERS'
            ),

            'isPaid' =>
            BooleanValue::fromDatabase(
                $user['is_paid']
                    ?? false
            ),
        ];
    }

    /**
     * Change the member password after checking the current password.
     */
    public function changePassword(
        int $userId,
        string $currentPassword,
        string $newPassword
    ): void {
        $user = $this->userModel->find(
            $userId
        );

        if (!is_array($user)) {
            throw new DomainException(
                'The member account could not be found.'
            );
        }

        $passwordHash = trim(
            (string) (
                $user['password_hash']
                ?? ''
            )
        );

        if (
            $passwordHash === ''
            || !password_verify(
                $currentPassword,
                $passwordHash
            )
        ) {
            throw new DomainException(
                'The current password is incorrect.'
            );
        }

        if (
            password_verify(
                $newPassword,
                $passwordHash
            )
        ) {
            throw new DomainException(
                'The new password must be different '
                    . 'from the current password.'
            );
        }

        $newPasswordHash = password_hash(
            $newPassword,
            PASSWORD_DEFAULT
        );

        if (!is_string($newPasswordHash)) {
            throw new RuntimeException(
                'The new password could not be secured.'
            );
        }

        if (
            $this->userModel->update(
                $userId,
                [
                    'password_hash' =>
                    $newPasswordHash,
                ]
            ) === false
        ) {
            throw new RuntimeException(
                'The password could not be updated.'
            );
        }
    }

    /**
     * Add or request replacement of the member's email.
     *
     * @return array{email:string}
     */
    public function saveEmail(
        int $userId,
        string $emailAddress
    ): array {
        $normalizedEmail = mb_strtolower(
            trim(
                $emailAddress
            )
        );

        if (
            !filter_var(
                $normalizedEmail,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            throw new DomainException(
                'Please enter a valid email address.'
            );
        }

        $user = $this->userModel->find(
            $userId
        );

        if (!is_array($user)) {
            throw new DomainException(
                'The member account could not be found.'
            );
        }

        $existingOwner = $this
            ->contactModel
            ->findByNormalizedValue(
                UserContactModel::TYPE_EMAIL,
                $normalizedEmail
            );

        if (
            is_array($existingOwner)
            && (int) (
                $existingOwner['user_id']
                ?? 0
            ) !== $userId
        ) {
            throw new DomainException(
                'This email address is already associated '
                    . 'with another account.'
            );
        }

        $primaryEmail = $this
            ->contactModel
            ->findPrimaryForUser(
                $userId,
                UserContactModel::TYPE_EMAIL
            );

        $pendingEmail = $this
            ->contactModel
            ->findPendingEmailForUser(
                $userId
            );

        if (
            is_array($pendingEmail)
            && !$this->canChangeEmail(
                $pendingEmail
            )
        ) {
            throw new DomainException(
                'A verification email has already been sent. '
                    . 'You can change or resend it after 24 hours.'
            );
        }

        if (
            is_array($primaryEmail)
            && !$this->canChangeEmail(
                $primaryEmail
            )
            && !BooleanValue::fromDatabase(
                $primaryEmail['is_verified']
                    ?? false
            )
        ) {
            throw new DomainException(
                'A verification email has already been sent. '
                    . 'You can change or resend it after 24 hours.'
            );
        }

        $changeAvailableAt = date(
            'Y-m-d H:i:sP',
            strtotime(
                '+' . self::EMAIL_CHANGE_LOCK_HOURS
                    . ' hours'
            )
        );

        $contactId = 0;

        $this->database->transBegin();

        try {
            if (is_array($pendingEmail)) {
                $pendingContactId = (int) (
                    $pendingEmail['id']
                    ?? 0
                );

                if ($pendingContactId > 0) {
                    $this->tokenModel
                        ->invalidateForContact(
                            $pendingContactId
                        );

                    $this->contactModel->delete(
                        $pendingContactId
                    );
                }
            }

            if (!is_array($primaryEmail)) {
                $inserted = $this
                    ->contactModel
                    ->insert(
                        [
                            'user_id' =>
                            $userId,

                            'contact_type' =>
                            UserContactModel::TYPE_EMAIL,

                            'contact_value' =>
                            $normalizedEmail,

                            'normalized_value' =>
                            $normalizedEmail,

                            'is_primary' =>
                            true,

                            'is_verified' =>
                            false,

                            'verified_at' =>
                            null,

                            'replaces_contact_id' =>
                            null,

                            'change_available_at' =>
                            $changeAvailableAt,
                        ],
                        true
                    );

                $contactId = is_numeric($inserted)
                    ? (int) $inserted
                    : 0;
            } elseif (
                BooleanValue::fromDatabase(
                    $primaryEmail['is_verified']
                        ?? false
                )
            ) {
                if (
                    mb_strtolower(
                        trim(
                            (string) (
                                $primaryEmail['normalized_value']
                                ?? ''
                            )
                        )
                    ) === $normalizedEmail
                ) {
                    throw new DomainException(
                        'This is already your verified email address.'
                    );
                }

                $inserted = $this
                    ->contactModel
                    ->insert(
                        [
                            'user_id' =>
                            $userId,

                            'contact_type' =>
                            UserContactModel::TYPE_EMAIL,

                            'contact_value' =>
                            $normalizedEmail,

                            'normalized_value' =>
                            $normalizedEmail,

                            'is_primary' =>
                            false,

                            'is_verified' =>
                            false,

                            'verified_at' =>
                            null,

                            'replaces_contact_id' =>
                            (int) $primaryEmail['id'],

                            'change_available_at' =>
                            $changeAvailableAt,
                        ],
                        true
                    );

                $contactId = is_numeric($inserted)
                    ? (int) $inserted
                    : 0;
            } else {
                $contactId = (int) (
                    $primaryEmail['id']
                    ?? 0
                );

                $this->tokenModel
                    ->invalidateForContact(
                        $contactId
                    );

                $updated = $this
                    ->contactModel
                    ->update(
                        $contactId,
                        [
                            'contact_value' =>
                            $normalizedEmail,

                            'normalized_value' =>
                            $normalizedEmail,

                            'is_verified' =>
                            false,

                            'verified_at' =>
                            null,

                            'change_available_at' =>
                            $changeAvailableAt,
                        ]
                    );

                if ($updated === false) {
                    throw new RuntimeException(
                        'The email address could not be updated.'
                    );
                }
            }

            if (
                $contactId <= 0
                || !$this->database->transStatus()
            ) {
                throw new RuntimeException(
                    'The email address could not be saved.'
                );
            }

            $this->database->transCommit();
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }

        $result = $this
            ->emailVerificationService
            ->sendForContact(
                $userId,
                $contactId
            );

        if (!$result->success) {
            throw new RuntimeException(
                $result->message
            );
        }

        return [
            'email' =>
            $normalizedEmail,
        ];
    }

    /**
     * Resend verification for the current pending/unverified email.
     *
     * @return array{email:string}
     */
    public function resendEmailVerification(
        int $userId
    ): array {
        $pendingEmail = $this
            ->contactModel
            ->findPendingEmailForUser(
                $userId
            );

        $contact = $pendingEmail;

        if (!is_array($contact)) {
            $contact = $this
                ->contactModel
                ->findPrimaryForUser(
                    $userId,
                    UserContactModel::TYPE_EMAIL
                );
        }

        if (!is_array($contact)) {
            throw new DomainException(
                'No email address is awaiting verification.'
            );
        }

        if (
            BooleanValue::fromDatabase(
                $contact['is_verified']
                    ?? false
            )
        ) {
            throw new DomainException(
                'Your email address is already verified.'
            );
        }

        if (!$this->canChangeEmail($contact)) {
            throw new DomainException(
                'You can resend the verification email after 24 hours.'
            );
        }

        $contactId = (int) (
            $contact['id']
            ?? 0
        );

        if ($contactId <= 0) {
            throw new RuntimeException(
                'The pending email address is invalid.'
            );
        }

        /*
     * Queue first. Do not start a new 24-hour lock unless
     * the verification request was successfully created.
     */
        $result = $this
            ->emailVerificationService
            ->sendForContact(
                $userId,
                $contactId
            );

        if (!$result->success) {
            throw new DomainException(
                $result->message
            );
        }

        $changeAvailableAt = date(
            'Y-m-d H:i:sP',
            strtotime(
                '+'
                    . self::EMAIL_CHANGE_LOCK_HOURS
                    . ' hours'
            )
        );

        $updated = $this->contactModel->update(
            $contactId,
            [
                'change_available_at' =>
                $changeAvailableAt,
            ]
        );

        if ($updated === false) {
            throw new RuntimeException(
                'The email verification restriction could not be updated.'
            );
        }

        return [
            'email' => trim(
                (string) (
                    $contact['contact_value']
                    ?? ''
                )
            ),
        ];
    }

    /**
     * Save the member's profile visibility.
     */
    public function saveVisibility(
        int $userId,
        string $visibility
    ): void {
        $visibility = $this
            ->normaliseVisibility(
                $visibility
            );

        if (
            $this->userModel->update(
                $userId,
                [
                    'profile_visibility' =>
                    $visibility,
                ]
            ) === false
        ) {
            throw new RuntimeException(
                'Profile visibility could not be updated.'
            );
        }
    }

    /**
     * @param array<string, mixed>|null $contact
     *
     * @return array<string, mixed>|null
     */
    private function emailPresentation(
        ?array $contact
    ): ?array {
        if (!is_array($contact)) {
            return null;
        }

        $availableAt = trim(
            (string) (
                $contact['change_available_at']
                ?? ''
            )
        );

        $availableTimestamp =
            $availableAt !== ''
            ? strtotime($availableAt)
            : false;

        return [
            'id' =>
            (int) (
                $contact['id']
                ?? 0
            ),

            'email' =>
            trim(
                (string) (
                    $contact['contact_value']
                    ?? ''
                )
            ),

            'isVerified' =>
            BooleanValue::fromDatabase(
                $contact['is_verified']
                    ?? false
            ),

            'isPrimary' =>
            BooleanValue::fromDatabase(
                $contact['is_primary']
                    ?? false
            ),

            'canChange' =>
            $this->canChangeEmail(
                $contact
            ),

            'changeAvailableAt' =>
            $availableAt,

            'remainingSeconds' =>
            $availableTimestamp !== false
                && $availableTimestamp > time()
                ? $availableTimestamp - time()
                : 0,
        ];
    }

    /**
     * @param array<string, mixed> $contact
     */
    private function canChangeEmail(
        array $contact
    ): bool {
        $availableAt = strtotime(
            (string) (
                $contact['change_available_at']
                ?? ''
            )
        );

        return $availableAt === false
            || $availableAt <= time();
    }

    private function normaliseVisibility(
        mixed $visibility
    ): string {
        $visibility = mb_strtoupper(
            trim(
                (string) $visibility
            )
        );

        if (
            !in_array(
                $visibility,
                [
                    'ALL_MEMBERS',
                    'PAID_MEMBERS_ONLY',
                ],
                true
            )
        ) {
            return 'ALL_MEMBERS';
        }

        return $visibility;
    }
}
