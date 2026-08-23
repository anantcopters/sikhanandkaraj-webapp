<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

final class MemberProfilePdfController
extends BaseController
{
    /**
     * Logged-in member's own profile PDF.
     */
    public function own(): ResponseInterface
    {
        $userId =
            $this->authenticatedUserId();

        try {
            $profile = service(
                'memberProfileSummaryService'
            )->getForUser(
                $userId
            );

            $profile['approvedPhotos'] = service(
                'memberPhotoUrlService'
            )->getApprovedThumbnailPhotos(
                $userId
            );

            $trust = service(
                'memberTrustVerificationService'
            )->getForUser(
                $userId
            );

            $mobile =
                isset($trust['mobile'])
                && is_array(
                    $trust['mobile']
                )
                ? $trust['mobile']
                : [];

            $email =
                isset($trust['email'])
                && is_array(
                    $trust['email']
                )
                ? $trust['email']
                : [];

            $profile['viewedMobile'] = (string) (
                $mobile['value']
                ?? ''
            );

            $profile['isViewedMobileVerified'] = (
                $mobile['isVerified']
                ?? false
            ) === true;

            $profile['viewedEmail'] = (string) (
                $email['value']
                ?? ''
            );

            $profile['isViewedEmailVerified'] = (
                $email['isVerified']
                ?? false
            ) === true;

            $profile['videoIntroductionState'] = service(
                'memberVideoIntroductionService'
            )->profileState(
                $userId
            );

            return $this->renderPdf(
                $userId,
                $profile
            );
        } catch (Throwable $exception) {
            return $this->failure(
                $exception
            );
        }
    }

    /**
     * Browser preview of the logged-in member's own
     * profile PDF HTML.
     *
     * This deliberately uses the same presentation-data
     * service and same Pdf.php view as PDF generation so
     * browser preview and generated PDF cannot diverge.
     */
    public function preview(): ResponseInterface
    {
        $userId =
            $this->authenticatedUserId();

        try {
            $profile = service(
                'memberProfileSummaryService'
            )->getForUser(
                $userId
            );

            $profile['approvedPhotos'] = service(
                'memberPhotoUrlService'
            )->getApprovedThumbnailPhotos(
                $userId
            );

            $trust = service(
                'memberTrustVerificationService'
            )->getForUser(
                $userId
            );

            $mobile =
                isset($trust['mobile'])
                && is_array(
                    $trust['mobile']
                )
                ? $trust['mobile']
                : [];

            $email =
                isset($trust['email'])
                && is_array(
                    $trust['email']
                )
                ? $trust['email']
                : [];

            $profile['viewedMobile'] =
                (string) (
                    $mobile['value']
                    ?? ''
                );

            $profile['isViewedMobileVerified'] =
                (
                    $mobile['isVerified']
                    ?? false
                ) === true;

            $profile['viewedEmail'] =
                (string) (
                    $email['value']
                    ?? ''
                );

            $profile['isViewedEmailVerified'] =
                (
                    $email['isVerified']
                    ?? false
                ) === true;

            $profile['videoIntroductionState'] =
                service(
                    'memberVideoIntroductionService'
                )->profileState(
                    $userId
                );

            $data = service(
                'memberProfilePdfDataService'
            )->prepare(
                $userId,
                $profile
            );

            $html = view(
                'Pages/Profile/Pdf',
                $data
            );

            return $this->response
                ->setHeader(
                    'Content-Type',
                    'text/html; charset=UTF-8'
                )
                ->setHeader(
                    'Cache-Control',
                    'private, no-store, no-cache, '
                        . 'must-revalidate, max-age=0'
                )
                ->setHeader(
                    'Pragma',
                    'no-cache'
                )
                ->setBody(
                    $html
                );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Profile PDF HTML preview failed: {message}',
                [
                    'message' =>
                    $exception
                        ->getMessage(),
                ]
            );

            return $this->response
                ->setStatusCode(422)
                ->setBody(
                    'The profile PDF preview could not be generated.'
                );
        }
    }

    /**
     * PDF of another member's currently authorized profile.
     */
    public function member(
        string $profileReference
    ): ResponseInterface {
        try {
            /*
             * Reuse the existing profile authorization boundary.
             *
             * Do not independently query another member here.
             */
            $profile = service(
                'memberProfileViewService'
            )->profileForViewer(
                $this->authenticatedUserId(),
                $profileReference
            );

            $memberId = max(
                0,
                (int) (
                    $profile['viewedMemberId']
                    ?? 0
                )
            );

            if ($memberId <= 0) {
                throw PageNotFoundException
                    ::forPageNotFound();
            }

            $profile['videoIntroductionState'] = service(
                'memberVideoIntroductionService'
            )->profileState(
                $memberId
            );

            return $this->renderPdf(
                $memberId,
                $profile
            );
        } catch (
            PageNotFoundException
            $exception
        ) {
            throw $exception;
        } catch (Throwable $exception) {
            return $this->failure(
                $exception
            );
        }
    }

    /**
     * @param array<string,mixed> $profile
     */
    private function renderPdf(
        int $profileOwnerUserId,
        array $profile
    ): ResponseInterface {
        $generated = service(
            'memberProfilePdfService'
        )->generate(
            $profileOwnerUserId,
            $profile
        );

        return $this->response
            ->setHeader(
                'Content-Type',
                'application/pdf'
            )
            ->setHeader(
                'Content-Disposition',
                'attachment; filename="'
                    . $generated['filename']
                    . '"'
            )
            ->setHeader(
                'Cache-Control',
                'private, no-store, no-cache, '
                    . 'must-revalidate, max-age=0'
            )
            ->setHeader(
                'Pragma',
                'no-cache'
            )
            ->setBody(
                $generated['content']
            );
    }

    private function failure(
        Throwable $exception
    ): ResponseInterface {
        log_message(
            'error',
            'Profile PDF generation failed: {message}',
            [
                'message' =>
                $exception
                    ->getMessage(),
            ]
        );

        return $this->response
            ->setStatusCode(422)
            ->setJSON([
                'message' =>
                'The profile PDF could not be generated. '
                    . 'Please try again.',
            ]);
    }
}
