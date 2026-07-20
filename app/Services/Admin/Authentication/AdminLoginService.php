<?php

declare(strict_types=1);

namespace App\Services\Admin\Authentication;

use App\Models\AdminUserModel;

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
            $this->normalizeIdentifier($identifier);

        if ($normalizedIdentifier === null) {
            return AdminLoginResult::failure(
                'Enter a valid email address or mobile number.'
            );
        }

        $admin = $this->adminUserModel
            ->findByIdentifier($normalizedIdentifier);

        /*
         * Do not expose whether the administrator exists.
         */
        if (!is_array($admin)) {
            return $this->invalidCredentials();
        }

        $passwordHash = trim(
            (string) ($admin['password_hash'] ?? '')
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
            ($admin['is_email_verified'] ?? false)
            !== true
            && ($admin['is_email_verified'] ?? false) !== 't'
            && ($admin['is_email_verified'] ?? false) !== 1
        ) {
            return AdminLoginResult::failure(
                'Your administrator email address is not verified.'
            );
        }

        $adminId = (int) $admin['id'];

        $this->adminUserModel->update(
            $adminId,
            [
                'last_login_at' =>
                date('Y-m-d H:i:s'),
            ]
        );

        return AdminLoginResult::success($admin);
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
            return mb_strtolower($identifier);
        }

        $digits = preg_replace(
            '/\D+/',
            '',
            $identifier
        ) ?? '';

        if (
            strlen($digits) === 12
            && str_starts_with($digits, '91')
        ) {
            $digits = substr($digits, 2);
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

    private function invalidCredentials(): AdminLoginResult
    {
        return AdminLoginResult::failure(
            'The email/mobile number or password is incorrect.'
        );
    }
}
