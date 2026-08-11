<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Services\Profile\MemberPhotoUrlService;
use App\Support\ProfileErrorContext;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;
use Throwable;

/**
 * Provides authorized member-profile photo read endpoints.
 */
final class ProfilePhotoController extends BaseController
{
    /**
     * Return the medium URL for one approved photo owned
     * by the authenticated member.
     *
     * Original photographs are never exposed through
     * member-facing gallery endpoints.
     */
    public function mediumUrl(
        int $photoId
    ): ResponseInterface {
        $memberId =
            $this->authenticatedUserId();

        try {
            /** @var MemberPhotoUrlService $service */
            $service = service(
                'memberPhotoUrlService'
            );

            $mediumUrl = $service
                ->getOwnedApprovedMediumUrl(
                    $memberId,
                    $photoId
                );

            return $this->response
                ->setHeader(
                    'Cache-Control',
                    'private, no-store, no-cache, '
                        . 'must-revalidate, max-age=0'
                )
                ->setHeader(
                    'Pragma',
                    'no-cache'
                )
                ->setJSON([
                    'status' =>
                    'success',

                    'data' => [
                        'mediumUrl' =>
                        $mediumUrl,
                    ],
                ]);
        } catch (
            DomainException $exception
        ) {
            /*
         * Foreign, deleted or unapproved photographs are
         * expected authorization outcomes.
         *
         * Do not reveal which authorization check failed.
         */
            return $this->response
                ->setStatusCode(404)
                ->setHeader(
                    'Cache-Control',
                    'private, no-store, no-cache, '
                        . 'must-revalidate, max-age=0'
                )
                ->setHeader(
                    'Pragma',
                    'no-cache'
                )
                ->setJSON([
                    'status' =>
                    'error',

                    'message' =>
                    'The requested photo is unavailable.',
                ]);
        } catch (
            Throwable $exception
        ) {
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'warning',
                ProfileErrorContext::forMember(
                    memberId: $memberId,

                    operation: 'member_profile_photo_medium_url',

                    component: self::class,

                    method: __FUNCTION__,

                    additionalContext: [
                        'photo_id' =>
                        $photoId,
                    ]
                )
            );

            return $this->response
                ->setStatusCode(500)
                ->setHeader(
                    'Cache-Control',
                    'private, no-store, no-cache, '
                        . 'must-revalidate, max-age=0'
                )
                ->setHeader(
                    'Pragma',
                    'no-cache'
                )
                ->setJSON([
                    'status' =>
                    'error',

                    'message' =>
                    'The enlarged photo could not be loaded.',
                ]);
        }
    }
}
