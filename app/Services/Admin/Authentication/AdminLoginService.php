<?php

declare(strict_types=1);

namespace App\Services\Admin\Authentication;

use App\Models\AdminUserModel;
use Throwable;

final class AdminLoginService
{
    public function __construct(
        private readonly AdminUserModel $adminUserModel
    ) {}

    public function authenticate(
        string $identifier,
        string $password
    ): AdminLoginResult {
        $normalizedIdentifier =
            $this->normalizeIdentifier(
                $identifier
            );

        if ($normalizedIdentifier === null) {
            return AdminLoginResult::failure(
                'Enter a valid email address or mobile number.'
            );
        }

        $admin = $this->adminUserModel
            ->findByIdentifier(
                $normalizedIdentifier
            );

        /*
         * Do not disclose whether the supplied identifier exists.
         */
        if (!is_array($admin)) {
            return $this->invalidCredentials();
        }

        $passwordHash = trim(
            (string) (
                $admin['password_hash']
                ?? ''
            )
        );

        if (
            $passwordHash === ''
            || !password_verify(
                $password,
                $passwordHash
            )
        ) {
            return $this->invalidCredentials();
        }

        /*
         * Check account state only after validating the password.
         * This avoids revealing administrator-account information to an
         * unauthenticated person.
         */
        $status = strtoupper(
            trim(
                (string) (
                    $admin['account_status']
                    ?? ''
                )
            )
        );

        if (
            $status ===
            AdminUserModel::STATUS_PENDING
        ) {
            return AdminLoginResult::failure(
                'Your administrator account is not verified. '
                    . 'Please use the invitation email to set your password.'
            );
        }

        if (
            $status ===
            AdminUserModel::STATUS_SUSPENDED
        ) {
            return AdminLoginResult::failure(
                'Your administrator account has been suspended. '
                    . 'Please contact the super administrator.'
            );
        }

        if (
            $status !==
            AdminUserModel::STATUS_VERIFIED
        ) {
            return AdminLoginResult::failure(
                'Your administrator account is unavailable.'
            );
        }

        if (
            !$this->databaseBoolean(
                $admin['is_email_verified']
                    ?? false
            )
        ) {
            return AdminLoginResult::failure(
                'Your administrator email address is not verified.'
            );
        }

        $adminId = (int) (
            $admin['id']
            ?? 0
        );

        if ($adminId <= 0) {
            return $this->invalidCredentials();
        }

        /*
         * Upgrade the stored password hash when PHP's recommended algorithm
         * or cost changes.
         *
         * Failure to rehash should not block an otherwise valid login.
         */
        $this->rehashPasswordIfRequired(
            adminId: $adminId,
            plainPassword: $password,
            currentHash: $passwordHash
        );

        /*
         * last_login_at is deliberately not updated here.
         *
         * The controller should update it only after the authenticated
         * session has been successfully created.
         */
        return AdminLoginResult::success(
            $admin
        );
    }

    private function normalizeIdentifier(
        string $identifier
    ): ?string {
        $identifier = trim($identifier);

        if (
            filter_var(
                $identifier,
                FILTER_VALIDATE_EMAIL
            ) !== false
        ) {
            return mb_strtolower(
                $identifier
            );
        }

        $digits = preg_replace(
            '/\D+/',
            '',
            $identifier
        ) ?? '';

        if (
            strlen($digits) === 12
            && str_starts_with(
                $digits,
                '91'
            )
        ) {
            $digits = substr(
                $digits,
                2
            );
        }

        if (
            preg_match(
                '/^[6-9][0-9]{9}$/',
                $digits
            ) !== 1
        ) {
            return null;
        }

        return '+91' . $digits;
    }

    private function rehashPasswordIfRequired(
        int $adminId,
        string $plainPassword,
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

        try {
            $newHash = password_hash(
                $plainPassword,
                PASSWORD_DEFAULT
            );

            if (!is_string($newHash)) {
                log_message(
                    'warning',
                    'Unable to generate a replacement password hash '
                        . 'for administrator {adminId}.',
                    [
                        'adminId' => $adminId,
                    ]
                );

                return;
            }

            $updated =
                $this->adminUserModel->update(
                    $adminId,
                    [
                        'password_hash' =>
                        $newHash,
                    ]
                );

            if ($updated === false) {
                log_message(
                    'warning',
                    'Unable to persist a replacement password hash '
                        . 'for administrator {adminId}.',
                    [
                        'adminId' => $adminId,
                    ]
                );
            }
        } catch (Throwable $exception) {
            /*
             * Rehash failure must never reject credentials that were already
             * successfully authenticated.
             */
            log_message(
                'warning',
                'Administrator password rehash failed for '
                    . '{adminId}: {message}',
                [
                    'adminId' => $adminId,
                    'message' =>
                    $exception->getMessage(),
                ]
            );
        }
    }

    private function databaseBoolean(
        mixed $value
    ): bool {
        return $value === true
            || $value === 1
            || $value === '1'
            || $value === 't'
            || $value === 'true';
    }

    private function invalidCredentials(): AdminLoginResult
    {
        return AdminLoginResult::failure(
            'The email/mobile number or password is incorrect.'
        );
    }
}
