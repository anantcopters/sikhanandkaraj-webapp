<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Services\Matchmaking\MemberInteractionService;
use App\Services\Matchmaking\MemberProfileViewService;
use App\Validation\Member\MemberBlockValidation;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;
use Throwable;

/**
 * Displays and manages interactions with another member profile.
 */
final class MemberProfileController extends BaseController
{
    /**
     * Display one authorized other-member profile.
     */
    public function view(
        string $profileReference
    ): string {
        /** @var MemberProfileViewService $service */
        $service = service(
            'memberProfileViewService'
        );

        $profile = $service
            ->profileForViewer(
                $this->authenticatedUserId(),
                $profileReference
            );

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

                    'profileBackLabel' =>
                    'Back to Dashboard',

                    'profileNoticeTitle' =>
                    'Member Profile',

                    'profileNoticeMessage' =>
                    'Review this member\'s profile and '
                        . 'choose whether you would like '
                        . 'to show interest.',

                    /*
                     * Server-side block validation.
                     */
                    'validationErrors' =>
                    $this->readValidationErrors(),

                    'memberActionNotice' =>
                    session(
                        'memberActionNotice'
                    ),

                    'pageScripts' => [
                        'assets/js/pages/profile-view.js',
                        'assets/js/pages/member-profile-actions.js',
                    ],
                ]
            )
        );
    }

    /**
     * Show interest in another member.
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

            /*
             * Resolve again server-side.
             *
             * Never trust a member ID supplied by the browser.
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

            $interactionService
                ->showInterest(
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
                        'Interest Shown',

                        'message' =>
                        'Your interest has been '
                            . 'shown successfully.',
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
                        'Interest not shown',

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
                        'Interest not shown',

                        'message' =>
                        'We could not save your '
                            . 'interest. Please try again.',
                    ]
                );
        }
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
                        'type' =>
                        'danger',

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
        ) {
            return redirect()
                ->to(
                    route_to(
                        'web.members.view',
                        $profileReference
                    )
                )
                /*
                 * Restore comment for the reopened modal.
                 */
                ->withInput()
                ->with(
                    'validationErrors',
                    $validation
                        ->getErrors()
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
}
