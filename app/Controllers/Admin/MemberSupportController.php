<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\Admin\MemberSupportService;
use App\Validation\Admin\MemberSupportValidation;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;
use RuntimeException;
use Throwable;

final class MemberSupportController extends BaseController
{
    public function reports(): string
    {
        /** @var MemberSupportService $service */
        $service = service(
            'memberSupportService'
        );

        $result = $service
            ->reportPage(
                (string) $this->request
                    ->getGet('status'),

                (string) $this->request
                    ->getGet('search')
            );

        return view(
            'Admin/Support/ProfileReports',
            [
                'pageTitle' =>
                'Reported Profiles',

                'reports' =>
                $result['reports'],

                'pager' =>
                $result['pager'],

                'selectedStatus' =>
                $result['status'],

                'searchTerm' =>
                $result['search'],

                'validationErrors' =>
                $this->readValidationErrors(),

                'reviewRecord' =>
                $this->readArrayFlashData(
                    'reviewRecord'
                ),

                'formAlert' =>
                $this->readFormAlert(),

                'pageScripts' => [
                    'assets/js/components/form-validator.js',
                    'assets/js/components/submit-loader.js',
                    'assets/js/pages/admin-member-support.js',
                ],
            ]
        );
    }

    public function updateReport(
        int $reportId
    ): RedirectResponse {
        $input = [
            'status' =>
            mb_strtoupper(
                trim(
                    (string) $this->request
                        ->getPost('status')
                )
            ),

            'resolution_note' =>
            preg_replace(
                '/\s+/u',
                ' ',
                trim(
                    (string) $this->request
                        ->getPost(
                            'resolution_note'
                        )
                )
            ) ?? '',
        ];

        $validation = service(
            'validation'
        );

        $validation->setRules(
            MemberSupportValidation::reportRules()
        );

        if (!$validation->run($input)) {
            return redirect()
                ->to(
                    route_to(
                        'admin.support.reports'
                    )
                )
                ->withInput()
                ->with(
                    'validationErrors',
                    $validation->getErrors()
                )
                ->with(
                    'reviewRecord',
                    [
                        'type' => 'report',
                        'id' => (string) $reportId,
                    ]
                );
        }

        try {
            /** @var MemberSupportService $service */
            $service = service(
                'memberSupportService'
            );

            $validated = $validation
                ->getValidated();

            $service->reviewReport(
                $reportId,
                $this->adminUserId(),
                (string) $validated['status'],
                (string) $validated['resolution_note']
            );

            return redirect()
                ->to(
                    route_to(
                        'admin.support.reports'
                    )
                )
                ->with(
                    'formAlert',
                    [
                        'type' => 'success',
                        'title' =>
                        'Report reviewed',
                        'message' =>
                        'The profile report has been updated.',
                    ]
                );
        } catch (DomainException $exception) {
            return redirect()
                ->to(
                    route_to(
                        'admin.support.reports'
                    )
                )
                ->with(
                    'formAlert',
                    [
                        'type' => 'warning',
                        'title' =>
                        'Report not updated',
                        'message' =>
                        $exception->getMessage(),
                    ]
                );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Administrator report review failed. '
                    . 'Report: {reportId}; '
                    . 'Admin: {adminId}; '
                    . 'reason: {message}',
                [
                    'reportId' => $reportId,
                    'adminId' =>
                    session('admin_user_id'),
                    'message' =>
                    $exception->getMessage(),
                ]
            );

            return redirect()
                ->to(
                    route_to(
                        'admin.support.reports'
                    )
                )
                ->with(
                    'formAlert',
                    [
                        'type' => 'danger',
                        'title' =>
                        'Report not updated',
                        'message' =>
                        'The report could not be updated. '
                            . 'Please try again.',
                    ]
                );
        }
    }

    public function contacts(): string
    {
        /** @var MemberSupportService $service */
        $service = service(
            'memberSupportService'
        );

        $result = $service
            ->contactPage(
                (string) $this->request
                    ->getGet('status'),

                (string) $this->request
                    ->getGet('search')
            );

        return view(
            'Admin/Support/ContactRequests',
            [
                'pageTitle' =>
                'Contact Requests',

                'requests' =>
                $result['requests'],

                'pager' =>
                $result['pager'],

                'selectedStatus' =>
                $result['status'],

                'searchTerm' =>
                $result['search'],

                'validationErrors' =>
                $this->readValidationErrors(),

                'reviewRecord' =>
                $this->readArrayFlashData(
                    'reviewRecord'
                ),

                'formAlert' =>
                $this->readFormAlert(),

                'pageScripts' => [
                    'assets/js/components/form-validator.js',
                    'assets/js/components/submit-loader.js',
                    'assets/js/pages/admin-member-support.js',
                ],
            ]
        );
    }

    public function updateContact(
        int $requestId
    ): RedirectResponse {
        $input = [
            'status' =>
            mb_strtoupper(
                trim(
                    (string) $this->request
                        ->getPost('status')
                )
            ),

            'response_note' =>
            preg_replace(
                '/\s+/u',
                ' ',
                trim(
                    (string) $this->request
                        ->getPost(
                            'response_note'
                        )
                )
            ) ?? '',
        ];

        $validation = service(
            'validation'
        );

        $validation->setRules(
            MemberSupportValidation::contactRules()
        );

        if (!$validation->run($input)) {
            return redirect()
                ->to(
                    route_to(
                        'admin.support.contacts'
                    )
                )
                ->withInput()
                ->with(
                    'validationErrors',
                    $validation->getErrors()
                )
                ->with(
                    'reviewRecord',
                    [
                        'type' => 'contact',
                        'id' => (string) $requestId,
                    ]
                );
        }

        try {
            /** @var MemberSupportService $service */
            $service = service(
                'memberSupportService'
            );

            $validated = $validation
                ->getValidated();

            $service->reviewContactRequest(
                $requestId,
                $this->adminUserId(),
                (string) $validated['status'],
                (string) $validated['response_note']
            );

            return redirect()
                ->to(
                    route_to(
                        'admin.support.contacts'
                    )
                )
                ->with(
                    'formAlert',
                    [
                        'type' => 'success',
                        'title' =>
                        'Request reviewed',
                        'message' =>
                        'The Contact Us request has been updated.',
                    ]
                );
        } catch (DomainException $exception) {
            return redirect()
                ->to(
                    route_to(
                        'admin.support.contacts'
                    )
                )
                ->with(
                    'formAlert',
                    [
                        'type' => 'warning',
                        'title' =>
                        'Request not updated',
                        'message' =>
                        $exception->getMessage(),
                    ]
                );
        }
    }

    private function adminUserId(): int
    {
        $adminUserId = session(
            'admin_user_id'
        );

        if (
            !is_numeric($adminUserId)
            || (int) $adminUserId <= 0
        ) {
            session()->destroy();

            throw new RuntimeException(
                'The administrator session is unavailable.'
            );
        }

        return (int) $adminUserId;
    }
}
