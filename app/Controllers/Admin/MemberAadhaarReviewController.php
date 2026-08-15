<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\Profile\MemberAadhaarService;
use App\Support\AdminErrorContext;
use App\Validation\Profile\MemberAadhaarValidation;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;
use Throwable;

/**
 * Lists and reviews pending member Aadhaar submissions.
 */
final class MemberAadhaarReviewController extends BaseController
{
    private const PER_PAGE = 20;

    public function index(): string
    {
        $search = mb_substr(
            trim((string) $this->request->getGet('search')),
            0,
            100
        );

        /** @var MemberAadhaarService $service */
        $service = service('memberAadhaarService');
        $data = $service->pendingPage($search, self::PER_PAGE);

        return view('Admin/Members/PendingAadhaarApproval', [
            'pageTitle' => 'Pending Aadhaar Authentication',
            'members' => $data['members'],
            'pager' => $data['pager'],
            'search' => $data['search'],
            'formAlert' => $this->readFormAlert(),
        ]);
    }

    public function review(string $profileReference): string
    {
        try {
            /** @var MemberAadhaarService $service */
            $service = service('memberAadhaarService');

            $review = $service->review($profileReference);

            return view(
                'Admin/Members/AadhaarReview',
                array_merge(
                    [
                        'pageTitle' =>
                        'Review Member Aadhaar',

                        'validationErrors' =>
                        session('validationErrors') ?? [],

                        'formAlert' =>
                        $this->readFormAlert(),

                        'pageScripts' => [
                            'assets/js/components/submit-loader.js',
                            'assets/js/pages/admin-member-aadhaar-review.js',
                        ],

                        /*
                     * The page receives the authenticated application
                     * route, not the raw CloudFront URL.
                     */
                        'documentDownloadUrl' =>
                        route_to(
                            'admin.members.'
                                . 'aadhaar-approvals.document',
                            $profileReference
                        ),
                    ],
                    $review
                )
            );
        } catch (DomainException) {
            throw PageNotFoundException::forPageNotFound();
        } catch (Throwable $exception) {
            $this->logFailure(
                $exception,
                'admin_member_aadhaar_review',
                $profileReference
            );

            throw PageNotFoundException::forPageNotFound();
        }
    }

    /**
     * Redirect an authenticated administrator to a short-lived
     * private document URL.
     */
    public function document(
        string $profileReference
    ): RedirectResponse {
        try {
            /** @var MemberAadhaarService $service */
            $service = service('memberAadhaarService');

            return redirect()
                ->to(
                    $service->documentDownloadUrl(
                        $profileReference
                    )
                )
                ->setHeader(
                    'Cache-Control',
                    'private, no-store, no-cache, '
                        . 'must-revalidate, max-age=0'
                )
                ->setHeader(
                    'Pragma',
                    'no-cache'
                );
        } catch (DomainException) {
            throw PageNotFoundException::forPageNotFound();
        } catch (Throwable $exception) {
            $this->logFailure(
                $exception,
                'admin_member_aadhaar_download',
                $profileReference
            );

            throw PageNotFoundException::forPageNotFound();
        }
    }

    public function approve(string $profileReference): RedirectResponse
    {
        $input = $this->approvalInput();
        $validation = service('validation');
        $validation->setRules(MemberAadhaarValidation::approvalRules());

        if (!$validation->run($input)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('validationErrors', $validation->getErrors());
        }

        try {
            /** @var MemberAadhaarService $service */
            $service = service('memberAadhaarService');
            $validated = $validation->getValidated();
            $service->approve(
                $profileReference,
                $this->authenticatedAdminId(),
                (string) $validated['aadhaar_name'],
                (string) $validated['date_of_birth']
            );

            return redirect()
                ->to(route_to('admin.members.aadhaar-approvals'))
                ->with('formAlert', [
                    'type' => 'success',
                    'title' => 'Aadhaar approved',
                    'message' => 'The member Aadhaar has been approved.',
                ]);
        } catch (DomainException $exception) {
            return $this->businessFailure($exception, $profileReference);
        } catch (Throwable $exception) {
            $this->logFailure($exception, 'admin_member_aadhaar_approve', $profileReference);
            return $this->technicalFailure();
        }
    }

    public function reject(string $profileReference): RedirectResponse
    {
        $input = [
            'rejection_reason' => preg_replace(
                '/\s+/u',
                ' ',
                trim((string) $this->request->getPost('rejection_reason'))
            ) ?? '',
        ];
        $validation = service('validation');
        $validation->setRules(MemberAadhaarValidation::rejectionRules());

        if (!$validation->run($input)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('validationErrors', $validation->getErrors());
        }

        try {
            /** @var MemberAadhaarService $service */
            $service = service('memberAadhaarService');
            $validated = $validation->getValidated();
            $service->reject(
                $profileReference,
                $this->authenticatedAdminId(),
                (string) $validated['rejection_reason']
            );

            return redirect()
                ->to(route_to('admin.members.aadhaar-approvals'))
                ->with('formAlert', [
                    'type' => 'success',
                    'title' => 'Aadhaar rejected',
                    'message' => 'The member can now upload a replacement Aadhaar document.',
                ]);
        } catch (DomainException $exception) {
            return $this->businessFailure($exception, $profileReference);
        } catch (Throwable $exception) {
            $this->logFailure($exception, 'admin_member_aadhaar_reject', $profileReference);
            return $this->technicalFailure();
        }
    }

    /** @return array{aadhaar_name:string,date_of_birth:string} */
    private function approvalInput(): array
    {
        $day = trim((string) $this->request->getPost('birth_day'));
        $month = trim((string) $this->request->getPost('birth_month'));
        $year = trim((string) $this->request->getPost('birth_year'));
        $dateOfBirth = '';

        if (ctype_digit($day) && ctype_digit($month) && ctype_digit($year)) {
            $dateOfBirth = sprintf('%04d-%02d-%02d', (int) $year, (int) $month, (int) $day);
        }

        return [
            'aadhaar_name' => preg_replace(
                '/\s+/u',
                ' ',
                trim((string) $this->request->getPost('aadhaar_name'))
            ) ?? '',
            'date_of_birth' => $dateOfBirth,
        ];
    }

    private function businessFailure(
        DomainException $exception,
        string $profileReference
    ): RedirectResponse {
        return redirect()
            ->to(route_to('admin.members.aadhaar-approvals.review', $profileReference))
            ->with('formAlert', [
                'type' => 'warning',
                'title' => 'Review not saved',
                'message' => $exception->getMessage(),
            ]);
    }

    private function technicalFailure(): RedirectResponse
    {
        return redirect()
            ->back()
            ->with('formAlert', [
                'type' => 'danger',
                'title' => 'Review not saved',
                'message' => 'The Aadhaar review could not be saved. Please try again.',
            ]);
    }

    private function logFailure(
        Throwable $exception,
        string $operation,
        string $profileReference
    ): void {
        service('applicationErrorLogger')->exception(
            $exception,
            'error',
            AdminErrorContext::forOperation(
                operation: $operation,
                component: self::class,
                method: __FUNCTION__,
                additionalContext: [
                    'target_member_reference' => mb_substr($profileReference, 0, 32),
                ]
            )
        );
    }
}
