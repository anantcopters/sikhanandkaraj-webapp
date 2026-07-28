<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\Prelaunch\PrelaunchAdminReviewService;
use App\Services\Prelaunch\PrelaunchPhotoService;
use App\Validation\Prelaunch\PrelaunchProfileValidation;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;
use Throwable;

/**
 * Administrator review controller for pre-launch profiles.
 */
final class PrelaunchProfileController extends BaseController
{
    public function index(): string
    {
        $config = config('Prelaunch');

        if (!$config->profileEntryEnabled) {
            throw \CodeIgniter\Exceptions\PageNotFoundException
                ::forPageNotFound();
        }

        /** @var PrelaunchAdminReviewService $service */
        $service = service(
            'prelaunchAdminReviewService'
        );

        $status = mb_strtoupper(trim(
            (string) $this->request->getGet(
                'status'
            )
        ));

        if (
            !in_array(
                $status,
                ['DRAFT', 'APPROVED', 'REJECTED'],
                true
            )
        ) {
            $status = 'DRAFT';
        }

        return view(
            'Admin/Prelaunch/Profiles/Index',
            [
                'pageTitle' =>
                'Pre-launch Profiles',

                'profiles' =>
                $service->listProfiles($status),

                'selectedStatus' =>
                $status,

                'formAlert' =>
                session('formAlert'),
            ]
        );
    }

    /**
     * Display one pre-launch profile for administrator review.
     */
    public function review(
        int $profileId
    ): string {
        $config = config('Prelaunch');

        if (!$config->profileEntryEnabled) {
            throw PageNotFoundException
                ::forPageNotFound();
        }

        try {
            /** @var PrelaunchAdminReviewService $service */
            $service = service(
                'prelaunchAdminReviewService'
            );

            $reviewData = $service->reviewData(
                $profileId
            );

            return view(
                'Admin/Prelaunch/Profiles/Review',
                [
                    'pageTitle' =>
                    'Review Pre-launch Profile',

                    'profile' =>
                    $reviewData['profile'],

                    'photos' =>
                    $reviewData['photos'],

                    'photoSummary' =>
                    $reviewData['photoSummary'],

                    'validationErrors' =>
                    session('validationErrors')
                        ?? [],

                    'formAlert' =>
                    session('formAlert'),

                    'pageScripts' => [
                        'assets/js/components/submit-loader.js',
                    ],
                ]
            );
        } catch (PageNotFoundException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            /*
         * Preserve the actual failure in the CI4 logs. The previous
         * empty catch converted every SQL or view error into a 404.
         */
            log_message(
                'error',
                'Unable to render pre-launch review for profile {profileId}. '
                    . 'Exception: {exceptionClass}. Message: {message}. '
                    . 'File: {file}. Line: {line}.',
                [
                    'profileId' =>
                    $profileId,

                    'exceptionClass' =>
                    $exception::class,

                    'message' =>
                    $exception->getMessage(),

                    'file' =>
                    $exception->getFile(),

                    'line' =>
                    $exception->getLine(),
                ]
            );

            if (ENVIRONMENT === 'development') {
                throw $exception;
            }

            throw PageNotFoundException
                ::forPageNotFound();
        }
    }

    public function photo(
        int $photoId,
        string $size = 'medium'
    ): ResponseInterface {
        /** @var PrelaunchAdminReviewService $reviewService */
        $reviewService = service(
            'prelaunchAdminReviewService'
        );

        /** @var \App\Models\Prelaunch\PrelaunchPhotoModel $photoModel */
        $photoModel = model(
            \App\Models\Prelaunch\PrelaunchPhotoModel::class
        );

        $photo = $photoModel->find($photoId);

        if (!is_array($photo)) {
            throw PageNotFoundException::forPageNotFound();
        }

        $field = match ($size) {
            'thumbnail' => 'thumbnail_path',
            'original' => 'original_path',
            default => 'medium_path',
        };

        /** @var PrelaunchPhotoService $photoService */
        $photoService = service(
            'prelaunchPhotoService'
        );

        $path = $photoService->absolutePath(
            (string) $photo[$field]
        );

        if (!is_file($path)) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $this->response
            ->setHeader(
                'Content-Type',
                (string) $photo['mime_type']
            )
            ->setHeader(
                'Cache-Control',
                'private, no-store, max-age=0'
            )
            ->setBody(
                (string) file_get_contents($path)
            );
    }

