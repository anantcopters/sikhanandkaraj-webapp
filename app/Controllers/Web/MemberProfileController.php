<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Services\Matchmaking\MemberInteractionService;
use App\Services\Matchmaking\MemberProfileViewService;
use App\Validation\Member\MemberBlockValidation;
use App\Services\Matchmaking\MemberInterestService;
use App\Exceptions\MembershipProfileQuotaExceededException;
use App\Services\Account\MemberProfileReportService;
use App\Validation\Member\MemberProfileReportValidation;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;
use Throwable;

/**
 * Displays and manages interactions with another member profile.
 */
final class MemberProfileController extends BaseController
{
    public function view(
        string $profileReference
    ): string {
        try {
            /** @var MemberProfileViewService $service */
            $service = service(
                'memberProfileViewService'
            );

            $profile = $service
                ->profileForViewer(
                    $this->authenticatedUserId(),
                    $profileReference
                );

            $profile['videoIntroductionState'] = service(
                'memberVideoIntroductionService'
            )->profileState(
                (int) (
                    $profile['viewedMemberId']
                    ?? 0
                )
            );

            $viewerUserId =
                $this->authenticatedUserId();

            /** @var MemberProfileReportService $reportService */
            $reportService = service(
                'memberProfileReportService'
            );

            $reportedProfileStatus =
                $reportService
                ->reportStatusForProfile(
                    $viewerUserId,
                    $profileReference
                );

            $hasReportedProfile =
                $reportedProfileStatus !== '';

            return view(
                'Pages/Profile/View',
                array_merge(
                    $profile,
                    [
                        'pageTitle' =>
                        'Member Profile',

                        'profileViewMode' =>
                        'other-member',

                        'profileBackUrl' =>
                        route_to(
                            'web.dashboard'
                        ),

                        'reportedProfileStatus' =>
                        $reportedProfileStatus,

                        'hasReportedProfile' =>
                        $hasReportedProfile,

                        'reportCaptcha' =>
                        $hasReportedProfile
                            ? ''
                            : service(
                                'memberProfileReportCaptchaService'
                            )->generate(),

                        'blockCaptcha' =>
                        service(
                            'memberProfileBlockCaptchaService'
                        )->generate(),

                        'reportValidationErrors' =>
                        session(
                            'reportValidationErrors'
                        ) ?? [],

                        'reopenReportModal' =>
                        !$hasReportedProfile
                            && session(
                                'reopenReportModal'
                            ) === true,

                        'memberActionNotice' =>
                        session(
                            'memberActionNotice'
                        ),

                        'pageScripts' => [
                            'assets/js/components/form-validator.js',
                            'assets/js/components/submit-loader.js',
                            'assets/js/pages/profile-view.js',
                            'assets/js/pages/member-profile-actions.js',
                            'assets/js/pages/video-introduction-playback.js',
                            'assets/js/pages/profile-pdf.js',
                        ],
                    ]
                )
            );
        } catch (
            MembershipProfileQuotaExceededException
            $exception
        ) {
            /*
            * Quota exhaustion is different from being Free.
            *
            * The member already owns a paid plan, so do not tell them simply
            * that a paid membership is required.
            */
            return view(
                'Pages/Profile/ProfileViewLimitReached',
                [
                    'pageTitle' =>
                    'Profile View Limit Reached',

                    'message' =>
                    $exception->getMessage(),
                ]
            );
        } catch (
            DomainException $exception
        ) {
            /*
            * ProfileAccessPolicy intentionally returns member-safe messages for:
            *
            * - Free membership;
            * - unverified target;
            * - female Interest privacy.
            *
            * Do not expose database or internal authorization information here.
            */
            return view(
                'Pages/Profile/ProfileAccessUnavailable',
                [
                    'pageTitle' =>
                    'Profile Unavailable',

                    'message' =>
                    $exception->getMessage(),
                ]
            );
        }
    }

