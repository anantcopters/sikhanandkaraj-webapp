<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\Admin\Authentication\AdminLoginService;
use App\Validation\AdminLoginValidation;
use CodeIgniter\HTTP\RedirectResponse;
use Throwable;

final class AdminAuthenticationController extends BaseController
{
    public function index(): string|RedirectResponse
    {
        if (
            session('admin_is_authenticated') === true
        ) {
            return redirect()->to(
                route_to('admin.dashboard')
            );
        }

        return view(
            'Admin/Authentication/Login',
            [
                'pageTitle' => 'Administrator Login',
                'pageScripts' => [
                    'assets/js/components/password-toggle.js',
                    'assets/js/components/submit-loader.js',
                ],
            ]
        );
    }

    public function login(): RedirectResponse
    {
        $identifier = trim(
            (string) $this->request
                ->getPost('identifier')
        );

        $password = (string) $this->request
            ->getPost('password');

        $validation = service('validation');

        $validation->setRules(
            AdminLoginValidation::rules()
        );

        if (
            !$validation->run([
                'identifier' => $identifier,
                'password' => $password,
            ])
        ) {

            $audit = service('adminAuditService');

            $audit->record(
                new \App\Services\Admin\Audit\AdminAuditEvent(
                    action: \App\Services\Admin\Audit\AdminAuditAction::LOGIN_FAILURE,
                    outcome: 'FAILURE',
                    description: 'Administrator login failed because of an internal error.',
                    metadata: [
                        'identifier' =>
                        $this->maskAdminIdentifier($identifier),
                        'failure_type' => 'INTERNAL_ERROR',
                    ]
                )
            );
            
            return redirect()
                ->to(route_to('admin.login'))
                ->with(
                    'adminLoginIdentifier',
                    $identifier
                )
                ->with(
                    'validationErrors',
                    $validation->getErrors()
                );
        }

        try {
            /** @var AdminLoginService $service */
            $service = service(
                'adminLoginService'
            );

            $result = $service->authenticate(
                $identifier,
                $password
            );

            if (
                !$result->successful
                || !is_array($result->admin)
            ) {

                /** @var \App\Services\Admin\Audit\AdminAuditService $audit */
                $audit = service('adminAuditService');

                $audit->record(
                    new \App\Services\Admin\Audit\AdminAuditEvent(
                        action: \App\Services\Admin\Audit\AdminAuditAction::LOGIN_FAILURE,

                        outcome: 'FAILURE',

                        description: 'Administrator login failed.',

                        metadata: [
                            /*
             * Store a masked value rather than the complete identifier.
             */
                            'identifier' =>
                            $this->maskAdminIdentifier(
                                $identifier
                            ),
                        ]
                    )
                );
                return redirect()
                    ->to(route_to('admin.login'))
                    ->with(
                        'adminLoginIdentifier',
                        $identifier
                    )
                    ->with('formAlert', [
                        'type' => 'danger',
                        'title' => 'Login failed',
                        'message' =>
                        $result->message
                            ?? 'Login could not be completed.',
                    ]);
            }

            $admin = $result->admin;

            session()->regenerate(true);

            session()->set([
                'admin_is_authenticated' => true,
                'admin_user_id' =>
                (int) $admin['id'],
                'admin_user_name' =>
                (string) $admin['full_name'],
                'admin_role' =>
                (string) $admin['role'],
                'admin_authenticated_at' =>
                time(),
            ]);

            /*
            * Update last_login_at only after the authenticated session has been
            * successfully established.
            */
            $adminModel = new \App\Models\AdminUserModel();

            $lastLoginUpdated = $adminModel->update(
                (int) $admin['id'],
                [
                    'last_login_at' =>
                    date('Y-m-d H:i:s'),
                ]
            );

            if ($lastLoginUpdated === false) {
                /*
                * A last-login tracking failure must not block a valid login.
                */
                log_message(
                    'warning',
                    'Unable to update last_login_at for '
                        . 'administrator {adminId}.',
                    [
                        'adminId' =>
                        (int) $admin['id'],
                    ]
                );
            }

            /*
            * Record ADMIN_LOGIN_SUCCESS audit here, after session creation and
            * last_login_at update.
            */
            /** @var \App\Services\Admin\Audit\AdminAuditService $audit */
            $audit = service('adminAuditService');

            $audit->record(
                new \App\Services\Admin\Audit\AdminAuditEvent(
                    action: \App\Services\Admin\Audit\AdminAuditAction::LOGIN_SUCCESS,

                    actorAdminId: (int) $admin['id'],

                    actorName: (string) $admin['full_name'],

                    actorRole: (string) $admin['role'],

                    targetType: 'ADMIN_USER',

                    targetId: (int) $admin['id'],

                    targetLabel: (string) $admin['email_address'],

                    description: 'Administrator login succeeded.'
                )
            );

            return redirect()
                ->to(route_to('admin.dashboard'))
                ->with('formAlert', [
                    'type' => 'success',
                    'title' => 'Welcome',
                    'message' =>
                    'Administrator login successful.',
                ]);
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Administrator login failed: {message}',
                [
                    'message' =>
                    $exception->getMessage(),
                ]
            );

            return redirect()
                ->to(route_to('admin.login'))
                ->with(
                    'adminLoginIdentifier',
                    $identifier
                )
                ->with('formAlert', [
                    'type' => 'danger',
                    'title' => 'Login unavailable',
                    'message' =>
                    'Administrator login is temporarily unavailable.',
                ]);
        }
    }

    public function logout(): RedirectResponse
    {
        /** @var \App\Services\Admin\Audit\AdminAuditService $audit */
        $audit = service('adminAuditService');

        $audit->record(
            new \App\Services\Admin\Audit\AdminAuditEvent(
                action: \App\Services\Admin\Audit\AdminAuditAction::LOGOUT,

                description: 'Administrator logged out.'
            )
        );

        session()->destroy();

        return redirect()
            ->to(route_to('admin.login'))
            ->with('formAlert', [
                'type' => 'success',
                'title' => 'Logged out',
                'message' =>
                'You have logged out successfully.',
            ]);
    }

    private function maskAdminIdentifier(
        string $identifier
    ): string {
        $identifier = trim($identifier);

        if (
            filter_var(
                $identifier,
                FILTER_VALIDATE_EMAIL
            ) !== false
        ) {
            [$local, $domain] = array_pad(
                explode('@', $identifier, 2),
                2,
                ''
            );

            $maskedLocal =
                mb_substr($local, 0, 2)
                . str_repeat(
                    '*',
                    max(
                        2,
                        mb_strlen($local) - 2
                    )
                );

            return $maskedLocal
                . '@'
                . $domain;
        }

        $digits = preg_replace(
            '/\D+/',
            '',
            $identifier
        ) ?? '';

        if (mb_strlen($digits) >= 4) {
            return str_repeat(
                '*',
                mb_strlen($digits) - 4
            ) . mb_substr($digits, -4);
        }

        return '[INVALID IDENTIFIER]';
    }
}
