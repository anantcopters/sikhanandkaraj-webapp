<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Services\Profile\MemberPhotoService;
use App\Support\ProfileErrorContext;
use App\Validation\Profile\MemberPhotoValidation;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;
use Throwable;

/**
 * Handles authenticated member-photo requests.
 */
final class MemberPhotoController extends BaseController
{
    /**
     * Display the member photo-management screen.
     */
    public function index(): string
    {
        $memberId = $this->authenticatedUserId();

        /** @var MemberPhotoService $service */
        $service = service(
            'memberPhotoService'
        );

        $data = $service->getForMember(
            $memberId
        );

        /** @var \Config\MemberMedia $mediaConfig */
        $mediaConfig = config(
            'MemberMedia'
        );

        return view(
            'Pages/Profile/Photos/Index',
            [
                'pageTitle' =>
                'Manage Photos',

                'user' =>
                $data['user'],

                'photos' =>
                $data['photos'],

                'photoCount' =>
                $data['count'],

                'approvedPhotoCount' =>
                $data['approvedCount'],

                'maximumPhotos' =>
                $data['maximum'],

                'remainingPhotos' =>
                $data['remaining'],

                /*
             * Keep upload guidance configuration-driven so the UI
             * cannot drift from server-side processing rules.
             */
                'maximumPhotoSizeKilobytes' =>
                $mediaConfig->profileMaxSizeKb,

                'minimumPhotoWidth' =>
                $mediaConfig->minimumWidth,

                'minimumPhotoHeight' =>
                $mediaConfig->minimumHeight,

                'recommendedPhotoWidth' =>
                $mediaConfig->recommendedWidth,

                'recommendedPhotoHeight' =>
                $mediaConfig->recommendedHeight,

                'validationErrors' =>
                $this->readValidationErrors()
                    ?? [],

                'formAlert' =>
                $this->readFormAlert(),

                'pageScripts' => [
                    'assets/js/pages/profile-photos.js',
                ],
            ]
        );
    }

    /**
     * Validate and upload one member photo.
     */
    public function upload(): RedirectResponse
    {
        $memberId = $this->authenticatedUserId();

        $validation = service(
            'validation'
        );

        /** @var \Config\MemberMedia $mediaConfig */
        $mediaConfig = config(
            'MemberMedia'
        );

        $validation->setRules(
            MemberPhotoValidation::uploadRules(
                $mediaConfig
            )
        );

        /*
         * Pass only scalar POST data. CI4 file validation reads the uploaded
         * photo directly from the request.
         */
        if (
            !$validation->run([
                'visibility' =>
                $this->request
                    ->getPost(
                        'visibility'
                    ),
            ])
        ) {
            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'validationErrors',
                    $validation->getErrors()
                );
        }

        $uploadedFile = $this->request
            ->getFile(
                'photo'
            );

