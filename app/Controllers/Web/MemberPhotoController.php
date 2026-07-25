<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Services\Profile\MemberPhotoService;
use App\Validation\Profile\MemberPhotoValidation;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use Config\MemberMedia;
use DomainException;
use Throwable;

/**
 * Handles authenticated member photo web requests.
 */
final class MemberPhotoController extends BaseController
{
    public function index(): string
    {
        $memberId = $this->authenticatedUserId();

        /** @var MemberPhotoService $service */
        $service = service('memberPhotoService');

        $data = $service->getForMember($memberId);

        return view(
            'Pages/Profile/Photos/Index',
            [
                'pageTitle' => 'Manage Photos',
                'user' => $data['user'],
                'photos' => $data['photos'],
                'photoCount' => $data['count'],
                'maximumPhotos' => $data['maximum'],
                'remainingPhotos' => $data['remaining'],
                'validationErrors' =>
                session('validationErrors') ?? [],
                'formAlert' => session('formAlert'),
                'pageScripts' => [
                    'assets/js/pages/profile-photos.js',
                ],
            ]
        );
    }

    public function upload(): RedirectResponse
    {
        $memberId = $this->authenticatedUserId();

        /** @var MemberMedia $config */
        $config = config('MemberMedia');

        $validation = service('validation');

        $validation->setRules(
            MemberPhotoValidation::uploadRules(
                $config->profileMaxSizeKb
            )
        );

        $input = [
            'photo' => $this->request->getFile('photo'),
            'visibility' =>
            strtoupper(
                trim(
                    (string) $this->request
                        ->getPost('visibility')
                )
            ),
            'is_primary' =>
            $this->request->getPost('is_primary')
                !== null
                ? '1'
                : '0',
        ];

        if (!$validation->run($input)) {
            return redirect()
                ->to(route_to('web.profile.photos'))
                ->withInput()
                ->with(
                    'validationErrors',
                    $validation->getErrors()
                );
        }

        $uploadedFile = $this->request
            ->getFile('photo');

        if (
            $uploadedFile === null
            || !$uploadedFile->isValid()
            || $uploadedFile->hasMoved()
        ) {
            return redirect()
                ->to(route_to('web.profile.photos'))
                ->withInput()
                ->with('formAlert', [
                    'type' => 'danger',
                    'title' => 'Photo not uploaded',
                    'message' =>
                    'Please select a valid photo and try again.',
                ]);
        }

        try {
            /** @var MemberPhotoService $service */
            $service = service('memberPhotoService');

            $service->upload(
                $memberId,
                $uploadedFile,
                $input['visibility'],
                $input['is_primary'] === '1'
            );

            return redirect()
                ->to(route_to('web.profile.photos'))
                ->with('formAlert', [
                    'type' => 'success',
                    'title' => 'Photo uploaded',
                    'message' =>
                    'Your photo has been uploaded and '
                        . 'is pending approval.',
                ]);
        } catch (DomainException $exception) {
            return redirect()
                ->to(route_to('web.profile.photos'))
                ->withInput()
                ->with('formAlert', [
                    'type' => 'danger',
                    'title' => 'Photo not uploaded',
                    'message' => $exception->getMessage(),
                ]);
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Member photo upload request failed for '
                    . 'member {memberId}: {message}',
                [
                    'memberId' => $memberId,
                    'message' => $exception->getMessage(),
                ]
            );

            return redirect()
                ->to(route_to('web.profile.photos'))
                ->withInput()
                ->with('formAlert', [
                    'type' => 'danger',
                    'title' => 'Photo not uploaded',
                    'message' =>
                    'We could not upload your photo. '
                        . 'Please try again.',
                ]);
        }
    }

    public function makePrimary(
        int $photoId
    ): RedirectResponse {
        $memberId = $this->authenticatedUserId();

        try {
            /** @var MemberPhotoService $service */
            $service = service('memberPhotoService');

            $service->setPrimary(
                $memberId,
                $photoId
            );

            return $this->successRedirect(
                'Main photo updated',
                'The selected photo is now your main photo.'
            );
        } catch (DomainException $exception) {
            throw PageNotFoundException::forPageNotFound();
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Main photo update failed for '
                    . 'member {memberId}, photo {photoId}: '
                    . '{message}',
                [
                    'memberId' => $memberId,
                    'photoId' => $photoId,
                    'message' => $exception->getMessage(),
                ]
            );

            return $this->errorRedirect(
                'Main photo not updated',
                'Please try again.'
            );
        }
    }

    public function updateVisibility(
        int $photoId
    ): RedirectResponse {
        $memberId = $this->authenticatedUserId();

        $input = [
            'visibility' => strtoupper(
                trim(
                    (string) $this->request
                        ->getPost('visibility')
                )
            ),
        ];

        $validation = service('validation');

        $validation->setRules(
            MemberPhotoValidation::visibilityRules()
        );

        if (!$validation->run($input)) {
            return $this->errorRedirect(
                'Visibility not updated',
                'Please select a valid visibility option.'
            );
        }

        try {
            /** @var MemberPhotoService $service */
            $service = service('memberPhotoService');

            $service->updateVisibility(
                $memberId,
                $photoId,
                $input['visibility']
            );

            return $this->successRedirect(
                'Visibility updated',
                'The photo visibility has been updated.'
            );
        } catch (DomainException $exception) {
            throw PageNotFoundException::forPageNotFound();
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Photo visibility update failed for '
                    . 'member {memberId}, photo {photoId}: '
                    . '{message}',
                [
                    'memberId' => $memberId,
                    'photoId' => $photoId,
                    'message' => $exception->getMessage(),
                ]
            );

            return $this->errorRedirect(
                'Visibility not updated',
                'Please try again.'
            );
        }
    }

    public function delete(
        int $photoId
    ): RedirectResponse {
        $memberId = $this->authenticatedUserId();

        try {
            /** @var MemberPhotoService $service */
            $service = service('memberPhotoService');

            $service->delete(
                $memberId,
                $photoId
            );

            return $this->successRedirect(
                'Photo deleted',
                'The photo has been removed.'
            );
        } catch (DomainException $exception) {
            throw PageNotFoundException::forPageNotFound();
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Photo deletion failed for member '
                    . '{memberId}, photo {photoId}: {message}',
                [
                    'memberId' => $memberId,
                    'photoId' => $photoId,
                    'message' => $exception->getMessage(),
                ]
            );

            return $this->errorRedirect(
                'Photo not deleted',
                'Please try again.'
            );
        }
    }

    private function successRedirect(
        string $title,
        string $message
    ): RedirectResponse {
        return redirect()
            ->to(route_to('web.profile.photos'))
            ->with('formAlert', [
                'type' => 'success',
                'title' => $title,
                'message' => $message,
            ]);
    }

    private function errorRedirect(
        string $title,
        string $message
    ): RedirectResponse {
        return redirect()
            ->to(route_to('web.profile.photos'))
            ->with('formAlert', [
                'type' => 'danger',
                'title' => $title,
                'message' => $message,
            ]);
    }

    
    /**
     * Resolve the authenticated user identifier.
     */
    private function authenticatedUserId(): int
    {
        $userId = session('auth_user_id');

        if (!is_numeric($userId)) {
            session()->destroy();

            throw PageNotFoundException::forPageNotFound();
        }

        return (int) $userId;
    }
}
