<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\Prelaunch\PrelaunchPhotoModel;
use App\Services\Prelaunch\PrelaunchAdminReviewService;
use App\Services\Prelaunch\PrelaunchPhotoService;
use App\Validation\Prelaunch\PrelaunchProfileValidation;
use App\Models\Prelaunch\PrelaunchProfileModel;
use App\Support\AdminErrorContext;
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
     * Number of profiles shown on one administrator list page.
     */
    private const PROFILES_PER_PAGE = 10;

    /**
     * Display the searchable and paginated prelaunch-profile listing.
     */
    public function index(): string
    {
        $this->assertFeatureEnabled();

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
                    PrelaunchProfileModel::STATUS_DRAFT,
                    PrelaunchProfileModel::STATUS_APPROVED,
                    PrelaunchProfileModel::STATUS_REJECTED,
                ],
                true
            )
        ) {
            $status =
                PrelaunchProfileModel::STATUS_DRAFT;
        }

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

        /** @var PrelaunchAdminReviewService $service */
        $service = service(
            'prelaunchAdminReviewService'
        );

        $result = $service->paginatedProfiles(
            $status,
            $search,
            self::PROFILES_PER_PAGE
        );

        return view(
            'Admin/Prelaunch/Profiles/Index',
            [
                'pageTitle' =>
                'Pre-launch Profiles',

                'profiles' =>
                $result['profiles'],

                'pager' =>
                $result['pager'],

                'selectedStatus' =>
                $result['status'],

                'searchTerm' =>
                $result['search'],

                'perPage' =>
                self::PROFILES_PER_PAGE,

                'formAlert' =>
                session('formAlert'),

                'pageScripts' => [
                    'assets/js/pages/'
                        . 'admin-prelaunch-profiles.js',
                ],
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
                        'assets/js/pages/admin-prelaunch-review.js',
                    ],
                ]
            );
        } catch (
            PageNotFoundException $exception
        ) {
            throw $exception;
        } catch (
            PageNotFoundException $exception
        ) {
            throw $exception;
        } catch (Throwable $exception) {
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'error',
                AdminErrorContext::forOperation(
                    operation: 'admin_prelaunch_profile_review',

                    component: self::class,

                    method: __FUNCTION__,

                    additionalContext: [
                        'prelaunch_profile_id' =>
                        $profileId,
                    ]
                )
            );

            if (ENVIRONMENT === 'development') {
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
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'critical',
                AdminErrorContext::forOperation(
                    operation: 'admin_prelaunch_profile_approve_and_migrate',

                    component: self::class,

                    method: __FUNCTION__,

                    additionalContext: [
                        'prelaunch_profile_id' =>
                        $profileId,
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
                        'Profile not approved',

                        'message' =>
                        'The profile could not be approved or migrated. '
                            . 'Please try again.',
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
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'error',
                AdminErrorContext::forOperation(
                    operation: 'admin_prelaunch_profile_reject',

                    component: self::class,

                    method: __FUNCTION__,

                    additionalContext: [
                        'prelaunch_profile_id' =>
                        $profileId,
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
                        'Profile not rejected',

                        /*
                 * Never display an unexpected internal exception message.
                 */
                        'message' =>
                        'The profile could not be rejected. '
                            . 'Please try again.',
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
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'error',
                AdminErrorContext::forOperation(
                    operation: 'admin_prelaunch_contact_update',

                    component: self::class,

                    method: __FUNCTION__,

                    additionalContext: [
                        'prelaunch_profile_id' =>
                        $profileId,
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
                        'Contact not updated',

                        /*
                 * Email and mobile are deliberately excluded from logging.
                 */
                        'message' =>
                        'The profile contact details could not be updated.',
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
                null,
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
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'error',
                AdminErrorContext::forOperation(
                    operation: 'admin_prelaunch_photo_moderation',

                    component: self::class,

                    method: __FUNCTION__,

                    additionalContext: [
                        'prelaunch_photo_id' =>
                        $photoId,

                        'requested_status' =>
                        $status,
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
                        'Photo not updated',

                        'message' =>
                        'The photograph status could not be updated.',
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