        if (
            $uploadedFile === null
            || !$uploadedFile->isValid()
        ) {
            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'error',
                    $uploadedFile?->getErrorString()
                        ?? 'Please select a valid photo.'
                );
        }

        $visibility = mb_strtoupper(
            trim(
                (string) $this->request
                    ->getPost(
                        'visibility'
                    )
            )
        );

        $makePrimary = $this->request
            ->getPost(
                'make_primary'
            ) === '1';

        try {
            /** @var MemberPhotoService $service */
            $service = service(
                'memberPhotoService'
            );

            $service->upload(
                $memberId,
                $uploadedFile,
                $visibility,
                $makePrimary
            );

            $this->refreshMatchScoringSignals(
                $memberId,
                'PHOTO_UPLOAD'
            );

            return $this->successRedirect(
                'Photo uploaded',
                'Your photo was uploaded successfully '
                    . 'and is pending approval.'
            );
        } catch (DomainException $exception) {
            /*
             * Photo-count, invalid-image and business-rule failures are
             * expected user-facing outcomes.
             */
            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'error',
                    $exception->getMessage()
                );
        } catch (Throwable $exception) {
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'error',
                ProfileErrorContext::forMember(
                    memberId: $memberId,

                    operation: 'member_photo_upload',

                    component: self::class,

                    method: __FUNCTION__,

                    additionalContext: [
                        'mime_type' =>
                        $this->safeMimeType(
                            $uploadedFile
                        ),

                        'file_size_bytes' =>
                        $this->safeFileSize(
                            $uploadedFile
                        ),

                        'file_extension' =>
                        mb_strtolower(
                            trim(
                                (string) $uploadedFile
                                    ->getClientExtension()
                            )
                        ),

                        'visibility' =>
                        $visibility,

                        'requested_primary' =>
                        $makePrimary,
                    ]
                )
            );

            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'error',
                    'The photo could not be uploaded. '
                        . 'Please try again.'
                );
        }
    }

    /**
     * Mark one owned approved photo as primary.
     */
    public function makePrimary(
        int $photoId
    ): RedirectResponse {
        $memberId = $this->authenticatedUserId();

        try {
            /** @var MemberPhotoService $service */
            $service = service(
                'memberPhotoService'
            );

            $service->setPrimary(
                $memberId,
                $photoId
            );

            return $this->successRedirect(
                'Main photo updated',
                'The selected photo is now your main photo.'
            );
        } catch (DomainException) {
            throw PageNotFoundException
                ::forPageNotFound();
        } catch (Throwable $exception) {
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'error',
                ProfileErrorContext::forMember(
                    memberId: $memberId,

                    operation: 'member_photo_set_primary',

                    component: self::class,

                    method: __FUNCTION__,

                    additionalContext: [
                        'photo_id' =>
                        $photoId,
                    ]
                )
            );

            return $this->errorRedirect(
                'Main photo not updated',
                'Please try again.'
            );
        }
    }

    /**
     * Update visibility for one owned photo.
     */
    public function updateVisibility(
        int $photoId
    ): RedirectResponse {
        $memberId = $this->authenticatedUserId();

        $input = [
            'visibility' =>
            mb_strtoupper(
                trim(
                    (string) $this->request
                        ->getPost(
                            'visibility'
                        )
                )
            ),
        ];

        $validation = service(
            'validation'
        );

        $validation->setRules(
            MemberPhotoValidation
                ::visibilityRules()
        );

        if (!$validation->run($input)) {
            return $this->errorRedirect(
                'Visibility not updated',
                'Please select a valid visibility option.'
            );
        }

        try {
            /** @var MemberPhotoService $service */
            $service = service(
                'memberPhotoService'
            );

            $service->updateVisibility(
                $memberId,
                $photoId,
                $input['visibility']
            );

            return $this->successRedirect(
                'Visibility updated',
                'The photo visibility has been updated.'
            );
        } catch (DomainException) {
            throw PageNotFoundException
                ::forPageNotFound();
        } catch (Throwable $exception) {
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'error',
                ProfileErrorContext::forMember(
                    memberId: $memberId,

                    operation: 'member_photo_visibility_update',

                    component: self::class,

                    method: __FUNCTION__,

                    additionalContext: [
                        'photo_id' =>
                        $photoId,

                        'visibility' =>
                        $input['visibility'],
                    ]
                )
            );

            return $this->errorRedirect(
                'Visibility not updated',
                'Please try again.'
            );
        }
    }

    /**
     * Delete one owned member photo.
     */
    public function delete(
        int $photoId
    ): RedirectResponse {
        $memberId = $this->authenticatedUserId();

        try {
            /** @var MemberPhotoService $service */
            $service = service(
                'memberPhotoService'
            );

            $service->delete(
                $memberId,
                $photoId
            );

            $this->refreshMatchScoringSignals(
                $memberId,
                'PHOTO_DELETE'
            );

            return $this->successRedirect(
                'Photo deleted',
                'The photo has been removed.'
            );
        } catch (DomainException) {
            throw PageNotFoundException
                ::forPageNotFound();
        } catch (Throwable $exception) {
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'error',
                ProfileErrorContext::forMember(
                    memberId: $memberId,

                    operation: 'member_photo_delete',

                    component: self::class,

                    method: __FUNCTION__,

                    additionalContext: [
                        'photo_id' =>
                        $photoId,
                    ]
                )
            );

            return $this->errorRedirect(
                'Photo not deleted',
                'Please try again.'
            );
        }
    }

    /**
     * Redirect with a success alert.
     */
    private function successRedirect(
        string $title,
        string $message
    ): RedirectResponse {
        return redirect()
            ->to(
                route_to(
                    'web.profile.photos'
                )
            )
            ->with(
                'formAlert',
                [
                    'type' =>
                    'success',

                    'title' =>
                    $title,

                    'message' =>
                    $message,
                ]
            );
    }

    /**
     * Redirect with an error alert.
     */
    private function errorRedirect(
        string $title,
        string $message
    ): RedirectResponse {
        return redirect()
            ->to(
                route_to(
                    'web.profile.photos'
                )
            )
            ->with(
                'formAlert',
                [
                    'type' =>
                    'danger',

                    'title' =>
                    $title,

                    'message' =>
                    $message,
                ]
            );
    }

    /**
     * Read a file MIME type without allowing diagnostics to fail.
     */
    private function safeMimeType(
        object $uploadedFile
    ): string {
        try {
            return mb_substr(
                trim(
                    (string) $uploadedFile
                        ->getMimeType()
                ),
                0,
                100
            );
        } catch (Throwable) {
            return '';
        }
    }

    /**
     * Read the uploaded size without allowing diagnostics to fail.
     */
    private function safeFileSize(
        object $uploadedFile
    ): ?int {
        try {
            $size = $uploadedFile
                ->getSize();

            return is_numeric($size)
                ? max(
                    0,
                    (int) $size
                )
                : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Refresh cached profile-completion after a successful member-photo change.
     *
     * Profile completion uses presence of an active uploaded photo, while the
     * separate Match Score photo component uses approved_photo_count.
     */
    private function refreshMatchScoringSignals(
        int $userId,
        string $source
    ): void {
        try {
            service(
                'memberMatchScoringSignalService'
            )->refreshForUser(
                $userId
            );
        } catch (Throwable $exception) {
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'error',
                [
                    'operation' =>
                    'member_match_scoring_signal_refresh',

                    'controller' =>
                    self::class,

                    'method' =>
                    __FUNCTION__,

                    'member_id' =>
                    $userId,

                    'source' =>
                    $source,
                ]
            );
        }
    }
}
