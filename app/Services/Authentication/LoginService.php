<?php

declare(strict_types=1);

namespace App\Services\Authentication;

use App\Models\UserContactModel;
use App\Models\UserModel;

/**
 * Authenticates a user through a verified email or mobile contact.
 */
final class LoginService
{
    private const ACTIVE_ACCOUNT = 'ACTIVE';

    public function __construct(
        private readonly UserModel $userModel,
        private readonly UserContactModel $contactModel
    ) {
    }

    public function authenticate(
        string $identifier,
        string $password
    ): LoginResult {
        $identity = $this->resolveIdentity($identifier);

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
         * Use a generic error when no account exists so the login page
         * does not expose whether an email/mobile number is registered.
         */
        if (!is_array($contact)) {
            return $this->invalidCredentials();
        }

        if (!$this->isVerified($contact)) {
            return $this->unverifiedContactResult(
                $identity['type']
            );
        }

        $userId = $contact['user_id'] ?? null;

        if (!is_numeric($userId)) {
            return $this->invalidCredentials();
        }

        $user = $this->userModel->find(
            (int) $userId
        );

        if (!is_array($user)) {
            return $this->invalidCredentials();
        }

        if (
            strtoupper(
                trim((string) ($user['account_status'] ?? ''))
            ) !== self::ACTIVE_ACCOUNT
        ) {
            return LoginResult::failure(
                'Your account is not currently active. '
                . 'Please contact support for assistance.'
            );
        }

        $passwordHash = (string) (
            $user['password_hash'] ?? ''
        );

        if (
            $passwordHash === ''
            || !password_verify($password, $passwordHash)
        ) {
            return $this->invalidCredentials();
        }

        /**
         * Automatically upgrade an old password hash when PHP's
         * configured hashing cost or algorithm changes.
         */
        if (
            password_needs_rehash(
                $passwordHash,
                PASSWORD_DEFAULT
            )
        ) {
            $newHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            if (is_string($newHash)) {
                $this->userModel->update(
                    (int) $userId,
                    [
                        'password_hash' => $newHash,
                    ]
                );
            }
        }

        return LoginResult::success($user);
    }

    /**
     * Determine whether the identifier is an email or Indian mobile.
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

        if (
            filter_var(
                $identifier,
                FILTER_VALIDATE_EMAIL
            ) !== false
        ) {
            return [
                'type' => UserContactModel::TYPE_EMAIL,

                'normalized_value' =>
                    mb_strtolower($identifier),
            ];
        }

        $digits = preg_replace(
            '/\D+/',
            '',
            $identifier
        ) ?? '';

        /**
         * Accept:
         * 9876543210
         * 919876543210
         * +91 98765 43210
         */
        if (
            strlen($digits) === 12
            && str_starts_with($digits, '91')
        ) {
            $digits = substr($digits, 2);
        }

        if (
            preg_match('/^[6-9][0-9]{9}$/', $digits) !== 1
        ) {
            return null;
        }

        /**
         * This must match the normalized format saved during
         * registration. The current registration flow combines the
         * country code and mobile number before storing the contact.
         */
        return [
            'type' => UserContactModel::TYPE_MOBILE,
            'normalized_value' => '+91' . $digits,
        ];
    }

    /**
     * PostgreSQL booleans may be returned as bool, integer or string,
     * depending on the database driver configuration.
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
}

