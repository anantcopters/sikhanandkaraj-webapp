<?php

declare(strict_types=1);

namespace App\Services\Authentication;

use App\Models\UserContactModel;
use App\Models\UserModel;
use App\Support\IndianMobileNormalizer;
use App\Support\BooleanValue;

/**
 * Authenticates a user through a verified email or mobile contact.
 */
final class LoginService
{
    private const ACTIVE_ACCOUNT = 'ACTIVE';

    public function __construct(
        private readonly UserModel $userModel,
        private readonly UserContactModel $contactModel
    ) {}

    /**
     * Authenticate a user using an email address or mobile number.
     *
     * Important security order:
     *
     * 1. Resolve and find the contact.
     * 2. Find the associated user.
     * 3. Verify the password.
     * 4. Only then reveal account or contact verification status.
     */
    public function authenticate(
        string $identifier,
        string $password
    ): LoginResult {
        $identity = $this->resolveIdentity(
            $identifier
        );

        if ($identity === null) {
            return LoginResult::failure(
                'Please enter a valid email address or mobile number.',
                'identifier'
            );
        }

        $contact = $this->contactModel
            ->findByNormalizedValue(
                $identity['type'],
                $identity['normalized_value']
            );

        /**
         * Do not reveal whether the supplied identifier exists.
         */
        if (!is_array($contact)) {
            return $this->invalidCredentials();
        }

        $userId = $contact['user_id'] ?? null;

        if (!is_numeric($userId)) {
            return $this->invalidCredentials();
        }

        $userId = (int) $userId;

        $user = $this->userModel->find(
            $userId
        );

        if (!is_array($user)) {
            return $this->invalidCredentials();
        }

        $passwordHash = trim(
            (string) ($user['password_hash'] ?? '')
        );

        /**
         * Verify the password before returning any account-status or
         * contact-verification information.
         */
        if (
            $passwordHash === ''
            || !password_verify(
                $password,
                $passwordHash
            )
        ) {
            return $this->invalidCredentials();
        }

        /**
         * Password is correct, so account-specific messages may now be
         * returned without exposing information to an unauthenticated
         * third party.
         */
        $accountStatus = strtoupper(
            trim(
                (string) (
                    $user['account_status'] ?? ''
                )
            )
        );

        if ($accountStatus !== self::ACTIVE_ACCOUNT) {
            return $this->inactiveAccountResult(
                $accountStatus
            );
        }

        /**
         * The contact used for login must itself be verified.
         *
         * A verified mobile does not make an unverified email valid for
         * email login. The user may instead log in using that verified
         * mobile contact.
         */
        if (!$this->isVerified($contact)) {
            return $this->unverifiedContactResult(
                $identity['type']
            );
        }

        /*
        * Upgrade an older password hash after successful authentication.
        *
        * Rehash persistence failure intentionally does not invalidate otherwise
        * correct credentials.
        */
        $this->rehashPasswordIfRequired(
            $userId,
            $password,
            $passwordHash
        );

        /*
        * Record successful authentication activity.
        *
        * last_login_at is auxiliary operational metadata. Failure to update this
        * timestamp must never invalidate otherwise successful authentication.
        */
        try {
            $loginRecorded =
                $this->userModel
                ->recordSuccessfulLogin(
                    $userId
                );

            if (!$loginRecorded) {
                log_message(
                    'warning',
                    'Successful password login activity could not be recorded. '
                        . 'Member: {memberId}.',
                    [
                        'memberId' =>
                        $userId,
                    ]
                );
            }
        } catch (\Throwable $exception) {
            log_message(
                'warning',
                'Successful password login activity update failed. '
                    . 'Member: {memberId}; reason: {message}',
                [
                    'memberId' =>
                    $userId,

                    'message' =>
                    $exception->getMessage(),
                ]
            );
        }

        return LoginResult::success(
            $user
        );

        return LoginResult::success(
            $user
        );
    }

    /**
     * Determine whether the identifier is an email or Indian mobile number.
     *
     * @return array{
     *     type: string,
     *     normalized_value: string
     * }|null
     */
    private function resolveIdentity(
        string $identifier
    ): ?array {
        $identifier = trim($identifier);

        if ($identifier === '') {
            return null;
        }

        if (
            filter_var(
                $identifier,
                FILTER_VALIDATE_EMAIL
            ) !== false
        ) {
            return [
                'type' =>
                UserContactModel::TYPE_EMAIL,

                'normalized_value' =>
                mb_strtolower($identifier),
            ];
        }

        $normalizedMobile =
            IndianMobileNormalizer::normalize(
                $identifier
            );

        if ($normalizedMobile === null) {
            return null;
        }

        return [
            'type' =>
            UserContactModel::TYPE_MOBILE,

            'normalized_value' =>
            $normalizedMobile,
        ];
    }

    /**
     * @param array<string, mixed> $contact
     */
    private function isVerified(
        array $contact
    ): bool {
        return BooleanValue::fromDatabase(
            $contact['is_verified'] ?? false
        );
    }

    private function inactiveAccountResult(
        string $accountStatus
    ): LoginResult {
        return match ($accountStatus) {
            'PENDING' => LoginResult::failure(
                'Your registration is not complete. '
                    . 'Please verify your mobile number before logging in.'
            ),

            'SUSPENDED' => LoginResult::failure(
                'Your account has been suspended. '
                    . 'Please contact support for assistance.'
            ),

            'DELETED' => LoginResult::failure(
                'This account is no longer available. '
                    . 'Please contact support for assistance.'
            ),

            default => LoginResult::failure(
                'Your account is not currently active. '
                    . 'Please contact support for assistance.'
            ),
        };
    }

    private function unverifiedContactResult(
        string $contactType
    ): LoginResult {
        if (
            $contactType ===
            UserContactModel::TYPE_EMAIL
        ) {
            return LoginResult::failure(
                'Your email address is not verified. '
                    . 'You can log in using your verified mobile number.',
                'identifier'
            );
        }

        return LoginResult::failure(
            'Your mobile number is not verified. '
                . 'Please verify it before logging in.',
            'identifier'
        );
    }

    private function invalidCredentials(): LoginResult
    {
        return LoginResult::failure(
            'The email/mobile number or password is incorrect.'
        );
    }

    private function rehashPasswordIfRequired(
        int $userId,
        string $password,
        string $currentHash
    ): void {
        if (
            !password_needs_rehash(
                $currentHash,
                PASSWORD_DEFAULT
            )
        ) {
            return;
        }

        $newHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        if (!is_string($newHash)) {
            log_message(
                'warning',
                'Unable to generate an updated password hash '
                    . 'for user {userId}.',
                [
                    'userId' => $userId,
                ]
            );

            return;
        }

        $updated = $this->userModel->update(
            $userId,
            [
                'password_hash' => $newHash,
            ]
        );

        if ($updated === false) {
            log_message(
                'warning',
                'Unable to persist the updated password hash '
                    . 'for user {userId}.',
                [
                    'userId' => $userId,
                ]
            );
        }
    }
}
