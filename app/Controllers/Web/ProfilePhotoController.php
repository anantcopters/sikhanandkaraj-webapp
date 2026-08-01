<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Services\Profile\MemberPhotoUrlService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;
use Throwable;

/**
 * Provides authorized profile-photo read endpoints.
 */
final class ProfilePhotoController extends BaseController
{
    /**
     * Return modal URLs for one approved member-owned photo.
     */
    public function originalUrl(
        int $photoId
    ): ResponseInterface {
        $memberId = $this->authenticatedUserId();

        try {
            /** @var MemberPhotoUrlService $service */
            $service = service(
                'memberPhotoUrlService'
            );

            $urls = $service
                ->getOwnedApprovedModalUrls(
                    $memberId,
                    $photoId
                );

            return $this->response
                ->setHeader(
                    'Cache-Control',
                    'no-store, no-cache, '
                        . 'must-revalidate, max-age=0'
                )
                ->setHeader(
                    'Pragma',
                    'no-cache'
                )
                ->setJSON([
                    'status' => 'success',
                    'data' => $urls,
                ]);
        } catch (DomainException $exception) {
            return $this->response
                ->setStatusCode(404)
                ->setHeader(
                    'Cache-Control',
                    'no-store, no-cache, '
                        . 'must-revalidate, max-age=0'
                )
                ->setJSON([
                    'status' => 'error',
                    'message' =>
                    $exception->getMessage(),
                ]);
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Profile modal photo request failed. '
                    . 'Member: {memberId}; '
                    . 'photo: {photoId}; '
                    . 'reason: {message}',
                [
                    'memberId' => $memberId,
                    'photoId' => $photoId,
                    'message' =>
                    $exception->getMessage(),
                ]
            );

            return $this->response
                ->setStatusCode(500)
                ->setHeader(
                    'Cache-Control',
                    'no-store, no-cache, '
                        . 'must-revalidate, max-age=0'
                )
                ->setJSON([
                    'status' => 'error',
                    'message' =>
                    'The enlarged photo could not be loaded.',
                ]);
        }
    }

    /**
     * Resolve the authenticated member ID.
     */
    private function authenticatedUserId(): int
    {
        $memberId = session(
            'auth_user_id'
        );

        if (!is_numeric($memberId)) {
            session()->destroy();

            throw PageNotFoundException::forPageNotFound();
        }

        return (int) $memberId;
    }
}
