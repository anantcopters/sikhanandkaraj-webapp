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

                    'memberActionNotice' =>
                    session(
                        'memberActionNotice'
                    ),

                    'pageScripts' => [
                        'assets/js/pages/'
                            . 'member-profile-actions.js',
                    ],
                ]
            )
        );
    }

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
                        (string) $target['profile_ref_number']
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
        } catch (PageNotFoundException) {
            throw PageNotFoundException
                ::forPageNotFound();
        } catch (DomainException $exception) {
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
        } catch (Throwable $exception) {
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

    public function block(
        string $profileReference
    ): RedirectResponse {
        $viewerUserId =
            $this->authenticatedUserId();

        /*
         * Explicit allowlist: comment is the only POST field.
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

        if (!$validation->run($input)) {
            return redirect()
                ->to(
                    route_to(
                        'web.members.view',
                        $profileReference
                    )
                )
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
                        $validation
                            ->getValidated()['comment']
                    )
                );

            /*
             * The newly blocked member must no longer be accessible,
             * therefore return to Dashboard rather than their profile.
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
        } catch (DomainException $exception) {
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
        } catch (Throwable $exception) {
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
}
