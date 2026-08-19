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
        /** @var MemberVideoModerationService $service */
        $service = service(
            'memberVideoModerationService'
        );

        return view(
            'Admin/Members/PendingVideoIntroductionApproval',
            [
                'pageTitle' =>
                'Video Introduction Approvals',

                'videos' =>
                $service->queue(),

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

            $service->moderate(
                $publicId,
                (int) session('admin_user_id'),
                (string) $this->request->getPost(
                    'decision'
                ),
                (string) $this->request->getPost(
                    'reason'
                )
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
