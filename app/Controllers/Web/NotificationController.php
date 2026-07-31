<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Services\Notification\MemberNotificationService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use Throwable;

/**
 * Displays and updates notifications belonging to the logged-in member.
 */
final class NotificationController extends BaseController
{
    /**
     * Display all recent notifications.
     */
    public function index(): string
    {
        $memberUserId =
            $this->authenticatedMemberId();

        /** @var MemberNotificationService $service */
        $service = service(
            'memberNotificationService'
        );

        return view(
            'Pages/Notifications/Index',
            [
                'pageTitle' => 'Notifications',

                'notifications' =>
                $service
                    ->getMemberNotifications(
                        $memberUserId
                    ),
            ]
        );
    }

    /**
     * Mark one notification as read and redirect safely.
     */
    public function open(
        int $notificationId
    ): RedirectResponse {
        $memberUserId =
            $this->authenticatedMemberId();

        try {
            /** @var MemberNotificationService $service */
            $service = service(
                'memberNotificationService'
            );

            $targetUrl = $service->read(
                $notificationId,
                $memberUserId
            );

            if ($targetUrl === null) {
                return redirect()
                    ->to(
                        route_to(
                            'web.notifications'
                        )
                    )
                    ->with('formAlert', [
                        'type' => 'warning',
                        'title' =>
                        'Notification unavailable',
                        'message' =>
                        'The selected notification could not be found.',
                    ]);
            }

            return redirect()->to(
                site_url(
                    ltrim($targetUrl, '/')
                )
            );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Unable to open member notification: {message}',
                [
                    'message' =>
                    $exception->getMessage(),
                ]
            );

            return redirect()
                ->to(
                    route_to(
                        'web.notifications'
                    )
                )
                ->with('formAlert', [
                    'type' => 'danger',
                    'title' =>
                    'Notification unavailable',
                    'message' =>
                    'The notification could not be opened.',
                ]);
        }
    }

    /**
     * Mark all notifications as read.
     */
    public function readAll(): RedirectResponse
    {
        $memberUserId =
            $this->authenticatedMemberId();

        try {
            /** @var MemberNotificationService $service */
            $service = service(
                'memberNotificationService'
            );

            $service->readAll(
                $memberUserId
            );

            return redirect()
                ->to(
                    route_to(
                        'web.notifications'
                    )
                )
                ->with('formAlert', [
                    'type' => 'success',
                    'title' =>
                    'Notifications updated',
                    'message' =>
                    'All notifications have been marked as read.',
                ]);
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Unable to mark notifications as read: {message}',
                [
                    'message' =>
                    $exception->getMessage(),
                ]
            );

            return redirect()
                ->to(
                    route_to(
                        'web.notifications'
                    )
                )
                ->with('formAlert', [
                    'type' => 'danger',
                    'title' =>
                    'Update failed',
                    'message' =>
                    'Notifications could not be updated.',
                ]);
        }
    }

    private function authenticatedMemberId(): int
    {
        $memberUserId = session(
            'auth_user_id'
        );

        if (! is_numeric($memberUserId)) {
            throw PageNotFoundException
                ::forPageNotFound();
        }

        return (int) $memberUserId;
    }
}
