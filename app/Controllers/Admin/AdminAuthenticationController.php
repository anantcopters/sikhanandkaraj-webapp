<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\Admin\Authentication\AdminCaptchaService;
use App\Services\Admin\Authentication\AdminLoginService;
use App\Services\Admin\Audit\AdminAuditAction;
use App\Services\Admin\Audit\AdminAuditEvent;
use App\Support\AdminErrorContext;
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

        /** @var AdminCaptchaService $captchaService */
        $captchaService = service(
            'adminCaptchaService'
        );

        /*
         * Every rendering of the login page receives
         * a fresh CAPTCHA.
         *
         * This also replaces a CAPTCHA after a failed
         * login attempt.
         */
        $captchaChallenge =
            $captchaService->generate();

        return view(
            'Admin/Authentication/Login',
            [
                'pageTitle' =>
                'Administrator Login',

                'validationErrors' =>
                $this->readValidationErrors(),

                'formAlert' =>
                $this->readFormAlert(),

                'adminLoginIdentifier' =>
                $this->readFlashString(
                    'adminLoginIdentifier'
                ),

                'captchaChallenge' =>
                $captchaChallenge,

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

        $captchaAnswer = trim(
            (string) $this->request
                ->getPost('captcha_answer')
        );

        $validation = service(
            'validation'
        );

        $validation->setRules(
            AdminLoginValidation::rules()
        );

        if (
            !$validation->run([
                'identifier' =>
                $identifier,

                'password' =>
                $password,

                'captcha_answer' =>
                $captchaAnswer,
            ])
        ) {
            /*
             * Consume the CAPTCHA even when normal request
             * validation fails.
             *
             * The redirected login page generates a fresh one.
             */
            /** @var AdminCaptchaService $captchaService */
            $captchaService = service(
                'adminCaptchaService'
            );

            $captchaService->clear();

            $this->recordAuditSafely(
                new AdminAuditEvent(
                    action: AdminAuditAction::LOGIN_FAILURE,

                    outcome: 'FAILURE',

                    description: 'Administrator login validation failed.',

                    metadata: [
                        'identifier' =>
                        $this->maskAdminIdentifier(
                            $identifier
                        ),

                        'failure_type' =>
                        'VALIDATION_FAILURE',
                    ]
                )
            );

            return redirect()
                ->to(
                    route_to('admin.login')
                )
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
            /*
             * CAPTCHA verification happens before any
             * administrator credential lookup.
             */
            /** @var AdminCaptchaService $captchaService */
            $captchaService = service(
                'adminCaptchaService'
            );

            if (
                !$captchaService->verify(
                    $captchaAnswer
                )
            ) {
                $this->recordAuditSafely(
                    new AdminAuditEvent(
                        action: AdminAuditAction::LOGIN_FAILURE,

                        outcome: 'FAILURE',

                        description: 'Administrator login CAPTCHA verification failed.',

                        metadata: [
                            'identifier' =>
                            $this->maskAdminIdentifier(
                                $identifier
                            ),

                            'failure_type' =>
                            'CAPTCHA_FAILURE',
                        ]
                    )
                );

                return redirect()
                    ->to(
                        route_to('admin.login')
                    )
                    ->with(
                        'adminLoginIdentifier',
                        $identifier
                    )
                    ->with(
                        'validationErrors',
                        [
                            'captcha_answer' =>
                            'The security verification answer '
                                . 'is incorrect or has expired. '
                                . 'Please try the new question.',
                        ]
                    );
            }

            /*
             * Only CAPTCHA-verified requests reach
             * administrator credential authentication.
             */
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
                $this->recordAuditSafely(
                    new AdminAuditEvent(
                        action: AdminAuditAction::LOGIN_FAILURE,

                        outcome: 'FAILURE',

                        description: 'Administrator login failed.',

                        metadata: [
                            /*
                             * Store a masked value rather than
                             * the complete identifier.
                             */
                            'identifier' =>
                            $this->maskAdminIdentifier(
                                $identifier
                            ),

                            'failure_type' =>
                            'INVALID_CREDENTIALS',
                        ]
                    )
                );

                return redirect()
                    ->to(
                        route_to('admin.login')
                    )
                    ->with(
                        'adminLoginIdentifier',
                        $identifier
                    )
                    ->with(
                        'formAlert',
                        [
                            'type' =>
                            'danger',

                            'title' =>
                            'Login failed',

                            'message' =>
                            $result->message
                                ?? 'Login could not be completed.',
                        ]
                    );
            }

            $admin = $result->admin;

            /*
             * Prevent session fixation after successful
             * administrator authentication.
             */
            session()->regenerate(true);

            session()->set([
                'admin_is_authenticated' =>
                true,

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
             * Update last_login_at only after the
             * authenticated session has been successfully
             * established.
             */
            $adminModel =
                new \App\Models\AdminUserModel();

            $lastLoginUpdated =
                $adminModel->update(
                    (int) $admin['id'],
                    [
                        'last_login_at' =>
                        date(
                            'Y-m-d H:i:s'
                        ),
                    ]
                );

            if (
                $lastLoginUpdated === false
            ) {
                /*
                 * A last-login tracking failure must not
                 * block a valid login.
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
             * Record ADMIN_LOGIN_SUCCESS after authenticated
             * session creation and last-login processing.
             */
            $this->recordAuditSafely(
                new AdminAuditEvent(
                    action: AdminAuditAction::LOGIN_SUCCESS,

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
                ->to(
                    route_to(
                        'admin.dashboard'
                    )
                )
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'success',

                        'title' =>
                        'Welcome',

                        'message' =>
                        'Administrator login successful.',
                    ]
                );
        } catch (Throwable $exception) {
            /*
             * Never retain a CAPTCHA after an unexpected
             * login failure.
             */
            try {
                /** @var AdminCaptchaService $captchaService */
                $captchaService = service(
                    'adminCaptchaService'
                );

                $captchaService->clear();
            } catch (Throwable) {
                /*
                 * CAPTCHA cleanup must not hide the
                 * original application exception.
                 */
            }

            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'error',
                AdminErrorContext::forOperation(
                    operation: 'admin_login',

                    component: self::class,

                    method: __FUNCTION__,

                    additionalContext: [
                        /*
                         * Never store the submitted email,
                         * password, CAPTCHA answer or complete
                         * login identifier.
                         */
                        'identifier_type' =>
                        filter_var(
                            $identifier,
                            FILTER_VALIDATE_EMAIL
                        ) !== false
                            ? 'EMAIL'
                            : 'OTHER',
                    ]
                )
            );

            return redirect()
                ->to(
                    route_to('admin.login')
                )
                ->with(
                    'adminLoginIdentifier',
                    $identifier
                )
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Login unavailable',

                        'message' =>
                        'Administrator login could not be completed. '
                            . 'Please try again.',
                    ]
                );
        }
    }

    public function logout(): RedirectResponse
    {
        $this->recordAuditSafely(
            new AdminAuditEvent(
                action: AdminAuditAction::LOGOUT,

                description: 'Administrator logged out.'
            )
        );

        session()->destroy();

        return redirect()
            ->to(
                route_to('admin.login')
            )
            ->with(
                'formAlert',
                [
                    'type' =>
                    'success',

                    'title' =>
                    'Logged out',

                    'message' =>
                    'You have logged out successfully.',
                ]
            );
    }

    private function maskAdminIdentifier(
        string $identifier
    ): string {
        $identifier =
            trim($identifier);

        if (
            filter_var(
                $identifier,
                FILTER_VALIDATE_EMAIL
            ) !== false
        ) {
            [$local, $domain] =
                array_pad(
                    explode(
                        '@',
                        $identifier,
                        2
                    ),
                    2,
                    ''
                );

            $maskedLocal =
                mb_substr(
                    $local,
                    0,
                    2
                )
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

        if (
            mb_strlen($digits) >= 4
        ) {
            return str_repeat(
                '*',
                mb_strlen($digits) - 4
            )
                . mb_substr(
                    $digits,
                    -4
                );
        }

        return '[INVALID IDENTIFIER]';
    }

    private function recordAuditSafely(
        AdminAuditEvent $event
    ): void {
        try {
            /** @var \App\Services\Admin\Audit\AdminAuditService $audit */
            $audit = service(
                'adminAuditService'
            );

            $audit->record(
                $event
            );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Administrator audit could not be recorded: {message}',
                [
                    'message' =>
                    $exception->getMessage(),
                ]
            );
        }
    }
}
