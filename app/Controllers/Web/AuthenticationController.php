<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use App\Services\Authentication\LoginResult;
use App\Services\Authentication\LoginService;
use App\Validation\LoginValidation;
use Throwable;


/**
 * Handles login and authenticated web-session actions.
 */
final class AuthenticationController extends BaseController
{
    public function index(): string|RedirectResponse
    {
        if (session('is_authenticated') === true) {
            return redirect()->to(
                route_to('web.dashboard')
            );
        }

        return view(
            'Pages/Authentication/Login',
            [
                'pageTitle' => 'Login',
                'pageScripts' => [
                    'assets/js/components/password-toggle.js',
                ],
            ]
        );
    }

    public function login(): RedirectResponse
    {
        /**
         * Prevent an already-authenticated session from being replaced
         * unexpectedly through another login form submission.
         */
        if (session('is_authenticated') === true) {
            return redirect()->to(
                route_to('web.dashboard')
            );
        }

        $input = $this->getLoginInput();

        $validation = service('validation');

        $validation->setRules(
            LoginValidation::rules()
        );

        if (!$validation->run($input)) {
            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'validationErrors',
                    $validation->getErrors()
                );
        }

        $validated = $validation->getValidated();

        try {
            /** @var LoginService $service */
            $service = service('loginService');

            $result = $service->authenticate(
                (string) $validated['identifier'],
                (string) $validated['password']
            );

            if (!$result->successful) {
                return $this->handleLoginFailure(
                    $result
                );
            }

            $user = $result->user;

            if (!is_array($user)) {
                throw new \RuntimeException(
                    'Successful login returned no user.'
                );
            }

            /**
             * Regenerate the session identifier after authentication
             * to prevent session fixation.
             */
            session()->regenerate(true);

            session()->set([
                'is_authenticated' => true,

                'auth_user_id' =>
                (int) $user['id'],

                'auth_user_name' =>
                trim(
                    (string) ($user['full_name'] ?? '')
                ),

                'auth_profile_reference' =>
                trim(
                    (string) (
                        $user['profile_ref_number'] ?? ''
                    )
                ),

                'authenticated_at' => time(),
            ]);

            return redirect()
                ->to(route_to('web.dashboard'))
                ->with('formAlert', [
                    'type' => 'success',
                    'title' => 'Welcome back',
                    'message' =>
                    'You have logged in successfully.',
                ]);
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Login failed: {message}',
                [
                    'message' => $exception->getMessage(),
                ]
            );

            return redirect()
                ->back()
                ->withInput()
                ->with('formAlert', [
                    'type' => 'danger',
                    'title' => 'Login unavailable',
                    'message' =>
                    'We could not log you in right now. '
                        . 'Please try again.',
                ]);
        }
    }

    public function logout(): RedirectResponse
    {
        session()->destroy();

        return redirect()
            ->to(route_to('web.home'))
            ->with('formAlert', [
                'type' => 'success',
                'title' => 'Logged out',
                'message' =>
                'You have been logged out successfully.',
            ]);
    }

    /**
     * @return array<string, string>
     */
    private function getLoginInput(): array
    {
        return [
            'identifier' => trim(
                (string) $this->request->getPost(
                    'identifier'
                )
            ),

            /**
             * Password must not be trimmed because spaces may be part
             * of a legitimate password.
             */
            'password' => (string) $this->request->getPost(
                'password'
            ),
        ];
    }

    private function handleLoginFailure(
        LoginResult $result
    ): RedirectResponse {
        if (
            $result->field !== null
            && $result->message !== null
        ) {
            return redirect()
                ->back()
                ->withInput()
                ->with('validationErrors', [
                    $result->field => $result->message,
                ]);
        }

        return redirect()
            ->back()
            ->withInput()
            ->with('formAlert', [
                'type' => 'danger',
                'title' => 'Login failed',
                'message' => $result->message
                    ?? 'The login could not be completed.',
            ]);
    }
}
