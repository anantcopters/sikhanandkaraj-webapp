<?php

declare(strict_types=1);

namespace App\Filters;

use App\Models\AdminUserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

final class AdminAuthFilter implements FilterInterface
{
    /**
     * Validate the administrator session and current database status.
     *
     * Reloading the administrator on each protected request ensures that
     * suspension takes effect immediately on the next request, even when
     * the administrator already has an authenticated session.
     */
    public function before(
        RequestInterface $request,
        $arguments = null
    ) {
        $adminUserId = session('admin_user_id');

        if (
            session('admin_is_authenticated') !== true
            || !is_numeric($adminUserId)
        ) {
            $this->clearAdminSession();

            return $this->redirectToLogin(
                title: 'Login required',
                message: 'Please log in to access administration.'
            );
        }

        try {
            $admin = (
                new AdminUserModel()
            )->find((int) $adminUserId);
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Unable to validate administrator session '
                    . 'for admin {adminId}: {message}',
                [
                    'adminId' => $adminUserId,
                    'message' => $exception->getMessage(),
                ]
            );

            /*
             * Fail closed. Administration access should not continue when
             * the current database account state cannot be verified.
             */
            $this->clearAdminSession();

            return $this->redirectToLogin(
                title: 'Session unavailable',
                message: 'Your administrator session could not be validated. '
                    . 'Please log in again.'
            );
        }

        if (!is_array($admin)) {
            $this->clearAdminSession();

            return $this->redirectToLogin(
                title: 'Account unavailable',
                message: 'Your administrator account is no longer available.'
            );
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
            AdminUserModel::STATUS_SUSPENDED
        ) {
            /** @var \App\Services\Admin\Audit\AdminAuditService $audit */
            $audit = service('adminAuditService');

            $audit->record(
                new \App\Services\Admin\Audit\AdminAuditEvent(
                    action: \App\Services\Admin\Audit\AdminAuditAction::ACCESS_DENIED,

                    outcome: 'DENIED',

                    actorAdminId: (int) $admin['id'],

                    actorName: (string) $admin['full_name'],

                    actorRole: (string) $admin['role'],

                    targetType: 'ADMIN_USER',

                    targetId: (int) $admin['id'],

                    targetLabel: (string) $admin['email_address'],

                    description: 'Access was denied because the administrator '
                        . 'account is suspended.',

                    metadata: [
                        'account_status' =>
                        AdminUserModel::STATUS_SUSPENDED,
                    ]
                )
            );
            $this->clearAdminSession();

            return $this->redirectToLogin(
                title: 'Account suspended',
                message: 'Your administrator account has been suspended. '
                    . 'Please contact the super administrator.'
            );
        }

        if (
            $status !==
            AdminUserModel::STATUS_VERIFIED
        ) {
            $this->clearAdminSession();

            return $this->redirectToLogin(
                title: 'Account not verified',
                message: 'Your administrator account is not currently verified.'
            );
        }

        /*
         * A verified administrator must still have a verified email and
         * a password. These checks protect against inconsistent database
         * records or incomplete manual inserts.
         */
        if (!$this->databaseBoolean(
            $admin['is_email_verified']
                ?? false
        )) {
            $this->clearAdminSession();

            return $this->redirectToLogin(
                title: 'Email not verified',
                message: 'Your administrator email address is not verified.'
            );
        }

        $passwordHash = trim(
            (string) (
                $admin['password_hash']
                ?? ''
            )
        );

        if ($passwordHash === '') {
            $this->clearAdminSession();

            return $this->redirectToLogin(
                title: 'Password not configured',
                message: 'Your administrator password has not been configured.'
            );
        }

        $role = strtoupper(
            trim(
                (string) (
                    $admin['role']
                    ?? ''
                )
            )
        );

        if (
            !in_array(
                $role,
                [
                    AdminUserModel::ROLE_SUPER_ADMIN,
                    AdminUserModel::ROLE_ADMIN,
                ],
                true
            )
        ) {
            $this->clearAdminSession();

            return $this->redirectToLogin(
                title: 'Access unavailable',
                message: 'Your administrator role is not valid.'
            );
        }

        /*
         * Refresh trusted session values from the database. Do not trust a
         * role or name stored indefinitely in an old session.
         */
        session()->set([
            'admin_user_name' => trim(
                (string) (
                    $admin['full_name']
                    ?? 'Administrator'
                )
            ),
            'admin_role' => $role,
        ]);

        return null;
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null
    ) {
        return null;
    }

    /**
     * Remove only administration values.
     *
     * Do not destroy the entire session because the browser may also contain
     * a valid matrimonial-member login.
     */
    private function clearAdminSession(): void
    {
        session()->remove([
            'admin_is_authenticated',
            'admin_user_id',
            'admin_user_name',
            'admin_role',
            'admin_authenticated_at',
        ]);

        session()->regenerate(true);
    }

    private function redirectToLogin(
        string $title,
        string $message
    ) {
        return redirect()
            ->to(route_to('admin.login'))
            ->with('formAlert', [
                'type' => 'danger',
                'title' => $title,
                'message' => $message,
            ]);
    }

    /**
     * Normalize PostgreSQL/CI4 boolean representations.
     */
    private function databaseBoolean(
        mixed $value
    ): bool {
        return $value === true
            || $value === 1
            || $value === '1'
            || $value === 't'
            || $value === 'true';
    }
}