    public function report(
        string $profileReference
    ): RedirectResponse {
        $input = [
            'description' =>
            preg_replace(
                '/\s+/u',
                ' ',
                trim(
                    (string) $this->request
                        ->getPost(
                            'description'
                        )
                )
            ) ?? '',

            'captcha_answer' =>
            trim(
                (string) $this->request
                    ->getPost(
                        'captcha_answer'
                    )
            ),
        ];

        $validation = service(
            'validation'
        );

        $validation->setRules(
            MemberProfileReportValidation::rules()
        );

        if (
            !$validation->run($input)
            || !service(
                'memberProfileReportCaptchaService'
            )->verify(
                $input['captcha_answer']
            )
        ) {
            $errors = $validation
                ->getErrors();

            if (
                !isset(
                    $errors['captcha_answer']
                )
            ) {
                $errors['captcha_answer'] =
                    'The security answer is incorrect or expired.';
            }

            return redirect()
                ->to(
                    route_to(
                        'web.members.view',
                        $profileReference
                    )
                )
                ->withInput()
                ->with(
                    'reportValidationErrors',
                    $errors
                )
                ->with(
                    'reopenReportModal',
                    true
                );
        }

        try {
            /** @var MemberProfileReportService $service */
            $service = service(
                'memberProfileReportService'
            );

            $service->report(
                $this->authenticatedUserId(),
                $profileReference,
                (string) $validation
                    ->getValidated()['description']
            );

            return redirect()
                ->to(
                    route_to(
                        'web.members.view',
                        $profileReference
                    )
                )
                ->with(
                    'memberActionNotice',
                    [
                        'title' =>
                        'Profile reported',

                        'message' =>
                        'Your report has been sent for '
                            . 'administrator review.',
                    ]
                );
        } catch (DomainException $exception) {
            return redirect()
                ->to(
                    route_to(
                        'web.members.view',
                        $profileReference
                    )
                )
                ->with(
                    'formAlert',
                    [
                        'type' => 'warning',
                        'title' =>
                        'Report not submitted',
                        'message' =>
                        $exception->getMessage(),
                    ]
                );
        }
    }

