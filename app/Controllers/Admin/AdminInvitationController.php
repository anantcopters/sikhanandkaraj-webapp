<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\Admin\AdminInvitationService;
use App\Validation\AdminUserValidation;
use CodeIgniter\HTTP\RedirectResponse;
use RuntimeException;
use Throwable;

final class AdminInvitationController extends BaseController
{
    /**
     * Display the administrator invitation acceptance page.
     */
    public function show(
        string $token
    ): string|RedirectResponse {
        $token = trim($token);

        try {
            /** @var AdminInvitationService $service */
            $service = service(
                'adminInvitationService'
            );

            // $invitationData = $service->inspectToken(
            //     $token
            // );

            return view(
                'Admin/Authentication/AcceptInvitation',
                [
                    'pageTitle' =>
                    'Create Administrator Password',

                    'admin' =>
                    'User',

                    'token' =>
                    $token,

                    'pageScripts' => [
                        'assets/js/components/password-toggle.js',
                        'assets/js/components/submit-loader.js',
                    ],
                ]
            );
        } catch (RuntimeException $exception) {
            return redirect()
                ->to(route_to('admin.login'))
                ->with(
                    'formAlert',
                    [
                        'type' => 'danger',
                        'title' =>
                        'Invitation unavailable',

                        'message' =>
                        $exception->getMessage(),
                    ]
                );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Unable to display administrator invitation: {message}',
                [
                    'message' =>
                    $exception->getMessage(),
                ]
            );

            return redirect()
                ->to(route_to('admin.login'))
                ->with(
                    'formAlert',
                    [
                        'type' => 'danger',
                        'title' =>
                        'Invitation unavailable',

                        'message' =>
                        'The invitation could not be opened. Please request a new invitation.',
                    ]
                );
        }
    }

    /**
     * Validate the password and activate the administrator account.
     */
    public function accept(
        string $token
    ): RedirectResponse {
        $token = trim($token);

        $input = [
            'password' => (string) $this->request
                ->getPost('password'),

            'password_confirmation' =>
            (string) $this->request
                ->getPost(
                    'password_confirmation'
                ),
        ];

        $validation = service('validation');

        $validation->setRules(
            AdminUserValidation::passwordRules()
        );

        if (!$validation->run($input)) {
            return redirect()
                ->to(
                    route_to(
                        'admin.invitation.show',
                        $token
                    )
                )
                ->withInput()
                ->with(
                    'validationErrors',
                    $validation->getErrors()
                );
        }

        try {
            /** @var AdminInvitationService $service */
            $service = service(
                'adminInvitationService'
            );

            $service->acceptInvitation(
                $token,
                $input['password']
            );

            return redirect()
                ->to(route_to('admin.login'))
                ->with(
                    'formAlert',
                    [
                        'type' => 'success',
                        'title' =>
                        'Account activated',

                        'message' =>
                        'Your administrator account has been verified. You can now log in.',
                    ]
                );
        } catch (RuntimeException $exception) {
            return redirect()
                ->to(route_to('admin.login'))
                ->with(
                    'formAlert',
                    [
                        'type' => 'danger',
                        'title' => 'Activation failed',
                        'message' =>
                        $exception->getMessage(),
                    ]
                );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Administrator invitation acceptance failed: {message}',
                [
                    'message' =>
                    $exception->getMessage(),
                ]
            );

            return redirect()
                ->to(
                    route_to(
                        'admin.invitation.show',
                        $token
                    )
                )
                ->withInput()
                ->with(
                    'formAlert',
                    [
                        'type' => 'danger',
                        'title' =>
                        'Activation failed',

                        'message' =>
                        'The administrator account could not be activated. Please try again.',
                    ]
                );
        }
    }
}
