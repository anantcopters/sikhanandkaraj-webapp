<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\Prelaunch\PrelaunchPhotoModel;
use App\Services\Prelaunch\PrelaunchAdminReviewService;
use App\Services\Prelaunch\PrelaunchPhotoService;
use App\Validation\Prelaunch\PrelaunchProfileValidation;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;
use Throwable;

/**
 * Administrator review controller for prelaunch profiles.
 */
final class PrelaunchProfileController extends BaseController
{
    /**
     * Display the administrator prelaunch-profile listing.
     */
    public function index(): string
    {
        $this->assertFeatureEnabled();

        /** @var PrelaunchAdminReviewService $service */
        $service = service(
            'prelaunchAdminReviewService'
        );

        $status = mb_strtoupper(
            trim(
                (string) $this->request
                    ->getGet('status')
            )
        );

        if (
            !in_array(
                $status,
                [
                    'DRAFT',
                    'APPROVED',
                    'REJECTED',
                ],
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
                $service->listProfiles(
                    $status
                ),

                'selectedStatus' =>
                $status,

                'formAlert' =>
                session('formAlert'),
            ]
        );
    }

    /**
     * Display one prelaunch profile for administrator review.
     */
    public function review(
        int $profileId
    ): string {
        $this->assertFeatureEnabled();

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
                    session(
                        'validationErrors'
                    ) ?? [],

                    'formAlert' =>
                    session('formAlert'),

                    'pageScripts' => [
                        'assets/js/components/submit-loader.js',
                    ],
                ]
            );
        } catch (
            PageNotFoundException $exception
        ) {
            throw $exception;
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Unable to render prelaunch review. '
                    . 'Profile: {profileId}; '
                    . 'exception: {exception}; '
                    . 'message: {message}; '
                    . 'file: {file}; '
                    . 'line: {line}.',
                [
                    'profileId' =>
                    $profileId,
                    'exception' =>
                    $exception::class,
                    'message' =>
                    $exception->getMessage(),
                    'file' =>
                    $exception->getFile(),
                    'line' =>
                    $exception->getLine(),
                ]
            );

            if (
                ENVIRONMENT
                === 'development'
            ) {
                throw $exception;
            }

            throw PageNotFoundException
                ::forPageNotFound();
        }
    }

    /**
     * Return a private locally staged prelaunch photograph.
     *
     * Prelaunch currently stores one optimized original. Medium and thumbnail
     * paths may be NULL, so unavailable variants safely fall back to original.
     */
    public function photo(
        int $photoId,
        string $size = 'original'
    ): ResponseInterface {
        $this->assertFeatureEnabled();

        /** @var PrelaunchPhotoModel $photoModel */
        $photoModel = model(
            PrelaunchPhotoModel::class
        );

        $photo = $photoModel->find(
            $photoId
        );

        if (!is_array($photo)) {
            throw PageNotFoundException
                ::forPageNotFound();
        }

        $requestedField = match (mb_strtolower(
            trim($size)
        )) {
            'thumbnail' =>
            'thumbnail_path',

            'medium' =>
            'medium_path',

            default =>
            'original_path',
        };

        $relativePath = trim(
            (string) (
                $photo[$requestedField]
                ?? ''
            )
        );

        /*
         * Current prelaunch storage generates only the optimized original.
         */
        if ($relativePath === '') {
            $relativePath = trim(
                (string) (
                    $photo['original_path']
                    ?? ''
                )
            );
        }

        if ($relativePath === '') {
            throw PageNotFoundException
                ::forPageNotFound();
        }

        /** @var PrelaunchPhotoService $photoService */
        $photoService = service(
            'prelaunchPhotoService'
        );

        $absolutePath =
            $photoService->absolutePath(
                $relativePath
            );

        if (
            !is_file($absolutePath)
            || !is_readable($absolutePath)
        ) {
            throw PageNotFoundException
                ::forPageNotFound();
        }

        $body = file_get_contents(
            $absolutePath
        );

        if ($body === false) {
            throw PageNotFoundException
                ::forPageNotFound();
        }

        return $this->response
            ->setHeader(
                'Content-Type',
                (string) (
                    $photo['mime_type']
                    ?? 'image/webp'
                )
            )
            ->setHeader(
                'Content-Disposition',
                'inline'
            )
            ->setHeader(
                'X-Content-Type-Options',
                'nosniff'
            )
            ->setHeader(
                'Cache-Control',
                'private, no-store, '
                    . 'no-cache, must-revalidate, '
                    . 'max-age=0'
            )
            ->setHeader(
                'Pragma',
                'no-cache'
            )
            ->setBody(
                $body
            );
    }

    /**
     * Approve one staged photograph.
     */
    public function approvePhoto(
        int $photoId
    ): RedirectResponse {
        return $this->decidePhoto(
            $photoId,
            PrelaunchPhotoModel
            ::STATUS_APPROVED
        );
    }

    /**
     * Reject one staged photograph.
     */
    public function rejectPhoto(
        int $photoId
    ): RedirectResponse {
        return $this->decidePhoto(
            $photoId,
            PrelaunchPhotoModel
            ::STATUS_REJECTED
        );
    }

    /**
     * Save corrected contacts, approve the profile and migrate it.
     */
    public function approve(
        int $profileId
    ): RedirectResponse {
        $this->assertFeatureEnabled();

        $input = $this->contactInput();

        $validation = service(
            'validation'
        );

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

            $result =
                $service
                ->saveContactAndApprove(
                    $profileId,
                    $validation
                        ->getValidated(),
                    $this->adminUserId()
                );

            return redirect()
                ->to(
                    route_to(
                        'admin.prelaunch.profiles.index'
                    )
                )
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'success',

                        'title' =>
                        'Profile approved',

                        'message' =>
                        sprintf(
                            'The profile was migrated '
                                . 'as member %s with '
                                . '%d approved photo(s).',
                            $result['profileReference'],
                            $result['migratedPhotoCount']
                        ),
                    ]
                );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Prelaunch approval failed. '
                    . 'Profile: {profileId}; '
                    . 'exception: {exception}; '
                    . 'message: {message}.',
                [
                    'profileId' =>
                    $profileId,
                    'exception' =>
                    $exception::class,
                    'message' =>
                    $exception->getMessage(),
                ]
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
                        'Profile not approved',

                        'message' =>
                        $exception->getMessage(),
                    ]
                );
        }
    }

    /**
     * Reject and permanently lock a draft prelaunch profile.
     */
    public function reject(
        int $profileId
    ): RedirectResponse {
        $this->assertFeatureEnabled();

        try {
            /** @var PrelaunchAdminReviewService $service */
            $service = service(
                'prelaunchAdminReviewService'
            );

            $service->rejectProfile(
                $profileId,
                (string) $this->request
                    ->getPost(
                        'rejection_reason'
                    ),
                $this->adminUserId()
            );

            return redirect()
                ->to(
                    route_to(
                        'admin.prelaunch.profiles.index'
                    )
                )
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'success',

                        'title' =>
                        'Profile rejected',

                        'message' =>
                        'The profile was rejected '
                            . 'and is now locked.',
                    ]
                );
        } catch (Throwable $exception) {
            return redirect()
                ->back()
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Profile not rejected',

                        'message' =>
                        $exception->getMessage(),
                    ]
                );
        }
    }

    /**
     * Retained for the existing route, although the review screen now uses
     * the combined Save Contact and Approve action.
     */
    public function updateContact(
        int $profileId
    ): RedirectResponse {
        $this->assertFeatureEnabled();

        $input = $this->contactInput();

        $validation = service(
            'validation'
        );

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
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'success',

                        'title' =>
                        'Contact updated',

                        'message' =>
                        'The mobile number and '
                            . 'email were updated.',
                    ]
                );
        } catch (Throwable $exception) {
            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Contact not updated',

                        'message' =>
                        $exception->getMessage(),
                    ]
                );
        }
    }

    /**
     * Save one photograph moderation decision.
     */
    private function decidePhoto(
        int $photoId,
        string $status
    ): RedirectResponse {
        $this->assertFeatureEnabled();

        try {
            /** @var PrelaunchAdminReviewService $service */
            $service = service(
                'prelaunchAdminReviewService'
            );

            $service->updatePhotoStatus(
                $photoId,
                $status,
                (string) $this->request
                    ->getPost(
                        'rejection_reason'
                    ),
                $this->adminUserId()
            );

            $action = $status
                === PrelaunchPhotoModel
                ::STATUS_APPROVED
                ? 'approved'
                : 'rejected';

            return redirect()
                ->back()
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'success',

                        'title' =>
                        'Photo updated',

                        'message' =>
                        sprintf(
                            'The photograph was %s.',
                            $action
                        ),
                    ]
                );
        } catch (Throwable $exception) {
            return redirect()
                ->back()
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Photo not updated',

                        'message' =>
                        $exception->getMessage(),
                    ]
                );
        }
    }

    /**
     * Return normalized contact input.
     *
     * @return array{
     *     email:string,
     *     country_code:string,
     *     mobile_number:string
     * }
     */
    private function contactInput(): array
    {
        return [
            'email' =>
            mb_strtolower(
                trim(
                    (string) $this->request
                        ->getPost('email')
                )
            ),

            'country_code' =>
            trim(
                (string) $this->request
                    ->getPost(
                        'country_code'
                    )
            ),

            'mobile_number' =>
            preg_replace(
                '/\D+/',
                '',
                (string) $this->request
                    ->getPost(
                        'mobile_number'
                    )
            ) ?? '',
        ];
    }

    /**
     * Resolve the currently logged-in administrator.
     */
    private function adminUserId(): int
    {
        $adminUserId = (int) session(
            'admin_user_id'
        );

        if ($adminUserId <= 0) {
            throw new RuntimeException(
                'The logged-in administrator '
                    . 'could not be identified.'
            );
        }

        return $adminUserId;
    }

    /**
     * Prevent access when prelaunch collection/review is disabled.
     */
    private function assertFeatureEnabled(): void
    {
        $configuration = config(
            'Prelaunch'
        );

        if (
            !$configuration
                ->profileEntryEnabled
        ) {
            throw PageNotFoundException
                ::forPageNotFound();
        }
    }
}
