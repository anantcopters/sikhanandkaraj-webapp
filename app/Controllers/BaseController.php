<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Services\Notification\MemberNotificationService;
use Throwable;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Load here all helpers you want to be available in your controllers that extend BaseController.
        // Caution: Do not put the this below the parent::initController() call below.
        // $this->helpers = ['form', 'url'];

        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
        // $this->session = service('session');

        /*
        * Public pages and administrator pages do not require member
        * notification data.
        */
        if (
            session('is_authenticated') !== true
            || ! is_numeric(
                session('auth_user_id')
            )
        ) {
            return;
        }

        $memberUserId = (int) session(
            'auth_user_id'
        );

        try {
            /** @var MemberNotificationService $service */
            $service = service(
                'memberNotificationService'
            );

            $headerData = $service->getHeaderData(
                $memberUserId
            );

            /*
            * Messaging unread state comes from the actual member_messages table.
            *
            * Notification read state is independent and must not become the
            * messaging source of truth.
            */
            $headerData['unreadMessageCount'] =
                service(
                    'memberMessagingService'
                )->unreadCount(
                    $memberUserId
                );

            try {
                $membershipState =
                    service(
                        'membershipService'
                    )->resolveForUser(
                        $memberUserId
                    );

                $trustState =
                    service(
                        'memberTrustVerificationService'
                    )->getForUser(
                        $memberUserId
                    );

                $aadhaarStatus =
                    mb_strtoupper(
                        trim(
                            (string) (
                                $trustState['aadhaar']['status']
                                ?? 'NOT_ADDED'
                            )
                        )
                    );

                $showPaidAadhaarReminder =
                    ($membershipState['isPaid'] ?? false)
                    === true
                    && in_array(
                        $aadhaarStatus,
                        [
                            'NOT_ADDED',
                            'REJECTED',
                        ],
                        true
                    );

                service('renderer')->setData(
                    [
                        'showPaidAadhaarReminder' =>
                        $showPaidAadhaarReminder,

                        'paidAadhaarReminderStatus' =>
                        $aadhaarStatus,
                    ],
                    'raw'
                );
            } catch (Throwable $exception) {
                log_message(
                    'error',
                    'Unable to prepare paid Aadhaar reminder: {message}',
                    [
                        'message' =>
                        $exception->getMessage(),
                    ]
                );

                service('renderer')->setData(
                    [
                        'showPaidAadhaarReminder' =>
                        false,

                        'paidAadhaarReminderStatus' =>
                        '',
                    ],
                    'raw'
                );
            }

            /*
            * setData makes these values available to all views rendered during
            * this request, including Components/Header.php.
            */
            service('renderer')->setData(
                $headerData,
                'raw'
            );
        } catch (Throwable $exception) {
            /*
            * Header notification failure must not prevent members from using
            * the application.
            */
            log_message(
                'error',
                'Unable to prepare member notification header: {message}',
                [
                    'message' =>
                    $exception->getMessage(),
                ]
            );

            service('renderer')->setData(
                [
                    'unreadNotificationCount' => 0,
                    'unreadMessageCount' => 0,
                    'recentNotifications' => [],
                ],
                'raw'
            );
        }
    }

    /**
     * Return validation errors passed through redirect flashdata.
     *
     * Only string field names and scalar error messages are returned. This
     * prevents unexpected session data from being passed directly to views.
     *
     * @return array<string, string>
     */
    protected function readValidationErrors(): array
    {
        $validationErrors = session(
            'validationErrors'
        );

        if (! is_array($validationErrors)) {
            return [];
        }

        $normalizedErrors = [];

        foreach ($validationErrors as $field => $message) {
            if (
                ! is_string($field)
                || ! is_scalar($message)
            ) {
                continue;
            }

            $normalizedErrors[$field] =
                (string) $message;
        }

        return $normalizedErrors;
    }

    /**
     * Return a form alert passed through redirect flashdata.
     *
     * Only string keys and scalar values are returned so arbitrary session
     * structures are not passed directly to a view.
     *
     * @return array<string, string>|null
     */
    protected function readFormAlert(): ?array
    {
        $formAlert = session(
            'formAlert'
        );

        if (! is_array($formAlert)) {
            return null;
        }

        $normalizedAlert = [];

        foreach ($formAlert as $key => $value) {
            if (
                ! is_string($key)
                || ! is_scalar($value)
            ) {
                continue;
            }

            $normalizedAlert[$key] =
                (string) $value;
        }

        return $normalizedAlert !== []
            ? $normalizedAlert
            : null;
    }

    /**
     * Return a scalar flashdata value as a string.
     */
    protected function readFlashString(
        string $key,
        string $default = ''
    ): string {
        $value = session($key);

        return is_scalar($value)
            ? (string) $value
            : $default;
    }

    /**
     * Return normalized array flashdata.
     *
     * Only string keys and scalar values are retained. This prevents
     * arbitrary session structures from being passed directly to views.
     *
     * @return array<string, string>
     */
    protected function readArrayFlashData(
        string $key
    ): array {
        $value = session($key);

        if (! is_array($value)) {
            return [];
        }

        $normalizedData = [];

        foreach ($value as $field => $fieldValue) {
            if (
                ! is_string($field)
                || ! is_scalar($fieldValue)
            ) {
                continue;
            }

            $normalizedData[$field] =
                (string) $fieldValue;
        }

        return $normalizedData;
    }

    /**
     * Prevent the current response from being stored by the browser or an
     * intermediate cache.
     *
     * Authentication, verification, password-reset and other sensitive pages
     * should call this before rendering their view.
     */
    protected function preventPageCaching(): void
    {
        $this->response
            ->setHeader(
                'Cache-Control',
                'no-store, no-cache, must-revalidate, max-age=0'
            )
            ->setHeader(
                'Pragma',
                'no-cache'
            )
            ->setHeader(
                'Expires',
                '0'
            );
    }

    /**
     * Establish the authenticated member web session.
     *
     * The session identifier is regenerated before authenticated data is stored
     * to protect against session fixation.
     *
     * @param array<string, mixed> $user
     */
    protected function establishMemberSession(
        array $user
    ): void {
        $userId = $user['id'] ?? null;

        if (!is_numeric($userId)) {
            throw new \RuntimeException(
                'The authenticated user has an invalid identifier.'
            );
        }

        $fullName = trim(
            (string) (
                $user['full_name']
                ?? ''
            )
        );

        $profileReference = trim(
            (string) (
                $user['profile_ref_number']
                ?? ''
            )
        );

        session()->regenerate(true);

        session()->set([
            'is_authenticated' => true,

            'auth_user_id' =>
            (int) $userId,

            'auth_user_name' =>
            $fullName !== ''
                ? $fullName
                : 'Member',

            'auth_profile_reference' =>
            $profileReference,

            'authenticated_at' =>
            time(),
        ]);
    }

    /**
     * Determine whether the current browser session represents an
     * authenticated member.
     */
    protected function isAuthenticated(): bool
    {
        return session('is_authenticated') === true
            && is_numeric(
                session('auth_user_id')
            );
    }

    /**
     * Return the authenticated member identifier.
     *
     * Authenticated member routes normally pass through the webAuth filter.
     * This remains a defensive server-side check so controllers never operate
     * with an invalid member session.
     */
    protected function authenticatedUserId(): int
    {
        $userId = session(
            'auth_user_id'
        );

        if (!is_numeric($userId)) {
            session()->destroy();

            throw \CodeIgniter\Exceptions\PageNotFoundException
                ::forPageNotFound();
        }

        return (int) $userId;
    }
}