    /**
     * Show Interest in another member.
     */
    public function showInterest(
        string $profileReference
    ): RedirectResponse {
        $viewerUserId =
            $this->authenticatedUserId();

        try {
            /** @var MemberProfileViewService $profileService */
            $profileService = service(
                'memberProfileViewService'
            );

            $target =
                $profileService
                ->targetForAction(
                    $viewerUserId,
                    $profileReference
                );

            $targetUserId = max(
                0,
                (int) (
                    $target['id']
                    ?? 0
                )
            );

            $actionSource =
                trim(
                    (string) $this->request
                        ->getPost(
                            'action_source'
                        )
                );

            if (
                $targetUserId <= 0
            ) {
                throw PageNotFoundException
                    ::forPageNotFound();
            }

            /** @var MemberInteractionService $interactionService */
            $interactionService = service(
                'memberInteractionService'
            );

            $created =
                $interactionService
                ->showInterest(
                    $viewerUserId,
                    $targetUserId
                );

            $redirect = $actionSource === 'card'
                ? redirect()->back()
                : redirect()->to(
                    route_to(
                        'web.members.view',
                        (string) (
                            $target['profile_ref_number']
                        )
                    )
                );

            return $redirect
                ->with(
                    'memberActionNotice',
                    [
                        'title' =>
                        $created
                            ? 'Interest Sent'
                            : 'Interest Already Exists',

                        'message' =>
                        $created
                            ? 'Your interest has been sent successfully.'
                            : 'An interest relationship already exists between '
                            . 'you and this member.',
                    ]
                );
        } catch (
            PageNotFoundException) {
            throw PageNotFoundException
                ::forPageNotFound();
        } catch (
            DomainException $exception
        ) {
            return redirect()
                ->to(
                    route_to(
                        'web.dashboard'
                    )
                )
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Interest not sent',

                        'message' =>
                        $exception
                            ->getMessage(),
                    ]
                );
        } catch (
            Throwable $exception
        ) {
            log_message(
                'error',
                'Member interest failed for '
                    . 'member {memberId}: {message}',
                [
                    'memberId' =>
                    $viewerUserId,

                    'message' =>
                    $exception
                        ->getMessage(),
                ]
            );

            return redirect()
                ->to(
                    route_to(
                        'web.dashboard'
                    )
                )
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Interest not sent',

                        'message' =>
                        'We could not save your '
                            . 'interest. Please try again.',
                    ]
                );
        }
    }

    /**
     * Accept a Pending Interest while viewing the
     * member who sent it.
     */
    public function acceptInterest(
        string $profileReference
    ): RedirectResponse {
        return $this->respondToInterest(
            $profileReference,
            'accept'
        );
    }


    /**
     * Decline a Pending Interest while viewing the
     * member who sent it.
     */
    public function declineInterest(
        string $profileReference
    ): RedirectResponse {
        return $this->respondToInterest(
            $profileReference,
            'decline'
        );
    }

    /**
     * Add/remove another member from the current member's shortlist.
     */
    public function toggleShortlist(
        string $profileReference
    ): RedirectResponse {
        $viewerUserId =
            $this->authenticatedUserId();

        try {
            /** @var MemberProfileViewService $profileService */
            $profileService = service(
                'memberProfileViewService'
            );

            /*
         * Resolve the target again on the server.
         *
         * Never accept another member's database ID
         * from POST data.
         */
            $target = $profileService
                ->targetForAction(
                    $viewerUserId,
                    $profileReference
                );

            /** @var MemberInteractionService $interactionService */
            $interactionService = service(
                'memberInteractionService'
            );

            $isShortlisted = $interactionService
                ->toggleShortlist(
                    $viewerUserId,
                    (int) $target['id']
                );

            return redirect()
                ->to(
                    route_to(
                        'web.members.view',
                        (string) (
                            $target['profile_ref_number']
                        )
                    )
                )
                ->with(
                    'memberActionNotice',
                    [
                        'title' =>
                        $isShortlisted
                            ? 'Profile Shortlisted'
                            : 'Removed from Shortlist',

                        'message' =>
                        $isShortlisted
                            ? 'This profile has been added '
                            . 'to your shortlist.'
                            : 'This profile has been removed '
                            . 'from your shortlist.',
                    ]
                );
        } catch (
            PageNotFoundException) {
            throw PageNotFoundException
                ::forPageNotFound();
        } catch (
            DomainException $exception
        ) {
            return redirect()
                ->to(
                    route_to(
                        'web.dashboard'
                    )
                )
                ->with(
                    'formAlert',
                    [
                        /*
                        * Domain denial includes membership entitlement and valid relationship
                        * restrictions. It is not an application failure.
                        */
                        'type' =>
                        'warning',
                        'title' =>
                        'Shortlist not updated',

                        'message' =>
                        $exception
                            ->getMessage(),
                    ]
                );
        } catch (
            Throwable $exception
        ) {
            log_message(
                'error',
                'Member shortlist update failed. '
                    . 'Member: {memberId}; '
                    . 'reason: {message}',
                [
                    'memberId' =>
                    $viewerUserId,

                    'message' =>
                    $exception
                        ->getMessage(),
                ]
            );

            return redirect()
                ->to(
                    route_to(
                        'web.dashboard'
                    )
                )
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Shortlist not updated',

                        'message' =>
                        'We could not update your '
                            . 'shortlist. Please try again.',
                    ]
                );
        }
    }

    /**
     * Block another member.
     */
    public function block(
        string $profileReference
    ): RedirectResponse {
        $viewerUserId =
            $this->authenticatedUserId();

        /*
         * Explicit request allowlist.
         *
         * The public profile reference comes from the route, not from POST.
         */
        $input = [
            'comment' =>
            trim(
                (string)
                $this->request
                    ->getPost(
                        'comment'
                    )
            ),

            'captcha_answer' =>
            trim(
                (string)
                $this->request
                    ->getPost(
                        'captcha_answer'
                    )
            ),
        ];

        $validation = service(
            'validation'
        );

        $validation->setRules(
            MemberBlockValidation::rules()
        );

        if (
            !$validation->run(
                $input
            )
            || !service(
                'memberProfileBlockCaptchaService'
            )->verify(
                $input['captcha_answer']
            )
        ) {
            $errors =
                $validation
                ->getErrors();

            if (
                !isset(
                    $errors['captcha_answer']
                )
            ) {
                $errors['captcha_answer'] =
                    'The security answer is incorrect or expired.';
            }

            return redirect()
                ->to(
                    route_to(
                        'web.members.view',
                        $profileReference
                    )
                )
                ->withInput()
                ->with(
                    'validationErrors',
                    $errors
                )
                ->with(
                    'reopenMemberBlockModal',
                    true
                );
        }

        $validatedData =
            $validation
            ->getValidated();

        try {
            /** @var MemberProfileViewService $profileService */
            $profileService = service(
                'memberProfileViewService'
            );

            $target = $profileService
                ->targetForAction(
                    $viewerUserId,
                    $profileReference
                );

            /** @var MemberInteractionService $interactionService */
            $interactionService = service(
                'memberInteractionService'
            );

            $interactionService
                ->blockMember(
                    $viewerUserId,
                    (int) $target['id'],
                    (string) (
                        $validatedData['comment']
                        ?? ''
                    )
                );

            /*
             * Once blocked, direct access to that profile is no longer
             * permitted. Return to Dashboard.
             */
            return redirect()
                ->to(
                    route_to(
                        'web.dashboard'
                    )
                )
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'success',

                        'title' =>
                        'Member blocked',

                        'message' =>
                        'The member will no longer '
                            . 'appear in your matches '
                            . 'or member activity.',
                    ]
                );
        } catch (
            PageNotFoundException) {
            throw PageNotFoundException
                ::forPageNotFound();
        } catch (
            DomainException $exception
        ) {
            return redirect()
                ->to(
                    route_to(
                        'web.dashboard'
                    )
                )
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Member not blocked',

                        'message' =>
                        $exception
                            ->getMessage(),
                    ]
                );
        } catch (
            Throwable $exception
        ) {
            log_message(
                'error',
                'Member-to-member block failed. '
                    . 'Member: {memberId}; '
                    . 'reason: {message}',
                [
                    'memberId' =>
                    $viewerUserId,

                    'message' =>
                    $exception
                        ->getMessage(),
                ]
            );

            return redirect()
                ->to(
                    route_to(
                        'web.dashboard'
                    )
                )
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Member not blocked',

                        'message' =>
                        'We could not block the '
                            . 'member. Please try again.',
                    ]
                );
        }
    }

    /**
     * Return a viewer-authorized medium photo URL for one
     * approved gallery image belonging to another member.
     */
    public function photoMediumUrl(
        string $profileReference,
        int $photoId
    ): \CodeIgniter\HTTP\ResponseInterface {
        $viewerUserId =
            $this->authenticatedUserId();

        if ($photoId <= 0) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'success' => false,
                    'message' =>
                    'The requested photo is unavailable.',
                ]);
        }

        try {
            /** @var \App\Services\Matchmaking\MemberProfileViewService $profileService */
            $profileService = service(
                'memberProfileViewService'
            );

            /*
         * Re-resolve the target server-side.
         *
         * This applies the same active-member and block
         * authorization as the normal profile view.
         */
            $target = $profileService
                ->targetForAction(
                    $viewerUserId,
                    $profileReference
                );

            $targetUserId = max(
                0,
                (int) (
                    $target['id']
                    ?? 0
                )
            );

            if ($targetUserId <= 0) {
                return $this->response
                    ->setStatusCode(404)
                    ->setJSON([
                        'success' => false,
                        'message' =>
                        'The requested photo is unavailable.',
                    ]);
            }

            /** @var \App\Services\Matchmaking\MemberInteractionService $interactionService */
            $interactionService = service(
                'memberInteractionService'
            );

            $hasInterestRelationship =
                $interactionService
                ->hasInterestBetween(
                    $viewerUserId,
                    $targetUserId
                );

            /** @var \App\Services\Profile\MemberPhotoUrlService $photoUrlService */
            $photoUrlService = service(
                'memberPhotoUrlService'
            );

            $mediumUrl = $photoUrlService
                ->getApprovedGalleryMediumUrlForViewer(
                    memberId: $targetUserId,
                    viewerUserId: $viewerUserId,
                    photoId: $photoId,
                    hasInterestRelationship: $hasInterestRelationship
                );

            if ($mediumUrl === '') {
                return $this->response
                    ->setStatusCode(404)
                    ->setJSON([
                        'status' =>
                        'error',

                        'message' =>
                        'The requested photo is unavailable.',
                    ]);
            }

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
            \CodeIgniter\Exceptions\PageNotFoundException) {
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
        } catch (\Throwable $exception) {
            log_message(
                'error',
                'Viewer gallery medium URL failed. '
                    . 'Viewer: {viewerUserId}; '
                    . 'photo: {photoId}; '
                    . 'reason: {message}',
                [
                    'viewerUserId' =>
                    $viewerUserId,

                    'photoId' =>
                    $photoId,

                    'message' =>
                    $exception->getMessage(),
                ]
            );

            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'success' => false,
                    'message' =>
                    'The photo could not be loaded.',
                ]);
        }
    }

    /**
     * Respond to an incoming Interest from the profile page.
     *
     * The route supplies only the public profile reference.
     * Internal user IDs are always re-resolved server-side.
     */
    private function respondToInterest(
        string $profileReference,
        string $action
    ): RedirectResponse {
        $viewerUserId =
            $this->authenticatedUserId();

        try {
            /** @var MemberProfileViewService $profileService */
            $profileService = service(
                'memberProfileViewService'
            );

            /*
         * Re-resolve the sender server-side.
         *
         * This applies the same:
         *
         * - active member check;
         * - self-access protection;
         * - block protection
         *
         * as the normal profile view.
         */
            $sender = $profileService
                ->targetForAction(
                    $viewerUserId,
                    $profileReference
                );

            $senderUserId = max(
                0,
                (int) (
                    $sender['id']
                    ?? 0
                )
            );

            $resolvedProfileReference =
                trim(
                    (string) (
                        $sender['profile_ref_number']
                        ?? ''
                    )
                );

            if (
                $senderUserId <= 0
                || $resolvedProfileReference === ''
            ) {
                throw PageNotFoundException
                    ::forPageNotFound();
            }

            /** @var MemberInterestService $interestService */
            $interestService = service(
                'memberInterestService'
            );

            if ($action === 'accept') {
                /*
             * Sender -> Viewer is the received Interest.
             */
                $interestService
                    ->accept(
                        $senderUserId,
                        $viewerUserId
                    );

                return redirect()
                    ->to(
                        route_to(
                            'web.members.view',
                            $resolvedProfileReference
                        )
                    )
                    ->with(
                        'memberActionNotice',
                        [
                            'title' =>
                            'Interest Accepted',

                            'message' =>
                            'The interest has been '
                                . 'accepted successfully.',
                        ]
                    );
            }

            if ($action !== 'decline') {
                throw new DomainException(
                    'The requested Interest response is invalid.'
                );
            }

            $interestService
                ->decline(
                    $senderUserId,
                    $viewerUserId
                );

            return redirect()
                ->to(
                    route_to(
                        'web.members.view',
                        $resolvedProfileReference
                    )
                )
                ->with(
                    'memberActionNotice',
                    [
                        'title' =>
                        'Interest Declined',

                        'message' =>
                        'The interest has been '
                            . 'declined successfully.',
                    ]
                );
        } catch (
            PageNotFoundException) {
            throw PageNotFoundException
                ::forPageNotFound();
        } catch (
            DomainException $exception
        ) {
            return redirect()
                ->to(
                    route_to(
                        'web.members.view',
                        $profileReference
                    )
                )
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Interest not updated',

                        'message' =>
                        $exception
                            ->getMessage(),
                    ]
                );
        } catch (
            Throwable $exception
        ) {
            log_message(
                'error',
                'Profile Interest response failed. '
                    . 'Member: {memberId}; '
                    . 'profile: {profileReference}; '
                    . 'action: {action}; '
                    . 'reason: {message}',
                [
                    'memberId' =>
                    $viewerUserId,

                    'profileReference' =>
                    $profileReference,

                    'action' =>
                    $action,

                    'message' =>
                    $exception
                        ->getMessage(),
                ]
            );

            return redirect()
                ->to(
                    route_to(
                        'web.members.view',
                        $profileReference
                    )
                )
                ->with(
                    'formAlert',
                    [
                        'type' =>
                        'danger',

                        'title' =>
                        'Interest not updated',

                        'message' =>
                        'We could not update the '
                            . 'interest. Please try again.',
                    ]
                );
        }
    }
}
