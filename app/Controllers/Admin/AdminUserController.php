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

    /**
     * Create a new Administrator and queue the invitation email.
     *
     * Normal field validation is handled first. Business errors returned by the
     * invitation service, such as duplicate mobile/email, are translated back
     * into the same field-level validation contract used by the Create view.
     */
    public function store(): RedirectResponse
    {
        $input = [
            'full_name' =>
            trim(
                (string) $this->request
                    ->getPost(
                        'full_name'
                    )
            ),

            'mobile_number' =>
            trim(
                (string) $this->request
                    ->getPost(
                        'mobile_number'
                    )
            ),

            'email_address' =>
            mb_strtolower(
                trim(
                    (string) $this->request
                        ->getPost(
                            'email_address'
                        )
                )
            ),
        ];

        $validation =
            service(
                'validation'
            );

        $validation->setRules(
            AdminUserValidation
                ::createRules()
        );

        /*
     * ----------------------------------------------------------
     * Standard request validation
     * ----------------------------------------------------------
     *
     * Required, format and length errors are returned directly to
     * the related form fields.
     */
        if (
            !$validation->run(
                $input
            )
        ) {
            return redirect()
                ->to(
                    route_to(
                        'admin.users.create'
                    )
                )
                ->withInput()
                ->with(
                    'adminFormInput',
                    $input
                )
                ->with(
                    'validationErrors',
                    $validation->getErrors()
                );
        }

        try {
            /** @var AdminInvitationService $service */
            $service =
                service(
                    'adminInvitationService'
                );

            $service->createAdmin(
                $input['full_name'],
                $input['mobile_number'],
                $input['email_address'],
                (int) session(
                    'admin_user_id'
                )
            );

            return redirect()
                ->to(
                    route_to(
                        'admin.users.index'
                    )
                )
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'success',

                        'title' =>
                        'Administrator invited',

                        'message' =>
                        'The administrator was created and '
                            . 'an invitation email was queued.',
                    ]
                );
        } catch (\RuntimeException $exception) {
            /*
         * Known service/business errors such as duplicate mobile/email
         * should appear against the corresponding field instead of being
         * reduced to a generic page alert.
         */
            $fieldErrors =
                $this->adminCreationFieldErrors(
                    $exception->getMessage()
                );

            if ($fieldErrors !== []) {
                return redirect()
                    ->to(
                        route_to(
                            'admin.users.create'
                        )
                    )
                    ->withInput()
                    ->with(
                        'adminFormInput',
                        $input
                    )
                    ->with(
                        'validationErrors',
                        $fieldErrors
                    );
            }

            /*
         * Unknown RuntimeExceptions are still logged and presented safely.
         */
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
                     * Role is fixed by AdminInvitationService to ADMIN.
                     * Do not reference an undefined $validatedData variable.
                     */
                        'requested_role' =>
                        AdminUserModel
                        ::ROLE_ADMIN,
                    ]
                )
            );

            return redirect()
                ->to(
                    route_to(
                        'admin.users.create'
                    )
                )
                ->withInput()
                ->with(
                    'adminFormInput',
                    $input
                )
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
        } catch (Throwable $exception) {
            /*
         * Unexpected failures must not expose internal exception details.
         */
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
                        'requested_role' =>
                        AdminUserModel
                        ::ROLE_ADMIN,
                    ]
                )
            );

            return redirect()
                ->to(
                    route_to(
                        'admin.users.create'
                    )
                )
                ->withInput()
                ->with(
                    'adminFormInput',
                    $input
                )
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

    /**
     * Convert known Admin creation business errors into the field-level
     * validation contract consumed by Admin/Users/Create.php.
     *
     * @return array<string, string>
     */
    private function adminCreationFieldErrors(
        string $message
    ): array {
        $message =
            trim(
                $message
            );

        if ($message === '') {
            return [];
        }

        $normalizedMessage =
            strtolower(
                $message
            );

        if (
            str_contains(
                $normalizedMessage,
                'mobile number'
            )
        ) {
            return [
                'mobile_number' =>
                $message,
            ];
        }

        if (
            str_contains(
                $normalizedMessage,
                'email address'
            )
            || str_contains(
                $normalizedMessage,
                'email'
            )
        ) {
            return [
                'email_address' =>
                $message,
            ];
        }

        return [];
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
