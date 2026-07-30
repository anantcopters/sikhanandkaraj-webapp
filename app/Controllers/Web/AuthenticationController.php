<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Services\Authentication\LoginResult;
use App\Services\Authentication\LoginService;
use App\Validation\LoginValidation;
use CodeIgniter\HTTP\RedirectResponse;
use RuntimeException;
use Throwable;

/**
 * Handles login and authenticated web-session actions.
 */
final class AuthenticationController extends BaseController
{
    /**
     * Display the login-method selection page.
     */
    public function index(): string|RedirectResponse
    {
        if ($this->isAuthenticated()) {
            return redirect()->to(
                route_to('web.dashboard')
            );
        }

        $this->preventPageCaching();

        return view(
            'Pages/Authentication/LoginOptions',
            [
                'pageTitle' =>
                'Login',

                'formAlert' =>
                $this->readFormAlert(),
            ]
        );
    }

    /**
     * Display the existing password login screen.
     */
    public function password(): string|RedirectResponse
    {
        if ($this->isAuthenticated()) {
            return redirect()->to(
                route_to('web.dashboard')
            );
        }

        $this->preventPageCaching();

        return view(
            'Pages/Authentication/Login',
            [
                'pageTitle' =>
                'Login with Password',

                'validationErrors' =>
                $this->readValidationErrors(),

                'formAlert' =>
                $this->readFormAlert(),

                'loginIdentifier' =>
                $this->readFlashString(
                    'loginIdentifier'
                ),

                'pageScripts' => [
                    'assets/js/components/password-toggle.js',
                    'assets/js/components/submit-loader.js',
                ],
            ]
        );
    }

    /**
     * Validate and process a password login.
     */
    public function login(): RedirectResponse
    {
        /**
         * Do not replace an already authenticated session through another
         * login form submission.
         */
        if ($this->isAuthenticated()) {
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
            return $this->redirectToLogin(
                identifier: $input['identifier'],
                validationErrors: $validation->getErrors()
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
                    $result,
                    (string) $validated['identifier']
                );
            }

            $user = $result->user;

            if (!is_array($user)) {
                throw new RuntimeException(
                    'Successful login returned no user.'
                );
            }

            $userId = $user['id'] ?? null;

            if (!is_numeric($userId)) {
                throw new RuntimeException(
                    'Successful login returned an invalid user ID.'
                );
            }

            /**
             * Regenerate the session identifier immediately after
             * authentication to prevent session fixation.
             */
            session()->regenerate(true);

            session()->set([
                'is_authenticated' => true,

                'auth_user_id' =>
                (int) $userId,

                'auth_user_name' =>
                $this->resolveUserName($user),

                'auth_profile_reference' =>
                trim(
                    (string) (
                        $user['profile_ref_number']
                        ?? ''
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
                    'message' =>
                    $exception->getMessage(),
                ]
            );

            /**
             * Preserve only the identifier.
             *
             * Never call withInput() here because the submitted request
             * also contains the plain password.
             */
            return $this->redirectToLogin(
                identifier: $input['identifier'],
                formAlert: [
                    'type' => 'danger',
                    'title' => 'Login unavailable',
                    'message' =>
                    'We could not log you in right now. '
                        . 'Please try again.',
                ]
            );
        }
    }

    /**
     * Destroy the authenticated session.
     */
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
     * Read only the expected login fields.
     *
     * @return array{
     *     identifier: string,
     *     password: string
     * }
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
             * Do not trim passwords because spaces may legitimately be
             * part of the user's password.
             */
            'password' => (string) $this->request
                ->getPost('password'),
        ];
    }

    private function handleLoginFailure(
        LoginResult $result,
        string $identifier
    ): RedirectResponse {
        if (
            $result->field !== null
            && $result->message !== null
        ) {
            return $this->redirectToLogin(
                identifier: $identifier,
                validationErrors: [
                    $result->field =>
                    $result->message,
                ]
            );
        }

        return $this->redirectToLogin(
            identifier: $identifier,
            formAlert: [
                'type' => 'danger',
                'title' => 'Login failed',
                'message' => $result->message
                    ?? 'The login could not be completed.',
            ]
        );
    }

    /**
     * Redirect back to the login page while preserving only the login
     * identifier.
     *
     * The password is deliberately never written to flashdata.
     *
     * @param array<string, string>      $validationErrors
     * @param array<string, string>|null $formAlert
     */
    private function redirectToLogin(
        string $identifier,
        array $validationErrors = [],
        ?array $formAlert = null
    ): RedirectResponse {
        $redirect = redirect()
            ->to(
                route_to(
                    'web.login.password'
                )
            )
            ->with(
                'loginIdentifier',
                $identifier
            );

        if ($validationErrors !== []) {
            $redirect->with(
                'validationErrors',
                $validationErrors
            );
        }

        if ($formAlert !== null) {
            $redirect->with(
                'formAlert',
                $formAlert
            );
        }

        return $redirect;
    }

    /**
     * Determine whether the current session is authenticated.
     */
    private function isAuthenticated(): bool
    {
        return session('is_authenticated') === true
            && is_numeric(
                session('auth_user_id')
            );
    }

    /**
     * @param array<string, mixed> $user
     */
    private function resolveUserName(
        array $user
    ): string {
        $name = trim(
            (string) ($user['full_name'] ?? '')
        );

        return $name !== ''
            ? $name
            : 'Member';
    }

    /**
     * Prevent caching of authentication and password-reset pages.
     */
    private function preventPageCaching(): void
    {
        $this->response
            ->setHeader(
                'Cache-Control',
                'no-store, no-cache, must-revalidate, max-age=0'
            )
            ->setHeader(
                'Pragma',
                'no-cache'
            )
            ->setHeader(
                'Expires',
                '0'
            );
    }
}
