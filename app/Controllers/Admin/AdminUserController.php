<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AdminUserModel;
use App\Services\Admin\AdminInvitationService;
use App\Services\Admin\AdminManagementService;
use App\Validation\AdminUserValidation;
use CodeIgniter\HTTP\RedirectResponse;
use Throwable;

final class AdminUserController extends BaseController
{
    public function index(): string
    {
        return view(
            'Admin/Users/Index',
            [
                'pageTitle' =>
                'Administrator Management',
                'administrators' => (
                    new AdminUserModel()
                )->listAdministrators(),
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
            return redirect()
                ->to(route_to('admin.users.create'))
                ->with('adminFormInput', $input)
                ->with('formAlert', [
                    'type' => 'danger',
                    'title' =>
                    'Administrator not created',
                    'message' =>
                    $exception->getMessage(),
                ]);
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
            return redirect()
                ->to(route_to('admin.users.index'))
                ->with('formAlert', [
                    'type' => 'danger',
                    'title' => 'Unable to resend',
                    'message' =>
                    $exception->getMessage(),
                ]);
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
            return redirect()
                ->to(route_to('admin.users.index'))
                ->with('formAlert', [
                    'type' => 'danger',
                    'title' =>
                    'Unable to suspend administrator',
                    'message' =>
                    $exception->getMessage(),
                ]);
        }
    }
}
