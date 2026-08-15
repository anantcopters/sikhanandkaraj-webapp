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

    /**
     * Display the searchable pending Aadhaar queue.
     */
    public function index(): string
    {
        $search = preg_replace(
            '/\s+/u',
            ' ',
            trim(
                (string) $this->request
                    ->getGet('search')
            )
        ) ?? '';

        $search = mb_substr(
            $search,
            0,
            100
        );

        /** @var MemberAadhaarService $service */
        $service = service(
            'memberAadhaarService'
        );

        $data = $service->pendingPage(
            $search,
            self::PER_PAGE
        );

        return view(
            'Admin/Members/PendingAadhaarApproval',
            [
                'pageTitle' =>
                'Pending Aadhaar Authentication',

                'members' =>
                $data['members'],

                'pager' =>
                $data['pager'],

                'search' =>
                $data['search'],

                'formAlert' =>
                $this->readFormAlert(),
            ]
        );
    }

    /**
     * Display one pending Aadhaar review.
     */
    public function review(
        string $profileReference
    ): string {
        try {
            /** @var MemberAadhaarService $service */
            $service = service(
                'memberAadhaarService'
            );

            $review = $service->review(
                $profileReference
            );

            return view(
                'Admin/Members/AadhaarReview',
                array_merge(
                    [
                        'pageTitle' =>
                        'Review Member Aadhaar',

                        'validationErrors' =>
                        $this->readValidationErrors(),

                        /*
                         * Determines whether rejection validation failed.
                         * Only the rejection modal is reopened in that case.
                         */
                        'validationWorkflow' =>
                        $this->readFlashString(
                            'validationWorkflow'
                        ),

                        'formAlert' =>
                        $this->readFormAlert(),

                        'pageScripts' => [
                            'assets/js/components/submit-loader.js',
                            'assets/js/pages/admin-member-aadhaar-review.js',
                        ],

                        /*
                         * The page receives the authenticated Admin route.
                         * It never receives a CloudFront URL directly.
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
            throw PageNotFoundException
                ::forPageNotFound();
        } catch (Throwable $exception) {
            $this->logFailure(
                $exception,
                'admin_member_aadhaar_review',
                $profileReference
            );

            throw PageNotFoundException
                ::forPageNotFound();
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
            $service = service(
                'memberAadhaarService'
            );

            $downloadUrl =
                $service->documentDownloadUrl(
                    $profileReference
                );

            return redirect()
                ->to($downloadUrl)
                ->setHeader(
                    'Cache-Control',
                    'private, no-store, no-cache, '
                        . 'must-revalidate, max-age=0'
                )
                ->setHeader(
                    'Pragma',
                    'no-cache'
                )
                ->setHeader(
                    'Expires',
                    '0'
                );
        } catch (DomainException) {
            throw PageNotFoundException
                ::forPageNotFound();
        } catch (Throwable $exception) {
            $this->logFailure(
                $exception,
                'admin_member_aadhaar_download',
                $profileReference
            );

            throw PageNotFoundException
                ::forPageNotFound();
        }
    }

    /**
     * Approve one pending Aadhaar submission.
     */
    public function approve(
        string $profileReference
    ): RedirectResponse {
        $input = $this->approvalInput();

        $validation = service(
            'validation'
        );

        $validation->setRules(
            MemberAadhaarValidation
                ::approvalRules()
        );

        if (!$validation->run($input)) {
            return redirect()
                ->to(
                    route_to(
                        'admin.members.'
                            . 'aadhaar-approvals.review',
                        $profileReference
                    )
                )
                ->withInput()
                ->with(
                    'validationErrors',
                    $validation->getErrors()
                )
                ->with(
                    'validationWorkflow',
                    'approve'
                );
        }

        try {
            /** @var MemberAadhaarService $service */
            $service = service(
                'memberAadhaarService'
            );

            $validated =
                $validation->getValidated();

            $service->approve(
                $profileReference,
                $this->adminUserId(),
                (string) $validated['aadhaar_name'],
                (string) $validated['date_of_birth']
            );

            return redirect()
                ->to(
                    route_to(
                        'admin.members.'
                            . 'aadhaar-approvals'
                    )
                )
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'success',

                        'title' =>
                        'Aadhaar approved',

                        'message' =>
                        'The member Aadhaar has been approved.',
                    ]
                );
        } catch (DomainException $exception) {
            return $this->businessFailure(
                $exception,
                $profileReference
            );
        } catch (Throwable $exception) {
            $this->logFailure(
                $exception,
                'admin_member_aadhaar_approve',
                $profileReference
            );

            return $this->technicalFailure(
                $profileReference
            );
        }
    }

    /**
     * Reject one pending Aadhaar submission.
     */
    public function reject(
        string $profileReference
    ): RedirectResponse {
        $input = [
            'rejection_reason' =>
            preg_replace(
                '/\s+/u',
                ' ',
                trim(
                    (string) $this->request
                        ->getPost(
                            'rejection_reason'
                        )
                )
            ) ?? '',
        ];

        $validation = service(
            'validation'
        );

        $validation->setRules(
            MemberAadhaarValidation
                ::rejectionRules()
        );

        if (!$validation->run($input)) {
            return redirect()
                ->to(
                    route_to(
                        'admin.members.'
                            . 'aadhaar-approvals.review',
                        $profileReference
                    )
                )
                ->withInput()
                ->with(
                    'validationErrors',
                    $validation->getErrors()
                )
                ->with(
                    'validationWorkflow',
                    'reject'
                );
        }

        try {
            /** @var MemberAadhaarService $service */
            $service = service(
                'memberAadhaarService'
            );

            $validated =
                $validation->getValidated();

            $service->reject(
                $profileReference,
                $this->adminUserId(),
                (string) $validated['rejection_reason']
            );

            return redirect()
                ->to(
                    route_to(
                        'admin.members.'
                            . 'aadhaar-approvals'
                    )
                )
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'success',

                        'title' =>
                        'Aadhaar rejected',

                        'message' =>
                        'The member can now upload a replacement Aadhaar document.',
                    ]
                );
        } catch (DomainException $exception) {
            return $this->businessFailure(
                $exception,
                $profileReference
            );
        } catch (Throwable $exception) {
            $this->logFailure(
                $exception,
                'admin_member_aadhaar_reject',
                $profileReference
            );

            return $this->technicalFailure(
                $profileReference
            );
        }
    }

    /**
     * Normalize approval form input before server validation.
     *
     * @return array{
     *     aadhaar_name:string,
     *     date_of_birth:string
     * }
     */
    private function approvalInput(): array
    {
        $day = trim(
            (string) $this->request
                ->getPost('birth_day')
        );

        $month = trim(
            (string) $this->request
                ->getPost('birth_month')
        );

        $year = trim(
            (string) $this->request
                ->getPost('birth_year')
        );

        $dateOfBirth = '';

        if (
            ctype_digit($day)
            && ctype_digit($month)
            && ctype_digit($year)
        ) {
            $dateOfBirth = sprintf(
                '%04d-%02d-%02d',
                (int) $year,
                (int) $month,
                (int) $day
            );
        }

        return [
            'aadhaar_name' =>
            preg_replace(
                '/\s+/u',
                ' ',
                trim(
                    (string) $this->request
                        ->getPost(
                            'aadhaar_name'
                        )
                )
            ) ?? '',

            'date_of_birth' =>
            $dateOfBirth,
        ];
    }

    /**
     * Return the current authenticated administrator ID.
     *
     * This follows the existing Admin controller session pattern.
     */
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

            throw PageNotFoundException
                ::forPageNotFound();
        }

        return (int) $adminUserId;
    }

    /**
     * Redirect after an expected business-rule failure.
     */
    private function businessFailure(
        DomainException $exception,
        string $profileReference
    ): RedirectResponse {
        return redirect()
            ->to(
                route_to(
                    'admin.members.'
                        . 'aadhaar-approvals.review',
                    $profileReference
                )
            )
            ->with(
                'formAlert',
                [
                    'type' =>
                    'warning',

                    'title' =>
                    'Review not saved',

                    'message' =>
                    $exception->getMessage(),
                ]
            );
    }

    /**
     * Redirect after an unexpected technical failure.
     */
    private function technicalFailure(
        string $profileReference
    ): RedirectResponse {
        return redirect()
            ->to(
                route_to(
                    'admin.members.'
                        . 'aadhaar-approvals.review',
                    $profileReference
                )
            )
            ->with(
                'formAlert',
                [
                    'type' =>
                    'danger',

                    'title' =>
                    'Review not saved',

                    'message' =>
                    'The Aadhaar review could not be saved. Please try again.',
                ]
            );
    }

    /**
     * Record a technical failure without exposing sensitive data.
     */
    private function logFailure(
        Throwable $exception,
        string $operation,
        string $profileReference
    ): void {
        service(
            'applicationErrorLogger'
        )->exception(
            $exception,
            'error',
            AdminErrorContext::forOperation(
                operation: $operation,

                component: self::class,

                method: $operation,

                additionalContext: [
                    'target_member_reference' =>
                    mb_substr(
                        $profileReference,
                        0,
                        32
                    ),
                ]
            )
        );
    }
}
