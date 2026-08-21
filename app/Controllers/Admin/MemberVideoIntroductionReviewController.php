<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\Admin\MemberVideoModerationService;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;
use Throwable;

final class MemberVideoIntroductionReviewController extends BaseController
{
    public function index(): string
    {
        $search = mb_substr(
            preg_replace(
                '/\s+/u',
                ' ',
                trim(
                    (string) $this->request
                        ->getGet(
                            'search'
                        )
                )
            ) ?? '',
            0,
            100
        );

        $selectedStatus = mb_strtoupper(
            trim(
                (string) $this->request
                    ->getGet(
                        'status'
                    )
            )
        );

        $allowedStatuses = [
            'ALL',
            'PROCESSING',
            'PROCESSING_FAILED',
            'PENDING_REVIEW',
            'APPROVED',
            'REJECTED',
            'RESUBMISSION_REQUESTED',
            'REPLACED',
            'DELETED',
        ];

        if (
            !in_array(
                $selectedStatus,
                $allowedStatuses,
                true
            )
        ) {
            $selectedStatus =
                'PENDING_REVIEW';
        }

        /** @var MemberVideoModerationService $service */
        $service = service(
            'memberVideoModerationService'
        );

        $videos = $service->listing(
            $selectedStatus
        );

        if ($search !== '') {
            $searchValue = mb_strtolower(
                $search
            );

            $videos = array_values(
                array_filter(
                    $videos,
                    static function (
                        array $video
                    ) use (
                        $searchValue
                    ): bool {
                        $memberName = mb_strtolower(
                            trim(
                                (string) (
                                    $video['full_name']
                                    ?? ''
                                )
                            )
                        );

                        $referenceNumber =
                            mb_strtolower(
                                trim(
                                    (string) (
                                        $video['profile_ref_number'] ?? ''
                                    )
                                )
                            );

                        return str_contains(
                            $memberName,
                            $searchValue
                        )
                            || str_contains(
                                $referenceNumber,
                                $searchValue
                            );
                    }
                )
            );
        }

        return view(
            'Admin/Members/PendingVideoIntroductionApproval',
            [
                'pageTitle' =>
                'Video Introduction Approvals',

                'videos' =>
                $videos,

                'search' =>
                $search,

                'selectedStatus' =>
                $selectedStatus,

                'formAlert' =>
                $this->readFormAlert(),
            ]
        );
    }

    public function review(
        string $publicId
    ): string {
        /** @var MemberVideoModerationService $service */
        $service = service(
            'memberVideoModerationService'
        );

        return view(
            'Admin/Members/VideoIntroductionReview',
            array_merge(
                $service->review($publicId),
                [
                    'pageTitle' =>
                    'Review Video Introduction',

                    'formAlert' =>
                    $this->readFormAlert(),

                    'validationErrors' =>
                    session('validationErrors')
                        ?? [],

                    'pageScripts' => [
                        'assets/js/components/form-validator.js',
                        'assets/js/pages/admin-video-introduction-review.js',
                        'assets/js/components/submit-loader.js',
                    ],
                ]
            )
        );
    }

    public function moderate(
        string $publicId
    ): RedirectResponse {
        try {
            /** @var MemberVideoModerationService $service */
            $service = service(
                'memberVideoModerationService'
            );

            $decision = mb_strtoupper(
                trim(
                    (string) $this->request->getPost(
                        'decision'
                    )
                )
            );

            $reason = trim(
                (string) $this->request->getPost(
                    'reason'
                )
            );

            $rules = [
                'decision' => [
                    'label' => 'Decision',
                    'rules' => [
                        'required',
                        'in_list[APPROVE,REJECT,RESUBMIT]',
                    ],
                    'errors' => [
                        'required' =>
                        'Please select a decision.',

                        'in_list' =>
                        'Please select a valid decision.',
                    ],
                ],

                'reason' => [
                    'label' => 'Reason',
                    'rules' => [
                        'permit_empty',
                        'max_length[500]',
                    ],
                    'errors' => [
                        'max_length' =>
                        'The reason cannot exceed 500 characters.',
                    ],
                ],
            ];

            if (
                in_array(
                    $decision,
                    [
                        'REJECT',
                        'RESUBMIT',
                    ],
                    true
                )
            ) {
                $rules['reason']['rules'] = [
                    'required',
                    'min_length[10]',
                    'max_length[500]',
                ];

                $rules['reason']['errors'] = [
                    'required' =>
                    'Please provide a reason.',

                    'min_length' =>
                    'The reason must contain at least '
                        . '10 characters.',

                    'max_length' =>
                    'The reason cannot exceed 500 characters.',
                ];
            }

            if (! $this->validate($rules)) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with(
                        'validationErrors',
                        $this->validator->getErrors()
                    );
            }

            $service->moderate(
                $publicId,
                (int) session('admin_user_id'),
                $decision,
                $reason
            );

            return redirect()
                ->to(
                    route_to(
                        'admin.members.video-introductions'
                    )
                )
                ->with(
                    'formAlert',
                    [
                        'type' => 'success',

                        'title' =>
                        'Review saved',

                        'message' =>
                        'The Video Introduction moderation '
                            . 'decision has been saved.',
                    ]
                );
        } catch (DomainException $exception) {
            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'formAlert',
                    [
                        'type' => 'warning',

                        'title' =>
                        'Review not saved',

                        'message' =>
                        $exception->getMessage(),
                    ]
                );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Video Introduction moderation failed: '
                    . '{message}',
                [
                    'message' =>
                    $exception->getMessage(),
                ]
            );

            return redirect()
                ->back()
                ->with(
                    'formAlert',
                    [
                        'type' => 'danger',

                        'title' =>
                        'Review not saved',

                        'message' =>
                        'Please try again.',
                    ]
                );
        }
    }
}
