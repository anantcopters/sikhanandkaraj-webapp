<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Services\Matchmaking\MemberInterestService;
use App\Services\Matchmaking\MemberProfileViewService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;
use Throwable;

/**
 * Displays and manages member Interests.
 */
final class InterestController extends BaseController
{
    /**
     * Display received/sent interests.
     */
    public function index(): string
    {
        $userId =
            $this->authenticatedUserId();

        $direction = trim(
            (string) (
                $this->request
                ->getGet(
                    'direction'
                )
                ?? MemberInterestService
                ::DIRECTION_RECEIVED
            )
        );

        $filter = trim(
            (string) (
                $this->request
                ->getGet(
                    'status'
                )
                ?? MemberInterestService
                ::FILTER_ALL
            )
        );

        /** @var MemberInterestService $service */
        $service = service(
            'memberInterestService'
        );

        $pageData = $service
            ->pageData(
                $userId,
                $direction,
                $filter
            );

        return view(
            'Pages/Interests/Index',
            array_merge(
                $pageData,
                [
                    'pageTitle' =>
                    'Interests',

                    'interestActionNotice' =>
                    session(
                        'interestActionNotice'
                    ),

                    'pageScripts' => [
                        'assets/js/pages/member-interests.js',
                    ],
                ]
            )
        );
    }

    /**
     * Accept an interest received from another member.
     */
    public function accept(
        string $profileReference
    ): RedirectResponse {
        return $this->respond(
            $profileReference,
            'accept'
        );
    }

    /**
     * Decline an interest received from another member.
     */
    public function decline(
        string $profileReference
    ): RedirectResponse {
        return $this->respond(
            $profileReference,
            'decline'
        );
    }

    private function respond(
        string $profileReference,
        string $action
    ): RedirectResponse {
        $userId =
            $this->authenticatedUserId();

        try {
            /*
             * Re-resolve the public profile reference server-side.
             *
             * Never accept another member's numeric DB user ID
             * from the browser.
             */
            /** @var MemberProfileViewService $profileService */
            $profileService = service(
                'memberProfileViewService'
            );

            $sender = $profileService
                ->targetForAction(
                    $userId,
                    $profileReference
                );

            $senderUserId = max(
                0,
                (int) (
                    $sender['id']
                    ?? 0
                )
            );

            if ($senderUserId <= 0) {
                throw PageNotFoundException
                    ::forPageNotFound();
            }

            /** @var MemberInterestService $interestService */
            $interestService = service(
                'memberInterestService'
            );

            if ($action === 'accept') {
                $interestService
                    ->accept(
                        $senderUserId,
                        $userId
                    );

                return redirect()
                    ->to(
                        route_to(
                            'web.interests'
                        )
                            . '?direction=received'
                            . '&status=pending'
                    )
                    ->with(
                        'interestActionNotice',
                        [
                            'title' =>
                            'Interest Accepted',

                            'message' =>
                            'The interest has been accepted successfully.',
                        ]
                    );
            }

            $interestService
                ->decline(
                    $senderUserId,
                    $userId
                );

            return redirect()
                ->to(
                    route_to(
                        'web.interests'
                    )
                        . '?direction=received'
                        . '&status=pending'
                )
                ->with(
                    'interestActionNotice',
                    [
                        'title' =>
                        'Interest Declined',

                        'message' =>
                        'The interest has been declined successfully.',
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
                        'web.interests'
                    )
                        . '?direction=received'
                        . '&status=pending'
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
                'Interest response failed. '
                    . 'Member: {memberId}; '
                    . 'profile: {profileReference}; '
                    . 'action: {action}; '
                    . 'reason: {message}',
                [
                    'memberId' =>
                    $userId,

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
                        'web.interests'
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
                        'We could not update the interest. Please try again.',
                    ]
                );
        }
    }
}
