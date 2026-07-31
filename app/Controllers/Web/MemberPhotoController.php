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
                'approvedPhotoCount' => $data['approvedCount'],
                'maximumPhotos' => $data['maximum'],
                'remainingPhotos' => $data['remaining'],
                'validationErrors' =>
                $this->readValidationErrors() ?? [],

                'formAlert' =>
                $this->readFormAlert(),
                'pageScripts' => [
                    'assets/js/pages/profile-photos.js',
                ],
            ]
        );
    }

    public function upload(): RedirectResponse
    {
        $memberId = $this->authenticatedUserId();

        $validation = service('validation');

        $validationRules = [
            'photo' => [
                'label' => 'Photo',
                'rules' => [
                    'uploaded[photo]',
                    'is_image[photo]',
                    'mime_in[photo,image/jpeg,image/png]',
                    'ext_in[photo,jpg,jpeg,png]',
                    'max_size[photo,10240]',
                    'min_dims[photo,400,400]',
                    'max_dims[photo,8000,8000]',
                ],
            ],

            'visibility' => [
                'label' => 'Photo visibility',
                'rules' => [
                    'required',
                    'in_list[PUBLIC,INTERESTED_MEMBERS]',
                ],
            ],
        ];

        $validation->setRules($validationRules);

        /*
     * Pass only scalar POST data. The photo is retrieved by CI4's
     * file-validation rules directly from the request.
     */
        if (
            !$validation->run([
                'visibility' => $this->request->getPost(
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

        $uploadedFile = $this->request->getFile('photo');

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

        $visibility = (string) $this->request->getPost(
            'visibility'
        );

        $makePrimary = $this->request->getPost(
            'make_primary'
        ) === '1';

        try {
            /** @var MemberPhotoService $memberPhotoService */
            $memberPhotoService = service(
                'memberPhotoService'
            );

            $memberPhotoService->upload(
                $memberId,
                $uploadedFile,
                $visibility,
                $makePrimary
            );

            return $this->successRedirect(
                'Photo uploaded',
                'Your photo was uploaded successfully and is pending approval.'
            );
        } catch (DomainException $exception) {
            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'error',
                    $exception->getMessage()
                );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Member photo upload failed for member '
                    . '{memberId}: {message}',
                [
                    'memberId' => $memberId,
                    'message' => $exception->getMessage(),
                ]
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
