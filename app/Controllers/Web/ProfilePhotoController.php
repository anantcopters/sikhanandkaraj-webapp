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

                    'data' =>
                    $urls,
                ]);
        } catch (DomainException $exception) {
            /*
             * An unavailable, unapproved or foreign photo is an expected
             * authorization outcome. Do not log it as an application error.
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

                    /*
                     * Do not expose internal ownership or moderation details.
                     */
                    'message' =>
                    'The requested photo is unavailable.',
                ]);
        } catch (Throwable $exception) {
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'warning',
                ProfileErrorContext::forMember(
                    memberId: $memberId,

                    operation: 'member_profile_photo_modal_url',

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

    /**
     * Resolve the authenticated member identifier.
     */
    private function authenticatedUserId(): int
    {
        $memberId = session(
            'auth_user_id'
        );

        if (!is_numeric($memberId)) {
            session()->destroy();

            throw PageNotFoundException
                ::forPageNotFound();
        }

        $resolvedMemberId =
            (int) $memberId;

        if ($resolvedMemberId <= 0) {
            session()->destroy();

            throw PageNotFoundException
                ::forPageNotFound();
        }

        return $resolvedMemberId;
    }
}
