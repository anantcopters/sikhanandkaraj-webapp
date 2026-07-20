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
        session()->destroy();

        return redirect()
            ->to(route_to('admin.login'));
    }
}
