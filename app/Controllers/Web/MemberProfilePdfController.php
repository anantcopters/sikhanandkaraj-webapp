<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Exceptions\MembershipProfileQuotaExceededException;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;
use Throwable;

/**
 * Generates privacy-safe member profile PDFs.
 *
 * SECURITY:
 *
 * Another member's PDF is a representation of the protected Full Profile.
 * It must therefore pass through the same MemberProfileViewService /
 * ProfileAccessPolicy authorization boundary as the browser Full Profile.
 */
final class MemberProfilePdfController
extends BaseController
{
    /**
     * Logged-in member's own profile PDF.
     *
     * Own-profile export does not consume membership allowance.
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
     * Browser preview of the logged-in member's own profile PDF HTML.
     *
     * This deliberately uses the same presentation-data service and Pdf.php
     * view as PDF generation so browser preview and generated PDF cannot
     * diverge.
     *
     * Own-profile preview does not consume membership allowance.
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
     * Download another member's currently authorized Full Profile PDF.
     *
     * IMPORTANT:
     *
     * Do NOT query MemberProfileSummaryService directly here.
     *
     * MemberProfileViewService is the protected another-member boundary and
     * performs ProfileAccessPolicy authorization before sensitive data and
     * signed photo URLs are resolved.
     *
     * MembershipProfileUsageService treats an already-consumed target as a
     * repeat opening, so PDF generation does not consume another Full Profile
     * allowance for a profile already opened during this membership.
     */
    public function member(
        string $profileReference
    ): ResponseInterface {
        try {
            $viewerUserId =
                $this->authenticatedUserId();

            $profile = service(
                'memberProfileViewService'
            )->profileForViewer(
                $viewerUserId,
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

            /*
             * Profile state contains badge/presentation information only.
             *
             * It does NOT generate a video playback URL, so downloading the
             * PDF does not consume Live Introduction allowance.
             */
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
        } catch (
            MembershipProfileQuotaExceededException
            $exception
        ) {
            /*
             * Keep quota exhaustion explicit rather than converting it into a
             * generic PDF generation failure.
             */
            return $this->response
                ->setStatusCode(429)
                ->setJSON(
                    [
                        'message' =>
                        $exception
                            ->getMessage(),
                    ]
                );
        } catch (DomainException $exception) {
            /*
             * Membership/privacy denial.
             *
             * Do not expose the PDF when the browser Full Profile would not
             * be authorized.
             */
            return $this->response
                ->setStatusCode(403)
                ->setJSON(
                    [
                        'message' =>
                        $exception
                            ->getMessage(),
                    ]
                );
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

    /**
     * Return a generic operational PDF failure.
     *
     * Authorization failures are handled before this method so internal
     * exception details are never exposed to the browser.
     */
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
            ->setJSON(
                [
                    'message' =>
                    'The profile PDF could not be generated. '
                        . 'Please try again.',
                ]
            );
    }
}