    public function approvePhoto(
        int $photoId
    ): RedirectResponse {
        return $this->decidePhoto(
            $photoId,
            'APPROVED'
        );
    }

    public function rejectPhoto(
        int $photoId
    ): RedirectResponse {
        return $this->decidePhoto(
            $photoId,
            'REJECTED'
        );
    }

    public function approve(
        int $profileId
    ): RedirectResponse {
        return $this->profileDecision(
            $profileId,
            'APPROVED'
        );
    }

    public function reject(
        int $profileId
    ): RedirectResponse {
        return $this->profileDecision(
            $profileId,
            'REJECTED'
        );
    }

    public function updateContact(
        int $profileId
    ): RedirectResponse {
        $input = [
            'email' =>
            mb_strtolower(trim(
                (string) $this->request->getPost(
                    'email'
                )
            )),

            'country_code' =>
            trim((string) $this->request->getPost(
                'country_code'
            )),

            'mobile_number' =>
            preg_replace(
                '/\D+/',
                '',
                (string) $this->request->getPost(
                    'mobile_number'
                )
            ) ?? '',
        ];

        $validation = service('validation');
        $validation->setRules(
            PrelaunchProfileValidation
                ::adminContactRules()
        );

        if (!$validation->run($input)) {
            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'validationErrors',
                    $validation->getErrors()
                );
        }

        try {
            /** @var PrelaunchAdminReviewService $service */
            $service = service(
                'prelaunchAdminReviewService'
            );

            $service->updateContact(
                $profileId,
                $validation->getValidated(),
                $this->adminUserId()
            );

            return redirect()
                ->back()
                ->with('formAlert', [
                    'type' => 'success',
                    'title' => 'Contact updated',
                    'message' =>
                    'The mobile number and email were updated and audited.',
                ]);
        } catch (Throwable $exception) {
            return redirect()
                ->back()
                ->withInput()
                ->with('formAlert', [
                    'type' => 'danger',
                    'title' => 'Contact not updated',
                    'message' => $exception->getMessage(),
                ]);
        }
    }

    private function decidePhoto(
        int $photoId,
        string $status
    ): RedirectResponse {
        try {
            /** @var PrelaunchAdminReviewService $service */
            $service = service(
                'prelaunchAdminReviewService'
            );

            $service->updatePhotoStatus(
                $photoId,
                $status,
                (string) $this->request->getPost(
                    'rejection_reason'
                ),
                $this->adminUserId()
            );

            return redirect()
                ->back()
                ->with('formAlert', [
                    'type' => 'success',
                    'title' => 'Photo updated',
                    'message' =>
                    'The photograph decision was saved.',
                ]);
        } catch (Throwable $exception) {
            return redirect()
                ->back()
                ->with('formAlert', [
                    'type' => 'danger',
                    'title' => 'Photo not updated',
                    'message' => $exception->getMessage(),
                ]);
        }
    }

    private function profileDecision(
        int $profileId,
        string $status
    ): RedirectResponse {
        try {
            /** @var PrelaunchAdminReviewService $service */
            $service = service(
                'prelaunchAdminReviewService'
            );

            if ($status === 'APPROVED') {
                $service->approveProfile(
                    $profileId,
                    $this->adminUserId()
                );
            } else {
                $service->rejectProfile(
                    $profileId,
                    (string) $this->request->getPost(
                        'rejection_reason'
                    ),
                    $this->adminUserId()
                );
            }

            return redirect()
                ->to(
                    route_to(
                        'admin.prelaunch.profiles.index'
                    )
                )
                ->with('formAlert', [
                    'type' => 'success',
                    'title' => 'Profile reviewed',
                    'message' =>
                    'The profile decision was saved.',
                ]);
        } catch (Throwable $exception) {
            return redirect()
                ->back()
                ->with('formAlert', [
                    'type' => 'danger',
                    'title' => 'Decision not saved',
                    'message' => $exception->getMessage(),
                ]);
        }
    }

    private function adminUserId(): int
    {
        $adminUserId = (int) session(
            'admin_user_id'
        );

        if ($adminUserId <= 0) {
            throw new RuntimeException(
                'The logged-in administrator could not be identified.'
            );
        }

        return $adminUserId;
    }
}
