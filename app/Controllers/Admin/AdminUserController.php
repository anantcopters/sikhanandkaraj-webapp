<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AdminUserModel;
use App\Services\Admin\AdminInvitationService;
use App\Services\Admin\AdminManagementService;
use App\Support\AdminErrorContext;
use App\Validation\AdminUserValidation;
use CodeIgniter\HTTP\RedirectResponse;
use Throwable;

final class AdminUserController extends BaseController
{
    public function index(): string
    {
        /** @var \App\Services\Admin\AdminManagementService $service */
        $service = service(
            'adminManagementService'
        );

        return view(
            'Admin/Users/Index',
            [
                'pageTitle' =>
                'Manage Administrators',

                'administrators' =>
                $service->listAdministrators(),
            ]
        );
    }

    public function create(): string
    {
        return view(
            'Admin/Users/Create',
            [
                'pageTitle' =>
                'Add Administrator',
                'pageScripts' => [
                    'assets/js/components/submit-loader.js',
                ],
            ]
        );
    }

    public function store(): RedirectResponse
    {
        $input = [
            'full_name' => trim(
                (string) $this->request
                    ->getPost('full_name')
            ),
            'mobile_number' => trim(
                (string) $this->request
                    ->getPost('mobile_number')
            ),
            'email_address' => trim(
                (string) $this->request
                    ->getPost('email_address')
            ),
        ];

        $validation = service('validation');

        $validation->setRules(
            AdminUserValidation::createRules()
        );

        if (!$validation->run($input)) {
            return redirect()
                ->to(route_to('admin.users.create'))
                ->with('adminFormInput', $input)
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

            $service->createAdmin(
                $input['full_name'],
                $input['mobile_number'],
                $input['email_address'],
                (int) session('admin_user_id')
            );

            return redirect()
                ->to(route_to('admin.users.index'))
                ->with('formAlert', [
                    'type' => 'success',
                    'title' =>
                    'Administrator invited',
                    'message' =>
                    'The administrator was created and '
                        . 'an invitation email was queued.',
                ]);
        } catch (Throwable $exception) {
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'error',
                AdminErrorContext::forOperation(
                    operation: 'admin_user_create',

                    component: self::class,

                    method: __FUNCTION__,

                    additionalContext: [
                        /*
                 * Do not log the invited administrator's email or name.
                 */
                        'requested_role' =>
                        $validatedData['role']
                            ?? null,
                    ]
                )
            );

            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Administrator not created',

                        'message' =>
                        'The administrator could not be created.',
                    ]
                );
        }
    }

    public function resend(
        int $adminUserId
    ): RedirectResponse {
        try {
            /** @var AdminInvitationService $service */
            $service = service(
                'adminInvitationService'
            );

            $service->resendInvitation(
                $adminUserId,
                (int) session('admin_user_id')
            );

            return redirect()
                ->to(route_to('admin.users.index'))
                ->with('formAlert', [
                    'type' => 'success',
                    'title' => 'Invitation resent',
                    'message' =>
                    'A new invitation email was queued.',
                ]);
        } catch (Throwable $exception) {
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'error',
                AdminErrorContext::forOperation(
                    operation: 'admin_invitation_resend',

                    component: self::class,

                    method: __FUNCTION__,

                    additionalContext: [
                        'target_admin_user_id' =>
                        $adminUserId,
                    ]
                )
            );

            return redirect()
                ->back()
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Invitation not resent',

                        'message' =>
                        'The invitation could not be resent.',
                    ]
                );
        }
    }

    public function suspend(
        int $adminUserId
    ): RedirectResponse {
        try {
            /** @var AdminManagementService $service */
            $service = service(
                'adminManagementService'
            );

            $service->suspend(
                $adminUserId
            );

            return redirect()
                ->to(route_to('admin.users.index'))
                ->with('formAlert', [
                    'type' => 'success',
                    'title' =>
                    'Administrator suspended',
                    'message' =>
                    'The administrator can no longer log in.',
                ]);
        } catch (Throwable $exception) {
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'error',
                AdminErrorContext::forOperation(
                    operation: 'admin_user_suspend',

                    component: self::class,

                    method: __FUNCTION__,

                    additionalContext: [
                        'target_admin_user_id' =>
                        $adminUserId,
                    ]
                )
            );

            return redirect()
                ->back()
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Administrator not suspended',

                        'message' =>
                        'The administrator status could not be changed.',
                    ]
                );
        }
    }
}
